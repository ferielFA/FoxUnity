<?php
class Coupon
{
    private $couponId;
    private $code;
    private $discountType;
    private $discountValue;
    private $minPurchase;
    private $maxDiscount;
    private $usageLimit;
    private $usedCount;
    private $expiresAt;
    private $publisherId;
    private $isActive;
    private $createdAt;
    private $updatedAt;

    public function __construct($couponId, $code, $discountType, $discountValue, $minPurchase, $maxDiscount, $usageLimit, $usedCount, $expiresAt, $publisherId, $isActive, $createdAt, $updatedAt)
    {
        $this->couponId = $couponId;
        $this->code = $code;
        $this->discountType = $discountType;
        $this->discountValue = $discountValue;
        $this->minPurchase = $minPurchase;
        $this->maxDiscount = $maxDiscount;
        $this->usageLimit = $usageLimit;
        $this->usedCount = $usedCount;
        $this->expiresAt = $expiresAt;
        $this->publisherId = $publisherId;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getCouponId()
    {
        return $this->couponId;
    }
    public function getCode()
    {
        return $this->code;
    }
    public function getDiscountType()
    {
        return $this->discountType;
    }
    public function getDiscountValue()
    {
        return $this->discountValue;
    }
    public function getMinPurchase()
    {
        return $this->minPurchase;
    }
    public function getMaxDiscount()
    {
        return $this->maxDiscount;
    }
    public function getUsageLimit()
    {
        return $this->usageLimit;
    }
    public function getUsedCount()
    {
        return $this->usedCount;
    }
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }
    public function getPublisherId()
    {
        return $this->publisherId;
    }
    public function getIsActive()
    {
        return $this->isActive;
    }
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    // Logic
    public function isValid()
    {
        if (!$this->isActive)
            return false;

        $now = new DateTime();
        $expires = new DateTime($this->expiresAt);
        if ($now > $expires)
            return false;

        if (!$this->hasUsageRemaining())
            return false;

        return true;
    }

    public function hasUsageRemaining()
    {
        if ($this->usageLimit === null || $this->usageLimit == 0)
            return true;
        return $this->usedCount < $this->usageLimit;
    }

    public function calculateDiscount($totalAmount)
    {
        $discount = 0;

        if ($this->discountType === 'percentage') {
            $discount = $totalAmount * ($this->discountValue / 100);
        } else {
            $discount = $this->discountValue;
        }

        if ($this->maxDiscount !== null && $this->maxDiscount > 0 && $discount > $this->maxDiscount) {
            $discount = $this->maxDiscount;
        }

        return $discount;
    }
}
?>