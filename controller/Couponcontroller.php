<?php
require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/Coupon.php';

class CouponController
{
    /**
     * Validate and apply coupon for a user
     */
    public function validateCoupon($code, $userId, $totalAmount)
    {
        try {
            $pdo = getDB();

            // Get coupon
            $sql = "SELECT * FROM coupons WHERE code = :code";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':code' => strtoupper(trim($code))]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return [
                    'success' => false,
                    'error' => 'Invalid coupon code'
                ];
            }

            $coupon = $this->rowToCoupon($row);

            // Check if valid
            if (!$coupon->isValid()) {
                if (!$coupon->getIsActive()) {
                    return ['success' => false, 'error' => 'This coupon is no longer active'];
                }

                $now = new DateTime();
                $expires = new DateTime($coupon->getExpiresAt());
                if ($now > $expires) {
                    return ['success' => false, 'error' => 'This coupon has expired'];
                }

                if (!$coupon->hasUsageRemaining()) {
                    return ['success' => false, 'error' => 'This coupon has reached its usage limit'];
                }
            }

            // Check if user already used this coupon
            $usageCheck = $pdo->prepare("SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = :cid AND user_id = :uid");
            $usageCheck->execute([
                ':cid' => $coupon->getCouponId(),
                ':uid' => $userId
            ]);

