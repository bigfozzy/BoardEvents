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
- **`wbIE`** (MSHTML `WebBrowser`) в `Main.Designer.cs` — IE-движок, вне поддержки.
  Используется для отображения вариантов с olx.ua. Замена на второй
  `ChromiumWebBrowser` или удаление с переходом на CEF для всех досок.
- **Пароль почты хранится открытым текстом** в user-settings. Учитывая
  фейковый характер тестовых данных — низкий приоритет, но при вводе
  реального пароля стоит перейти на DPAPI (`ProtectedData`).
- **`tasks.json` и резервные копии** — в `.gitignore`, но при разработке
  рядом с exe накапливаются `.bak`/`.bak2`/`.bak3`. Ротация трёх копий
  теперь сообщает об ошибке, если копия не создалась.

## Проверенные настройки расписания

Строки в `cbTimeCheck` (`AddTaskDlg.Designer.cs`) должны совпадать с условиями
в `BaseTask.StartScheduling`. Несовпадение означает молчаливо неработающий триггер.

Ранее `"раз 10 часов"` не совпадало с `"раз в 10 часов"` из UI — интервал
не работал вообще. Проверять это при добавлении новых вариантов интервала.
