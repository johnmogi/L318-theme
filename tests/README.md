# Promo Code System Tests

This directory contains the tests for the LearnDash promo code registration system.

## Overview

The test suite covers:

- Basic promo code validation
- Course-specific validation
- Expiry date enforcement
- Multiple-use code handling
- Shortcode functionality

## Running Tests

### Prerequisites

1. Install PHPUnit
2. Set up WordPress test environment (WP-CLI has a command for this: `wp scaffold plugin-tests`)
3. Set the WP_TESTS_DIR environment variable to point to your WordPress test library

### Running the Tests

From the theme root directory:

```bash
phpunit
```

Or to run a specific test:

```bash
phpunit tests/test-promo-codes.php
```

## Adding New Tests

To add new tests:

1. Create a new test file in the `tests` directory with the prefix `test-`
2. Extend the `WP_UnitTestCase` class
3. Add your test methods with the prefix `test_`
4. Run the tests to make sure everything works

## Troubleshooting

Common issues:

- **Can't find WP_UnitTestCase**: Make sure the WP_TESTS_DIR is set correctly
- **Database errors**: The tests need to be able to create temporary tables in the test database
- **Failing assertions**: Check that the WordPress environment is correctly loaded and the promo code system is initialized
