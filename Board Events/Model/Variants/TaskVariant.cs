using Board_Events.Threads;
using Quartz;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Net;
using System.Net.Mail;
using System.Text;
using System.Threading;
using System.Threading.Tasks;
using System.Windows.Forms;
using XHE;
using XHE._Helper.Tools.File;
using XHE._Helper.Tools.GUI;
using XHE._Helper.Tools.Web;

namespace Board_Events.Model.Results
{
    /// <summary>
    /// вариант задачи
    /// </summary>
    public class TaskVariant
    {
        #region делегаты

        /// <summary>
        /// делегат логирования - заказ звонка
        /// </summary>
        /// <param name="variant"></param>
        public delegate void VariantRequestCallProgressEvent(TaskVariant variant, string message);
        public event VariantRequestCallProgressEvent onVariantRequestCallCheckProgressLog = null;

        /// <summary>
        /// делегат логирования - провекра варианта
        /// </summary>
        /// <param name="variant"></param>
        public delegate void VariantCheckProgressEvent(TaskVariant variant, string message);
        public event VariantCheckProgressEvent onVarianCheckProgressLog = null;
        

        #endregion

        #region данные

        /// <summary>
        /// урл результата
        /// </summary>        
        public string Url { get; set; }

        /// <summary>
        /// номер иконки
        /// </summary>        
        public int IconIndex { get; set; }

        /// <summary>
        /// прмечания
        /// </summary>        
        public string Description { get; set; }

        /// <summary>
        /// статус
        /// </summary>        
        public string Status { get; set; }

        /// <summary>
        /// телефон
        /// </summary>        
        public string Phone { get; set; }

        /// <summary>
        /// разговор
        /// </summary>        
        public string Talk { get; set; }

        /// <summary>
        /// дата получения
        /// </summary>        
        public DateTime ReceiveDate { get; set; }

        /// <summary>
        /// дата появленяи объявления на доске
        /// </summary>        
        public DateTime PostedDate { get; set; }

        #endregion

        #region вспомогательные данные

        // имя пункта распиания, связанного с заказом звонка
        string variantJobRequestCallName = "";
        // имя пункта распиания, связанного с проверокй варианта
        string variantJobCheckName = "";
        // идентификатор задачи в шедулере
        static UInt64 shedulerVariantCounter = 0;
        // указывает что начат заказ варианта
        //
        // volatile - флаг пишется из рабочего потока, читается из UI
        // при включении кнопки заказа звонка
        public volatile bool IsRequestCallNow = false;
        // указывает что начата проверка варианта
        public volatile bool IsCheckNow = false;

        #endregion

        #region создание

        /// <summary>
        /// конструктор для JSON сериализации
        /// </summary>
        public TaskVariant()
        {
        }        

        /// <summary>
        /// конструктор
        /// </summary>
        public TaskVariant(string url)
        {
            Url = url;

            IconIndex = -1;
            Status = "к работе";
            Talk = "не проводился";
            ReceiveDate=DateTime.Now;
            Phone = "";
        }

        #endregion

        #region получение данных в различных форматах

