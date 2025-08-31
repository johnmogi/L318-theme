Here’s how you can specify your request to your developer regarding a **modular, reversible plugin/extension** for managing special features in quizzes like hint toggling:

---

### ✅ Feature Request: Modular Admin Plugin for Quiz Enhancements

**Purpose:**
To develop a self-contained WordPress plugin/extension that allows the admin to manage optional enhancements (e.g. hints, timers, difficulty toggles) for LearnDash quizzes **without modifying the quiz post content directly**—making it reversible and manageable from a central admin screen.

---

### 📌 Plugin Requirements

**1. Admin Panel (Plugin Page UI)**

* Located under a custom menu (e.g., “Quiz Extensions”).
* List of all quizzes (by title, ID, or dropdown selection).
* Toggles for enabling/disabling per-feature options:

  * ✅ Show “Hint” button
  * ⏰ Enable Timer Notifications
  * 🧪 Custom Instructions per Quiz
* Option to reset/clear modifications.

**2. Reversible Behavior**

* No direct DB updates to quiz post content or LearnDash core tables.
* Store settings in plugin options table or a custom post meta with clear namespace (e.g., `_quiz_ext_hints_enabled`).
* Hook into `wpProQuiz_front` or `learndash_template_script` via filters/actions to apply enhancements dynamically.

**3. Compatibility with Native Quizzes**

* The plugin must work using **quiz ID or slug**. Do **not hardcode** based on post classes or DOM selectors alone.
* It should auto-detect whether the quiz is a `wpProQuiz` or `LearnDash` quiz.

**4. Extensibility**

* Easy to add future enhancements (e.g., text-to-speech, accessibility options).
* Architecture must separate UI, settings logic, and output hooks.

---

### ⚠️ Known Issues to Address (From Current Setup)

1. **Hardcoded Hint Logic**: Hints currently render based on embedded inline JS and ACF meta. Needs decoupling.
2. **DOM-dependent activation**: Feature buttons are injected into DOM directly; this limits flexibility.
3. **Conflicts with LearnDash styling/scripts**: Ensure no override or unexpected UI changes occur.
4. **Performance**: Avoid loading enhancements on pages where they're not needed.

---

### 🧩 How It Affects Native Quizzes

* **Quiz Selection**: Use a dropdown (autocomplete) to select quizzes by title/post ID for applying features.
* **Isolation**: Enhancements are applied as overlays/scripts without touching core quiz logic or altering existing quiz flow.

---

Would you like a visual wireframe or feature flow to accompany this spec?




this is a sample test
<body class="rtl wp-singular sfwd-quiz-template-default single single-sfwd-quiz postid-1367 logged-in wp-embed-responsive wp-theme-hello-elementor wp-child-theme-hello-theme-child-master theme-hello-elementor woocommerce-js theme-default elementor-default elementor-kit-11 learndash-cpt learndash-cpt-sfwd-quiz learndash-template-ld30 learndash-cpt-sfwd-quiz-1367-current learndash-embed-responsive e--ua-isTouchDevice e--ua-blink e--ua-chrome e--ua-webkit" data-elementor-device-mode="desktop">


<a class="skip-link screen-reader-text" href="#content">דלג לתוכן</a>

		<div data-elementor-type="header" data-elementor-id="891" class="elementor elementor-891 elementor-location-header" data-elementor-post-type="elementor_library">
			<div class="elementor-element elementor-element-f337361 e-con-full e-flex e-con e-parent e-lazyloaded" data-id="f337361" data-element_type="container" data-settings="{&quot;background_background&quot;:&quot;classic&quot;}">
		<div class="elementor-element elementor-element-9061211 e-con-full e-flex e-con e-child" data-id="9061211" data-element_type="container">
		<div class="elementor-element elementor-element-3a954a0 e-con-full e-flex e-con e-child" data-id="3a954a0" data-element_type="container">
		<div class="elementor-element elementor-element-319e0d8 e-con-full e-flex e-con e-child" data-id="319e0d8" data-element_type="container">
				<div class="elementor-element elementor-element-c532764 elementor-widget elementor-widget-image" data-id="c532764" data-element_type="widget" data-widget_type="image.default">
				<div class="elementor-widget-container">
																<a href="https://lilac.local">
							<img fetchpriority="high" width="978" height="283" src="https://lilac.local/wp-content/uploads/2025/02/לוגו-עם-טקסט.png" class="attachment-full size-full wp-image-1076" alt="" srcset="https://lilac.local/wp-content/uploads/2025/02/לוגו-עם-טקסט.png 978w, https://lilac.local/wp-content/uploads/2025/02/לוגו-עם-טקסט-600x174.png 600w, https://lilac.local/wp-content/uploads/2025/02/לוגו-עם-טקסט-300x87.png 300w, https://lilac.local/wp-content/uploads/2025/02/לוגו-עם-טקסט-768x222.png 768w" sizes="(max-width: 978px) 100vw, 978px">								</a>
															</div>
				</div>
				</div>
				</div>
		<div class="elementor-element elementor-element-4e296e6 e-con-full e-flex e-con e-child" data-id="4e296e6" data-element_type="container">
				<div class="elementor-element elementor-element-c3fb80f elementor-nav-menu--dropdown-mobile elementor-nav-menu--stretch elementor-nav-menu__text-align-aside elementor-nav-menu--toggle elementor-nav-menu--burger elementor-widget elementor-widget-nav-menu" data-id="c3fb80f" data-element_type="widget" data-settings="{&quot;full_width&quot;:&quot;stretch&quot;,&quot;layout&quot;:&quot;horizontal&quot;,&quot;submenu_icon&quot;:{&quot;value&quot;:&quot;<svg class=\&quot;e-font-icon-svg e-fas-caret-down\&quot; viewBox=\&quot;0 0 320 512\&quot; xmlns=\&quot;http:\/\/www.w3.org\/2000\/svg\&quot;><path d=\&quot;M31.3 192h257.3c17.8 0 26.7 21.5 14.1 34.1L174.1 354.8c-7.8 7.8-20.5 7.8-28.3 0L17.2 226.1C4.6 213.5 13.5 192 31.3 192z\&quot;><\/path><\/svg>&quot;,&quot;library&quot;:&quot;fa-solid&quot;},&quot;toggle&quot;:&quot;burger&quot;}" data-widget_type="nav-menu.default">
				<div class="elementor-widget-container">
								<nav aria-label="תפריט" class="elementor-nav-menu--main elementor-nav-menu__container elementor-nav-menu--layout-horizontal e--pointer-none">
				<ul id="menu-1-c3fb80f" class="elementor-nav-menu" data-smartmenus-id="1746604469130312"><li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1054"><a href="#" class="elementor-item elementor-item-anchor">מי אנחנו</a></li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-has-children menu-item-1055"><a href="#" class="elementor-item elementor-item-anchor has-submenu" id="sm-1746604469130312-1" aria-haspopup="true" aria-controls="sm-1746604469130312-2" aria-expanded="false">למה ללמוד אצלנו<span class="sub-arrow"><svg class="e-font-icon-svg e-fas-caret-down" viewBox="0 0 320 512" xmlns="http://www.w3.org/2000/svg"><path d="M31.3 192h257.3c17.8 0 26.7 21.5 14.1 34.1L174.1 354.8c-7.8 7.8-20.5 7.8-28.3 0L17.2 226.1C4.6 213.5 13.5 192 31.3 192z"></path></svg></span></a>
