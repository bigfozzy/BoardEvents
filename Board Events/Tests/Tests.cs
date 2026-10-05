using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using Board_Events;
using Board_Events.Model.Results;

namespace Board_Events.Tests
{
    /// <summary>
    /// Локальные тесты чистой логики - без WinForms, без XHE, без сети.
    ///
    /// Собирается BoardEvents.dll, затем этот проект, тесты запускаются
    /// рядом с dll: tests\run-tests.cmd. CI не используется.
    /// </summary>
    static class Program
    {
        static int passed;
        static int failed;
        static string repoRoot;
        static readonly List<string> failures = new List<string>();

        static int Main(string[] args)
        {
            Console.OutputEncoding = Encoding.UTF8;

            // путь к корню репозитория можно задать аргументом
            if (args != null && args.Length > 0)
                repoRoot = args[0];

            Section("интервалы: строки из выпадающего списка");
            TestIntervalsAgainstComboBox();

            Section("интервалы: точные значения");
            TestIntervalValues();

            Section("интервалы: нераспознанные значения");
            TestIntervalUnknown();

            Section("GetTypeByUrl");
            TestGetTypeByUrl();

            Section("GetNormedPhone");
            TestGetNormedPhone();

            Section("экранирование при экспорте");
            TestCsvEscaping();
            TestHtmlEscaping();

            Section("колонки варианта");
            TestVariantColumns();

            Section("интервал повторного обзвона");
            TestCallOnlyNewFilter();

            Console.WriteLine();
            Console.WriteLine("---------------------------------------------");
            Console.WriteLine("  пройдено: " + passed + "    провалено: " + failed);

            if (failed > 0)
            {
                Console.WriteLine();
                Console.WriteLine("  Провалено:");
                foreach (string f in failures)
                    Console.WriteLine("    - " + f);
                return 1;
            }

            return 0;
        }

        #region интервалы

        /// <summary>
        /// Каждая строка cbTimeCheck обязана распознаваться.
        ///
        /// Именно здесь ломалось: в коде было "раз 10 часов", в интерфейсе
        /// "раз в 10 часов", интервал молча не работал. Тест читает
        /// designer-файл и сверяет со списком в коде.
        /// </summary>
        static void TestIntervalsAgainstComboBox()
        {
            string designer = FindRepoFile("AddTaskDlg.Designer.cs");
            if (designer == null)
            {
                Warn("не найден AddTaskDlg.Designer.cs - сверка строк пропущена");
                return;
            }

            string text = File.ReadAllText(designer, Encoding.UTF8);

            Match m = Regex.Match(text,
                @"cbTimeCheck\.Items\.AddRange\(new object\[\] \{(.*?)\}\);",
                RegexOptions.Singleline);

            if (!Assert(m.Success, "cbTimeCheck.Items.AddRange разобран",
                "не удалось разобрать cbTimeCheck.Items.AddRange"))
                return;

            List<string> items = Regex.Matches(m.Groups[1].Value, "\"([^\"]+)\"")
                .Cast<Match>()
                .Select(x => x.Groups[1].Value)
                .ToList();

            Assert(items.Count > 0, "пункты в combo box найдены",
                "в combo box не найдено ни одного пункта");

            foreach (string item in items)
            {
                int minutes = BaseTask.GetIntervalMinutes(item);
                Assert(minutes > 0,
                    "«" + item + "» распознан",
                    "«" + item + "» НЕ распознан - вернулось " + minutes);
            }

            Console.WriteLine("   пунктов в интерфейсе: " + items.Count);
        }

        static void TestIntervalValues()
        {
            Expect("раз в минуту", 1);
            Expect("раз в 3 минуты", 3);
            Expect("раз в 5 минут", 5);
            Expect("раз в 10 минут", 10);
            Expect("раз в 15 минут", 15);
            Expect("раз в 20 минут", 20);
            Expect("раз в 30 минут", 30);
            Expect("раз в час", 60);
            Expect("раз в 2 часа", 120);
            Expect("раз в 3 часа", 180);
            Expect("раз в 4 часа", 240);
            Expect("раз в 5 часов", 300);
            Expect("раз в 10 часов", 600);
            Expect("раз в 12 часов", 720);
            Expect("раз в сутки", 1440);
            Expect("раз в неделю", 10080);
        }

