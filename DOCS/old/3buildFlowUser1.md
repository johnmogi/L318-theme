

## ✅ Task: Paid Practice-Only Subscription (No Book)

### 🎯 Purpose

Create a simplified purchase and access flow for the **“מנוי תרגול לאתר”** product — a standalone practice subscription for 10th-grade students, valid until June 30 each year.

---

### 🔧 Functional Requirements

#### 📦 Product Setup

* Product: **מנוי תרגול לאתר** (`/product/מנוי-תרגול-לאתר`)
* Price: **40 ₪**
* Product type: Simple (no variations)
* No shipping required
* No coupon code required

#### 🔐 Access Logic

* Upon successful purchase:

  * Automatically enroll the user into the **“חינוך תעבורתי”** course
  * Set access expiration date to **June 30** of the current academic year (e.g., `llm_subscription_expiry`)
  * Ensure LearnDash access is blocked after expiry

#### 🧾 Checkout Behavior

* On "Purchase Now" button:

  * ✅ Redirect user directly to: `/checkout/?add-to-cart=[product_id]`
  * ❌ Prevent adding the product to the cart multiple times (if already exists)

#### 🔄 Post-Purchase Redirection

* After WooCommerce order is marked as “Completed”:

  * Redirect the user to the course page:
    `https://testli.local/courses/חינוך-תעבורתי/`
  * Not to `/my-courses/`

---

### 🐞 Issues to Fix

* **Cart page** behaves unpredictably (multiple timers, duplicate checkouts)

  * Simplify or bypass cart completely for this product
* **Post-login redirection** for users already enrolled should point to:
  `https://testli.local/courses/חינוך-תעבורתי/`
* Remove or hide the **“Expand All”** course overview page (not in use)

---

### 📌 Summary of Key Logic

| Feature            | Behavior                           |
| ------------------ | ---------------------------------- |
| Subscription type  | Paid annual access, no book        |
| Expiry enforcement | Ends 30/06, enforced via LearnDash |
| Shipping           | ❌ No shipping or address needed    |
| Promo code         | ❌ Not required                     |
| After payment      | ✅ Auto-enroll in course + redirect |
| Course page        | `/courses/חינוך-תעבורתי/`          |




Build Items for Practice Subscription Flow
Product Configuration
[ V ] Create "מנוי תרגול לאתר" product
[V ] Set price to 40₪
[ V ] Configure as simple product
[ X ] Disable shipping requirements
User Registration & Login
[ X] Custom registration form for student details
[ V ] Phone/email login functionality
[ X] User role assignment (student_private)
Course Access Management
[ ] Automatic enrollment in "חינוך תעבורתי" course
[ ] Set subscription expiry to June 30 of current academic year
[ ] Implement access control for expired subscriptions
Purchase Flow
[ ] Custom "Purchase Now" button
[ ] Redirect to registration if not logged in
[ ] Direct checkout flow (skip cart)
[ ] Payment processing integration
Post-Purchase Actions
[ ] Course enrollment trigger
[ ] Subscription expiry date setting
[ ] Confirmation email/SMS
[ ] Welcome email with access details
Access Control
[ ] Check subscription status on course access
[ ] Restrict access after expiry
[ ] Graceful expiry notifications
Testing & Validation
[ ] Test purchase flow
[ ] Verify course enrollment
[ ] Test subscription expiry
[ ] Mobile responsiveness



“ספר פיזי” לעומת “קורס אונ־ליין” – איך נראים שני תהליכי-Checkout שונים
ספר / ערכת ספרים (Physical)	קורס / מנוי (Virtual)
מוצר בחנות	Simple / Variable Product רגיל עם משקל, “דורש משלוח” ✓	Virtual & Downloadable Product → לא צריך משלוח ✗
שדות חובה	• שם פרטי / משפחה
• טלפון (שם־משתמש)
• ת.ז (סיסמה)
• אימייל (×2)
• כתובת משלוח (עיר, רחוב, טלפון לשליח)
• שיטת משלוח (- משלוח / איסוף)	• שם פרטי / משפחה
• טלפון (שם־משתמש) ×2
• ת.ז ×2
• אימייל ×2
• בחירת קורס • קוד־קופון (אופציונלי)
• אין כתובת / שיטת-משלוח
כפתורי תשלום	“תשלום בביט” ⁄ “תשלום באשראי”	“תשלום באשראי” ⁄ “ביט”
לוגיקת Backend	• needs_shipping = true
• פקודות ל-WooCommerce Shipping + חישובי דמי-משלוח (אם יש).
• סטטוס הזמנה = Processing עד “סופק”.	• needs_shipping = false (virtual)
• בעת completed – פותחים מנוי ומפילים אימייל קבלת-פנים.
הערות / Hooks	על כפתור Place Order להציג אזהרה: “ההרשמה לאתר תתבצע רק לאחר קבלת הספר וקוד ההטבה המצורף.”	אחרי payment_complete – קריאה ל-API שמקים אוטומטית User + Subscription + Course access.

“כיבוי” שדות המשלוח באופן דינמי (3 מצבים)
סל עם מוצר(ים) פיזיים בלבד

php
Copiar
Editar
add_filter('woocommerce_cart_needs_shipping', '__return_true');
סל עם מוצר(ים) וירטואליים בלבד (קורסים) – מסתירים כתובת/משלוח:

php
Copiar
Editar
add_filter('woocommerce_cart_needs_shipping', '__return_false');
add_filter('woocommerce_checkout_fields', function($f){
  unset($f['shipping']);          // מסיר בלוק כתובת משלוח
  return $f;
});
סל “מעורב”
בדרך-כלל לא מרשים לערבב ספר + קורס; אם כן – משאירים משלוח פתוח.
אפשר לחסום:

php
Copiar
Editar
add_action('woocommerce_add_to_cart_validation', function($valid,$pid){
  $is_virtual = get_post_meta($pid,'_virtual',true)=='yes';
  $has_physical = WC()->cart->needs_shipping();
  if($is_virtual && $has_physical){ wc_add_notice('לא ניתן לשלב קורס עם מוצר פיזי בעגלה.','error'); return false; }
  return $valid;
},10,2);
התאמות Elementor / WooCommerce בממשק
Checkout Template 1 – ספר

html
Copiar
Editar
<section class="e-checkout book">
  <!-- Billing + Shipping columns -->
  <div class="e-col">
    <e-field label="עיר למשלוח">...</e-field>
    ...
  </div>
</section>
Checkout Template 2 – קורס

html
Copiar
Editar
<section class="e-checkout course">
  <!-- רק Billing → ללא “כתובת משלוח” -->
  <e-field label="טלפון (שם משתמש)">...</e-field>
  <e-field label="קוד קורס (אוטומטי)" disabled />
  ...
</section>
ניתן לטעון תבנית Elementor שונה לפי סוג-מוצר באמצעות
add_filter('woocommerce_locate_template', ...) או באמצעות Display Conditions של Elementor Pro.

סיכום
ספרים – שדות משלוח, שיטת משלוח, דוא״ל אזהרה ‘קוד הטבה בספר’.

קורסים / מנויים – Checkout מצומצם, ללא כתובת משלוח; לאחר תשלום יוצרים אוטומטית מנוי ומפיצים ✉️ “ברוכים הבאים”.

פיצול-לוגיקה ע״פ Virtual Product + קטגוריה מאפשר ניהול אחיד ונקי.

