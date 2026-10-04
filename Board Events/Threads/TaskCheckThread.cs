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
            TaskCheckThreads[index] = false;
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
        public void Execute(IJobExecutionContext context)
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

                // проверим
                if (tbTaskCheck != null && !tbTaskCheck.IsDisposed)
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

                    // уведомить по емайл
                    if (newVariantsCount > 0)
                    {
                        // если програмам еще работает
                        if (tbTaskCheck != null && !tbTaskCheck.IsDisposed)
                        {
                            // уведомление по е-майл
                            if (Properties.Settings.Default.SendNewvariansEMailAfterTaskCheck)
                            {
                                tbTaskCheck.Invoke(new Action(() =>
                                {
                                    if (task.EMailVariantsTo(newVariants, "Новые варианты по задаче " + task.Name, Properties.Settings.Default.EMailTo))
                                        LogTaskCheck("отправлено уведомление о новых вариантах задачи по почте");
                                }));
                            }

                            // уведомление по телефону
                            if (Properties.Settings.Default.RequestCallToNewVariants && task.CheckCount > 1)
                            {
                                tbTaskCheck.Invoke(new Action(() =>
                                {
                                    int numNewcalls = task.RequestCallByVariants(newVariants, context.Scheduler, true);
                                    if (numNewcalls > 0)
                                        LogTaskCheck("обновлена очередь заказа звонков , новых вариантов : " + numNewcalls.ToString());
                                }));
                            }
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
