using System;
using System.Collections.Generic;
using System.Linq;
using Board_Events.Model.Tasks;
using Board_Events.Model.Results;
using XHE._Helper.Tools.File;
using XHE;
using System.Windows.Forms;
using Quartz;
using System.Threading;
using XHE._Helper.Tools.GUI;
using XHE._Helper.Tools.Web;

namespace Board_Events
{
    /// <summary>
    /// базовая задача
    /// </summary>
    public class BaseTask
    {
        #region делегаты

        /// <summary>
        /// делегат события - задача была обновлена
        /// </summary>
        /// <param name="task">задача</param>        
        public delegate void UpdatedTaskEvent(BaseTask task);
        public event UpdatedTaskEvent onTaskUpdated = null;

        /// <summary>
        /// делегат логирования - проверки задачи
        /// </summary>
        /// <param name="task">задача</param>        
        public delegate void TaskCheckProgressEvent(BaseTask task,string message);
        public event TaskCheckProgressEvent onTaskCheckProgressLog = null;

        #endregion

        #region данные задачи

        /// <summary>
        /// тип задачи
        /// </summary>        
        public string Type { get; set; }

        /// <summary>
        /// имя задачи
        /// </summary>        
        public string Name { get; set; }

        /// <summary>
        /// проверяемый урл
        /// </summary>        
        public string Url { get; set; }

        /// <summary>
        /// время проверки
        /// </summary>        
        public string TimeCheck { get; set; }

        /// <summary>
        /// дата создания
        /// </summary>        
        public DateTime CreateDate { get; set; }

        /// <summary>
        /// результаты выполнения задачи
        /// </summary>
        public List<TaskVariant> Variants { get; set; }

        #endregion

        #region данные проверок

        /// <summary>
        /// число проверок
        /// </summary>
        public int CheckCount { get; set; }

        /// <summary>
        /// дата последней проверки
        /// </summary>
        public DateTime LastCheckDate { get; set; }

        /// <summary>
        /// разрешить уведомление по емайл о новых вариантах
        /// </summary>        
        public bool EnableMailNotification { get; set; }

        /// <summary>
        /// разрешить уведомление по телефону о новых вариантах
        /// </summary>        
        public bool EnableCallNotification { get; set; }

        /// <summary>
        /// проверяется ли сейчас задача
        /// </summary>
        public bool IsCheckNow { get; set; }

        #endregion

        #region вспомогательные данные

        // имя пункта распиания, связанного с задачей
        string taskJobName = "";
        // идентификатор задачи в шедулере
        static UInt64 shedulerTaskCounter = 0;

        #endregion

        #region вспомогательное

        /// <summary>
        /// получить тип по урлу задачи
        /// </summary>
        /// <param name="url"></param>
        /// <returns></returns>
        public static string GetTypeByUrl(string url)
        {
            // в зависимости от урла
            if (url.IndexOf("auto.ria.com") != -1 || url.IndexOf("autoria.com")!=-1)
                return "autoria.com";
            else if (url.IndexOf("olx.ua") != -1 || url.IndexOf("olx.com") != -1)
                return "olx.ua";
            else if (url.IndexOf("rst.ua") != -1)
                return "rst.ua";

            // не известно что
            return "unknown";
        }

        #endregion

        #region создание

        /// <summary>
        /// конструктор - для сериализации JSON
        /// </summary>
        public BaseTask()
        {
        }

        /// <summary>
        /// конструктор
        /// </summary>
        /// <param name="url">урл задачи</param>
        /// <param name="name">имя задачи</param>
        /// <param name="time_check">период проверки вариантов</param>        
        protected BaseTask(string url, string name, string time_check, UpdatedTaskEvent onTaskUpdated)
        {
            // урл задачи
            Url = url;
            // имя задачи
            Name = name;
            // время проверки
            TimeCheck = time_check;

            // дата создания
            CreateDate = DateTime.Now; // сейчас

            // дата проверки
            LastCheckDate = new DateTime(0); // не проверяли
            // количество проверок
            CheckCount = 0;
            // разрешать уведомление по е-майл
            EnableMailNotification = true;
            // разрешать уведомление по звонку
            EnableCallNotification = false;

            // спсико результатов
            Variants = new List<TaskVariant>();

            // делегаты
            this.onTaskUpdated = onTaskUpdated;
        }