<ul class="sub-menu elementor-nav-menu--dropdown" id="sm-1746604469130312-2" role="group" aria-hidden="true" aria-labelledby="sm-1746604469130312-1" aria-expanded="false">
	<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1230"><a href="#" class="elementor-sub-item elementor-item-anchor">חינוך תעבורתי</a></li>
</ul>
</li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1056"><a href="#" class="elementor-item elementor-item-anchor">שאלות ותשובות</a></li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-has-children menu-item-1057"><a href="#" class="elementor-item elementor-item-anchor has-submenu" id="sm-1746604469130312-3" aria-haspopup="true" aria-controls="sm-1746604469130312-4" aria-expanded="false">טיפים למבחן התיאוריה<span class="sub-arrow"><svg class="e-font-icon-svg e-fas-caret-down" viewBox="0 0 320 512" xmlns="http://www.w3.org/2000/svg"><path d="M31.3 192h257.3c17.8 0 26.7 21.5 14.1 34.1L174.1 354.8c-7.8 7.8-20.5 7.8-28.3 0L17.2 226.1C4.6 213.5 13.5 192 31.3 192z"></path></svg></span></a>
<ul class="sub-menu elementor-nav-menu--dropdown" id="sm-1746604469130312-4" role="group" aria-hidden="true" aria-labelledby="sm-1746604469130312-3" aria-expanded="false">
	<li class="menu-item menu-item-type-post_type menu-item-object-tipes menu-item-1228"><a href="https://lilac.local/tipes/6-%d7%98%d7%99%d7%a4%d7%99%d7%9d-%d7%97%d7%a9%d7%95%d7%91%d7%99%d7%9d-%d7%9c%d7%97%d7%99%d7%a0%d7%95%d7%9a-%d7%aa%d7%a2%d7%91%d7%95%d7%a8%d7%aa%d7%99-%d7%9c%d7%9e%d7%91%d7%97%d7%9f-%d7%94%d7%aa/" class="elementor-sub-item">6 טיפים חשובים לחינוך תעבורתי – למבחן התיאוריה בבתי הספר</a></li>
	<li class="menu-item menu-item-type-post_type menu-item-object-tipes menu-item-1229"><a href="https://lilac.local/tipes/%d7%98%d7%99%d7%a4-7-%d7%98%d7%99%d7%a4-%d7%91%d7%98%d7%99%d7%97%d7%95%d7%aa%d7%99-%d7%97%d7%a9%d7%95%d7%91-%d7%9c%d7%9e%d7%91%d7%97%d7%9f/" class="elementor-sub-item">טיפ 7 – טיפ בטיחותי חשוב למבחן</a></li>
	<li class="menu-item menu-item-type-post_type menu-item-object-tipes menu-item-1227"><a href="https://lilac.local/tipes/%d7%a1%d7%a8%d7%98%d7%95%d7%9f-%d7%94%d7%a8%d7%a9%d7%9e%d7%94-%d7%9c%d7%9c%d7%90-%d7%a7%d7%95%d7%93-%d7%94%d7%98%d7%91%d7%94-%d7%90%d7%99%d7%9a-%d7%9c%d7%94%d7%a4%d7%a8%d7%99%d7%93-%d7%91%d7%99/" class="elementor-sub-item">סרטון – הרשמה ללא קוד הטבה: איך להפריד בין הרישום לפתיחת המנוי?</a></li>
</ul>
</li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1058"><a href="#" class="elementor-item elementor-item-anchor">תנו מילה טובה</a></li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1059"><a href="#" class="elementor-item elementor-item-anchor">ספרי ההוצאה</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-1192"><a href="https://lilac.local/%d7%aa%d7%a7%d7%a0%d7%95%d7%9f/" class="elementor-item">תקנון</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-1184"><a href="https://lilac.local/%d7%99%d7%a6%d7%99%d7%a8%d7%aa-%d7%a7%d7%a9%d7%a8/" class="elementor-item">יצירת קשר</a></li>
<li class="ld-button menu-item menu-item-type-custom menu-item-object-custom menu-item-1115"><a href="https://lilac.local/wp-login.php?action=logout&amp;redirect_to=https%3A%2F%2Flilac.local%2Fquizzes%2F%25d7%2594%25d7%259e%25d7%25a8%25d7%2597%25d7%2591-%25d7%2594%25d7%25aa%25d7%25a2%25d7%2591%25d7%2595%25d7%25a8%25d7%25aa%25d7%2599-%25d7%2594%25d7%2592%25d7%2593%25d7%25a8%25d7%2594-%25d7%2595%25d7%259e%25d7%2590%25d7%25a4%25d7%2599%25d7%2599%25d7%25a0%25d7%2599%25d7%259d-2%2F&amp;_wpnonce=676449b004" class="elementor-item">התחברות</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-1231"><a href="https://lilac.local/testlogin/" class="elementor-item">test</a></li>
</ul>			</nav>
					<div class="elementor-menu-toggle" role="button" tabindex="0" aria-label="כפתור פתיחת תפריט" aria-expanded="false" style="">
			<svg aria-hidden="true" role="presentation" class="elementor-menu-toggle__icon--open e-font-icon-svg e-eicon-menu-bar" viewBox="0 0 1000 1000" xmlns="http://www.w3.org/2000/svg"><path d="M104 333H896C929 333 958 304 958 271S929 208 896 208H104C71 208 42 237 42 271S71 333 104 333ZM104 583H896C929 583 958 554 958 521S929 458 896 458H104C71 458 42 487 42 521S71 583 104 583ZM104 833H896C929 833 958 804 958 771S929 708 896 708H104C71 708 42 737 42 771S71 833 104 833Z"></path></svg><svg aria-hidden="true" role="presentation" class="elementor-menu-toggle__icon--close e-font-icon-svg e-eicon-close" viewBox="0 0 1000 1000" xmlns="http://www.w3.org/2000/svg"><path d="M742 167L500 408 258 167C246 154 233 150 217 150 196 150 179 158 167 167 154 179 150 196 150 212 150 229 154 242 171 254L408 500 167 742C138 771 138 800 167 829 196 858 225 858 254 829L496 587 738 829C750 842 767 846 783 846 800 846 817 842 829 829 842 817 846 804 846 783 846 767 842 750 829 737L588 500 833 258C863 229 863 200 833 171 804 137 775 137 742 167Z"></path></svg>		</div>
					<nav class="elementor-nav-menu--dropdown elementor-nav-menu__container" aria-hidden="true" style="top: 43.9815px; --menu-height: 0; width: 2343px; right: -2103.92px;">
				<ul id="menu-2-c3fb80f" class="elementor-nav-menu" data-smartmenus-id="1746604469132061"><li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1054"><a href="#" class="elementor-item elementor-item-anchor" tabindex="-1">מי אנחנו</a></li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-has-children menu-item-1055"><a href="#" class="elementor-item elementor-item-anchor has-submenu" tabindex="-1" id="sm-1746604469132061-1" aria-haspopup="true" aria-controls="sm-1746604469132061-2" aria-expanded="false">למה ללמוד אצלנו<span class="sub-arrow"><svg class="e-font-icon-svg e-fas-caret-down" viewBox="0 0 320 512" xmlns="http://www.w3.org/2000/svg"><path d="M31.3 192h257.3c17.8 0 26.7 21.5 14.1 34.1L174.1 354.8c-7.8 7.8-20.5 7.8-28.3 0L17.2 226.1C4.6 213.5 13.5 192 31.3 192z"></path></svg></span></a>
<ul class="sub-menu elementor-nav-menu--dropdown" id="sm-1746604469132061-2" role="group" aria-hidden="true" aria-labelledby="sm-1746604469132061-1" aria-expanded="false">
	<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1230"><a href="#" class="elementor-sub-item elementor-item-anchor" tabindex="-1">חינוך תעבורתי</a></li>
