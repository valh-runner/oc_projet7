<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ExceptionListener
{
    #/**
    # * @param ExceptionEvent $event
    # */
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable(); // get the exception from the event
        $response = new JsonResponse();
        $data = [];

        // if type of exception is HttpExceptionInterface it holds status code and header details
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $data['status'] = $statusCode;
            $response->setStatusCode($statusCode);
            $response->headers->replace($exception->getHeaders());

            switch ($statusCode) {
                case '400':
                    $data['message'] = 'La requête ne peut être traitée correctement';
                    break;
                case '401':
                    $data['message'] = 'L\'authentification a échoué';
                    break;
                case '403':
                    $data['message'] = 'L\'accès à cette ressource n\'est pas autorisé';
                    break;
                case '404':
                    $data['message'] = 'La ressource n\'existe pas';
                    break;
                case '405':
                    $data['message'] = 'La méthode HTTP utilisée n\'est pas traitable par l\'API';
                    break;
                case '406':
                    $data['message'] = 'Le serveur n\'est pas en mesure de répondre aux attentes des entêtes';
                    break;
                case '500':
                    $data['message'] = 'Le serveur a rencontré un problème';
                    break;
                default:
                    $data['message'] = $exception->getMessage();
            }

            $response->setData($data);
            $event->setResponse($response); // sends the modified response object to the event
            return;
        }

        $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        $data['error code'] = $exception->getCode();
        $data['message'] = $exception->getMessage();

        $response->setData($data);
        $event->setResponse($response); // sends the modified response object to the event
    }
}
