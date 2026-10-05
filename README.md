<img src="https://humanemulator.info/images/logo.png" alt="[logo]" width="48"/> Board events for Human Emulator Studio 7.x

Two things live in this repository:

| Folder | What it is | Status |
|---|---|---|
| [`robot/`](robot/) | **The product.** A PHP robot for Human Emulator Studio 7.x that watches classified boards and reports new ads. | current |
| [`legacy-csharp/`](legacy-csharp/) | The original C# / .NET Framework desktop app. Built on `XHE.dll` 4.x, the IE-based line. | archived |

# robot/ — the product

Watches [auto.ria.com], opens only the ads it has not seen, and writes one
result file per task into `data/`. By default it collects ad data only —
see [Board conditions](#board-conditions--read-this-before-selling) for why
phone numbers are not collected.

## Requirements

- Human Emulator Studio 7.x (current build: Chromium / CEF)
- Nothing else. No composer, no npm, no pip.

## Run it

Open the robot folder as a project in Studio, edit the tasks in `run.php`,
and start `run.php`. From a shell, `php run.php` does **not** work — the XHE
calls are HTTP requests to a running Studio, and without one every facade
fails. See [Hard rules] in `robot/AGENTS.md`.

A task is a URL you copied off the board: open it by hand, filter it (make,
model, city, price), copy the address. The filters stay in the URL, so the
robot repeats your choice instead of guessing. Add as many tasks as you like
— each gets its own result file:

```php
$autoriaBoards = [
    ['name' => "auto.ria — BMW", 'url' => "https://auto.ria.com/car/bmw",
     'file' => __DIR__ . "/data/autoria_bmw.xlsx"],
    ['name' => "Киев, до 5000 $", 'url' => "https://auto.ria.com/car/used/?"],
];
```

A task that fails does not cancel the others — the error lands in the log and
the run continues. Otherwise one typo in one URL would silently stop
collection for every other filter.

## Layout

```
robot/
  run.php                    entry point: config block + TOOLS::$robot->run()
  tools/Robot.php            configure() -> schedule -> walks the tasks
  tools/slices/autoria_listings/
    AutoriaListingsSlice.php     wires it together: collect, pick, write
    AutoriaListingsScraper.php   reads the pages, knows the locators
    AutoriaListingsSelection.php what goes in the report - no browser
    AutoriaListingsSink.php      writes the table, knows only the port
    AutoriaListingItem.php       one ad: price, mileage, VIN, seller
    AutoriaListingsVehicleData.php  JSON-LD -> fields, no browser involved
    AutoriaListingsPhoneReveal.php  click the mask, read the number (off)
    AutoriaListingsState.php     which ads were already reported
    BoardRules.php               pure rules: phone, dates, escaping
  tools/slices/schedule_setup/
    RobotSchedule.php        register/update the task in WINDOW::$scheduler
    ScheduleIntervals.php    interval -> scheduler type, pure and tested
  tools/core/                 vendor helpers, mailer, spreadsheet adapters
  data/                       result files + state
  log/                        run logs
```

The split follows the vendor's rules: a scraper does not format rows, a sink
does not parse HTML, and the slice only wires them. `BoardRules.php`,
`AutoriaListingsVehicleData.php`, `AutoriaListingsSelection.php` and
`ScheduleIntervals.php` hold no browser calls at all, which is why they are
testable — including the decision about what the buyer actually gets, which
used to sit inside the browser loop and was covered by nothing.

## What auto.ria actually serves

Checked against the live board in October 2026. This is the part that matters,
because it invalidated the old parser:

| Was | Is now |
|---|---|
| card class `mainlink` | `product-card`, with `data-car-id` |
| ad URL contains `/car/` | ad URL is `/auto_<model>_<id>.html` — `/car/` is the catalog menu |
| `class="address" href=` in raw text | JSON-LD `schema.org/Vehicle` on the ad page |
| phone from `.phone-wrap` | masked in HTML; real number after a click, see below |
| date from "Объявление добавлено …" | **not in the page at all** |

So:

- **The parser reads JSON-LD**, not CSS classes. `schema.org/Vehicle` carries
  brand, model, year, VIN, mileage, body, colour, fuel, gearbox, doors, price
  and currency; the city comes from the breadcrumb block. JSON-LD is a declared
  markup contract, so cosmetic redesigns do not break it.
- **The phone is collected by clicking.** The mask lives in the initial HTML as
  `"phone":{"content":"(068) XXX XX XX"}`, but the page is rendered on the
  client, and in the rendered DOM the mask is a button:

  ```html
  <button class="size-large conversion" data-action="showBottomPopUp">
    <span class="common-text ws-pre-wrap action">(068) XXX XX XX</span>
  </button>
  ```

  Clicking it posts to `/bff/final-page/public/auto/popUp/` and shows a
  `div.popup` with the real number. Verified on 8 live ads — 8 of 8 revealed,
  dealers and private sellers alike, no login required.

  The button is found by its mask text, not by class: there are two
  `size-large conversion` buttons on the page (the other is "Понимаю и
  разрешаю") and six `data-action="showBottomPopUp"` buttons, so neither
  attribute identifies it on its own.
- **There is no posted date**, so "only new" is decided by the state file, not
  by the board's date. That is more accurate anyway — the C# version lost ads
  silently on exactly this field.

**And there is no sort by date either.** The board offers no ordering control
at all: no `sort_by`, no ordering switch — only filters (Все / Б/у / Новые /
Под пригон). On a make page like `/car/bmw` the order is whatever the board
chose. Reading only the first page therefore means missing new ads, and the
miss looks like "no new ads". So the robot walks several pages: `$autoriaPages`
in `run.php`, 3 by default, 20 ads per page, verified as 60 distinct ads over
3 pages. This costs little in steady state, because ads already reported are
not opened again — each run pays only for loading the list pages.

If the reveal ever stops working, `phone_masked` still shows what the board
displayed, and the log says the number was not released — it never silently
leaves the column empty, which would look like "this ad has no phone".

`tests/RealPageFixture.php` holds markup lifted from the live pages. Whoever
fixes the parser after the next redesign updates the fixture, and the tests fail
until parsing works again.

## Scheduling

The robot registers itself in the Studio scheduler (`WINDOW::$scheduler`) on
start. Set `$scheduleInterval` in `run.php` and the task is created — or updated
if it already exists, so re-running never piles up duplicate tasks and never
doubles how often the board gets checked.

The interval list is the same one the C# version showed:

```
раз в минуту, 3 минуты, 5, 10, 15, 20, 30 минут, час,
2, 3, 4, 5, 10, 12 часов, сутки, неделю
```

**The Studio scheduler cannot express all of them.** It has fixed intervals —
every minute, 5 minutes, 10 minutes, half an hour, hourly, daily, weekly — and
no arbitrary value. So `раз в 2 часа` is *not* created, and the log says so
instead of silently substituting something else: quietly checking twice as
often as someone asked is worse than not scheduling at all. For odd intervals,
use Windows Task Scheduler and set `$scheduleRegisterOnRun = false`.

## Tests

No CI, all local:

```
php robot/tests/tests.php
```

279 checks. Phone normalization, `8` → `7` conversion, date comparison, ad
de-duplication, HTML/CSV escaping, which links count as ads, JSON-LD parsing
against real markup, what the report accepts and rejects, interval mapping,
and a full write of the result file through the vendor writer — objects →
writer → file, parsed back and checked. No Studio and no browser needed.

These checks are not decoration. They are the rules ported from the C# version,
where they had already caught two real bugs — `GetTypeByUrl(null)` throwing on
`IndexOf`, and Russian numbers with an `8` prefix being treated as foreign. The
fixture then caught three more: the seller name is stored in two different
shapes in the payload, and the ad id is not at the end of the URL when query
parameters follow.

## Output format

`$autoriaBoards[*]['file']` decides the format by extension, and the report
**accumulates**: every run appends what it found and never rewrites the
previous rows. That matters — checks run every few minutes, and a file that
only holds the last five minutes is no use to anyone.

This is why the default is `.csv`: the CSV writer can append, the xlsx
writer cannot. Its adapter creates the workbook from scratch on every run,
so an `.xlsx` result would contain only the last check. Use `.xlsx` if you
want a per-run snapshot and keep one file per run.

The CSV carries a UTF-8 byte-order mark, written by the sink because the
vendor writer emits plain UTF-8 — Excel opens such files by guessing and
shows mojibake for Cyrillic. The mark is added once, when the report is
created, and is not duplicated on later runs.

Because the report accumulates, its first two columns are `board` and
`found_at` — without them you cannot tell this morning's ads from last
week's, and after changing filters you cannot tell which filter found what.
The sink stamps both at write time, so they stay correct even if the same
ad is reached by a different task.

Columns are picked by name, not by position, so reordering fields in
`AutoriaListingItem` cannot shift data into the wrong column.

The `.xlsx` path could not be run here — it goes through `SYSTEM::$excelfile`
on the Studio side — but it was checked against the API: `ExcelHelper::create()`
writes the header row, and the `0` passed to `add_rows()` is the **sheet
number, not the first row**, so the header is not overwritten. The CSV path
is exercised for real.

## Before submitting to the catalog

`robot/passport.json` is filled in as far as it can be without you: robot
name, a unique key, timestamps and the description. Three fields still need
you, because they are yours and I would only be guessing:

- `LaunchedFilePath` — full path to `run.php` on a buyer's machine;
- `DeveloperName` — your name;
- `DeveloperComments` — a link to the project documentation.

The remaining fields (`Customer`, `WmName`, `DogovorNum` and the rest) are
vendor internal fields the robot never reads. How to fill them is in the
Human Emulator documentation, section "Паспорт".

## Boards that were not built, and why

- **rst.ua is behind Cloudflare Turnstile.** A plain request gets `403`, and a
  real Chrome — headless or not — lands on "Один момент…" and stays there.
  That is an anti-bot wall by design, not a parsing problem: getting past it
  means a captcha-solving service (ongoing cost per ad) or relying on Studio's
  headed browser passing where headless does not. Untested and unpriced, so
  there is no parser here.
- **OLX forbids scraping in its terms.** Do not build a paid product on it.
- **Phone numbers on auto.ria are off by default** — see the conditions
  section above. Not a technical limit, a licensing one.

The robots' own conditions for auto.ria are also unchecked. Read them before
selling.

## Board conditions — read this before selling

Checked against `https://www.ria.com/offert/auto/` and
`https://auto.ria.com/robots.txt`.

**`robots.txt` allows what the robot does.** For `User-agent: *` there is no
`Disallow: /` — only specific paths are excluded, none of which the listing or
ad pages use.

**The offer allows collecting ad data.** Automated access to platform data
that does *not* contain phone numbers is explicitly permitted: price, mileage,
VIN, city, title, seller. That is most of what this robot collects.

**The offer forbids collecting phone numbers.** Clause 1.21 says, in the
parts that matter:

- 1.21.1 — any automated access for collecting, copying, indexing, scraping,
  aggregating or otherwise using users' phone numbers;
- 1.21.2 — using or copying phone numbers without a separate licensing
  agreement with the company;
- 1.21.3 — using phone numbers without prior written consent, including
  automated extraction, accumulation, archiving or caching, and putting them
  into databases;
- 1.21.4 — using numbers for commerce or mailing, or passing them to third
  parties.

So the phone-reveal code in `AutoriaListingsPhoneReveal` is **off by default**
(`$collectPhones = false` in `run.php`) and must stay off unless you hold
written consent from RIA. It is not there as a convenience toggle: selling a
paid robot that does this is the same category of problem as OLX's scraping
ban, and it would put your own channel at risk.

Everything the robot collects with the flag off is the permitted part.

## What is not finished

- The reveal itself is verified through a real browser on 8 live ads, but the
  XHE calls that drive it (`click()`, `get_all_by_class`, the popup wait) have
  not been run inside Studio. The order of actions and the phone extraction are
  verified; the transport is not. The same applies to the scheduler.
- Notification is not wired into the slice yet: the vendor mailer
  (`TOOLS::$mailer`) is available and configured in `run.php`, but new ads only
  reach the file.

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