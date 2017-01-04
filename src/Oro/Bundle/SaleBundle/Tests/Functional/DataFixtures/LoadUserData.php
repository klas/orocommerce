<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserManager;
use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;
use Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LoadUserData extends AbstractFixture implements FixtureInterface
{
    const USER1 = 'sale-user1';
    const USER2 = 'sale-user2';

    const ROLE1 = 'sale-role1';
    const ROLE2 = 'sale-role2';
    const ROLE3 = 'sale-role3';
    const ROLE4 = 'sale-role4';
    const ROLE5 = 'sale-role5';
    const ROLE6 = 'sale-role6';
    const ROLE7 = 'sale-role7';

    const PARENT_ACCOUNT = 'sale-parent-account';
    const ACCOUNT1 = 'sale-account1';
    const ACCOUNT2 = 'sale-account2';

    const ACCOUNT1_USER1    = 'sale-account1-user1@example.com';
    const ACCOUNT1_USER2    = 'sale-account1-user2@example.com';
    const ACCOUNT1_USER3    = 'sale-account1-user3@example.com';
    const ACCOUNT2_USER1    = 'sale-account2-user1@example.com';
    const PARENT_ACCOUNT_USER1    = 'sale-parent-account-user1@example.com';
    const PARENT_ACCOUNT_USER2    = 'sale-parent-account-user2@example.com';

    /**
     * @var array
     */
    protected $roles = [
        self::ROLE1 => [
            [
                'class' => 'oro_sale.entity.quote.class',
                'acls'  => ['VIEW_BASIC'],
            ],
            [
                'class' => 'oro_customer.entity.account_user.class',
                'acls'  => [],
            ],
        ],
        self::ROLE2 => [
            [
                'class' => 'oro_sale.entity.quote.class',
                'acls'  => ['VIEW_LOCAL'],
            ],
            [
                'class' => 'oro_customer.entity.account_user.class',
                'acls'  => [],
            ],
        ],
        self::ROLE3 => [
            [
                'class' => 'oro_sale.entity.quote.class',
                'acls'  => ['VIEW_LOCAL'],
            ],
            [
                'class' => 'oro_customer.entity.account_user.class',
                'acls'  => ['VIEW_LOCAL'],
            ]
        ],
        self::ROLE4 => [
            [
                'class' => 'oro_order.entity.order.class',
                'acls'  => [],
            ],
        ],
        self::ROLE5 => [
            [
                'class' => 'oro_order.entity.order.class',
                'acls'  => ['VIEW_BASIC', 'CREATE_BASIC'],
            ],
        ],
        self::ROLE6 => [
            [
                'class' => 'oro_sale.entity.quote.class',
                'acls'  => ['VIEW_DEEP'],
            ],
            [
                'class' => 'oro_customer.entity.account_user.class',
                'acls'  => ['VIEW_DEEP'],
            ],
            [
                'class' => 'oro_checkout.entity.checkout.class',
                'acls'  => ['VIEW_DEEP', 'CREATE_LOCAL'],
            ],
        ],
        self::ROLE7 => [
            [
                'class' => 'oro_checkout.entity.checkout.class',
                'acls'  => ['VIEW_LOCAL', 'EDIT_LOCAL', 'CREATE_LOCAL'],
            ],
        ],
    ];

    /**
     * @var array
     */
    protected $accounts = [
        [
            'name' => self::PARENT_ACCOUNT,
        ],
        [
            'name' => self::ACCOUNT1,
            'parent' => self::PARENT_ACCOUNT
        ],
        [
            'name' => self::ACCOUNT2,
            'parent' => self::PARENT_ACCOUNT
        ],
    ];

    /**
     * @var array
     */
    protected $accountUsers = [
        [
            'email'     => self::ACCOUNT1_USER1,
            'firstname' => 'User1FN',
            'lastname'  => 'User1LN',
            'password'  => self::ACCOUNT1_USER1,
            'account'   => self::ACCOUNT1,
            'roles'     => [
                self::ROLE1,
                self::ROLE4,
            ],
        ],
        [
            'email'     => self::ACCOUNT1_USER2,
            'firstname' => 'User2FN',
            'lastname'  => 'User2LN',
            'password'  => self::ACCOUNT1_USER2,
            'account'   => self::ACCOUNT1,
            'roles'     => [
                self::ROLE2,
                self::ROLE7,
            ],
        ],
        [
            'email'     => self::ACCOUNT1_USER3,
            'firstname' => 'User3FN',
            'lastname'  => 'User3LN',
            'password'  => self::ACCOUNT1_USER3,
            'account'   => self::ACCOUNT1,
            'roles'     => [
                self::ROLE3,
                self::ROLE5,
                self::ROLE7,
            ],
        ],
        [
            'email'     => self::ACCOUNT2_USER1,
            'firstname' => 'User1FN',
            'lastname'  => 'User1LN',
            'password'  => self::ACCOUNT2_USER1,
            'account'   => self::ACCOUNT2,
            'roles'     => [
                self::ROLE1,
            ],
        ],
        [
            'email'     => self::PARENT_ACCOUNT_USER1,
            'firstname' => 'ParentUser1FN',
            'lastname'  => 'ParentUser1LN',
            'password'  => self::PARENT_ACCOUNT_USER1,
            'account'   => self::PARENT_ACCOUNT,
            'roles'     => [
                self::ROLE6
            ],
        ],
        [
            'email'     => self::PARENT_ACCOUNT_USER2,
            'firstname' => 'ParentUser2FN',
            'lastname'  => 'ParentUser2LN',
            'password'  => self::PARENT_ACCOUNT_USER2,
            'account'   => self::PARENT_ACCOUNT,
            'roles'     => [
                self::ROLE2,
            ],
        ],
    ];

    /**
     * @var array
     */
    protected $users = [
        [
            'email'     => 'sale-user1@example.com',
            'username'  => self::USER1,
            'password'  => self::USER1,
            'firstname' => 'SaleUser1FN',
            'lastname'  => 'SaleUser1LN',
        ],
        [
            'email'     => 'sale-user2@example.com',
            'username'  => self::USER2,
            'password'  => self::USER2,
            'firstname' => 'SaleUser2FN',
            'lastname'  => 'SaleUser2LN',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadUsers($manager);
        $this->loadRoles($manager);
        $this->loadAccounts($manager);
        $this->loadAccountUsers($manager);
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadRoles(ObjectManager $manager)
    {
        /* @var $aclManager AclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');

        foreach ($this->roles as $key => $items) {
            $role = new AccountUserRole(AccountUserRole::PREFIX_ROLE . $key);
            $role->setLabel($key);

            foreach ($items as $acls) {
                $className = $this->container->getParameter($acls['class']);

                $this->setRolePermissions($aclManager, $role, $className, $acls['acls']);
            }

            $manager->persist($role);

            $this->setReference($key, $role);
        }

        $manager->flush();
        $aclManager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadAccounts(ObjectManager $manager)
    {
        $defaultUser    = $this->getUser($manager);
        $organization   = $defaultUser->getOrganization();

        foreach ($this->accounts as $item) {
            $account = new Account();
            $account
                ->setName($item['name'])
                ->setOrganization($organization)
            ;
            if (isset($item['parent'])) {
                $account->setParent($this->getReference($item['parent']));
            }
            $manager->persist($account);

            $this->addReference($item['name'], $account);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadAccountUsers(ObjectManager $manager)
    {
        /* @var $userManager CustomerUserManager */
        $userManager = $this->container->get('oro_account_user.manager');

        $defaultUser    = $this->getUser($manager);
        $organization   = $defaultUser->getOrganization();

        foreach ($this->accountUsers as $item) {
            /* @var $accountUser AccountUser */
            $accountUser = $userManager->createUser();

            $accountUser
                ->setEmail($item['email'])
                ->setAccount($this->getReference($item['account']))
                ->setFirstName($item['firstname'])
                ->setLastName($item['lastname'])
                ->setConfirmed(true)
                ->setOrganization($organization)
                ->addOrganization($organization)
                ->setSalt('')
                ->setPlainPassword($item['password'])
                ->setEnabled(true)
            ;

            foreach ($item['roles'] as $role) {
                /** @var AccountUserRole $roleEntity */
                $roleEntity = $this->getReference($role);
                $accountUser->addRole($roleEntity);
            }

            $userManager->updateUser($accountUser);

            $this->setReference($item['email'], $accountUser);
        }
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadUsers(ObjectManager $manager)
    {
        /* @var $userManager UserManager */
        $userManager    = $this->container->get('oro_user.manager');

        $defaultUser    = $this->getUser($manager);

        $businessUnit   = $defaultUser->getOwner();
        $organization   = $defaultUser->getOrganization();

        foreach ($this->users as $item) {
            /* @var $user User */
            $user = $userManager->createUser();

            $user
                ->setEmail($item['email'])
                ->setFirstName($item['firstname'])
                ->setLastName($item['lastname'])
                ->setBusinessUnits($defaultUser->getBusinessUnits())
                ->setOwner($businessUnit)
                ->setOrganization($organization)
                ->addOrganization($organization)
                ->setUsername($item['username'])
                ->setPlainPassword($item['password'])
                ->setEnabled(true)
            ;
            $userManager->updateUser($user);

            $this->setReference($user->getUsername(), $user);
        }
    }

    /**
     * @param AclManager $aclManager
     * @param AccountUserRole $role
     * @param string $className
     * @param array $allowedAcls
     */
    protected function setRolePermissions(AclManager $aclManager, AccountUserRole $role, $className, array $allowedAcls)
    {
        /* @var $chainMetadataProvider ChainMetadataProvider */
        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');

        if ($aclManager->isAclEnabled()) {
            $sid = $aclManager->getSid($role);
            $oid = $aclManager->getOid('entity:' . $className);
            $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);

            $builder = $aclManager->getMaskBuilder($oid);
            $mask = $builder->reset()->get();
            foreach ($allowedAcls as $acl) {
                $mask = $builder->add($acl)->get();
            }
            $aclManager->setPermission($sid, $oid, $mask);

            $chainMetadataProvider->stopProviderEmulation();
        }
    }
}
