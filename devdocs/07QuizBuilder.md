# 07: Quiz Builder Tool – CSV Import for Question Sets

## 1. Problem

Creating quizzes manually in the WordPress admin is slow and error-prone, especially when managing large question sets organized by topic. We need a tool to streamline quiz creation by allowing admins to import question sets via CSV — categorized and pre-tagged — to automate and speed up quiz generation.

This feature should be built incrementally, starting from simple CSV parsing for text-only quizzes and later expanding to support media (images, videos) and ACF fields.

---

## 2. Acceptance Criteria (Phase 1)

* [ ] A new **admin menu page** is added under the “LearnDash” or “Tools” section
* [ ] Admin page is labeled: **"Quiz Builder"**
* [ ] Page contains an upload interface for importing a **CSV file**
* [ ] A sample CSV template is provided for download
* [ ] The CSV tool:

  * [ ] Reads question text, 4 answer options, and correct answer
  * [ ] Creates a new LearnDash question post per entry
  * [ ] Groups questions into a question set/category based on CSV column
  * [ ] Assigns imported questions to a new or existing quiz
* [ ] Import supports these columns:

  * `topic`, `question`, `answer_1`, `answer_2`, `answer_3`, `answer_4`, `correct_answer`
* [ ] The system outputs a log of actions: how many questions added, errors, etc.

---

## 3. Future Enhancements (Phase 2+)

* [ ] CSV support for media fields:

  * ACF: `question_sidebar_image`, `question_sidebar_video`
  * Image paths or video URLs parsed and attached
* [ ] Media validation (existence, supported formats)
* [ ] Multi-question quiz auto-generator by topic
* [ ] UI for previewing parsed data before submission
* [ ] Integration with sidebar-enabled template flow

---

## 4. Suggested Technical Approach

* Use OOP PHP class under `includes/admin/QuizBuilder.php`
* Hook into `admin_menu` to register the builder page
* Use `wp_handle_upload()` for safe CSV handling
* Parse CSV with `fgetcsv()` or external lib if needed
* Use `wp_insert_post()` to create questions and `update_field()` for ACF fields
* Code must be modular and extendable, with basic exception handling and inline docs

---

## 5. References

* ACF field keys: `question_sidebar_image`, `question_sidebar_video`
* Related logic: `02fix.md`, `2quizsidebar.md`
* LearnDash docs: [https://developers.learndash.com/](https://developers.learndash.com/)

> Dev must start with core CSV logic and question insertion first. Once stable, media support and UI polish will follow.

---

File format: `07task.md` – implement fix in `07fix.md` after approval.
