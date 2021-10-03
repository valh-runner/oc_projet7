<?php

namespace App\Serializer;

use App\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *      schema="UserHalLinks",
 *      @OA\Property(
 *          property="self",
 *          @OA\Property(
 *              property="href",
 *              type="string",
 *              example="https://localhost:8000/api/users/12"
 *          )
 *      ),
 *      @OA\Property(
 *          property="modify",
 *          @OA\Property(
 *              property="href",
 *              type="string",
 *              example="https://localhost:8000/api/users/12"
 *          )
 *      ),
 *      @OA\Property(
 *          property="delete",
 *          @OA\Property(
 *              property="href",
 *              type="string",
 *              example="https://localhost:8000/api/users/12"
 *          )
 *      )
 * )
 * 
 * @OA\Schema(
 *      schema="UserHalEmbedded",
 *      @OA\Property(
 *          property="owner",
 *          @OA\Property(
 *              property="id",
 *              type="integer",
 *              example="2"
 *          ),
 *          @OA\Property(
 *              property="username",
 *              type="string",
 *              example="E Corp"
 *          ),
 *          @OA\Property(
 *              property="_links",
 *              @OA\Property(
 *                  property="self",
 *                  @OA\Property(
 *                      property="href",
 *                      type="string",
 *                      example="https://localhost:8000/api/users/2"
 *                  )
 *              )
 *          )
 *      )
 * )
 * 
 * @OA\Schema(
 *      schema="UserIndex",
 *      description = "Normalized user index",
 *      @OA\Property(type="integer", property="id", example=12),
 *      @OA\Property(type="string", property="username", example="T.Wellick"),
 *      @OA\Property(property="_links", ref="#/components/schemas/UserHalLinks"),
 *      @OA\Property(property="_embedded", ref="#/components/schemas/UserHalEmbedded")
 * )
 * 
 * @OA\Schema(
 *      schema="UserIndexList",
 *      description = "List of normalized user index",
 *      @OA\Property(
 *          property="meta",
 *          @OA\Property(property="totalItems", type="integer", example="2")
 *      ),
 *      @OA\Property(
 *          property="data",
 *          type="array",
 *          @OA\Items(ref="#/components/schemas/UserIndex"),
 *          example={
 *              {
 *                  "id":42, "username":"E.Alderson",
 *                  "_links":{
 *                      "self":{"href":"https://localhost:8000/api/users/42"},
 *                      "modify":{"href":"https://localhost:8000/api/users/42"},
 *                      "delete":{"href":"https://localhost:8000/api/users/42"}
 *                  },
 *                  "_embedded":{"owner":{
 *                      "id":3, "username":"Allsafe cybersecurity",
 *                      "_links":{"self":{"href":"https://localhost:8000/api/users/3"}}
 *                  }}
 *              },
 *              {
 *                  "id":45, "username":"A.Moss",
 *                  "_links":{
 *                      "self":{"href":"https://localhost:8000/api/users/42"},
 *                      "modify":{"href":"https://localhost:8000/api/users/42"},
 *                      "delete":{"href":"https://localhost:8000/api/users/42"}
 *                  },
 *                  "_embedded":{"owner":{
 *                      "id":3, "username":"Allsafe cybersecurity",
 *                      "_links":{"self":{"href":"https://localhost:8000/api/users/3"}}
 *                  }}
 *              }
 *          }
 *      )
 * )
 */
class UserHalNormalizer implements ContextAwareNormalizerInterface
{
    private $router;
    private $normalizer;

    public function __construct(UrlGeneratorInterface $router, ObjectNormalizer $normalizer)
    {
        $this->router = $router;
        $this->normalizer = $normalizer;
    }

    public function normalize($user, string $format = null, array $context = [])
    {
        $data = $this->normalizer->normalize($user, $format, $context);

        // Here, add, edit, or delete some data:
        $data['_links']['self']['href'] = $this->router->generate('api_user_detail', [
            'userId' => $user->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $data['_links']['modify']['href'] = $this->router->generate('api_user_password_update', [
            'userId' => $user->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $data['_links']['delete']['href'] = $this->router->generate('api_user_delete', [
            'userId' => $user->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $data['_embedded']['owner']['id'] = $user->getOwner()->getId();
        $data['_embedded']['owner']['username'] = $user->getOwner()->getUsername();
        $data['_embedded']['owner']['_links']['self']['href'] = $this->router->generate('api_user_detail', [
            'userId' => $user->getOwner()->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $data;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof User;
    }
}
