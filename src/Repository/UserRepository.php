<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     * @return void
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Returns the owned users of a customer user
     * @return User[] Returns an array of User objects owned by a customer
     */
    public function findOwnedUsersOfUser(User $customerUser)
    {
        return $customerUser->getOwnedUsers();
    }


    /**
     * @return int Returns the number of users of a customer
     */
    public function customerSimpleUsersCount(int $customerId)
    {
        $qBuilder = $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.owner = ?1')
            ->setParameter(1, $customerId);

        $query = $qBuilder->getQuery();
        return (int) $query->getSingleScalarResult();
    }
}
