<img src="https://humanemulator.info/images/logo.png" alt="[logo]" width="48"/> Board events for Human Emulator Studio 7.x

Two things live in this repository:

| Folder | What it is | Status |
|---|---|---|
| [`robot/`](robot/) | **The product.** A PHP robot for Human Emulator Studio 7.x that watches classified boards and reports new ads. | current |
| [`legacy-csharp/`](legacy-csharp/) | The original C# / .NET Framework desktop app. Built on `XHE.dll` 4.x, the IE-based line. | archived |

# robot/ — the product

Watches [auto.ria.com] (olx and rst parsers are stubbed out), opens only the
ads it has not seen, normalizes the phone number, and writes the result to
`data/autoria.csv`.

Built on the PHP API of Human Emulator Studio 7.x — `WEB::$browser`,
`DOM::$anchor`, `TOOLS::$log` and the rest. [API docs].

## Requirements

- Human Emulator Studio 7.x (current build: Chromium / CEF)
- Nothing else. No composer, no npm, no pip.

## Run it

Open the robot folder as a project in Studio, edit the board URL in `run.php`,
and start `run.php`. From a shell, `php run.php` does **not** work — the XHE
calls are HTTP requests to a running Studio, and without one every facade
fails. See [Hard rules] in `robot/AGENTS.md`.

Edit the search before running: `run.php` holds `$autoriaListUrl`, which is
where your filters live. Open the board by hand, filter it, copy the URL.

## Layout

```
robot/
  run.php                    entry point: config block + TOOLS::$robot->run()
  tools/Robot.php            configure() -> calls the slice -> report
  tools/slices/autoria_listings/
    AutoriaListingsSlice.php     wires it together: collect, filter, write
    AutoriaListingsScraper.php   reads the pages, knows the locators
    AutoriaListingsSink.php      writes the table, knows only the port
    AutoriaListingsItem.php      one ad: url, title, price, city, phone, date
    AutoriaListingsState.php     which ads were already reported
    BoardRules.php               pure rules: phone, dates, escaping
  tools/core/                 vendor helpers, mailer, spreadsheet adapters
  data/                       result files + state
  log/                        run logs
```

The split follows the vendor's rules: a scraper does not format rows, a sink
does not parse HTML, and the slice only wires them. `BoardRules.php` and the
date parsing hold no browser calls at all, which is why they are testable.

## Tests

No CI, all local:

```
php robot/tests/tests.php
```

72 checks over the rules that decide what gets reported: phone normalization,
`8` → `7` conversion, date comparison, ad de-duplication, HTML/CSV escaping,
column order. No Studio and no browser needed.

These checks are not decoration. They are the rules ported from the C# version,
where they had already caught two real bugs — `GetTypeByUrl(null)` throwing on
`IndexOf`, and Russian numbers with an `8` prefix being treated as foreign.

## What is not finished

- Only auto.ria parses. `BoardRules` knows olx and rst, but no scraper exists
  yet — one slice per board.
- OLX forbids scraping in its terms. Do not build a paid product on it.
- Scheduling is not wired up. One run = one pass over the boards. The Studio
  scheduler (`WINDOW\scheduler`) or Windows Task Scheduler is the next step.
- Telegram and mail notification is wired only through the vendor mailer
  (`TOOLS::$mailer`). Telegram still needs its own slice.

# legacy-csharp/ — the archived desktop app

The original program: three boards, e-mail or callback notification, one XHE
instance per port, [Quartz] scheduling, embedded CEF browser. C# on
`.NET Framework 4.6.2`, built against `XHE.dll` 4.10.9.

Kept for reference and diffing. It does not run on Studio 7.x: that line of
`XHE.dll` is IE-based and no longer developed or sold.

### Build

`legacy-csharp.sln` references the XHE project through an absolute path that
exists only on the original author's machine, so **the solution does not open
for anyone else**. There is no `XHE.dll` in the repository and it is not
published. The test runner compiles the sources directly with Roslyn `csc`
instead, which is the only way to build here — see below.

### Tests

```
pwsh -File legacy-csharp/tests/run-tests.ps1
```

69 checks over the scheduling intervals, board-type detection, phone
normalization and export escaping. Also local, also no CI.

### Known limitations

- `XHE.dll` is IE-based and owned by the library vendor.
- The sending mailbox password sits unencrypted in .NET user settings.
- MSBuild cannot build this project: the `XHE.dll` and `libcurl.NET.dll`
  references use a hardcoded `HintPath` that exists on one machine only.

[auto.ria.com]: https://auto.ria.com
[API docs]: https://humanemulator.net
[Hard rules]: robot/AGENTS.md
[Quartz]: https://www.quartz-scheduler.net/
[Library sources on github]: https://github.com/bigfozzy/Templates-CSHARP
[Visual Studio 2019]: https://www.visualstudio.com/downloads/
[.NET Framework 4.6.2 Developer Pack]: https://dotnet.microsoft.com/download/dotnet-framework/net462