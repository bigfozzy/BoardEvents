# BoardEvents — рабочие заметки

## Структура репозитория

Две независимые вещи:

- `robot/` — **продукт**. PHP-робот под Human Emulator Studio 7.x.
  Правила проекта: `robot/AGENTS.md` (это правила вендора XHE, лежат
  в комплекте шаблона — не переписывать).
- `legacy-csharp/` — архив. C#-приложение под `XHE.dll` 4.x на движке IE.
  Сохраняется для сравнения и переноса идей; на Studio 7.x не работает
  и в продаже не участвует.

## Тестирование

**Все проверки локальные. CI не нужен и не создаётся.**

Две команды, независимые друг от друга:

```
php robot\tests\tests.php                        # 72 проверки, XHE не нужен
pwsh -File legacy-csharp\tests\run-tests.ps1     # 69 проверок, нужен csc
```

`robot/tests/tests.php` — самодостаточный раннер без тестового фреймворка:
ни PHPUnit, ни composer. Проверяет чистые правила (телефоны, даты,
экранирование, порядок колонок) и потому запускается любым PHP.

**Что покрыто:** нормализация телефонов (в т.ч. российский префикс `8` →
`7`), `getTypeByUrl`, отсев по дате, дедупликация адресов, экранирование
CSV и HTML, разбор человеческих дат доски, согласованность заголовков и
колонок.

Тесты находят реальные баги — в C#-версии они уже нашли
`GetTypeByUrl(null)` и российские номера с префиксом `8`.

Не покрыто: всё, что требует живого браузера (сам парсер auto.ria,
открытие объявлений), и работа в Studio.

## НЕЛЬЗЯ запускать робот из консоли

`php run.php` **не работает** — так написано в `robot/AGENTS.md`
(Hard rules). Обращения `WEB::$browser` — это HTTP к запущенной Studio;
без неё падает каждый фасад. Проверка изменений робота идёт через Studio
или через assistant run tool, а не через shell.

Для правок логики достаточно `php robot\tests\tests.php` — он Studio
не касается.

## Ло��атор auto.ria

Локаторы живут в одном месте: константы в
`robot/tools/slices/autoria_listings/AutoriaListingsScraper.php`.

Вёрстка доски меняется без предупреждения. Когда перестал работать —
сначала открыть страницу глазами и посмотреть, что в разметке, и только
потом менять константы. **Ничего не хардкодим наугад: пустой результат
честнее неверного.**

## Учётные данные в репозитории

Почта и пароль в `legacy-csharp/Settings.settings` / `App.config` /
`Settings.Designer.cs`, а также `robot/settings/settings.json` —
**фейковые, изначально заведённые как заглушки**. Реальных доступов нет,
ротация пароля не требуется.

История git очищена от этих значений через `git filter-repo`
(сентябрь 2026), force-push выполнен. Не восстанавливать эти строки при
отладке или откате настроек — заменить на пустые значения.

`robot/data/*_state.json` — локальные данные покупателя (какие
объявления уже отправлены), в git не попадают.

## Сборка legacy через MSBuild не работает

