using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Board_Events.Model.Tasks;
using System.Windows.Forms;
using XHE._Helper.Tools.File;
using Quartz;
using XHE._Helper.Tools.GUI;
using System.IO;
using XHE._Helper.Tools.Log;

namespace Board_Events.Controller
{
    /// <summary>
    /// управление задачами и их отображение
    /// </summary>
    public class TasksController
    {
        #region делегаты

        /// <summary>
        /// делегат события - список задач изменился
        /// </summary>        
        public delegate void TasksUpdatedEvent();
        public event TasksUpdatedEvent onTasksUpdated = null;

        #endregion

        #region данные

        // задачи не запущены
        public bool isTasksSheduled =false;

        /// <summary>
        /// шедулер
        /// </summary>
        IScheduler scheduler = null;

        /// <summary>
        /// данные задач
        /// </summary>
        private TasksList tasks = null;
        
        /// <summary>
        /// GUI списка задач
        /// </summary>
        ListView lwTasks = null;

        /// <summary>
        /// указатель на контроллер текущей задачи
        /// </summary>
        TaskController taskController = null;

        /// <summary>
        /// когда последний раз сохраняли - чтобы не писать файл на каждый чек
        /// </summary>
        DateTime lastSaveTime = DateTime.MinValue;

        /// <summary>
        /// минимальный интервал между автосохранениями (секунд)
        /// </summary>
        const int autoSaveMinInterval = 60;

        #endregion

        #region создание

        /// <summary>
        /// конструктор
        /// </summary>
        /// <param name="tasks">ссылка на данные задачи</param>
        /// <param name="lwTasks">GUI списка задач</param>
        public TasksController(ListView lwTasks,IScheduler scheduler)
        {
            // GUI
            this.lwTasks = lwTasks;
            this.scheduler = scheduler;

            /// список всех задач
            tasks = new TasksList();

            // подпишемся на события модели
            tasks.onTaskAdded += TaskAdded;
            tasks.onTaskDeleted += TaskDeleted;
            tasks.onTaskUpdated += TaskUpdated;
        }

        /// <summary>
        /// задать контроллер текущей задачи
        /// </summary>
        /// <param name="taskController">ссылка на контроллер текущей задачи</param>
        public void SetTaskController(TaskController taskController)
        {
            this.taskController = taskController;
        }

        #endregion

        #region работа с одной задачей

        /// <summary>
        /// выберем задачу
        /// </summary>
        /// <param name="taskController">контролер текущей задачи</param>
        /// <returns></returns>
        public bool SelectTask(int index=-1)
        {
            // задан индекс - выделим его
            // Событие SelectedIndexChanged само вызовет SelectTask() без индекса,
            // но оно не срабатывает если элемент уже был выбран - поэтому
            // текущую задачу указываем здесь явно
            if (index != -1)
            {
                SetSelectedTaskIndex(index);
                SetCurrentTaskBySelection();
                return true;
            }

            SetCurrentTaskBySelection();
            return taskController.Task != null;
        }

        /// <summary>
        /// указать текущую задачу по тому что выделено в списке
        /// </summary>
        void SetCurrentTaskBySelection()
        {
            // обновим GUI
            RefreshGUI();

            // получим выбор
            int selIndex = GetSelectedTaskIndex();

            // укажем текущую задачу
            taskController.SetCurrentTask((selIndex != -1) ? tasks.GetTask(selIndex) : null);
        }

        /// <summary>
        /// добавим задачу
        /// </summary>
        /// <param name="url">урл</param>
        /// <param name="name">имя</param>
        /// <param name="time_check">период задачи</param>
        /// <returns></returns>
        public int AddTask(string url,string name,string time_check)
        {            
            // добавим
            return tasks.AddTask(url, name, time_check);
        }

        /// <summary>
        /// удалим задачу 
        /// </summary>
        /// <returns></returns>
        public bool DeleteTask()
        {
            int selIndex = GetSelectedTaskIndex();
            if (selIndex == -1)
                return false;

            // вопрос об удалении
            DialogResult dialogResult = MessageBox.Show("Удалить задачу ? ", "Удалить задачу", MessageBoxButtons.YesNo);
            if (dialogResult == DialogResult.No)
                return false;

            // удалим
            return tasks.DeleteTask(selIndex);
        }

        #endregion

        #region работа со всеми задачами