        /// <summary>
        /// задать данные задачи
        /// </summary>
        /// <param name="url"></param>
        /// <param name="name"></param>
        /// <param name="time_check"></param>
        public void SetTaskDatas(string url, string name, string time_check, bool enableMailNotification, bool enableCallNotification)
        {
            // изменим задачу
            Name = name;
            Url = url;
            TimeCheck = time_check;
            EnableMailNotification = enableMailNotification;
            EnableCallNotification = enableCallNotification;

            // событие - задача изменилас ь
            OnTaskUpdated();
        }

        #endregion

        #region проверка вариантов

        /// <summary>
        /// запустить задачу на выполнение сейчас
        /// </summary>
        /// <returns></returns>
        public bool StartNow(IScheduler scheduler)
        {
            // проверить заадчу сейчас
            return StartScheduling(scheduler, true);
        }

        /// <summary>
        /// запустить задачу на выполнение сейчас
        /// </summary>
        /// <returns></returns>
        public bool StartScheduling(IScheduler scheduler,bool isNow=false)
        {
            // им задачи в шедулере
            shedulerTaskCounter++;
            taskJobName= "check task " + Name + shedulerTaskCounter.ToString();

            // создадим задачу из класса TaskJob для выполнения сейчас
            IJobDetail job = JobBuilder.Create<TaskCheckThread>()
                .WithIdentity(taskJobName, "scheduling")
                .Build();

            // укажем что начали проверку
            IsCheckNow = true;
            OnTaskUpdated();

            // создадим тригер
            ITrigger trigger=null;
            if (isNow)
            {
                // тригер - запустить сейчас
                trigger = TriggerBuilder.Create()
                    .WithIdentity("SchedulingTrigger" + Name + shedulerTaskCounter.ToString(), "scheduling")
                    .StartNow()
                    .Build();
            }
            else
            {
                // распиание задач в часах
                int hoursInterval = -1;
                if (TimeCheck == "раз в час")
                    hoursInterval = 1;
                else if (TimeCheck == "раз в 2 часа")
                    hoursInterval = 2;
                else if (TimeCheck == "раз в 3 часа")
                    hoursInterval = 3;
                else if (TimeCheck == "раз в 4 часа")
                    hoursInterval = 4;
                else if (TimeCheck == "раз в 5 часов")
                    hoursInterval = 5;
                else if (TimeCheck == "раз 10 часов")
                    hoursInterval = 10;
                else if (TimeCheck == "раз в 12 часов")
                    hoursInterval = 12;
                else if (TimeCheck == "раз в сутки")
                    hoursInterval = 24;
                else if (TimeCheck == "раз в неделю")
                    hoursInterval = 168;
                // интервал в часах
                if (hoursInterval > 0)
                {
                    // тригер - запустить с заданным часовым интервалом
                    trigger = TriggerBuilder.Create()
                        .WithIdentity("SchedulingTrigger" + Name + shedulerTaskCounter.ToString(), "scheduling")
                        .WithSimpleSchedule(x => x
                            .WithIntervalInHours(hoursInterval)
                            .RepeatForever())
                        .Build();
                }
                
                int minuteInterval = -1;
                if (TimeCheck == "раз в минуту")
                    minuteInterval = 1;
                else if (TimeCheck == "раз в 3 минуты")
                    minuteInterval = 3;
                else if (TimeCheck == "раз в 5 минут")
                    minuteInterval = 5;
                else if (TimeCheck == "раз в 10 минут")
                    minuteInterval = 10;
                else if (TimeCheck == "раз в 15 минут")
                    minuteInterval = 15;
                else if (TimeCheck == "раз в 20 минут")
                    minuteInterval = 20;
                else if (TimeCheck == "раз в 30 минут")
                    minuteInterval = 30;
                // интервал в минутах
                if (minuteInterval > 0)
                {
                    // тригер - запустить с заданным часовым интервалом
                    trigger = TriggerBuilder.Create()
                        .WithIdentity("SchedulingTrigger" + Name + shedulerTaskCounter.ToString(), "scheduling")
                        .WithSimpleSchedule(x => x
                            .WithIntervalInMinutes(minuteInterval)
                            .RepeatForever())
                        .Build();
                }
            }

            // укажем задачу - как данные работы
            IDictionary<string, object> data = new Dictionary<string, object>();
            job.JobDataMap.Add("Data#1", this);

            // запустим задачу
            try
            {
                if (trigger != null)
                    scheduler.ScheduleJob(job, trigger);                
            }
            catch (Exception)
            {
            }
            return true;
        }
        /// <summary>
        /// убрать задачу изшедулера
        /// </summary>
        /// <param name="scheduler"></param>
        /// <returns></returns>
        public bool StopScheduling(IScheduler scheduler)
        {
            // укажем что задача остановлена
            taskJobName = "";
            return scheduler.DeleteJob(new JobKey(taskJobName, "scheduling"));            
        }