</ul>
</li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1056"><a href="#" class="elementor-item elementor-item-anchor" tabindex="-1">שאלות ותשובות</a></li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-has-children menu-item-1057"><a href="#" class="elementor-item elementor-item-anchor has-submenu" tabindex="-1" id="sm-1746604469132061-3" aria-haspopup="true" aria-controls="sm-1746604469132061-4" aria-expanded="false">טיפים למבחן התיאוריה<span class="sub-arrow"><svg class="e-font-icon-svg e-fas-caret-down" viewBox="0 0 320 512" xmlns="http://www.w3.org/2000/svg"><path d="M31.3 192h257.3c17.8 0 26.7 21.5 14.1 34.1L174.1 354.8c-7.8 7.8-20.5 7.8-28.3 0L17.2 226.1C4.6 213.5 13.5 192 31.3 192z"></path></svg></span></a>
<ul class="sub-menu elementor-nav-menu--dropdown" id="sm-1746604469132061-4" role="group" aria-hidden="true" aria-labelledby="sm-1746604469132061-3" aria-expanded="false">
	<li class="menu-item menu-item-type-post_type menu-item-object-tipes menu-item-1228"><a href="https://lilac.local/tipes/6-%d7%98%d7%99%d7%a4%d7%99%d7%9d-%d7%97%d7%a9%d7%95%d7%91%d7%99%d7%9d-%d7%9c%d7%97%d7%99%d7%a0%d7%95%d7%9a-%d7%aa%d7%a2%d7%91%d7%95%d7%a8%d7%aa%d7%99-%d7%9c%d7%9e%d7%91%d7%97%d7%9f-%d7%94%d7%aa/" class="elementor-sub-item" tabindex="-1">6 טיפים חשובים לחינוך תעבורתי – למבחן התיאוריה בבתי הספר</a></li>
	<li class="menu-item menu-item-type-post_type menu-item-object-tipes menu-item-1229"><a href="https://lilac.local/tipes/%d7%98%d7%99%d7%a4-7-%d7%98%d7%99%d7%a4-%d7%91%d7%98%d7%99%d7%97%d7%95%d7%aa%d7%99-%d7%97%d7%a9%d7%95%d7%91-%d7%9c%d7%9e%d7%91%d7%97%d7%9f/" class="elementor-sub-item" tabindex="-1">טיפ 7 – טיפ בטיחותי חשוב למבחן</a></li>
	<li class="menu-item menu-item-type-post_type menu-item-object-tipes menu-item-1227"><a href="https://lilac.local/tipes/%d7%a1%d7%a8%d7%98%d7%95%d7%9f-%d7%94%d7%a8%d7%a9%d7%9e%d7%94-%d7%9c%d7%9c%d7%90-%d7%a7%d7%95%d7%93-%d7%94%d7%98%d7%91%d7%94-%d7%90%d7%99%d7%9a-%d7%9c%d7%94%d7%a4%d7%a8%d7%99%d7%93-%d7%91%d7%99/" class="elementor-sub-item" tabindex="-1">סרטון – הרשמה ללא קוד הטבה: איך להפריד בין הרישום לפתיחת המנוי?</a></li>
</ul>
</li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1058"><a href="#" class="elementor-item elementor-item-anchor" tabindex="-1">תנו מילה טובה</a></li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1059"><a href="#" class="elementor-item elementor-item-anchor" tabindex="-1">ספרי ההוצאה</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-1192"><a href="https://lilac.local/%d7%aa%d7%a7%d7%a0%d7%95%d7%9f/" class="elementor-item" tabindex="-1">תקנון</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-1184"><a href="https://lilac.local/%d7%99%d7%a6%d7%99%d7%a8%d7%aa-%d7%a7%d7%a9%d7%a8/" class="elementor-item" tabindex="-1">יצירת קשר</a></li>
<li class="ld-button menu-item menu-item-type-custom menu-item-object-custom menu-item-1115"><a href="https://lilac.local/wp-login.php?action=logout&amp;redirect_to=https%3A%2F%2Flilac.local%2Fquizzes%2F%25d7%2594%25d7%259e%25d7%25a8%25d7%2597%25d7%2591-%25d7%2594%25d7%25aa%25d7%25a2%25d7%2591%25d7%2595%25d7%25a8%25d7%25aa%25d7%2599-%25d7%2594%25d7%2592%25d7%2593%25d7%25a8%25d7%2594-%25d7%2595%25d7%259e%25d7%2590%25d7%25a4%25d7%2599%25d7%2599%25d7%25a0%25d7%2599%25d7%259d-2%2F&amp;_wpnonce=676449b004" class="elementor-item" tabindex="-1">התחברות</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-1231"><a href="https://lilac.local/testlogin/" class="elementor-item" tabindex="-1">test</a></li>
</ul>			</nav>
						</div>
				</div>
				</div>
				</div>
				</div>
		<div class="elementor-element elementor-element-87e3881 e-con-full e-flex e-con e-parent e-lazyloaded" data-id="87e3881" data-element_type="container" data-settings="{&quot;background_background&quot;:&quot;classic&quot;}">
				<div class="elementor-element elementor-element-103af19 elementor-widget__width-initial elementor-widget elementor-widget-shortcode" data-id="103af19" data-element_type="widget" data-widget_type="shortcode.default">
				<div class="elementor-widget-container">
							<div class="elementor-shortcode">        <div class="lilac-user-widget lilac-user-logged-in">
            <div class="lilac-user-wrap">
                                <div class="lilac-profile-pic">
                    <a href="https://lilac.local/quizzes/%d7%94%d7%9e%d7%a8%d7%97%d7%91-%d7%94%d7%aa%d7%a2%d7%91%d7%95%d7%a8%d7%aa%d7%99-%d7%94%d7%92%d7%93%d7%a8%d7%94-%d7%95%d7%9e%d7%90%d7%a4%d7%99%d7%99%d7%a0%d7%99%d7%9d-2/">
                        <img src="https://secure.gravatar.com/avatar/?s=60&amp;d=mm&amp;r=g" alt="7 7">
                    </a>
                </div>
                                
                <div class="lilac-profile-details">
                    <h3>שלום, 7 7!</h3>
                                    </div>
            </div>
            
                        
            <div class="lilac-profile-links">
                                
                <div class="lilac-edit">
                    <a href="https://lilac.local/quizzes/%d7%94%d7%9e%d7%a8%d7%97%d7%91-%d7%94%d7%aa%d7%a2%d7%91%d7%95%d7%a8%d7%aa%d7%99-%d7%94%d7%92%d7%93%d7%a8%d7%94-%d7%95%d7%9e%d7%90%d7%a4%d7%99%d7%99%d7%a0%d7%99%d7%9d-2/">
                        ערוך חשבון
                    </a>
                </div>
                
                                <div class="lilac-stats">
                    <a href="https://lilac.local/quizzes/%d7%94%d7%9e%d7%a8%d7%97%d7%91-%d7%94%d7%aa%d7%a2%d7%91%d7%95%d7%a8%d7%aa%d7%99-%d7%94%d7%92%d7%93%d7%a8%d7%94-%d7%95%d7%9e%d7%90%d7%a4%d7%99%d7%99%d7%a0%d7%99%d7%9d-2/">
                        סטטיסטיקות לימוד
                    </a>
                </div>
                                
                <div class="lilac-logout">
                    <a href="https://lilac.local/wp-login.php?action=logout&amp;redirect_to=https%3A%2F%2Flilac.local&amp;_wpnonce=676449b004">
                        התנתק
                    </a>
                </div>
            </div>
        </div>
        </div>
						</div>
				</div>
				</div>
				</div>
		
