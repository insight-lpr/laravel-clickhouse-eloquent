# AGENTS Instructions

## Purpose
- This repo builds a native Eloquent package so Laravel apps can talk to ClickHouse via the familiar ORM. Keep the focus on API clarity, compatibility with Laravel, and safe data handling.
- Document any architectural or UX decisions (design docs, usage notes) in `docs/` with clear dates and owner initials.

## Workflows
- Before designing or implementing new functionality, run the `brainstorming` skill: define intent, surface alternatives, and validate via small sections as described in `/Users/jeffreycobb/.agents/skills/brainstorming/SKILL.md`.
- Use `systematic-debugging` for reproducing or isolating bugs before proposing fixes.
- Prefer short, focused plans via `update_plan` when work spans multiple files or logical steps.
- Run `composer lint`/`composer test` only if the change touches linked behavior; otherwise explain why not.

## Repo norms
- Keep PHP style consistent with the existing Laravel conventions (PSR-12 style, docblocks, short helper functions).
- Prefer adding tests in `tests/Feature` or `tests/Unit` when behavior changes.
- Don’t duplicate logic elsewhere; extract shared helpers when needed.
