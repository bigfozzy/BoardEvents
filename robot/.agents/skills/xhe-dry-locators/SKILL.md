---
name: xhe-dry-locators
version: 1.0.0-stable
last_updated: 2026-09-19T00:00:00Z
author: system
compatibility: ">=2.0.0"
dependencies:
  - xhe-slice-solid
changelog:
  - "1.0.0-stable: First release"
description: One source of truth for locators, URLs and column lists — reuse helpers instead of writing the same lookup again
---

# One source of truth (DRY)

## Purpose

Keep each fact — a CSS class, a URL, a column list, a wait timeout — in exactly one place, so a page
redesign costs one edit instead of a search through the project.

## When to Use

- You are about to write a `get_by_*` / `get_all_by_*` call you already wrote elsewhere.
- The same header list or file name appears in two files.
- You are about to copy a block of code "with small changes".

## Rules

- Locators live in the scraper that owns the page, as named constants:
  `private const ITEM_HREF_PART = '/?q=node/';` — not inline in five methods.
- Column lists live in the sink (`HEADERS`) and are exposed once (`headers()`), so the writer factory
  and the writer use the same array.
- Paths and tunables that the operator may change belong in the config block of `run.php`
  (`$exampleNewsUrl`, `$exampleNewsLimit`), not buried in a class.
- Search `tools/core/helpers/` and `TOOLS::$*` before writing a helper: waits, DOM clicks, csv/xlsx and
  mail already exist there.
- Two slices need the same thing → move it to `tools/core/` and delete both copies. Duplicated parsing
  of the same page is how robots start lying differently on different days.
- Copy-paste with a changed constant is still duplication: parameterise instead.

## Anti-patterns

- `str_replace`-style re-implementations of what `TOOLS::$dom` already does.
- A second `data/out.csv` written by hand in the sink because "the writer adds a header".
- Magic strings repeated per page: `'?page='`, `'node/'`, `'aft-post'` — name them once.
