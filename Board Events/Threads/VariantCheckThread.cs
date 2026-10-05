using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using Board_Events.Model.Results;
using Quartz;

namespace Board_Events.Threads
{
    class VariantCheckThread : BaseThreadWithXHE, IJob
    {
        #region статические данные

        // для сообщений о проверке варианта
        public static TextBox tbVariantCheck = null;

        // максимальное число потоков проверки вариантов
        static int numThreads = XhePorts.variantCheckCount;

        // слоты занятости. Размер держим равным numThreads - иначе
        // GetFreeThreadIndex молча обрезает лимит по длине массива
        static bool[] VariantCheckThreads = new bool[XhePorts.variantCheckCount];

        #endregion

        #region сервисные

        /// <summary>
        /// получить номер свободного потока для проверки варианта
        /// </summary>
        /// <returns></returns>
        protected int GetFreeThread()
        {
            // получим незанятый поток
            return GetFreeThreadIndex(VariantCheckThreads, numThreads);
        }
        /// <summary>
        /// укажем что поток обзвона осовободился
        /// </summary>
        /// <param name="index"></param>
        protected void FreeThread(int index)
        {
            FreeThreadIndex(VariantCheckThreads, index);
        }
        /// <summary>
        /// обновить задачу
        /// </summary>
        void UpdateVariant()
        {
            // обновим задачу
            if (tbVariantCheck != null && !tbVariantCheck.IsDisposed)
                tbVariantCheck.Invoke(new Action(() => { task.OnTaskUpdated(); }));
        }
        /// <summary>
        /// лог
        /// </summary>
        /// <param name="task"></param>
        void LogVariantCheck(string message)
        {
            string url = (variant != null) ? variant.Url : "?";
            Log(message + " [ вариант " + url + " , поток " + threadNum + "]", tbVariantCheck);
        }

        #endregion

        #region выполнение

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
                // задача которую надо обнвоить
                task = context.JobDetail.JobDataMap.Get("Data#1") as BaseTask;
                // вариант по котрому надо заказать звонок
                variant = context.JobDetail.JobDataMap.Get("Data#2") as TaskVariant;
                if (variant == null)
                    return;
                // укажем что начали проверку
                variant.IsCheckNow = true;

                // получим номер свободного потока
                threadNum = GetFreeThread();
                if (threadNum == -1)
                    return; // получена команда останова
                LogVariantCheck("подготовка проверки варианта ...");

                // обновим задачу 
                UpdateTask(tbVariantCheck);

                // проверка не зависит от наличия панели лога
                {
                    // разберем вариант
                    variant.onVarianCheckProgressLog += OnVariqntCheckLog;
                    try
                    {
                        variant.Check(threadNum, task);
                    }
                    finally
                    {
                        variant.onVarianCheckProgressLog -= OnVariqntCheckLog;
                    }

                    // укажем что закончили проверку
                    variant.IsCheckNow = false;
                    // обновим задачу, свзяанную с вариантом
                    UpdateTask(tbVariantCheck);
                }
            }
            catch (Exception ex)
            {
                // лог
                LogVariantCheck("ошибка проверки варианта " + ex.ToString());
            }
            finally
            {
                // укажем что закончили проверку
                if (variant != null)
                    variant.IsCheckNow = false;

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
        protected void OnVariqntCheckLog(TaskVariant variant, string message)
        {
            // лог
            LogVariantCheck(message);
        }

        #endregion
    }
}