        /// <summary>
        /// проверить - запущена ли задача в шедулинг
        /// </summary>
        /// <returns></returns>
        public bool IsScheduling()
        {
            return taskJobName != "";
        }

        /// <summary>
        /// надо ли завершить проверку
        /// </summary>
        /// <returns></returns>
        bool IsNeedStopCheck()
        {
            return Main.NeedClose;
        }
        /// <summary>
        /// завершение задачи
        /// </summary>
        /// <param name="message"></param>
        List<TaskVariant> EndCheck(string message, XHEScriptMulti script, List<TaskVariant> res)
        {
            // лог
            if (onTaskCheckProgressLog!=null)
                onTaskCheckProgressLog.Invoke(this, message);
            // закроем хуман
            script.Exit();

            // вернем варианты
            return res;
        }

        /// <summary>
        /// проверить варианты
        /// </summary>
        /// <returns></returns>
        public virtual List<TaskVariant> Check(int thread)
        {
            // результат
            List<TaskVariant> newVariants = null;

            // еще 1 проверка
            CheckCount++;
            // дата проверки
            LastCheckDate= DateTime.Now; // прверяем сейчас

            // запустить хуман из заданного пути на заданном порту (по номеру потока)
            int port = 11000 + thread*10;
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
                        return EndCheck("задача прервана", script, newVariants);
                }

                // скрыть хуманы если надо
                script.app.show_tray_icon(false);
                if (Properties.Settings.Default.bShowTaskCheckingInXHE)
                    script.app.show_from_tray();
                else
                    script.app.minimize_to_tray();
                script.app.clear();
                script.browser.set_home_page("about:blank");
                script.browser.enable_browser_message_boxes(false);
                script.browser.enable_java_script(true);
                if (onTaskCheckProgressLog!=null)
                    onTaskCheckProgressLog.Invoke(this,"запущен фоновый браузер");

                // чтоб работало не смотря ни на что
                try
                {
                    // если надо закрыть
                    if (IsNeedStopCheck())
                        return EndCheck("задача прервана", script, newVariants);

                    // получим результаты                
                    newVariants = ParseVariants(script);                    

                    // лог
                    string message = "получено новых вариантов : " + newVariants.Count().ToString();
                    if (newVariants.Count() > 0)
                        message += ", начинаем их обработку ....";
                    else
                        message += ".";
                    if (onTaskCheckProgressLog!=null)
                        onTaskCheckProgressLog.Invoke(this, message);

                    // получим телефоны по результатам
                    for (int i= newVariants.Count()-1; i>=0 ; i--)
                    {
                        // обрабатываемы вариант
                        TaskVariant variant = newVariants[i];

                        // если надо закрыть
                        if (IsNeedStopCheck())
                            return EndCheck("задача прервана", script, newVariants);

                        // лог
                        if (onTaskCheckProgressLog!=null)
                            onTaskCheckProgressLog.Invoke(this, "обрабатываем вариант "+ variant.Url+" ...");

                        // обработаем варианты
                        try
                        {
                            // обработка
                            ParseVariantPhone(variant, script);

                            // лог
                            if (onTaskCheckProgressLog!=null)
                                onTaskCheckProgressLog.Invoke(this, "обработан вариант " + variant.Url + " [" + (i + 1).ToString() + " из" + newVariants.Count().ToString() + "]");
                        }
                        catch (Exception ex)
                        {
                            // лог
                            if (onTaskCheckProgressLog!=null)
                                onTaskCheckProgressLog.Invoke(this, "ошибка при разборе варианта " + variant.Url + "\n" + ex.ToString());
                        }

                        // добавим вариант
                        if (!AddVariant(variant, false, Properties.Settings.Default.AddOnlyNewVariants))
                            newVariants.RemoveAt(i);
                    }
                }
                catch (Exception ex)
                {
                    // лог
                    if (onTaskCheckProgressLog!=null)
                        onTaskCheckProgressLog.Invoke(this, "ошибка при разборе задачи " + Url + "\n" + ex.ToString());
                }

                // завершим задачу
                EndCheck("задача завершена, добавлено вариантов :"+ newVariants.Count.ToString(), script, newVariants);
            }

