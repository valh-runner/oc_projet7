<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }
    public function load(ObjectManager $manager)
    {
        $customersDataset = [
            [1, 'PsaFrance', '416s411v+'],
            [2, 'ChauffeurPrive', '8g14srvsh6'],
            [3, 'PhoneStoreRivoli', 'N3v3rFound'],
            [4, 'Deliveroo', 'Runn4w4y'],
            [5, 'KaufmanAndBroad', 'Gst42Dsn%18']
        ];

        $simpleUsersDataset = [
            [1, 'P17-fournitures', '14q81mhu'],
            [1, 'CollaborateursPeugeot', 'Atv18nG3'],
            [1, 'Siege75GrandeArmee', 'Kgh75GA#'],
            [2, 'FleetManagement', 'FL33Tmng'],
            [2, 'CE-ChauffeurPrive', 'c298cenh'],
            [3, 'Eric', 'kaboulox*'],
            [3, 'Nina', 'ikigai75'],
            [4, 'ServiceAchat', '92cay46k'],
            [5, 'Secteur Nord', 'gt4tr8sp'],
            [5, 'Secteur Sud', 'np3tsu67']
        ];

        $adminUser = new User();
        $adminUser->setUsername('BilemoAdmin')
            ->setPassword($this->passwordHasher->hashPassword($adminUser, 'K1ndOfS3cr3t'))
            ->setRoles(['ROLE_ADMIN']);
        $manager->persist($adminUser);

        //customers users creation
        for ($i = 0; $i < count($customersDataset); $i++) {
            $customerUser = new User();
            $costumerPasswordHash = $this->passwordHasher->hashPassword($customerUser, $customersDataset[$i][2]);

            $customerUser->setUsername($customersDataset[$i][1])
                ->setPassword($costumerPasswordHash)
                ->setRoles(['ROLE_CUSTOMER']);
            $manager->persist($customerUser);

            //current costumer simple users creation
            for ($j = 0; $j < count($simpleUsersDataset); $j++) {
                if ($customersDataset[$i][0] == $simpleUsersDataset[$j][0]) {
                    $simpleUser = new User();
                    $simpleUserPasswordHash = $this->passwordHasher->hashPassword($simpleUser, $simpleUsersDataset[$j][2]);

                    $simpleUser->setUsername($simpleUsersDataset[$j][1])
                        ->setPassword($simpleUserPasswordHash)
                        ->setRoles(['ROLE_USER'])
                        ->setOwner($customerUser);
                    $manager->persist($simpleUser);
                }
            }
        }

        $manager->flush();
    }
}
