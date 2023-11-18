<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ErrorResponder extends AbstractController
{
    /**
     * Respond a description of validation errors
     *
     * @return JsonResponse
     */
    public function validationErrorsResponse(ConstraintViolationListInterface $violations): JsonResponse
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
    public function errorResponse(int $statusCode, string $message): JsonResponse
    {
        return $this->json([
            'code' => $statusCode,
            'message' => $message
        ], $statusCode);
    }
}
