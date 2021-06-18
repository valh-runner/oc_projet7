<?php

namespace App\Serializer;

use App\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

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