        /// <summary>
        /// получить массив заголовков
        /// </summary>
        /// <returns></returns>
        public string[] GetHeaders()
        {
            // заголовки
            string[] headers = new string[]
            {
                "Метка",
                "Получен",
                "Адрес",
                "Телефон",
                "Статус",
                "Примечание",
                "Разговор"
            };
            return headers;
        }
        /// <summary>
        /// получить массив содержимого
        /// </summary>
        /// <returns></returns>
        public string[] GetContents()
        {
            string check = "";
            if (IconIndex >= 0)
                check = "метка " + (IconIndex + 1).ToString();
            // содержимое
            string[] contents = new string[]
            {
                check,
                ReceiveDate.ToString(),
                Url,
                Phone,
                Status,
                Description,
                Talk
            };
            return contents;
        }
        /// <summary>
        /// получить хтмл заголовок
        /// </summary>
        /// <returns></returns>
        public string GetHtmlTitle(string endLine="\r\n",bool asHeader=true)
        {
            // разделитель - заголовко таблицы или строка cnhjrf nf,kbws
            string div = "td ";
            if (asHeader)
                div = "th";

            // сформируем
            string res = "";            
            foreach (string s in GetHeaders())
                res = res + "<" + div + ">" + WebUtility.HtmlEncode(s) + "</" + div + ">";

            // для строки таблицы
            if (!asHeader)
                res = "<tr>" + res + "</tr>";
            // результат
            return res + endLine;
        }
        /// <summary>
        /// получить хтмл строку
        /// </summary>
        /// <returns></returns>
        public string GetHtmlString(string endLine = "\r\n")
        {
            // сформируем
            string res = "<tr>";
            string[] contents = GetContents();
            for (int i = 0; i < contents.Length; i++)
            {
                string s = contents[i];
                string tmp;

                // сравниваем по позиции, а не по значению - иначе описание,
                // случайно совпавшее с адресом, тоже станет ссылкой
                if (i == 2)
                    tmp = "<a href=\"" + WebUtility.HtmlEncode(Url) + "\">" + WebUtility.HtmlEncode(Url) + "</a>";
                else if (i == 3)
                    tmp = "<a href=\"tel:" + WebUtility.HtmlEncode(Phone) + "\">" + WebUtility.HtmlEncode(Phone) + "</a>";
                else
                    tmp = WebUtility.HtmlEncode(s);

                res = res + "<td>" + tmp + "</td>";
            }            
            res += "</tr>";

            // результат
            return res + endLine;            
        }
        /// <summary>
        /// получить csv заголовок
        /// </summary>
        /// <returns></returns>
        public string GetCsvTitle(string endLine = "\r\n")
        {
            // сформируем
            string res = "";
            foreach (string s in GetHeaders())
                res = res + "\"" + s + "\";";

            // результат
            return res + endLine;                   
        }
        /// <summary>
        /// получить csv строку
        /// </summary>
        /// <returns></returns>
        public string GetCsvString(string endLine = "\r\n")
        {
            // сформируем
            string res = "";
            foreach (string s in GetContents())
                // кавычки внутри значения ломают экспорт - экранируем удвоением
                res = res + "\"" + s.Replace("\"", "\"\"") + "\";";

            // результат
            return res + endLine;
        }
        /// <summary>
        /// получить как HTML
        /// </summary>
        /// <param name="endLine"></param>
        /// <returns></returns>
        public string GetAsHtml(string endLine = "\r\n",int view=1)
        {
            // создадим хтмл таблицу                
            string str = "<html><body><center><table border=1 cellpadding=10> " + endLine;
            if (view == 0 || view == 1) // горизонтальная таблица
            {                
                str += GetHtmlTitle(endLine, view == 0);
                str += GetHtmlString(endLine);                
            }
            else if (view == 3)
            { // вертикальняа талица
                string[] headers = GetHeaders();
                string[] contents = GetContents();
                for (int i = 0; i < headers.Length; i++)
                {
                    // переделка контента
                    string tmp = contents[i];
                    if (i == 2)
                        tmp = "<a href=\"" + WebUtility.HtmlEncode(Url) + "\">" + WebUtility.HtmlEncode(Url) + "</a>";
                    else if (i == 3)
                    {
                        // убираем все разделители из ссылки, но из показа - оставляем
                        string phoneUrl = GetNormedPhone();
                        if (phoneUrl == "")
                            phoneUrl = Phone;
                        tmp = "<a href=\"tel:" + WebUtility.HtmlEncode(phoneUrl) + "\">" + WebUtility.HtmlEncode(Phone) + "</a>";
                    }
                    else
                        tmp = WebUtility.HtmlEncode(tmp);

                    str += "<tr><td>" + headers[i] + "</td><td>" + tmp + "</td></tr>" + endLine;
                }
            }
            str += "</table></center></body></html>" + endLine;

            return str;
        }
        /// <summary>
        /// получить как CSV
        /// </summary>
        /// <param name="endLine"></param>
        /// <returns></returns>
        public string GetAsCSV(string endLine = "\r\n")
        {
            // создадим таблицу
            string str = GetCsvTitle(endLine);
            str += GetCsvString(endLine);

            return str;
        }

        #endregion

        #region экспорт данных в различных форматах

        /// <summary>
        /// открыть вариант вбраузере
        /// </summary>
        /// <returns></returns>
        public bool OpenVariant()
        {
            return FileTools.ShowFile(Url);
        }

        /// <summary>
        /// экспорт варианта в Html
        /// </summary>
        /// <param name="path"></param>
        /// <returns></returnsshow
        public bool ExportToHtml(string path,bool show = false)
        {
            // добавим срасширение если надо
            if (FileTools.GetFileExtension(path) == "")
                path += ".html";

            // запишем
            bool bRes = TextFileTools.WriteFile(path, GetAsHtml(),"utf-8");

            // покажем
            if (bRes && show)
                FileTools.ShowFile(path);
            return bRes;
        }

