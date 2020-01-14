using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Board_Events;
using System.Windows.Forms;
using XHE._Helper.Tools.File;
using CefSharp.WinForms;
using XHE._Helper.Standart_Forms;
using Board_Events.Model.Results;
using XHE._Helper.Tools.GUI;
using Quartz;

namespace Board_Events.Controller
{
    public class TaskController
    {
        #region делегаты

        /// <summary>
        /// делегат события - задача изменилась
        /// </summary>        
        public delegate void TaskUpdatedEvent(BaseTask task);
        public event TaskUpdatedEvent onTaskUpdated = null;

        /// <summary>
        /// делегат события - вариант изменился
        /// </summary>        
        public delegate void VariantUpdatedEvent(TaskVariant variant);
        public event VariantUpdatedEvent onVariantUpdated = null;

        #endregion

        #region переменные

        /// <summary>
        /// шедулер
        /// </summary>
        IScheduler scheduler = null;

        /// <summary>
        /// текущая задача
        /// </summary>
        public BaseTask Task { get; set; }

        // ссылки на GUI
        ListView lwVariants;

        #endregion

        #region создание

        // конструктор
        public TaskController(ListView lwVariants, IScheduler scheduler)
        {
            Task = null;
            this.lwVariants = lwVariants;
            this.scheduler = scheduler;
        }

        #endregion

        #region работа с интерфейсом

        // получим индекс выбранного варианта
        int GetSelectedVariantIndex()
        {
            // получим выбор
            ListView.SelectedListViewItemCollection selItems = lwVariants.SelectedItems;
            if (selItems.Count == 0)
                return -1;

            // получим индекс выбранного
            return selItems[0].Index;
        }
        // зададим индекс выбранного варианта
        void SetSelectedVariantIndex(int selIndex)
        {
            // получим выбор
            if (selIndex == -1)
                lwVariants.SelectedItems.Clear();
            else
            {
                if (selIndex <= lwVariants.Items.Count - 1)
                {
                    lwVariants.Items[selIndex].Selected = true;
                    lwVariants.SelectedIndices.Add(selIndex);
                }
            }
        }
        // обновить список вариантов
        void RefreshVariantsList()
        {
            // индекс выбранного
            int selIndex = GetSelectedVariantIndex();

            // начало обновления
            lwVariants.BeginUpdate();
            // обнулим
            lwVariants.Items.Clear();
            if (Task != null)
            {
                for (int i = 0; i < Task.Variants.Count(); i++)
                {
                    // вариант
                    ListViewItem item = lwVariants.Items.Add(Task.Variants[i].ReceiveDate.Date.ToString());
                    item.ImageIndex = Task.Variants[i].IconIndex;
                    item.Tag = Task.Variants[i];

                    // столбцы
                    item.SubItems.Add(Task.Variants[i].PostedDate.Date.ToString());
                    item.SubItems.Add(Task.Variants[i].Url);
                    item.SubItems.Add(Task.Variants[i].Phone);
                    item.SubItems.Add(Task.Variants[i].Status);
                    item.SubItems.Add(Task.Variants[i].Description);
                    item.SubItems.Add(Task.Variants[i].Talk);
                }
            }
            // конец обновления
            lwVariants.EndUpdate();

            // укажем чтобы был выбор
            if (lwVariants.Items.Count > 0 && selIndex >= lwVariants.Items.Count)
                selIndex = lwVariants.Items.Count - 1;
            if (selIndex == -1 && lwVariants.Items.Count > 0)
                selIndex = 0;
            // зададим выбор
            SetSelectedVariantIndex(selIndex);
            // заджать активный враинат
            SetVariant(selIndex);
        }

        /// <summary>
        /// обновим интефейс для текущей задачи
        /// </summary>
        public void RefreshTaskGUI()
        {
            // вызвать делегат что данные текущей задачи поменялась - если он прописан
            if (onTaskUpdated!=null)
                onTaskUpdated.Invoke(Task);            

            // обновить список вариантов
            RefreshVariantsList();            
        }

        #endregion

        #region работа с задачей

        /// <summary>
        /// задать текущую задачу
        /// </summary>
        /// <param name="task"></param>
        /// <returns></returns>
        public bool SetCurrentTask(BaseTask task)
        {
            // зададим задачу
            Task = task;
            
            // обновим интефейс
            RefreshTaskGUI();

            return (Task!=null);
        }

        /// <summary>
        /// проверить варианты по задаче прямо сейчас
        /// </summary>
        /// <returns></returns>
        public bool StartNow(IScheduler scheduler)
        {
            if (Task != null)
            {
                Task.StartNow(scheduler);
                return true;
            }
            else
                return false;
        }

