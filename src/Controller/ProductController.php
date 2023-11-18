<?php

namespace App\Controller;

use OpenApi\Annotations as OA;
use App\Repository\BrandRepository;
use App\Repository\ProductRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use App\Service\ErrorResponder;

class ProductController extends AbstractController
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
    public function productIndex(Request $request, ProductRepository $productRepository, BrandRepository $brandRepository, ValidatorInterface $validator, CacheItemPoolInterface $productPool, ErrorResponder $errorResponder): JsonResponse
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
            $productIndexItem->set($this->productIndexProcess($getParams, $productRepository, $brandRepository, $validator, $errorResponder));
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
    public function productDetail(int $productId, ProductRepository $productRepository, CacheItemPoolInterface $productPool, ErrorResponder $errorResponder): JsonResponse
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
        return $errorResponder->errorResponse(JsonResponse::HTTP_NOT_FOUND, 'Ce produit n\'existe pas'); // code 404
    }

    /**
     * Main logic of productIndex
     *
     * @return JsonResponse
     */
    private function productIndexProcess(array $getParams, ProductRepository $productRepository, BrandRepository $brandRepository, ValidatorInterface $validator, ErrorResponder $errorResponder): JsonResponse
    {
        $violations = $this->productIndexValidation($getParams);
        if (count($violations) > 0) {
            return $errorResponder->validationErrorsResponse($violations); //send errors details response
        }

        // data retrieve
        $PageItemsLimit = 5;
        $fullProductsCount = $productRepository->unpaginatedSearchCount($getParams['brand']);
        $currentProducts = $productRepository->paginatedSearch($getParams['brand'], $getParams['order'], $PageItemsLimit, $getParams['page']);
        $lastPage = ceil($fullProductsCount / $PageItemsLimit);

        //if asked page is out of bound
        if ($getParams['page'] > $lastPage) {
            return $errorResponder->errorResponse(JsonResponse::HTTP_NOT_FOUND, 'La page n\'existe pas'); // code 404
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

        return $this->json($content, JsonResponse::HTTP_OK, [], ['groups' => 'product:index']); // code 200
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
