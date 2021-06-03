<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AppController extends AbstractController
{
    /**
     * @Route("/api/products", name="api_product_index", methods={"GET"})
     */
    public function index(ProductRepository $productRepository, SerializerInterface $serializer): Response
    {
        $products = $productRepository->findAll();
        $response = $this->json($products, 200, [], ['groups' => 'product:index']);
        return $response;
    }

    /**
     * @Route("/api/products/{id}", name="api_product_detail", methods={"GET"})
     */
    public function detail(int $id, ProductRepository $productRepository, SerializerInterface $serializer): Response
    {
        $product = $productRepository->findOneBy(['id' => $id]);
        $response = $this->json($product, 200, [], ['groups' => 'product:read']);
        return $response;
    }
}
