<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\RegistrationForm;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use FOS\RestBundle\View\ViewHandler;
use FOS\RestBundle\View\ViewHandlerInterface;
use FOS\RestBundle\View\View;

class UserController extends FOSRestController
{
    /**
     * @var ViewHandler
     */
    protected $viewHandler;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var UserPasswordEncoderInterface
     */
    protected $passwordEncoder;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        ValidatorInterface $validator,
        UserPasswordEncoderInterface $passwordEncoder,
        EntityManagerInterface $entityManager)
    {
        $this->viewHandler = $viewHandler;
        $this->validator = $validator;
        $this->passwordEncoder = $passwordEncoder;
        $this->em = $entityManager;
    }

    /**
     * Registration
     *
     * @Route("/api/users", methods={"POST"})
     * @SWG\Parameter(name="username", in="formData", type="string")
     * @SWG\Parameter(name="password", in="formData", type="string")
     * @SWG\Parameter(name="email", in="formData", type="string")
     * @SWG\Response(
     *     description="Registration.",
     *     response=202,
     *     @Model(type=User::class)
     * )
     */
    public function postUsers(Request $request)
    {
        $user = new User();
        $view = new View();

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_ANONYMOUSLY');

        $user->username = $request->get('username');
        $user->setPassword($request->get('password'), $this->passwordEncoder);
        $user->email = $request->get('email');

        $errors = $this->validator->validate($user);

        if (count($errors)) {
            $view->setStatusCode(400);
            $view->setData($errors);
        } else {
            $this->em->persist($user);
            $this->em->flush();
            $view->setData($user);
        }

        return $this->viewHandler->handle($view);
    }

    /**
     * Authentication
     *
     * @Route("/api/login", methods={"POST"})
     * @SWG\Response(
     *     description="Authentication.",
     *     response=202
     * )
     */
    public function login()
    {
        throw new NotFoundHttpException('This action should not be called!');
    }

    /**
     * Get single user
     *
     * @Route("/api/users/{id}", methods={"GET"})
     * @SWG\Response(
     *     description="REST action which returns user by id.",
     *     response=200,
     *     @Model(type=User::class)
     * )
     *
     * @param int $id
     * @return User
     */
    public function getUsers($id)
    {
        /**
         * @var $userRepository UserRepository
         */
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $user = $userRepository->find($id);

        if ($user === null) {
            throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $id));
        }

        return $user;
    }

    /**
     * Get all users
     *
     * @Route("/api/users", methods={"GET"})
     * @SWG\Response(
     *     description="REST action which returns user by id.",
     *     response=201,
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=User::class, groups={"full"}))
     *     )
     * )
     *
     * @return User[]
     */
    public function getAll()
    {
        /**
         * @var $userRepository UserRepository
         */
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $users = $userRepository->findAll();

        return $users;
    }
}