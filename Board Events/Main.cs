using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.IO;
using System.Text;
using System.Windows.Forms;
using Board_Events.Controller;
using Board_Events.Model.Tasks;
using XHE._Helper.Tools.Folder;
using CefSharp;
using CefSharp.WinForms;
using Keywords.GUI.Forms;
using XHE._Helper.Tools.File;
using Quartz;
using Quartz.Impl;
using Board_Events.Model.Results;
using XHE._Helper.Tools.GUI;
using Board_Events.Threads;
using XHE;
using System.Threading;
using System.Threading.Tasks;

namespace Board_Events
{
    /// <summary>
    /// главное окно
    /// </summary>
    public partial class Main : Form
    {
        #region данные

        // фабрика шедулера
        ISchedulerFactory schedulerFact = null;
        // шедулер
        IScheduler scheduler = null;

        /// <summary>
        /// контрллер списка задач
        /// </summary>
        private TasksController tasksController = null;

        /// <summary>
        /// контролер текущей (выбранной) задачи 
        /// </summary>
        private TaskController taskController = null;

        /// <summary>
        /// флаг указывающий что приложенеи закрывается
        /// </summary>
        static public bool NeedClose = false;

        /// <summary>
        /// путь к папке Temp
        /// </summary>
        public string TempPath { get; set; }

        #endregion

        #region вспомогательные данные

        /// <summary>
        /// браузер хрома дял отображения текщей задачи
        /// </summary>
        public ChromiumWebBrowser chromeVariant;

        #endregion

        #region создание

        /// <summary>
        /// задать папки программы
        /// </summary>
        void InitFolders()
        {
            // папки программы
            TempPath = Application.StartupPath + "\\Temp";
            Directory.CreateDirectory(TempPath);
            FolderTools.ClearFolder(TempPath);
            Directory.CreateDirectory("Tasks");
            Directory.CreateDirectory("Logs");
            Directory.CreateDirectory("Results");
        }
        /// <summary>
        /// задать шедулер
        /// </summary>
        void InitScheduler()
        {
            // создадим фабрику шедулера
            schedulerFact = new StdSchedulerFactory();

            // сохдадим и запустим шедулер
            scheduler = schedulerFact.GetScheduler();
            scheduler.Start();
        }
        /// <summary>
        /// инициализировать компоненты (стандартные и не очень)
        /// </summary>
        void InitComponents()
        {
            // запустим и закроем хуман при старте
            if (Properties.Settings.Default.FirstTimesStartted)
                 RunAndCloseXHEByStart(true);

            // компоненты
            InitializeComponent();

            // создадим хромиум для просмотра задачи
            CefSettings settings = new CefSettings();
            Cef.Initialize(settings);
            chromeVariant = new ChromiumWebBrowser("about:blank");
            pnlChromeVariant.Controls.Add(chromeVariant);
            chromeVariant.Dock = DockStyle.Fill;

            // текст главного окна - добавим информацию о версии
            this.Text += " " + Application.ProductVersion;

            // размеры
            if (Properties.Settings.Default.MainSize.Width != 0 && Properties.Settings.Default.MainSize.Height != 0)
                Size = Properties.Settings.Default.MainSize;
            // сплитеры
            //splitTasksToResults.SplitterDistance = Properties.Settings.Default.splitTasksToResultsDistance;
            //splitResultsToResult.SplitterDistance = Properties.Settings.Default.splitResultsToResultDistance;
            //splitTaskResultsToLog.SplitterDistance = Properties.Settings.Default.splitTaskResultsToLogDistance;

            // для сообщений
            TaskCheckThread.tbTaskCheck = tbCheck;
            VariantCallThread.TbOutCall = tbCalls;
            VariantCheckThread.tbVariantCheck= tbCheck;
        }
        // инициадлизировать контроллеры
        void InitControllers()
        {
            // контроллер списка задач
            tasksController = new TasksController(lwTasks, scheduler);
            // обработчик того что список задач поменялся
            tasksController.onTasksUpdated += OnTasksListUpdated;

            // контроллер текущей задачи
            taskController = new TaskController(lwVariants, scheduler);
            // обработчик того что задача поменялась
            taskController.onTaskUpdated += OnCurrentTaskUpdated;
            taskController.onVariantUpdated += OnCurrentVariantUpdated;

            // указать контролер задачи
            tasksController.SetTaskController(taskController);
        }
        // инициализируем заадчи
        void InitTasks()
        {
            // прочитаем задачи с диска                        
            tasksController.DeserializeAllTasks();
            // выберем нулевую задачу
            tasksController.SelectTask(0);

            // запустим все задачи согласно расписанию - если надо
            if (global::Board_Events.Properties.Settings.Default.bAutoStartScheduler)
                tasksController.StartTaskSheduling();            
        }

