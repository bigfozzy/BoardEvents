using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading;
using System.Threading.Tasks;
using System.Windows.Forms;
using Board_Events.Model.Results;

namespace Board_Events.Threads
{
    /// <summary>
    /// базовый поток для потоков - использующих XHE
    /// </summary>
    public class BaseThreadWithXHE
    {
        #region данные модели

        /// <summary>
        /// обрабатываемый вариант
        /// </summary>
        public TaskVariant variant = null;
        /// <summary>
        /// обрабатываемая задача
        /// </summary>
        public BaseTask task = null;

        #endregion

        #region данные потока

        /// <summary>
        /// надо остановить все потоки
        /// </summary>
        public static bool needStop = false;

        /// <summary>
        /// относительный номер потока (относительно своего класса)
        /// </summary>
        protected int threadNum = -1;

        /// <summary>
        /// лок на многопоточность
        /// </summary>
        private static Object thisLock = new Object();

        #endregion

        #region сервсиные

        /// <summary>
        /// получить номер свободного потока , используя данные своего класса
        /// </summary>
        /// <returns></returns>
        protected int GetFreeThreadIndex(bool[] threads,int max)
        {
            // поправим ошибки - есали они есть
            if (max > threads.Length)
                max = threads.Length;

            // начнем поиск свободного потока
            int threadNum = -1;
            while (threadNum == -1)
            {
                // пауза
                Main.Sleep(3000);
                // надо остановить
                if (needStop || Main.NeedClose)
                    return -1;

                // получим незанятый поток
                lock (thisLock)
                {

                    // получим незанятый поток в пределах максимального числа потоков
                    for (int i = 0; i < max; i++)
                    {
                        if (!threads[i])
                        {
                            threads[i] = true;
                            threadNum = i;
                            break;
                        }
                    }
                }
            }

            // результат
            return threadNum;
        }

        /// <summary>
        /// лог
        /// </summary>
        /// <param name="Message"></param>
        protected void Log(string Message,TextBox tbLog)
        {            
            if (tbLog!=null && !tbLog.IsDisposed)
                tbLog.Invoke(new Action(() => 
                {
                    // добавим лог
                    tbLog.AppendText(DateTime.Now.ToString() + ": " + Message + "\r\n");
                }));
        }

        /// <summary>
        /// обновить задачу
        /// </summary>
        protected void UpdateTask(TextBox tbLog)
        {
            // обновим задачу
            if (tbLog != null && !tbLog.IsDisposed)
                tbLog.Invoke(new Action(() => { task.OnTaskUpdated(); }));
        }
        #endregion

    }
}
