<?php

namespace Shoplo\AllegroBundle\Security\Core\User;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Shoplo\AllegroBundle\Entity\User;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthUserProvider;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Shoplo\AllegroBundle\Entity\UserRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class DoctrineUserProvider extends OAuthUserProvider
{
    /**
     * @var UserRepository
     */
    protected $repository;
    protected $manager;

    public function __construct(Registry $registry)
    {
        $this->repository = $registry->getRepository('ShoploAllegroBundle:User');
        $this->manager = $registry->getManager();
    }

    /**
     * @param  UserResponseInterface $response
     * @return User
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        try {
            $user = $this->loadUserByUsername($response->getUsername());

            return $this->update($user, $response);
        } catch (UsernameNotFoundException $e) {
            return $this->create($response);
        }
    }

    /**
     * @param  int $shopId
     * @return User
     * @throws UsernameNotFoundException
     */
    public function loadUserByUsername($shopId)
    {
        if (null === $user = $this->repository->findOneByShopId($shopId)) {
            throw new UsernameNotFoundException('Shop not found.');
        }

        return $user;
    }

    /**
     * @param  UserInterface $user
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user)
    {
        return $user;
    }

    /**
     * @param  UserResponseInterface $response
     * @return User
     */
    private function create(UserResponseInterface $response)
    {
        $token = $response->getAccessToken();
        $user = new User();
        $user
            ->setShopId($response->getUsername())
            ->setOauthToken($token['oauth_token'])
            ->setOauthTokenSecret($token['oauth_token_secret']);

        $this->manager->persist($user);
        $this->manager->flush();

        return $user;
    }

    /**
     * @param  User                  $user
     * @param  UserResponseInterface $response
     * @return User
     */
    private function update(User $user, UserResponseInterface $response)
    {
        $token = $response->getAccessToken();
        $user
            ->setOauthToken($token['oauth_token'])
            ->setOauthTokenSecret($token['oauth_token_secret']);

        $this->manager->persist($user);
        $this->manager->flush();

        return $user;
    }
}
