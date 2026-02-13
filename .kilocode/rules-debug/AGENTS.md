# AGENTS.md

This file provides guidance to agents when working with code in this repository.

## Project Debug Rules

- Use Laravel Boost's `tinker` tool for debugging PHP code and querying Eloquent models
- Use `database-query` tool for read-only database queries
- Use `browser-logs` tool to read browser logs, errors, and exceptions (only recent logs are useful)
- Use `last-error` tool to get details of the last error/exception on the backend
- Application is served by Laravel Herd at https?://[kebab-case-project-dir].test
- Use `get-absolute-url` tool to generate valid URLs for testing
