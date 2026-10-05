# AI Agent Manual — XHE robot project

One folder is one robot. The robot is a PHP 8 script driving the XHE browser/DOM API: your code calls
the `WEB::`, `DOM::`, `SYSTEM::`, `WINDOW::` facades, the XHE server executes them in the real browser.

## Layout — keep it, do not invent another one

| Path | What lives there |
|------|------------------|
| `run.php` | Entry: config block, `require` of `init.php` and `tools/robotInit.php`, `TOOLS::$robot->run()`, `TOOLS::$app->quit()`. Never put business logic here. |
| `init.php` | XHE API bootstrap. Never rewrite its `require`, never copy it to another file name. |
| `tools/Robot.php` | Robot body: `configure()` → business flow → report. It only *calls* slices. |
| `tools/robotInit.php` | Auto-loader. Anything you drop into `tools/slices/<name>/`, `tools/core/contracts/` or `tools/core/adapters/` is required automatically — no editing needed. |
| `tools/slices/<slice>/` | One slice = one responsibility (one page you scrape, one report you build). Inside: `*Item.php` (data), `*Scraper.php` (reads the page), `*Sink.php` (writes results), `*Entry`-class with `run()` that wires them. |
| `tools/core/helpers/` | Ready helpers, exposed as `TOOLS::$log`, `TOOLS::$dom`, `TOOLS::$web`, … |
| `tools/core/contracts/` | Interfaces (ports). A slice may depend on these only — never on a concrete writer. |
| `tools/core/adapters/` | Implementations of the ports. `SpreadsheetWriters::forFile($path)` returns csv or xlsx writer by extension. |
| `data/` | What the robot produces (csv/xlsx/json). `res/` — downloaded files. |
| `log/`, `temp/` | Runtime noise. Never write source files there. |
| `settings/settings.json` | Runtime settings, loaded by `SETTINGS::$settings->selfConfigure()`. |
| `.agents/rules/`, `.agents/skills/` | Rules are already inlined into your instructions. Skills are opt-in via `.agents/skills/INDEX.md`. |

## Hard rules

- Never invent an XHE method. Look it up first (`search_api` with the action in RU or EN, then
  `get_method_info` / `get_class_info`) and paste the exact `Facade->method(...)` from the answer.
- Facades are static properties: `WEB::$browser->navigate(...)`. Not `WEB::$browser::navigate`, not `$WEB->…`.
- `WEB::$browser->navigate()` is always followed by `WEB::$browser->wait_js()`.
- `get_by_*` / `get_all_by_*` return an interface (or a collection of them). Check `->is_exist()` (or
  `count()`) before acting. Collection is `Countable` and iterable; `->get($i)` gives one element.
- Check that a file or folder exists before reading, writing or deleting it.
- No `namespace` and no `use X\Y` anywhere: `tools/robotInit.php` requires files by path and PHP
  resolves classes by SHORT name, so a namespaced slice is unreachable — the run dies with
  `Class "VcNewsSlice" not found` on a file that exists. One plain global class per file.
- A slice folder is FLAT: the autoloader scans one directory level, so `tools/slices/<name>/src/X.php`
  is never loaded either — put every class file directly in `tools/slices/<name>/`.
- Never `require_once` your own project files: the autoloader already loads every `.php` under
  `tools/core/*` and `tools/slices/<name>/`, so a hand-written relative path only produces
  `Failed opening required` fatals. Where a require IS needed (the sticky `Templates/init.php` in a
  standalone script), copy the exact on-disk file name and case — on Linux a wrong case is fatal.
- Never run the robot from a shell (`php run.php`, `node run.js`, `python run.py`). That process has no
  XHE server attached: every facade fails there. Run it through the assistant's run tool, read the log
  it returns, fix that line, run again.
- No package managers (`composer`, `npm`, `pip`) and no downloaded libraries. CSV/XLSX/JSON output already
  exists in `tools/core/adapters/`, the browser and the filesystem already exist as facades.
- The scaffold (`init.php`, `tools/robotInit.php`, `tools/core/`, `.agents/`) is the project base: never
  delete or rewrite it. New code goes into `tools/slices/`.
- `run.php` is the only entry point; the robot itself is `tools/Robot.php`. Never add a second root script
  that requires `init.php` — that forks config, logging and the quit path into two copies.
- End every run through the project's quit path (`TOOLS::$app->quit()`).

## Quality bar

- **Reuse before you write**: grep `tools/core/` for the port you need (spreadsheet, mailer, logger).
  A second copy of that logic is the usual way to break DRY in this project.
- **One reason to change per file** (SOLID): a scraper does not format rows, a sink does not parse HTML.
- **New output format** = new adapter implementing an existing interface. Do not add `if ($format === …)`
  inside a slice.
- **KISS**: build only the layer the task needs. A one-page scrape with a csv file is one slice plus
  `SpreadsheetWriters::forFile()` — no database, no queue, no extra config.
- One class per file, file name equals class name.

## Working method

1. If input data is missing (URL, file path, column names), ask one specific question. Do not guess.
2. If the target page is unknown, read its HTML first, then put the locators you actually saw into the
   scraper. Never hard-code a locator you have not seen.
3. Save code with your file tools — one file per class. Do not paste a whole file into the chat after saving it.
4. Run the robot through the assistant's run tool (Hard rules: not a shell `php …`). On failure quote
   `file:line` and the message from the log it returns, fix that line, run again.
   The log is localized: the failure line is `[ERROR]` in English but `[ОШИБКА]` in Russian — grep for the
   marker the robot's own language writes, otherwise a crashed log looks clean.
5. The work is not finished while the promised file in `data/` does not exist: run, read the log, fix,
   run again — then read the result file back and confirm its row count.
6. Report at the end: what ran, which file holds the result, how many items, and what you could not verify.

## API cheat

`WEB::$browser` navigation and downloads · `WEB::$webpage` page content (`get_title`, `get_source`) ·
`DOM::$anchor|input|button|div|image` elements · `SYSTEM::$file_os|textfile|folder|excelfile` files ·
`WINDOW::$app` lifecycle.

Canonical flow: `navigate` → `wait_js` → `DOM::$*->get_all_by_*` → check → read text/href → write to
`data/` → `quit()`.

Typical harvest:

```php
$links = DOM::$anchor->get_all_by_class('news-title', false);
foreach ($links as $link) {
    $url = $link->get_href();
    $title = $link->get_inner_text();
}
```

Rules: [`.agents/rules/`](.agents/rules/) · Skills: [`.agents/skills/INDEX.md`](.agents/skills/INDEX.md)