        /// <summary>
        /// экспорт варианта в Excel
        /// </summary>
        /// <param name="path"></param>
        /// <returns></returns>
        public bool ExportToExcel(string path, bool show = false)
        {
            // добавим срасширение если надо
            if (FileTools.GetFileExtension(path) == "")
                path += ".xls";

            // запишем
            bool bRes=TextFileTools.WriteFile(path, GetAsCSV(), "utf-8");

            // покажем
            if (bRes && show)
                FileTools.ShowFile(path);
            return bRes;
        }

        /// <summary>
        /// отправить вариант по почте
        /// </summary>
        /// 
        /// <param name="mailTo"></param>
        /// <returns></returns>
        public bool EMailTo(string mailTo)
        {            
            // поулчить как хтмл
            string html= GetAsHtml("\r\n",3).Replace("<body>", "<body><center><br><br><h3>Получен вариант</h3></center>");
            html = html.Replace("</body>", "<br><br></body>");

            // отправить письмо
            return MailTools.SendHtmlMail(html, "Задача : " + Url, mailTo, Properties.Settings.Default.EMailFrom, Properties.Settings.Default.EmailFromPassword);
        }

        #endregion

        #region заказ звонка

        /// <summary>
        /// поставить заказ звонка в очередь задач на сейчас
        /// </summary>
        /// <returns></returns>
        public bool RequestCallNow(BaseTask task,IScheduler scheduler,bool OnlyNew=false)
        {
            // задачи нет - заказывать нечего
            if (task == null)
                return false;

            // проверим что вариант новеве заадчи
            // если дату не удалось распарсить - PostedDate пустой, и такой
            // вариант отсекался как старый; считаем его новым
            if (OnlyNew && PostedDate != DateTime.MinValue)
            {
                if (PostedDate.Date < task.CreateDate.Date)
                    return false;
            }
            // плохой телефон
            if (GetNormedPhone() == "")
                return false;

            // укажем что начали проверку
            IsRequestCallNow = true;

            // им задачи в шедулере
            shedulerVariantCounter++;
            variantJobRequestCallName = "request call " + Url + shedulerVariantCounter.ToString();

            // создадим задачу из класса TaskJob для выполнения сейчас
            IJobDetail job = JobBuilder.Create<VariantCallThread>()
                .WithIdentity(variantJobRequestCallName, "scheduling")
                .Build();

            // триггер - запустить сейчас
            ITrigger trigger = TriggerBuilder.Create()
                    .WithIdentity("SchedulingTrigger" + Url + shedulerVariantCounter.ToString(), "scheduling")
                    .StartNow()
                    .Build();

            // укажем задачу - как данные работы
            job.JobDataMap.Add("Data#1", task);
            job.JobDataMap.Add("Data#2", this);

            // запустим задачу
            // Quartz 3 вернул асинхронный API, но вызываем мы это из UI-потока,
            // поэтому ждем здесь. ConfigureAwait(false) обязателен - иначе
            // продолжение вернется в контекст UI и будет ждать сам себя
            try
            {
                scheduler.ScheduleJob(job, trigger).ConfigureAwait(false).GetAwaiter().GetResult();
                return true;
            }
            catch (Exception)
            {
                // звонок не встал в очередь - сбрасываем флаг, иначе
                // кнопка звонка останется заблокированной навсегда
                IsRequestCallNow = false;
                return false;
            }
        }

        /// <summary>
        /// проверка того что телефон хороший
        /// </summary>
        /// <returns></returns>
        public bool IsValidPhone()
        {
            if (Phone == null)
                return false;
            if (Phone=="")
                return false;

            return true;
        }
        /// <summary>
        /// получить нормализованный телефон 
        /// </summary>
        /// <returns></returns>
        public string GetNormedPhone()
        {
            // результат
            string phone = "";

            // не валидный телефон
            if (!IsValidPhone())
                return phone;

            // скопируем только цифры            
            for (int i = 0; i<Phone.Length ;i++)
            {
                if (Char.IsDigit(Phone[i]))
                    phone += Phone[i];
            }

            // в телефоне не оказалось ни одной цифры
            if (phone.Length == 0)
                return "";

            // нормализуем - начинается с 0 - значит украина
            if (phone[0] == '0')
                phone = "+38"+ phone;
            // добавим + для россии и украины
            else if (phone[0] == '7' || phone.StartsWith("38"))
                phone = "+"+ phone;

            // если не +7 и +38 - то не звонить
            if (phone.StartsWith("+7") || phone.StartsWith("+38"))
                return phone;

            return "";
        }

