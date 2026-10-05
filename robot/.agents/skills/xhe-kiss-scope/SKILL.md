---
name: xhe-kiss-scope
version: 1.0.0-stable
last_updated: 2026-09-19T00:00:00Z
author: system
compatibility: ">=2.0.0"
dependencies:
  - xhe-project-layout
changelog:
  - "1.0.0-stable: First release"
description: Build only the layers the task asks for — no database, no config framework, no abstraction nobody requested
---

# Scope discipline (KISS)

## Purpose

The most common failure of a generated robot is not a wrong method call, it is extra machinery: a
layer the task never asked for, which then has to be debugged.

## When to Use

- Before creating any folder, class or setting that the task did not mention.
- When a task looks like "open a page, take N headlines, give me a file".

## Rules

- Count the responsibilities in the request. "Сходи на сайт и собери 5 новостей в Excel" = one scraper,
  one sink, one entry. Three files in one slice — that is the whole project.
- No storage engine, no queue, no DI container, no "settings framework", no base class with one
  descendant, unless the request needs them.
- New abstraction appears only at the second real use. One writer today is a class, not an interface
  hierarchy — except the ports the template already ships (`SpreadsheetWriter`), which you reuse instead
  of inventing your own.
- Missing input data (URL, output path, columns) → one concrete question to the user. Guessing costs a
  rewrite; asking costs one message.
- Prefer the boring platform call that exists over the clever one you would write.
- Delete what you did not need before you finish.

## Anti-patterns

- `tools/core/RepositoryInterface.php`, `MySQLRepository.php` for a job that writes one csv.
- A `Config` class wrapping the three variables already sitting in `run.php`.
- "Universal" scrapers parameterised for pages nobody will ever scrape (the task named one site).
- Ten files for a five-line job: a slice per verb is over-splitting, one slice per responsibility is not.
