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
            // получим выбор
            int selIndex = GetSelectedTaskIndex();
            if (index != -1)
            {
                SetSelectedTaskIndex(index);
                return true;
            }

            // обновим GUI
            RefreshGUI();

            // получим выбор
            ListView.SelectedListViewItemCollection selItems = lwTasks.SelectedItems;
            if (selIndex != -1)
                return taskController.SetCurrentTask(tasks.GetTask(selIndex)); // укажем что у нас новая задача
            else
                return taskController.SetCurrentTask(null); // задача не выбрана

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
        /// <returns></returns>
        public bool SerializeAllTasks()
        {
            // резервные копии - 3 штуки
            try
            {
                if (File.Exists(Application.StartupPath + "\\tasks.json"))
                {
                    string bak1 = Application.StartupPath + "\\tasks.json.bak";
                    string bak2 = Application.StartupPath + "\\tasks.json.bak2";
                    string bak3 = Application.StartupPath + "\\tasks.json.bak3";

                    if (File.Exists(bak3))
                        File.Delete(bak3);
                    if (File.Exists(bak2))
                        File.Move(bak2, bak3);

                    if (File.Exists(bak2))
                        File.Delete(bak2);
                    if (File.Exists(bak1))
                        File.Move(bak1, bak2);

                    if (File.Exists(bak1))
                        File.Delete(bak1);
                    File.Move(Application.StartupPath + "\\tasks.json", bak1);
                }
            }
            catch (Exception)
            {

            }
            return tasks.Serialize("tasks.json");
        }
        // заполнить спиок задач
        void FillTasksList()
        {
            // начнем обновление списка
            lwTasks.BeginUpdate();
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
            FillTasksList();

            // подпишемся на события модели
            tasks.onTaskUpdated += TaskUpdated;
            tasks.onTaskAdded += TaskAdded;

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
            // индекс слишком большой
            if (index >= lwTasks.Items.Count)
                return;

            // уберем выбор
            if (index == -1)
            {
                lwTasks.SelectedItems.Clear();
                return;
            }

            // получим выбор
            lwTasks.Items[index].Selected=true;
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
            lwTasks.Items[lwTasks.Items.Count-1].Selected = true;

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
            // убеерм
            lwTasks.Items.RemoveAt(index);

            // выберем предыдущий элемент
            if (index >= lwTasks.Items.Count)
                index--;
            if (index >= 0)
                lwTasks.Items[index].Selected=true;
        }

        /// <summary>
        /// событие - обновили задачу - нужно ее изменить в списке
        /// </summary>
        /// <param name="task">задача</param>
        /// <param name="iIndex">индекс задачи</param>
        public void TaskUpdated(BaseTask task, int index)
        {
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
