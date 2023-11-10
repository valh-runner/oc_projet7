<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable(); // get the exception from the event
        $response = new JsonResponse();
        $data = [];

        // if type of exception is HttpExceptionInterface it holds status code and header details
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $data['code'] = $statusCode;
            $response->setStatusCode($statusCode);
            $response->headers->replace($exception->getHeaders());

            $data['message'] = match ($statusCode) {
                400 => 'La requête ne peut être traitée correctement',
                401 => 'L\'authentification a échoué',
                403 => 'L\'accès à cette ressource n\'est pas autorisé',
                404 => 'La ressource n\'existe pas',
                405 => 'La méthode HTTP utilisée n\'est pas traitable par l\'API',
                406 => 'Le serveur n\'est pas en mesure de répondre aux attentes des entêtes',
                500 => 'Le serveur a rencontré un problème',
                default => $exception->getMessage(),
            };

            $response->setData($data);
            $event->setResponse($response); // sends the modified response object to the event
            return;
        }

        $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        $data['code'] = Response::HTTP_INTERNAL_SERVER_ERROR; // code 500
        $data['errorCode'] = $exception->getCode();
        $data['message'] = $exception->getMessage();

        $response->setData($data);
        $event->setResponse($response); // sends the modified response object to the event
    }
}