<main id="content" class="site-main post-1367 sfwd-quiz type-sfwd-quiz status-publish hentry">

			<div class="page-header">
			<h1 class="entry-title">TEST with hint 2</h1>		</div>
	
	<div class="page-content">
		<div class="learndash user_has_no_access" id="learndash_post_1367"><div class="learndash-wrapper">

<div class="ld-tabs ld-tab-count-1">
	
	<div class="ld-tabs-content">
		
			<div aria-labelledby="ld-content-tab-1367" class="ld-tab-content ld-visible" id="ld-tab-content-1367" role="tabpanel" tabindex="0">
								<p>לפניך שאלות לפי נושאי לימוד למבחן התיאוריה. תשובה שגויה מסומנת באדום.<br>
באתר ישנו מנגנון ייחודי של קבלת רמזים לכל שאלה. ללמידה נכונה העזרו במנגון הרמזים. לתיקון התשובה לחצו: קבל רמז!</p>
<p><img decoding="async" src="https://lilac.local/wp-content/uploads/2025/02/noPic.png" alt="" width="290" height="180"></p>
<p><a role="button"><br>
קח רמז<br>
</a></p>
			</div>

			
	</div> <!--/.ld-tabs-content-->

</div> <!--/.ld-tabs-->
		<div class="wpProQuiz_content" id="wpProQuiz_9" data-quiz-meta="{&quot;quiz_pro_id&quot;:9,&quot;quiz_post_id&quot;:1367}">
			<div class="wpProQuiz_spinner" style="display: none;">
				<div></div>
			</div>
			<div style="display: none;" class="wpProQuiz_time_limit">
	<div class="time">
		זמן מוגבל: <span>0</span>	</div>
	<div class="wpProQuiz_progress"></div>
</div>
<div class="wpProQuiz_checkPage" style="display: none;">
	<h4 class="wpProQuiz_header">
	תקציר המבחן	</h4>
	<p><span>0</span> מתוך 8 שאלות הושלמו</p>	<p>שאלות:</p>
	<div class="wpProQuiz_reviewSummary"></div>

	
	<input type="button" name="endQuizSummary" value="סיים מבחן" class="wpProQuiz_button"> </div>
<div class="wpProQuiz_infopage" style="display: none;">
	<h4>מידע</h4>
		<input type="button" name="endInfopage" value="סיים מבחן" class="wpProQuiz_button"> </div>
<div class="wpProQuiz_text" style="display: none;">
		<div>
		<input class="wpProQuiz_button" type="button" value="התחל מבחן" name="startQuiz">	</div>
</div>
<div style="display: none;" class="wpProQuiz_lock">		
	<p>כבר השלמת את המבחן בעבר. לכן אינך יכול להתחיל אותו שוב.</p></div>
<div style="display: none;" class="wpProQuiz_loadQuiz">
	<p>
		המבחן נטען…	</p>
</div>
<div style="display: none;" class="wpProQuiz_startOnlyRegisteredUser">
	<p>עליך להתחבר או להירשם כדי להתחיל את המבחן.</p></div>
<div style="display: none;" class="wpProQuiz_prerequisite">
	<p>אתה חייב להשלים קודם את הבאים: <span></span></p></div>
<div style="display: none;" class="wpProQuiz_sending">
	<h4 class="wpProQuiz_header">תוצאות</h4>
	<p>
		</p><div>
		מבחןהושלם. התוצאות נרשמות.		</div>
		<div>
			<dd class="course_progress">
				<div class="course_progress_blue sending_progress_bar" style="width: 0%;">
				</div>
			</dd>
		</div>
	<p></p>
</div>

<div style="display: none;" class="wpProQuiz_results">
	<h4 class="wpProQuiz_header">תוצאות</h4>
	<p><span class="wpProQuiz_correct_answer">0</span> מתוך <span>8</span> שאלות נענו נכון</p>		<p class="wpProQuiz_quiz_time">
		הזמן שלך: <span></span>		</p>
			<p class="wpProQuiz_time_limit_expired" style="display: none;">
	זמן חלף	</p>

			<p class="wpProQuiz_points">
		השגת <span>0</span> מתוך <span>0</span> נקודה(ות), (<span>0</span>)		</p>
		<p class="wpProQuiz_graded_points" style="display: none;">
		נקודה(ות) שקיבלת: <span>0</span> מתוך <span>0</span>, (<span>0</span>)		<br>
		<span>0</span> שאלות פתוחות בהמתנה (נקודה(ות) אפשרית(יות): <span>0</span>)		<br>
		</p>
				<p class="wpProQuiz_certificate" style="display: none ;"></p>
		<script>var certificate_details = [];</script><script>var certificate_pending = "התעודה בהמתנה - עדיין צריך לתת ציון לשאלות, אנא בדוק את הפרופיל שלך";</script><script>var continue_details ='';</script>		
	<div class="wpProQuiz_catOverview" style="display:none;">
		<h4>
		קטגוריות		</h4>

		<div style="margin-top: 10px;">
			<ol>
							<li data-category_id="0">
					<span class="wpProQuiz_catName">כללי</span>
					<span class="wpProQuiz_catPercent">0%</span>
				</li>
							</ol>
		</div>
	</div>
	<div>
		<ul class="wpProQuiz_resultsList">
							<li style="display: none;">
					<div>
											</div>
				</li>
					</ul>
	</div>
		<div class="ld-quiz-actions" style="margin: 10px 0px;">
				<div class="quiz_continue_link
				">

		</div>
					<input class="wpProQuiz_button wpProQuiz_button_restartQuiz" type="button" name="restartQuiz" value="התחל מבחן מחדש">						<input class="wpProQuiz_button wpProQuiz_button_reShowQuestion" type="button" name="reShowQuestion" value="הצג שאלות">					</div>
</div>
<div class="wpProQuiz_reviewDiv" style="">
	<div class="wpProQuiz_reviewQuestion">
	<ol style="margin-top: 0px !important">
					<li class="wpProQuiz_reviewQuestionTarget wpProQuiz_reviewQuestionSolvedIncorrect">1</li>
					<li>2</li>
					<li>3</li>
					<li>4</li>
					<li>5</li>
					<li>6</li>
					<li>7</li>
					<li>8</li>
			</ol>
	<div style="display: none; top: 0px;"></div>
</div>
<div class="wpProQuiz_reviewLegend">
	<ol>
		<li class="learndash-quiz-review-legend-item-current">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewQuestion_Target"></span>
			<span class="wpProQuiz_reviewText">הנוכחי</span>
		</li>
		<li class="learndash-quiz-review-legend-item-review">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewColor_Review"></span>
			<span class="wpProQuiz_reviewText">ביקורת</span>
		</li>
		<li class="learndash-quiz-review-legend-item-answered">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewColor_Answer"></span>
			<span class="wpProQuiz_reviewText">נענו</span>
		</li>
		<li class="learndash-quiz-review-legend-item-correct">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewColor_AnswerCorrect"></span>
			<span class="wpProQuiz_reviewText">נכון</span>
		</li>
		<li class="learndash-quiz-review-legend-item-incorrect">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewColor_AnswerIncorrect"></span>
			<span class="wpProQuiz_reviewText">לא נכון</span>
		</li>
	</ol>
	<div style="clear: both;"></div>
