<?php
/**
 * Premium Check Utility
 * Centralizes premium status checking across the application
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($pdo)) {
    require_once __DIR__ . '/../config/db.php';
}

/**
 * Check if user is premium and update session if expired
 * @param int $userId - User ID to check
 * @return bool - True if user is currently premium
 */
function isPremiumUser($userId = null) {
    global $pdo;
    
    if ($userId === null) {
        $userId = $_SESSION['user_id'] ?? null;
    }
    
    if (!$userId) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT is_premium, premium_expires_at 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        // Check if premium and not expired
        if ($user['is_premium'] == 1) {
            // If no expiry date, premium is permanent
            if ($user['premium_expires_at'] === null) {
                return true;
            }
            
            // Check if expiry date is in the future
            if (strtotime($user['premium_expires_at']) > time()) {
                return true;
            } else {
                // Premium has expired - update database
                expirePremium($userId);
                return false;
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error checking premium status: " . $e->getMessage());
        return false;
    }
}

/**
 * Expire a user's premium subscription
 * @param int $userId - User ID to expire
 */
function expirePremium($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users
            SET is_premium = 0,
                premium_expires_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        
        // Clear session variable if this is current user
        if ($_SESSION['user_id'] ?? null == $userId) {
            $_SESSION['is_premium'] = 0;
        }
    } catch (Exception $e) {
        error_log("Error expiring premium: " . $e->getMessage());
    }
}

/**
 * Activate premium for a user
 * @param int $userId - User ID to activate
 * @param int $daysValid - Number of days the subscription is valid (default: 30)
 */
function activatePremium($userId, $daysValid = 30) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users
            SET is_premium = 1,
                premium_expires_at = DATE_ADD(NOW(), INTERVAL ? DAY)
            WHERE id = ?
        ");
        $stmt->execute([$daysValid, $userId]);
        
        // Set session variable if this is current user
        if ($_SESSION['user_id'] ?? null == $userId) {
            $_SESSION['is_premium'] = 1;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error activating premium: " . $e->getMessage());
        return false;
    }
}

/**
 * Get premium expiry date for user
 * @param int $userId - User ID
 * @return string|null - Expiry date or null if no expiry
 */
function getPremiumExpiryDate($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT premium_expires_at 
            FROM users 
            WHERE id = ? AND is_premium = 1
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user['premium_expires_at'] ?? null;
    } catch (Exception $e) {
        error_log("Error getting premium expiry: " . $e->getMessage());
        return null;
    }
}

/**
 * Get days remaining for premium subscription
 * @param int $userId - User ID
 * @return int - Days remaining (0 if expired or not premium)
 */
function getPremiumDaysRemaining($userId) {
    $expiryDate = getPremiumExpiryDate($userId);
    
    if (!$expiryDate) {
        return 0;
    }
    
    $now = new DateTime();
    $expiry = new DateTime($expiryDate);
    
    if ($expiry <= $now) {
        return 0;
    }
    
    $diff = $expiry->diff($now);
    return $diff->days;
}
?>
