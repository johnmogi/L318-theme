/**
 * Lilac Bonus Coupon System
 * Shows different messages based on user purchase status
 */

(function($) {
    'use strict';

    // Configuration from admin settings
    const settings = window.lilacBonusSettings || {
        enabled: false,
        generalMessage: 'רוצה להצטרף לקורס במחיר מיוחד? לחץ כאן לפרטים נוספים!',
        purchaserMessage: 'ברכות על רכישת התרגול! קוד ההנחה שלך לקורסים: {coupon_code}',
        couponCode: 'LILACBONUS',
        excludeCourses: false, // Allow messages on course pages
        sessionKey: 'lilac_bonus_shown'
    };

    // Check if current user should be excluded (admin/teacher roles)
    const shouldExcludeCurrentUser = function() {
        const bodyClasses = document.body.className;
        
        // Check for admin/privileged user indicators (ONLY specific privileged roles)
        const isAdmin = bodyClasses.includes('wp-admin') || 
                       bodyClasses.includes('role-administrator') ||
                       bodyClasses.includes('role-teacher') ||
                       bodyClasses.includes('role-instructor') ||
                       bodyClasses.includes('role-group_leader') ||
                       bodyClasses.includes('role-course_author') ||
                       bodyClasses.includes('role-editor') ||
                       bodyClasses.includes('role-author');
        
        // NOTE: Removed 'admin-bar' check as it appears for ALL logged-in users
        
        // Check for LearnDash instructor indicators
        const isInstructor = bodyClasses.includes('sfwd-instructor') ||
                           bodyClasses.includes('group_leader') ||
                           bodyClasses.includes('course_author');
        
        // Check global user role if available
        const hasPrivilegedRole = window.lilacUserRole === 'administrator' ||
                                 window.lilacUserRole === 'instructor' ||
                                 window.lilacUserRole === 'teacher' ||
                                 window.lilacUserRole === 'group_leader' ||
                                 window.lilacUserRole === 'editor' ||
                                 window.lilacUserRole === 'author';
        
        // Check if user has opted-in to see messages
        const hasOptedIn = localStorage.getItem('lilac_message_opt_in') === 'true';
        
        // Exclude if privileged user who hasn't opted-in
        if ((isAdmin || isInstructor || hasPrivilegedRole) && !hasOptedIn) {
            console.log('Lilac Bonus Debug - Excluded: Privileged user without opt-in');
            return true;
        }
        
        return false;
    };

    // Check if user has purchased practice/exercises
    const checkUserPurchaseStatus = function() {
        // First check if user should be excluded
        if (shouldExcludeCurrentUser()) {
            return null; // Special return value to indicate exclusion
        }
        
        // Check various indicators for purchase status
        const bodyClasses = document.body.className;
        
        // Debug: Log current body classes
        console.log('Lilac Bonus Debug - Body classes:', bodyClasses);
        
        // LearnDash purchase indicators
        const hasPurchased = bodyClasses.includes('ld-user-enrolled') ||
                           bodyClasses.includes('ld-course-access') ||
                           bodyClasses.includes('sfwd-courses') ||
                           localStorage.getItem('lilac_user_purchased') === 'true';
        
        // Check for WooCommerce purchase indicators
        const hasWooCommercePurchase = bodyClasses.includes('woocommerce-account') ||
                                     bodyClasses.includes('customer-logged-in') ||
                                     bodyClasses.includes('logged-in') ||
                                     sessionStorage.getItem('wc_user_purchased') === 'true';
        
        // Check global user data if available
        const hasGlobalPurchase = window.lilacUserData && window.lilacUserData.hasPurchased;
        
        // Check if user is logged in (basic indicator)
        const isLoggedIn = bodyClasses.includes('logged-in');
        
        // Check for recent purchase in session/local storage
        const hasRecentPurchase = sessionStorage.getItem('lilac_recent_purchase') === 'true' ||
                                localStorage.getItem('lilac_recent_purchase') === 'true';
        
        // Debug: Log all purchase indicators
        console.log('Lilac Bonus Debug - Purchase indicators:', {
            hasPurchased,
            hasWooCommercePurchase,
            hasGlobalPurchase,
            isLoggedIn,
            hasRecentPurchase,
            localStorage_lilac: localStorage.getItem('lilac_user_purchased'),
            sessionStorage_wc: sessionStorage.getItem('wc_user_purchased'),
            sessionStorage_recent: sessionStorage.getItem('lilac_recent_purchase')
        });
        
        // For debugging: temporarily assume logged-in users have purchased
        // This will help us test the purchaser message
        const finalResult = hasPurchased || hasWooCommercePurchase || hasGlobalPurchase || hasRecentPurchase || isLoggedIn;
        
        console.log('Lilac Bonus Debug - Final purchase status:', finalResult);
        
        return finalResult;
    };

    // Check if we should show the message on current page
    const shouldShowMessage = function() {
        // Don't show if disabled
        if (!settings.enabled) {
            return false;
        }
        
        // Check if already shown too many times in this session
        const maxDisplays = 5; // Allow up to 5 displays per session
        const currentCount = parseInt(sessionStorage.getItem(settings.sessionKey) || '0');
        
        if (currentCount >= maxDisplays) {
            console.log('Lilac Bonus Debug - Max displays reached:', currentCount + '/' + maxDisplays);
            return false;
        }
        
        console.log('Lilac Bonus Debug - Display count:', currentCount + '/' + maxDisplays);
        
        // Don't show on course pages if excluded
        if (settings.excludeCourses) {
            const bodyClasses = document.body.className;
            const isCourseePage = bodyClasses.includes('single-sfwd-courses') ||
                                bodyClasses.includes('sfwd-courses') ||
                                bodyClasses.includes('learndash-course') ||
                                window.location.href.includes('/courses/') ||
                                window.location.href.includes('/course/');
            
            if (isCourseePage) {
                console.log('Lilac Bonus Debug - Excluded: Course page detected');
                return false;
            }
        }
        
        // Don't show on specific WooCommerce pages (checkout, cart, shop, etc.)
        // NOTE: Removed generic 'woocommerce' class check to avoid blocking homepage
        const bodyClasses = document.body.className;
        const isWooCommercePage = bodyClasses.includes('woocommerce-checkout') ||
                                bodyClasses.includes('woocommerce-cart') ||
                                bodyClasses.includes('woocommerce-account') ||
                                bodyClasses.includes('single-product') ||
                                bodyClasses.includes('product-template-default') ||
                                bodyClasses.includes('wc-shortcodes-wrapper') ||
                                window.location.href.includes('/checkout') ||
                                window.location.href.includes('/cart') ||
                                window.location.href.includes('/shop') ||
                                window.location.href.includes('/product/') ||
                                window.location.href.includes('/my-account') ||
                                // Only exclude if we're actually on a WooCommerce-specific page
                                (bodyClasses.includes('woocommerce-page') && !bodyClasses.includes('home'));
        
        if (isWooCommercePage) {
            console.log('Lilac Bonus Debug - Excluded: WooCommerce page detected');
            return false;
        }
        
        // Don't show on admin pages
        if (window.location.href.includes('/wp-admin/')) {
            return false;
        }
        
        return true;
    };

    // Main function to show bonus message
    const showBonusMessage = function() {
        if (!settings.enabled) {
            console.log('Lilac Bonus Debug - Disabled in settings');
            return false;
        }
        
        // Check purchase status
        const purchaseStatus = checkUserPurchaseStatus();
        
        // If user should be excluded (admin/teacher without opt-in)
        if (purchaseStatus === null) {
            return false;
        }
        
        let message, title, type;
        
        if (purchaseStatus) {
            // Show message with coupon code for purchasers
            message = settings.purchaserMessage.replace('{coupon_code}', '<strong>' + settings.couponCode + '</strong>');
            title = 'קוד הנחה מיוחד עבורך!';
            type = 'success';
        } else {
            // Show general invitation message
            message = settings.generalMessage;
            title = 'הצעה מיוחדת!';
            type = 'info';
        }
        
        // Show the toast message
        if (window.LilacToast && typeof window.LilacToast.showToast === 'function') {
            window.LilacToast.showToast({
                title: title,
                message: message,
                type: type,
                duration: hasPurchased ? 0 : 8000, // Persistent for purchasers, 8 seconds for others
                closable: true,
                cssClass: 'lilac-bonus-toast',
                onClose: function() {
                    // Increment display counter for this session
                    const currentCount = parseInt(sessionStorage.getItem(settings.sessionKey) || '0');
                    sessionStorage.setItem(settings.sessionKey, (currentCount + 1).toString());
                    console.log('Lilac Bonus Debug - Display count incremented to:', currentCount + 1);
                }
            });
        } else if (window.LilacShowToast) {
            window.LilacShowToast({
                title: title,
                message: message,
                type: type,
                duration: hasPurchased ? 0 : 8000,
                closable: true,
                cssClass: 'lilac-bonus-toast'
            });
            
            // Increment display counter for this session
            const currentCount = parseInt(sessionStorage.getItem(settings.sessionKey) || '0');
            sessionStorage.setItem(settings.sessionKey, (currentCount + 1).toString());
            console.log('Lilac Bonus Debug - Display count incremented to:', currentCount + 1);
        }
        
        console.log('Lilac Bonus message shown:', hasPurchased ? 'purchaser' : 'general');
    };

    // Initialize the system
    const init = function() {
        // Wait for page to be fully loaded
        $(document).ready(function() {
            // Small delay to ensure all other systems are loaded
            setTimeout(showBonusMessage, 2000);
        });
    };

    // Expose functions for external use
    window.LilacBonusSystem = {
        init: init,
        showMessage: showBonusMessage,
        checkPurchaseStatus: checkUserPurchaseStatus,
        resetSession: function() {
            sessionStorage.removeItem(settings.sessionKey);
        }
    };

    // Auto-initialize
    init();

})(jQuery);