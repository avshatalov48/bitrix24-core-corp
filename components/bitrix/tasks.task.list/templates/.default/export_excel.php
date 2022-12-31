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
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__DIR__.'/template.php');
Loc::loadMessages(__DIR__.'/export_excel.php');

/** @var $APPLICATION CMain */
/** @var array $arResult */
/** @var array $arParams */
/** @var $component CBitrixComponent */
/** @var $this CBitrixComponentTemplate */



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

$columns = $arParams['COLUMNS'];

if ($arResult['EXPORT_ALL'])
{
	$arParams['COLUMNS'] = array_unique($arParams['COLUMNS']);
	$columns = array_unique($arResult['EXPORT_COLUMNS']);
}
?>

<meta http-equiv="Content-type" content="text/html;charset=<?=LANG_CHARSET ?>"/>

<table border="1">
	<thead>
	<tr>
		<?php foreach ($columns as $field):
			if (in_array($field, $columnsToIgnore, true))
			{
				continue;
			}
			if ($field === 'PARENT_ID')
			{
				$field = 'BASE_ID';
			}
			if ($field === 'PARENT_TITLE')
			{
				$field = 'BASE_TITLE';
			}
			$header = Loc::getMessage("TASKS_EXCEL_{$field}");
			if ($header === null && array_key_exists($field, $arParams['UF']))
			{
				$header = $arParams['UF'][$field]['EDIT_FORM_LABEL'];
			}
			?><th><?=$header?></th>
		<?php endforeach;?>
	</tr>
	</thead>

	<tbody>
	<?php foreach ($arResult['EXPORT_LIST'] as $task):?>
		<tr>
			<?php
			foreach ($arParams['COLUMNS'] as $field)
			{
				if (in_array($field, $columnsToIgnore, true))
				{
					continue;
				}

				$prefix = '';
				$columnValue = $task[$field];

				switch ($field)
				{
					case 'DESCRIPTION':
						$columnValue = CTextParser::clearAllTags(
							htmlspecialchars_decode($task[$field], ENT_QUOTES)
						);
						break;

					case 'PARENT_TITLE':
					case 'TITLE':
						if (array_key_exists('__LEVEL', $task))
						{
							$prefix = str_repeat('&nbsp;&nbsp;&nbsp;', $task['__LEVEL']);
						}
						else if (preg_match('/^[0-9 \t]*$/', $columnValue))
						{
							// due to http://jabber.bx/view.php?id=39850
							$columnValue = $task[$field].' ';
						}
						break;

					case 'PARENT_ID':
						$columnValue = (int)$task[$field] === 0 ? '' : $task[$field];
						break;

					case 'ORIGINATOR_NAME':
					case 'RESPONSIBLE_NAME':
						$map = [
							'ORIGINATOR_NAME' => 'CREATED_BY',
							'RESPONSIBLE_NAME' => 'RESPONSIBLE_ID',
						];
						$role = $map[$field];

						if (!array_key_exists($role, $task))
						{
							break;
						}

						$userId = $task[$role];
						if (!array_key_exists($userId, $userCache))
						{
							$userResult = CUser::GetByID($userId);
							if ($user = $userResult->GetNext())
							{
								$userCache[$userId] = htmlspecialchars_decode(
									tasksFormatNameShort(
										$user['NAME'],
										$user['LAST_NAME'],
										$user['LOGIN'],
										$user['SECOND_NAME'],
										$arParams['NAME_TEMPLATE']
									)
								);
							}
						}

						$columnValue = $userCache[$userId];
						break;

					case 'A':
					case 'U':
						if (!$task[$field])
						{
							$columnValue = '';
							break;
						}

						$columnValue = implode(', ', $task[$field]);
						break;

					case 'GROUP_NAME':
						if (!array_key_exists('GROUP_ID', $task))
						{
							break;
						}

						$groupId = $task['GROUP_ID'];
						if (!array_key_exists($groupId, $groupCache))
						{
							$group = Group::getData([$groupId]);
							$groupCache[$groupId] = htmlspecialcharsbx($group[$groupId]['NAME']);
						}

						$columnValue = $groupCache[$groupId];
						break;

					case 'PRIORITY':
						$columnValue = Loc::getMessage('TASKS_PRIORITY_'.$columnValue);
						break;

					case 'TAG':
						if (is_array($columnValue) && !empty($columnValue))
						{
							$columnValue = implode(', ', $columnValue);
						}
						break;

					case 'STATUS':
					case 'REAL_STATUS':
						$columnValue = Loc::getMessage('TASKS_STATUS_'.$task['REAL_STATUS']);
						break;

					case 'MARK':
						$columnValue = Loc::getMessage('TASKS_MARK_'.($columnValue ?: 'NONE'));
						break;

					case 'TIME_ESTIMATE':
					case 'TIME_SPENT_IN_LOGS':
						if ($columnValue)
						{
							$columnValue = sprintf(
								'%02d:%02d:%02d',
								floor($columnValue / 3600), // hours
								floor($columnValue / 60) % 60, // minutes
								$columnValue % 60 // seconds
							);
						}
						else
						{
							$columnValue = '';
						}
						break;

					case 'GROUP_ID':
						if ($columnValue && CSocNetGroup::CanUserViewGroup(User::getId(), $columnValue))
						{
							$group = CSocNetGroup::GetByID($columnValue);
							if ($group)
							{
								$columnValue = $group['NAME'];
							}
						}

						if (!$columnValue)
						{
							$columnValue = '';
						}
						break;

					case 'UF_CRM_TASK':
						if (!empty($columnValue) && Loader::includeModule('crm'))
						{
							sort($columnValue);

							$collection = [];
							foreach ($columnValue as $value)
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
							if (!empty($collection))
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
							$columnValue = ob_get_clean();
						}
						else
						{
							$columnValue = '';
						}
						break;

					default:
						if (mb_strpos($field, 'UF_CRM_TASK_') === 0 && Loader::includeModule('crm'))
						{
							$titles = [];
							$values = $task['UF_CRM_TASK'];
							$allNames = CCrmOwnerType::GetAllNames();
							$currentName = str_replace('UF_CRM_TASK_', '', $field);

							if (!is_array($values) || empty($values) || !in_array($currentName, $allNames, true))
							{
								break;
							}

							sort($values);

							foreach ($values as $value)
							{
								[$type, $id] = explode('_', $value);
								$name = CCrmOwnerTypeAbbr::ResolveName($type);

								if ($name === $currentName)
								{
									$typeId = CCrmOwnerType::ResolveID($name);
									$titles[] = CCrmOwnerType::GetCaption($typeId, $id);
								}
							}

							$columnValue = implode(', ', $titles);
						}
						else if (is_array($columnValue))
						{
							if (!empty($columnValue))
							{
								$columnValue = implode(', ', $columnValue);
							}
							else
							{
								$columnValue = '';
							}
						}
						else if (in_array(strtoupper($columnValue), ['Y', 'N']))
						{
							$columnValue = Loc::getMessage('TASKS_EXCEL_COLUMN_'.$columnValue);
						}
						$UFTypeQuery = "SELECT USER_TYPE_ID FROM b_user_field WHERE FIELD_NAME = '{$field}'";
						$UFType = Application::getConnection()->query($UFTypeQuery)->fetch();

						if ($UFType['USER_TYPE_ID'] === 'boolean')
						{
							$map = [
								'1' => 'Y',
								'0' => 'N',
							];

							$columnValue = Loc::getMessage('TASKS_EXCEL_COLUMN_'.$map[$columnValue]);
						}
						else if (trim($columnValue) === '')
						{
							$columnValue = '';
						}
						break;
				}

				echo '<td>'.$prefix.htmlspecialcharsbx($columnValue).'</td>';
			}
			?>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>