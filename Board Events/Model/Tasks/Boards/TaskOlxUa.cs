using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using XHE;
using System.Xml.Serialization;
using Board_Events.Model.Results;
using XHE._Helper.Tools.String;
using XHE.XHE_DOM;

namespace Board_Events.Model.Tasks
{
    /// <summary>
    /// задача отслеживания olx.ua
    /// </summary>    
    class TaskOlxCom : BaseTask
    {
        #region создание

        /// <summary>
        /// конструктор
        /// </summary>
        /// <param name="url">урл задачи</param>
        /// <param name="name">имя задачи</param>
        /// <param name="time_check">период проверки вариантов</param>
        public TaskOlxCom(string url, string name, string time_check, UpdatedTaskEvent onTaskUpdated)
            : base(url, name, time_check, onTaskUpdated)
        {
            // тип
            Type = "olx.ua";
        }

        #endregion

        #region проверка вариантов

        /// <summary>
        /// разобрать и получить варианты из страницы задачи (тупо по индексу)
        /// </summary>
        /// <param name="content"></param>
        /// <returns></returns>
        public override List<TaskVariant> ParseVariants(XHEScriptMulti script)
        {
            // разобрать урл
            string content = script.GetContent(Url, 5, 7);
            // новые вараинты
            List<TaskVariant> newVariants = new List<TaskVariant>();
            
            // разберем страницу
            string prefix_begin = "detailsLink\" href=\"";
            string prefix_end = "\">";
            int index = 0;
            while (index >= 0)
            {
                // поулчим урлы задач
                string result_url = StringTools.GetSubstringByPrefix(content, prefix_begin, prefix_end, ref index);
                if (result_url == null)
                    break;
                if (result_url.IndexOf("//") == -1)
                    result_url = "http://rst.ua" + result_url;

                // добавим к результатам
                TaskVariant variant = null;
                try
                {
                    variant = CreateVariant(result_url);
                }
                catch (Exception)
                {
                }
                if (variant != null)
                    newVariants.Add(variant);
            }


            return newVariants;
        }

        /// <summary>
        /// разобрать телефон варианта
        /// </summary>
        /// <param name="variant"></param>
        /// <param name="variantContent"></param>
        /// <returns></returns>
        public override bool ParseVariantPhone(TaskVariant variant, XHEScriptMulti script)
        {
            // получим содержимое
            script.browser.set_wait_params(5, 1);
            script.browser.navigate(variant.Url);
            // пауза
            XHEScriptMulti.sleep(1);

            // получим данные
            XHEInterface phone=script.div.get_by_attribute("class", "contactitem", false);
            phone.focus();
            phone.click();
            
            // пауза
            XHEScriptMulti.sleep(1);

            // получим телефон
            string phoneStr=phone.get_inner_text();
            if (phoneStr != "false")
            {
                phoneStr = phoneStr.Replace("Показать", "");
                phoneStr = phoneStr.Replace("\r\n\r\n", "\t");
                string[] phoneStrArr = phoneStr.Split('\t');
                phoneStr = "";
                for (int i = 0; i < 1; i++)
                {
                    /*if (phoneStrArr[0] =="0")
                        phoneStrArr[i] = "+38(" + phoneStrArr[i];
                    int index = phoneStrArr[i].IndexOf(" ");
                    phoneStrArr[i]=phoneStrArr[i].Insert(index, ")");*/
                    phoneStr = phoneStrArr[i] + "\t";
                }
            }
            else
                phoneStr = "";

            // телефон
            variant.Phone = phoneStr;
            if (variant.Phone == null)
                variant.Phone = "";

            // получим содержимое
            string variantContent = script.webpage.get_body();

            // поулчим дату постинга
            string prefix_0 = "Добавлено:";
            int index = variantContent.IndexOf(prefix_0);
            if (index==-1)
            {
                prefix_0 = "Опубликовано с";
                index = variantContent.IndexOf(prefix_0);
            }
            string prefix_begin = ",";
            string prefix_end = ",";
            string str = StringTools.GetSubstringByPrefix(variantContent, prefix_begin, prefix_end, ref index);
            variant.PostedDate = DateTime.Parse(str);
            

            return variant.Phone != "";
        }

        #endregion       
    }
}
