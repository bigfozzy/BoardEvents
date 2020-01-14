using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using XHE._Helper.Tools.File;
using XHE._Helper.Tools.GUI;

namespace Board_Events
{
    /// <summary>
    /// форма настроек
    /// </summary>
    public partial class SettingsForm : Form
    {
        #region создание

        /// <summary>
        /// конструктор
        /// </summary>
        public SettingsForm()
        {
            InitializeComponent();
            // обновим GUI
            RefreshGUI();            
        }

        #endregion

        #region валидация

        /// <summary>
        /// обновим GUI
        /// </summary>
        void RefreshGUI()
        {
            // Проверки
            {
                // Максимальное число потоков проверок задач
                hsbMaxXHECheckThread.Value = Properties.Settings.Default.iMaxCheckThreads;
                // Автозапуск задачи по расписанию (при старте, создании и изменении новой задачи)
                chAutoStartScheduler.Checked = Properties.Settings.Default.bAutoStartScheduler;
            }

            // E-Mail
            {
                // Отсылать уведомление о новых вариантах после проверки задачи (если в задаче это разрешено)
                chSendNewVariantsEMailAfterTaskCheck.Checked = Properties.Settings.Default.SendNewvariansEMailAfterTaskCheck;
                // Почта, куда будут приходить уведомления
                tbToEMail.Text = Properties.Settings.Default.EMailTo;
            }

            // Звонки
            {
                // Адрес страницы с плагином обратного звонка CallbackKiller.com
                tbCallPlaginCode.Text = Properties.Settings.Default.CalbackKillerPluginUrl;
                // Делать прозвон при появлении нового варианта (если в задаче это разрешено)
                chCallNewVariants.Checked = Properties.Settings.Default.RequestCallToNewVariants;
            }

            // Расширеное
            {
                // Почта с которой будет отсылка уведомлений (рекомендуем зарегистрировать почту на Яндексе)
                tbFromEMail.Text = Properties.Settings.Default.EMailFrom;
                // Пароль почты с которой будут рассылаться уведомления
                tbFromPassword.Text = Properties.Settings.Default.EmailFromPassword;

                // Показывать процесс проверки задачи во внутренних браузерах
                chShowTaskCheckInXHE.Checked = Properties.Settings.Default.bShowTaskCheckingInXHE;
                // Показывать процесс заказа прозвона во внутренних браузерах
                chShowVariantCallRequestInXHE.Checked = Properties.Settings.Default.ShowVariantCallRequestInXHE;
                // Добавлять только новые варианты
                chAddOnlyNewVariants.Checked=Properties.Settings.Default.AddOnlyNewVariants;
            }

            // обновим связанные занчения
            hsbMaxXHECheckThread_Scroll(null, null);
        }

        /// <summary>
        /// проверка что все поля заполнены
        /// </summary>
        /// <returns></returns>
        bool IsAllFieldFiiled()
        {
            // почта
            if (tbToEMail.Text == "" && chSendNewVariantsEMailAfterTaskCheck.Checked)
            {
                // перейдем туда где надо ввести
                tsSettings.SelectedIndex = 1;
                tbToEMail.Focus();
                ShowMessage.ShowInfoMessage("Не задана почта на которую будут присылаться уведомления о новых вариантах (пустое поле)");

                // не закрывать
                return false;
            }
            // звонки
            if (tbCallPlaginCode.Text == "" && chCallNewVariants.Checked)
            {
                // перейдем туда где надо ввести
                tsSettings.SelectedIndex = 2;
                tbCallPlaginCode.Focus();
                ShowMessage.ShowInfoMessage("Не задана страница с плагином CallbackKiller.com (пустое поле)");

                // не закрывать
                return false;
            }
            return true;
        }

        /// <summary>
        /// задать данные из диалога
        /// </summary>
        bool SetDatas()
        {
            // проверим можно ли закрыть
            if (!IsAllFieldFiiled())
                return false; 

            // Проверки
            {
                // Максимальное число потоков проверок задач
                Properties.Settings.Default.iMaxCheckThreads= hsbMaxXHECheckThread.Value;
                // Автозапуск задачи по расписанию (при старте, создании и изменении новой задачи)
                Properties.Settings.Default.bAutoStartScheduler= chAutoStartScheduler.Checked;
            }

            // E-Mail
            {
                // Отсылать уведомление о новых вариантах после проверки задачи (если в задаче это разрешено)
                Properties.Settings.Default.SendNewvariansEMailAfterTaskCheck= chSendNewVariantsEMailAfterTaskCheck.Checked;
                // Почта, куда будут приходить уведомления
                Properties.Settings.Default.EMailTo= tbToEMail.Text;
            }

            // Звонки
            {
                // Адрес страницы с плагином обратного звонка CallbackKiller.com
                Properties.Settings.Default.CalbackKillerPluginUrl= tbCallPlaginCode.Text;
                // Делать прозвон при появлении нового варианта (если в задаче это разрешено)
                Properties.Settings.Default.RequestCallToNewVariants= chCallNewVariants.Checked;
            }

            // Расширеное
            {
                // Почта с которой будет отсылка уведомлений (рекомендуем зарегистрировать почту на Яндексе)
                Properties.Settings.Default.EMailFrom= tbFromEMail.Text;
                // Пароль почты с которой будут рассылаться уведомления
                Properties.Settings.Default.EmailFromPassword= tbFromPassword.Text;

                // Показывать процесс проверки задачи во внутренних браузерах
                Properties.Settings.Default.bShowTaskCheckingInXHE= chShowTaskCheckInXHE.Checked;
                // Показывать процесс заказа прозвона во внутренних браузерах
                Properties.Settings.Default.ShowVariantCallRequestInXHE= chShowVariantCallRequestInXHE.Checked;
                // Добавлять только новые варианты
                Properties.Settings.Default.AddOnlyNewVariants = chAddOnlyNewVariants.Checked;
            }

            return true;
        }

        #endregion

        #region обработчики

        /// <summary>
        /// выбор числа потоков проверки задач
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void hsbMaxXHECheckThread_Scroll(object sender, ScrollEventArgs e)
        {
            lblMaxXHECheckThread.Text = "Максимальное число потоков проверок задач : " + hsbMaxXHECheckThread.Value.ToString();
        }

        /// <summary>
        /// зарегистрирвоать аккаунт в calcback
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void lblRegCallbackAccount_LinkClicked(object sender, LinkLabelLinkClickedEventArgs e)
        {
            FileTools.ShowFile("http://cbkiller.ru/url/f5dd24/");
        }

        private void btnOk_Click(object sender, EventArgs e)
        {
            // задать данные
            if (!SetDatas())
            {
                this.DialogResult = DialogResult.None;
                return;
            }
        }

        #endregion
    }
}
