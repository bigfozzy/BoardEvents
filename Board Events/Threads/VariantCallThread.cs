using Board_Events.Model.Results;
using Quartz;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace Board_Events.Threads
{
    class VariantCallThread : BaseThreadWithXHE, IJob
    {
        #region статические данные

        /// <summary>
        /// для сообщений о звонках
        /// </summary>
        public static TextBox TbOutCall = null;

        /// <summary>
        /// максимальное число потоков
        /// </summary>
        static int numThreads = 2;
        /// <summary>
        /// используемые порты для звонков
        /// </summary>
        protected static bool[] CallThreads = new bool[2] { false, false };

        #endregion

        #region сервисные

        /// <summary>
        /// получить номер свободного потока для звоноков
        /// </summary>
        /// <returns></returns>
        protected int GetFreeThread()
        {
            // получим незанятый поток
            return GetFreeThreadIndex(CallThreads, numThreads);
        }
        /// <summary>
        /// укажем что поток обзвона осовободился
        /// </summary>
        /// <param name="index"></param>
        protected void FreeThread(int index)
        {
            CallThreads[index] = false;
        }

        /// <summary>
        /// лог
        /// </summary>
        /// <param name="task"></param>
        void LogVariantRequestCall(string message)
        {
            Log(message + " [ вариант " + variant.Url + " , поток " + threadNum + "]", TbOutCall);
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
                // задача которую надо обнвоить
                task = context.JobDetail.JobDataMap.Get("Data#1") as BaseTask;
                // вариант по котрому надо заказать звонок
                variant = context.JobDetail.JobDataMap.Get("Data#2") as TaskVariant;
                if (!variant.IsValidPhone()) // плохой телефон
                    return;
                // укажем что начали проверку
                variant.IsRequestCallNow = true;

                // получим номер свободного потока
                threadNum = GetFreeThread();
                if (threadNum == -1)
                    return; // получена команда останова
                LogVariantRequestCall("подготовка заказа звонка ...");

                // обновим задачу 
                UpdateTask(TbOutCall);

                // проверим
                if (TbOutCall != null && !TbOutCall.IsDisposed)
                {
                    // закажем звонок
                    variant.onVariantRequestCallCheckProgressLog += OnVariqntRequestCallLog;
                    string message = variant.RequestCall(threadNum);
                    variant.onVariantRequestCallCheckProgressLog -= OnVariqntRequestCallLog;

                    // укажем что закончили проверку
                    variant.IsRequestCallNow = false;
                    // обновим задачу, свзяанную с вариантом
                    UpdateTask(TbOutCall);

                    // укажем что поток стал свободен
                    FreeThread(threadNum);
                }
            }
            catch (Exception ex)
            {
                // укажем что заколнчили проверку
                variant.IsRequestCallNow = false;
                // лог
                LogVariantRequestCall("ошибка заказа звонка " + ex.ToString());
            }
        }

        #endregion

        #region обработчики событий

        /// <summary>
        /// прогресс по проверке задачи
        /// </summary>
        /// <param name="task"></param>
        /// <param name="message"></param>
        protected void OnVariqntRequestCallLog(TaskVariant variant, string message)
        {
            // лог
            LogVariantRequestCall(message);
        }

        #endregion
    }
}