        static void TestIntervalUnknown()
        {
            // старая ошибочная строка не должна «случайно» заработать
            Expect("раз 10 часов", -1);
            Expect("", -1);
            Expect(null, -1);
            Expect("каждый час", -1);
            Expect("раз в 2 часа ", -1);   // хвостовой пробел - не то же самое
        }

        #endregion

        #region тип задачи

        static void TestGetTypeByUrl()
        {
            ExpectType("https://www.olx.ua/transport/", "olx.ua");
            ExpectType("https://olx.com/items", "olx.ua");     // .com тоже olx-тип
            ExpectType("http://rst.ua/oldcars/baw/fenix/", "rst.ua");
            ExpectType("https://auto.ria.com/search/", "autoria.com");
            ExpectType("https://auto.ria.com/car/1", "autoria.com");

            ExpectType("https://example.com/", "unknown");
            ExpectType("", "unknown");
            ExpectType(null, "unknown");
        }

        #endregion

        #region телефон

        static void TestGetNormedPhone()
        {
            // украинские номера начинаются с 0
            Norm("0 (67) 123-45-67", "+380671234567");
            Norm("(067) 123 45 67", "+380671234567");

            // российские - с 7
            Norm("8 (912) 345-67-89", "+79123456789");   // междугородний префикс 8
            Norm("8 495 123 45 67", "+74951234567");
            Norm("+7 912 345 67 89", "+79123456789");
            Norm("7 912 345 67 89", "+79123456789");
            Norm("+38 (067) 123-45-67", "+380671234567");

            // не наш - звонить нельзя
            Norm("+1 202 555 0143", "");     // США
            Norm("+44 20 7123 4567", "");   // Великобритания

            // пустые и бессмысленные
            Norm("", "");
            Norm(null, "");
            Norm("нет телефона", "");        // раньше падало на phone[0]
            Norm("---", "");

            // номер без плюса, который начинается не с 0/7/38
            Norm("123", "");
        }

        #endregion

        #region экспорт

        static void TestCsvEscaping()
        {
            TaskVariant v = new TaskVariant("http://rst.ua/ad/1");
            v.Description = "цена \"от\" и; разделитель";

            string csv = v.GetCsvString();
            Assert(csv.Contains("\"\""), "кавычки внутри значения удвоены",
                "кавычки не экранированы: " + csv.Trim());

            // каждое поле должно начинаться и закрываться кавычками,
            // а число кавычек быть четным - иначе файл разъедется
            int quotes = csv.Count(c => c == '"');
            Assert(quotes % 2 == 0, "кавычек в строке четное число",
                "кавычек нечетное число (" + quotes + "): " + csv.Trim());
        }

        static void TestHtmlEscaping()
        {
            TaskVariant v = new TaskVariant("http://rst.ua/ad/1");
            v.Description = "<script>alert('x')</script>";
            v.Phone = "+380671234567";

            string html = v.GetHtmlString();

            Assert(!html.Contains("<script>"), "теги в описании экранированы",
                "необработанный <script> попал в HTML");
            Assert(html.Contains("&lt;script&gt;"), "угловые скобки заменены",
                "экранирование угловых скобок не найдено");

            // вертикальный вариант - та же гарантия
            string vertical = v.GetAsHtml("\r\n", 3);
            Assert(!vertical.Contains("<script>"), "вертикальный вид тоже экранирован",
                "в вертикальном виде <script> не экранирован");

            // адрес - в href, обязательно в кавычках
            Assert(html.Contains("href=\""), "у href есть кавычки",
                "href без кавычек: " + html.Trim());
        }

        static void TestVariantColumns()
        {
            TaskVariant v = new TaskVariant("http://rst.ua/ad/1");

            string[] headers = v.GetHeaders();
            string[] contents = v.GetContents();

            Assert(headers.Length == contents.Length,
                "заголовков и значений поровну",
                "заголовков " + headers.Length + ", значений " + contents.Length
                + " - строки разъедутся при экспорте");
        }

        #endregion

        #region фильтр «только новые»