            if ($usageCheck->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'error' => 'You have already used this coupon'
                ];
            }

            // Check minimum purchase
            if ($totalAmount < $coupon->getMinPurchase()) {
                return [
                    'success' => false,
                    'error' => 'Minimum purchase of $' . number_format($coupon->getMinPurchase(), 2) . ' required'
                ];
            }

            // Calculate discount
            $discount = $coupon->calculateDiscount($totalAmount);
            $finalAmount = max(0, $totalAmount - $discount);

            return [
                'success' => true,
                'coupon' => $coupon,
                'discount' => $discount,
                'original_amount' => $totalAmount,
                'final_amount' => $finalAmount,
                'message' => 'Coupon applied! You saved $' . number_format($discount, 2)
            ];

        } catch (PDOException $e) {
            error_log("Coupon validation error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error validating coupon'
            ];
        }
    }

    /**
     * Record coupon usage (call after successful purchase)
     */
    public function recordUsage($couponId, $userId, $orderAmount, $discountApplied)
    {
        try {
            $pdo = getDB();

            // Check if user already used this coupon (prevent duplicate records)
            $usageCheck = $pdo->prepare("SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = :cid AND user_id = :uid");
            $usageCheck->execute([
                ':cid' => $couponId,
                ':uid' => $userId
            ]);

            if ($usageCheck->fetchColumn() > 0) {
                error_log("Attempted duplicate coupon use: User $userId for Coupon $couponId");
                return false;
            }

            $pdo->beginTransaction();

            // Insert usage record
            $sql = "INSERT INTO coupon_usage (coupon_id, user_id, order_amount, discount_applied) 
                    VALUES (:cid, :uid, :amount, :discount)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':cid' => $couponId,
                ':uid' => $userId,
                ':amount' => $orderAmount,
                ':discount' => $discountApplied
            ]);

            // Increment used_count
            $update = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE coupon_id = :cid");
            $update->execute([':cid' => $couponId]);

            // Check if usage limit reached and deactivate
            $check = $pdo->prepare("SELECT usage_limit, used_count FROM coupons WHERE coupon_id = :cid");
            $check->execute([':cid' => $couponId]);
            $couponData = $check->fetch(PDO::FETCH_ASSOC);

            if ($couponData && $couponData['usage_limit'] > 0 && $couponData['used_count'] >= $couponData['usage_limit']) {
                $deactivate = $pdo->prepare("UPDATE coupons SET is_active = 0 WHERE coupon_id = :cid");
                $deactivate->execute([':cid' => $couponId]);
            }

            $pdo->commit();
            return true;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Record coupon usage error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all coupons (admin)
     */
    public function getAllCoupons()
    {
        try {
            $pdo = getDB();
            $sql = "SELECT c.*, u.username as publisher_name 
                    FROM coupons c 
                    LEFT JOIN users u ON c.publisher_id = u.id 
                    ORDER BY c.created_at DESC";
            $stmt = $pdo->query($sql);

            $coupons = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $coupon = $this->rowToCoupon($row);
                $coupons[] = [
                    'coupon' => $coupon,
                    'publisher_name' => $row['publisher_name'] ?? 'Unknown'
                ];
            }

            return $coupons;

        } catch (PDOException $e) {
            error_log("Get all coupons error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active coupons only
     */
    public function getActiveCoupons()
    {
        try {
            $pdo = getDB();
            $sql = "SELECT * FROM coupons 
                    WHERE is_active = 1 AND expires_at > NOW() 
                    ORDER BY created_at DESC";
            $stmt = $pdo->query($sql);

            $coupons = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $coupons[] = $this->rowToCoupon($row);
            }

            return $coupons;

        } catch (PDOException $e) {
            error_log("Get active coupons error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active coupons that a specific user has NOT used yet
     */
    public function getUnusedActiveCoupons($userId)
    {
        try {
            $pdo = getDB();
            $sql = "SELECT c.* FROM coupons c
                    LEFT JOIN coupon_usage cu ON c.coupon_id = cu.coupon_id AND cu.user_id = :uid
                    WHERE c.is_active = 1 
                    AND c.expires_at > NOW() 
                    AND cu.usage_id IS NULL
                    AND (c.usage_limit IS NULL OR c.used_count < c.usage_limit)
                    ORDER BY c.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $userId]);

            $coupons = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $coupons[] = $this->rowToCoupon($row);
            }

            return $coupons;

        } catch (PDOException $e) {
            error_log("Get unused active coupons error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create new coupon (admin)
     */
    public function createCoupon($data)
    {
        try {
            $pdo = getDB();

            // Check if code already exists
            $check = $pdo->prepare("SELECT COUNT(*) FROM coupons WHERE code = :code");
            $check->execute([':code' => strtoupper($data['code'])]);
            if ($check->fetchColumn() > 0) {
                return ['success' => false, 'error' => 'Coupon code already exists'];
            }

            $sql = "INSERT INTO coupons (code, discount_type, discount_value, min_purchase, max_discount, usage_limit, expires_at, publisher_id, is_active) 
                    VALUES (:code, :dtype, :dvalue, :minp, :maxd, :ulimit, :expires, :pub, :active)";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                ':code' => strtoupper($data['code']),
                ':dtype' => $data['discount_type'],
                ':dvalue' => $data['discount_value'],
                ':minp' => $data['min_purchase'] ?? 0,
                ':maxd' => $data['max_discount'] ?? null,
                ':ulimit' => $data['usage_limit'] ?? null,
                ':expires' => $data['expires_at'],
                ':pub' => $data['publisher_id'],
                ':active' => $data['is_active'] ?? 1
            ]);

            return [
                'success' => $result,
                'message' => 'Coupon created successfully'
            ];

        } catch (PDOException $e) {
            error_log("Create coupon error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error creating coupon'
            ];
        }
    }

    /**
     * Update coupon (admin)
     */
    public function updateCoupon($couponId, $data)
    {
        try {
            $pdo = getDB();

            $fields = [];
            $params = [':id' => $couponId];

            if (isset($data['discount_value'])) {
                $fields[] = 'discount_value = :dvalue';
                $params[':dvalue'] = $data['discount_value'];
            }
            if (isset($data['min_purchase'])) {
                $fields[] = 'min_purchase = :minp';
                $params[':minp'] = $data['min_purchase'];
            }
            if (isset($data['max_discount'])) {
                $fields[] = 'max_discount = :maxd';
                $params[':maxd'] = $data['max_discount'];
            }
            if (isset($data['usage_limit'])) {
                $fields[] = 'usage_limit = :ulimit';
                $params[':ulimit'] = $data['usage_limit'];
            }
            if (isset($data['expires_at'])) {
                $fields[] = 'expires_at = :expires';
                $params[':expires'] = $data['expires_at'];
            }
            if (isset($data['is_active'])) {
                $fields[] = 'is_active = :active';
                $params[':active'] = $data['is_active'];
            }

            if (empty($fields)) {
                return ['success' => false, 'error' => 'No fields to update'];
            }

            $sql = "UPDATE coupons SET " . implode(', ', $fields) . " WHERE coupon_id = :id";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);

            return [
                'success' => $result,
                'message' => 'Coupon updated successfully'
            ];

        } catch (PDOException $e) {
            error_log("Update coupon error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error updating coupon'];
        }
    }

    /**
     * Delete coupon (admin)
     */
    public function deleteCoupon($couponId)
    {
        try {
            $pdo = getDB();
            $sql = "DELETE FROM coupons WHERE coupon_id = :id";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([':id' => $couponId]);

        } catch (PDOException $e) {
            error_log("Delete coupon error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get coupon usage history
     */
    public function getCouponUsageHistory($couponId = null)
    {
        try {
            $pdo = getDB();

            if ($couponId) {
                $sql = "SELECT cu.*, c.code, u.username 
                        FROM coupon_usage cu 
                        JOIN coupons c ON cu.coupon_id = c.coupon_id 
                        JOIN users u ON cu.user_id = u.id 
                        WHERE cu.coupon_id = :cid 
                        ORDER BY cu.used_at DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':cid' => $couponId]);
            } else {
                $sql = "SELECT cu.*, c.code, u.username 
                        FROM coupon_usage cu 
                        JOIN coupons c ON cu.coupon_id = c.coupon_id 
                        JOIN users u ON cu.user_id = u.id 
                        ORDER BY cu.used_at DESC 
                        LIMIT 100";
                $stmt = $pdo->query($sql);
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Get coupon usage history error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get coupon statistics
     */
    public function getCouponStats()
    {
        try {
            $pdo = getDB();

            $stats = [];

            // Total coupons
            $stats['total_coupons'] = $pdo->query("SELECT COUNT(*) FROM coupons")->fetchColumn();

            // Active coupons
            $stats['active_coupons'] = $pdo->query("SELECT COUNT(*) FROM coupons WHERE is_active = 1 AND expires_at > NOW()")->fetchColumn();

            // Total usage
            $stats['total_usage'] = $pdo->query("SELECT COUNT(*) FROM coupon_usage")->fetchColumn();

            // Total discount given
            $stats['total_discount'] = $pdo->query("SELECT COALESCE(SUM(discount_applied), 0) FROM coupon_usage")->fetchColumn();

            return $stats;

        } catch (PDOException $e) {
            error_log("Get coupon stats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Helper: Convert DB row to Coupon object
     */
    private function rowToCoupon($row)
    {
        return new Coupon(
            $row['coupon_id'],
            $row['code'],
            $row['discount_type'],
            $row['discount_value'],
            $row['min_purchase'],
            $row['max_discount'],
            $row['usage_limit'],
            $row['used_count'],
            $row['expires_at'],
            $row['publisher_id'],
            $row['is_active'],
            $row['created_at'],
            $row['updated_at']
        );
    }
}
?>