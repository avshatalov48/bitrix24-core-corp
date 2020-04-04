<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\SocialNetwork\Group;

Loc::loadMessages(dirname(__FILE__).'/template.php');
Loc::loadMessages(dirname(__FILE__).'/export_excel.php');

$APPLICATION->RestartBuffer();

header('Content-Description: File Transfer');
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");
header('Content-Disposition: attachment; filename="tasks.xls"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

$userCache = [];
$groupCache = [];
$columnsToIgnore = ['FLAG_COMPLETE', 'RESPONSIBLE_ID', 'CREATED_BY'];
?>

<meta http-equiv="Content-type" content="text/html;charset=<? echo LANG_CHARSET ?>"/>

<table border="1">
	<thead>
	<tr>
		<?php foreach ($arParams['COLUMNS'] as $field):
			if (in_array($field, $columnsToIgnore))
			{
				continue;
			}

			$header = Loc::getMessage('TASKS_EXCEL_' . $field);
			if ($header == null && array_key_exists($field, $arParams['UF']))
			{
				$header = $arParams['UF'][$field]['EDIT_FORM_LABEL'];
			}
			?>
			<th><?=$header?></th>
		<?php endforeach ?>
	</tr>
	</thead>

	<tbody>
	<?php foreach ($arResult["LIST"] as $task): ?>
		<tr>
			<?php
			foreach ($arParams['COLUMNS'] as $field)
			{
				if (in_array($field, $columnsToIgnore))
				{
					continue;
				}

				$columnValue = $task[$field];

				switch ($field)
				{
					case "TITLE":
						// due to http://jabber.bx/view.php?id=39850
						if (!array_key_exists('__LEVEL', $task) && preg_match('/^[0-9 \t]*$/', $columnValue))
						{
							$columnValue = "'" . $task[$field];
						}
						break;

					case "ORIGINATOR_NAME":
					case "RESPONSIBLE_NAME":
						$map = [
							'ORIGINATOR_NAME' => 'CREATED_BY',
							'RESPONSIBLE_NAME' => 'RESPONSIBLE_ID'
						];

						if (!array_key_exists($map[$field], $task))
						{
							continue;
						}

						if (!array_key_exists($task[$map[$field]], $userCache))
						{
							$rsUser = CUser::GetByID($task[$map[$field]]);
							if ($arUser = $rsUser->GetNext())
							{
								$userCache[$task[$map[$field]]] = htmlspecialchars_decode(
									tasksFormatNameShort(
										$arUser["NAME"],
										$arUser["LAST_NAME"],
										$arUser["LOGIN"],
										$arUser["SECOND_NAME"],
										$arParams["NAME_TEMPLATE"]
									)
								);
							}
						}

						$columnValue = $userCache[$task[$map[$field]]];
						break;

					case "GROUP_NAME":
						if (!array_key_exists('GROUP_ID', $task))
						{
							continue;
						}

						if (!array_key_exists($task['GROUP_ID'], $groupCache))
						{
							$group = Group::getData([$task['GROUP_ID']]);
							$groupCache[$task['GROUP_ID']] = htmlspecialcharsbx($group[$task['GROUP_ID']]['NAME']);
						}

						$columnValue = $groupCache[$task['GROUP_ID']];
						break;

					case "PRIORITY":
						$columnValue = Loc::GetMessage("TASKS_PRIORITY_" . $columnValue);
						break;

					case "TAG":
						if (is_array($columnValue) && !empty($columnValue))
						{
							$columnValue = join(', ', $columnValue);
						}
						break;

					case "STATUS":
					case "REAL_STATUS":
						$columnValue = Loc::GetMessage("TASKS_STATUS_" . $task["REAL_STATUS"]);
						break;

					case "MARK":
						$columnValue = (
							$columnValue? Loc::GetMessage("TASKS_MARK_" . $columnValue) : Loc::GetMessage("TASKS_MARK_NONE")
						);
						break;

					case "TIME_ESTIMATE":
					case "TIME_SPENT_IN_LOGS":
						if ($columnValue)
						{
							$columnValue = sprintf(
								'%02d:%02d:%02d',
								floor($columnValue / 3600),       // hours
								floor($columnValue / 60) % 60, // minutes
								$columnValue % 60                       // seconds
							);
						}
						else
						{
							$columnValue = "";
						}
						break;

					case "GROUP_ID":
						if ($columnValue && CSocNetGroup::CanUserViewGroup($USER->GetID(), $columnValue))
						{
							$arGroup = CSocNetGroup::GetByID($columnValue);
							if ($arGroup)
							{
								$columnValue = $arGroup["NAME"];
							}
						}

						if (!$columnValue)
						{
							$columnValue = "";
						}
						break;

					case "UF_CRM_TASK":
						if (!empty($columnValue))
						{
							$collection = [];
							sort($columnValue);

							foreach ($columnValue as $value)
							{
								$crmElement = explode('_', $value);
								$type = $crmElement[0];
								$typeId = CCrmOwnerType::ResolveID(CCrmOwnerTypeAbbr::ResolveName($type));
								$title = CCrmOwnerType::GetCaption($typeId, $crmElement[1]);

								if (!isset($collection[$type]))
								{
									$collection[$type] = [];
								}

								if ($title)
								{
									$collection[$type][] = $title;
								}
							}

							ob_start();
							if ($collection)
							{
								$prevType = null;

								foreach ($collection as $type => $items)
								{
									if (empty($items))
									{
										continue;
									}

									if ($type !== $prevType)
									{
										echo Loc::GetMessage('TASKS_LIST_CRM_TYPE_' . $type) . ': ';
									}

									$prevType = $type;

									echo implode(', ', $items) . ';';
								}
							}

							$columnValue = ob_get_clean();
						}
						else
						{
							$columnValue = "";
						}
						break;

					default:
						if ($columnValue == 'Y' || $columnValue == 'N')
						{
							$columnValue = Loc::GetMessage("TASKS_EXCEL_COLUMN_" . $columnValue);
						}

						if (is_array($columnValue))
						{
							if (!empty($columnValue))
							{
								$columnValue = join(', ', $columnValue);
							}
						}
						else if (trim($columnValue) == "")
						{
							$columnValue = "";
						}
						break;
				}

				echo '<td>' .
					(
						$field == 'TITLE' && array_key_exists('__LEVEL', $task)
						? str_repeat('&nbsp;&nbsp;&nbsp;', $task['__LEVEL'])
						: ''
					) .
					htmlspecialcharsbx($columnValue) . '</td>';
			}
			?>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>