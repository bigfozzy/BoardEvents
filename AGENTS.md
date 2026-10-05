# BoardEvents — рабочие заметки

## Тестирование

**Все проверки локальные. CI не нужен и не создаётся.**
Компиляция проверяется напрямую через Roslyn `csc`, без MSBuild и без серверов.

Полезная команда (VS2019 BuildTools, .NET Framework 4.6.2, C# 7.3):

```
& "C:\Program Files (x86)\Microsoft Visual Studio\2019\BuildTools\MSBuild\Current\Bin\Roslyn\csc.exe" @build.rsp
```

Список пакетов лежит в `E:\Nuget` (global packages folder) — версии 13.0.3 Newtonsoft,
120.2.70 CefSharp, 3.8.0 Quartz. Полный список остальных версий — в `packages.config`.

Замечание: `csc` из `C:\Windows\Microsoft.NET\Framework64\v4.0.30319\` — старый,
поддерживает только C# 5. Для C# 7.3 нужен Roslyn из BuildTools (см. выше).

## Учётные данные в репозитории

Почта и пароль в `Settings.settings` / `App.config` / `Settings.Designer.cs` —
**фейковые, изначально заведённые как заглушки**. Реальных доступов нет,
ротация пароля не требуется.

История git очищена от этих значений через `git filter-repo` (сентябрь 2026),
force-push выполнен. Не восстанавливать эти строки при отладке или откате
настроек — заменить на пустые значения.

## Сборка через MSBuild не работает

`XHE.dll` и `libcurl.NET.dll` подключаются по жёстко зашитому `HintPath`:
`..\..\..\..\Visual C\XHE\_Debug\Templates CSHARP\Lib\XHE\XHE\bin\Debug\`.
Путь существует только на машине автора. То же в `Board Events.sln` — там
подключён проект XHE по тому же пути.

Из-за этого `nuget restore` + сборка на другой машине не пройдёт. Проверять
изменения нужно через `csc` с DLL из `E:\Nuget`.

## Внешняя зависимость: XHE

`XHE.dll` версии 4.10.9 — Human Emulator на базе IE. Управляется версией
владельца библиотеки, не обновляется в рамках этого проекта. IE-мотор
вне поддержки Microsoft — при отладке страниц учитывать расхождения с
современными OLX / RST / AutoRia.

Замена `XHE.dll` на что-либо современное — крупная задача, затрагивает
`BaseTask.Check`, `TaskVariant.RequestCall` и все три класса потоков.

## Порты эмуляторов

Номера портов **нельзя считать вручную**. Всё в `Threads/XhePorts.cs`:
`XhePorts.Check(thread)`, `.Call(thread)`, `.VariantCheck(thread)`,
`.All()` для подготовки при старте, `.IsPrepared(port)`.

`numThreads` в трёх `*Thread` и размер массива слотов заданы теми же
константами (`XhePorts.checkCount` / `callCount` / `variantCheckCount`).
`GetFreeThreadIndex` молча обрезает лимит по длине массива — если размер
разойдётся с числом потоков, лишние потоки просто не заработают.

## Сохранение задач

`TasksController.SerializeAllTasks` пишет `tasks.json.tmp`, подменяет
через `File.Replace` (одна атомарная операция, бэкап уходит в `prev`),
и только потом сдвигает цепочку `.bak` → `.bak2` → `.bak3`. Ротация
**до** записи была причиной потери всех задач при сбое.

Автосохранение — `AutoSaveTasks()`, раз в 60 секунд после проверки, с
фонового потока. Из не-UI-потока диалоги не показываются
(`SerializeAllTasks(quiet: true)` пишет в лог) — `MessageBox` из рабочего
потока вешает интерфейс.

## Один экземпляр

`Program.Main` держит `Mutex` на всё время работы. Второй экземпляр
перетирал бы `tasks.json` при выходе и запускал те же порты XHE.

## Тесты

**CI не нужен и не создаётся. Тесты локальные, запуск одной командой:**

```
pwsh -File tests\run-tests.ps1
```

Скрипт собирает приложение и тесты напрямую через Roslyn `csc` (MSBuild не
работает, см. выше), кладёт всё в `tests-out\` (в `.gitignore`) и запускает.
Пути к компилятору и пакетному кэшу переопределяются аргументами
`-CscPath` / `-NuGetRoot`.

`Board Events\Tests\Tests.cs` — самодостаточный раннер без тестового
фреймворка: ни MSTest, ни NUnit, ни xUnit. Нужен `System.Core` и
собранный `Board Events.dll`. Код возврата 0 — все проверки прошли.

**Что покрыто:** интервалы расписания (включая сверку строк с
`cbTimeCheck` в designer-файле), `GetTypeByUrl`, `GetNormedPhone`,
экранирование CSV и HTML, соответствие заголовков и колонок экспорта.

Тесты находят реальные баги — они уже нашли `GetTypeByUrl(null)` и
российские номера с префиксом `8`.

Не покрыто: всё, что требует живого XHE или WinForms (`Check`,
`RequestCall`, работа со списками на форме).

## Известные осознанные долги

- **Quartz 3.8.0** — обновлён с 2.4.1 (октябрь 2026). Ключевые отличия:
  - `IJob.Execute` возвращает `Task`. Тела задач оставлены синхронными,
    `Execute` их запускает через `RunJobBody` (`BaseThreadWithXHE`) в
    ThreadPool — иначе поток пула Quartz занят на все `Thread.Sleep`
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
  современные JS-сайты. Все доски открываются в `chromeVariant`,
  при `LoadError` страница уходит в системный браузер
  (`Main.OnVariantLoadError`).
- **`AddOnlyNewVariants` по умолчанию включён.** Отсеивает варианты с
  `PostedDate` раньше `CreateDate` задачи. Если доска отдаёт неожиданную
  дату (архив, «сегодня» без года) — варианты отбрасываются. Отсев
  виден в логе: «не добавлено: старые по дате N, без распознанной даты M».
  Поведение намеренное, менять только осознанно.
- **Пароль почты хранится открытым текстом** в user-settings. Учитывая
  фейковый характер тестовых данных — низкий приоритет, но при вводе
  реального пароля стоит перейти на DPAPI (`ProtectedData`).
- **Неиспользуемые `using`** в большинстве файлов. Автоудаление не
  делалось: правка ради косметики с риском сломать сборку.
- **Неиспользуемые настройки** `MainFormLocation`, `bCheckTasksByStart`,
  `tcTaskDescribtion_SelectedIndex` — остались от прежних версий.

## Проверенные настройки расписания

Строки в `cbTimeCheck` (`AddTaskDlg.Designer.cs`) должны совпадать с условиями
в `BaseTask.StartScheduling`. Несовпадение означает молчаливо неработающий триггер.

Ранее `"раз 10 часов"` не совпадало с `"раз в 10 часов"` из UI — интервал
не работал вообще. Проверять это при добавлении новых вариантов интервала.
