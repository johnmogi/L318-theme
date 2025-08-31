

we want to improve the basic login and regcustom form



what we currently have
<div class="e-con-inner">
				<div class="elementor-element elementor-element-4a3695a elementor-widget elementor-widget-shortcode" data-id="4a3695a" data-element_type="widget" data-widget_type="shortcode.default">
				<div class="elementor-widget-container">
							<div class="elementor-shortcode"><form method="post"><input type="hidden" name="registration_code" value="LCH7A3BDA"><input type="hidden" name="registration_group" value="y natanya"><p><label>שם פרטי:</label><br><input type="text" name="first_name" required="" value=""></p><p><label>שם משפחה:</label><br><input type="text" name="last_name" required="" value=""></p><p><label>טלפון נייד:</label><br><input type="text" name="phone" required="" value=""></p><p><label>אימות טלפון:</label><br><input type="text" name="phone_confirm" required="" value=""></p><p><label>כתובת אימייל (לא חובה, לשחזור סיסמה):</label><br><input type="email" name="email" value=""></p><p><label>סיסמה:</label><br><input type="password" name="password" required=""></p><p><label>אימות סיסמה:</label><br><input type="password" name="password_confirm" required=""></p><input type="hidden" name="program" value="default"><p><button type="submit">רשום</button></p></form></div>
						</div>
				</div>
					</div>

what we need to do

לתלמידי כיתה י' - תיאוריה בבתיה"ס – משרד החינוך

אופציה לקלוט רשימת תלמידים מאקסל לפתיחת מנוי באופן אוטומטי לפי הקטגוריות הבאות: שם משפחה – שם פרטי – שם בי"ס –עיר בי"ס – קוד בי"ס- טלפון נייד- סיסמה. 

לרכישת ספר חינוך תעבורתי + ספרון תמרורים + תרגול: 
פרק לדוגמה מהספר + תרגול חינמי מתחתיו.
למשתמש יש אפשרות לראות פרק דוגמה מהספר ולאחריו מבחן נושא חינמי באותו נושא.   (מחיר התחלתי?

כפתור – הוסף לסל
בחירה בין איסוף עצמי למשלוח – מי שלוחץ משלוח מתווסף לו עוד סכום מחיר משלוח התחלתי? (שאפשר לשנות אותו כשרוצים). משלוח תוך 3 ימי עסקים, לא כולל יום ההזמנה.
אישור.
פרטים למשלוח:
שם פרטי
שם משפחה
טלפון נייד 
שם בי"ס
עיר בי"ס
כתובת:
רחוב _____________ מספר _____________ דירה _______________ עיר ______________ טלפון לשליח לתיאום _______________
מגיעים לתשלום באשראי פרטי אשראי 
אישור
לאחר אישור – מקבלים הודעה: 

תודה שבחרתם בלילך תיאוריה.
ערכת חינוך תעבורתי בדרך אליכם.
בתוך הערכה מופיע קוד הטבה לפתיחת המנוי השנתי לתרגול. 
אין להירשם לאתר לפני קבלת הערכה.
הנחיות הרשמה מופיעות בספר הלימוד בעמוד הקדמי. 

(לאחר קבלת הספרים וקוד המנוי - רוכשי הספר ייכנסו להרשמה לתרגול וירשמו שם. הרישום לרכישת ספר הוא בנפרד לרישום לתרגול).  

לשלוח חשבונית בהודעה. 


old site signup form and page structure
<div class="registration" bis_skin_checked="1">
			<div class="filedText h4" bis_skin_checked="1"><span class="bold">למה ללמוד תאוריה אצלנו?</span><br>נהנים מחווית למידה – כיף ללמוד! 90% הצלחה במבחן הראשון, בשיטת משחק התאוריה קל ללמוד ולזכור, לומדים הכל בפחות זמן.<br>בהצלחה, צוות לילך תיאוריה.<br><br><span class="bold">מנוי בעלות זולה במיוחד:</span><br>37 שקלים לשבועיים.<br>49 שקלים לחודש.<br>מי שרוכש את הערכה מקבל מנוי חודשי חינם.<br><!--<br><a href="qShopperLookup.taf?_function=facebook&_target=&_ID=13663&did=1095">להתחברות דרך הפייסבוק</a><br>מומלץ להתחבר דרך הפייסבוק ולחסוך לעצמך את הרישום של פרטי ההרשמה.<br>--><br><span class="red bold">לתשומת לב הנרשמים,<br>האתר מאובטח בתקן PCI שהוא התקן הגבוה ביותר.</span><br></div>
		</div>


        lets do this then we can add it into a custom page then we can do the client form which has very similar structure and logic

        ! important and i see it from here even before the mod- those clients are distinguished in their courses and groups and the reg code is not used for anything else 

        $ wp role list
+---------------+--------------------+
| name          | role               |
+---------------+--------------------+
| Administrator | administrator      |
| Editor        | editor             |
| Author        | author             |
| Contributor   | contributor        |
| Subscriber    | subscriber         |
| Customer      | customer           |
| Shop manager  | shop_manager       |
| SEO Manager   | wpseo_manager      |
| SEO Editor    | wpseo_editor       |
| Instructor    | stm_lms_instructor |
| Group Leader  | group_leader       |
+---------------+--------------------+