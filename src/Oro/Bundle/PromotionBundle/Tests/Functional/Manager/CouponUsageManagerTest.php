<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Manager;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponUsageData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CouponUsageManagerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadCouponUsageData::class
        ]);
    }

    public function testGetCouponUsageCount()
    {
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL);

        $manager = $this->getContainer()->get('oro_promotion.coupon_usage_manager');

        $this->assertEquals(3, $manager->getCouponUsageCount($coupon));
    }

    public function testGetCouponUsageCountByCustomerUser()
    {
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL);
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        $manager = $this->getContainer()->get('oro_promotion.coupon_usage_manager');

        $this->assertEquals(2, $manager->getCouponUsageCountByCustomerUser($coupon, $customerUser));
    }

    public function testCreateCouponUsage()
    {
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL);
        $customerUser = $this->getReference(LoadCustomerUserData::ANONYMOUS_EMAIL);
        $promotion = $this->getReference(LoadPromotionData::ORDER_PERCENT_PROMOTION);

        $manager = $this->getContainer()->get('oro_promotion.coupon_usage_manager');

        $this->assertEmpty($manager->getCouponUsageCountByCustomerUser($coupon, $customerUser));

        $manager->createCouponUsage($coupon, $promotion, $customerUser, true);
        $manager->createCouponUsage($coupon, $promotion, $customerUser, true);
        $manager->createCouponUsage($coupon, $promotion, $customerUser, false);
        $this->assertEquals(2, $manager->getCouponUsageCountByCustomerUser($coupon, $customerUser));
    }
}