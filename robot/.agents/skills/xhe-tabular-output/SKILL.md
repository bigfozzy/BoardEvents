---
name: xhe-tabular-output
version: 1.0.0-stable
last_updated: 2026-09-19T00:00:00Z
author: system
compatibility: ">=2.0.0"
dependencies:
  - xhe-slice-solid
changelog:
  - "1.0.0-stable: First release"
description: Writing the robot result — csv or xlsx through SpreadsheetWriters, columns, encodings, dedupe and where the file goes
---

# Tabular output

## Purpose

Produce a result file the operator can open: stable columns, one row per item, in `data/`.

## When to Use

- The task says "в Excel", "в таблицу", "csv", "отчёт", "выгрузка".
- The robot must return a count of items and the path of the file.

## Rules

- Always go through the port, never open the file yourself:

  ```php
  $writer = SpreadsheetWriters::forFile($dataFolderPath . "news.xlsx", NewsSink::headers());
  $sink   = new NewsSink($writer);
  $path   = $sink->write($items);   // save() inside, returns the path written
  ```

  The extension picks the adapter: `.xlsx`/`.xls` → ExcelHelper (`SYSTEM::$excelfile`, native xlsx,
  Excel is not required), anything else → CsvHelper (`;` delimiter, header written on first write).
- Columns: name them once in the sink, keep the order of `toRow()` the same, and do not add a column
  the task did not ask for.
- One row per logical item. Deduplicate by the natural key (url) before writing, not by eye afterwards.
- Empty result is a valid result: still write the header and log "0 items", so the operator sees the
  difference between "nothing found" and "robot did not run".
- Text from the page arrives as-is; trim it, collapse whitespace, and never put a raw HTML fragment into
  a cell — take `get_inner_text()`, not `get_inner_html()`.
- Big outputs: let the adapter batch (ExcelSpreadsheetWriter flushes every 500 rows); do not call the
  file API per row.

## Anti-patterns

- `if (strtolower($ext) == 'csv') … else …` inside a slice — that decision lives in the factory.
- Writing the result into the project root, into `res/`, or into `log/`.
- A file name with a timestamp inside a slice when the operator asked for one predictable path.
