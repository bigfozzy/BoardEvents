using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using Board_Events.Controller;
using XHE._Helper.Tools.File;

namespace Board_Events
{
    /// <summary>
    /// диалог добавления задачи
    /// </summary>
    public partial class AddTaskDlg : Form
    {

        #region данные

        /// <summary>
        /// ссылка на контролер задач
        /// </summary>
        TasksController taskController;

        #endregion

        #region создание

        /// <summary>
        /// конструктор
        /// </summary>
        /// <param name="taskController">ссылк ана котроллер задачи</param>
        public AddTaskDlg(TasksController taskController)
        {
            // задать контролер
            this.taskController = taskController;

            // копоненты
            InitializeComponent();

            // данные по умолчанию
            cbTimeCheck.SelectedIndex = 2;
            tbName.Text = "Задача";
            tbUrl.Text = "https://www.olx.ua/transport/";
        }

        #endregion

        #region создание задачи

        /// <summary>
        /// нажатие OK
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void btnOk_Click(object sender, EventArgs e)
        {
            // добавим
            int iRes = taskController.AddTask(tbUrl.Text, tbName.Text, cbTimeCheck.Text);

            // что-то не так ?
            if (iRes < 0) 
            {            
                // сообщение    
                if (iRes==-1)
                    MessageBox.Show("Задача с таким URL уже есть");
                else if (iRes == -2)
                    MessageBox.Show("Проверьте адрес задачи. Поддерживаются следующие доски : \n\n olx.ua\n rst.ua\n autoria.com"); 

                // не закрывать
                this.DialogResult = DialogResult.None;
            }
        }

        #endregion

        #region примеры

        /// <summary>
        /// RTS.ua
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void llbRST_UA_LinkClicked(object sender, LinkLabelLinkClickedEventArgs e)
        {
            tbName.Text = "rst.ua: BAW Fenix";
            cbTimeCheck.Text = "раз в час";
            tbUrl.Text = "http://rst.ua/oldcars/baw/fenix/";        
        }

        /// <summary>
        /// OLX.ua
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void llbOLX_UA_LinkClicked(object sender, LinkLabelLinkClickedEventArgs e)
        {
            tbName.Text = "olx.ua: Daewoo Tico синий";
            cbTimeCheck.Text = "раз в час";
            tbUrl.Text = "https://www.olx.ua/transport/legkovye-avtomobili/daewoo/tico/?search%5Bfilter_enum_color%5D%5B0%5D=3";                        
        }

        /// <summary>
        /// AutoRia.com
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void llbAutoRia_COM_LinkClicked(object sender, LinkLabelLinkClickedEventArgs e)
        {
            tbName.Text = "autoria.com: Audi A1";
            cbTimeCheck.Text = "раз в час";
            tbUrl.Text = "https://auto.ria.com/search/#category_id=0&marka_id[0]=6&model_id[0]=31914&brandOrigin=276&s_yers=0&po_yers=0&currency=1&state[0]=0&city[0]=0&custom=0&under_credit=0&confiscated_car=0&damage=0&auto_repairs=0&matched_country=0&power_name=1&fuelRatesType=city&color=0&order_by=0&top=4&saledParam=0";
        }

        #endregion
    }
}
