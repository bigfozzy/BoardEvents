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
        ///
        /// Нигде не выставляется. Оставлено как заготовка - если понадобится
        /// останавливать потоки без закрытия приложения, флаг надо ставить
        /// здесь, иначе проверка ниже бесполезна.
        /// </summary>
        public static bool needStop = false;

        /// <summary>
        /// относительный номер потока (относительно своего класса)
        /// </summary>
        protected int threadNum = -1;

        /// <summary>
        /// лок на многопоточность
        ///
        /// Общий для всех трех классов: массивы у них свои, но лок держим
        /// один - захват дешевый, а пересекающиеся ожидания не мешают друг другу.
        /// FreeThread тоже обязан брать этот лок, иначе запись и чтение массива
        /// не будут атомарны по отношению друг к другу.
        /// </summary>
        protected static Object thisLock = new Object();

        #endregion

        #region сервсиные

        /// <summary>
        /// сколько ждать свободный поток перед отказом (секунд)
        /// </summary>
        protected const int waitFreeThreadTimeout = 600;

        /// <summary>
        /// получить номер свободного потока , используя данные своего класса
        /// </summary>
        /// <returns>-1 если свободного потока не появилось за отведенное время либо приложение закрывается</returns>
        protected int GetFreeThreadIndex(bool[] threads,int max)
        {
            // поправим ошибки - есали они есть
            if (max > threads.Length)
                max = threads.Length;
            // некорректное число потоков - ждать бессмысленно
            if (max <= 0)
                return -1;

            // счетчик ожидания
            int waitedSeconds = 0;

            // начнем поиск свободного потока
            int threadNum = -1;
            while (threadNum == -1)
            {
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

                // свободный поток найден
                if (threadNum != -1)
                    break;

                // пауза перед следующей попыткой
                Main.Sleep(3000);
                waitedSeconds += 3;

                // надо остановить
                if (needStop || Main.NeedClose)
                    return -1;

                // не ждем вечно - иначе задача или вариант потеряют слот навсегда
                if (waitedSeconds >= waitFreeThreadTimeout)
                    return -1;
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
            // приведение к нужному потоку делаем только если панель есть и жива.
            // IsDisposed проверяем еще раз внутри Invoke - между проверкой и
            // вызовом контрол может быть уничтожен закрытием формы
            if (tbLog == null || tbLog.IsDisposed)
                return;

            try
            {
                tbLog.Invoke(new Action(() =>
                {
                    if (tbLog.IsDisposed)
                        return;

                    // добавим лог
                    tbLog.AppendText(DateTime.Now.ToString() + ": " + Message + "\r\n");
                }));
            }
            catch (ObjectDisposedException)
            {
                // форму закрыли, пока писали лог - терять из-за этого задачу незачем
            }
            catch (InvalidOperationException)
            {
                // у Invoke нет handle (форма еще не создана) - тоже не критично
            }
        }

        /// <summary>
        /// обновить задачу
        /// </summary>
        protected void UpdateTask(TextBox tbLog)
        {
            if (task == null)
                return;

            // приведение к UI-потоку нужно только когда панель лога есть:
            // task.OnTaskUpdated() сам ходит по контролам ListView, которые
            // обязаны трогаться из UI-потока
            if (tbLog == null || tbLog.IsDisposed)
                return;

            try
            {
                tbLog.Invoke(new Action(() =>
                {
                    if (tbLog.IsDisposed)
                        return;

                    task.OnTaskUpdated();
                }));
            }
            catch (ObjectDisposedException)
            {
            }
            catch (InvalidOperationException)
            {
            }
        }

        /// <summary>
        /// освободить занятый слот потока
        /// </summary>
        /// <param name="threads">массив занятости своего класса</param>
        /// <param name="index">номер слота</param>
        protected static void FreeThreadIndex(bool[] threads, int index)
        {
            // тот же лок, что и при захвате, иначе запись проскочит мимо чтения
            lock (thisLock)
            {
                if (threads != null && index >= 0 && index < threads.Length)
                    threads[index] = false;
            }
        }

        /// <summary>
        /// запустить тело задачи в фоновом потоке
        ///
        /// В Quartz 3 IJob.Execute возвращает Task и шедулер ждет его
        /// завершения, удерживая поток пула. Тела наших задач синхронные
        /// и спят по несколько секунд (Thread.Sleep, ожидание эмулятора),
        /// поэтому выносим их в ThreadPool, а поток шедулера освобождаем.
        /// </summary>
        /// <param name="body">синхронное тело задачи</param>
        /// <returns></returns>
        protected static Task RunJobBody(Action body)
        {
            return Task.Run(body);
        }
        #endregion

    }
}
