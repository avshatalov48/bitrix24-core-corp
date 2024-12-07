<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__.'/template.php');
Loc::loadMessages(__DIR__.'/export_excel.php');

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

$userCache = array();
$groupCache = array();
$skipColumns = [
	'FLAG_COMPLETE',
	'RESPONSIBLE_ID',
	'CREATED_BY'
];
$locMap = [
	'START_DATE_PLAN' => 'START_DATE_PLAN',
	'END_DATE_PLAN' => 'END_DATE_PLAN',
];
?>
<meta http-equiv="Content-type" content="text/html;charset=<? echo LANG_CHARSET ?>"/>

<table border="1">
	<thead>
	<tr>
		<?php foreach ($arParams['COLUMNS'] as $field):
			if (in_array($field, $skipColumns, true))
			{
				continue;
			}
			$field = $locMap[$field] ?? $field;
			?>
			<th><?=GetMessage("TASKS_EXCEL_".$field)?></th>
		<?php endforeach ?>
	</tr>
	</thead>

	<tbody>
	<?php foreach ($arResult["LIST"] as $i => $arTask): ?>
		<tr>
			<?php

			foreach ($arParams['COLUMNS'] as $field)
			{
				if (in_array($field, array('FLAG_COMPLETE', 'RESPONSIBLE_ID', 'CREATED_BY')))
				{
					continue;
				}

				switch ($field)
				{
					case 'TITLE':
						// due to http://jabber.bx/view.php?id=39850
						if (!array_key_exists('__LEVEL', $arTask) && preg_match('/^[0-9 \t]*$/', $arTask[$field]))
						{
							$arTask[$field] = "'".$arTask[$field];
						}
						break;

					case 'CREATED_BY':
					case 'ORIGINATOR_NAME':
						if (!array_key_exists('CREATED_BY', $arTask))
						{
							continue;
						}

						if (!array_key_exists($arTask['CREATED_BY'], $userCache))
						{
							$rsUser = CUser::GetByID($arTask['CREATED_BY']);
							if ($arUser = $rsUser->GetNext())
							{
								$userCache[$arTask['CREATED_BY']] = htmlspecialchars_decode(
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
						$arTask[$field] = $userCache[$arTask['CREATED_BY']];
						break;
					case 'RESPONSIBLE_ID':
					case 'RESPONSIBLE_NAME':
						if (!array_key_exists('RESPONSIBLE_ID', $arTask))
						{
							continue;
						}
						if (!array_key_exists($arTask['RESPONSIBLE_ID'], $userCache))
						{
							$rsUser = CUser::GetByID($arTask['RESPONSIBLE_ID']);
							if ($arUser = $rsUser->GetNext())
							{
								$userCache[$arTask['RESPONSIBLE_ID']] = htmlspecialchars_decode(
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
						$arTask[$field] = $userCache[$arTask['RESPONSIBLE_ID']];
						break;
					case "GROUP_NAME":
						if (!array_key_exists('GROUP_ID', $arTask))
						{
							continue;
						}
						if (!array_key_exists($arTask['GROUP_ID'], $groupCache))
						{
							$group = \Bitrix\Tasks\Integration\SocialNetwork\Group::getData(array($arTask['GROUP_ID']));
							$groupCache[$arTask['GROUP_ID']] = htmlspecialcharsbx($group[$arTask['GROUP_ID']]['NAME']);
						}
						$arTask['GROUP_NAME'] = $groupCache[$arTask['GROUP_ID']];
						break;
					case "PRIORITY":
						$arTask[$field] = GetMessage("TASKS_PRIORITY_".$arTask[$field]);
						break;
					case "TAG":
						if(is_array($arTask[$field]) && !empty($arTask[$field]))
						{
							$arTask[$field] = join(',', $arTask[$field]);
						}
						break;
					case "STATUS":
					case "REAL_STATUS":
						$arTask[$field] = GetMessage("TASKS_STATUS_".$arTask['REAL_STATUS']);
						break;
					case "MARK":
						$arTask[$field] = $arTask[$field]
							? GetMessage("TASKS_MARK_".$arTask[$field])
							: GetMessage(
								"TASKS_MARK_NONE"
							);
						break;
					case "TIME_ESTIMATE":
						if ($arTask[$field])
						{
							$arTask[$field] = sprintf(
								'%02d:%02d:%02d',
								floor($arTask[$field] / 3600),        // hours
								floor($arTask[$field] / 60) % 60,    // minutes
								$arTask[$field] % 60                    // seconds
							);
						}
						else
						{
							$arTask[$field] = "";
						}
						break;
					case "TIME_SPENT_IN_LOGS":
						if ($arTask[$field])
						{
							$arTask[$field] = sprintf(
								'%02d:%02d:%02d',
								floor($arTask[$field] / 3600),        // hours
								floor($arTask[$field] / 60) % 60,    // minutes
								$arTask[$field] % 60                    // seconds
							);
						}
						else
						{
							$arTask[$field] = "";
						}
						break;
					case "GROUP_ID":
						if ($arTask[$field] && CSocNetGroup::CanUserViewGroup($USER->GetID(), $arTask[$field]))
						{
							$arGroup = CSocNetGroup::GetByID($arTask[$field]);
							if ($arGroup)
							{
								$arTask[$field] = $arGroup["NAME"];
							}
						}
						if (!$arTask[$field])
						{
							$arTask[$field] = "";
						}
						break;
					case "UF_CRM_TASK":
						if (!empty($arTask[$field]))
						{
							sort($arTask[$field]);

							$collection = [];
							foreach ($arTask[$field] as $value)
							{
								[$type, $id] = explode('_', $value);
								$typeId = CCrmOwnerType::ResolveID(CCrmOwnerTypeAbbr::ResolveName($type));
								$title = CCrmOwnerType::GetCaption($typeId, $id);

								if (!isset($collection[$typeId]))
								{
									$collection[$typeId] = [];
								}
								if ($title)
								{
									$collection[$typeId][] = $title;
								}
							}

							ob_start();
							if ($collection)
							{
								$previousTypeId = null;
								foreach ($collection as $typeId => $items)
								{
									if (empty($items))
									{
										continue;
									}

									if ($typeId !== $previousTypeId)
									{
										$factory = Container::getInstance()->getFactory($typeId);
										$typeTitle = ($factory ? $factory->getEntityDescription() : '');

										echo "{$typeTitle}: ";
									}

									$previousTypeId = $typeId;

									echo implode(', ', $items) . ';';
								}
							}
							$arTask[$field] = ob_get_clean();
						}
						else
						{
							$arTask[$field] = "";
						}
						break;
					default:
						$t='';
						if ($arTask[$field] == 'Y' || $arTask[$field] == 'N')
						{
							$arTask[$field] = GetMessage("TASKS_EXCEL_COLUMN_".$arTask[$field]);
						}
						if (trim($arTask[$field]) == "")
						{
							$arTask[$field] = "";
						}
						break;
				}

				echo '<td>'.($field == 'TITLE' && array_key_exists('__LEVEL', $arTask) // levels for TITLE column
						? str_repeat('&nbsp;&nbsp;&nbsp;', $arTask['__LEVEL']) : '')

					 .htmlspecialcharsbx($arTask[$field]).'</td>';

			}
			?>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>