</div>
<div class="wpProQuiz_reviewButtons">
			<input type="button" name="review" value="סקור שאלה" class="wpProQuiz_button2" style="float: left; display: block;"> 				<div style="clear: both;"></div>
	</div>
</div>
<div class="wpProQuiz_quizAnker" style="display: none;"></div>
<div style="" class="wpProQuiz_quiz">
	<ol class="wpProQuiz_list">
					<li class="wpProQuiz_listItem" style="" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:41,&quot;question_post_id&quot;:1369}">
				<div class="wpProQuiz_question_page" style="display:none;">
				שאלה <span>1</span> מתוך <span>8</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>1</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>מהם מרכיבי ה”מרחב התעבורתי”?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="41" data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;">1. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_41" value="1" disabled="disabled"> כל מה שנמצא בעולם, כולל הרכוש ומזג האוויר שבתוך הבתים.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem wpProQuiz_answerCorrectIncomplete" data-pos="1">
																			<span style="display:none;">2. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_41" value="2" disabled="disabled"> כל מה שנמצא בדרך וכל מי שמשתמש בדרך: פני הדרך, עוברי דרך, כלי הרכב ותנאי הסביבה.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;">3. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_41" value="3" disabled="disabled"> מרכיבים ששייכים לעולם התעבורה בלבד: עוברי דרך וכלי רכב.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem wpProQuiz_answerIncorrect" data-pos="3">
																			<span style="display:none;">4. </span>
										<label class="is-selected">
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_41" value="4" disabled="disabled"> מרכיבים ששייכים למרחב בלבד: פני הדרך ותנאי הסביבה.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="position: relative; display: none;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br>
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p><span class="highlight-hint">א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב. עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</span></p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br>
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left; margin-right: 10px;" data-question-lock="true"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; margin-right: 10px; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:42,&quot;question_post_id&quot;:1370}">
				<div class="wpProQuiz_question_page" style="display:none;">
				שאלה <span>2</span> מתוך <span>8</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>2</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>במרחב התעבורתי מתקיימים מפגשים רבים בין עוברי דרך בהם עליהם להתחשב אחד בשני. נכון או לא נכון?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="42" data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;">1. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_42" value="1"> לא נכון. אם עוברי הדרך ינהגו לפי הוראות החוק לא יתקיימו נקודות מגע ביניהם.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;">2. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_42" value="2"> לא נכון, בכל מקרה במרחב התעבורתי אין מפגשים בין עוברי דרך.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;">3. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_42" value="3"> לא נכון, כל אחד ממשתמשי הדרך נע בנתיב שלו ולא נפגש עם אחרים.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;">4. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_42" value="4"> נכון, מפגשים מתקיימים במרחב התעבורתי בכל שעות היום ובכל מקום בדרך.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br>
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p>א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. <span class="highlight-hint">ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</span></p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br>
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; margin-right: 10px;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:43,&quot;question_post_id&quot;:1371}">
				<div class="wpProQuiz_question_page" style="display:none;">
				שאלה <span>3</span> מתוך <span>8</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>3</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>הנהיגה היא משימה מורכבת. נכון או לא נכון?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="43" data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;">1. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_43" value="1"> לא נכון. רק כשיש עומס בדרכים לפעמים קשה לנהוג.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;">2. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_43" value="2"> לא נכון. רק נהגי משאיות כבדות מתעייפים בנהיגה בגלל משאם הכבד.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;">3. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_43" value="3"> נכון. מטלת הנהיגה דורשת ביצוע מספר מטלות במקביל ובזמן קצר וזה מורכב.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;">4. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_43" value="4"> נכון. אבל רק בגלל שלנהגים אין סבלנות והם לא מעניקים זכות קדימה כנדרש בחוק.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br>
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p>א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב. עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש</p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p><span class="highlight-hint">הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</span></p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br>
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; margin-right: 10px;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:44,&quot;question_post_id&quot;:1372}">
				<div class="wpProQuiz_question_page" style="display:none;">
				שאלה <span>4</span> מתוך <span>8</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>4</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>האם תלמיד נהיגה הוא חלק מהמרחב התעבורתי?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="44" data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;">1. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_44" value="1"> לא. רק אחרי שיקבל רישיון נהיגה יהפוך תלמיד נהיגה לחלק מהמרחב התעבורתי.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;">2. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_44" value="2"> לא. המרחב התעבורתי לא כולל תלמידי נהיגה הנוהגים ברכב ללימוד נהיגה.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;">3. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_44" value="3"> כן, אבל רק אם הוא נוהג בדרך בינעירונית.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;">4. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_44" value="4"> כן, כל מי שנמצא בדרך ומשתמש בה הוא חלק מהמרחב התעבורתי.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p><span class="highlight-hint">המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.</span><br>
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p>א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br>
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; margin-right: 10px;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:45,&quot;question_post_id&quot;:1373}">
				<div class="wpProQuiz_question_page" style="display:none;">
				שאלה <span>5</span> מתוך <span>8</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>5</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>האם יש סיכונים במרחב התעבורתי?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="45" data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;">1. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_45" value="1"> ממש לא.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;">2. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_45" value="2"> כן, רק בדרכים בינעירוניות.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;">3. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_45" value="3"> כן, המרחב התעבורתי מאופיין באי ודאות ובסכנות רבות.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;">4. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_45" value="4"> כן, רק בדרכים עירוניות צפופות.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br>
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p>א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p><span class="highlight-hint">נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</span></p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br>
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; margin-right: 10px;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:46,&quot;question_post_id&quot;:1374}">
				<div class="wpProQuiz_question_page" style="display:none;">
				שאלה <span>6</span> מתוך <span>8</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>6</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>מפגש בין עובר דרך לסביבה הוא:</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="46" data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;">1. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_46" value="1"> מפגש בצומת מרומזר בין הולך רגל לנהג.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;">2. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_46" value="2"> מפגש בין תמרור להולך רגל.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;">3. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_46" value="3"> מפגש בין רוכב אופניים חשמליים לנהג.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;">4. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_46" value="4"> נהיגה בשעה שקרני השמש מסנוורות את הנהג.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br>
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p><span class="highlight-hint">א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</span></p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br>
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; margin-right: 10px;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:47,&quot;question_post_id&quot;:1375}">
				<div class="wpProQuiz_question_page" style="display:none;">
				שאלה <span>7</span> מתוך <span>8</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>7</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>מפגש בין הדרך לעובר דרך הוא:</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="47" data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;">1. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_47" value="1"> מפגש בצומת מרומזר בין הולך רגל לנהג.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;">2. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_47" value="2"> מפגש בשעות הלילה בין נהג להולך רגל.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;">3. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_47" value="3"> מפגש ביום גשום בין רוכב אופניים חשמליים לנהג.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;">4. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_47" value="4"> נהיגה בשעה שקרני השמש מסנוורות את הנהג.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br>
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p><span class="highlight-hint">א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</span></p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br>
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; margin-right: 10px;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:48,&quot;question_post_id&quot;:1376}">
				<div class="wpProQuiz_question_page" style="display:none;">
				שאלה <span>8</span> מתוך <span>8</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>8</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>מהו “מרחב תעבורתי”?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="48" data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;">1. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_48" value="1"> מרחב תעבורתי הוא המרחב שבו כלי הרכב נעים בדרך.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;">2. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_48" value="2"> מרחב תעבורתי כולל את כל בני האדם המשתמשים בדרך.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;">3. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_48" value="3"> מרחב תעבורתי הוא מושג רחב הכולל את כל מי שנמצא בדרך ואת כל מי שמשתמש בדרך.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;">4. </span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off" type="radio" name="question_9_48" value="4"> מרחב תעבורתי הוא מושג רחב הכולל את כל מי שנמצא בדרך ואת כל מי שמשתמש בדרך, מלבד עגלות שאין להן מנוע.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p><span class="highlight-hint marked" id="hinted" style="transition: background-color 0.3s;">המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.</span><br>
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p>א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br>
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="סיים מבחן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; margin-right: 10px;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

			</ol>
	</div>
		</div>
		