        /// <summary>
        /// задать настройки
        /// </summary>
        /// <returns></returns>
        public bool EditTask(string name,string url,string timeCheck,bool mailNotification,bool callNotification, TasksController tasksController)
        {
            // проверим чтобы урл был уникальный
            BaseTask task = tasksController.GetTaskByUrl(url);
            if (task!=null && task != Task)
            {
                ShowMessage.ShowInfoMessage("Задача с таким адресом уже есть");
                return false;
            }

            // перезапустим шедулер - при изменении параметров задачи
            if (global::Board_Events.Properties.Settings.Default.bAutoStartScheduler)
            {
                if (Task.IsScheduling())
                {
                    Task.StopScheduling(scheduler);
                    Task.StartScheduling(scheduler);
                }
            }

            // изменим задачу
            Task.SetTaskDatas(url, name, timeCheck, mailNotification, callNotification);

            // сохраним задачи
            tasksController.SerializeAllTasks();

            return true;
        }

        #endregion

        #region работа с вариантами задачи

        /// <summary>
        /// задать текущий вариант по индексу
        /// </summary>
        /// <param name="index"></param>
        public void SetVariant(int index = -1)
        {
            // получим текущий вариант
            TaskVariant variant;
            if (index != -1)
            {
                SetSelectedVariantIndex(index);
                return;// variant = Task.GetVariant(index);
            }
            else
                variant = GetVariant();

            // вызвать делегат что данные текущего вариант поменялась - если он прописан
            if (onVariantUpdated!=null)
                onVariantUpdated.Invoke(variant);
        }

        /// <summary>
        /// получим  текущий вариант
        /// </summary>
        /// <returns></returns>
        public TaskVariant GetVariant()
        {
            // получим выбор
            ListView.SelectedListViewItemCollection selItems = lwVariants.SelectedItems;
            if (selItems.Count == 0)
                return null;

            // получим индекс выбранного
            int iSelIndex = selItems[0].Index;

            // результат
            return Task.GetVariant(iSelIndex);
        }


        /// <summary>
        /// добавить новый вариант
        /// </summary>
        /// <returns></returns>
        public bool AddVariant()
        {
            // нет задачи
            if (Task == null)
                return false;

            // получим урл варианта
            EnterStringDlg dlg = new EnterStringDlg("Введите адрес варианта, который надо добавить", "");
            if (dlg.ShowDialog() != DialogResult.OK)
                return false;

            // добавим
            TaskVariant variant = null;
            try
            {
                variant = Task.AddVariant(dlg.m_tbString.Text, true);
            }
            catch (Exception ex)
            {
                ShowMessage.ShowWarningMessage("Вариант не был добавлен : "+ex.Message, "Предупреждение");                
            }

            // проверим вариант
            if (variant != null)
            {
                variant.CheckNow(Task, scheduler);

                // обновим список вариантов
                RefreshVariantsList();
                SetSelectedVariantIndex(lwVariants.Items.Count - 1);
                lwVariants.Focus();
            }

            // результат
            return variant!=null;

        }

        /// <summary>
        /// удалить текущий вариант
        /// </summary>
        /// <returns></returns>
        public bool DeleteVariant()
        {
            // получим текущий вариант
            TaskVariant variant = GetVariant();
            if (variant == null)
                return false;

            // вопрос об удалении
            DialogResult dialogResult = MessageBox.Show("Удалить результат c адресом "+ variant.Url, "Удалить результат", MessageBoxButtons.YesNo);
            if (dialogResult == DialogResult.No)
                return false;

            // удалим
            bool res = Task.DeleteVariant(variant);
            
            // обновим gui
            if (res)
                RefreshVariantsList();
            else
                ShowMessage.ShowWarningMessage("Вариант "+ variant.Url + " не был удален", "Предупреждение");

            // результат
            return res;
        }

        /// <summary>
        /// удалить все варианты вариант
        /// </summary>
        /// <returns></returns>
        public bool DeleteAllVariants()
        {
            // нечего удалять            
            if (Task.GetVariantsCount()==0)
                return false;

            // вопрос об удалении
            DialogResult dialogResult = MessageBox.Show("Удалить все варианты текущей задачи ("+Task.Name+")", "Удалить результаты", MessageBoxButtons.YesNo);
            if (dialogResult == DialogResult.No)
                return false;

            // удалим все
            bool res = Task.DeleteAllVariants();

            // обновим gui
            if (res)
                RefreshVariantsList();
            else
                ShowMessage.ShowWarningMessage("Варианты задачи "+Task.Name+" не были удален", "Предупреждение");

            // результат
            return res;
        }

        /// <summary>
        /// задать описание для текущего варианта
        /// </summary>
        /// <returns></returns>
        public bool SetVariantDescription()
        {
            // получим текущий вариант
            TaskVariant variant = GetVariant();
            if (variant == null)
                return false;

            // получим описание
            EnterStringDlg dlg = new EnterStringDlg("Введите описание для варианта", variant.Description);
            if (dlg.ShowDialog() != DialogResult.OK)
                return false;

            // зададим описание
            bool res = Task.SetVariantDescription(variant, dlg.m_tbString.Text);

            // обновим gui
            if (res)
                RefreshVariantsList();
            else
                ShowMessage.ShowWarningMessage("Вариант не был изменен", "Предупреждение");

            // результат
            return res;
        }

