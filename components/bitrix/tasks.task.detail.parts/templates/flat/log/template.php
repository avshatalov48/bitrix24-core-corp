<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\UI;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CBitrixComponent $component */

Loc::loadMessages(__FILE__);
$commentAndResult = ['COMMENT', 'COMMENT_EDIT', 'COMMENT_DEL', 'RESULT', 'RESULT_EDIT', 'RESULT_REMOVE'];
if (!function_exists('lambda_sgkrg456d_funcFormatForHuman') )
{
	function lambda_sgkrg456d_funcFormatForHuman($seconds)
	{
		if ($seconds === NULL || $seconds === "")
		{
			return "";
		}

		$hours = intval($seconds / 3600);

		if ($hours < 24)
		{
			$duration = $hours . " " . Loc::getMessagePlural("TASKS_TASK_DURATION_HOURS", $hours);
		}
		elseif ($houresInResid = $hours % 24)
		{
			$days = intval($hours / 24);
			$duration = $days
				. " "
				. Loc::getMessagePlural("TASKS_TASK_DURATION_DAYS", $days)
				. " "
				. intval($houresInResid)
				. " "
				. Loc::getMessagePlural("TASKS_TASK_DURATION_HOURS", intval($houresInResid));
		}
		else
		{
			$days = (int) ($hours / 24);
			$duration = $days . " " . Loc::getMessagePlural("TASKS_TASK_DURATION_DAYS", $days);
		}

		return ($duration);
	}
}

if (!function_exists('lambda_sgkrg457d_funcFormatForHumanMinutes') )
{
	function lambda_sgkrg457d_funcFormatForHumanMinutes($in, $bDataInSeconds = false)
	{
		if ($in === NULL || $in === "")
		{
			return '';
		}

		if ($bDataInSeconds)
		{
			$minutes = (int)($in / 60);
		}

		$hours = (int) ($minutes / 60);

		if ($minutes < 60)
		{
			$duration = $minutes . ' ' . Loc::getMessagePlural(
					'TASKS_TASK_DURATION_MINUTES',
					(int)$minutes
				);
		}
		elseif ($minutesInResid = $minutes % 60)
		{
			$duration = $hours
				. ' '
				. Loc::getMessagePlural("TASKS_TASK_DURATION_HOURS", $hours)
				. ' '
				. (int) $minutesInResid
				. ' '
				. Loc::getMessagePlural("TASKS_TASK_DURATION_MINUTES", (int)$minutesInResid);
		}
		else
		{
			$duration = $hours . " " . Loc::getMessagePlural("TASKS_TASK_DURATION_HOURS", $hours);
		}

		if ($bDataInSeconds && ($in < 3600))
		{
			if ($secondsInResid = $in % 60)
			{
				$duration .= ' ' . (int) $secondsInResid
					. ' '
					. Loc::getMessagePlural("TASKS_TASK_DURATION_SECONDS", (int)$secondsInResid);
			}
		}

		return ($duration);
	}
}

if (!function_exists('prepareUfCrmEntities'))
{
	function prepareUfCrmEntities(array $entities): string
	{
		$elements = [];

		foreach ($entities as $entity)
		{
			[$type, $id] = explode('_', $entity);
			$typeId = CCrmOwnerType::ResolveID($type);
			$url = CCrmOwnerType::GetEntityShowPath($typeId, $id);
			$title = CCrmOwnerType::GetCaption($typeId, $id);
			$elements[] = "<a href='{$url}'>{$title}</a>";
		}

		return implode(', ', $elements);
	}
}

$logRecords = isset($arParams["TEMPLATE_DATA"]["DATA"]["TASK"]["SE_LOG"]) ? $arParams["TEMPLATE_DATA"]["DATA"]["TASK"]["SE_LOG"] : array();
$groups = $arParams["TEMPLATE_DATA"]["DATA"]["GROUP"];
$users = $arParams["TEMPLATE_DATA"]["DATA"]["USER"];
$relatedTasks = $arParams["TEMPLATE_DATA"]["DATA"]["RELATED_TASK"];
$taskData = $arParams["TEMPLATE_DATA"]["DATA"]["TASK"];

$trackedFields = CTaskLog::getTrackedFields();
?>