        /// надо ли завершить заказ звонка
        /// </summary>
        /// <returns></returns>
        bool IsNeedStopCheck()
        {
            return Main.NeedClose;
        }
        /// <summary>
        /// дождаться запуска эмулятора
        /// </summary>
        /// <returns>false если не запустился или приложение закрывается</returns>
        static bool WaitXHEStarted(XHEScriptMulti script)
        {
            int num = 0;
            while (script.app.get_version(true) == "")
            {
                Thread.Sleep(1000);

                // пользователь закрывает приложение
                if (Main.NeedClose)
                    return false;

                // ожидаем не дольше отведенного времени
                num++;
                if (num >= BaseTask.xheStartWaitSeconds)
                    return false;
            }

            return true;
        }

        /// <summary>
        /// завершение заказ звонка
        /// </summary>
        /// <param name="message"></param>
        string EndRequestCall(string message, XHEScriptMulti script)
        {
            // лог
            if (onVariantRequestCallCheckProgressLog!=null)
                onVariantRequestCallCheckProgressLog.Invoke(this, message);

            // эмулятора не было - закрывать нечего
            if (script == null)
                return message;

            // закроем хуман - он мог не запуститься, и тогда Exit кинет
            try
            {
                script.Exit();
            }
            catch (Exception)
            {
            }

            // вернем варианты
            return message;
        }

        /// <summary>
        /// обзвон варианта
        /// </summary>
        /// <param name="threadNum"></param>
        /// <returns></returns>
        public string RequestCall(int thread)
        {            
            // запустить хуман из заданного пути на заданном порту (по номеру потока)
            int port = XhePorts.Call(thread);
            string path = XhePorts.GetPortExe(port);

            // эмулятора нет - не ждем 30 секунд, а сразу говорим почему
            if (!XhePorts.IsPrepared(port))
                return EndRequestCall("нет эмулятора для порта " + port.ToString()
                    + " (ожидался файл " + path + "), звонок не заказан", null);

            XHEApp xhe = new XHEApp(path, port);

            // XHE задача
            using (XHEScriptMulti script = new XHEScriptMulti("localhost:" + xhe.GetPort().ToString()))
            {
                // ожидаем запуска
                if (!WaitXHEStarted(script))
                    return EndRequestCall("эмулятор не запустился на порту " + port.ToString(), script);

                // скрыть хуманы если надо
                script.app.show_tray_icon(false);
                if (Properties.Settings.Default.ShowVariantCallRequestInXHE)
                    script.app.show_from_tray();
                else
                    script.app.minimize_to_tray();
                script.app.clear();
                script.browser.set_home_page("about:blank");
                script.browser.enable_browser_message_boxes(false);
                script.browser.enable_java_script(true);
                if (onVariantRequestCallCheckProgressLog!=null)
                    onVariantRequestCallCheckProgressLog.Invoke(this, "запущен фоновый браузер");

                // чтоб работало не смотря ни на что
                try
                {
                    // параметры ожидания загрузки страницы
                    script.browser.set_wait_params(10, 3);

                    // перейдем на заданный урл
                    script.browser.navigate(Properties.Settings.Default.CalbackKillerPluginUrl);

                    // введем телефон
                    string phone = GetNormedPhone();
                    if (phone == "")
                        return EndRequestCall("заказать звонок не получилось: телефон " +Phone+" не поддерживается",script);

                    // нашли ли поле для ввода. Раньше здесь был Thread.Sleep(1)
                    // после navigate - поля на странице еще не было, и клик
                    // уходил в пустоту
                    script.anchor.click_by_inner_text("Закажите звонок", false);

                    bool ordered = false;

                    if (script.input.wait_element_exist_by_name("cbkPhoneInput", "cbkPhoneInput"))
                    {
                        script.input.set_value_by_name("cbkPhoneInput", phone);
                        script.btn.click_by_inner_text("Позвоните мне!", false);
                        ordered = true;
                    }
                    else if (script.input.wait_element_exist_by_name("cbkPhoneDeferredInput", "cbkPhoneDeferredInput"))
                    {
                        script.input.set_value_by_name("cbkPhoneDeferredInput", phone);
                        script.btn.click_by_inner_text("Жду звонка!", false);
                        ordered = true;
                    }

                    // поля не нашлись - раньше здесь все равно ставилось
                    // "заказан звонок", и вариант больше нельзя было обзвонить
                    if (!ordered)
                        return EndRequestCall("заказать звонок не получилось: поле ввода телефона не найдено",script);

                    // укажем что звонок заказан
                    Status = "заказан звонок";

                    // результат
                    return EndRequestCall("заказан звонок на телефон " + phone,script);
                }
                catch (Exception ex)
                {
                    // лог
                    if (onVariantRequestCallCheckProgressLog!=null)
                        onVariantRequestCallCheckProgressLog.Invoke(this, "ошибка запроса обратного звонка " + Url + "\n" + ex.ToString());
                }
                return EndRequestCall("ошибка",script);
            }            
        }

