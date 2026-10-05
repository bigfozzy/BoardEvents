---
name: xhe-project-layout
version: 1.0.0-stable
last_updated: 2026-09-19T00:00:00Z
author: system
compatibility: ">=2.0.0"
dependencies: []
changelog:
  - "1.0.0-stable: First release, follows the slice skeleton of the PHP template"
description: Where the code of a robot project goes — entry, slices, core ports and adapters, output folders
---

# Project layout

## Purpose

Put every new file where the project already expects it, so the next reader (or the next robot)
finds it without asking.

## When to Use

- Before writing a robot that needs more than one file.
- When the project root has no `tools/` / `data/` yet.
- When you are about to create a file outside `tools/` and you do not know why.

## Layout

| Path | Belongs there | Never there |
|------|---------------|-------------|
| `run.php` | config block, `require` of init + `tools/robotInit.php`, `TOOLS::$robot->run()`, `TOOLS::$app->quit()` | business logic |
| `tools/Robot.php` | `configure()` and the sequence of slice calls | HTML parsing, file writing |
| `tools/slices/<name>/` | one responsibility of the task | code reused by two slices (move it to `tools/core/`) |
| `tools/core/helpers/` | platform helpers already exposed as `TOOLS::$*` | per-task logic |
| `tools/core/contracts/` | interfaces a slice depends on | implementations |
| `tools/core/adapters/` | implementations of those interfaces | task rules |
| `data/` | results the robot produces | sources, code |
| `res/` | files downloaded by the browser | code |
| `log/`, `temp/` | runtime output only | anything you write by hand |

## Rules

- A missing skeleton: lay it down with the project-scaffold tool of the current mode (it adds only what
  is absent), then write the files. Do not create a nested project folder inside the current root.
- One class per file, file name equals class name, class name prefixed with the slice name
  (`ExampleNewsScraper`).
- `tools/slices/<name>/` and `tools/core/{contracts,adapters}/` are auto-loaded: adding a file needs no
  edit of the loader. If you find yourself editing `tools/robotInit.php` to add a class, the folder is wrong.
- `require_once` paths must match the file name on disk exactly, including case (Linux treats a wrong
  case as a fatal error).

## Anti-patterns

- All logic in `run.php`, or a second `run2.php`.
- `tools/utils.php` / `tools/common.php` growing into a dump.
- Writing results to the project root or into `log/`.