        /// <summary>
        /// конструктор
        /// </summary>
        public Main()
        {
            // задать папки прогоаммы
            InitFolders();
            // инициализировать компоненты
            InitComponents();

            // задать шедулер
            InitScheduler();

            // инициадлизировать контроллеры
            InitControllers();
            // инициализируем заадчи
            InitTasks();

            // для первого старта - зададим настройки
            if (Properties.Settings.Default.FirstTimesStartted)
            {
                Visible = true;
                ShowMessage.ShowInfoMessage("Для работы программы нужно указать следующите данные :\n\n 1. E-Mail на котрый будут присылаться новые уведомления\n 2. Адрес с плагином CallbackKiller через котрый будут заказываться звонки.");
                tsSettings_Click(null, null);
            }
            // добавим первую задачу
            if (Properties.Settings.Default.FirstTimesStartted && tasksController.GetTaskCount() == 0)
            {
                ShowMessage.ShowInfoMessage("Добавьте первую задачу.\n\nПоддерживаются адреса с досок :\n\nOLX.ua\nRST.ua\nAutoRia.com");
                tsAddTask_Click(null, null);
            }

            // уже не первый старт
            Properties.Settings.Default.FirstTimesStartted = false;
        }

        /// <summary>
        /// закрытие формы
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void Main_FormClosing(object sender, FormClosingEventArgs e)
        {
            // размеры
            Properties.Settings.Default.MainSize= Size;

            // сплитеры
            Properties.Settings.Default.splitTasksToResultsDistance= splitTasksToResults.SplitterDistance;
            Properties.Settings.Default.splitResultsToResultDistance= splitResultsToResult.SplitterDistance;
            Properties.Settings.Default.splitTaskResultsToLogDistance=splitTaskResultsToLog.SplitterDistance;

            // надо закрывать потоки
            NeedClose = true;
        }
        /// <summary>
        /// форма закрылась
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void Main_FormClosed(object sender, FormClosedEventArgs e)
        {
            // для сообщений
            TaskCheckThread.tbTaskCheck = null;
            VariantCallThread.TbOutCall = null;

            // сохраним задачи
            tasksController.SerializeAllTasks();
            // сохраним настройки
            Properties.Settings.Default.Save();

            // завершим шедулер
            scheduler.Shutdown();
        }

        #endregion

        #region приложение

        /// <summary>
        /// выход
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsmExit_Click(object sender, EventArgs e)
        {
            this.Close();
        }

        /// <summary>
        /// диалог настроек
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsSettings_Click(object sender, EventArgs e)
        {
            SettingsForm dlgSettings = new SettingsForm();
            dlgSettings.ShowDialog();
        }

        /// <summary>
        /// онлайн справка
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsOnlineHelp_Click(object sender, EventArgs e)
        {
            FileTools.ShowFile("http://browserautomationstudio/boardevent/");
        }

        /// <summary>
        /// диалог о программе
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsAboutProgram_Click(object sender, EventArgs e)
        {
            SplashForm aboutDlg = new SplashForm("Уведомления о новых событиях в на досках объявлений : " + Application.ProductVersion);
            aboutDlg.ShowDialog();
        }

        #endregion

        #region работа с одной задачей

