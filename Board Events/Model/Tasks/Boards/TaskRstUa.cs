using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using XHE;
using System.Xml.Serialization;
using XHE._Helper.Tools.File;
using XHE._Helper.Tools.String;
using Board_Events.Model.Results;
using XHE.XHE_DOM;

namespace Board_Events.Model.Tasks
{
    /// <summary>
    /// задача отслеживания olx.ua
    /// </summary>    
    class TaskRstUa : BaseTask
    {
        #region создание

        /// <summary>
        /// конструктор
        /// </summary>
        /// <param name="url">урл задачи</param>
        /// <param name="name">имя задачи</param>
        /// <param name="time_check">период проверки вариантов</param>
        public TaskRstUa(string url, string name, string time_check,  UpdatedTaskEvent onTaskUpdated)
            : base(url, name, time_check, onTaskUpdated)
        {
            // тип
            Type = "rst.ua";
        }

        #endregion

        #region проверка варинтов

        /// <summary>
        /// разобрать и получить варианты из страницы задачи (тупо по индексу)
        /// </summary>
        /// <param name="content"></param>
        /// <returns></returns>
        public override List<TaskVariant> ParseVariants(XHEScriptMulti script)
        {
            // разобрать урл
            string content = script.GetContent(Url, 10, 7);

            // новые варианты
            List<TaskVariant> newVariants = new List<TaskVariant>();

            // разберем страницу
            string prefix_begin = "class=\"rst-ocb-i-a\" href=\"";
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

            // результат
            return newVariants;
        }

        /// <summary>
        /// разобрать телефон варианта (тупо по индексу)
        /// </summary>
        /// <param name="variant"></param>
        /// <param name="variantContent"></param>
        /// <returns></returns>
        public override bool ParseVariantPhone(TaskVariant variant, XHEScriptMulti script)
        {
            // получим содержимое
            string variantContent = script.GetContent(variant.Url, 3, 5);

            XHEInterface tableTel = script.table.get_by_inner_html("тел.", false);
            if (tableTel.is_exist())
            {
                variant.Phone=tableTel.get_inner_text();
                if (variant.Phone == "false")
                    variant.Phone = "";
                variant.Phone = variant.Phone.Replace("тел.:", "");
                variant.Phone = variant.Phone.Replace("\n", "");
                variant.Phone = variant.Phone.Replace("\r", "");
            }
            else
            {
                // начиная с чего искать
                string prefix_0 = "rst-page-oldcars-item-option-block-container";
                // разберем
                string prefix_begin = "тел.: ";
                string prefix_end = "<br>";
                int index = variantContent.IndexOf(prefix_0);
                if (index >= 0)
                    variant.Phone = StringTools.GetSubstringByPrefix(variantContent, prefix_begin, prefix_end, ref index);
                if (variant.Phone == null)
                    variant.Phone = "";
            }

            // обрежем лишнее
            variant.Phone = variant.Phone.Replace("&nbsp;", "");
            int index2 = variant.Phone.IndexOf("<");
            if (index2 > 0)
                variant.Phone = variant.Phone.Substring(0, index2);

            // укажем страну
            if (variant.Phone != "")
            {
                if (variant.Phone[0] == '0')
                    variant.Phone = "+38" + variant.Phone;
                if (variant.Phone[0] != '+')
                    variant.Phone = "+" + variant.Phone;
            }

            // поулчим дату постинга
            index2 = 0;
            string prefix_begin2 ="<span class=\"rst-uix-black\">";
            string prefix_end2 = "</span>";
            variant.PostedDate = DateTime.Parse(StringTools.GetSubstringByPrefix(variantContent, prefix_begin2, prefix_end2, ref index2));

            return variant.Phone != "";
        }

        #endregion
    }
}
