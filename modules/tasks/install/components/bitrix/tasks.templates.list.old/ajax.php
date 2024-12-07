<?php

use \Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Task\Status;

define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
define('PUBLIC_AJAX_MODE', true);

define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('tasks');

Loc::loadMessages(__FILE__);
Loc::loadMessages(__DIR__.'/templates/.default/template.php');

if (check_bitrix_sessid())
{
	if (intval($_POST["id"]) > 0)
	{
		$userIsAdmin = \Bitrix\Tasks\Integration\SocialNetwork\User::isAdmin();

		$template = \Bitrix\Tasks\Item\Task\Template::findOne(
			array(
				'filter' => array('=ID' => (int)$_POST["id"]),
			),
			array(
				'USER_ID' => \Bitrix\Tasks\Util\User::getId(),
			)
		);

		if ($template)
		{
			if ($_POST["mode"] == "delete")
			{
				$deleteResult = $template->delete();

				if (!$deleteResult->isSuccess())
				{
					$strError = implode('<br />', $deleteResult->getErrors()->getMessages());

					if ($_POST["type"] == "json")
					{
						echo "['strError' : '".CUtil::JSEscape(htmlspecialcharsbx($strError))."']";
					}
					else
					{
						echo htmlspecialcharsbx($strError);
					}
				}
			}
			elseif ($_POST["mode"] == "load")
			{
				$arPaths = array(
					'PATH_TO_TASKS_TASK' => null,
					'PATH_TO_USER_PROFILE' => null,
					'PATH_TO_USER_TASKS_TASK' => null,
					'PATH_TO_TEMPLATES_TEMPLATE' => null
				);

				if (isset($_POST['path_to_task']))
				{
					$arPaths['PATH_TO_TASKS_TASK'] = $_POST['path_to_task'];
				}

				if (isset($_POST['path_to_user']))
				{
					$arPaths['PATH_TO_USER_PROFILE'] = $_POST['path_to_user'];
				}

				if (isset($_POST['path_to_user_tasks_task']))
				{
					$arPaths['PATH_TO_USER_TASKS_TASK'] = $_POST['path_to_user_tasks_task'];
				}

				if (isset($_POST['path_to_templates_template']))
				{
					$arPaths['PATH_TO_TEMPLATES_TEMPLATE'] = $_POST['path_to_templates_template'];
				}

				$arOrder = $_POST["order"] ? $_POST["order"] : array();
				$arFilter = $_POST["filter"] ? $_POST["filter"] : array();
				$arFilter["BASE_TEMPLATE_ID"] = intval($_POST["id"]);
				$depth = intval($_POST["depth"]) + 1;

				$cUserId = \Bitrix\Tasks\Util\User::getId();

				$res = CTaskTemplates::GetList(
					$arOrder,
					$arFilter,
					false,
					array(
						'USER_ID' => $cUserId,
						'USER_IS_ADMIN' => $userIsAdmin,
					),
					array('*', 'BASE_TEMPLATE_ID')
				);
				$templates = array();
				$ids = array();
				while ($template = $res->GetNext())
				{
					$templates[$template["ID"]] = $template;
					$ids[] = $template['ID'];
				}

				// need to count REACHABLE sub-templates...
				$childCounts = \Bitrix\Tasks\Internals\Helper\Task\Template\Dependence::getDirectChildCount(
					$ids,
					array(
						'USER_ID' => $cUserId
					)
				);
				foreach ($childCounts as $parentId => $cCount)
				{
					if (array_key_exists($parentId, $templates))
					{
						$templates[$parentId]['TEMPLATE_CHILDREN_COUNT'] = $cCount;
					}
				}

				// need to calculate available operations
				$ops = \Bitrix\Tasks\Util\User::getAccessOperationsForEntity('task_template');
				$allowed = \Bitrix\Tasks\Internals\Helper\Task\Template\Access::getAvailableOperations(
					$ids,
					array(
						'USER_ID' => $cUserId
					)
				);
				foreach ($allowed as $itemId => $itemOps)
				{
					if (array_key_exists($itemId, $templates))
					{
						$flipped = array();
						foreach ($itemOps as $opId)
						{
							$flipped[mb_strtoupper($ops[$opId]['NAME'])] = true;
						}
						$templates[$itemId]['ALLOWED_ACTIONS'] = $flipped;
					}
				}

				$APPLICATION->RestartBuffer();
				Header('Content-Type: text/html; charset='.LANG_CHARSET);

				//$arGroups = array();

				$i = 0;
				$iMax = count($templates);
				$bIsJSON = ($_POST["type"] === "json");
				if ($bIsJSON)
				{
					echo "[";
				}

				foreach ($templates as $template)
				{
					++$i;

					/*
					if ($task["GROUP_ID"])
					{
						if ( ! isset($arGroups[$task["GROUP_ID"]]) )
						{
							$arGroups[$task["GROUP_ID"]] = CSocNetGroup::GetByID($task["GROUP_ID"]);
						}

						$arGroup = $arGroups[$task["GROUP_ID"]];
						if ($arGroup)
						{
							$task["GROUP_NAME"] = $arGroup["NAME"];
						}
					}
					*/

					if ($bIsJSON)
					{
						tasksRenderJSON(
							$template,
							$template['TEMPLATE_CHILDREN_COUNT'],
							$arPaths,
							true,
							false,
							false,
							$nameTemplate
						);

						if ($i < $iMax)
						{
							echo ", ";
						}
					}
					else
					{
						$template['META:ALLOWED_ACTIONS'] = $template['ALLOWED_ACTIONS'];
						$template['STATUS'] = Status::PENDING;

						$params = array(
							"PATHS" => $arPaths,
							"PLAIN" => false,
							"DEFER" => true,
							"SITE_ID" => $SITE_ID,
							"TASK_ADDED" => false,
							'IFRAME' => 'N',
							"NAME_TEMPLATE" => $nameTemplate,
							"COLUMNS_IDS" => array(
								CTaskColumnList::COLUMN_TITLE,
								//CTaskColumnList::COLUMN_DEADLINE,
								CTaskColumnList::COLUMN_RESPONSIBLE,
								CTaskColumnList::COLUMN_ORIGINATOR,
								CTaskColumnList::SYS_COLUMN_EMPTY,
							),
							'DATA_COLLECTION' => array(
								array(
									"CHILDREN_COUNT" => $template["TEMPLATE_CHILDREN_COUNT"],
									"DEPTH" => $depth,
									"UPDATES_COUNT" => 0,
									"PROJECT_EXPANDED" => true,
									'ALLOWED_ACTIONS' => null,
									"TASK" => $template
								)
							),

							// new params
							"SYSTEM_COLUMN_IDS" => array(
								CTaskColumnList::SYS_COLUMN_CHECKBOX
							),
							"SHOW_QUICK_INFORMERS" => false,
							"OPEN_TASK_IN_POPUP" => false,
							"CUSTOM_ACTIONS_CALLBACK" => 'templatesGetListItemActions', // use with caution
						);

						if ($columnsOrder !== null)
						{
							$params['COLUMNS_IDS'] = $columnsOrder;
						}

						$APPLICATION->IncludeComponent(
							'bitrix:tasks.list.items',
							'.default',
							$params,
							null,
							array("HIDE_ICONS" => "Y")
						);
					}
				}
				if ($bIsJSON)
				{
					echo "]";
				}
			}
		}
	}

	CMain::FinalActions(); // to make events work on bitrix24
}