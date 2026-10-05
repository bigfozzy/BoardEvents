using System;
using System.Collections.Generic;
using System.Windows.Forms;

namespace Board_Events.Threads
{
    /// <summary>
    /// порты эмуляторов XHE
    ///
    /// Номера портов используются и потоками (BaseTask.Check,
    /// TaskVariant.RequestCall, TaskVariant.Check), и подготовкой при старте.
    /// Если считать их в разных местах, списки разъезжаются - как это и
    /// вышло с диапазоном 13000, который никто не готовил.
    /// </summary>
    public static class XhePorts
    {
        #region диапазоны

        /// <summary>
        /// базовый порт проверок задач
        /// </summary>
        public const int checkBase = 11000;

        /// <summary>
        /// сколько потоков проверки задач поддерживаем
        /// </summary>
        public const int checkCount = 10;

        /// <summary>
        /// базовый порт заказа звонков
        /// </summary>
        public const int callBase = 12000;

        /// <summary>
        /// сколько потоков заказа звонков поддерживаем
        /// </summary>
        public const int callCount = 2;

        /// <summary>
        /// базовый порт проверки отдельных вариантов
        /// </summary>
        public const int variantCheckBase = 13000;

        /// <summary>
        /// сколько потоков проверки вариантов поддерживаем
        /// </summary>
        public const int variantCheckCount = 1;

        /// <summary>
        /// шаг между портами - на него же умножается номер потока
        /// </summary>
        const int portStep = 10;

        #endregion

        #region порты по номеру потока

        /// <summary>
        /// порт проверки задачи по номеру потока
        /// </summary>
        public static int Check(int thread)
        {
            return checkBase + thread * portStep;
        }

        /// <summary>
        /// порт заказа звонка по номеру потока
        /// </summary>
        public static int Call(int thread)
        {
            return callBase + thread * portStep;
        }

        /// <summary>
        /// порт проверки варианта по номеру потока
        /// </summary>
        public static int VariantCheck(int thread)
        {
            return variantCheckBase + thread * portStep;
        }

        #endregion

        #region список всех портов

        /// <summary>
        /// все порты, которые может использовать приложение
        /// </summary>
        /// <returns></returns>
        public static List<int> All()
        {
            List<int> ports = new List<int>();

            for (int i = 0; i < checkCount; i++)
                ports.Add(Check(i));
            for (int i = 0; i < callCount; i++)
                ports.Add(Call(i));
            for (int i = 0; i < variantCheckCount; i++)
                ports.Add(VariantCheck(i));

            return ports;
        }

        /// <summary>
        /// папка эмулятора для порта
        /// </summary>
        /// <param name="port"></param>
        /// <returns></returns>
        public static string GetPortDir(int port)
        {
            return Application.StartupPath + "\\XHE\\" + port.ToString();
        }

        /// <summary>
        /// исполняемый файл эмулятора для порта
        /// </summary>
        /// <param name="port"></param>
        /// <returns></returns>
        public static string GetPortExe(int port)
        {
            return GetPortDir(port) + "\\" + port.ToString() + ".exe";
        }

        /// <summary>
        /// эмулятор для порта подготовлен на диске
        /// </summary>
        /// <param name="port"></param>
        /// <returns></returns>
        public static bool IsPrepared(int port)
        {
            return System.IO.File.Exists(GetPortExe(port));
        }

        #endregion
    }
}