</div> <!--/.learndash-wrapper-->
</div>
		
			</div>

	
</main>

			<div data-elementor-type="footer" data-elementor-id="1287" class="elementor elementor-1287 elementor-location-footer" data-elementor-post-type="elementor_library">
			<div class="elementor-element elementor-element-354aa413 e-flex e-con-boxed e-con e-parent e-lazyloaded" data-id="354aa413" data-element_type="container" data-settings="{&quot;background_background&quot;:&quot;classic&quot;}">
					<div class="e-con-inner">
				<div class="elementor-element elementor-element-2e4d3e28 elementor-widget elementor-widget-heading" data-id="2e4d3e28" data-element_type="widget" data-widget_type="heading.default">
				<div class="elementor-widget-container">
					<p class="elementor-heading-title elementor-size-default">© All Rights Reserved.</p>				</div>
				</div>
					</div>
				</div>
				</div>
		
			<script>
				const lazyloadRunObserver = () => {
					const lazyloadBackgrounds = document.querySelectorAll( `.e-con.e-parent:not(.e-lazyloaded)` );
					const lazyloadBackgroundObserver = new IntersectionObserver( ( entries ) => {
						entries.forEach( ( entry ) => {
							if ( entry.isIntersecting ) {
								let lazyloadBackground = entry.target;
								if( lazyloadBackground ) {
									lazyloadBackground.classList.add( 'e-lazyloaded' );
								}
								lazyloadBackgroundObserver.unobserve( entry.target );
							}
						});
					}, { rootMargin: '200px 0px 200px 0px' } );
					lazyloadBackgrounds.forEach( ( lazyloadBackground ) => {
						lazyloadBackgroundObserver.observe( lazyloadBackground );
					} );
				};
				const events = [
					'DOMContentLoaded',
					'elementor/lazyload/observe',
				];
				events.forEach( ( event ) => {
					document.addEventListener( event, lazyloadRunObserver );
				} );
			</script>
				<script>
		(function () {
			var c = document.body.className;
			c = c.replace(/woocommerce-no-js/, 'woocommerce-js');
			document.body.className = c;
		})();
	</script>
	<link rel="stylesheet" id="wc-blocks-style-rtl-css" href="https://lilac.local/wp-content/plugins/woocommerce/assets/client/blocks/wc-blocks-rtl.css?ver=1745821976" media="all">
<style id="core-block-supports-inline-css">
/**
 * Core styles: block-supports
 */

