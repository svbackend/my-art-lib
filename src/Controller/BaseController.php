<?php

declare(strict_types=1);

namespace App\Controller;

use App\Guests\Entity\GuestSession;
use App\Guests\Repository\GuestRepository;
use App\Pagination\PaginatedCollectionInterface;
use App\Transformer\Transformer;
use App\Translation\TranslatedResponseTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BaseController.
 */
abstract class BaseController extends Controller implements ControllerInterface
{
    use TranslatedResponseTrait;

    public function items($data, Transformer $transformer): JsonResponse
    {
        return $this->response($data, 200, [], [], $transformer);
    }

    /**
     * @param $data
     * @param int   $status
     * @param array $headers
     * @param array $context
     * @param Transformer $transformer
     *
     * @return JsonResponse
     */
    public function response($data, int $status = 200, array $headers = [], array $context = [], ?Transformer $transformer = null): JsonResponse
    {
        if ($data instanceof PaginatedCollectionInterface) {
            $data = $this->addMetaPaginationInfo($data);
        }

        $contextWithRoles = $this->appendRolesToContextGroups($context);
        $response = $this->translateResponse($data, $contextWithRoles);
        if ($transformer !== null) {
            $response = $this->prepareResponseData($response, $transformer);
        }
        return $this->json($response, $status, $headers, $context);
    }

    public function getLocales()
    {
        return $this->getParameter('locales');
    }

    public function getGuest(): ?GuestSession
    {
        /** @var $request Request */
        $request = $this->get('request_stack')->getCurrentRequest();
        $guestSessionToken = (string) $request->get('guest_api_token', '');

        /** @var $guestRepository GuestRepository */
        $guestRepository = $this->getDoctrine()->getRepository(GuestSession::class);

        return $guestRepository->findOneBy([
            'token' => $guestSessionToken,
        ]);
    }

    private function addMetaPaginationInfo(PaginatedCollectionInterface $paginatedCollection)
    {
        return [
            'data' => $paginatedCollection->getItems(),
            'paging' => [
                'total' => $paginatedCollection->getTotal(),
                'offset' => $paginatedCollection->getOffset(),
                'limit' => $paginatedCollection->getLimit(),
            ],
        ];
    }

    private function appendRolesToContextGroups(?array $context): array
    {
        if ($this->getUser() === null) {
            return $context;
        }

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

    private function prepareResponseData(array $data, Transformer $transformer): array
    {
        $response = [];
        $data = $transformer->process($data);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                //$value = $transformer->process($value);
                $response[$key] = $this->prepareResponseData($value, $transformer);
                continue;
            }
            $response[$key] = $value;
        }

        return $response;
    }
}
