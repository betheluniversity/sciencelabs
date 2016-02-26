<?php
namespace Bethel\FrontBundle\Security;

use Bethel\EntityBundle\Entity\UserRepository;
use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class ApiKeyAuthenticator implements SimplePreAuthenticatorInterface {

    protected $userProvider;

    public function __construct(UserRepository $userProvider) {
        $this->userProvider = $userProvider;
    }

    public function createToken(Request $request, $providerKey) {
        if (!$request->query->has('apikey')) {
            throw new BadCredentialsException('No API key found');
        }

        return new PreAuthenticatedToken(
            'anon.',
            $request->query->get('apikey'),
            $providerKey
        );
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey) {
        $apiKey = $token->getCredentials();
        $username = $this->userProvider->getUsernameForApiKey($apiKey);

        if (!$username) {
            throw new AuthenticationException(
            sprintf('API Key "%s" does not exist.', $apiKey)
            );
        }

        $user = $this->userProvider->loadUserByUsername($username);

        return new PreAuthenticatedToken(
            $user,
            $apiKey,
            $providerKey,
            $user->getRoles()
        );
    }

    public function supportsToken(TokenInterface $token, $providerKey) {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }
}