`XHE.dll` и `libcurl.NET.dll` подключаются по жёстко зашитому `HintPath`:
`..\..\..\..\Visual C\XHE\_Debug\Templates CSHARP\Lib\XHE\XHE\bin\Debug\`.
Путь существует только на машине автора. То же в
`legacy-csharp/legacy-csharp.sln` — там подключён проект XHE по тому
же пути.

Из-за этого `nuget restore` + сборка на другой машине не пройдёт.
Проверять изменения нужно через `csc` с DLL из `E:\Nuget`.

Компиляция проверяется напрямую через Roslyn `csc`, без MSBuild и без
серверов (VS2019 BuildTools, .NET Framework 4.6.2, C# 7.3):

```
& "C:\Program Files (x86)\Microsoft Visual Studio\2019\BuildTools\MSBuild\Current\Bin\Roslyn\csc.exe" @build.rsp
```

Список пакетов лежит в `E:\Nuget` (global packages folder) — версии
13.0.3 Newtonsoft, 120.2.70 CefSharp, 3.8.0 Quartz. Полный список
остальных версий — в `legacy-csharp/packages.config`.

Замечание: `csc` из `C:\Windows\Microsoft.NET\Framework64\v4.0.30319\` —
старый, поддерживает только C# 5. Для C# 7.3 нужен Roslyn из BuildTools.

## API Human Emulator 7.x

Источник истины — локальные исходники, а не документация:

```
Templates\Objects\Web\xhe_browser.php     navigate, wait_js, set_default_download
Templates\Objects\DOM\xhe_anchor.php      get_all_by_class, get_all_by_href, get_by_class
Templates\Objects\System\xhe_logger.php   init, info, warn, error
Templates\Objects\Web\xhe_mail.php        smtp_connect, send_mail_via_smtp
```

Подписи **проверять по исходникам**, а не по памяти. Уже находились
расхождения: `get_all_hrefs_by_class` не существует (есть
`get_all_by_class`, возвращающий коллекцию), логинатор не инициализируется
самим `init.php` — вызывать `SYSTEM::$logger->init(...)` самому.

Коллекции `get_all_by_*` — `Countable`, итерируются, элемент через
`->get($i)`. Перед действием проверять `->is_exist()`.

## Порты эмуляторов (legacy)

Номера портов **нельзя считать вручную**. Всё в
`legacy-csharp/Threads/XhePorts.cs`: `XhePorts.Check(thread)`,
`.Call(thread)`, `.VariantCheck(thread)`, `.All()` для подготовки при
старте, `.IsPrepared(port)`.

`numThreads` в трёх `*Thread` и размер массива слотов заданы теми же
константами (`XhePorts.checkCount` / `callCount` / `variantCheckCount`).
`GetFreeThreadIndex` молча обрезает лимит по длине массива — если размер
разойдётся с числом потоков, лишние потоки просто не заработают.

## Сохранение задач (legacy)

`TasksController.SerializeAllTasks` пишет `tasks.json.tmp`, подменяет
через `File.Replace` (одна атомарная операция, бэкап уходит в `prev`),
и только потом сдвигает цепочку `.bak` → `.bak2` → `.bak3`. Ротация
**до** записи была причиной потери всех задач при сбое.

Автосохранение — `AutoSaveTasks()`, раз в 60 секунд после проверки, с
фонового потока. Из не-UI-потока диалоги не показываются
(`SerializeAllTasks(quiet: true)` пишет в лог) — `MessageBox` из рабочего
потока вешает интерфейс.

## Один экземпляр (legacy)

`Program.Main` держит `Mutex` на всё время работы. Второй экземпляр
перетирал бы `tasks.json` при выходе и запускал те же порты XHE.

## Проверенные настройки расписания (legacy)

Строки в `cbTimeCheck` (`legacy-csharp/AddTaskDlg.Designer.cs`) должны
совпадать с условиями в `BaseTask.StartScheduling`. Несовпадение означает
молчаливо неработающий триггер.

Ранее `"раз 10 часов"` не совпадало с `"раз в 10 часов"` из UI — интервал
не работал вообще. Проверять это при добавлении новых вариантов интервала.

Тот же список строк продублирован в
`robot/tools/slices/autoria_listings/BoardRules.php`
(`getIntervalMinutes`) и покрыт тестами.

## Известные осознанные долги

### legacy-csharp/

- **Quartz 3.8.0** — обновлён с 2.4.1 (октябрь 2026). Ключевые отличия:
  - `IJob.Execute` возвращает `Task`. Тела задач оставлены синхронными,
    `Execute` их запускает через `RunJobBody` (`BaseThreadWithXHE`)
    в ThreadPool — иначе поток пула Quartz занят на все `Thread.Sleep`
  - `IScheduler.ScheduleJob/DeleteJob/Shutdown/GetScheduler/Start`
    асинхронные. Вызовы из UI-потока ждут через
    `.ConfigureAwait(false).GetAwaiter().GetResult()` — без
    `ConfigureAwait(false)` продолжение возвращается в контекст UI и
    ждёт сам себя (дедлок)
  - пул: `quartz.threadPool.type` = `Quartz.Simpl.DefaultThreadPool`,
    `maxConcurrency` = 20 (в 3.x ключ не константа в исходниках фабрики,
    но `PropertyThreadPoolPrefix` = `quartz.threadPool` — префикс верный)
- **`wbIE` удалён** (октябрь 2026). Второй встроенный движок
  (`System.Windows.Forms.WebBrowser`, MSHTML) использовался только для
  olx.ua с пометкой «не работает в CEF» — при CEF 53 / Chromium 53.
  На CEF 120 причина исчезла, а IE-мотор всё равно не грузит
  современные JS-сайты.
- **`AddOnlyNewVariants` по умолчанию включён.** Отсеивает варианты с
  `PostedDate` раньше `CreateDate` задачи. Поведение намеренное.
- **Пароль почты хранится открытым текстом** в user-settings.
  Учитывая фейковый характер тестовых данных — низкий приоритет, но при
  вводе реального пароля стоит перейти на DPAPI (`ProtectedData`).
- **Неиспользуемые `using`** в большинстве файлов. Автоудаление не
  делалось: правка ради косметики с риском сломать сборку.
- **Неиспользуемые настройки** `MainFormLocation`, `bCheckTasksByStart`,
  `tcTaskDescribtion_SelectedIndex` — остались от прежних версий.

### robot/

- **Парсер только auto.ria.** `BoardRules` знает olx и rst, но
  `AutoriaListingsScraper` — единственный. Новая доска = новый слайс.
- **Расписание не подключено.** Один запуск = один проход. Дальше —
  `WINDOW\scheduler` или планировщик Windows.
- **Telegram не сделан.** Почта подключена через `TOOLS::$mailer`
  вендора; для Telegram нужен отдельный слайс.
- **OLX запрещает scraping** в своих условиях. Платный продукт на нём
  строить нельзя. RST условия не проверялись.
- **Пустая дата принимается** как «новое» (`BoardRules::shouldAccept`).
  В C# пустая дата считалась старой и объявление молча терялось.
  В логе видно счётчик «без даты».
