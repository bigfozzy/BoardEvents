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
    class TaskOlxUa : BaseTask
    {
        #region создание

        /// <summary>
        /// конструктор
        /// </summary>
        /// <param name="url">урл задачи</param>
        /// <param name="name">имя задачи</param>
        /// <param name="time_check">период проверки вариантов</param>
        public TaskOlxUa(string url, string name, string time_check, UpdatedTaskEvent onTaskUpdated)
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
            // счетчик адресов, которые не удалось разобрать - попадет в лог
            int skipped = 0;
            
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
                catch (Exception ex)
                {
                    // не молчим: иначе сломанный парсер выглядит как
                    // «новых объявлений нет»
                    skipped++;
                    LogCheck("пропущен адрес " + result_url + " : " + ex.Message);
                }
                if (variant != null)
                    newVariants.Add(variant);

                // страница закончилась - дальше парсить нечего
                if (index <= 0)
                    break;
            }


            if (skipped > 0)
                LogCheck("пропущено адресов, которые не удалось разобрать : " + skipped.ToString());

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
            script.browser.set_wait_params(10, 3);
            script.browser.navigate(variant.Url);

            // получим данные
            // Блока с контактами может не быть - страница изменилась
            // или объявление уже снято
            if (!script.div.wait_element_exist_by_attribute("class", "contactitem", false, ""))
            {
                variant.Phone = "";
                return false;
            }

            XHEInterface phone = script.div.get_by_attribute("class", "contactitem", false);
            phone.focus();
            phone.click();

            // телефон раскрывается после клика - ждем появления цифр,
            // фиксированная пауза здесь означала пустой результат
            // на медленной сети
            string phoneStr = "";
            for (int attempt = 0; attempt < 10; attempt++)
            {
                string candidate = phone.get_inner_text();
                if (candidate != "false" && HasDigit(candidate))
                {
                    phoneStr = candidate;
                    break;
                }

                XHEScriptMulti.sleep(1);
            }

            if (phoneStr != "")
            {
                phoneStr = phoneStr.Replace("Показать", "");
                phoneStr = phoneStr.Replace("\r\n\r\n", "\t");
                string[] phoneStrArr = phoneStr.Split('\t');

                // разделителей могло не оказаться - тогда остается исходная строка
                phoneStr = (phoneStrArr.Length > 0 && phoneStrArr[0] != "") ? phoneStrArr[0] : phoneStr.Trim();
            }

            // телефон
            variant.Phone = phoneStr;

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
            if (index != -1)
            {
                string prefix_begin = ",";
                string prefix_end = ",";
                string str = StringTools.GetSubstringByPrefix(variantContent, prefix_begin, prefix_end, ref index);
                DateTime postedDate;
                if (DateTime.TryParse(str, out postedDate))
                    variant.PostedDate = postedDate;
            }

            return variant.Phone != "";
        }

        /// <summary>
        /// есть ли в строке хоть одна цифра
        /// </summary>
        /// <param name="text"></param>
        /// <returns></returns>
        static bool HasDigit(string text)
        {
            if (string.IsNullOrEmpty(text))
                return false;

            for (int i = 0; i < text.Length; i++)
                if (Char.IsDigit(text[i]))
                    return true;

            return false;
        }

        #endregion       
    }
}
