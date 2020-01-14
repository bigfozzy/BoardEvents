using System.Collections.Generic;
using XHE._Helper.Tools.Log;
using System.IO;
using XHE._Helper.Tools.File;
using Newtonsoft.Json;
using Board_Events.Model.Results;
using Quartz;
using XHE._Helper.Tools.GUI;

namespace Board_Events.Model.Tasks
{
    /// <summary>
    /// список задач
    /// </summary>
    public class TasksList
    {
        #region делегаты

        /// <summary>
        /// делегат события - задача была добавлена
        /// </summary>
        /// <param name="task">задача</param>
        /// <param name="index">индекс задачи</param>
        public delegate void AddTaskEvent(BaseTask task, int index);
        public event AddTaskEvent onTaskAdded = null;

        /// <summary>
        /// делегат события - задача была удалена
        /// </summary>
        /// <param name="task">задача</param>
        /// <param name="iIndex">индекс задачи</param>
        public delegate void DeleteTaskEvent(BaseTask task, int index);
        public event DeleteTaskEvent onTaskDeleted = null;

        /// <summary>
        /// делегат события - задача была обновлена
        /// </summary>
        /// <param name="task">задача</param>
        /// <param name="iIndex">индекс задачи</param>
        public delegate void UpdateTaskEvent(BaseTask task, int index);
        public event UpdateTaskEvent onTaskUpdated = null;

        #endregion

        #region данные

        /// <summary>
        /// список всех задач
        /// </summary>
        List<BaseTask> tasks =new List<BaseTask>();

        #endregion

        #region создание задач    

        /// <summary>
        /// создать задачу
        /// </summary>
        /// <param name="url">урл</param>
        /// <param name="name">имя</param>
        /// <param name="time_check">период проверки</param>
        /// <returns></returns>
        BaseTask CreateTask(string url,string name,string time_check)
        {
            // новая задача
            BaseTask task = null;

            // получим тип заадчи
            string type = BaseTask.GetTypeByUrl(url);

            // в зависимости от урла
            if (type== "auto.ria.com" || type == "autoria.com")
                task = new TaskAutoRiaCom(url,name,time_check, OnTaskUpdated);
            if (type == "olx.ua")
                task = new TaskOlxCom(url, name, time_check, OnTaskUpdated);
            if (type == "rst.ua")
                task = new TaskRstUa(url, name, time_check, OnTaskUpdated );

            return task;
        }

        /// <summary>
        /// добавить задачу
        /// </summary>
        /// <param name="url">урл</param>
        /// <param name="name">имя</param>
        /// <param name="time_check">время проверки</param>
        /// <returns></returns>
        public int AddTask(string url, string name, string time_check,bool notifyDelegate=true)
        {
            // поищем уже такую задачу
            if (IsTaskExist(url))
            {
                LogTools.LogEvent("Такая задача уже существует");
                return -1;
            }

            // создадим новую задачу
            BaseTask task = CreateTask(url, name, time_check);
            if (task==null)
            {
                LogTools.LogEvent("Адрес не распознан, задача не создана");
                return -2;
            }

            // добавим задачу в списко задач
            tasks.Add(task);            

            // пошлем событие что задача добавлена
            if (notifyDelegate)
                onTaskAdded(tasks[tasks.Count-1], tasks.Count-1);

            return 1;
        }

        /// <summary>
        /// добавим задачу из базовой задачи
        /// </summary>
        /// <param name=""></param>
        /// <returns></returns>
        public int AddTask(BaseTask task)
        {
            // добавим задачу не посылая событие что на создана
            int iRes=AddTask(task.Url, task.Name, task.TimeCheck,false);
            if (iRes>0)
            {
                // данные проверок задачи
                tasks[tasks.Count - 1].CreateDate = task.CreateDate;
                tasks[tasks.Count - 1].CheckCount = task.CheckCount;
                tasks[tasks.Count - 1].LastCheckDate = task.LastCheckDate;
                tasks[tasks.Count - 1].EnableMailNotification = task.EnableMailNotification;
                tasks[tasks.Count - 1].EnableCallNotification = task.EnableCallNotification;

                // результаты
                tasks[tasks.Count - 1].Variants = task.Variants;
                if (task.Variants == null)
                    tasks[tasks.Count - 1].Variants = new List<TaskVariant>();

                // пошлем событие что задача добавлена - если такой делегат есть
                if (onTaskAdded!=null)
                    onTaskAdded.Invoke(tasks[tasks.Count - 1], tasks.Count - 1);
            }
            return iRes;
        }

        /// <summary>
        /// удалить задачу
        /// </summary>
        /// <param name="index">индекс</param>
        /// <returns></returns>
        public bool DeleteTask(int index)
        {
            // проверим что такая задача есть
            if (index >= tasks.Count)
                return false;

            // уберем
            BaseTask task = tasks[index];
            tasks.RemoveAt(index);

            // пошлем событие что задача удалена - если такой делегат есть
            if (onTaskDeleted!=null)
                onTaskDeleted.Invoke(task, index);
            return true;
        }

