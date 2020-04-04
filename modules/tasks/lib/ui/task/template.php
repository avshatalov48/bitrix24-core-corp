<?
/**
 * This class contains ui helper for task/template entity
 *
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Tasks\UI\Task;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/tasks/tools.php'); // todo: move to __FILE__

final class Template
{
	public static function makeActionUrl($path, $templateId = 0, $actionId = 'edit')
	{
		if((string) $path == '')
		{
			return '';
		}

		$templateId = intval($templateId);
		$actionId = $actionId == 'edit' ? 'edit' : 'view';

		return \CComponentEngine::MakePathFromTemplate($path, array(
			"template_id" => $templateId,
			"action" => $actionId,
			"TEMPLATE_ID" => $templateId,
			"ACTION" => $actionId,
		));
	}

	public static function makeReplicationPeriodString($arParams)
	{
		$strRepeat = '';
		switch ($arParams["PERIOD"])
		{
			case "daily":
				if (intval($arParams["EVERY_DAY"]) == 1)
				{
					$strRepeat = GetMessage("TASKS_EVERY_DAY");
				}
				else
				{
					$strRepeat = str_replace("#NUM#", intval($arParams["EVERY_DAY"]), GetMessage("TASKS_EVERY_N_DAY".taskMessSuffix(intval($arParams["EVERY_DAY"]))));
				}
				break;
			case "weekly":
				if (intval($arParams["EVERY_WEEK"]) == 1)
				{
					$strRepeat = GetMessage("TASKS_EVERY_WEEK");
				}
				else
				{
					$strRepeat = str_replace("#NUM#", intval($arParams["EVERY_WEEK"]), GetMessage("TASKS_EVERY_N_WEEK".taskMessSuffix(intval($arParams["EVERY_WEEK"]))));
				}
				if (sizeof($arParams["WEEK_DAYS"]))
				{
					$arDays = array();
					foreach($arParams["WEEK_DAYS"] as $day)
					{
						if ($day < 8 && $day > 0)
						{
							$arDays[] = GetMessage("TASKS_REPEAT_DAY_2_".($day - 1));
						}
					}
					if (sizeof($arDays))
					{
						$strRepeat .= str_replace("#DAYS#", implode(", ", $arDays), GetMessage("TASKS_AT_WEEK_DAYS"));
					}
				}
				break;
			case "monthly":
				if ($arParams["MONTHLY_TYPE"] == 1)
				{
					$strRepeat = str_replace("#DAY#", $arParams["MONTHLY_DAY_NUM"], GetMessage("TASKS_MONTHLY_DAY_NUM".taskMessSuffix(intval($arParams["MONTHLY_DAY_NUM"]))));
					if (intval($arParams["MONTHLY_MONTH_NUM_1"]) < 2)
					{
						$strRepeat .= " ".GetMessage("TASKS_EVERY_MONTH");
					}
					else
					{
						$strRepeat .= " ".str_replace("#NUM#", intval($arParams["MONTHLY_MONTH_NUM_1"]), GetMessage("TASKS_EVERY_N_MONTH".taskMessSuffix(intval($arParams["MONTHLY_MONTH_NUM_1"]))));
					}
				}
				else
				{
					$arParams["MONTHLY_WEEK_DAY"] = intval($arParams["MONTHLY_WEEK_DAY"]);
					if ($arParams["MONTHLY_WEEK_DAY"] < 0 || $arParams["MONTHLY_WEEK_DAY"] > 6)
					{
						$arParams["MONTHLY_WEEK_DAY"] = 0;
					}
					$arParams["MONTHLY_WEEK_DAY_NUM"] = intval($arParams["MONTHLY_WEEK_DAY_NUM"]);
					if ($arParams["MONTHLY_WEEK_DAY_NUM"] < 0 || $arParams["MONTHLY_WEEK_DAY_NUM"] > 4)
					{
						$arParams["MONTHLY_WEEK_DAY_NUM"] = 0;
					}
					$strRepeat = GetMessage("TASKS_REPEAT_DAY_NUM_".$arParams["MONTHLY_WEEK_DAY_NUM"])." ".GetMessage("TASKS_REPEAT_DAY_".$arParams["MONTHLY_WEEK_DAY"]);
					if (intval($arParams["MONTHLY_MONTH_NUM_2"]) < 2)
					{
						$strRepeat .= " ".GetMessage("TASKS_EVERY_MONTH");
					}
					else
					{
						$strRepeat .= " ".str_replace("#NUM#", intval($arParams["MONTHLY_MONTH_NUM_2"]), GetMessage("TASKS_EVERY_N_MONTH_2".taskMessSuffix(intval($arParams["MONTHLY_MONTH_NUM_2"]))));
					}
				}
				break;
			case "yearly":
				if ($arParams["YEARLY_TYPE"] == 1)
				{
					$arParams["YEARLY_MONTH_1"] = intval($arParams["YEARLY_MONTH_1"]);
					if ($arParams["YEARLY_MONTH_1"] > 11 || $arParams["YEARLY_MONTH_1"] < 0)
					{
						$arParams["YEARLY_MONTH_1"] = 0;
					}
					$strRepeat = str_replace(array("#NUM#", "#MONTH#"), array($arParams["YEARLY_DAY_NUM"], GetMessage("TASKS_REPEAT_MONTH_".$arParams["YEARLY_MONTH_1"])), GetMessage("TASKS_EVERY_N_DAY_OF_MONTH".taskMessSuffix(intval($arParams["YEARLY_DAY_NUM"]))));
				}
				else
				{
					$arParams["YEARLY_MONTH_2"] = intval($arParams["YEARLY_MONTH_2"]);
					if ($arParams["YEARLY_MONTH_2"] > 11 || $arParams["YEARLY_MONTH_2"] < 0)
					{
						$arParams["YEARLY_MONTH_2"] = 0;
					}
					$arParams["YEARLY_WEEK_DAY"] = intval($arParams["YEARLY_WEEK_DAY"]);
					if ($arParams["YEARLY_WEEK_DAY"] < 0 || $arParams["YEARLY_WEEK_DAY"] > 6)
					{
						$arParams["YEARLY_WEEK_DAY"] = 0;
					}
					$arParams["YEARLY_WEEK_DAY_NUM"] = intval($arParams["YEARLY_WEEK_DAY_NUM"]);
					if ($arParams["YEARLY_WEEK_DAY_NUM"] < 0 || $arParams["YEARLY_WEEK_DAY_NUM"] > 4)
					{
						$arParams["YEARLY_WEEK_DAY_NUM"] = 0;
					}
					$strRepeat = str_replace(array("#NUM#", "#DAY#", "#MONTH#"), array(GetMessage("TASKS_REPEAT_DAY_NUM_".$arParams["YEARLY_WEEK_DAY_NUM"]), GetMessage("TASKS_REPEAT_DAY_".$arParams["YEARLY_WEEK_DAY"]), GetMessage("TASKS_REPEAT_MONTH_".$arParams["YEARLY_MONTH_2"])), GetMessage("TASKS_AT_N_DAY_OF_MONTH".taskMessSuffix(intval($arParams["TASKS_REPEAT_DAY_NUM_"]))));
				}
				break;
		}

		return $strRepeat;
	}
}