        /// <summary>
        /// задать новые настройки для текущей задачи
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void btnTaskSetSettings_Click(object sender, EventArgs e)
        {
            // зададим новые настройки
            taskController.EditTask(tbTaskName.Text, tbTaskUrl.Text, cmTimeCheck.Text, chEnableEMailNotification.Checked, chEnableCallNotification.Checked, tasksController);
        }

        /// <summary>
        /// проверка вариантов по текущей задаче
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void btnCheckNow_Click(object sender, EventArgs e)
        {
            tsbCheckCurrentTask.Enabled = true;
            taskController.StartNow(scheduler);
        }

        #endregion

        #region работа со списком задача

        /// <summary>
        /// выбор задачи
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void lwTasks_SelectedIndexChanged(object sender, EventArgs e)
        {
            tasksController.SelectTask();
        }


        /// <summary>
        /// добавить задачу
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsAddTask_Click(object sender, EventArgs e)
        {
            // диалог добавления задачи
            AddTaskDlg addTaskDlg = new AddTaskDlg(tasksController);
            if (addTaskDlg.ShowDialog() == DialogResult.OK)
            {
                // сохраним задачи
                tasksController.SerializeAllTasks();
            }
        }

        /// <summary>
        /// удалить задачу
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsDel_Click(object sender, EventArgs e)
        {
            tasksController.DeleteTask();
        }

        /// <summary>
        /// удалить все задачи
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsmDeleteAllTasks_Click(object sender, EventArgs e)
        {
            tasksController.DeleteAllTask();
        }

        /// <summary>
        /// экспорт всех задач
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsmExportAllTasks_Click(object sender, EventArgs e)
        {
            tasksController.ExportAllTasks();
        }

        /// <summary>
        /// импорт задач из файла
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsmImportAllTasks_Click(object sender, EventArgs e)
        {
            tasksController.ImportAllTasks();
        }

        /// <summary>
        /// проверить все задачи
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsbCheckAllTasks_Click(object sender, EventArgs e)
        {
            tasksController.StartNowAllTasks();
        }

        /// <summary>
        /// запустить или остановить все задачи
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsbStartStopSheduling_Click(object sender, EventArgs e)
        {
            tasksController.StartStopScheduling();
        }

        #endregion

        #region работа с вариантами текущей задачи

        /// <summary>
        /// выбор варианта
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void lwVariants_SelectedIndexChanged(object sender, EventArgs e)
        {
            // выполним
            taskController.SetVariant();
        }

        /// <summary>
        /// зададим описание для текущего результата
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsbDescribtionvariant_Click(object sender, EventArgs e)
        {
            taskController.SetVariantDescription();
        }

        /// <summary>
        /// изменить статус варианта
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsStatusVariant_Click(object sender, EventArgs e)
        {
            // активируем лог 
            tcLogs.SelectedTab = tabMessages;
            // выполним
            taskController.SetVariantStatus(sender.ToString());            
        }

        /// <summary>
        /// пометить выбранный вариант
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsCheckVariant_Click(object sender, EventArgs e)
        {
            // активируем лог 
            tcLogs.SelectedTab = tabMessages;
            // выполним
            taskController.SetVariantIcon(sender.ToString());
        }

        /// <summary>
        /// открыть вариант в браузере
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsbOpenVariant_Click(object sender, EventArgs e)
        {
            // активируем лог 
            tcLogs.SelectedTab = tabMessages;
            // выполним
            taskController.OpenVariant();
        }

        /// <summary>
        /// добавить вариант
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsbAddVariant_Click(object sender, EventArgs e)
        {
            // активируем лог 
            tcLogs.SelectedTab = tabCheck;
            // выполним
            taskController.AddVariant();
        }

        /// <summary>
        /// удалить текущий вариант
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsbDelVariant_Click(object sender, EventArgs e)
        {
            // активируем лог 
            tcLogs.SelectedTab = tabMessages;
            // выполним
            taskController.DeleteVariant();
        }

