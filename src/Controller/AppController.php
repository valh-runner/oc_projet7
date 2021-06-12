<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AppController extends AbstractController
{
    /**
     * @Route("/api/products", name="api_product_index", methods={"GET"})
     */
    public function index(ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER'); //restrict access to users and admin
        $products = $productRepository->findAll();
        return $this->json($products, Response::HTTP_OK, [], ['groups' => 'product:index']); // code 200
    }

    /**
     * @Route("/api/products/{productId<\d+>}", name="api_product_detail", methods={"GET"})
     */
    public function detail(int $productId, ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER'); //restrict access to users and admin
        $product = $productRepository->findOneBy(['id' => $productId]);
        return $this->json($product, Response::HTTP_OK, [], ['groups' => 'product:read']); // code 200
    }

    /**
     * @Route("/api/users", name="api_user_index", methods={"GET"})
     */
    public function userIndex(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CUSTOMER'); //restrict access to customers and admin
        $customerUser = $this->getUser();
        $ownedUsers = $userRepository->findOwnedUsersOfUser($customerUser);
        return $this->json($ownedUsers, Response::HTTP_OK, [], ['groups' => 'user:index']); // code 200
    }

    /**
     * @Route("/api/users", name="api_user_create", methods={"POST"})
     */
    public function userCreate(Request $request, SerializerInterface $serializer, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CUSTOMER'); //restrict access to customers and admin
        $customerUser = $this->getUser();
        $receivedJson = $request->getContent();

        try {
            $user = $serializer->deserialize($receivedJson, User::class, 'json');
            $user->setRoles(['ROLE_USER'])
                ->setOwner($customerUser);

            $errors = $validator->validate($user, null, ['creation']);
            if (count($errors) > 0) {
                $formatedViolationList = [];
                for ($i = 0; $i < $errors->count(); $i++) {
                    $violation = $errors->get($i);
                    $formatedViolationList[] = ['propertyPath' => $violation->getPropertyPath(), 'message' => $violation->getMessage()];
                }
                return $this->json([
                    'status' => Response::HTTP_BAD_REQUEST,
                    'message' => $formatedViolationList
                ], Response::HTTP_BAD_REQUEST); // code 400
            }
            $simpleUserPasswordHash = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($simpleUserPasswordHash);

            $manager->persist($user);
            $manager->flush();

            $userLocation = '/api/users/' . $user->getId();
            return $this->json($user, Response::HTTP_CREATED, ['Location' => $userLocation], ['groups' => 'user:index']); // code 201
        } catch (NotEncodableValueException $e) {
            return $this->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST); // code 400
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

        //if customer or admin is the user owner
        if ($user->getOwner() == $this->getUser()) {

            try {
                $receivedUser = $serializer->deserialize($receivedJson, User::class, 'json');
                $errors = $validator->validate($receivedUser, null, ['update']);
                if (count($errors) > 0) {
                    $formatedViolationList = [];
                    for ($i = 0; $i < $errors->count(); $i++) {
                        $violation = $errors->get($i);
                        $formatedViolationList[] = ['propertyPath' => $violation->getPropertyPath(), 'message' => $violation->getMessage()];
                    }
                    return $this->json([
                        'statusCode' => Response::HTTP_BAD_REQUEST,
                        'errors' => $formatedViolationList
                    ], Response::HTTP_BAD_REQUEST); // code 400
                }
                $simpleUserPasswordHash = $passwordHasher->hashPassword($user, $receivedUser->getPassword());
                $user->setPassword($simpleUserPasswordHash);

                $manager->persist($user);
                $manager->flush();
                return $this->json(null, Response::HTTP_NO_CONTENT); // code 204

            } catch (NotEncodableValueException $e) {
                return $this->json([
                    'statusCode' => Response::HTTP_BAD_REQUEST,
                    'message' => $e->getMessage()
                ], Response::HTTP_BAD_REQUEST); // code 400
            }
        } else {
            return $this->json([
                'statusCode' => Response::HTTP_FORBIDDEN,
                'message' => 'Vous ne disposez pas du droit de modification de cet utilisateur'
            ], Response::HTTP_FORBIDDEN); // code 403
        }
    }

    /**
     * @Route("/api/users/{userId<\d+>}", name="api_user_delete", methods={"DELETE"})
     */
    public function userDelete(int $userId, UserRepository $userRepository, EntityManagerInterface $manager, AuthorizationCheckerInterface $authChecker): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CUSTOMER'); //restrict access to customers and admin
        $user = $userRepository->findOneBy(['id' => $userId]);

        //if admin or if customer is the user owner - admin can delete unowned user for security reason
        if ($user->getOwner() == $this->getUser() || $authChecker->isGranted('ROLE_ADMIN')) {
            $manager->remove($user);
            $manager->flush();
            return $this->json(null, Response::HTTP_NO_CONTENT); // code 204
        } else {
            return $this->json([
                'statusCode' => Response::HTTP_FORBIDDEN,
                'message' => 'Vous ne disposez pas du droit de suppression de cet utilisateur'
            ], Response::HTTP_FORBIDDEN); // code 403
        }
    }
}
