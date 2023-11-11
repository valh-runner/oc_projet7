<?php

namespace App\Controller;

use App\Entity\User;
use OpenApi\Annotations as OA;
use App\Repository\UserRepository;
use App\Repository\BrandRepository;
use App\Repository\ProductRepository;
use Psr\Cache\CacheItemPoolInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AppController extends AbstractController
{
    /**
     * Authentication
     * 
     * @OA\Post(
     *      path="/api/login_check",
     *      description="Ask for an authentication token",
     *      tags={"Login"},
     *      security={},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"username", "password"},
     *              @OA\Property(type="string", property="username", example="T.Wellick"),
     *              @OA\Property(type="string", property="password", example="19N1t10n%")
     *          )
     *      ),
     *      @OA\Response(
     *          response="200", description="OK - Success",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="token",
     *                  type="string",
     *                  example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MzI4NDI2NTgsImV4cCI6MTYzMjg0NjI1OCwicm9sZXMiOlsiUk9MRV9DVVNUT01FUiJdLCJ1c2VybmFtZSI6IlBzYUZyYW5jZSJ9.lpgfnOpZQ13Q-HtOCqtRdD8ChcI3NlWpTsiK9Ff6LlXiC7ih89tvxyPgj5xKeYFQQZlLS7838ukT_QnWdjbyEmG8NvZD5mD-jmMKBTGaEGMiVs8c0J7OcrFb_duKe8-9cmho3j1DvODzVXOqDTYL3J-C-2Qg0bW4RzCT1rVxgvAcFGHG5MbpFyvwft96V-ZablsmTE_7a3rkFnc8mvcWW4ivVr2UDKxcZKa9dQWXO72KMKF7-7eXEFmJSSXfRc9kUo-rXA9IWnJ9upzXWQeDP-0Ccgmzl6ukMzowUS2NsYwaHq7cmhB4wCTqTA8ubgpjYWbZiTxts0rbOjY5KW_0plB7VPrIarBSmi4rmjK0RJ68-MDrofpyMGy32S_TnBM0xN4cNqozxL0Rx9OxvyYnaxxSZ8NFOFQfABAevulCCG68jhXcEA6qknJ6OXVD3zESbFNYbkvt8iO48CuUXoRVb7Xv-mokrJW9CQpA7Aiz2M7vXYbEAIfux012Y_ZnTfDPseikYYT8xKFEGOFZ-oeejx15y7GbF5VLhV-IEisYC7Z7rTdsaa6OXt7sfJ1Ux9Y98bksWqncgOBddUwbGC9-r2cWF2V0C8-zcjHsfNfP5jttHgpbsEMguguX_al6QARYRnUu3_CrGqgQD2mh8EO4AX3EaswfuV-6IldyL7z3A7A"
     *              )
     *          )
     *      ),
     *      @OA\Response(response="401", ref="#/components/responses/LoginUnauthorized"),
     *      @OA\Response(response="400", ref="#/components/responses/LoginBadRequest")
     * )
     */
    /**
     * Products index
     *
     * @OA\Get(
     *      path="/api/products",
     *      description="Obtain a list of products",
     *      tags={"Products"},
     *      security={{"Bearer": {}}},
     *      @OA\Parameter(
     *          name="brand", in="query", description="Refine to a specific brand", required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="order", in="query", description="Define an order sort", required=false,
     *          @OA\Schema(type="string", enum={"asc", "desc"}, default="asc")
     *      ),
     *      @OA\Parameter(
     *          name="page", in="query", description="Page number to ask", required=false,
     *          @OA\Schema(type="integer", default="1")
     *      ),
     *      @OA\Response(
     *          response="200", description="OK - Success",
     *          @OA\JsonContent(ref="#/components/schemas/ProductIndexList")
     *      ),
     *      @OA\Response(response="400", ref="#/components/responses/ProductBadRequest"),
     *      @OA\Response(response="403", ref="#/components/responses/ProductActionForbidden"),
     *      @OA\Response(response="401", ref="#/components/responses/AccessUnauthorized")
     * )
     *
     * @return JsonResponse
     * @Route("/api/products", name="api_product_index", methods={"GET"}, requirements={"page"="\d+"}, stateless=true)
     */
    public function productIndex(Request $request, CacheItemPoolInterface $productPool): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER'); //restrict access to users and admin

        // get params initialisation
        $getParams = [
            'brand' => $request->query->get('brand', 'all'),
            'order' => $request->query->get('order', 'asc'),
            'page' => (int) $request->query->get('page', '1')
        ];

        //cache management
        $itemKey = 'product-index-' . $getParams['brand'] . '-' . $getParams['order'] . '-' . $getParams['page'];
        $productIndexItem = $productPool->getItem($itemKey);
        if (!$productIndexItem->isHit()) {
            $productIndexItem->set($this->productIndexProcess($getParams));
            $productPool->save($productIndexItem);
        }
        return $productIndexItem->get();
    }

    /**
     * Product detail
     *
     * @OA\Get(
     *      path="/api/products/{productId}",
     *      description="Obtain the detail of a product",
     *      tags={"Products"},
     *      security={{"Bearer": {}}},
     *      @OA\Parameter(
     *          name="productId", in="path", description="Id of asked product", required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response="200", description="OK - Success",
     *          @OA\JsonContent(ref="#/components/schemas/ProductRead")
     *      ),
     *      @OA\Response(response="404", ref="#/components/responses/ProductNotFound"),
     *      @OA\Response(response="403", ref="#/components/responses/ProductActionForbidden"),
     *      @OA\Response(response="401", ref="#/components/responses/AccessUnauthorized")
     * )
     *
     * @return JsonResponse
     * @Route("/api/products/{productId<\d+>}", name="api_product_detail", methods={"GET"}, stateless=true)
     */
    public function productDetail(int $productId, ProductRepository $productRepository, CacheItemPoolInterface $productPool): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER'); //restrict access to users and admin
        $product = $productRepository->findOneBy(['id' => $productId]);
        //if the asked product exists
        if ($product) {
            //cache management
            $itemKey = 'product-detail-' . $product->getId();
            $productDetailItem = $productPool->getItem($itemKey);
            if (!$productDetailItem->isHit()) {
                $productDetailItem->set(
                    $this->json($product, JsonResponse::HTTP_OK, [], ['groups' => 'product:read']) // code 200
                );
                $productPool->save($productDetailItem);
            }
            return $productDetailItem->get();
        }
        //in case the asked product don't exists
        return $this->errorResponse(JsonResponse::HTTP_NOT_FOUND, 'Ce produit n\'existe pas'); // code 404
    }

    /**
     * Users index
     *
     * @OA\Get(
     *      path="/api/users",
     *      description="Obtain the list of owned simple users",
     *      tags={"Users"},
     *      security={{"Bearer": {}}},
     *      @OA\Response(
     *          response="200", description="OK - Success",
     *          @OA\JsonContent(ref="#/components/schemas/UserIndexList")
     *      ),
     *      @OA\Response(response="403", ref="#/components/responses/UserActionForbidden"),
     *      @OA\Response(response="401", ref="#/components/responses/AccessUnauthorized")
     * )
     *
     * @return JsonResponse
     * @Route("/api/users", name="api_user_index", methods={"GET"}, stateless=true)
     */
    public function userIndex(UserRepository $userRepository, CacheInterface $userPool): JsonResponse
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

        //cache management
        return $userPool->get('user-index-' . $customerUser->getId(), function (ItemInterface $item) use ($content) {
            return $this->json($content, JsonResponse::HTTP_OK, [], ['groups' => 'user:index']); // code 200
        });
    }

    /**
     * User detail
     *
     * @OA\Get(
     *      path="/api/users/{userId}",
     *      description="Obtain the detail of a user you own",
     *      tags={"Users"},
     *      security={{"Bearer": {}}},
     *      @OA\Parameter(
     *          name="userId", in="path", description="Id of user to read", required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response="200", description="OK - Success",
     *          @OA\JsonContent(ref="#/components/schemas/UserIndex")
     *      ),
     *      @OA\Response(response="404", ref="#/components/responses/UserNotFound"),
     *      @OA\Response(response="403", ref="#/components/responses/UserActionForbidden"),
     *      @OA\Response(response="401", ref="#/components/responses/AccessUnauthorized")
     * )
     *
     * @return JsonResponse
     * @Route("/api/users/{userId<\d+>}", name="api_user_detail", methods={"GET"}, stateless=true)
     */
    public function userDetail(int $userId, UserRepository $userRepository, AuthorizationCheckerInterface $authChecker, CacheInterface $userPool): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CUSTOMER'); //restrict access to customers and admin
        $user = $userRepository->findOneBy(['id' => $userId]);

        //if the asked user exists
        if ($user) {
            //if customer is the user owner or if admin - admin can display unowned user
            if ($user->getOwner() == $this->getUser() || $authChecker->isGranted('ROLE_ADMIN')) {
                //cache management
                return $userPool->get('user-detail-' . $user->getId(), function (ItemInterface $item) use ($user) {
                    return $this->json($user, JsonResponse::HTTP_OK, [], ['groups' => 'user:index']); // code 200
                });
            }
            //in case the customer is not the user owner
            return $this->errorResponse(JsonResponse::HTTP_FORBIDDEN, 'Droit d\'affichage de cet utilisateur refusé'); // code 403
        }
        //in case the asked user don't exists
        return $this->errorResponse(JsonResponse::HTTP_NOT_FOUND, 'Cet utilisateur n\'existe pas'); // code 404
    }

    /**
     * User creation
     *
     * @OA\Post(
     *      path="/api/users",
     *      description="Create a simple user you own",
     *      tags={"Users"},
     *      security={{"Bearer": {}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"username", "password"},
     *              @OA\Property(type="string", property="username", example="T.Wellick"),
     *              @OA\Property(type="string", property="password", example="19N1t10n%")
     *          )
     *      ),
     *      @OA\Response(
     *          response="201", description="Created - Success",
     *          @OA\Header(
     *              header="Location", description="location of created user",
     *              @OA\Schema(type="string", example="https://localhost:8000/api/users/12")
     *          ),
     *          @OA\JsonContent(ref="#/components/schemas/UserIndex")
     *      ),
     *      @OA\Response(response="400", ref="#/components/responses/UserCreateBadRequest"),
     *      @OA\Response(response="403", ref="#/components/responses/UserCreateForbidden"),
     *      @OA\Response(response="401", ref="#/components/responses/AccessUnauthorized")
     * )
     *
     * @return JsonResponse
     * @Route("/api/users", name="api_user_create", methods={"POST"}, stateless=true)
     */
    public function userCreate(Request $request, UserRepository $userRepository, SerializerInterface $serializer, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator, CacheInterface $userPool): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CUSTOMER'); //restrict access to customers and admin
        $customerUser = $this->getUser();
        $receivedJson = $request->getContent();

        // Check if user have reached 20 simple users limit
        if ($userRepository->customerSimpleUsersCount($customerUser->getId()) >= 20) {
            return $this->errorResponse(JsonResponse::HTTP_FORBIDDEN, 'Création d\'utilisateur refusée - limite de 20 utilisateurs par client'); // code 403
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
            $userPool->delete('user-index-' . $customerUser->getId()); //force cache expiration if cache exists

            $userLocation = $this->generateUrl('api_user_detail', ['userId' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            return $this->json($user, JsonResponse::HTTP_CREATED, ['Location' => $userLocation], ['groups' => 'user:index']); // code 201
        } catch (NotEncodableValueException $e) {
            return $this->errorResponse(JsonResponse::HTTP_BAD_REQUEST, $e->getMessage()); // code 400
        }
    }

    /**
     * User update
     *
     * @OA\Put(
     *      path="/api/users/{userId}",
     *      description="Update the password of a simple user you own",
     *      tags={"Users"},
     *      security={{"Bearer": {}}},
     *      @OA\Parameter(
     *          name="userId", in="path", description="Id of user to update", required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"password"},
     *              @OA\Property(type="string", property="password", example="%Ex0g3n%")
     *          )
     *      ),
     *      @OA\Response(response="204", description="No content - Success"),
     *      @OA\Response(response="400", ref="#/components/responses/UserUpdateBadRequest"),
     *      @OA\Response(response="403", ref="#/components/responses/UserUpdateForbidden"),
     *      @OA\Response(response="404", ref="#/components/responses/UserNotFound"),
     *      @OA\Response(response="401", ref="#/components/responses/AccessUnauthorized")
     * )
     *
     * @return JsonResponse
     * @Route("/api/users/{userId<\d+>}", name="api_user_password_update", methods={"PUT"}, stateless=true)
     */
    public function userPasswordUpdate(int $userId, Request $request, SerializerInterface $serializer, UserRepository $userRepository, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CUSTOMER'); //restrict access to customers and admin
        $user = $userRepository->findOneBy(['id' => $userId]);
        $receivedJson = $request->getContent();

        //if the asked user exists
        if ($user) {
            $userOwner = $user->getOwner();
            //if customer or admin is the user owner
            if ($userOwner == $this->getUser()) {

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

                    return $this->json(null, JsonResponse::HTTP_NO_CONTENT); // code 204
                } catch (NotEncodableValueException $e) {
                    return $this->errorResponse(JsonResponse::HTTP_BAD_REQUEST, $e->getMessage()); // code 400
                }
            }
            //in case the customer or admin is not the user owner
            return $this->errorResponse(JsonResponse::HTTP_FORBIDDEN, 'Droit de modification de cet utilisateur refusé'); // code 403
        }
        //in case the asked user don't exists
        return $this->errorResponse(JsonResponse::HTTP_NOT_FOUND, 'Cet utilisateur n\'existe pas'); // code 404
    }

    /**
     * User deletion
     *
     * @OA\Delete(
     *      path="/api/users/{userId}",
     *      description="Delete a simple user you own",
     *      tags={"Users"},
     *      security={{"Bearer": {}}},
     *      @OA\Parameter(
     *          name="userId", in="path", description="Id of user to delete", required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(response="204", description="No content - Success"),
     *      @OA\Response(response="403", ref="#/components/responses/UserDeleteForbidden"),
     *      @OA\Response(response="404", ref="#/components/responses/UserNotFound"),
     *      @OA\Response(response="401", ref="#/components/responses/AccessUnauthorized")
     * )
     *
     * @return JsonResponse
     * @Route("/api/users/{userId<\d+>}", name="api_user_delete", methods={"DELETE"}, stateless=true)
     */
    public function userDelete(int $userId, UserRepository $userRepository, EntityManagerInterface $manager, AuthorizationCheckerInterface $authChecker, CacheInterface $userPool): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CUSTOMER'); //restrict access to customers and admin
        $user = $userRepository->findOneBy(['id' => $userId]);

        //if the asked user exists
        if ($user) {
            $userOwner = $user->getOwner();
            //if customer is the user owner or if admin - admin can delete unowned user for security reasons
            if ($userOwner == $this->getUser() || $authChecker->isGranted('ROLE_ADMIN')) {
                $userPool->delete('user-index-' . $userOwner->getId()); //force cache expiration if cache exists
                $userPool->delete('user-detail-' . $user->getId()); //force cache expiration if cache exists
                $manager->remove($user);
                $manager->flush();
                return $this->json(null, JsonResponse::HTTP_NO_CONTENT); // code 204
            }
            //in case the customer is not the user owner
            return $this->errorResponse(JsonResponse::HTTP_FORBIDDEN, 'Droit de suppression de cet utilisateur refusé'); // code 403
        }
        //in case the asked user don't exists
        return $this->errorResponse(JsonResponse::HTTP_NOT_FOUND, 'Cet utilisateur n\'existe pas'); // code 404
    }

    /**
     * Respond a description of validation errors
     *
     * @return JsonResponse
     */
    private function validationErrorsResponse(ConstraintViolationListInterface $violations): JsonResponse
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
            'code' => JsonResponse::HTTP_BAD_REQUEST,
            'errors' => $errors
        ], JsonResponse::HTTP_BAD_REQUEST); // code 400
    }

    /**
     * Respond a custom description of error
     *
     * @return JsonResponse
     */
    private function errorResponse(int $statusCode, string $message): JsonResponse
    {
        return $this->json([
            'code' => $statusCode,
            'message' => $message
        ], $statusCode);
    }

    /**
     * Main logic of productIndex
     *
     * @return JsonResponse
     */
    private function productIndexProcess(array $getParams, ProductRepository $productRepository, BrandRepository $brandRepository, ValidatorInterface $validator): JsonResponse
    {
        $violations = $this->productIndexValidation($getParams);
        if (count($violations) > 0) {
            return $this->validationErrorsResponse($violations); //send errors details response
        }

        // data retrieve
        $PageItemsLimit = 5;
        $fullProductsCount = $productRepository->unpaginatedSearchCount($getParams['brand']);
        $currentProducts = $productRepository->paginatedSearch($getParams['brand'], $getParams['order'], $PageItemsLimit, $getParams['page']);
        $lastPage = ceil($fullProductsCount / $PageItemsLimit);

        //if asked page is out of bound
        if ($getParams['page'] > $lastPage) {
            return $this->errorResponse(JsonResponse::HTTP_NOT_FOUND, 'La page n\'existe pas'); // code 404
        }

        // response content build
        $content = [
            'meta' => [
                'totalPaginatedItems' => $fullProductsCount,
                'maxItemsPerPage' => $PageItemsLimit,
                'lastPage' => $lastPage,
                'currentPage' => $getParams['page'],
                'currentPageItems' => count($currentProducts),
                'availableBrands' => $brandRepository->getBrandsNames()
            ],
            'data' => $currentProducts
        ];

        return $this->json($content, JsonResponse::HTTP_OK, [], ['groups' => 'product:index']) // code 200
    }

    /**
     * Validation of productIndex query params
     *
     * @return ConstraintViolationListInterface
     */
    private function productIndexValidation(array $getParams): ConstraintViolationListInterface
    {
        // query params validation
        $validator = Validation::createValidator();
        $constraint = new Assert\Collection([
            'brand' => new Assert\Optional([
                new Assert\NotBlank(null, 'La valeur ne doit pas être vide'),
                new Assert\Type('string', 'Chaîne de caratères attendue')
            ]),
            'order' => [
                new Assert\NotBlank(null, 'La valeur ne doit pas être vide'),
                new Assert\Choice(['choices' => ['asc', 'desc'], 'message' => 'Valeur erronée'])
            ],
            'page' => [
                new Assert\NotBlank(null, 'La valeur ne doit pas être vide'),
                new Assert\Regex('#^[1-9]\d*$#', 'Nombre entier positif attendu - zéro exclu')
            ]
        ]);
        return $validator->validate($getParams, $constraint);
    }
}