            return newVariants;
        }

        /// <summary>
        /// разобрать и получить варианты из страницы задачи
        /// </summary>
        /// <param name="content"></param>
        /// <returns></returns>
        public virtual List<TaskVariant> ParseVariants(XHEScriptMulti script)
        {
            // новые вараинты
            List<TaskVariant> newVariants = new List<TaskVariant>();
            ShowMessage.ShowInfoMessage("для правильной работы надо переопределить в дочернем классе");
            return newVariants;
        }
        // разобрать телефон варианта
        public virtual bool ParseVariantPhone(TaskVariant variant, XHEScriptMulti script)
        {
            ShowMessage.ShowInfoMessage("для правильной работы надо переопределить в дочернем классе");
            return false;
        }

        #endregion

        #region заказ звонков

        /// <summary>
        /// заказ звонков дял всех враинтов
        /// </summary>
        /// <param name="scheduler"></param>
        public int VariantsAllRequestCallNow(IScheduler scheduler)
        {
            return RequestCallByVariants(Variants, scheduler);
        }

        /// <summary>
        /// заказ звонков дял списка враиантов
        /// </summary>
        public int RequestCallByVariants(List<TaskVariant> variants,IScheduler scheduler,bool OnlyNew=false)
        {
            // результат
            int res = 0;

            // не вариантов
            if (variants == null)
                return res;

            // получим строки всех вариантов
            for (int i = 0; i < variants.Count; i++)
            {
                if (variants[i].RequestCallNow(this, scheduler, OnlyNew))
                    res++;
            }

            // число заказанных результатов
            return res;
        }

        #endregion

        #region работа с вариантами

        /// <summary>
        /// получить количество вариантов
        /// </summary>        
        /// <returns></returns>
        public int GetVariantsCount()
        {
            // нашли
            return Variants.Count;
        }

        /// <summary>
        /// получить вариант с заданным индексом
        /// </summary>
        /// <param name="index"></param>
        /// <returns></returns>
        public TaskVariant GetVariant(int index)
        {
            // не нашли
            if (index >= Variants.Count)
                return null;

            // нашли
            return Variants[index];
        }

        /// <summary>
        /// получить вариант с заданным урлом
        /// </summary>
        /// <param name="index"></param>
        /// <returns></returns>
        public TaskVariant GetVariant(string url)
        {
            // сделаем поиск
            for (int i = 0; i < Variants.Count; i++)
                if (Variants[i].Url == url)
                    return Variants[i];

            return null;
        }

        /// <summary>
        /// создадим вариант
        /// </summary>
        /// <returns></returns>
        public TaskVariant CreateVariant(string url)
        {
            // проверим что такой есть
            if (GetVariant(url) != null)
                throw new Exception("такой вариант уже есть");

            // вариант не совпадает
            if (GetTypeByUrl(url) != Type)
                throw new Exception("адрес варианта не подходит для этой задачи");

            // создадаим
            TaskVariant result = new TaskVariant(url);
            return result;
        }

        /// <summary>
        /// добавить результат
        /// </summary>
        /// <param name="result_url">урл результата</param>
        /// <returns></returns>
        public TaskVariant AddVariant(string url,bool notifyDelegete)
        {
            // создадим вариант
            TaskVariant variant = CreateVariant(url);
            if (variant == null)
                return null;

            // добавим            
            Variants.Add(variant);

            // задача изменилась
            if (notifyDelegete)
                OnTaskUpdated();

            return variant;
        }
        /// <summary>
        /// добавить результат
        /// </summary>
        /// <param name="result_url">урл результата</param>
        /// <returns></returns>
        public bool AddVariant(TaskVariant variant, bool notifyDelegete,bool OnlyNew)
        {
            // создадим вариант
            if (variant == null)
                return false;

            // проверим что вариант новеве заадчи
            if (OnlyNew)
            {
                if (variant.PostedDate.Date < CreateDate.Date)
                    return false;
            }

            // добавим            
            Variants.Add(variant);

            // задача изменилась
            if (notifyDelegete)
                OnTaskUpdated();

            return true;
        }

        /// <summary>
        /// удалить вариант
        /// </summary>
        /// <param name="variant"></param>
        /// <returns></returns>
        public bool DeleteVariant(TaskVariant variant)
        {
            // проверка
            if (variant == null)
                return false;

            // удалим
            Variants.Remove(variant);

            // задача изменилась
            OnTaskUpdated();

            return true;
        }

        /// <summary>
        /// удалить все враианты
        /// </summary>
        /// <returns></returns>
        public bool DeleteAllVariants()
        {
            // нет вариантов
            if (GetVariantsCount() == 0)
                return false;

            // удалим все
            Variants.Clear();

            // задача изменилась
            OnTaskUpdated();

            return true;
        }

        /// <summary>
        /// задать описание варианта
        /// </summary>
        /// <param name="variant"></param>
        /// <param name="description"></param>
        /// <returns></returns>
        public bool SetVariantDescription(TaskVariant variant, string description)
        {
            // проверка
            if (variant == null)
                return false;

            // зададим описание
            variant.Description = description;

            // задача изменилась
            OnTaskUpdated();

            return true;
        }

        /// <summary>
        /// задать статус варианта
        /// </summary>
        /// <param name="variant"></param>
        /// <param name="status"></param>
        /// <returns></returns>
        public bool SetVariantStatus(TaskVariant variant, string status)
        {
            // проверка
            if (variant == null)
                return false;

            // зададим статус
            variant.Status = status;

            // задача изменилась
            OnTaskUpdated();

            return true;
        }

        /// <summary>
        /// задать иконку варианта
        /// </summary>
        /// <param name="variant"></param>
        /// <param name="icon"></param>
        /// <returns></returns>
        public bool SetVariantIcon(TaskVariant variant, string icon)
        {
            // проверка
            if (variant == null)
                return false;

            // зададим статус
            variant.IconIndex = -1;
            if (icon == "Метка 1")
                variant.IconIndex = 0;
            else if (icon == "Метка 2")
                variant.IconIndex = 1;
            else if (icon == "Метка 3")
                variant.IconIndex = 2;

            // задача изменилась
            OnTaskUpdated();

            return true;
        }

        /// <summary>
        /// экспорт всех вариантов в Exel
        /// </summary>
        /// <param name="path"></param>
        /// <param name="show"></param>
        /// <returns></returns>
        public bool ExportAllVariansToExcel(string path,bool show)
        {
            // нет вариантов
            if (Variants.Count == 0)
                return false;

            // разделитель строк
            string separator = "\r\n";

            // создадим таблицу
            string str= Variants[0].GetCsvTitle() + separator;
            // получим строки всех вариантов
            for (int i = 0; i < Variants.Count; i++)
                str += Variants[i].GetCsvString()+separator;

            // добавим срасширение если надо
            if (FileTools.GetFileExtension(path) == "")
                path += ".xls";

            // запишем
            bool bRes = TextFileTools.WriteFile(path, str, "utf-8");
            // покажем
            if (bRes && show)
                FileTools.ShowFile(path);
            return bRes;            
        }

        /// <summary>
        /// отправить все варианты по почте
        /// </summary>
        /// <param name="to"></param>
        /// <returns></returns>
        public bool EMailAllVariantsTo(string mailTo)
        {
            return EMailVariantsTo(Variants, "Все варианты по Задаче : " + Name, mailTo);
        }

        /// <summary>
        /// отправить массив вариантов по почте
        /// </summary>
        /// <param name="to"></param>
        /// <returns></returns>
        public bool EMailVariantsTo(List<TaskVariant> variants,string subject,string mailTo)
        {
            // нет вариантов
            if (variants.Count == 0)
                return false;

            // разделитель строк
            string endLine = "\r\n";

            // создадим таблицу
            string html = "<html><body><center><table border=1 cellpadding=10> " + endLine;
            html += variants[0].GetHtmlTitle(endLine, true);
            // получим строки всех вариантов
            for (int i = 0; i < variants.Count; i++)
                html += variants[i].GetHtmlString(endLine);
            html += "</table></center></body></html>" + endLine;

            //TextFileTools.WriteFile("c:\\1\\1.html",html);
            //FileTools.ShowFile("c:\\1\\1.html");
            //return false;

            // отправить письмо
            return MailTools.SendHtmlMail(html, subject, mailTo, Properties.Settings.Default.EMailFrom, Properties.Settings.Default.EmailFromPassword);
        }

        #endregion

        #region события задачи

        /// <summary>
        /// уажем что задача изменилась
        /// </summary>
        public void OnTaskUpdated()
        {
            // вызовем обработчик делегата                        
            if (onTaskUpdated!=null)
                onTaskUpdated.Invoke(this);
        }

        #endregion
    }
}