        /// <summary>
        /// удалить все варианты
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsbDelAllVariant_Click(object sender, EventArgs e)
        {
            // активируем лог 
            tcLogs.SelectedTab = tabMessages;
            // выполним
            taskController.DeleteAllVariants();
        }

        /// <summary>
        /// экспорт вбранного варианта в excel
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsbExportVariant_Click(object sender, EventArgs e)
        {
            // активируем лог 
            tcLogs.SelectedTab = tabMessages;
            // выполним
            taskController.ExportVariant();
        }

        /// <summary>
        /// экспорт всех вариантов в excel
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsbExportAllVariants_Click(object sender, EventArgs e)
        {
            // активируем лог 
            tcLogs.SelectedTab = tabMessages;
            // выполним
            taskController.ExportAllVariants();
        }

        /// <summary>
        /// отправить вариант на почту
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsbEmailVariant_Click(object sender, EventArgs e)
        {
            // проверим настройки
            if (Properties.Settings.Default.EMailTo=="")
            {
                ShowMessage.ShowWarningMessage("Не задано EMail, куда отсылать почтовые уведомления");
                tsSettings_Click(sender, e);
                return;
            }
            // активируем лог 
            tcLogs.SelectedTab = tabMessages;
            // отправим
            taskController.EmailVariant();
        }

        /// <summary>
        /// отправить все враианты на почту
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsbEMailAllVariants_Click(object sender, EventArgs e)
        {
            // проверим настройки
            if (global::Board_Events.Properties.Settings.Default.EMailTo == "")
            {
                ShowMessage.ShowWarningMessage("Не задано EMail, куда отсылать почтовые уведомления");
                tsSettings_Click(sender, e);
                return;
            }
            // активируем лог 
            tcLogs.SelectedTab = tabMessages;
            // отправим
            taskController.EmailAllVariants();                
        }

        /// <summary>
        /// позвонить варианту
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsbCallVariant_Click(object sender, EventArgs e)
        {
            // проверим настройки
            if (Properties.Settings.Default.CalbackKillerPluginUrl == "")
            {
                ShowMessage.ShowWarningMessage("Не задан URL с плагином CallbackKiller, для заказов обратных звонков");
                tsSettings_Click(sender, e);
                return;
            }
            // активируем лог 
            tcLogs.SelectedTab=tabCalls;
            // закажем
            taskController.VariantRequestCall();
        }

        /// <summary>
        /// позвонить всем вариантам
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void tsbCallAllVariants_Click(object sender, EventArgs e)
        {
            // проверим настройки
            if (Properties.Settings.Default.CalbackKillerPluginUrl == "")
            {
                ShowMessage.ShowWarningMessage("Не задан URL с плагином CallbackKiller, для заказов обратных звонков");
                tsSettings_Click(sender, e);
                return;
            }
            // активируем лог 
            tcLogs.SelectedTab = tabCalls;
            // закажем
            taskController.VariantsAllRequestCall();
        }

        /// <summary>
        /// двойной щедчок по списку вариантов
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void lwVariants_MouseDoubleClick(object sender, MouseEventArgs e)
        {
            // строку и столбец
            var senderList = (ListView)sender;
            var clickedItem = senderList.HitTest(e.Location).Item;
            if (clickedItem != null)
            {
                var clickedSubItem = senderList.HitTest(e.Location).SubItem;
                int row = senderList.Items.IndexOf(clickedItem);
                int column = clickedItem.SubItems.IndexOf(clickedSubItem);
                switch (column)
                {
                    case 2:
                        taskController.OpenVariant();
                        break;
                    case 5:
                        taskController.SetVariantDescription();
                        break;
                }
            }
        }


        #endregion                

        #region обработчики делегатов от задач и вариантов