        #endregion

        #region проверка варианта

        /// <summary>
        /// поставить проверку варианта в очередь задач на сейчас
        /// </summary>
        /// <returns></returns>
        public bool CheckNow(BaseTask task, IScheduler scheduler)
        {
            // укажем что начали проверку
            IsCheckNow = true;

            // им задачи в шедулере
            shedulerVariantCounter++;
            variantJobCheckName = "request check " + Url + shedulerVariantCounter.ToString();

            // создадим задачу из класса TaskJob для выполнения сейчас
            IJobDetail job = JobBuilder.Create<VariantCheckThread>()
                .WithIdentity(variantJobCheckName, "scheduling")
                .Build();

            // тригер - запустить сейчас
            ITrigger trigger = TriggerBuilder.Create()
                    .WithIdentity("SchedulingTrigger" + Url + shedulerVariantCounter.ToString(), "scheduling")
                    .StartNow()
                    .Build();

            // укажем задачу - как данные работы
            job.JobDataMap.Add("Data#1", task);
            job.JobDataMap.Add("Data#2", this);

            // запустим задачу
            // Quartz 3 вернул асинхронный API, но вызываем мы это из UI-потока,
            // поэтому ждем здесь. ConfigureAwait(false) обязателен - иначе
            // продолжение вернется в контекст UI и будет ждать сам себя
            try
            {
                scheduler.ScheduleJob(job, trigger).ConfigureAwait(false).GetAwaiter().GetResult();
                return true;
            }
            catch (Exception)
            {
                // проверка не встала в очередь - сбрасываем флаг
                IsCheckNow = false;
                return false;
            }
        }

        /// <summary>
        /// завершение заказ звонка
        /// </summary>
        /// <param name="message"></param>
        string EndCheck(string message, XHEScriptMulti script)
        {
            // лог
            if (onVarianCheckProgressLog!=null)
                onVarianCheckProgressLog.Invoke(this, message);

            // эмулятора не было - закрывать нечего
            if (script == null)
                return message;

            // закроем хуман - он мог не запуститься, и тогда Exit кинет
            try
            {
                script.Exit();
            }
            catch (Exception)
            {
            }

            // вернем варианты
            return message;
        }

        /// <summary>
        /// обзвон варианта
        /// </summary>
        /// <param name="threadNum"></param>
        /// <returns></returns>
        public string Check(int thread,BaseTask task)
        {
            // запустить хуман из заданного пути на заданном порту (по номеру потока)
            int port = XhePorts.VariantCheck(thread);
            string path = XhePorts.GetPortExe(port);

            // эмулятора нет - не ждем 30 секунд, а сразу говорим почему
            if (!XhePorts.IsPrepared(port))
                return EndCheck("нет эмулятора для порта " + port.ToString()
                    + " (ожидался файл " + path + "), вариант не проверен", null);

            XHEApp xhe = new XHEApp(path, port);

            // XHE задача
            using (XHEScriptMulti script = new XHEScriptMulti("localhost:" + xhe.GetPort().ToString()))
            {
                // ожидаем запуска
                if (!WaitXHEStarted(script))
                    return EndCheck("эмулятор не запустился на порту " + port.ToString(), script);

                // скрыть хуманы если надо
                script.app.show_tray_icon(false);
                if (Properties.Settings.Default.ShowVariantCallRequestInXHE)
                    script.app.show_from_tray();
                else
                    script.app.minimize_to_tray();
                script.app.clear();
                script.browser.set_home_page("about:blank");
                script.browser.enable_browser_message_boxes(false);
                script.browser.enable_java_script(true);
                if (onVarianCheckProgressLog!=null)
                    onVarianCheckProgressLog.Invoke(this, "запущен фоновый браузер");

                // чтоб работало не смотря ни на что
                try
                {
                    // разобрать вариант
                    task.ParseVariantPhone(this, script);

                    // результат
                    return EndCheck("проверка варианта завершена " + Url, script);
                }
                catch (Exception ex)
                {
                    // лог
                    if (onVarianCheckProgressLog!=null)
                        onVarianCheckProgressLog.Invoke(this, "ошибка запроса обратного звонка " + Url + "\n" + ex.ToString());
                }
                return EndCheck("ошибка",script);
            }
        }

        #endregion

    }
}
