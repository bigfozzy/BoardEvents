# Skills Index

Each skill is `.agents/skills/<name>/SKILL.md`. The catalog of names and one-line descriptions is
already in your instructions; load a skill only when its trigger matches the request.

Routing:

1. Match the user request against the triggers below.
2. Loaded skill wins over a guess; a rule from `.agents/rules/` wins over a skill (rules are always in
   your instructions already).
3. No skill matches → look up the API (`search_api`) or ask for the missing data. Never invent a method.
4. Several matches → load at most three, in the order below.
5. Nothing worked → say what blocked you, with the exact error.

## Architecture (start here for any new robot)

### `xhe-project-layout`

**Triggers**: "структура проекта", "где положить код", "проект робота", "project layout", "scaffold", "папки проекта"
**Purpose**: Where each file belongs: entry, slices, core ports/adapters, output folders.

### `xhe-slice-solid`

**Triggers**: "солид", "solid", "архитектура", "слайс", "slice", "порты и адаптеры", "по классам", "не в одном файле"
**Purpose**: Shape of one slice (item / scraper / sink / entry) and dependence on ports, not formats.

### `xhe-dry-locators`

**Triggers**: "не дублировать", "dry", "переиспользовать", "один источник истины", "локаторы", "reuse"
**Purpose**: Locators, column lists and tunables each live in exactly one place.

### `xhe-kiss-scope`

**Triggers**: "проще", "kiss", "зачем столько файлов", "минимум кода", "keep it simple"
**Purpose**: Build only the layers the task needs; ask for missing data instead of inventing infra.

### `xhe-tabular-output`

**Triggers**: "в excel", "в таблицу", "csv", "xlsx", "отчёт", "выгрузка", "сохрани результат"
**Purpose**: Results into `data/` through `SpreadsheetWriters` — columns, formats, empty result, batching.

### `xhe-run-and-verify`

**Triggers**: "запусти", "проверь", "не работает", "ошибка", "run", "verify", "готово"
**Purpose**: Run, read the real output, fix the exact line, report only what was verified.

## XHE API patterns

### `xhe-initialization`

**Triggers**: "инициализация XHE", "инициализация", "initialize XHE", "initialization"
**Purpose**: Initialize the XHE API in PHP scripts.

### `xhe-quit-pattern`

**Triggers**: "завершение", "выход", "quit", "shutdown"
**Purpose**: Proper XHE server shutdown.

### `xhe-static-property-access`

**Triggers**: "статическое свойство", "static property", "WEB::", "DOM::", "SYSTEM::", "WINDOW::"
**Purpose**: Access XHE classes through their static properties.

### `xhe-dom-corresponding-class`

**Triggers**: "DOM класс", "DOM element", "DOM элемент"
**Purpose**: Pick the matching class for each DOM element type.

### `xhe-element-selection`

**Triggers**: "выбор элемента", "element selection", "find element", "поиск элемента"
**Purpose**: Pick the right search method for an element.

### `xhe-element-waiting`

**Triggers**: "ожидание элемента", "wait element", "wait for element"
**Purpose**: Wait for a DOM element to appear.

### `xhe-interface-usage`

**Triggers**: "интерфейс", "interface", "XHEInterface"
**Purpose**: Work with `XHEInterface` / `XHEInterfaces` results.

### `xhe-browser-navigation-pattern`

**Triggers**: "навигация", "browser navigation", "navigate"
**Purpose**: `wait_js()` after every `navigate()`.

### `xhe-server-side-architecture`

**Triggers**: "архитектура сервера", "server architecture", "XHE architecture"
**Purpose**: XHE API as a client-server system.

### `mcp-php-resources-read`

**Triggers**: "MCP ресурс", "читать ресурс", "mcp resource", "read resource"
**Purpose**: Read XHE API documentation resources over MCP.
**Note**: only when the MCP documentation server is reachable.

## Planning

### `task-planner`

**Triggers**: "спланируй", "создай план", "plan", "create plan", "generate plan"
**Purpose**: Write the execution plan into `PLAN.md` at the project root, nothing else.

## Creating a new skill

Only when the user explicitly asks for one: create `.agents/skills/<kebab-name>/SKILL.md` with the same
front matter as the files above (`name` equals the folder name, `description` one line), then add its
section here with triggers.
