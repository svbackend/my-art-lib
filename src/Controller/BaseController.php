<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class BaseController
 * @package App\Controller
 */
abstract class BaseController extends Controller implements ControllerInterface
{
    protected $serializer;
    protected $normalizer;
    protected $currentRequest;

    public function __construct(SerializerInterface $serializer, NormalizerInterface $normalizer)
    {
        $this->serializer = $serializer;
        $this->normalizer = $normalizer;
    }

    protected function response($data, int $status = 200, array $headers = array(), array $context = array())
    {
        $response = $this->normalizer->normalize($data, null, $context);
        $response = $this->translateEntities(is_array($response) ? $response : [$response]);

        return $this->json($response, $status, $headers, $context);
    }

    protected function translateEntities(array $data, $recursive = true): array
    {
        $translatedData = [];

        foreach ($data as $key => $value) {
            if ($key === 'translations') {
                $translatedData = array_merge($translatedData, $this->getEntityTranslation($value));
            }
        }

        echo '<pre>';
        echo var_export($data); exit;
    }

    private function getEntityTranslation(array $translations)
    {
        $locale = $this->currentRequest->getLocale() ?? $this->currentRequest->getDefaultLocale();

        if (isset($translations[$locale])) return $translations[$locale];

        echo var_export($this->currentRequest->getLanguages()); exit;
    }
}