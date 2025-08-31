# LearnDash Customizations - Documentation

This folder contains all custom development, enhancements, and integrations related to LearnDash for this project.

## Folder Purpose
- Store all code, scripts, and documentation related to LearnDash quiz, course, and user management customizations.
- Serve as the main point of reference for future development and maintenance of LearnDash-related features.

## Current Features
- **CSV Quiz Builder**: Import/export quizzes, support for quiz sets, categories, and bulk management via CSV.
  - Supports UTF-8 encoding with automatic conversion for Hebrew characters
  - Handles multi-quiz imports with proper categorization
  - Exports full quiz structure including sets, descriptions, and categories
- **Custom User Roles**: Teacher and super admin roles with tailored permissions.
- **Hints and Explanations**: Enhanced question hints and teacher-facing explanations.
- **Shortcodes and UI Extensions**: Custom shortcodes for login, registration, and dashboard features.

## Development Guidelines
- Place all LearnDash-related PHP, JS, and documentation files in this folder or its subfolders.
- Use clear file and folder names for new features (e.g., `quiz-builder`, `user-management`).
- Document all new features and APIs in this README or in dedicated markdown files.

## Future Development
- Expand quiz analytics and reporting features.
- Integrate more granular user progress tracking.
- Enhance teacher dashboards and bulk management tools.
- Maintain compatibility with future LearnDash and WordPress updates.

---

**For questions or contributions, please document your changes and update this README accordingly.**
