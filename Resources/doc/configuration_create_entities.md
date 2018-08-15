# Summary

- [Create role entities with transactions](#create-role-entities-with-transactions)
- [Create user entities with transactions](#create-user-entities-with-transactions)
- [Create transactions with multiple managers](#create-transactions-with-multiple-managers)

## Create role entities with transactions


```php
<?php
use Sfynx\MigrationBundle\Model\AbstractMigration;
use UserContext\Domain\Entity\Role;
use UserContext\Domain\Service\Entity\Role\RoleManager;

/**
 * Class Migration_1518010504
 *
 * @category SfynxMigration
 * @package Sfynx
 * @subpackage orm
 */
class Migration_1518010504 extends AbstractMigration
{
    /** @var RoleManager */
    protected $roleManager;

    /**
     * @return array
     */
    protected function getTransactionManagers(): array
    {
        $this->roleManager = $this->container->get('user_role_manager');

        return [
            $this->roleManager->getCommandRepository()->getEm(),
        ];
    }

    /**
     * Does the migration
     */
    public function Up()
    {
        $this->update();
    }

    /**
     * Creates an Actor and persists and flushes it
     */
    protected function update()
    {
        $internalRoleType = Role::ROLE_INTERNAL_TYPE;
        $adminRoleType = Role::ROLE_ADMIN_TYPE;

        $fixtures = [
            [
                'label' => 'Directeur Régional', 'type' => 'ROLE_DR','typemetier'=>$internalRoleType, 'restricted' =>false
            ],
            [
                'label' => 'Direction Technique', 'type' => 'ROLE_DT','typemetier'=>$internalRoleType, 'restricted' =>false
            ],
            [
                'label' => 'Direction Commerciale', 'type' => 'ROLE_DC','typemetier'=>$internalRoleType, 'restricted' =>false
            ],
            [
                'label' => 'Direction Juridique', 'type' => 'ROLE_DJ','typemetier'=>$internalRoleType, 'restricted' =>false
            ],
            [
                'label' => 'Administrateur', 'type' => 'ROLE_ADMIN','typemetier'=>$adminRoleType, 'restricted' =>false
            ],
        ];

        foreach ($fixtures as $fixture) {
            $role = new Role();
            $role->setLabel($fixture['label']);
            $role->setType($fixture['type']);
            $role->setTypemetier($fixture['typemetier']);
            $role->setRestricted($fixture['restricted']);
            $this->roleManager->getCommandRepository()->persist($role, false);
        }
    }
}
```

## Create user entities with transactions

```php
<?php
use Sfynx\MigrationBundle\Model\AbstractMigration;
use UserContext\Domain\Entity\User;
use UserContext\Domain\Entity\Role;
use UserContext\Domain\Service\Entity\User\UserManager;

/**
 * Class Migration_5
 *
 * @category SfynxMigration
 * @package Sfynx
 * @subpackage orm
 */
class Migration_1518010505 extends AbstractMigration
{
    /** * @var UserManager */
    protected $userManager;

    /**
     * @return array
     */
    protected function getTransactionManagers(): array
    {
        $this->userManager = $this->container->get('promotion_user_manager');
        $this->encoder = $this->container->get('security.encoder_factory');

        return [
            $this->userManager->getCommandRepository()->getEm(),
        ];
    }

    /**
     * Does the migration
     */
    public function Up()
    {
        $this->update();
    }

    /**
     * Creates an Actor and persists and flushes it
     */
    protected function update()
    {
        /** @var $user_role_repository_query UserContext\Infrastructure\Repository\Query\RoleRepository */
        $user_role_repository_query = $this->container->get('user_role_repository_query');
        $roleAdmin = $user_role_repository_query->find(Role::ADMIN);

        $fixtures = [
            [
                'username' => "ADMIN",
                'username_canonical' => 'admin',
                'email' => 'admin@alterway.fr',
                'email_canonical' => 'admin@alterway.fr',
                'enabled' => 1,
                'salt' => NULL,
                'password' => NULL,
                'last_login' => new \DateTime('2017-11-12 18:59:10'),
                'confirmation_token' => NULL,
                'password_requested_at' => NULL,
                'role' => $roleAdmin,
                'createdAt' => new \DateTime('2017-11-09 13:23:23'),
                'startAt' => new \DateTime('2017-11-09 13:23:23'),
                'endAt' => NULL
            ]
        ];

        foreach ($fixtures as $fixture) {
            $user = new User();
            $user->setUsername($fixture['username']);
            $user->setUsernameCanonical($fixture['username_canonical']);
            $user->setEmail($fixture['email']);
            $user->setEmailCanonical($fixture['email_canonical']);
            $user->setEnabled($fixture['enabled']);
            $user->setSalt($fixture['salt']);
            $user->setPassword($this->encoder->getEncoder($user)->encodePassword(base64_encode($fixture['email']), $user->getSalt()));
            $user->setLastLogin($fixture['last_login']);
            $user->setConfirmationToken($fixture['confirmation_token']);
            $user->setPasswordRequestedAt($fixture['password_requested_at']);
            $user->addUserRole($fixture['role']);
            $user->setCreatedAt($fixture['createdAt']);
            $user->setStartAt($fixture['startAt']);
            $user->setEndAt($fixture['endAt']);

            $this->userManager->getCommandRepository()->persist($user, false);
        }
    }
}
```

## Create transactions with multiple managers

```php
<?php
use Sfynx\MigrationBundle\Model\AbstractMigration;
use ProfilContext\Domain\Entity\Profil;
use ProfilContext\Domain\Entity\Contact;
use ProfilContext\Domain\Entity\Person;
use GeographieContext\Domain\Entity\Address;

use UserContext\Domain\Service\Entity\User\UserManager;
use ProfilContext\Domain\Service\Entity\Profil\ProfilManager;
use ProfilContext\Domain\Service\Entity\Person\PersonManager;
use ProfilContext\Domain\Service\Entity\Contact\ContactManager;
use GeographieContext\Domain\Service\Entity\Region\RegionManager;
use GeographieContext\Domain\Service\Entity\Pays\PaysManager;
use GeographieContext\Domain\Service\Entity\Address\AddressManager;

/**
 * Class Migration_1512908060
 *
 * @category SfynxMigration
 * @package Sfynx
 * @subpackage orm
 */
class Migration_1518010509 extends AbstractMigration
{
    /** * @var UserManager */
    protected $userManager;
    /** @var ProfilManager */
    protected $profilManager;
    /** @var PersonManager  */
    protected $personManager;
    /** @var AddressManager  */
    protected $addressManager;
    /** @var ContactManager  */
    protected $contactManager;
    /** @var RegionManager */
    protected $regionManager;
    /** @var PaysManager */
    protected $paysManager;

    /**
     * @return array
     */
    protected function getTransactionManagers(): array
    {
        $this->userManager = $this->container->get('promotion_user_manager');
        $this->personManager = $this->container->get('profil_person_manager');
        $this->addressManager = $this->container->get('geographie_address_manager');
        $this->contactManager = $this->container->get('profil_contact_manager');
        $this->profilManager = $this->container->get('profil_manager');
        $this->regionManager = $this->container->get('geographie_region_manager');
        $this->paysManager = $this->container->get('geographie_pays_manager');

        return [
            $this->userManager->getCommandRepository()->getEm(),
            $this->personManager->getCommandRepository()->getEm(),
            $this->addressManager->getCommandRepository()->getEm(),
            $this->contactManager->getCommandRepository()->getEm(),
            $this->profilManager->getCommandRepository()->getEm()
        ];
    }

    /**
     * Does the migration
     */
    public function Up()
    {
        $this->update();
    }

    /**
     * Creates an Actor and persists and flushes it
     */
    protected function update()
    {
        $fixtures = [
            [
                'type' => Person::TYPE_USER,
                'lname' => 'Foncier',
                'fname' => 'Développeur',
                'email' => 'df1@gmail.fr',
                'mobile' => '0701020304',
                'cp' => 75001,
                'city' => 'Paris',
                'streetNumber' => 36,
                'streetName' => 'Avenue du Lac',
                'country_id' => 1,//France
                'region_id' => null,//IDF
            ],
            [
                'type' => Person::TYPE_USER,
                'lname' => 'Foncier2',
                'fname' => 'Développeur2',
                'email' => 'df2@gmail.fr',
                'mobile' => '0701020304',
                'cp' => 75001,
                'city' => 'Paris',
                'streetNumber' => 36,
                'streetName' => 'Avenue du Lac',
                'country_id' => 1,//France
                'region_id' => null,//IDF
            ],
            [
                'type' => Person::TYPE_USER,
                'lname' => 'Admin',
                'fname' => 'Admin',
                'email' => 'admin@alterway.fr',
                'mobile' => '0701020304',
                'cp' => 75001,
                'city' => 'Paris',
                'streetNumber' => 36,
                'streetName' => 'Avenue du Lac',
                'country_id' => 1,//France
                'region_id' => null,//IDF
            ]
        ];

        foreach ($fixtures as $fixture) {
            $this->createProfil($fixture);
        }
    }

    /**
     * @param $userInfo
     * @return Profil
     */
    protected function createProfil($userInfo)
    {
        // set params
        $streetNumber = $userInfo['streetNumber'];
        $streetName = $userInfo['streetName'];
        $cp = $userInfo['cp'];
        $city = $userInfo['city'];
        $userType = $userInfo['type'];
        $userFirstName = $userInfo['fname'];
        $userLastName = $userInfo['lname'];
        $userMobile = $userInfo['mobile'];
        $userEmail = $userInfo['email'];
        $region_id = $userInfo['region_id'];
        $country_id = $userInfo['country_id'];

        // get user
        $user = $this->userManager->getRepositoryQuery()->findUserByEmail($userEmail)->getQuery()->getOneOrNullResult();

        // set profil if not existed
        $person = new Person();
        $addressProfil = new Address();
        $contact = new Contact();
        $profil = new Profil();

        // add person
        $person
            ->setLname($userLastName)
            ->setFname($userFirstName)
            ->setType($userType)
        ;
        $this->personManager->getCommandRepository()->persist($person, false);

        // add address
        $addressProfil
            ->setApplicantProfil($profil)
            ->setStreetNumber($streetNumber)
            ->setStreetName($streetName)
            ->setCp($cp)
            ->setCity($city)
            ->setPays($this->paysManager->getReference($country_id));

        $this->addressManager->getCommandRepository()->persist($addressProfil, false);

        /**
         * Loop to assign the other regions to the admin user,
         * outside the default region
         */
        $regions = $this->regionManager->findAll();
        foreach ($regions as $region) {
            if(!is_null($region) && method_exists($region, 'getId')) {
                if ($userEmail === 'admin@alterway.fr') {
                    $addressProfilRegions = (new Address())
                        ->setApplicantProfil($profil)
                        ->setStreetNumber(null)
                        ->setStreetName(null)
                        ->setCp(null)
                        ->setCity(null)
                        ->setPays(null)
                        ->setRegion($region);

                    $this->addressManager->getCommandRepository()->persist($addressProfilRegions, false);
                }
            }
        }

        /**
         * Assign default regions to users who are not admin
         */
        if ($userEmail !== 'admin@alterway.fr') {
            $addressProfilNotAdmin = (new Address())
                ->setApplicantProfil($profil)
                ->setStreetNumber(null)
                ->setStreetName(null)
                ->setCp(null)
                ->setCity(null)
                ->setPays(null)
                ->setRegion($this->regionManager->getReference(1));
            $this->addressManager->getCommandRepository()->persist($addressProfilNotAdmin, false);
        }

        // add profile
        $profil->setUser($user);
        $profil->setPerson($person);
        $profil->addAddress($addressProfil);
        $profil->addContact($contact);
        $this->profilManager->add($profil);

        // add contact
        $contact
            ->setProfil($profil)
            ->setEmail($userEmail)
            ->setMobile($userMobile)
        ;
        $this->contactManager->getCommandRepository()->persist($contact, false);

        return $profil;
    }
}
```