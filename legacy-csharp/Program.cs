using System;
using System.Threading;
using System.Windows.Forms;

namespace Board_Events
{
    static class Program
    {
        /// <summary>
        /// имя мьютекса - второй экземпляр программы
        /// </summary>
        const string mutexName = "BoardEvents_SingleInstance";

        /// <summary>
        /// Главная точка входа для приложения.
        /// </summary>
        [STAThread]
        static void Main()
        {
            // два экземпляра делят tasks.json, его резервные копии и порты
            // эмуляторов - второй перетирает данные первого при выходе
            bool createdNew;
            using (Mutex mutex = new Mutex(true, mutexName, out createdNew))
            {
                if (!createdNew)
                {
                    MessageBox.Show(
                        "Программа уже запущена.\r\n\r\n"
                        + "Второй экземпляр нельзя запускать: он будет мешать проверке задач"
                        + " и перетирать файл с задачами.",
                        "Board Events", MessageBoxButtons.OK, MessageBoxIcon.Information);

                    // мьютекс не наш - выходим, не освобождая чужой
                    return;
                }

                try
                {
                    Application.EnableVisualStyles();
                    Application.SetCompatibleTextRenderingDefault(false);
                    Application.Run(new Main());
                }
                finally
                {
                    // мутекс держим до конца работы приложения
                    mutex.ReleaseMutex();
                }
            }
        }
    }
}