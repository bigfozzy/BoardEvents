using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using XHE;
using System.Xml.Serialization;
using XHE._Helper.Tools.String;
using Board_Events.Model.Results;

namespace Board_Events.Model.Tasks
{
    /// <summary>
    /// задача отслеживания auto.ria.com
    /// </summary>    
    class TaskAutoRiaCom : BaseTask
    {
        #region создание

        /// <summary>
        /// конструктор
        /// </summary>
        /// <param name="url">урл задачи</param>
        /// <param name="name">имя задачи</param>
        /// <param name="time_check">период проверки вариантов</param>
        public TaskAutoRiaCom(string url, string name, string time_check, UpdatedTaskEvent onTaskUpdated)
            : base(url, name, time_check, onTaskUpdated)
        {
            // тип
            Type = "autoria.com";
        }

        #endregion
        
        #region проверка враиантов

        /// <summary>
        /// разобрать и получить варианты из страницы задачи (тупо по индексу)
        /// </summary>
        /// <param name="content"></param>
        /// <returns></returns>
        public override List<TaskVariant> ParseVariants(XHEScriptMulti script)
        {
            // разобрать урл
            string content = script.GetContent(Url, 10, 20);
            // новые вараинты
            List<TaskVariant> newVariants = new List<TaskVariant>();

            // перейдем к блоку объявлений
            string prefix0 = "<a title=\"Поднять вверх при поиске\"";
            int index = content.IndexOf(prefix0);
            if (index == -1)
                index = 0;

            int maxIndex = content.IndexOf("<div class=\"saved-search\" id=\"searchHistoryViewed\">");
            if (index >= 0)
            {
                string prefix_begin = "class=\"address\" href=\"";
                string prefix_end = "\" ";
                while (index > 0)
                {
                    // поулчим урлы задач
                    string result_url = StringTools.GetSubstringByPrefix(content, prefix_begin, prefix_end, ref index);

                    // дальше не надо
                    if (maxIndex != -1 && index > maxIndex)
                        break;
                    if (result_url == null)
                        break;
                    if (result_url.Length > 0 && result_url[0] == '/')
                        result_url = "https://auto.ria.com" + result_url;
                    // не обрабатываем левые урлы
                    if (result_url.Substring(0, 20).ToLower() != "https://auto.ria.com")
                        continue;
                    int index1 = result_url.IndexOf("\"");
                    if (index1 > 0)
                        result_url = result_url.Substring(0, index1 - 1);
                    if (result_url.IndexOf("?") != -1)
                        continue;
                    if (result_url.IndexOf("newauto") != -1)
                        continue;

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
            }

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
            string variantContent = script.GetContent(variant.Url, 2, 5);

            // разберем
            string prefix_begin ="<span class=\"phone-wrap\">";
            string prefix_end = "</span>";
            int index = 0;
            variant.Phone = StringTools.GetSubstringByPrefix(variantContent, prefix_begin, prefix_end, ref index);
            if (variant.Phone == null)
                variant.Phone = "";

            // поулчим дату постинга
            index = 0;
            prefix_begin = "Объявление добавлено ";
            prefix_end = "\"";
            string str = StringTools.GetSubstringByPrefix(variantContent, prefix_begin, prefix_end, ref index);
            if (str != null)
            {
                try
                {
                    int index1 = str.IndexOf(" ");
                    string str1 = str;//
                    int day = 0;
                    if (index1 != -1)
                    {
                        try
                        {
                            str1 = str1.Substring(0, index1);
                            day = Convert.ToInt32(str1);
                        }
                        catch(Exception)
                        {

                        }
                    }
                    int month = -1;
                    if (str.IndexOf("января") != -1)
                        month = 1;
                    else if (str.IndexOf("февраля") != -1)
                        month = 2;
                    else if (str.IndexOf("марта") != -1)
                        month = 3;
                    else if (str.IndexOf("апреля") != -1)
                        month = 4;
                    else if (str.IndexOf("мая") != -1)
                        month = 5;
                    else if (str.IndexOf("июня") != -1)
                        month = 6;
                    else if (str.IndexOf("июля") != -1)
                        month = 7;
                    else if (str.IndexOf("августа") != -1)
                        month = 8;
                    else if (str.IndexOf("сентября") != -1)
                        month = 9;
                    else if (str.IndexOf("октября") != -1)
                        month = 10;
                    else if (str.IndexOf("ноября") != -1)
                        month = 11;
                    else if (str.IndexOf("декабря") != -1)
                        month = 12;
                    else if (str.IndexOf("сегодня") != -1 || str.IndexOf("часов") != -1 || str.IndexOf("час") != -1 || str.IndexOf("мин") != -1)
                    {
                        month = DateTime.Now.Month;
                        day = DateTime.Now.Day;
                    }
                    else if (str.IndexOf("вчера") != -1)
                    {
                        month = (DateTime.Now - new TimeSpan(24, 0, 0)).Month;
                        day = (DateTime.Now - new TimeSpan(24, 0, 0)).Day;
                    }
                    else if (str.IndexOf("понедельник") != -1)
                    {
                        month = (DateTime.Now - new TimeSpan(24, 0, 0)).Month;
                        day = (DateTime.Now - new TimeSpan(24, 0, 0)).Day;
                    }
                    else if (str.IndexOf("вторник") != -1)
                    {
                        month = (DateTime.Now - new TimeSpan(24, 0, 0)).Month;
                        day = (DateTime.Now - new TimeSpan(24, 0, 0)).Day;
                    }
                    else if (str.IndexOf("сред") != -1)
                    {
                        month = (DateTime.Now - new TimeSpan(24, 0, 0)).Month;
                        day = (DateTime.Now - new TimeSpan(24, 0, 0)).Day;
                    }
                    else if (str.IndexOf("четверг") != -1)
                    {
                        month = (DateTime.Now - new TimeSpan(24, 0, 0)).Month;
                        day = (DateTime.Now - new TimeSpan(24, 0, 0)).Day;
                    }
                    else if (str.IndexOf("пятниц") != -1)
                    {
                        month = (DateTime.Now - new TimeSpan(24, 0, 0)).Month;
                        day = (DateTime.Now - new TimeSpan(24, 0, 0)).Day;
                    }
                    else if (str.IndexOf("суббот") != -1)
                    {
                        month = (DateTime.Now - new TimeSpan(24, 0, 0)).Month;
                        day = (DateTime.Now - new TimeSpan(24, 0, 0)).Day;
                    }
                    else if (str.IndexOf("воскресенье") != -1)
                    {
                        month = (DateTime.Now - new TimeSpan(24, 0, 0)).Month;
                        day = (DateTime.Now - new TimeSpan(24, 0, 0)).Day;
                    }
                    int year = DateTime.Now.Year;
                    if (month > DateTime.Now.Month)
                        year--;
                    variant.PostedDate = DateTime.Parse(day.ToString() + "." + month.ToString() + "." + year.ToString());
                }
                catch (Exception)
                {
                }
            }

            return variant.Phone!="";
        }

        #endregion        
    }
}