        /// <summary>
        /// список задач был обновлен
        /// </summary>
        public void OnTasksListUpdated()
        {
            // выбрана ли задача
            bool isTaskSelected = tasksController.GetSelectedTaskIndex() != -1;
            // количество задач
            int iTasksCount = tasksController.GetTaskCount();

            // достпность удаления
            tsbTasksDel.Enabled = isTaskSelected;
            cmiTaskDel.Enabled = tsbTasksDel.Enabled;

            // запуск и останов задач по расписанию
            if (tasksController.isTasksSheduled)
                tsbStartStopSheduling.Image = Board_Events.Properties.Resources.IDI_ICON_OP_STOPWATCH_STOP;
            else
                tsbStartStopSheduling.Image = Board_Events.Properties.Resources.IDI_ICON_OP_STOPWATCH_RUN;
            tsbStartStopSheduling.Enabled = iTasksCount > 0;

            // задание новых свойств задачи
            btnTaskSetSettings.Enabled = isTaskSelected;
            tsbCheckAllTasks.Enabled = iTasksCount > 0;
            tsmDeleteAllTasks.Enabled = iTasksCount > 0;
            tsmExportAllTasks.Enabled = iTasksCount > 0;
        }
        /// <summary>
        /// текущая задача был обновлена
        /// </summary>
        public void OnCurrentTaskUpdated(BaseTask task)
        {            
            if (task != null) // есть текущая задача
            {
                tsbCheckCurrentTask.Enabled = !task.IsCheckNow;

                lblTaskName.Text = task.Name;
                tbTaskName.Text = task.Name;
                tbTaskUrl.Text = task.Url;
                llbTaskAddress.Text = task.Url;
                cmTimeCheck.Text = task.TimeCheck;
                lblTotalCheckCount.Text = task.CheckCount.ToString();
                lblCreateDate.Text = "Дата создания : " + task.CreateDate.ToString();
                if (task.CheckCount != 0)
                    lblLastCheckDate.Text = "Дата последней проверки : " + task.LastCheckDate.ToString();
                else
                    lblLastCheckDate.Text = "Дата последней проверки : еще не проверяли";
                lblTotalCheckCount.Text = "Общее число проверок : " + task.CheckCount.ToString();
                chEnableEMailNotification.Checked = task.EnableMailNotification;
                chEnableCallNotification.Checked = task.EnableCallNotification;
            }
            else // задача не выбрана
            {
                tsbCheckCurrentTask.Enabled = false;                
                                          
                tbTaskName.Text = "N/A";
                tbTaskUrl.Text = "N/A";
                llbTaskAddress.Text = "N/A";
                cmTimeCheck.Text = "";                
                lblLastCheckDate.Text = "Дата последней проверки : N/A";
                lblTotalCheckCount.Text = "Общее число проверок : N/A";
                lblCreateDate.Text = "Дата создания : N/A";
                chEnableEMailNotification.Checked = false;
                chEnableCallNotification.Checked = false;

                lwVariants.Items.Clear();                
            }

            tsiAddVariant.Enabled=tsbAddVariant.Enabled = task!=null;

            // число вариантов
            int variantsCount = 0;
            if (taskController.Task!=null)
                variantsCount = taskController.Task.GetVariantsCount();
            tsiDelAllVariants.Enabled=tsbDelAllVariant.Enabled = variantsCount > 0;
            tsiExportAllVariants.Enabled=tsbExportAllVariants.Enabled = variantsCount > 0;
            tsbEMailAllVariants.Enabled = variantsCount > 0;
            tsbCallAllVariants.Enabled = variantsCount > 0;
        }
        /// <summary>
        /// задать урл варианта во встроеный браузер
        /// </summary>
        /// <param name="url"></param>
        string prev_url = "about:blank";
        void LoadVariantUrl(string url)
        {
            if (url == "about:blank")
            {
                if (prev_url == "about:blank")
                {
                    chromeVariant.Visible = true;
                    wbIE.Visible = false;
                    chromeVariant.Load("about:blank");
                }
            }
            else
            {
                if (url != chromeVariant.Address)
                {
                    if (url.IndexOf("olx.ua") != -1)
                    {
                        chromeVariant.Visible = false;
                        wbIE.Visible = true;
                        wbIE.Navigate(url);
                    }
                    else
                    {
                        wbIE.Visible = false;
                        chromeVariant.Visible = true;
                        if (url != chromeVariant.Address)
                            chromeVariant.Load(url);
                    }
                }
            }
            prev_url = url;
        }
        /// <summary>
        /// текущий вариант был обновлен
        /// </summary>
        public void OnCurrentVariantUpdated(TaskVariant variant)
        {
            // выбранный вариант            
            tsiCheckVariant.Enabled = tsbCheckVariant.Enabled = variant != null;
            tsiStatusVariant.Enabled=tsbStatusVariant.Enabled = variant != null;
            tsiVariantDescription.Enabled=tsbDescribtionVariant.Enabled = variant != null;
            tsiOpenVariant.Enabled=tsbOpenVariant.Enabled = variant != null;
            tsbCheckVariant.Enabled = variant != null;
            tsiDelVariant.Enabled=tsbDelVariant.Enabled = variant != null;
            tsiExportVariant.Enabled=tsbExportVariant.Enabled = variant != null;
            tsbEmailVariant.Enabled = variant != null;
            tsbCallVariant.Enabled = variant != null && variant.IsValidPhone() && !variant.IsRequestCallNow && variant.Status!="заказан звонок";

            // загрузим вариант в браузер
            if (variant == null)
                LoadVariantUrl("about:blank");
            else
            {
                /*if (variant.Url.IndexOf("olx.ua") != -1)
                    LoadVariantUrl("https://whoer.net/");
                else*/
                LoadVariantUrl(variant.Url);
            }
        }

