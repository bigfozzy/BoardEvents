<img src="https://humanemulator.info/images/logo.png" alt="[logo]" width="48"/> Board event based on XWeb Human Emulator

## BoardEvents

The Board Event program works with three classified boards (olx.ua, rst.ua,
auto.ria.com), downloads new ads in the specified topics, and notifies by
e-mail or requests a call back.

Each check runs in its own thread and launches an XHE instance on its own
port. Checks are driven by [Quartz] scheduling; the board pages are shown in
an embedded CEF browser.

Built on the XHE library (C#). [Library sources on github].

### Requirements

- [Visual Studio 2019] & [.NET Framework 4.6.2 Developer Pack]
- `XHE.dll` and `libcurl.NET.dll` — see **Build** below

### Build

`Board Events.sln` references the XHE project through an absolute path that
exists only on the original author's machine, so **the solution does not
open for anyone else**. Build the single project instead:

```
nuget restore
msbuild "Board Events\Board Events.csproj" /p:Configuration=Release /p:Platform=x86
```

The XHE reference resolves through `HintPath` to
`..\..\..\..\Visual C\XHE\...\XHE.dll`. Point it at your own copy of
`XHE.dll` and `libcurl.NET.dll`, or drop both into `Board Events\lib\` and
edit the two `HintPath` entries.

### Layout at runtime

Emulators are expected one folder per port, next to the executable:

```
Board Events.exe
XHE\
  11000\11000.exe    ... 11090\11090.exe   task checks
  12000\12000.exe    ... 12010\12010.exe   call requests
  13000\13000.exe                          variant re-checks
```

The port list lives in `Threads/XhePorts.cs` and is used both by the workers
and by the start-up preparation, so the two cannot drift. On first run the
app launches and closes each existing emulator once to trigger activation.
Missing folders are reported in one dialog with the expected paths; nothing
works until they are in place.

`tasks.json` and up to three backups (`.bak`, `.bak2`, `.bak3`) are written
next to the executable. The save is atomic - it goes to `tasks.json.tmp` and
is swapped in with `File.Replace`, so a crash mid-write cannot destroy the
previous version. Checks that find new ads save automatically once a minute.

### Known limitations

- `XHE.dll` is IE-based and is owned by the library vendor; it is not
  upgraded with this project. Modern JS-heavy boards may not parse cleanly.
- The password of the sending mailbox is stored unencrypted in the .NET user
  settings.

[Quartz]: https://www.quartz-scheduler.net/
[Library sources on github]: https://github.com/bigfozzy/Templates-CSHARP
[Visual Studio 2019]: https://www.visualstudio.com/downloads/
[.NET Framework 4.6.2 Developer Pack]: https://dotnet.microsoft.com/download/dotnet-framework/net462

---

# Русский
## BoardEvents

Программа Board Event работает с тремя досками объявлений (olx.ua, rst.ua,
auto.ria.com), скачивает новые объявления в заданных темах и уведомляет по
e-mail или заказывает обратный звонок.

Каждая проверка идёт в своём потоке и поднимает отдельный экземпляр
[Human Emulator на основе IE] на своём порту. Проверки запускает
планировщик [Quartz], просмотр объявлений - встроенный браузер на CEF.

Проект работает на основе библиотеки XHE.dll на C#.
[Исходники библиотеки на github]

### Что нужно

1. [Visual Studio 2019] & [.NET Framework 4.6.2 Developer Pack]
2. `XHE.dll` и `libcurl.NET.dll` — см. раздел «Сборка»

### Сборка

`Board Events.sln` подключает проект XHE по абсолютному пути, который
существует только на машине автора, поэтому **решение не откроется ни у
кого другого**. Собирайте один проект:

```
nuget restore
msbuild "Board Events\Board Events.csproj" /p:Configuration=Release /p:Platform=x86
```

Ссылка на XHE идёт через `HintPath` на
`..\..\..\..\Visual C\XHE\...\XHE.dll`. Укажите свою копию `XHE.dll` и
`libcurl.NET.dll`, либо положите обе в `Board Events\lib\` и поправьте две
строки `HintPath`.

### Что должно лежать рядом с программой

По одному эмулятору на порт:

```
Board Events.exe
XHE\
  11000\11000.exe    ... 11090\11090.exe   проверки задач
  12000\12000.exe    ... 12010\12010.exe   заказ звонков
  13000\13000.exe                          проверка вариантов
```

Список портов в `Threads/XhePorts.cs`; им пользуются и потоки, и подготовка
при старте, поэтому разойтись они не могут. При первом запуске программа
запускает и закрывает каждый существующий эмулятор, чтобы прошла активация.
Отсутствующие папки перечисляются одним диалогом с ожидаемыми путями; без
них работать не будет.

`tasks.json` и до трёх резервных копий (`.bak`, `.bak2`, `.bak3`) пишутся
рядом с программой. Запись атомарная: сначала `tasks.json.tmp`, затем
подмена через `File.Replace`, поэтому обрыв записи не уничтожает
предыдущую версию. Проверки, нашедшие новые объявления, сохраняют сами раз
в минуту.

### Известные ограничения

- `XHE.dll` работает на движке IE и принадлежит владельцу библиотеки, в
  рамках этого проекта не обновляется. Современные JS-сайты могут
  разбираться некорректно.
- Пароль почты отправителя хранится без шифрования в настройках .NET.

[Quartz]: https://www.quartz-scheduler.net/
[Human Emulator на основе IE]: https://humanemulator.info
[Исходники библиотеки на github]: https://github.com/bigfozzy/Templates-CSHARP
[Visual Studio 2019]: https://www.visualstudio.com/downloads/
[.NET Framework 4.6.2 Developer Pack]: https://dotnet.microsoft.com/download/dotnet-framework/net462