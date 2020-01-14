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
        /// максимальное число потоков
        /// </summary>
        static int numThreads = 10;
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
            // проверим что все настрйока правильная
            if (Properties.Settings.Default.iMaxCheckThreads > numThreads)
                Properties.Settings.Default.iMaxCheckThreads = numThreads;

            // получим незанятый поток
            return GetFreeThreadIndex(TaskCheckThreads, Properties.Settings.Default.iMaxCheckThreads);
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
                    List<TaskVariant> newVariants = task.Check(threadNum);
                    task.onTaskCheckProgressLog -= OnTaskCheckProgressLog;

                    // число вариантов
                    int newVariantsCount = 0;
                    if (newVariants != null)
                        newVariantsCount = newVariants.Count;

                    // уведомить по емайл
                    if (newVariantsCount>0)
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

                    // укажем что поток стал свободен
                    FreeThread(threadNum);                    
                }

                // проверка закончена
                task.IsCheckNow = false;
                // обновим задачу 
                UpdateTask(tbTaskCheck);
            }
            catch (Exception ex)
            {
                // проверка закончена
                task.IsCheckNow = false;
                LogTaskCheck("ошибка при проверке "+ex.ToString());
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
