# Contributing to Simpel POS

Thank you for your interest in contributing to Simpel POS! This document provides guidelines and information for contributing.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Development Setup](#development-setup)
- [How to Contribute](#how-to-contribute)
  - [Reporting Bugs](#reporting-bugs)
  - [Suggesting Features](#suggesting-features)
  - [Code Contributions](#code-contributions)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)
  - [PHP](#php)
  - [JavaScript](#javascript)
  - [Blade Templates](#blade-templates)
  - [Filament Resources](#filament-resources)
- [Commit Guidelines](#commit-guidelines)
  - [Types](#types)
  - [Examples](#examples)
- [Testing](#testing)
  - [Running Tests](#running-tests)
  - [Writing Tests](#writing-tests)
- [Questions?](#questions)

## Code of Conduct

This project follows the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md). By participating in this project, you are expected to uphold this code. Please report unacceptable behavior to [ryan.sutrisno@gmail.com].

## Getting Started

### Prerequisites

- PHP >= 8.2
- Composer
- Node.js >= 18
- NPM or Yarn
- MySQL or SQLite
- Laravel Herd (optional, for macOS)

### Development Setup

1. **Fork and Clone Repository**

```bash
git clone https://github.com/YOUR_USERNAME/simpel-pos-laravel.git
cd simpel-pos-laravel
```

2. **Install Dependencies**

```bash
composer install
npm install
```

3. **Environment Setup**

```bash
cp .env.example .env
php artisan key:generate
```

4. **Database Setup**

```bash
php artisan migrate:fresh --seed
```

5. **Build Assets**

```bash
npm run build
```

6. **Run Development Server**

```bash
# Using Herd (automatically running)
# or
php artisan serve
```

## How to Contribute

### Reporting Bugs

If you find a bug, please create an [Issue](https://github.com/ryansutrisno/simpel-pos-laravel/issues) with the following information:

- A clear and descriptive title
- Steps to reproduce the bug
- Expected vs actual behavior
- Screenshots (if relevant)
- Environment details (OS, PHP version, etc.)

### Suggesting Features

To propose a new feature:

1. Open an [Issue](https://github.com/ryansutrisno/simpel-pos-laravel/issues) with the `enhancement` label
2. Describe the proposed feature in detail
3. Explain why this feature would be useful to users

### Code Contributions

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Pull Request Process

1. **Ensure tests pass**

```bash
php artisan test
```

2. **Follow coding standards**

```bash
vendor/bin/pint --dirty
```

3. **Update documentation** if necessary

4. **PR Description Template**

```markdown
## Description
Describe the changes you have made

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## How Has This Been Tested?
Describe the testing that has been done

## Checklist:
- [ ] Code follows style guidelines
- [ ] Self-review has been performed
- [ ] Comments added for complex code
- [ ] Documentation has been updated
- [ ] Tests have been added/passed
```

## Coding Standards

### PHP

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style
- Use Laravel Pint for formatting:

```bash
vendor/bin/pint
```

### JavaScript

- Follow ESLint standards
- Use arrow functions for callbacks
- Use const/let, avoid var

### Blade Templates

- Use 4-space indentation
- Follow [Laravel Blade](https://laravel.com/docs/blade) best practices

### Filament Resources

- Use form sections for grouping
- Follow patterns in existing resources
- Add labels in Indonesian for local context

## Commit Guidelines

We use [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Formatting changes (does not affect code)
- `refactor`: Code refactoring
- `test`: Adding/fixing tests
- `chore`: Build, dependencies, etc. changes

### Examples

```
feat(pos): add discount calculation to cart
fix(transaction): resolve stock deduction issue
docs(readme): update installation instructions
test(product): add product factory tests
```

## Testing

### Running Tests

```bash
# All tests
php artisan test

# Specific test
php artisan test --filter=ProductTest

# Tests with coverage
php artisan test --coverage
```

### Writing Tests

- Use Pest PHP
- Write tests for:
  - Happy path (positive cases)
  - Edge cases
  - Error handling

Example test:

```php
it('can create a product', function () {
    $category = Category::factory()->create();

    actingAs(User::factory()->create());

    post('/admin/products', [
        'name' => 'Test Product',
        'category_id' => $category->id,
        'purchase_price' => 10000,
        'selling_price' => 15000,
        'stock' => 50,
    ])->assertRedirect();
});
```

## Questions?

If you have questions, you can:

1. Open a [Discussion](https://github.com/ryansutrisno/simpel-pos-laravel/discussions)
2. Send an email to [ryan.sutrisno@gmail.com]

---

Thank you for contributing! 🎉
