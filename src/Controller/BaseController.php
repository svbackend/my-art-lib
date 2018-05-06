<?php
declare(strict_types=1);

namespace App\Controller;

use App\Pagination\PaginatedCollection;
use App\Pagination\PaginatorBuilder;
use App\Translation\TranslatedResponseTrait;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class BaseController
 * @package App\Controller
 */
abstract class BaseController extends Controller implements ControllerInterface
{
    use TranslatedResponseTrait;

    /**
     * @param $data
     * @param int $status
     * @param array $headers
     * @param array $context
     * @return JsonResponse
     */
    protected function response($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        if ($data instanceof PaginatedCollection) {
            $data = $this->addMetaPaginationInfo($data);
        }

        $contextWithRoles = $this->appendRolesToContextGroups($context);
        $translatedContent = $this->translateResponse($data, $contextWithRoles);

        return $this->json($translatedContent, $status, $headers, $context);
    }

    private function addMetaPaginationInfo(PaginatedCollection $paginatedCollection)
    {
        return [
            'data' => $paginatedCollection->getItems(),
            'paging' => [
                'total' => $paginatedCollection->getTotal(),
                'offset' => $paginatedCollection->getOffset(),
                'limit' => $paginatedCollection->getLimit(),
            ]
        ];
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
}