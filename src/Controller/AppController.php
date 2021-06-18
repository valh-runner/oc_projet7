<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\BrandRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AppController extends AbstractController
{
    /**
     * @Route("/api/products", name="api_product_index", methods={"GET"}, requirements={"page"="\d+"})
     */
    public function productIndex(Request $request, ProductRepository $productRepository, BrandRepository $brandRepository, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER'); //restrict access to users and admin

        // get params initialisation
        $getParams = [
            'brand' => $request->query->get('brand', 'all'),
            'order' => $request->query->get('order', 'asc'),
            'page' => $request->query->get('page', '1')
        ];

        // query params validation
        $validator = Validation::createValidator();
        $constraint = new Assert\Collection([
            'brand' => new Assert\Optional([
                new Assert\NotBlank(null, 'la valeur ne doit pas être vide'),
                new Assert\Type('string', 'chaîne de caratères attendue')
            ]),
            'order' => [
                new Assert\NotBlank(null, 'la valeur ne doit pas être vide'),
                new Assert\Choice(['choices' => ['asc', 'desc'], 'message' => 'valeur erronée'])
            ],
            'page' => [
                new Assert\NotBlank(null, 'la valeur ne doit pas être vide'),
                new Assert\Regex('#^[1-9]\d*$#', 'nombre entier positif attendu - zéro exclu')
            ]
        ]);
        $violations = $validator->validate($getParams, $constraint);
        if (count($violations) > 0) {
            return $this->validationErrorsResponse($violations); //send errors details response
        }
        $getParams['page'] = (int) $getParams['page']; //set asked page as int

        // data retrieve
        $PageItemsLimit = 5;
        $fullProductsCount = $productRepository->unpaginatedSearchCount($getParams['brand']);
        $currentProducts = $productRepository->paginatedSearch($getParams['brand'], $getParams['order'], $PageItemsLimit, $getParams['page']);
        $lastPage = ceil($fullProductsCount / $PageItemsLimit);
        $currentProductsCount = count($currentProducts);

        //if asked page is out of bound
        if ($getParams['page'] > $lastPage) {
            return $this->errorResponse(Response::HTTP_BAD_REQUEST, 'La page n\'existe pas'); // code 400
        }

        // available brands content format
        $brands = $brandRepository->findAll();
        $brandsNames = array_map(function ($value) {
            return $value->getName();
        }, $brands);

        // response content build
        $content = [
            'meta' => [
                'totalPaginatedItems' => $fullProductsCount,
                'maxItemsPerPage' => $PageItemsLimit,
                'lastPage' => $lastPage,
                'currentPage' => $getParams['page'],
                'currentPageItems' => $currentProductsCount,
                'availableBrands' => $brandsNames
            ],
            'data' => $currentProducts
        ];
        return $this->json($content, Response::HTTP_OK, [], ['groups' => 'product:index']); // code 200
    }

    /**
     * @Route("/api/products/{productId<\d+>}", name="api_product_detail", methods={"GET"})
     */
    public function productDetail(int $productId, ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER'); //restrict access to users and admin
        $product = $productRepository->findOneBy(['id' => $productId]);
        //if the asked product exist
        if ($product) {
            return $this->json($product, Response::HTTP_OK, [], ['groups' => 'product:read']); // code 200
        }
        //in case the asked product don't exist
        return $this->errorResponse(Response::HTTP_BAD_REQUEST, 'ce produit n\'existe pas'); // code 400
    }

    /**
     * @Route("/api/users", name="api_user_index", methods={"GET"})
     */
    public function userIndex(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CUSTOMER'); //restrict access to customers and admin
        $customerUser = $this->getUser();
        $ownedUsers = $userRepository->findOwnedUsersOfUser($customerUser);
        $ownedUsersCount = count($ownedUsers);

        // response content build
        $content = [
            'meta' => ['totalItems' => $ownedUsersCount],
            'data' => $ownedUsers
        ];
        return $this->json($content, Response::HTTP_OK, [], ['groups' => 'user:index']); // code 200
    }

    /**
     * @Route("/api/users/{userId<\d+>}", name="api_user_detail", methods={"GET"})
     */
    public function userDetail(int $userId, UserRepository $userRepository, AuthorizationCheckerInterface $authChecker): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CUSTOMER'); //restrict access to customers and admin
        $user = $userRepository->findOneBy(['id' => $userId]);

        //if the asked user exist
        if ($user) {
            //if customer is the user owner or if admin - admin can display unowned user
            if ($user->getOwner() == $this->getUser() || $authChecker->isGranted('ROLE_ADMIN')) {
                return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user:index']); // code 200
            }
            //in case the customer is not the user owner
            return $this->errorResponse(Response::HTTP_FORBIDDEN, 'droit d\'affichage de cet utilisateur refusé'); // code 403
        }
        //in case the asked user don't exist
        return $this->errorResponse(Response::HTTP_BAD_REQUEST, 'cet utilisateur n\'existe pas'); // code 400
    }

    /**
     * @Route("/api/users", name="api_user_create", methods={"POST"})
     */
    public function userCreate(Request $request, UserRepository $userRepository, SerializerInterface $serializer, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CUSTOMER'); //restrict access to customers and admin
        $customerUser = $this->getUser();
        $receivedJson = $request->getContent();

        // Check if user have reached 20 simple users limit
        if ($userRepository->customerSimpleUsersCount($customerUser->getId()) >= 20) {
            return $this->errorResponse(Response::HTTP_FORBIDDEN, 'création d\'utilisateur refusée - limite de 20 utilisateurs par client'); // code 403
        }

        try {
            $user = $serializer->deserialize($receivedJson, User::class, 'json');
            $user->setRoles(['ROLE_USER'])
                ->setOwner($customerUser);

            $violations = $validator->validate($user, null, ['create']);
            if (count($violations) > 0) {
                return $this->validationErrorsResponse($violations); //send errors details response
            }
            $userPasswordHash = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($userPasswordHash);

            $manager->persist($user);
            $manager->flush();

            $userLocation = $this->generateUrl('api_user_detail', ['userId' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            return $this->json($user, Response::HTTP_CREATED, ['Location' => $userLocation], ['groups' => 'user:index']); // code 201
        } catch (NotEncodableValueException $e) {
            return $this->errorResponse(Response::HTTP_BAD_REQUEST, $e->getMessage()); // code 400
        }
    }

    /**
     * @Route("/api/users/{userId<\d+>}", name="api_user_password_update", methods={"PUT"})
     */
    public function userPasswordUpdate(int $userId, Request $request, SerializerInterface $serializer, UserRepository $userRepository, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CUSTOMER'); //restrict access to customers and admin
        $user = $userRepository->findOneBy(['id' => $userId]);
        $receivedJson = $request->getContent();

        //if the asked user exist
        if ($user) {
            //if customer or admin is the user owner
            if ($user->getOwner() == $this->getUser()) {

                try {
                    $receivedUser = $serializer->deserialize($receivedJson, User::class, 'json');
                    $violations = $validator->validate($receivedUser, null, ['update']);
                    if (count($violations) > 0) {
                        return $this->validationErrorsResponse($violations); //send errors details response
                    }
                    $userPasswordHash = $passwordHasher->hashPassword($user, $receivedUser->getPassword());
                    $user->setPassword($userPasswordHash);

                    $manager->persist($user);
                    $manager->flush();
                    return $this->json(null, Response::HTTP_NO_CONTENT); // code 204

                } catch (NotEncodableValueException $e) {
                    return $this->errorResponse(Response::HTTP_BAD_REQUEST, $e->getMessage()); // code 400
                }
            }
            //in case the customer or admin is not the user owner
            return $this->errorResponse(Response::HTTP_FORBIDDEN, 'droit de modification de cet utilisateur refusé'); // code 403
        }
        //in case the asked user don't exist
        return $this->errorResponse(Response::HTTP_BAD_REQUEST, 'cet utilisateur n\'existe pas'); // code 400
    }

    /**
     * @Route("/api/users/{userId<\d+>}", name="api_user_delete", methods={"DELETE"})
     */
    public function userDelete(int $userId, UserRepository $userRepository, EntityManagerInterface $manager, AuthorizationCheckerInterface $authChecker): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CUSTOMER'); //restrict access to customers and admin
        $user = $userRepository->findOneBy(['id' => $userId]);

        //if the asked user exist
        if ($user) {
            //if customer is the user owner or if admin - admin can delete unowned user for security reasons
            if ($user->getOwner() == $this->getUser() || $authChecker->isGranted('ROLE_ADMIN')) {
                $manager->remove($user);
                $manager->flush();
                return $this->json(null, Response::HTTP_NO_CONTENT); // code 204
            }
            //in case the customer is not the user owner
            return $this->errorResponse(Response::HTTP_FORBIDDEN, 'droit de suppression de cet utilisateur refusé'); // code 403
        }
        //in case the asked user don't exist
        return $this->errorResponse(Response::HTTP_BAD_REQUEST, 'cet utilisateur n\'existe pas'); // code 400
    }

    private function validationErrorsResponse($violations)
    {
        $errors = [];
        for ($i = 0; $i < $violations->count(); $i++) {
            $violation = $violations->get($i);
            $errors[] = [
                'propertyPath' => $violation->getPropertyPath(),
                'message' => $violation->getMessage()
            ];
        }
        return $this->json([
            'status' => Response::HTTP_BAD_REQUEST,
            'errors' => $errors
        ], Response::HTTP_BAD_REQUEST); // code 400
    }

    private function errorResponse($statusCode, $message)
    {
        return $this->json([
            'statusCode' => $statusCode,
            'message' => $message
        ], $statusCode);
    }
}
