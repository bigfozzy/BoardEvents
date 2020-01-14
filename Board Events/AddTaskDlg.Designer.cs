namespace Board_Events
{
    partial class AddTaskDlg
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
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(AddTaskDlg));
            this.btnCancel = new System.Windows.Forms.Button();
            this.cbTimeCheck = new System.Windows.Forms.ComboBox();
            this.label7 = new System.Windows.Forms.Label();
            this.label3 = new System.Windows.Forms.Label();
            this.tbName = new System.Windows.Forms.TextBox();
            this.tbUrl = new System.Windows.Forms.TextBox();
            this.label2 = new System.Windows.Forms.Label();
            this.btnOk = new System.Windows.Forms.Button();
            this.llbRST_UA = new System.Windows.Forms.LinkLabel();
            this.llbOLX_UA = new System.Windows.Forms.LinkLabel();
            this.llbAutoRia_COM = new System.Windows.Forms.LinkLabel();
            this.SuspendLayout();
            // 
            // btnCancel
            // 
            this.btnCancel.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnCancel.DialogResult = System.Windows.Forms.DialogResult.Cancel;
            this.btnCancel.Image = ((System.Drawing.Image)(resources.GetObject("btnCancel.Image")));
            this.btnCancel.ImageAlign = System.Drawing.ContentAlignment.MiddleLeft;
            this.btnCancel.Location = new System.Drawing.Point(328, 182);
            this.btnCancel.Name = "btnCancel";
            this.btnCancel.Size = new System.Drawing.Size(144, 23);
            this.btnCancel.TabIndex = 14;
            this.btnCancel.Text = "Отмена";
            this.btnCancel.UseVisualStyleBackColor = true;
            // 
            // cbTimeCheck
            // 
            this.cbTimeCheck.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.cbTimeCheck.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cbTimeCheck.FormattingEnabled = true;
            this.cbTimeCheck.Items.AddRange(new object[] {
            "раз в 3 минуты",
            "раз в 5 минут",
            "раз в 10 минут",
            "раз в 15 минут",
            "раз в 20 минут",
            "раз в 30 минут",
            "раз в час",
            "раз в 2 часа",
            "раз в 3 часа",
            "раз в 4 часа",
            "раз в 5 часов",
            "раз в 10 часов",
            "раз в 12 часов",
            "раз в сутки",
            "раз в неделю"});
            this.cbTimeCheck.Location = new System.Drawing.Point(12, 72);
            this.cbTimeCheck.Name = "cbTimeCheck";
            this.cbTimeCheck.Size = new System.Drawing.Size(460, 21);
            this.cbTimeCheck.TabIndex = 13;
            // 
            // label7
            // 
            this.label7.AutoSize = true;
            this.label7.Location = new System.Drawing.Point(12, 9);
            this.label7.Name = "label7";
            this.label7.Size = new System.Drawing.Size(67, 13);
            this.label7.TabIndex = 11;
            this.label7.Text = "Имя задачи";
            // 
            // label3
            // 
            this.label3.AutoSize = true;
            this.label3.Location = new System.Drawing.Point(9, 56);
            this.label3.Name = "label3";
            this.label3.Size = new System.Drawing.Size(96, 13);
            this.label3.TabIndex = 12;
            this.label3.Text = "Период проверки";
            // 
            // tbName
            // 
            this.tbName.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.tbName.Location = new System.Drawing.Point(12, 25);
            this.tbName.Name = "tbName";
            this.tbName.Size = new System.Drawing.Size(460, 20);
            this.tbName.TabIndex = 9;
            // 
            // tbUrl
            // 
            this.tbUrl.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.tbUrl.Location = new System.Drawing.Point(12, 119);
            this.tbUrl.Name = "tbUrl";
            this.tbUrl.Size = new System.Drawing.Size(460, 20);
            this.tbUrl.TabIndex = 10;
            // 
            // label2
            // 
            this.label2.AutoSize = true;
            this.label2.Location = new System.Drawing.Point(9, 103);
            this.label2.Name = "label2";
            this.label2.Size = new System.Drawing.Size(121, 13);
            this.label2.TabIndex = 8;
            this.label2.Text = "Что проверять (адрес)";
            // 
            // btnOk
            // 
            this.btnOk.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnOk.DialogResult = System.Windows.Forms.DialogResult.OK;
            this.btnOk.Image = ((System.Drawing.Image)(resources.GetObject("btnOk.Image")));
            this.btnOk.ImageAlign = System.Drawing.ContentAlignment.MiddleLeft;
            this.btnOk.Location = new System.Drawing.Point(177, 182);
            this.btnOk.Name = "btnOk";
            this.btnOk.Size = new System.Drawing.Size(144, 23);
            this.btnOk.TabIndex = 14;
            this.btnOk.Text = "Создать";
            this.btnOk.UseVisualStyleBackColor = true;
            this.btnOk.Click += new System.EventHandler(this.btnOk_Click);
            // 
            // llbRST_UA
            // 
            this.llbRST_UA.AutoSize = true;
            this.llbRST_UA.Location = new System.Drawing.Point(14, 151);
            this.llbRST_UA.Name = "llbRST_UA";
            this.llbRST_UA.Size = new System.Drawing.Size(96, 13);
            this.llbRST_UA.TabIndex = 15;
            this.llbRST_UA.TabStop = true;
            this.llbRST_UA.Text = "Пример с RST.ua";
            this.llbRST_UA.LinkClicked += new System.Windows.Forms.LinkLabelLinkClickedEventHandler(this.llbRST_UA_LinkClicked);
            // 
            // llbOLX_UA
            // 
            this.llbOLX_UA.AutoSize = true;
            this.llbOLX_UA.Location = new System.Drawing.Point(14, 172);
            this.llbOLX_UA.Name = "llbOLX_UA";
            this.llbOLX_UA.Size = new System.Drawing.Size(95, 13);
            this.llbOLX_UA.TabIndex = 15;
            this.llbOLX_UA.TabStop = true;
            this.llbOLX_UA.Text = "Пример с OLX.ua";
            this.llbOLX_UA.LinkClicked += new System.Windows.Forms.LinkLabelLinkClickedEventHandler(this.llbOLX_UA_LinkClicked);
            // 
            // llbAutoRia_COM
            // 
            this.llbAutoRia_COM.AutoSize = true;
            this.llbAutoRia_COM.Location = new System.Drawing.Point(14, 194);
            this.llbAutoRia_COM.Name = "llbAutoRia_COM";
            this.llbAutoRia_COM.Size = new System.Drawing.Size(120, 13);
            this.llbAutoRia_COM.TabIndex = 15;
            this.llbAutoRia_COM.TabStop = true;
            this.llbAutoRia_COM.Text = "Пример с AutoRia.com";
            this.llbAutoRia_COM.LinkClicked += new System.Windows.Forms.LinkLabelLinkClickedEventHandler(this.llbAutoRia_COM_LinkClicked);
            // 
            // AddTaskDlg
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(6F, 13F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.CancelButton = this.btnCancel;
            this.ClientSize = new System.Drawing.Size(484, 217);
            this.Controls.Add(this.llbAutoRia_COM);
            this.Controls.Add(this.llbOLX_UA);
            this.Controls.Add(this.llbRST_UA);
            this.Controls.Add(this.btnOk);
            this.Controls.Add(this.btnCancel);
            this.Controls.Add(this.cbTimeCheck);
            this.Controls.Add(this.label7);
            this.Controls.Add(this.label3);
            this.Controls.Add(this.tbName);
            this.Controls.Add(this.tbUrl);
            this.Controls.Add(this.label2);
            this.MaximizeBox = false;
            this.MaximumSize = new System.Drawing.Size(3000, 256);
            this.MinimizeBox = false;
            this.MinimumSize = new System.Drawing.Size(500, 256);
            this.Name = "AddTaskDlg";
            this.ShowIcon = false;
            this.ShowInTaskbar = false;
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterParent;
            this.Text = "Добавить задачу";
            this.ResumeLayout(false);
            this.PerformLayout();

        }

        #endregion

        private System.Windows.Forms.Button btnCancel;
        private System.Windows.Forms.ComboBox cbTimeCheck;
        private System.Windows.Forms.Label label7;
        private System.Windows.Forms.Label label3;
        private System.Windows.Forms.TextBox tbName;
        private System.Windows.Forms.TextBox tbUrl;
        private System.Windows.Forms.Label label2;
        private System.Windows.Forms.Button btnOk;
        private System.Windows.Forms.LinkLabel llbRST_UA;
        private System.Windows.Forms.LinkLabel llbOLX_UA;
        private System.Windows.Forms.LinkLabel llbAutoRia_COM;
    }
}