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

        // для сообщений о звонках
        public static TextBox tbVariantCheck = null;

        // максимальное число потоков
        static int numThreads = 1;
        // используемые порты для звонков        
        static bool[] VariantCheckThreads = new bool[10] { false, false, false, false, false, false, false, false, false, false };

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
            VariantCheckThreads[index] = false;
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
            Log(message + " [ вариант " + variant.Url + " , поток " + threadNum + "]", tbVariantCheck);
        }

        #endregion

        #region выполнение

        public void Execute(IJobExecutionContext context)
        {
            try
            {
                // задача которую надо обнвоить
                task = context.JobDetail.JobDataMap.Get("Data#1") as BaseTask;
                // вариант по котрому надо заказать звонок
                variant = context.JobDetail.JobDataMap.Get("Data#2") as TaskVariant;
                // укажем что начали проверку
                variant.IsCheckNow = true;

                // получим номер свободного потока
                threadNum = GetFreeThread();
                if (threadNum == -1)
                    return; // получена команда останова
                LogVariantCheck("подготовка проверки варианта ...");

                // обновим задачу 
                UpdateTask(tbVariantCheck);

                // проверим
                if (tbVariantCheck != null && !tbVariantCheck.IsDisposed)
                {
                    // закажем звонок
                    variant.onVarianCheckProgressLog += OnVariqntCheckLog;
                    string message = variant.Check(threadNum,task);
                    variant.onVarianCheckProgressLog -= OnVariqntCheckLog;

                    // укажем что закончили проверку
                    variant.IsCheckNow = false;
                    // обновим задачу, свзяанную с вариантом
                    UpdateTask(tbVariantCheck);

                    // укажем что поток стал свободен
                    FreeThread(threadNum);
                }
            }
            catch (Exception ex)
            {
                // укажем что заколнчили проверку
                variant.IsRequestCallNow = false;
                // лог
                LogVariantCheck("ошибка заказа звонка " + ex.ToString());
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