</style>
<script src="//lilac.local/wp-content/plugins/sfwd-lms/themes/legacy/templates/learndash_pager.js?ver=4.20.2.1-1746604468" id="learndash_pager_js-js"></script>
<script id="learndash_template_script_js-js-extra">
var sfwd_data = {"json":"{\"ajaxurl\":\"https:\\\/\\\/lilac.local\\\/wp-admin\\\/admin-ajax.php\"}"};
</script>
<script src="//lilac.local/wp-content/plugins/sfwd-lms/themes/legacy/templates/learndash_template_script.js?ver=4.20.2.1-1746604468" id="learndash_template_script_js-js"></script>
<script src="//lilac.local/wp-content/plugins/sfwd-lms/assets/js/jquery.dropdown.min.js?ver=4.20.2.1-1746604468" id="jquery-dropdown-js-js"></script>
<script id="ld-hints-script-js-extra">
var LD_HINTS_AJAX = {"ajax_url":"https:\/\/lilac.local\/wp-admin\/admin-ajax.php","nonce":"db374c5ea5"};
</script>
<script src="https://lilac.local/wp-content/themes/hello-theme-child-master/learndash-hints/js/learndash-hints.js" id="ld-hints-script-js"></script>
<script id="lilac-toast-js-extra">
var lilacToastData = {"sessionCounter":"15","ajaxUrl":"https:\/\/lilac.local\/wp-admin\/admin-ajax.php","nonce":"8920d1ba1f"};
</script>
<script src="https://lilac.local/wp-content/themes/hello-theme-child-master/src/Messaging/assets/js/toast.js?ver=1745390895" id="lilac-toast-js"></script>
<script id="lilac-timer-observer-js-before">
console.log('Lilac Quiz Timer Notifications loaded on: TEST with hint 2');
</script>
<script src="https://lilac.local/wp-content/themes/hello-theme-child-master/includes/quiz/timer-notifications/timer-observer.js?ver=1746456080" id="lilac-timer-observer-js"></script>
<script src="https://lilac.local/wp-content/themes/hello-theme-child-master/includes/quiz/timer-notifications/timer-ui.js?ver=1746456123" id="lilac-timer-ui-js"></script>
<script id="acf-quiz-hints-js-extra">
var quizHint = {"ajaxUrl":"https:\/\/lilac.local\/wp-admin\/admin-ajax.php","nonce":"fa7875b787"};
</script>
<script src="https://lilac.local/wp-content/themes/hello-theme-child-master/js/acf-quiz-hints.js?ver=1746506234" id="acf-quiz-hints-js"></script>
<script src="https://lilac.local/wp-content/themes/hello-elementor/assets/js/hello-frontend.js?ver=3.3.0" id="hello-theme-frontend-js"></script>
<script id="lilac-progress-js-extra">
var lilacProgressData = {"ajaxUrl":"https:\/\/lilac.local\/wp-admin\/admin-ajax.php","courseViews":[],"courseProgress":[],"lastActivity":"0","nonce":"b3b72e349d"};
</script>
<script src="https://lilac.local/wp-content/themes/hello-theme-child-master/src/Messaging/assets/js/progress.js?ver=1745374079" id="lilac-progress-js"></script>
<script id="learndash-front-js-extra">
var ldVars = {"postID":"1367","videoReqMsg":"\u05d0\u05ea\u05d4 \u05d7\u05d9\u05d9\u05d1 \u05dc\u05e6\u05e4\u05d5\u05ea \u05d1\u05e1\u05e8\u05d8\u05d5\u05df \u05db\u05d3\u05d9 \u05dc\u05d2\u05e9\u05ea \u05dc\u05ea\u05d5\u05db\u05df \u05d4\u05d6\u05d4","ajaxurl":"https:\/\/lilac.local\/wp-admin\/admin-ajax.php"};
</script>
<script src="//lilac.local/wp-content/plugins/sfwd-lms/themes/ld30/assets/js/learndash.js?ver=4.20.2.1-1746604468" id="learndash-front-js"></script>
<script src="https://lilac.local/wp-content/plugins/elementor-pro/assets/lib/smartmenus/jquery.smartmenus.js?ver=1.2.1" id="smartmenus-js"></script>
<script src="https://lilac.local/wp-content/plugins/woocommerce/assets/js/sourcebuster/sourcebuster.js?ver=9.8.2" id="sourcebuster-js-js"></script>
<script id="wc-order-attribution-js-extra">
var wc_order_attribution = {"params":{"lifetime":1.0000000000000001e-5,"session":30,"base64":false,"ajaxurl":"https:\/\/lilac.local\/wp-admin\/admin-ajax.php","prefix":"wc_order_attribution_","allowTracking":true},"fields":{"source_type":"current.typ","referrer":"current_add.rf","utm_campaign":"current.cmp","utm_source":"current.src","utm_medium":"current.mdm","utm_content":"current.cnt","utm_id":"current.id","utm_term":"current.trm","utm_source_platform":"current.plt","utm_creative_format":"current.fmt","utm_marketing_tactic":"current.tct","session_entry":"current_add.ep","session_start_time":"current_add.fd","session_pages":"session.pgs","session_count":"udata.vst","user_agent":"udata.uag"}};
</script>
<script src="https://lilac.local/wp-content/plugins/woocommerce/assets/js/frontend/order-attribution.js?ver=9.8.2" id="wc-order-attribution-js"></script>
<script src="https://lilac.local/wp-includes/js/jquery/ui/core.js?ver=1.13.3" id="jquery-ui-core-js"></script>
<script src="https://lilac.local/wp-includes/js/jquery/ui/mouse.js?ver=1.13.3" id="jquery-ui-mouse-js"></script>
<script src="https://lilac.local/wp-includes/js/jquery/ui/sortable.js?ver=1.13.3" id="jquery-ui-sortable-js"></script>
<script id="wpProQuiz_front_javascript-js-extra">
var WpProQuizGlobal = {"ajaxurl":"\/\/lilac.local\/wp-admin\/admin-ajax.php","loadData":"\u05d8\u05d5\u05e2\u05df","questionNotSolved":"\u05e2\u05dc\u05d9\u05da \u05dc\u05e2\u05e0\u05d5\u05ea \u05e2\u05dc \u05e9\u05d0\u05dc\u05d4 \u05d6\u05d5.","questionsNotSolved":"You must answer all \u05e9\u05d0\u05dc\u05d5\u05ea before you can complete the \u05de\u05d1\u05d7\u05df.","fieldsNotFilled":"\u05d7\u05d5\u05d1\u05d4 \u05dc\u05de\u05dc\u05d0 \u05d0\u05ea \u05db\u05dc \u05d4\u05e9\u05d3\u05d5\u05ea."};
</script>
<script src="https://lilac.local/wp-content/plugins/sfwd-lms/includes/lib/wp-pro-quiz/js/wpProQuiz_front.js?ver=4.20.2.1-1746604468" id="wpProQuiz_front_javascript-js"></script>
<script src="https://lilac.local/wp-content/plugins/woocommerce/assets/js/jquery-cookie/jquery.cookie.js?ver=1.4.1-wc.9.8.2" id="jquery-cookie-js" defer="" data-wp-strategy="defer"></script>
<script src="https://lilac.local/wp-content/plugins/sfwd-lms/includes/lib/wp-pro-quiz/js/jquery.ui.touch-punch.min.js?ver=0.2.2" id="jquery-ui-touch-punch-js"></script>
<script src="https://lilac.local/wp-content/plugins/elementor-pro/assets/js/webpack-pro.runtime.js?ver=3.28.3" id="elementor-pro-webpack-runtime-js"></script>
<script src="https://lilac.local/wp-content/plugins/elementor/assets/js/webpack.runtime.js?ver=3.28.4" id="elementor-webpack-runtime-js"></script>
<script src="https://lilac.local/wp-content/plugins/elementor/assets/js/frontend-modules.js?ver=3.28.4" id="elementor-frontend-modules-js"></script>
<script src="https://lilac.local/wp-includes/js/dist/hooks.js?ver=be67dc331e61e06d52fa" id="wp-hooks-js"></script>
<script src="https://lilac.local/wp-includes/js/dist/i18n.js?ver=5edc734adb78e0d7d00e" id="wp-i18n-js"></script>
<script id="wp-i18n-js-after">
wp.i18n.setLocaleData( { 'text direction\u0004ltr': [ 'rtl' ] } );
</script>
<script id="elementor-pro-frontend-js-before">
var ElementorProFrontendConfig = {"ajaxurl":"https:\/\/lilac.local\/wp-admin\/admin-ajax.php","nonce":"f3620317ca","urls":{"assets":"https:\/\/lilac.local\/wp-content\/plugins\/elementor-pro\/assets\/","rest":"https:\/\/lilac.local\/wp-json\/"},"settings":{"lazy_load_background_images":true},"popup":{"hasPopUps":true},"shareButtonsNetworks":{"facebook":{"title":"Facebook","has_counter":true},"twitter":{"title":"Twitter"},"linkedin":{"title":"LinkedIn","has_counter":true},"pinterest":{"title":"Pinterest","has_counter":true},"reddit":{"title":"Reddit","has_counter":true},"vk":{"title":"VK","has_counter":true},"odnoklassniki":{"title":"OK","has_counter":true},"tumblr":{"title":"Tumblr"},"digg":{"title":"Digg"},"skype":{"title":"Skype"},"stumbleupon":{"title":"StumbleUpon","has_counter":true},"mix":{"title":"Mix"},"telegram":{"title":"Telegram"},"pocket":{"title":"Pocket","has_counter":true},"xing":{"title":"XING","has_counter":true},"whatsapp":{"title":"WhatsApp"},"email":{"title":"Email"},"print":{"title":"Print"},"x-twitter":{"title":"X"},"threads":{"title":"Threads"}},"woocommerce":{"menu_cart":{"cart_page_url":"https:\/\/lilac.local\/cart\/","checkout_page_url":"https:\/\/lilac.local\/checkout\/","fragments_nonce":"e2b8f61733"}},"facebook_sdk":{"lang":"he_IL","app_id":""},"lottie":{"defaultAnimationUrl":"https:\/\/lilac.local\/wp-content\/plugins\/elementor-pro\/modules\/lottie\/assets\/animations\/default.json"}};
</script>
<script src="https://lilac.local/wp-content/plugins/elementor-pro/assets/js/frontend.js?ver=3.28.3" id="elementor-pro-frontend-js"></script>
<script id="elementor-frontend-js-before">
var elementorFrontendConfig = {"environmentMode":{"edit":false,"wpPreview":false,"isScriptDebug":true},"i18n":{"shareOnFacebook":"\u05e9\u05ea\u05e3 \u05d1\u05e4\u05d9\u05d9\u05e1\u05d1\u05d5\u05e7","shareOnTwitter":"\u05e9\u05ea\u05e3 \u05d1\u05d8\u05d5\u05d5\u05d9\u05d8\u05e8","pinIt":"\u05dc\u05e0\u05e2\u05d5\u05e5 \u05d1\u05e4\u05d9\u05e0\u05d8\u05e8\u05e1\u05d8","download":"\u05d4\u05d5\u05e8\u05d3\u05d4","downloadImage":"\u05d4\u05d5\u05e8\u05d3\u05ea \u05ea\u05de\u05d5\u05e0\u05d4","fullscreen":"\u05de\u05e1\u05da \u05de\u05dc\u05d0","zoom":"\u05de\u05d9\u05e7\u05d5\u05d3","share":"\u05e9\u05ea\u05e3","playVideo":"\u05e0\u05d2\u05df \u05d5\u05d9\u05d3\u05d0\u05d5","previous":"\u05e7\u05d5\u05d3\u05dd","next":"\u05d4\u05d1\u05d0","close":"\u05e1\u05d2\u05d5\u05e8","a11yCarouselPrevSlideMessage":"\u05e9\u05e7\u05d5\u05e4\u05d9\u05ea \u05e7\u05d5\u05d3\u05de\u05ea","a11yCarouselNextSlideMessage":"\u05e9\u05e7\u05d5\u05e4\u05d9\u05ea \u05d4\u05d1\u05d0\u05d4","a11yCarouselFirstSlideMessage":"\u05d6\u05d5\u05d4\u05d9 \u05d4\u05e9\u05e7\u05d5\u05e4\u05d9\u05ea \u05d4\u05e8\u05d0\u05e9\u05d5\u05e0\u05d4","a11yCarouselLastSlideMessage":"\u05d6\u05d5\u05d4\u05d9 \u05d4\u05e9\u05e7\u05d5\u05e4\u05d9\u05ea \u05d4\u05d0\u05d7\u05e8\u05d5\u05e0\u05d4","a11yCarouselPaginationBulletMessage":"\u05dc\u05e2\u05d1\u05d5\u05e8 \u05dc\u05e9\u05e7\u05d5\u05e4\u05d9\u05ea"},"is_rtl":true,"breakpoints":{"xs":0,"sm":480,"md":768,"lg":1025,"xl":1440,"xxl":1600},"responsive":{"breakpoints":{"mobile":{"label":"\u05de\u05d5\u05d1\u05d9\u05d9\u05dc \u05d0\u05e0\u05db\u05d9","value":767,"default_value":767,"direction":"max","is_enabled":true},"mobile_extra":{"label":"\u05de\u05d5\u05d1\u05d9\u05d9\u05dc \u05d0\u05d5\u05e4\u05e7\u05d9","value":880,"default_value":880,"direction":"max","is_enabled":false},"tablet":{"label":"\u05d8\u05d0\u05d1\u05dc\u05d8 \u05d0\u05e0\u05db\u05d9","value":1024,"default_value":1024,"direction":"max","is_enabled":true},"tablet_extra":{"label":"\u05d8\u05d0\u05d1\u05dc\u05d8 \u05d0\u05d5\u05e4\u05e7\u05d9","value":1200,"default_value":1200,"direction":"max","is_enabled":false},"laptop":{"label":"\u05dc\u05e4\u05d8\u05d5\u05e4","value":1366,"default_value":1366,"direction":"max","is_enabled":false},"widescreen":{"label":"\u05de\u05e1\u05da \u05e8\u05d7\u05d1","value":2400,"default_value":2400,"direction":"min","is_enabled":false}},"hasCustomBreakpoints":false},"version":"3.28.4","is_static":false,"experimentalFeatures":{"e_font_icon_svg":true,"additional_custom_breakpoints":true,"container":true,"e_local_google_fonts":true,"theme_builder_v2":true,"hello-theme-header-footer":true,"nested-elements":true,"editor_v2":true,"e_element_cache":true,"home_screen":true,"launchpad-checklist":true},"urls":{"assets":"https:\/\/lilac.local\/wp-content\/plugins\/elementor\/assets\/","ajaxurl":"https:\/\/lilac.local\/wp-admin\/admin-ajax.php","uploadUrl":"https:\/\/lilac.local\/wp-content\/uploads"},"nonces":{"floatingButtonsClickTracking":"daccedcc6a"},"swiperClass":"swiper","settings":{"page":[],"editorPreferences":[]},"kit":{"active_breakpoints":["viewport_mobile","viewport_tablet"],"global_image_lightbox":"yes","lightbox_enable_counter":"yes","lightbox_enable_fullscreen":"yes","lightbox_enable_zoom":"yes","lightbox_enable_share":"yes","lightbox_title_src":"title","lightbox_description_src":"description","woocommerce_notices_elements":[],"hello_header_logo_type":"title","hello_header_menu_layout":"horizontal","hello_footer_logo_type":"logo"},"post":{"id":1367,"title":"TEST%20with%20hint%202%20%E2%80%93%20lilac","excerpt":"","featuredImage":false},"user":{"roles":{"1":"subscriber"}}};
</script>
<script src="https://lilac.local/wp-content/plugins/elementor/assets/js/frontend.js?ver=3.28.4" id="elementor-frontend-js"></script><span id="elementor-device-mode" class="elementor-screen-only"></span>
<script src="https://lilac.local/wp-content/plugins/elementor-pro/assets/js/elements-handlers.js?ver=3.28.3" id="pro-elements-handlers-js"></script><svg style="display: none;" class="e-font-icon-svg-symbols"></svg>
 <script type="text/javascript">
		function load_wpProQuizFront9() {
			jQuery('#wpProQuiz_9').wpProQuizFront({
				course_id: 0,
				lesson_id: 0,
				topic_id: 0,
				quiz: 1367,
				quizId: 9,
				mode: 2,
				globalPoints: 0,
				timelimit: 0,
				timelimitcookie: 0,
				resultsGrade: [0],
				bo: 7552,
				passingpercentage: 80,
				user_id: 69,
				qpp: 0,
				catPoints: [0],
				formPos: 0,
				essayUploading: 'מעלה',
				essaySuccess: 'הצלחה',
				lbn: "\u05e1\u05d9\u05d9\u05dd \u05de\u05d1\u05d7\u05df",
				json: {"41":{"type":"single","id":41,"question_post_id":1369,"catId":0},"42":{"type":"single","id":42,"question_post_id":1370,"catId":0},"43":{"type":"single","id":43,"question_post_id":1371,"catId":0},"44":{"type":"single","id":44,"question_post_id":1372,"catId":0},"45":{"type":"single","id":45,"question_post_id":1373,"catId":0},"46":{"type":"single","id":46,"question_post_id":1374,"catId":0},"47":{"type":"single","id":47,"question_post_id":1375,"catId":0},"48":{"type":"single","id":48,"question_post_id":1376,"catId":0}},
				ld_script_debug: 0,
				quiz_nonce: '97c0b88eae',
				scrollSensitivity: '10',
				scrollSpeed: '10',
				quiz_resume_enabled:  '0',
				quiz_resume_id: '0',
				quiz_resume_quiz_started: '0',
				quiz_resume_data: '[]',
				quiz_resume_cookie_expiration: '604800',
				quiz_resume_cookie_send_timer: '5',
			});
		}
		var loaded_wpProQuizFront9 = 0;
		jQuery( function($) {
			load_wpProQuizFront9();
			loaded_wpProQuizFront9 = 1;
		});
		jQuery(window).on('load',function($) {
			if(loaded_wpProQuizFront9 == 0)
			load_wpProQuizFront9();
		});
		</script> 


<div class="widget_shopping_cart_live_region screen-reader-text" role="status"></div></body>


reversible modifications

the situation we want is to 
disable navigation
<div class="wpProQuiz_reviewQuestion">
	<ol style="margin-top: 0px !important">
					<li class="wpProQuiz_reviewQuestionTarget wpProQuiz_reviewQuestionSolvedIncorrect">1</li>
					<li>2</li>
					<li>3</li>
					<li>4</li>
					<li>5</li>
					<li>6</li>
					<li>7</li>
					<li>8</li>
			</ol>
	<div style="display: none; top: 0px;"></div>
</div>
the user will see the question number hes on but pressing on those wont affect the quiz - he must answer the question before moving to the next one.

request- if and when the user submits a wrong question

he will be asked to press on the hint
<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;">

only after this the button for the next question will be enabled
<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left; margin-right: 10px;" data-question-lock="true">

all of this flow is something i would rather add in a shortcode, a folder base extention, or a plugin.

issue - we have a hint system that´s been started to develop, but it only works on native quiz pages, if we want to extend our addons using shortcodes such as 
[ld_quiz quiz_id="1367"]
[LDAdvQuiz 9]
[LDAdvQuiz_toplist 9]

then we will have to go and extend that first


