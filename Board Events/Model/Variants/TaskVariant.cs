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
        public bool IsRequestCallNow = false;
        // указывает что начата проверка варианта
        public bool IsCheckNow = false;

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
                res = res + "<"+div+ " class>" + s + "</"+div+">";

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
            foreach (string s in GetContents())
            {
                string tmp = s;
                if (s == Url)
                    tmp = "<a href=" + Url + ">" + Url + "</a>";
                else if (s == Phone)
                    tmp = "<a href=\"tel:" + Phone + "\">" + Phone + "</a>";
                else
                    tmp = s;

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
                res = res + "\"" + s + "\";";

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
                    if (tmp == Url)
                        tmp = "<a href=" + Url + ">" + Url + "</a>";
                    else if (tmp == Phone)
                    {
                        string phoneUrl = Phone.Replace(" ", "");
                        phoneUrl = Phone.Replace("-", "");
                        phoneUrl = Phone.Replace("(", "");
                        phoneUrl = Phone.Replace(")", "");
                        tmp = "<a href=\"tel:" + phoneUrl + "\">" + Phone + "</a>";
                    }

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
            // проверим что вариант новеве заадчи
            if (OnlyNew)
            {
                if (PostedDate < task.CreateDate)
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

            // тригер - запустить сейчас
            ITrigger trigger = TriggerBuilder.Create()
                    .WithIdentity("SchedulingTrigger" + Url + shedulerVariantCounter.ToString(), "scheduling")
                    .StartNow()
                    .Build();

            // укажем задачу - как данные работы
            IDictionary<string, object> data = new Dictionary<string, object>();
            job.JobDataMap.Add("Data#1", task);
            job.JobDataMap.Add("Data#2", this);

            // запустим задачу
            try
            {
                scheduler.ScheduleJob(job, trigger);
            }
            catch (Exception)
            {
            }
            return true;
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

            // нормализуем - начинается с 0 - значит украина
            if (phone[0] == '0')
                phone = "+38"+ phone;
            // добавим + для россии и украины
            else if (phone[0] == '7' || phone.Substring(0, 2) == "38")
                phone = "+"+ phone;
            if (phone[0] == '+')
            {
                if (phone.Substring(0,2)=="+7" || phone.Substring(0, 3) == "+38")
                    return phone;
            }

            // если не +7 и +38 - то не звонить
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
        /// завершение заказ звонка
        /// </summary>
        /// <param name="message"></param>
        string EndRequestCall(string message, XHEScriptMulti script)
        {
            // лог
            if (onVariantRequestCallCheckProgressLog!=null)
                onVariantRequestCallCheckProgressLog.Invoke(this, message);
            // закроем хуман
            script.Exit();

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
            int port = 12000 + thread * 10;
            string path = Application.StartupPath + "\\XHE\\" + port.ToString() + "\\" + port.ToString() + ".exe";
            XHEApp xhe = new XHEApp(path, port);

            // XHE задача
            using (XHEScriptMulti script = new XHEScriptMulti("localhost:" + xhe.GetPort().ToString()))
            {
                // ожидаем запуска
                int num = 0;
                while (script.app.get_version(true) == "")
                {
                    Thread.Sleep(1000);

                    // ожидаем 10 секунд
                    num++;
                    if (num > 10)
                        break;

                    // если надо закрыть
                    if (IsNeedStopCheck())
                        return EndRequestCall("прерван", script);
                }

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
                    // перейдем на заданный урл
                    script.browser.navigate(Properties.Settings.Default.CalbackKillerPluginUrl);
                    Thread.Sleep(1);

                    // введем телефон
                    string phone = GetNormedPhone();
                    if (phone != "")
                    {
                        script.anchor.click_by_inner_text("Закажите звонок", false);
                        if (script.input.get_x_by_name("cbkPhoneInput") > 0)
                        {
                            script.input.set_value_by_name("cbkPhoneInput", phone);
                            script.btn.click_by_inner_text("Позвоните мне!", false);  
                        }
                        else if (script.input.get_x_by_name("cbkPhoneDeferredInput") > 0)
                        {
                            script.input.set_value_by_name("cbkPhoneDeferredInput", phone);
                            script.btn.click_by_inner_text("Жду звонка!", false);  
                        }

                        // уажем что звонок заказан
                        Status = "заказан звонок";

                        // результат
                        return EndRequestCall("заказан звонок на телефон " + phone,script);
                    }
                    else
                        return EndRequestCall("заказать звонок не получилось: телефон " +Phone+" не поддерживается",script);
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
            IDictionary<string, object> data = new Dictionary<string, object>();
            job.JobDataMap.Add("Data#1", task);
            job.JobDataMap.Add("Data#2", this);

            // запустим задачу
            try
            {
                scheduler.ScheduleJob(job, trigger);
            }
            catch (Exception)
            {
            }
            return true;
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
            // закроем хуман
            script.Exit();

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
            int port = 13000 + thread * 10;
            string path = Application.StartupPath + "\\XHE\\" + port.ToString() + "\\" + port.ToString() + ".exe";
            XHEApp xhe = new XHEApp(path, port);

            // XHE задача
            using (XHEScriptMulti script = new XHEScriptMulti("localhost:" + xhe.GetPort().ToString()))
            {
                // ожидаем запуска
                int num = 0;
                while (script.app.get_version(true) == "")
                {
                    Thread.Sleep(1000);

                    // ожидаем 10 секунд
                    num++;
                    if (num > 10)
                        break;

                    // если надо закрыть
                    if (IsNeedStopCheck())
                        return EndCheck("прерван", script);
                }

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
