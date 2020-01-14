namespace Board_Events
{
    partial class SettingsForm
    {
        /// <summary>
        /// Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        /// Clean up any resources being used.
        /// </summary>
        /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Windows Form Designer generated code

        /// <summary>
        /// Required method for Designer support - do not modify
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(SettingsForm));
            this.tsSettings = new System.Windows.Forms.TabControl();
            this.tabPage1 = new System.Windows.Forms.TabPage();
            this.label9 = new System.Windows.Forms.Label();
            this.chAutoStartScheduler = new System.Windows.Forms.CheckBox();
            this.hsbMaxXHECheckThread = new System.Windows.Forms.HScrollBar();
            this.lblMaxXHECheckThread = new System.Windows.Forms.Label();
            this.tabPage3 = new System.Windows.Forms.TabPage();
            this.label6 = new System.Windows.Forms.Label();
            this.chSendNewVariantsEMailAfterTaskCheck = new System.Windows.Forms.CheckBox();
            this.tbToEMail = new System.Windows.Forms.TextBox();
            this.label5 = new System.Windows.Forms.Label();
            this.tabPage2 = new System.Windows.Forms.TabPage();
            this.label7 = new System.Windows.Forms.Label();
            this.label2 = new System.Windows.Forms.Label();
            this.lblRegCallbackAccount = new System.Windows.Forms.LinkLabel();
            this.tbCallPlaginCode = new System.Windows.Forms.TextBox();
            this.label1 = new System.Windows.Forms.Label();
            this.chCallNewVariants = new System.Windows.Forms.CheckBox();
            this.tabPage4 = new System.Windows.Forms.TabPage();
            this.label8 = new System.Windows.Forms.Label();
            this.label4 = new System.Windows.Forms.Label();
            this.label3 = new System.Windows.Forms.Label();
            this.tbFromPassword = new System.Windows.Forms.TextBox();
            this.tbFromEMail = new System.Windows.Forms.TextBox();
            this.chShowVariantCallRequestInXHE = new System.Windows.Forms.CheckBox();
            this.chShowTaskCheckInXHE = new System.Windows.Forms.CheckBox();
            this.panel1 = new System.Windows.Forms.Panel();
            this.btnOk = new System.Windows.Forms.Button();
            this.btnCancel = new System.Windows.Forms.Button();
            this.chAddOnlyNewVariants = new System.Windows.Forms.CheckBox();
            this.tsSettings.SuspendLayout();
            this.tabPage1.SuspendLayout();
            this.tabPage3.SuspendLayout();
            this.tabPage2.SuspendLayout();
            this.tabPage4.SuspendLayout();
            this.panel1.SuspendLayout();
            this.SuspendLayout();
            // 
            // tsSettings
            // 
            this.tsSettings.Controls.Add(this.tabPage1);
            this.tsSettings.Controls.Add(this.tabPage3);
            this.tsSettings.Controls.Add(this.tabPage2);
            this.tsSettings.Controls.Add(this.tabPage4);
            this.tsSettings.DataBindings.Add(new System.Windows.Forms.Binding("SelectedIndex", global::Board_Events.Properties.Settings.Default, "SelIndexSettingsPage", true, System.Windows.Forms.DataSourceUpdateMode.OnPropertyChanged));
            this.tsSettings.Dock = System.Windows.Forms.DockStyle.Top;
            this.tsSettings.Location = new System.Drawing.Point(0, 0);
            this.tsSettings.Multiline = true;
            this.tsSettings.Name = "tsSettings";
            this.tsSettings.SelectedIndex = global::Board_Events.Properties.Settings.Default.SelIndexSettingsPage;
            this.tsSettings.Size = new System.Drawing.Size(684, 279);
            this.tsSettings.TabIndex = 4;
            // 
            // tabPage1
            // 
            this.tabPage1.Controls.Add(this.label9);
            this.tabPage1.Controls.Add(this.chAutoStartScheduler);
            this.tabPage1.Controls.Add(this.hsbMaxXHECheckThread);
            this.tabPage1.Controls.Add(this.lblMaxXHECheckThread);
            this.tabPage1.Location = new System.Drawing.Point(4, 22);
            this.tabPage1.Name = "tabPage1";
            this.tabPage1.Padding = new System.Windows.Forms.Padding(3);
            this.tabPage1.Size = new System.Drawing.Size(676, 253);
            this.tabPage1.TabIndex = 0;
            this.tabPage1.Text = "Проверки";
            this.tabPage1.UseVisualStyleBackColor = true;
            // 
            // label9
            // 
            this.label9.Location = new System.Drawing.Point(21, 22);
            this.label9.Name = "label9";
            this.label9.Size = new System.Drawing.Size(641, 33);
            this.label9.TabIndex = 7;
            this.label9.Text = "Чем больше число потоков, тем больше программа забирает ресурсов компьютера, но т" +
    "ем быстрее идут проверки задач и новых вариантов. Реккомендуется установить 5 по" +
    "токов.";
            // 
            // chAutoStartScheduler
            // 
            this.chAutoStartScheduler.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.chAutoStartScheduler.AutoSize = true;
            this.chAutoStartScheduler.Checked = true;
            this.chAutoStartScheduler.CheckState = System.Windows.Forms.CheckState.Checked;
            this.chAutoStartScheduler.Location = new System.Drawing.Point(120, 166);
            this.chAutoStartScheduler.Name = "chAutoStartScheduler";
            this.chAutoStartScheduler.Size = new System.Drawing.Size(460, 17);
            this.chAutoStartScheduler.TabIndex = 6;
            this.chAutoStartScheduler.Text = "Автозапуск задачи по расписанию (при старте, создании и изменении новой задачи)";
            this.chAutoStartScheduler.UseVisualStyleBackColor = true;
            // 
            // hsbMaxXHECheckThread
            // 
            this.hsbMaxXHECheckThread.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.hsbMaxXHECheckThread.LargeChange = 1;
            this.hsbMaxXHECheckThread.Location = new System.Drawing.Point(24, 82);
            this.hsbMaxXHECheckThread.Maximum = 10;
            this.hsbMaxXHECheckThread.Minimum = 1;
            this.hsbMaxXHECheckThread.Name = "hsbMaxXHECheckThread";
            this.hsbMaxXHECheckThread.Size = new System.Drawing.Size(639, 18);
            this.hsbMaxXHECheckThread.TabIndex = 5;
            this.hsbMaxXHECheckThread.Value = 5;
            this.hsbMaxXHECheckThread.Scroll += new System.Windows.Forms.ScrollEventHandler(this.hsbMaxXHECheckThread_Scroll);
            // 
            // lblMaxXHECheckThread
            // 
            this.lblMaxXHECheckThread.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.lblMaxXHECheckThread.AutoSize = true;
            this.lblMaxXHECheckThread.Font = new System.Drawing.Font("Microsoft Sans Serif", 12F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(204)));
            this.lblMaxXHECheckThread.Location = new System.Drawing.Point(21, 55);
            this.lblMaxXHECheckThread.Name = "lblMaxXHECheckThread";
            this.lblMaxXHECheckThread.Size = new System.Drawing.Size(433, 20);
            this.lblMaxXHECheckThread.TabIndex = 4;
            this.lblMaxXHECheckThread.Text = "Максимальное число потоков проверок задач : 5 ";
            // 
            // tabPage3
            // 
            this.tabPage3.Controls.Add(this.label6);
            this.tabPage3.Controls.Add(this.chSendNewVariantsEMailAfterTaskCheck);
            this.tabPage3.Controls.Add(this.tbToEMail);
            this.tabPage3.Controls.Add(this.label5);
            this.tabPage3.Location = new System.Drawing.Point(4, 22);
            this.tabPage3.Name = "tabPage3";
            this.tabPage3.Size = new System.Drawing.Size(676, 253);
            this.tabPage3.TabIndex = 2;
            this.tabPage3.Text = "E-Mail";
            this.tabPage3.UseVisualStyleBackColor = true;
            // 
            // label6
            // 
            this.label6.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.label6.Location = new System.Drawing.Point(17, 117);
            this.label6.Name = "label6";
            this.label6.Size = new System.Drawing.Size(651, 57);
            this.label6.TabIndex = 3;
            this.label6.Text = resources.GetString("label6.Text");
            // 
            // chSendNewVariantsEMailAfterTaskCheck
            // 
            this.chSendNewVariantsEMailAfterTaskCheck.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.chSendNewVariantsEMailAfterTaskCheck.AutoSize = true;
            this.chSendNewVariantsEMailAfterTaskCheck.Checked = true;
            this.chSendNewVariantsEMailAfterTaskCheck.CheckState = System.Windows.Forms.CheckState.Checked;
            this.chSendNewVariantsEMailAfterTaskCheck.Location = new System.Drawing.Point(20, 20);
            this.chSendNewVariantsEMailAfterTaskCheck.Name = "chSendNewVariantsEMailAfterTaskCheck";
            this.chSendNewVariantsEMailAfterTaskCheck.Size = new System.Drawing.Size(525, 17);
            this.chSendNewVariantsEMailAfterTaskCheck.TabIndex = 2;
            this.chSendNewVariantsEMailAfterTaskCheck.Text = "Отсылать уведомление о новых вариантах после проверки задачи (если в задаче это р" +
    "азрешено)";
            this.chSendNewVariantsEMailAfterTaskCheck.UseVisualStyleBackColor = true;
            // 
            // tbToEMail
            // 
            this.tbToEMail.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.tbToEMail.Location = new System.Drawing.Point(20, 80);
            this.tbToEMail.Name = "tbToEMail";
            this.tbToEMail.Size = new System.Drawing.Size(648, 20);
            this.tbToEMail.TabIndex = 1;
            // 
            // label5
            // 
            this.label5.AutoSize = true;
            this.label5.Location = new System.Drawing.Point(17, 64);
            this.label5.Name = "label5";
            this.label5.Size = new System.Drawing.Size(221, 13);
            this.label5.TabIndex = 0;
            this.label5.Text = "Почта, куда будут приходить уведомления";
            // 
            // tabPage2
            // 
            this.tabPage2.Controls.Add(this.label7);
            this.tabPage2.Controls.Add(this.label2);
            this.tabPage2.Controls.Add(this.lblRegCallbackAccount);
            this.tabPage2.Controls.Add(this.tbCallPlaginCode);
            this.tabPage2.Controls.Add(this.label1);
            this.tabPage2.Controls.Add(this.chCallNewVariants);
            this.tabPage2.Location = new System.Drawing.Point(4, 22);
            this.tabPage2.Name = "tabPage2";
            this.tabPage2.Padding = new System.Windows.Forms.Padding(3);
            this.tabPage2.Size = new System.Drawing.Size(676, 253);
            this.tabPage2.TabIndex = 1;
            this.tabPage2.Text = "Звонки";
            this.tabPage2.UseVisualStyleBackColor = true;
            // 
            // label7
            // 
            this.label7.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.label7.Location = new System.Drawing.Point(21, 193);
            this.label7.Name = "label7";
            this.label7.Size = new System.Drawing.Size(646, 47);
            this.label7.TabIndex = 4;
            this.label7.Text = resources.GetString("label7.Text");
            // 
            // label2
            // 
            this.label2.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.label2.Location = new System.Drawing.Point(21, 129);
            this.label2.Name = "label2";
            this.label2.Size = new System.Drawing.Size(646, 47);
            this.label2.TabIndex = 4;
            this.label2.Text = resources.GetString("label2.Text");
            // 
            // lblRegCallbackAccount
            // 
            this.lblRegCallbackAccount.AutoSize = true;
            this.lblRegCallbackAccount.Location = new System.Drawing.Point(21, 101);
            this.lblRegCallbackAccount.Name = "lblRegCallbackAccount";
            this.lblRegCallbackAccount.Size = new System.Drawing.Size(427, 13);
            this.lblRegCallbackAccount.TabIndex = 3;
            this.lblRegCallbackAccount.TabStop = true;
            this.lblRegCallbackAccount.Text = "Если нет аккаунта то тут можно зарегистрироваться на сервисе CallbackKiller.com";
            this.lblRegCallbackAccount.LinkClicked += new System.Windows.Forms.LinkLabelLinkClickedEventHandler(this.lblRegCallbackAccount_LinkClicked);
            // 
            // tbCallPlaginCode
            // 
            this.tbCallPlaginCode.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.tbCallPlaginCode.Location = new System.Drawing.Point(24, 68);
            this.tbCallPlaginCode.Name = "tbCallPlaginCode";
            this.tbCallPlaginCode.Size = new System.Drawing.Size(643, 20);
            this.tbCallPlaginCode.TabIndex = 2;
            // 
            // label1
            // 
            this.label1.AutoSize = true;
            this.label1.Location = new System.Drawing.Point(21, 52);
            this.label1.Name = "label1";
            this.label1.Size = new System.Drawing.Size(334, 13);
            this.label1.TabIndex = 1;
            this.label1.Text = "Адрес страницы с плагином обратного звонка CallbackKiller.com";
            // 
            // chCallNewVariants
            // 
            this.chCallNewVariants.AutoSize = true;
            this.chCallNewVariants.Location = new System.Drawing.Point(24, 22);
            this.chCallNewVariants.Name = "chCallNewVariants";
            this.chCallNewVariants.Size = new System.Drawing.Size(434, 17);
            this.chCallNewVariants.TabIndex = 0;
            this.chCallNewVariants.Text = "Делать прозвон при появлении нового варианта (если в задаче это разрешено)";
            this.chCallNewVariants.UseVisualStyleBackColor = true;
            // 
            // tabPage4
            // 
            this.tabPage4.Controls.Add(this.label8);
            this.tabPage4.Controls.Add(this.label4);
            this.tabPage4.Controls.Add(this.label3);
            this.tabPage4.Controls.Add(this.tbFromPassword);
            this.tabPage4.Controls.Add(this.tbFromEMail);
            this.tabPage4.Controls.Add(this.chAddOnlyNewVariants);
            this.tabPage4.Controls.Add(this.chShowVariantCallRequestInXHE);
            this.tabPage4.Controls.Add(this.chShowTaskCheckInXHE);
            this.tabPage4.Location = new System.Drawing.Point(4, 22);
            this.tabPage4.Name = "tabPage4";
            this.tabPage4.Size = new System.Drawing.Size(676, 253);
            this.tabPage4.TabIndex = 3;
            this.tabPage4.Text = "Расширенные";
            this.tabPage4.UseVisualStyleBackColor = true;
            // 
            // label8
            // 
            this.label8.AutoSize = true;
            this.label8.Location = new System.Drawing.Point(138, 136);
            this.label8.Name = "label8";
            this.label8.Size = new System.Drawing.Size(461, 13);
            this.label8.TabIndex = 14;
            this.label8.Text = "Если вы желаете видеть как просиходит опрос и дозвон в деталях, включите эти опци" +
    "и :";
            // 
            // label4
            // 
            this.label4.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.label4.AutoSize = true;
            this.label4.Location = new System.Drawing.Point(16, 84);
            this.label4.Name = "label4";
            this.label4.Size = new System.Drawing.Size(301, 13);
            this.label4.TabIndex = 10;
            this.label4.Text = "Пароль почты с которой будут рассылаться уведомления";
            // 
            // label3
            // 
            this.label3.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.label3.AutoSize = true;
            this.label3.Location = new System.Drawing.Point(16, 35);
            this.label3.Name = "label3";
            this.label3.Size = new System.Drawing.Size(503, 13);
            this.label3.TabIndex = 11;
            this.label3.Text = "Почта с которой будет отсылка уведомлений (рекомендуем зарегистрировать почту на " +
    "Яндексе)";
            // 
            // tbFromPassword
            // 
            this.tbFromPassword.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.tbFromPassword.Location = new System.Drawing.Point(19, 100);
            this.tbFromPassword.Name = "tbFromPassword";
            this.tbFromPassword.Size = new System.Drawing.Size(645, 20);
            this.tbFromPassword.TabIndex = 12;
            this.tbFromPassword.Text = "qtn45jgL";
            // 
            // tbFromEMail
            // 
            this.tbFromEMail.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.tbFromEMail.Location = new System.Drawing.Point(19, 51);
            this.tbFromEMail.Name = "tbFromEMail";
            this.tbFromEMail.Size = new System.Drawing.Size(645, 20);
            this.tbFromEMail.TabIndex = 13;
            this.tbFromEMail.Text = "kareenpyros3ia@yandex.ru";
            // 
            // chShowVariantCallRequestInXHE
            // 
            this.chShowVariantCallRequestInXHE.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.chShowVariantCallRequestInXHE.AutoSize = true;
            this.chShowVariantCallRequestInXHE.Location = new System.Drawing.Point(167, 187);
            this.chShowVariantCallRequestInXHE.Name = "chShowVariantCallRequestInXHE";
            this.chShowVariantCallRequestInXHE.Size = new System.Drawing.Size(354, 17);
            this.chShowVariantCallRequestInXHE.TabIndex = 9;
            this.chShowVariantCallRequestInXHE.Text = "Показывать процесс заказа прозвона во внутренних браузерах";
            this.chShowVariantCallRequestInXHE.UseVisualStyleBackColor = true;
            // 
            // chShowTaskCheckInXHE
            // 
            this.chShowTaskCheckInXHE.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.chShowTaskCheckInXHE.AutoSize = true;
            this.chShowTaskCheckInXHE.Location = new System.Drawing.Point(167, 164);
            this.chShowTaskCheckInXHE.Name = "chShowTaskCheckInXHE";
            this.chShowTaskCheckInXHE.Size = new System.Drawing.Size(353, 17);
            this.chShowTaskCheckInXHE.TabIndex = 8;
            this.chShowTaskCheckInXHE.Text = "Показывать процесс проверки задачи во внутренних браузерах";
            this.chShowTaskCheckInXHE.UseVisualStyleBackColor = true;
            // 
            // panel1
            // 
            this.panel1.Controls.Add(this.btnOk);
            this.panel1.Controls.Add(this.btnCancel);
            this.panel1.Dock = System.Windows.Forms.DockStyle.Bottom;
            this.panel1.Location = new System.Drawing.Point(0, 281);
            this.panel1.Name = "panel1";
            this.panel1.Size = new System.Drawing.Size(684, 45);
            this.panel1.TabIndex = 5;
            // 
            // btnOk
            // 
            this.btnOk.DialogResult = System.Windows.Forms.DialogResult.OK;
            this.btnOk.Image = ((System.Drawing.Image)(resources.GetObject("btnOk.Image")));
            this.btnOk.ImageAlign = System.Drawing.ContentAlignment.MiddleLeft;
            this.btnOk.Location = new System.Drawing.Point(446, 8);
            this.btnOk.Name = "btnOk";
            this.btnOk.Size = new System.Drawing.Size(110, 25);
            this.btnOk.TabIndex = 0;
            this.btnOk.Text = "Сохранить";
            this.btnOk.UseVisualStyleBackColor = true;
            this.btnOk.Click += new System.EventHandler(this.btnOk_Click);
            // 
            // btnCancel
            // 
            this.btnCancel.DialogResult = System.Windows.Forms.DialogResult.Cancel;
            this.btnCancel.Image = ((System.Drawing.Image)(resources.GetObject("btnCancel.Image")));
            this.btnCancel.ImageAlign = System.Drawing.ContentAlignment.MiddleLeft;
            this.btnCancel.Location = new System.Drawing.Point(562, 8);
            this.btnCancel.Name = "btnCancel";
            this.btnCancel.Size = new System.Drawing.Size(110, 25);
            this.btnCancel.TabIndex = 0;
            this.btnCancel.Text = "Закрыть";
            this.btnCancel.UseVisualStyleBackColor = true;
            // 
            // chAddOnlyNewVariants
            // 
            this.chAddOnlyNewVariants.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.chAddOnlyNewVariants.AutoSize = true;
            this.chAddOnlyNewVariants.Location = new System.Drawing.Point(167, 223);
            this.chAddOnlyNewVariants.Name = "chAddOnlyNewVariants";
            this.chAddOnlyNewVariants.Size = new System.Drawing.Size(207, 17);
            this.chAddOnlyNewVariants.TabIndex = 9;
            this.chAddOnlyNewVariants.Text = "Добавлять только новые варианты";
            this.chAddOnlyNewVariants.UseVisualStyleBackColor = true;
            // 
            // SettingsForm
            // 
            this.AcceptButton = this.btnOk;
            this.AutoScaleDimensions = new System.Drawing.SizeF(6F, 13F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.CancelButton = this.btnCancel;
            this.ClientSize = new System.Drawing.Size(684, 326);
            this.Controls.Add(this.panel1);
            this.Controls.Add(this.tsSettings);
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MaximumSize = new System.Drawing.Size(1000, 365);
            this.MinimizeBox = false;
            this.MinimumSize = new System.Drawing.Size(700, 365);
            this.Name = "SettingsForm";
            this.ShowIcon = false;
            this.ShowInTaskbar = false;
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterParent;
            this.Text = "Настройки программы";
            this.tsSettings.ResumeLayout(false);
            this.tabPage1.ResumeLayout(false);
            this.tabPage1.PerformLayout();
            this.tabPage3.ResumeLayout(false);
            this.tabPage3.PerformLayout();
            this.tabPage2.ResumeLayout(false);
            this.tabPage2.PerformLayout();
            this.tabPage4.ResumeLayout(false);
            this.tabPage4.PerformLayout();
            this.panel1.ResumeLayout(false);
            this.ResumeLayout(false);

        }

        #endregion
        private System.Windows.Forms.TabControl tsSettings;
        private System.Windows.Forms.TabPage tabPage1;
        private System.Windows.Forms.CheckBox chAutoStartScheduler;
        private System.Windows.Forms.HScrollBar hsbMaxXHECheckThread;
        private System.Windows.Forms.Label lblMaxXHECheckThread;
        private System.Windows.Forms.TabPage tabPage2;
        private System.Windows.Forms.LinkLabel lblRegCallbackAccount;
        private System.Windows.Forms.TextBox tbCallPlaginCode;
        private System.Windows.Forms.Label label1;
        private System.Windows.Forms.CheckBox chCallNewVariants;
        private System.Windows.Forms.TabPage tabPage3;
        private System.Windows.Forms.TextBox tbToEMail;
        private System.Windows.Forms.Label label5;
        private System.Windows.Forms.CheckBox chSendNewVariantsEMailAfterTaskCheck;
        private System.Windows.Forms.TabPage tabPage4;
        private System.Windows.Forms.CheckBox chShowVariantCallRequestInXHE;
        private System.Windows.Forms.CheckBox chShowTaskCheckInXHE;
        private System.Windows.Forms.Label label6;
        private System.Windows.Forms.Label label7;
        private System.Windows.Forms.Label label2;
        private System.Windows.Forms.Label label4;
        private System.Windows.Forms.Label label3;
        private System.Windows.Forms.TextBox tbFromPassword;
        private System.Windows.Forms.TextBox tbFromEMail;
        private System.Windows.Forms.Panel panel1;
        private System.Windows.Forms.Button btnOk;
        private System.Windows.Forms.Button btnCancel;
        private System.Windows.Forms.Label label9;
        private System.Windows.Forms.Label label8;
        private System.Windows.Forms.CheckBox chAddOnlyNewVariants;
    }
}