        /// <summary>
        /// задать статус для текущего варианта
        /// </summary>
        /// <returns></returns>
        public bool SetVariantStatus(string status)
        {
            // получим текущий вариант
            TaskVariant variant = GetVariant();
            if (variant == null)
                return false;

            // зададим описание
            bool res = Task.SetVariantStatus(variant, status);

            // обновим gui
            if (res)
                RefreshVariantsList();
            else
                ShowMessage.ShowWarningMessage("Вариант не был изменен", "Предупреждение");

            // результат
            return res;
        }

        /// <summary>
        /// задать иконку варианта
        /// </summary>
        /// <param name="check"></param>
        /// <returns></returns>
        public bool SetVariantIcon(string icon)
        {
            // получим текущий вариант
            TaskVariant variant = GetVariant();
            if (variant == null)
                return false;

            // зададим иконку
            bool res = Task.SetVariantIcon(variant, icon);

            // обновим gui
            if (res)
                RefreshVariantsList();
            else
                ShowMessage.ShowWarningMessage("Вариант не был изменен", "Предупреждение");

            // результат
            return res;
        }

        /// <summary>
        /// открыть текущий вариант в браузере
        /// </summary>
        /// <returns></returns>
        public bool OpenVariant()
        {
            // получим текущий вариант
            TaskVariant variant = GetVariant();
            if (variant == null)
                return false;

            // открыть вариантв браузере
            variant.OpenVariant();
            return true;
        }

        /// <summary>
        /// экспорт выбранного варианта в exel
        /// </summary>
        /// <returns></returns>
        public bool ExportVariant()
        {
            // получим текущий вариант
            TaskVariant variant = GetVariant();
            if (variant == null)
                return false;

            // получим имя файла для экпорта
            string path="";
            if (!FileTools.SelectFile(new SaveFileDialog(), "ExportVariant", ref path))
                return false;

            // сделаем экпорт
            if (!variant.ExportToExcel(path, true))
            {
                ShowMessage.ShowWarningMessage("Вариант не был экспортирован", "Предупреждение");
                return false;
            }

            return true;
        }

        /// <summary>
        /// экспорт всех вариантов в exel
        /// </summary>
        /// <returns></returns>
        public bool ExportAllVariants()
        {
            // получим текущий вариант
            TaskVariant variant = GetVariant();
            if (variant == null)
                return false;

            // получим имя файла для экпорта
            string path = "";
            if (!FileTools.SelectFile(new SaveFileDialog(), "ExportAllVariants", ref path))
                return false;

            // сделаем экпорт
            if (!Task.ExportAllVariansToExcel(path, true))
            {
                ShowMessage.ShowWarningMessage("Варианты не были экспортированы", "Предупреждение");
                return false;
            }
            
            return true;
        }

        /// <summary>
        /// отправить текущий враиант на почту
        /// </summary>
        /// <returns></returns>
        public bool EmailVariant()
        {
            // получим текущий вариант
            TaskVariant variant = GetVariant();

            // сделаем экпорт
            if (variant != null && variant.EMailTo(global::Board_Events.Properties.Settings.Default.EMailTo))
            {
                ShowMessage.ShowInfoMessage("Уведомление отправлено.");
                return true;
            }
            else
            {
                ShowMessage.ShowWarningMessage("Уведомление не было отправлено", "Предупреждение");
                return false;
            }

        }
        /// <summary>
        /// отправить все враианты на почту
        /// </summary>
        /// <returns></returns>
        public bool EmailAllVariants()
        {
            // сделаем экпорт
            if (Task!=null && Task.EMailAllVariantsTo(Properties.Settings.Default.EMailTo))
            {
                ShowMessage.ShowInfoMessage("Уведомление отправлено.");
                return true;
            }
            else
            {
                ShowMessage.ShowWarningMessage("Вариант не был отправлен", "Предупреждение");
                return false;
            }            
        }

        /// <summary>
        /// заказать обратный звонок по варианту
        /// </summary>
        public bool VariantRequestCall()
        {
            // получим текущий вариант
            TaskVariant variant = GetVariant();

            // сделаем экпорт
            if (variant != null && Task != null && variant.RequestCallNow(Task, scheduler))
            {
                return true;
            }
            else
            {
                ShowMessage.ShowWarningMessage("Звонок для телефона " + variant.Url + " не был заказан", "Предупреждение");
                return false;
            }
        }

        /// <summary>
        /// заказать обратный звонок по всем вариантам
        /// </summary>
        public bool VariantsAllRequestCall()
        {
            // сделаем экпорт
            if (Task != null && Task.VariantsAllRequestCallNow(scheduler)>0)
            {
                return true;
            }
            else
            {
                ShowMessage.ShowWarningMessage("Звонки для телефона для всех вариантов задачи " + Task.Name + " не были заказаны", "Предупреждение");
                return false;
            }
        }

        #endregion

    }
}
