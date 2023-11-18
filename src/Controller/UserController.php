<?php

namespace App\Controller;

use App\Entity\User;
use OpenApi\Annotations as OA;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use App\Service\ErrorResponder;

class UserController extends AbstractController
{
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
        return $userPool->get('user-index-' . $customerUser->getId(), function () use ($content) {
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
    public function userDetail(int $userId, UserRepository $userRepository, AuthorizationCheckerInterface $authChecker, CacheInterface $userPool, ErrorResponder $errorResponder): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CUSTOMER'); //restrict access to customers and admin
        $user = $userRepository->findOneBy(['id' => $userId]);

        //if the asked user exists
        if ($user) {
            //if customer is the user owner or if admin - admin can display unowned user
            if ($user->getOwner() == $this->getUser() || $authChecker->isGranted('ROLE_ADMIN')) {
                //cache management
                return $userPool->get('user-detail-' . $user->getId(), function () use ($user) {
                    return $this->json($user, JsonResponse::HTTP_OK, [], ['groups' => 'user:index']); // code 200
                });
            }
            //in case the customer is not the user owner
            return $errorResponder->errorResponse(JsonResponse::HTTP_FORBIDDEN, 'Droit d\'affichage de cet utilisateur refusé'); // code 403
        }
        //in case the asked user don't exists
        return $errorResponder->errorResponse(JsonResponse::HTTP_NOT_FOUND, 'Cet utilisateur n\'existe pas'); // code 404
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
    public function userCreate(Request $request, UserRepository $userRepository, SerializerInterface $serializer, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator, CacheInterface $userPool, ErrorResponder $errorResponder): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CUSTOMER'); //restrict access to customers and admin
        $customerUser = $this->getUser();
        $receivedJson = $request->getContent();

        // Check if user have reached 20 simple users limit
        if ($userRepository->customerSimpleUsersCount($customerUser->getId()) >= 20) {
            return $errorResponder->errorResponse(JsonResponse::HTTP_FORBIDDEN, 'Création d\'utilisateur refusée - limite de 20 utilisateurs par client'); // code 403
        }

        try {
            $user = $serializer->deserialize($receivedJson, User::class, 'json');
            $user->setRoles(['ROLE_USER'])
                ->setOwner($customerUser);

            $violations = $validator->validate($user, null, ['create']);
            if (count($violations) > 0) {
                return $errorResponder->validationErrorsResponse($violations); //send errors details response
            }
            $userPasswordHash = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($userPasswordHash);

            $manager->persist($user);
            $manager->flush();
            $userPool->delete('user-index-' . $customerUser->getId()); //force cache expiration if cache exists

            $userLocation = $this->generateUrl('api_user_detail', ['userId' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            return $this->json($user, JsonResponse::HTTP_CREATED, ['Location' => $userLocation], ['groups' => 'user:index']); // code 201
        } catch (NotEncodableValueException $e) {
            return $errorResponder->errorResponse(JsonResponse::HTTP_BAD_REQUEST, $e->getMessage()); // code 400
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
    public function userPasswordUpdate(int $userId, Request $request, SerializerInterface $serializer, UserRepository $userRepository, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator, ErrorResponder $errorResponder): JsonResponse
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
                        return $errorResponder->validationErrorsResponse($violations); //send errors details response
                    }
                    $userPasswordHash = $passwordHasher->hashPassword($user, $receivedUser->getPassword());
                    $user->setPassword($userPasswordHash);
                    $manager->persist($user);
                    $manager->flush();

                    return $this->json(null, JsonResponse::HTTP_NO_CONTENT); // code 204
                } catch (NotEncodableValueException $e) {
                    return $errorResponder->errorResponse(JsonResponse::HTTP_BAD_REQUEST, $e->getMessage()); // code 400
                }
            }
            //in case the customer or admin is not the user owner
            return $errorResponder->errorResponse(JsonResponse::HTTP_FORBIDDEN, 'Droit de modification de cet utilisateur refusé'); // code 403
        }
        //in case the asked user don't exists
        return $errorResponder->errorResponse(JsonResponse::HTTP_NOT_FOUND, 'Cet utilisateur n\'existe pas'); // code 404
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
    public function userDelete(int $userId, UserRepository $userRepository, EntityManagerInterface $manager, AuthorizationCheckerInterface $authChecker, CacheInterface $userPool, ErrorResponder $errorResponder): JsonResponse
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
            return $errorResponder->errorResponse(JsonResponse::HTTP_FORBIDDEN, 'Droit de suppression de cet utilisateur refusé'); // code 403
        }
        //in case the asked user don't exists
        return $errorResponder->errorResponse(JsonResponse::HTTP_NOT_FOUND, 'Cet utilisateur n\'existe pas'); // code 404
    }
}
