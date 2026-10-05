---
name: xhe-run-and-verify
version: 1.0.0-stable
last_updated: 2026-09-19T00:00:00Z
author: system
compatibility: ">=2.0.0"
dependencies:
  - xhe-project-layout
changelog:
  - "1.0.0-stable: First release"
description: Run the robot, read what actually happened, fix the exact failing line, and report only what was verified
---

# Run and verify

## Purpose

A robot is done when it ran and produced the expected file — not when the code was written.

## When to Use

- After the last file of a slice is saved.
- After any failed run.
- Before saying "готово".

## Steps

1. Save every file first; if the editor reports a syntax problem for a file, fix that file before running.
2. Run the project's entry (`run.php`) with the run facility of the current mode. If the mode has no run
   tool, say so and ask the user to run it — do not describe a result you did not observe.
3. Read the output and `log/log_<date>.log`. The last line that is not a `debug` line is usually the truth.
4. On failure: quote the message plus `File:`/`Line:` from it, open that line, fix it, run again. One fix
   per iteration — two edits at once hide which one worked.
5. Check the artifact, not the log: open the file in `data/`, confirm the header, the row count and that
   the first row looks like the page.

## Reporting

Say what ran, which file holds the result, how many items, and explicitly what you could not verify
(the page needed a login, the captcha blocked you, the sink was not exercised because the list was empty).
Never substitute sample values, never "assume" a green run.

## Blocked pages are a result, not a bug to hide

- Cookie/consent wall, captcha, 403/429, region block: stop, report what blocked you and what you need
  from the user. Do not invent items and do not quietly switch to another site with "similar" data.
- Retry at most twice with a different locator; a third identical failure means the assumption about the
  page is wrong — go back to reading the HTML.

## Anti-patterns

- Declaring success after `write_file` without a run.
- Catching an exception and logging "done".
- Re-running five times hoping for a different result.