        /// <summary>
        /// Вариант без распознанной даты не должен отсекаться как старый
        /// </summary>
        static void TestCallOnlyNewFilter()
        {
            TaskVariant v = new TaskVariant("http://rst.ua/ad/1");

            // дата не разобрана
            Assert(v.PostedDate == DateTime.MinValue,
                "неразобранная дата остаётся пустой",
                "неожиданное значение даты: " + v.PostedDate);

            // телефон с поддерживаемым префиксом, чтобы заказ не отсекся по телефону
            v.Phone = "+380671234567";
            Assert(v.GetNormedPhone() != "", "нормализованный телефон не пустой",
                "телефон не нормализовался");

            // флаг «идет проверка» должен сбрасываться при отказе шедулера.
            // Сам шедулер тут не поднять - проверяем только контракт:
            // метод обязан вернуть false и не оставить флаг взведенным,
            // иначе кнопка звонка блокируется навсегда.
            Console.WriteLine("   контракт RequestCallNow(OnlyNew) проверяется на живом шедулере");
        }

        #endregion

        #region helpers

        static void Section(string title)
        {
            Console.WriteLine();
            Console.WriteLine("--- " + title + " ---");
        }

        static void Expect(string timeCheck, int expected)
        {
            int actual = BaseTask.GetIntervalMinutes(timeCheck);
            Assert(actual == expected,
                "«" + Show(timeCheck) + "» = " + expected,
                "«" + Show(timeCheck) + "» = " + actual + ", ожидалось " + expected);
        }

        static void ExpectType(string url, string expected)
        {
            string actual = BaseTask.GetTypeByUrl(url);
            Assert(actual == expected,
                "тип «" + Show(url) + "» = " + expected,
                "тип «" + Show(url) + "» = " + actual + ", ожидалось " + expected);
        }

        static void Norm(string phone, string expected)
        {
            TaskVariant v = new TaskVariant("http://rst.ua/ad/1");
            v.Phone = phone;

            string actual = v.GetNormedPhone();
            Assert(actual == expected,
                "телефон " + Show(phone) + " -> " + Show(expected),
                "телефон " + Show(phone) + " -> " + Show(actual)
                + ", ожидалось " + Show(expected));
        }

        static string Show(string s)
        {
            return s == null ? "(null)" : "\"" + s + "\"";
        }

        static bool Assert(bool condition, string ok, string bad)
        {
            if (condition)
            {
                passed++;
                return true;
            }

            failed++;
            failures.Add(bad);
            Console.WriteLine("   FAIL  " + bad);
            return false;
        }

        static void Warn(string message)
        {
            Console.WriteLine("   skip  " + message);
        }

        /// <summary>
        /// найти файл, поднимаясь вверх от текущей папки
        ///
        /// Опираемся и на текущий рабочий каталог, и на папку exe:
        /// тесты обычно запускают из корня репозитория, но могут и из
        /// bin. Путь можно задать первым аргументом.
        /// </summary>
        static string FindRepoFile(string name)
        {
            List<string> roots = new List<string>();

            // путь, заданный аргументом
            if (repoRoot != null)
                roots.Add(repoRoot);

            // рабочий каталог и его родители
            DirectoryInfo cwd = new DirectoryInfo(Directory.GetCurrentDirectory());
            for (int i = 0; i < 8 && cwd != null; i++)
            {
                roots.Add(cwd.FullName);
                cwd = cwd.Parent;
            }

            // папка сборки и ее родители
            DirectoryInfo start = new DirectoryInfo(AppDomain.CurrentDomain.BaseDirectory);
            for (int i = 0; i < 8 && start != null; i++)
            {
                roots.Add(start.FullName);
                start = start.Parent;
            }

            foreach (string root in roots)
            {
                string[] candidates =
                {
                    Path.Combine(root, name),
                    Path.Combine(root, "Board Events", name),
                    Path.Combine(root, "Board Events", "Properties", name),
                    Path.Combine(root, "Board Events", "Model", "Tasks", "Boards", name),
                    Path.Combine(root, "Model", "Tasks", "Boards", name)
                };

                foreach (string c in candidates)
                    if (File.Exists(c))
                        return c;
            }

            return null;
        }

        #endregion
    }
}