<?php

namespace Oro\Bundle\PromotionBundle\ValidationService;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Manager\CouponUsageManager;

class CouponValidationService
{
    const MESSAGE_ABSENT_PROMOTION = 'oro.promotion.coupon.violation.absent_promotion';
    const MESSAGE_EXPIRED = 'oro.promotion.coupon.violation.expired';
    const MESSAGE_USAGE_LIMIT_EXCEEDED = 'oro.promotion.coupon.violation.usage_limit_exceeded';
    const MESSAGE_USER_USAGE_LIMIT_EXCEEDED = 'oro.promotion.coupon.violation.customer_user_usage_limit_exceeded';

    /**
     * @var CouponUsageManager
     */
    private $couponUsageManager;

    /**
     * @param CouponUsageManager $couponUsageManager
     */
    public function __construct(CouponUsageManager $couponUsageManager)
    {
        $this->couponUsageManager = $couponUsageManager;
    }

    /**
     * @param Coupon $coupon
     * @param CustomerUser|null $customerUser
     * @return bool
     */
    public function isValid(Coupon $coupon, CustomerUser $customerUser = null): bool
    {
        return !$this->getViolations($coupon, $customerUser);
    }

    /**
     * @param Coupon $coupon
     * @param CustomerUser $customerUser
     * @return array
     */
    public function getViolations(Coupon $coupon, CustomerUser $customerUser = null): array
    {
        $violations = [];

        if (!$coupon->getPromotion()) {
            $violations[] = self::MESSAGE_ABSENT_PROMOTION;
        }

        if ($this->isCouponExpired($coupon)) {
            $violations[] = self::MESSAGE_EXPIRED;
        }

        if ($this->isCouponUsageLimitExceeded($coupon)) {
            $violations[] = self::MESSAGE_USAGE_LIMIT_EXCEEDED;
        }

        if ($this->isCouponUsagePerCustomerUserLimitExceeded($coupon, $customerUser)) {
            $violations[] = self::MESSAGE_USER_USAGE_LIMIT_EXCEEDED;
        }

        return $violations;
    }

    /**
     * @param Coupon $coupon
     * @return bool
     */
    private function isCouponExpired(Coupon $coupon): bool
    {
        return $coupon->getValidUntil() && $coupon->getValidUntil() < new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @param Coupon $coupon
     * @return bool
     */
    private function isCouponUsageLimitExceeded(Coupon $coupon): bool
    {
        return $coupon->getUsesPerCoupon() !== null
            &&$coupon->getUsesPerCoupon() <= $this->couponUsageManager->getCouponUsageCount($coupon);
    }

    /**
     * @param Coupon $coupon
     * @param CustomerUser $customerUser
     * @return bool
     */
    private function isCouponUsagePerCustomerUserLimitExceeded(Coupon $coupon, CustomerUser $customerUser = null): bool
    {
        return $customerUser
            && $coupon->getUsesPerUser() !== null
            && $coupon->getUsesPerUser() <= $this->couponUsageManager
                ->getCouponUsageCountByCustomerUser($coupon, $customerUser);
    }
}