        /// <summary>
        /// количество задач
        /// </summary>
        /// <returns></returns>
        public int GetTaskCount()
        {
            // получим выбор
            return tasks.GetTaskCount();
        }
        /// <summary>
        /// получим задачу с заданным урл
        /// </summary>
        /// <param name="url"></param>
        /// <returns></returns>
        public BaseTask GetTaskByUrl(string url)
        {
            return tasks.GetTaskByUrl(url);
        }

        /// <summary>
        /// удалить все задачи
        /// </summary>
        /// <returns></returns>
        public bool DeleteAllTask()
        {
            // вопрос об удалении
            DialogResult dialogResult = MessageBox.Show("Удалить все задачи ?", "Удалить задачи", MessageBoxButtons.YesNo);
            if (dialogResult == DialogResult.No)
                return false;

            // удалим все задачи
            return tasks.DeleteAllTasks();            
        }

        /// <summary>
        /// экспортв сех задач
        /// </summary>
        /// <returns></returns>
        public bool ExportAllTasks()
        {
            // выберем файл
            string path = "";
            if (!FileTools.SelectFile(new SaveFileDialog(), "ExportAllTasks", ref path))
                return false;

            // добавим срасширение если надо
            if (FileTools.GetFileExtension(path) == "")
                path += ".tasks";

            // сделаем экспорт
            return tasks.Serialize(path);
        }

        /// <summary>
        /// импорт задач
        /// </summary>
        /// <returns></returns>
        public bool ImportAllTasks()
        {
            // выберем файл
            string path = "";
            if (!FileTools.SelectFile(new OpenFileDialog(), "ImportAllTasks", ref path))
                return false;

            // сделаем импорт
            return tasks.Deserialize(path);
        }

        /// <summary>
        /// сериализация задач
        /// </summary>
        /// <returns>false если файл не записан</returns>
        public bool SerializeAllTasks()
        {
            return SerializeAllTasks(false);
        }

        /// <summary>
        /// сериализация задач
        /// </summary>
        /// <param name="quiet">true - не показывать диалоги (вызов не из UI-потока)</param>
        /// <returns>false если файл не записан</returns>
        bool SerializeAllTasks(bool quiet)
        {
            lastSaveTime = DateTime.Now;
            string main = Application.StartupPath + "\\tasks.json";
            string bak1 = main + ".bak";
            string bak2 = main + ".bak2";
            string bak3 = main + ".bak3";

            // сюда уходит предыдущая версия, дальше она станет bak1
            string prev = main + ".bak.tmp";

            // сериализуем во временный файл рядом с целевым.
            // Прямая запись в tasks.json обрезала файл при сбое,
            // а ротация бэкапов успевала отвести единственную годную копию
            string tmp = main + ".tmp";
            if (!tasks.Serialize(tmp))
            {
                ShowMessage.ShowWarningMessage("Не удалось записать файл задач. Проверьте доступ к папке программы.", "Предупреждение");
                return false;
            }

            try
            {
                if (File.Exists(main))
                {
                    // File.Replace подменяет файл и уводит старую версию в bak.tmp -
                    // одной операцией, окна без tasks.json не появляется
                    File.Replace(tmp, main, prev, true);
                }
                else
                {
                    File.Move(tmp, main);
                    prev = null;
                }
            }
            catch (Exception ex)
            {
                // свежие данные лежат во временном файле - терять их нельзя
                Report("Не удалось сохранить файл задач : " + ex.Message
                    + "\r\n\r\nДанные оставлены в файле " + tmp, quiet);
                return false;
            }

            // сдвигаем цепочку бэкапов на один шаг: bak3 <- bak2 <- bak1 <- prev
            try
            {
                if (File.Exists(bak3))
                    File.Delete(bak3);
                if (File.Exists(bak2))
                    File.Move(bak2, bak3);
                if (File.Exists(bak1))
                    File.Move(bak1, bak2);

                // отведённая версия становится первой копией
                if (prev != null && File.Exists(prev))
                    File.Move(prev, bak1);
            }
            catch (Exception)
            {
                // рабочий файл уже записан - сбой ротации бэкапов не критичен
                Report("Не удалось обновить резервные копии tasks.json", quiet);
            }

            return true;
        }

