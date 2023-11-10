<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;

class LexikJWTErrorsListener
{

    public function onAuthenticationFailureResponse(AuthenticationFailureEvent $event)
    {
        $response = new JWTAuthenticationFailureResponse('Informations de connexion non valides', JsonResponse::HTTP_UNAUTHORIZED);
        $event->setResponse($response);
    }

    public function onJWTInvalid(JWTInvalidEvent $event)
    {
        $response = new JWTAuthenticationFailureResponse('Jeton d\'identification JWT non valide', JsonResponse::HTTP_UNAUTHORIZED);
        $event->setResponse($response);
    }

    public function onJWTNotFound(JWTNotFoundEvent $event)
    {
        $data = [
            'code'  => JsonResponse::HTTP_UNAUTHORIZED,
            'message' => 'Jeton d\'identification JWT manquant',
        ];
        $response = new JsonResponse($data, JsonResponse::HTTP_UNAUTHORIZED);
        $event->setResponse($response);
    }

    public function onJWTExpired(JWTExpiredEvent $event)
    {
        /** @var JWTAuthenticationFailureResponse */
        $response = $event->getResponse();
        $response->setMessage('Jeton d\'identification JWT expiré');
    }
}
