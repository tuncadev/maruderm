# Agent Rules

## Core Principles

- Always inspect project structure before making changes
- Never assume file contents — read first
- Do not modify multiple files at once
- Prefer small, incremental changes

---

## Code Style

- OOP only
- No procedural logic outside bootstrap
- Use dependency injection (no globals)
- One responsibility per class

---

## File Editing Rules

- ALWAYS read file before editing
- If file is unclear → ask for clarification
- Do not overwrite large files blindly
- Do not introduce breaking changes without reason

---

## WordPress Rules

- No logic in templates
- No direct DB queries if WP API exists
- Use wp_remote_* for external calls
- Use wp_set_auth_cookie for login

---

## Plugin Rules

- Plugin must be isolated (no theme coupling)
- No hardcoded credentials
- Use .env for secrets
- Use Config class for access

---

## OAuth Rules

- Use ProviderInterface
- Normalize user data
- Never leak tokens
- Validate all inputs

---

## Workflow

1. Read context
2. Plan minimal change
3. Apply change
4. Verify result

---

## Project Skills

- For any custom catalog, `/catalog/` route, product-category archive, catalog filter, product-card visibility, badge, or filter-panel task, use `.agents/skills/maintain-maruderm-catalog/SKILL.md` before making changes. Its local contract and validation script are authoritative; do not search the internet for the established Maruderm catalog workflow.

---

## Forbidden

- chmod 777
- hardcoded secrets
- editing unrelated files
- mixing responsibilities

---

## Post-task commands (if applicable)

npm run build
npm run phpcs:fix
npm run phpcs:check
npm run build
