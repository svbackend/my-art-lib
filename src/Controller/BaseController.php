<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

    public function __construct(NormalizerInterface $normalizer, RequestStack $requestStack)
    {
        $this->normalizer = $normalizer;
        $this->currentRequest = $requestStack->getCurrentRequest();
    }

    protected function response($data, int $status = 200, array $headers = array(), array $context = array())
    {
        $contextWithRoles = $this->appendRolesToContextGroups($context);

        $response = $this->normalizer->normalize($data, null, $contextWithRoles);
        $response = $this->translateEntities(is_array($response) ? $response : [$response]);

        return $this->json($response, $status, $headers, $context);
    }

    private function appendRolesToContextGroups(?array $context): array
    {
        if ($this->getUser() === null) return $context;

        if ($context === null) {
            return [
                'groups' => $this->getUser()->getRoles(),
            ];
        }

        if (isset($context['groups'])) {
            $context['groups'] = array_merge($context['groups'], $this->getUser()->getRoles());
        } else {
            $context['groups'] = $this->getUser()->getRoles();
        }

        return $context;
    }

    protected function translateEntities(array $data, $recursive = true): array
    {
        $translatedData = [];

        foreach ($data as $key => $value) {
            if ($key === 'translations') {
                $translatedData = array_merge($translatedData, $this->getEntityTranslation($value));

                if ($recursive === true) {
                    continue;
                } else {
                    break;
                }
            }

            if (is_array($value)) {
                $data[$key] = $this->translateEntities($value, $recursive);
            }
        }

        unset($data['translations']);
        $data = array_merge($data, $translatedData);

        return $data;
    }

    private function getEntityTranslation(array $translations)
    {
        $userLocale = $this->getUserPreferredLocale(array_keys($translations));
        return $translations[$userLocale];
    }

    private function getUserPreferredLocale(array $locales = [])
    {
        if (!isset($locales[0])) {
            // there's no translations for this entity
            throw new NotFoundHttpException();
        }

        $preferredLocale = $this->currentRequest->getPreferredLanguage($locales);

        $locale = $this->currentRequest->getLocale(); // can be set by query param (?language=ru) or by symfony
        if ($locale !== $this->currentRequest->getDefaultLocale() && in_array($locale, $locales) === true) {
            $preferredLocale = $locale;
        }

        return $preferredLocale;
    }
}