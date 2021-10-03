<?php

namespace App\Serializer;

use App\Entity\Product;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ProductHalLinks",
 *     @OA\Property(
 *         property="self",
 *         @OA\Property(property="href", type="string", example="https://localhost:8000/api/products/12")
 *     )
 * )
 * 
 * @OA\Schema(
 *      schema="ProductIndex",
 *      description = "Normalized product index",
 *      @OA\Property(type="integer", property="id"),
 *      @OA\Property(type="string", property="model"),
 *      @OA\Property(
 *          property="brand",
 *          @OA\Property(property="id", type="integer"),
 *          @OA\Property(property="name", type="string")
 *      ),
 *      @OA\Property(property="_links", ref="#/components/schemas/ProductHalLinks")
 * )
 * 
 * @OA\Schema(
 *      schema="ProductRead",
 *      description = "Normalized product read",
 *      @OA\Property(type="integer", property="id", example=12),
 *      @OA\Property(type="string", property="model", example="Samsung Galaxy A52 5G"),
 *      @OA\Property(type="number", property="ht_price", example=320),
 *      @OA\Property(type="string", property="release_year", example="2021"),
 *      @OA\Property(type="integer", property="weight", example=189),
 *      @OA\Property(type="string", property="plateform", example="Android 11"),
 *      @OA\Property(type="string", property="color", example="noir"),
 *      @OA\Property(type="number", property="screen_size", example=6.5),
 *      @OA\Property(type="integer", property="storage_size", example=128),
 *      @OA\Property(type="integer", property="ram", example=6),
 *      @OA\Property(type="integer", property="core_nbr", example=8),
 *      @OA\Property(type="integer", property="cam_mpx", example=64),
 *      @OA\Property(type="integer", property="battery", example=4500),
 *      @OA\Property(
 *          property="brand",
 *          @OA\Property(property="id", type="integer", example=2),
 *          @OA\Property(property="name", type="string", example="Samsung")
 *      ),
 *      @OA\Property(property="_links", ref="#/components/schemas/ProductHalLinks")
 * )
 * 
 * @OA\Schema(
 *      schema="ProductIndexList",
 *      description = "List of normalized product index",
 *      @OA\Property(
 *          property="meta",
 *          @OA\Property(property="totalPaginatedItems", type="integer", example="3"),
 *          @OA\Property(property="maxItemsPerPage", type="integer", example="5"),
 *          @OA\Property(property="lastPage", type="integer", example="1"),
 *          @OA\Property(property="currentPage", type="integer", example="1"),
 *          @OA\Property(property="currentPageItems", type="integer", example="3"),
 *          @OA\Property(property="availableBrands", type="array", example={"Apple", "Samsung", "Huawei", "Xiaomi"})
 *      ),
 *      @OA\Property(
 *          property="data",
 *          type="array",
 *          @OA\Items(ref="#/components/schemas/ProductIndex"),
 *          example={
 *              {"id":1, "model":"iPhone 12 - 256", "brand":{"id":1, "name":"Apple"}, 
 *                  "_links":{"self":{"href":"https://localhost:8000/api/products/1"}}},
 *              {"id":14, "model":"P40 5G", "brand":{"id":3, "name":"Huawei"}, 
 *                  "_links":{"self":{"href":"https://localhost:8000/api/products/14"}}},
 *              {"id":10, "model":"S21 5G - 256 Go - 8 Go", "brand":{"id":2, "name":"Samsung"}, 
 *                  "_links":{"self":{"href":"https://localhost:8000/api/products/10"}}}
 *          }
 *      )
 * )
 */
class ProductHalNormalizer implements ContextAwareNormalizerInterface
{
    private $router;
    private $normalizer;

    public function __construct(UrlGeneratorInterface $router, ObjectNormalizer $normalizer)
    {
        $this->router = $router;
        $this->normalizer = $normalizer;
    }

    public function normalize($product, string $format = null, array $context = [])
    {
        $data = $this->normalizer->normalize($product, $format, $context);

        // Here, add, edit, or delete some data:
        $data['_links']['self']['href'] = $this->router->generate('api_product_detail', [
            'productId' => $product->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $data;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof Product;
    }
}
