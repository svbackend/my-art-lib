<?php

declare(strict_types=1);

namespace App\Controller;

use App\Guests\Entity\GuestSession;
use App\Guests\Repository\GuestRepository;
use App\Pagination\PaginatedCollectionInterface;
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

    /**
     * @param $data
     * @param int   $status
     * @param array $headers
     * @param array $context
     *
     * @return JsonResponse
     */
    protected function response($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        if ($data instanceof PaginatedCollectionInterface) {
            $data = $this->addMetaPaginationInfo($data);
        }

        $contextWithRoles = $this->appendRolesToContextGroups($context);
        $translatedContent = $this->translateResponse($data, $contextWithRoles);

        return $this->json($translatedContent, $status, $headers, $context);
    }

    protected function getLocales()
    {
        return $this->getParameter('locales');
    }

    protected function getGuest(): ?GuestSession
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
        if (null === $this->getUser()) {
            return $context;
        }

        if (null === $context) {
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
}