        /// <summary>
        /// показать ошибку пользователю или записать в лог
        ///
        /// Диалог из не-UI-потока вешает интерфейс, поэтому для фоновых
        /// вызовов пишем только в лог
        /// </summary>
        void Report(string message, bool quiet)
        {
            if (quiet)
                LogTools.LogEvent(message);
            else
                ShowMessage.ShowWarningMessage(message, "Предупреждение");
        }
        /// <summary>
        /// сохранить задачи, если с прошлого раза прошло достаточно времени
        ///
        /// Вызывается из рабочих потоков после проверки, поэтому интервал
        /// ограничивает количество записей при частых проверках. Ошибку
        /// не показываем - пользователя может быть нет за клавиатурой,
        /// пишем в лог.
        /// </summary>
        public void AutoSaveTasks()
        {
            // слишком часто - задачи не менялись так быстро
            if ((DateTime.Now - lastSaveTime).TotalSeconds < autoSaveMinInterval)
                return;

            // сохраняем в фоне, чтобы не блокировать поток проверки
            // и интерфейс на операции с диском
            System.Threading.ThreadPool.QueueUserWorkItem(_ =>
            {
                SerializeAllTasks(true);
            });
        }

        // заполнить спиок задач
        void FillTasksList()
        {
            // начнем обновление списка
            lwTasks.BeginUpdate();
            // очистим - иначе повторный вызов задвоит строки
            lwTasks.Items.Clear();
            for (int i=0;i<tasks.GetTaskCount();i++)
            {
                BaseTask task = tasks.GetTask(i);
                ListViewItem item = lwTasks.Items.Add(task.Name);
                item.SubItems.Add("");
                item.SubItems.Add("");
                item.SubItems.Add("");
                item.SubItems.Add("");
                item.SubItems.Add("");
                item.SubItems.Add("");
                item.SubItems.Add("");
                item.SubItems.Add("");
                this.SetTaskRow(item, task);
            }
            // закончим обновление списка
            lwTasks.EndUpdate();

        }
        /// <summary>
        /// десериализация задач
        /// </summary>
        /// <returns></returns>
        public bool DeserializeAllTasks()
        {
            // отпишемся от событий модели
            tasks.onTaskUpdated -= TaskUpdated;
            tasks.onTaskAdded -= TaskAdded;
            tasks.onTaskDeleted -= TaskDeleted;

            // прочитаем с диска
            bool res = false;
            try
            {
                res = tasks.Deserialize("tasks.json");
            }
            catch (Exception ex)
            {
                ShowMessage.ShowWarningMessage(ex.ToString(), "Ошибка при чтении задач с диска");
                ShowMessage.ShowInfoMessage("Задачи можно восстановить из последней реезврной копии task.json.bak из папки программы");
            }

            // список задач в памяти уже заменен целиком - перерисуем с нуля
            FillTasksList();

            // подпишемся на события модели
            tasks.onTaskUpdated += TaskUpdated;
            tasks.onTaskAdded += TaskAdded;
            tasks.onTaskDeleted += TaskDeleted;

            return res;
        }

        #endregion

        #region выполнение задач

        /// <summary>
        /// проверить сейчас все задачи
        /// </summary>
        /// <returns></returns>
        public bool StartNowAllTasks()
        {
            // проверим все задачи
            return tasks.StartAllTasksNow(scheduler);
        }
        /// <summary>
        /// запустить расписание задач
        /// </summary>
        /// <returns></returns>
        public bool StartTaskSheduling()
        {
            // укажем что проверка запущена
            isTasksSheduled = true;
            // обновим GUI
            RefreshGUI();
            // запустим задачи
            return tasks.StartTaskSheduling(scheduler);
        }
        /// <summary>
        /// остановить расписание задач
        /// </summary>
        /// <returns></returns>
        public bool StopTaskSheduling()
        {
            // укажем что проверка остановлена
            isTasksSheduled = false;
            // обновим GUI
            RefreshGUI();
            // остановим все задачи
            return tasks.StopTaskSheduling(scheduler);
        }

        /// <summary>
        /// запустить или остановить выполнение задач по расписанию
        /// </summary>
        public bool StartStopScheduling()
        {            
            if (isTasksSheduled)
                return StopTaskSheduling(); // если были запущены - сотановим
            else
                return StartTaskSheduling(); // еслди были остановлены - запустим
        }

        #endregion

        #region работа с интерфейсом