        /// <summary>
        /// удалить все задачи
        /// </summary>
        /// <returns></returns>
        public bool DeleteAllTasks()
        {
            // нет задач в списке
            if (tasks.Count == 0)
                return false;

            // удалим вс задачи
            for (int i = tasks.Count - 1; i >= 0; i--)
                DeleteTask(i);

            return true;
        }

        #endregion

        #region сериализация

        /// <summary>
        /// сериализация задач
        /// </summary>
        /// <returns></returns>
        public bool Serialize(string path)
        {
            // получим содержимое в json
            string serialized = JsonConvert.SerializeObject(tasks);

            // запишем в файл
            TextFileTools.WriteFile(path, serialized, "utf-16");            

            return true;
        }

        /// <summary>
        /// десериализация задач 
        /// </summary>
        /// <param name="path"></param>
        /// <returns></returns>
        public bool Deserialize(string path)
        {
            // десериализуем
            if (File.Exists(path))
            {
                // получим солдержимое файла
                string serialized = TextFileTools.ReadFile(path, "utf-16");

                // получим из файла
                List<BaseTask> newList = JsonConvert.DeserializeObject<List<BaseTask>>(serialized);

                // добавим в список с учетом классов
                for (int i = 0; i < newList.Count; i++)
                    AddTask(newList[i]);
            }

            return true;
        }

        #endregion

        #region запуск задач

        /// <summary>
        /// запустить задачу на выполнение сейчас
        /// </summary>
        /// <returns></returns>
        public bool StartTaskNow(BaseTask task, IScheduler scheduler)
        {
            return task.StartNow(scheduler);            
        }
        /// <summary>
        /// запустить все задачи на выполнение сейчас
        /// </summary>
        /// <returns></returns>
        public bool StartAllTasksNow(IScheduler scheduler)
        {
            // проверим все как поток 0            
            foreach (BaseTask task in tasks)
                StartTaskNow(task, scheduler);

            // число новых
            return true;
        }

        /// <summary>
        /// запустить все задачи на работу по расписанию
        /// </summary>
        /// <returns></returns>
        public bool StartTaskSheduling(IScheduler scheduler)
        {
            // остановить все
            foreach (BaseTask task in tasks)
                task.StartScheduling(scheduler);

            return true;
        }

        /// <summary>
        /// остановить все задачи на рабоут по расписанию
        /// </summary>
        /// <returns></returns>
        public bool StopTaskSheduling(IScheduler scheduler)
        {
            // остановить все
            foreach (BaseTask task in tasks)
                task.StopScheduling(scheduler);

            return true;
        }

        #endregion

        #region получение информации о задаче

        /// <summary>
        /// проверить что такая задача существует (по улру)
        /// </summary>
        /// <param name="url">урл задачи</param>
        /// <returns></returns>
        bool IsTaskExist(string url)
        {
            // поищем в задачах
            for (int i=0;i< tasks.Count;i++)
            {
                if (tasks[i].Url == url)
                    return true;
            }

            // не нашли
            return false;
        }
        /// <summary>
        /// получить индекс задачи
        /// </summary>
        /// <param name="url">урл задачи</param>
        /// <returns></returns>
        int GetTaskIndexByUrl(string url)
        {
            // поищем в задачах
            for (int i = 0; i < tasks.Count; i++)
            {
                if (tasks[i].Url == url)
                    return i;
            }

            // не нашли
            return -1;
        }
        /// <summary>
        /// получим задачу
        /// </summary>
        /// <param name="index">индекс задачи</param>
        /// <returns></returns>
        public BaseTask GetTask(int index)
        {
            // не нашли
            if (index >= tasks.Count)
                return null;

            // нашли
            return tasks[index];
        }

        /// <summary>
        /// получим задачу с заданным урл
        /// </summary>
        /// <param name="index">индекс задачи</param>
        /// <returns></returns>
        public BaseTask GetTaskByUrl(string url)
        {
            // поищем в задачах
            for (int i = 0; i < tasks.Count; i++)
            {
                if (tasks[i].Url == url)
                    return tasks[i];
            }

            // не нашли
            return null;
        }

        /// <summary>
        /// получить число задач
        /// </summary>
        public int GetTaskCount()
        {
            return tasks.Count;
        }

        #endregion

        #region событие от задачи

        /// <summary>
        /// задача была обновлена
        /// </summary>
        /// <param name="task"></param>
        public void OnTaskUpdated(BaseTask task)
        {
            if (onTaskUpdated!=null)
                onTaskUpdated.Invoke(task,tasks.IndexOf(task));
        }

        #endregion
    }
}
