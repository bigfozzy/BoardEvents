using Board_Events.Model.Results;
using Board_Events.Threads;
using Quartz;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace Board_Events.Model.Tasks
{
    /// <summary>
    /// выполнение проверки задачи в потоке + шедулинг
    /// </summary>
    public class TaskCheckThread : BaseThreadWithXHE, IJob  
    {
        #region статические данные

        /// <summary>
        /// для сообщений о проверке
        /// </summary>
        public static TextBox tbTaskCheck = null;

        /// <summary>
        /// автосохранение задач после проверки
        ///
        /// Статика по той же причине, что и tbTaskCheck: задачу выполняет
        /// Quartz, а TasksController живёт в форме. Ставится в Main.InitControllers.
        /// </summary>
        public static Action AutoSaveHandler = null;

        /// <summary>
        /// используемые порты для проверок
        /// </summary>
        static bool[] TaskCheckThreads = new bool[10] { false, false, false, false, false, false, false, false, false, false };

        #endregion

        #region сервсиные

        /// <summary>
        /// получить номер свободного потока для проверок
        /// </summary>
        /// <returns></returns>
        protected int GetFreeThread()
        {
            // число потоков из настроек - читаем только, писать в настройки из потока нельзя
            int maxThreads = Properties.Settings.Default.iMaxCheckThreads;

            // получим незанятый поток
            return GetFreeThreadIndex(TaskCheckThreads, maxThreads);
        }

        /// <summary>
        /// укажем что поток проверки осовободился
        /// </summary>
        /// <param name="index"></param>
        protected void FreeThread(int index)
        {
            FreeThreadIndex(TaskCheckThreads, index);
        }

        /// <summary>
        /// лог
        /// </summary>
        /// <param name="task"></param>
        void LogTaskCheck(string message)
        {
            Log(message + " [ задача " + task.Name + " , поток " + threadNum + "]", tbTaskCheck);
        }

        #endregion

        #region выполнение

        /// <summary>
        /// выполнить задачу
        /// </summary>
        /// <param name="context"></param>
        public Task Execute(IJobExecutionContext context)
        {
            // тело задачи синхронное и спит - выполняем его в ThreadPool,
            // чтобы не держать поток пула Quartz
            return RunJobBody(() => ExecuteJob(context));
        }

        /// <summary>
        /// тело задачи
        /// </summary>
        /// <param name="context"></param>
        void ExecuteJob(IJobExecutionContext context)
        {            
            try
            {
                // задачу что надо выполнять
                task = context.JobDetail.JobDataMap.Get("Data#1") as BaseTask;
                if (task == null)
                    return;
                // укажем что начали проверку
                task.IsCheckNow = true;

                // получим номер свободного потока
                threadNum = GetFreeThread();
                if (threadNum == -1)
                    return; // получена команда останова
                LogTaskCheck("подготавливаем запуск задачи ... ");

                // обновим задачу 
                UpdateTask(tbTaskCheck);                    

                // проверку надо выполнить независимо от того, есть ли панель лога -
                // раньше работа шла только внутри этой проверки, и задача
                // молча не проверялась, если лог недоступен
                {
                    // проверим задачу
                    task.onTaskCheckProgressLog += OnTaskCheckProgressLog;
                    List<TaskVariant> newVariants;
                    try
                    {
                        newVariants = task.Check(threadNum);
                    }
                    finally
                    {
                        task.onTaskCheckProgressLog -= OnTaskCheckProgressLog;
                    }

                    // число вариантов
                    int newVariantsCount = 0;
                    if (newVariants != null)
                        newVariantsCount = newVariants.Count;

                    // появились новые варианты - сохраняем их на диск,
                    // иначе до следующего выхода из программы они не переживут
                    // аварийный перезапуск
                    if (newVariantsCount > 0)
                    {
                        Action save = AutoSaveHandler;
                        if (save != null)
                            save();
                    }

                    // уведомить по емайл
                    if (newVariantsCount > 0)
                    {
                        // уведомление по е-майл
                        if (Properties.Settings.Default.SendNewvariansEMailAfterTaskCheck)
                        {
                            if (task.EMailVariantsTo(newVariants, "Новые варианты по задаче " + task.Name, Properties.Settings.Default.EMailTo))
                                LogTaskCheck("отправлено уведомление о новых вариантах задачи по почте");
                        }

                        // уведомление по телефону
                        if (Properties.Settings.Default.RequestCallToNewVariants && task.CheckCount > 1)
                        {
                            int numNewcalls = task.RequestCallByVariants(newVariants, context.Scheduler, true);
                            if (numNewcalls > 0)
                                LogTaskCheck("обновлена очередь заказа звонков , новых вариантов : " + numNewcalls.ToString());
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                LogTaskCheck("ошибка при проверке "+ex.ToString());
            }
            finally
            {
                // проверка закончена
                if (task != null)
                {
                    task.IsCheckNow = false;
                    // обновим задачу
                    UpdateTask(tbTaskCheck);
                }

                // всегда освобождаем слот потока - иначе он теряется навсегда
                if (threadNum != -1)
                    FreeThread(threadNum);
            }
        }

        #endregion

        #region обработчики событий

        /// <summary>
        /// прогресс по проверке задачи
        /// </summary>
        /// <param name="task"></param>
        /// <param name="message"></param>
        protected void OnTaskCheckProgressLog(BaseTask task, string message)
        {
            // лог
            LogTaskCheck(message);

            // обнвоим задачу
            UpdateTask(tbTaskCheck);
        }

        #endregion

    }
}