        /// <summary>
        /// выбранная задача
        /// </summary>
        /// <returns></returns>
        public int GetSelectedTaskIndex()
        {
            // получим выбор
            ListView.SelectedListViewItemCollection selItems = lwTasks.SelectedItems;
            if (selItems.Count == 0)
                return -1;

            // результат
            return selItems[0].Index;
        }
        /// <summary>
        /// выбранная задача
        /// </summary>
        /// <returns></returns>
        public void SetSelectedTaskIndex(int index)
        {
            // уберем выбор
            lwTasks.SelectedItems.Clear();

            // выбирать нечего
            if (index == -1)
                return;

            // индекс за пределами списка
            if (index < 0 || index >= lwTasks.Items.Count)
                return;

            // получим выбор
            lwTasks.Items[index].Selected = true;
        }

        /// <summary>
        /// обновить интерфейс (достпность кнопк)
        /// </summary>
        void RefreshGUI()
        {
            // вызовем делегат - если он задан
            if (onTasksUpdated!=null)
                onTasksUpdated.Invoke();
        }

        /// <summary>
        /// задать задачу в списке задач
        /// </summary>
        /// <param name="item"></param>
        /// <param name="task"></param>
        void SetTaskRow(ListViewItem item,BaseTask task)
        {
            item.Text = task.Name;
            item.SubItems[0].Text = task.Name;
            item.SubItems[1].Text = task.GetVariantsCount().ToString();
            item.SubItems[2].Text = task.LastCheckDate.ToString();
            item.SubItems[3].Text = task.CheckCount.ToString();
            item.SubItems[4].Text = task.TimeCheck;
            item.SubItems[5].Text = task.CreateDate.ToString();
            item.SubItems[6].Text = task.Type;
            item.SubItems[7].Text = task.Url;
            item.Tag = task;
            if (task.Type == "rst.ua")
                item.ImageIndex = 0;
            else if (task.Type == "olx.com" || task.Type == "olx.ua")
                item.ImageIndex = 1;
            else if (task.Type == "autoria.com")
                item.ImageIndex = 2;
        }

        /// <summary>
        /// событие - добавили задачу - нужно ее добавить в список
        /// </summary>
        /// <param name="task">задача</param>
        /// <param name="iIndex">индекс задачи</param>
        public void TaskAdded(BaseTask task,int index)
        {
            // добавим
            ListViewItem item=lwTasks.Items.Add(task.Name);

            // укажем данные            
            item.SubItems.Add("");
            item.SubItems.Add("");
            item.SubItems.Add("");
            item.SubItems.Add("");
            item.SubItems.Add("");
            item.SubItems.Add("");
            item.SubItems.Add("");
            item.SubItems.Add("");
            SetTaskRow(item, task);

            // укажем что надо выбрать
            SetSelectedTaskIndex(index);

            // новая задача стала текущей - обновим панель вариантов
            SetCurrentTaskBySelection();

            // запустим проверку всех задач
            if (Properties.Settings.Default.bAutoStartScheduler || isTasksSheduled)
                task.StartScheduling(scheduler);
        }

        /// <summary>
        /// событие - добавили задачу - нужно ее удалить из списка
        /// </summary>
        /// <param name="task">задача</param>
        /// <param name="iIndex">индекс задачи</param>
        public void TaskDeleted(BaseTask task, int index)
        {
            // задача удаляется - снимем ее с расписания, иначе
            // Quartz продолжит запускать проверку удаленной задачи
            if (task != null && task.IsScheduling())
                task.StopScheduling(scheduler);

            // индекс за пределами списка - удалять нечего
            if (index < 0 || index >= lwTasks.Items.Count)
                return;

            // убеерм
            lwTasks.Items.RemoveAt(index);

            // выберем предыдущий элемент, иначе следующий
            if (index >= lwTasks.Items.Count)
                index--;
            SetSelectedTaskIndex(index);

            // список изменился - переключим текущую задачу на оставшийся выбор
            SetCurrentTaskBySelection();
        }

        /// <summary>
        /// событие - обновили задачу - нужно ее изменить в списке
        /// </summary>
        /// <param name="task">задача</param>
        /// <param name="iIndex">индекс задачи</param>
        public void TaskUpdated(BaseTask task, int index)
        {
            // задача могла быть удалена из списка пока шло обновление
            if (index < 0 || index >= lwTasks.Items.Count)
                return;

            // поменяем в таблице
            ListViewItem item = lwTasks.Items[index];
            SetTaskRow(item,task);

            // обновим - если задача текущая
            if (GetSelectedTaskIndex() == index)
            {
                taskController.RefreshTaskGUI();
                RefreshGUI();
            }
        }

        #endregion
    }
}