        #endregion

        #region сервсиные

        /// <summary>
        /// ожидание
        /// </summary>
        static public void Sleep(int milliseconds)
        {
            for (int i = 0; i < (milliseconds / 1000); i++)
            {
                Thread.Sleep(1000);
                if (Main.NeedClose)
                    break;
            }
        }

        // запустить и закрыть хуман эумлятор при старте (ввод кода активации)
        void RunAndCloseXHEByStart(bool first)
        {
            // запустить хуман из заданного пути на заданном порту (по номеру потока)
            if (first)            
            {
                int port = 11000;
                string path = Application.StartupPath + "\\XHE\\" + port.ToString() + "\\" + port.ToString() + ".exe";
                XHEApp xhe = new XHEApp(path, port);

                // XHE задача
                using (XHEScriptMulti script = new XHEScriptMulti("localhost:" + port.ToString()))
                {
                    // ожидаем запуска                      
                    while (script.app.get_version(true) == "")
                        Thread.Sleep(1000);

                    // закрыть
                    script.Exit();
                }
                return;
            }

            for (int i = 0; i < 10; i++)
            {
                // запустить хуман из заданного пути на заданном порту (по номеру потока)
                int portCheck = 11000+i;                
                string pathCheck = Application.StartupPath + "\\XHE\\" + portCheck.ToString() + "\\" + portCheck.ToString() + ".exe";
                XHEApp xheCheck = new XHEApp(pathCheck, portCheck);
                // XHE задача
                using (XHEScriptMulti script = new XHEScriptMulti("localhost:" + portCheck.ToString()))
                {
                    // ожидаем запуска                
                    while (script.app.get_version(true) == "")
                        Thread.Sleep(1000);

                    // закрыть
                    script.Exit();
                }
            }

            for (int i = 0; i < 2; i++)
            {
                // запустить хуман из заданного пути на заданном порту (по номеру потока)
                int portCheck = 12000 + i;
                string pathCheck = Application.StartupPath + "\\XHE\\" + portCheck.ToString() + "\\" + portCheck.ToString() + ".exe";
                XHEApp xheCheck = new XHEApp(pathCheck, portCheck);
                // XHE задача
                using (XHEScriptMulti script = new XHEScriptMulti("localhost:" + portCheck.ToString()))
                {
                    // ожидаем запуска                
                    while (script.app.get_version(true) == "")
                        Thread.Sleep(1000);

                    // закрыть
                    script.Exit();
                }
            }            
        }

        #endregion
    }
}