<table id="task-log-table" class="task-log-table">
	<col class="task-log-date-column" />
	<col class="task-log-author-column" />
	<col class="task-log-where-column" />
	<col class="task-log-what-column" />

	<tr>
		<th class="task-log-date-column"><?=Loc::getMessage("TASKS_LOG_WHEN")?></th>
		<th class="task-log-author-column"><?=Loc::getMessage("TASKS_LOG_WHO")?></th>
		<th class="task-log-where-column"><?=Loc::getMessage("TASKS_LOG_WHERE")?></th>
		<th class="task-log-what-column"><?=Loc::getMessage("TASKS_LOG_WHAT")?></th>
	</tr>

	<? foreach ($logRecords as $record):

		$authorName = tasksFormatNameShort(
			$record["USER_NAME"],
			$record["USER_LAST_NAME"],
			$record["USER_LOGIN"],
			$record["USER_SECOND_NAME"],
			$arParams["NAME_TEMPLATE"],
			true
		);

		$authorUrl = CComponentEngine::makePathFromTemplate(
			$arParams["PATH_TO_USER_PROFILE"],
			array("user_id" => $record["USER_ID"])
		);
	?>
	<tr>
		<td class="task-log-date-column">
			<span class="task-log-date"><?=FormatDateFromDB($record["CREATED_DATE"]);?></span>
		</td>
		<td class="task-log-author-column">
			<?
			if ($authorUrl !== "")
			{
				?><a class="task-log-author" target="_top" href="<?=$authorUrl?>"><?=$authorName?></a><?
			}
			else
			{
				?><?=$authorName?><?
			}
			?>
		</td>
		<td class="task-log-where-column">

			<?
			$fieldName = Loc::getMessage("TASKS_LOG_".$record["FIELD"]);
			if($record['FIELD'] == \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode())
			{
				$fieldName = Loc::getMessage('TASKS_LOG_FILES');
			}
			elseif($fieldName == '' && ($trackedFields[$record["FIELD"]]['TITLE'] ?? '') != '')
			{
				$fieldName = $trackedFields[$record["FIELD"]]['TITLE'];
			}
			?>

			<span class="task-log-where"><?=htmlspecialcharsbx($fieldName)?><?
			if ($record["FIELD"] == "DELETED_FILES")
			{
				?>: <?=htmlspecialcharsbx($record["FROM_VALUE"])?><?
			}
			elseif ($record["FIELD"] == "NEW_FILES")
			{
				?>: <?=htmlspecialcharsbx($record["TO_VALUE"])?><?
			}
			elseif (in_array($record['FIELD'], $commentAndResult)
			)
			{
				$link = \Bitrix\Tasks\UI\Task::makeActionUrl($arParams["PATH_TO_TASKS_TASK"], $taskData["ID"], 'view');
				$link = \Bitrix\Tasks\Integration\Forum\Comment::makeUrl($link, $record["TO_VALUE"]);

				if (!$arParams["PUBLIC_MODE"])
				{
					if ($record["FIELD"] != "COMMENT_DEL")
					{
						?> <a class="task-log-link" href="<?=$link?>">#<?=$record["TO_VALUE"]?></a><?
					}
					else
					{
						?> #<?=$record["TO_VALUE"]?><?
					}
				}
			}
			?></span>
		</td>
		<td class="task-log-what-column"><span class="task-log-what"><?
		switch($record["FIELD"])
		{
			case "DURATION_PLAN_SECONDS":
				echo lambda_sgkrg456d_funcFormatForHuman($record["FROM_VALUE"]);
				?><span class="task-log-arrow">&rarr;</span><?
				echo lambda_sgkrg456d_funcFormatForHuman($record["TO_VALUE"]);
				break;

			case "TITLE":
			case "DURATION_PLAN":
			case "CHECKLIST_ITEM_CREATE":
			case "CHECKLIST_ITEM_REMOVE":
			case "CHECKLIST_ITEM_RENAME":
			case "CHECKLIST_ITEM_MAKE_IMPORTANT":
			case "CHECKLIST_ITEM_MAKE_UNIMPORTANT":
			case "STAGE":
				if(\Bitrix\Tasks\UI\Task\CheckList::checkIsSeparatorValue($record["FROM_VALUE"]))
				{
					$from = Loc::getMessage('TASKS_TTDP_TEMPLATE_LOG_SEPARATOR');
				}
				else
				{
					$from = UI::convertBBCodeToHtmlSimple($record["FROM_VALUE"]);
				}

				if(\Bitrix\Tasks\UI\Task\CheckList::checkIsSeparatorValue($record["TO_VALUE"]))
				{
					$to = Loc::getMessage('TASKS_TTDP_TEMPLATE_LOG_SEPARATOR');
				}
				else
				{
					$to = UI::convertBBCodeToHtmlSimple($record["TO_VALUE"]);
				}

				?><?=$from?><span class="task-log-arrow">&rarr;</span><?=$to?><?
				break;

			case "CHECKLIST_ITEM_UNCHECK":
				echo '<span style="text-decoration:line-through; color: grey;">'.UI::convertBBCodeToHtmlSimple($record["FROM_VALUE"]).'</span>';
				?><span class="task-log-arrow">&rarr;</span><?= UI::convertBBCodeToHtmlSimple($record["TO_VALUE"])?>
				<?
				break;

			case 'CHECKLIST_ITEM_CHECK':
				echo UI::convertBBCodeToHtmlSimple($record["FROM_VALUE"]);
				?><span class="task-log-arrow">&rarr;</span><?
				echo '<span style="text-decoration:line-through; color: grey;">'.UI::convertBBCodeToHtmlSimple($record["TO_VALUE"]).'</span>';
				break;

			case "DURATION_FACT":
				echo lambda_sgkrg457d_funcFormatForHumanMinutes($record["FROM_VALUE"]);
				?><span class="task-log-arrow">&rarr;</span><?
				echo lambda_sgkrg457d_funcFormatForHumanMinutes($record["TO_VALUE"]);
				break;

			case "TIME_ESTIMATE":
			case "TIME_SPENT_IN_LOGS":
				$bDataInSeconds = true;
				echo lambda_sgkrg457d_funcFormatForHumanMinutes($record["FROM_VALUE"], true);	// true => data in seconds
				?><span class="task-log-arrow">&rarr;</span><?
				echo lambda_sgkrg457d_funcFormatForHumanMinutes($record["TO_VALUE"], true);	// true => data in seconds
				break;

			case "CREATED_BY":
			case "RESPONSIBLE_ID":

				$userFromStr = "";
				$userToStr = "";
				if (isset($users[$record["FROM_VALUE"]]))
				{
					$userFrom = $users[$record["FROM_VALUE"]];
					$userFromName = tasksFormatNameShort(
						$userFrom["NAME"],
						$userFrom["LAST_NAME"],
						$userFrom["LOGIN"],
						$userFrom["SECOND_NAME"],
						$arParams["NAME_TEMPLATE"],
						true
					);

					$userFromUrl = CComponentEngine::makePathFromTemplate(
						$arParams["PATH_TO_USER_PROFILE"],
						array("user_id" => $userFrom["ID"])
					);

					$userFromStr = $userFromUrl !== "" ?
						'<a class="task-log-author" href="'.$userFromUrl.'" target="_top">'.$userFromName.'</a>' :
						$userFromName;
				}

				if (isset($users[$record["TO_VALUE"]]))
				{
					$userTo = $users[$record["TO_VALUE"]];
					$userToName = tasksFormatNameShort(
						$userTo["NAME"],
						$userTo["LAST_NAME"],
						$userTo["LOGIN"],
						$userTo["SECOND_NAME"],
						$arParams["NAME_TEMPLATE"],
						true
					);

					$userToUrl = CComponentEngine::makePathFromTemplate(
						$arParams["PATH_TO_USER_PROFILE"],
						array("user_id" => $userTo["ID"])
					);

					$userToStr = $userToUrl !== "" ?
						'<a class="task-log-author" href="'.$userToUrl.'" target="_top">'.$userToName.'</a>' :
						$userToName;
				}
				?>
				<?=$userFromStr?><span class="task-log-arrow">&rarr;</span><?=$userToStr?><?
				break;

			case "DEADLINE":
			case "START_DATE_PLAN":
			case "END_DATE_PLAN":

				if ($record["FROM_VALUE"] > 0)
				{
					print(\Bitrix\Tasks\UI::formatDateTime($record["FROM_VALUE"], '^'.\Bitrix\Tasks\UI::getDateTimeFormat()));
				}

				?><span class="task-log-arrow">&rarr;</span><?

				if ($record["TO_VALUE"] > 0)
				{
					print(\Bitrix\Tasks\UI::formatDateTime($record["TO_VALUE"], '^'.\Bitrix\Tasks\UI::getDateTimeFormat()));
				}
				break;

			case "ACCOMPLICES":
			case "AUDITORS":
				$usersFromStr = array();
				if ($record["FROM_VALUE"])
				{
					foreach (explode(",", $record["FROM_VALUE"]) as $userId)
					{
						if (!isset($users[$userId]))
						{
							continue;
						}

						$userFrom = $users[$userId];
						$userFromName = tasksFormatNameShort(
							$userFrom["NAME"],
							$userFrom["LAST_NAME"],
							$userFrom["LOGIN"],
							$userFrom["SECOND_NAME"],
							$arParams["NAME_TEMPLATE"],
							true
						);

						$userFromUrl = CComponentEngine::makePathFromTemplate(
							$arParams["PATH_TO_USER_PROFILE"],
							array("user_id" => $userFrom["ID"])
						);

						if ($userFromUrl !== "")
						{
							$usersFromStr[] =
								'<a class="task-log-link" href="'.$userFromUrl.'" target="_top">'.
								$userFromName.
								'</a>';
						}
						else
						{
							$usersFromStr[] = $userFromName;
						}
					}
				}

				$usersToStr = array();
				if ($record["TO_VALUE"])
				{
					foreach (explode(',', $record["TO_VALUE"]) as $userId)
					{
						if (!isset($users[$userId]))
						{
							continue;
						}

						$userTo = $users[$userId];
						$userToName = tasksFormatNameShort(
							$userTo["NAME"],
							$userTo["LAST_NAME"],
							$userTo["LOGIN"],
							$userTo["SECOND_NAME"],
							$arParams["NAME_TEMPLATE"],
							true
						);

						$userToUrl = CComponentEngine::makePathFromTemplate(
							$arParams["PATH_TO_USER_PROFILE"],
							array("user_id" => $userTo["ID"])
						);

						if ($userToUrl !== "")
						{
							$usersToStr[] =
								'<a class="task-log-link" href="'.$userToUrl.'" target="_top">'.
								$userToName.
								'</a>';
						}
						else
						{
							$usersToStr[] = $userToName;
						}

					}
				}

				echo implode(", ", $usersFromStr)?><span class="task-log-arrow">&rarr;</span><? echo implode(", ", $usersToStr);
				break;

			case "TAGS":
				echo str_replace(",", ", ", htmlspecialcharsbx($record["FROM_VALUE"]))?><span class="task-log-arrow">&rarr;</span><? echo str_replace(",", ", ", htmlspecialcharsbx($record["TO_VALUE"]));
				break;

			case "PRIORITY":
				echo Loc::getMessage($record["FROM_VALUE"] == 2 ? "TASKS_COMMON_YES" : "TASKS_COMMON_NO")?><span class="task-log-arrow">&rarr;</span><?=Loc::getMessage($record["TO_VALUE"] == 2 ? "TASKS_COMMON_YES" : "TASKS_COMMON_NO");
				break;

			case "GROUP_ID":

				if ($record["FROM_VALUE"] && isset($groups[$record["FROM_VALUE"]]) &&
					CSocNetGroup::CanUserViewGroup($USER->getId(), $record["FROM_VALUE"]))
				{
					$groupFrom = $groups[$record["FROM_VALUE"]];
					$groupFrom["URL"] = CComponentEngine::makePathFromTemplate(
						$arParams["PATH_TO_GROUP"],
						array("group_id" => $groupFrom["ID"])
					);

					if ($groupFrom["URL"] !== "")
					{
						?><a class="task-log-link" href="<?=$groupFrom["URL"]; ?>" target="_top"><?=htmlspecialcharsbx($groupFrom["NAME"])?></a><?
					}
					else
					{
						?><?=htmlspecialcharsbx($groupFrom["NAME"])?><?
					}
				}
				?><span class="task-log-arrow">&rarr;</span><?
				if ($record["TO_VALUE"] && isset($groups[$record["TO_VALUE"]]) &&
					CSocNetGroup::CanUserViewGroup($USER->getId(), $record["TO_VALUE"]))
				{
					$groupTo = $groups[$record["TO_VALUE"]];
					$groupTo["URL"] = CComponentEngine::makePathFromTemplate(
						$arParams["PATH_TO_GROUP"],
						array("group_id" => $groupTo["ID"])
					);

					if ($groupTo["URL"] !== "")
					{
						?><a class="task-log-link" href="<?=$groupTo["URL"]?>" target="_top"><?=htmlspecialcharsbx($groupTo["NAME"])?></a><?
					}
					else
					{
						?><?=htmlspecialcharsbx($groupTo["NAME"])?><?
					}
				}
				break;

			case "PARENT_ID":
				if ($record["FROM_VALUE"] && isset($relatedTasks[$record["FROM_VALUE"]]))
				{
					$taskFrom = $relatedTasks[$record["FROM_VALUE"]];
					$taskFrom["URL"] = CComponentEngine::makePathFromTemplate(
						$arParams["PATH_TO_TASKS_TASK"],
						array("task_id" => $taskFrom["ID"], "action" => "view")
					);

					if ($taskFrom["URL"] !== "")
					{
						?><a class="task-log-link" href="<?=$taskFrom["URL"]?>"><?=htmlspecialcharsbx($taskFrom["TITLE"])?></a><?
					}
					else
					{
						?><?=htmlspecialcharsbx($taskFrom["TITLE"])?><?
					}
				}
				?><span class="task-log-arrow">&rarr;</span><?
				if ($record["TO_VALUE"] && isset($relatedTasks[$record["TO_VALUE"]]))
				{
					$taskFrom = $relatedTasks[$record["TO_VALUE"]];
					$taskFrom["URL"] = CComponentEngine::makePathFromTemplate(
						$arParams["PATH_TO_TASKS_TASK"],
						array("task_id" => $taskFrom["ID"], "action" => "view")
					);

					if ($taskFrom["URL"] !== "")
					{
						?><a class="task-log-link" href="<?=$taskFrom["URL"]?>"><?=htmlspecialcharsbx($taskFrom["TITLE"])?></a><?
					}
					else
					{
						?><?=htmlspecialcharsbx($taskFrom["TITLE"])?><?
					}
				}
				break;

			case "DEPENDS_ON":
				$tasksFromStr = array();
				if ($record["FROM_VALUE"])
				{
					foreach (explode(",", $record["FROM_VALUE"]) as $taskId)
					{
						if (!isset($relatedTasks[$taskId]))
						{
							continue;
						}

						$taskFrom = $relatedTasks[$taskId];
						$taskFrom["URL"] = CComponentEngine::makePathFromTemplate(
							$arParams["PATH_TO_TASKS_TASK"],
							array("task_id" => $taskFrom["ID"], "action" => "view")
						);

						if ($taskFrom["URL"] !== "")
						{
							$tasksFromStr[] =
								'<a class="task-log-link" href="'.$taskFrom["URL"].'">'.
								htmlspecialcharsbx($taskFrom["TITLE"]).
								"</a>";
						}
						else
						{
							$tasksFromStr[] = htmlspecialcharsbx($taskFrom["TITLE"]);
						}
					}
				}

				$tasksToStr = array();
				if ($record["TO_VALUE"])
				{
					foreach (explode(",", $record["TO_VALUE"]) as $taskId)
					{
						if (!isset($relatedTasks[$taskId]) )
						{
							continue;
						}

						$taskTo = $relatedTasks[$taskId];
						$taskTo["URL"] = CComponentEngine::makePathFromTemplate(
							$arParams["PATH_TO_TASKS_TASK"],
							array("task_id" => $taskTo["ID"], "action" => "view")
						);

						if ($taskTo["URL"] !== "")
						{
							$tasksToStr[] =
								'<a class="task-log-link" href="'.$taskTo["URL"].'">'.
								htmlspecialcharsbx($taskTo["TITLE"]).
								'</a>';
						}
						else
						{
							$tasksToStr[] = htmlspecialcharsbx($taskTo["TITLE"]);
						}
					}
				}

				echo implode(", ", $tasksFromStr)?><span class="task-log-arrow">&rarr;</span><? echo implode(", ", $tasksToStr);
				break;

			case "STATUS":
				echo Loc::getMessage("TASKS_STATUS_".$record["FROM_VALUE"])?><span class="task-log-arrow">&rarr;</span><?=Loc::getMessage("TASKS_STATUS_".$record["TO_VALUE"]);
				break;

			case "MARK":
				echo !$record["FROM_VALUE"] ? Loc::getMessage("TASKS_MARK_NONE") : Loc::getMessage("TASKS_MARK_".$record["FROM_VALUE"])?><span class="task-log-arrow">&rarr;</span><? echo !$record["TO_VALUE"] ? Loc::getMessage("TASKS_MARK_NONE") : Loc::getMessage("TASKS_MARK_".$record["TO_VALUE"]);
				break;

			case "ADD_IN_REPORT":
				echo $record["FROM_VALUE"] == "Y" ? Loc::getMessage("TASKS_SIDEBAR_IN_REPORT_YES") : Loc::getMessage("TASKS_SIDEBAR_IN_REPORT_NO")?><span class="task-log-arrow">&rarr;</span><? echo $record["TO_VALUE"] == "Y" ? Loc::getMessage("TASKS_SIDEBAR_IN_REPORT_YES") : Loc::getMessage("TASKS_SIDEBAR_IN_REPORT_NO");
				break;

			case "UF_CRM_TASK_ADDED":
				$added = array_filter(explode(',', $record['TO_VALUE']));
				sort($added);
				echo prepareUfCrmEntities($added);
				break;

			case "UF_CRM_TASK_DELETED":
				$deleted = array_filter(explode(',', $record['FROM_VALUE']));
				sort($deleted);
				echo prepareUfCrmEntities($deleted);
				break;

			default:
				echo "&nbsp;";
				break;
		}
			?></span>
		</td>
	</tr>
	<? endforeach ?>
</table>