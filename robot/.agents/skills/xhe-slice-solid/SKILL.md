---
name: xhe-slice-solid
version: 1.0.0-stable
last_updated: 2026-09-19T00:00:00Z
author: system
compatibility: ">=2.0.0"
dependencies:
  - xhe-project-layout
changelog:
  - "1.0.0-stable: First release, ports-and-adapters shape of a slice"
description: How one slice is built inside — data, reader, writer, entry — and how it depends on ports instead of concrete formats
---

# Slice structure (SOLID)

## Purpose

Split a task into slices that each change for one reason, and keep the slice ignorant of how the
result is stored.

## When to Use

- The task has more than one responsibility (open a page, parse it, store rows, notify).
- The output format may change (csv today, xlsx tomorrow).
- Two pages must be scraped with the same result shape.

## Shape of one slice

```
tools/slices/news_to_table/
  NewsItem.php          // data only: url, title, text + toRow()
  NewsScraper.php       // opens the page, returns NewsItem[]  — knows the DOM, not the file
  NewsSink.php          // accepts a SpreadsheetWriter, writes rows — knows the file, not the DOM
  NewsSlice.php         // run(): wires the three, logs the counts
```

- `run()` of the entry class is the only thing `tools/Robot.php` calls.
- The sink takes the port (`SpreadsheetWriter`), never `CsvHelper`/`ExcelHelper` directly; the entry
  obtains the writer from `SpreadsheetWriters::forFile($path, $headers)`.
- Return values over side effects: a scraper returns items, it does not write files or send mail.

## Rules

- **Single reason to change**: a scraper that also formats dates for Excel is two classes.
- **Open/closed**: a new output format is a new adapter implementing the existing interface, plus one
  line in the factory. Never `if ($format === 'xlsx')` inside a slice.
- **Depend on the abstraction**: type-hint `SpreadsheetWriter`, `Mailer`, `LogHelper` — not a concrete writer.
- Before writing a new port, grep `tools/core/contracts/` — the port usually exists already.
- Keep the entry thin: wiring, logging, and the count of what was produced. No DOM calls there.

## Anti-patterns

- A "god" `Robot.php` that navigates, parses, writes and mails.
- Passing an element object into a `*_by_name` shortcut, or a file path into a scraper.
- Copy-pasting a scraper for the second page instead of parameterising the existing one.
