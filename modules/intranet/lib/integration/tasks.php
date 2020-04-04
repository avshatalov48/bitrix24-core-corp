<?

namespace Bitrix\Intranet\Integration;

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class Tasks
{
	public static function createDemoTasksForUser($ID)
	{
		if (\CModule::IncludeModule("tasks"))
		{
			$now = time();

			$res = \CTaskTemplates::GetList(false, array(
				'TPARAM_TYPE' => \CTaskTemplates::TYPE_FOR_NEW_USER,
				'BASE_TEMPLATE_ID' => false
			), false, false, array('ID', 'XML_ID', 'CREATED_BY'));

			$initialTask = false;
			$inviteTask = false;
			$installTask = false;

			$linkType = \Bitrix\Tasks\Task\DependenceTable::LINK_TYPE_FINISH_START;

			$wasImDisabled = \CTaskNotifications::disableInstantNotifications();

			while($item = $res->fetch())
			{
				$addResult = \CTaskItem::addByTemplate($item['ID'], $item['CREATED_BY'], false, array(

					'BEFORE_ADD_CALLBACK' => function(&$fields) use($ID, $now)
					{
						if(!intval($fields['RESPONSIBLE_ID']))
						{
							$fields['RESPONSIBLE_ID'] = $ID;
						}

						$xml = $fields['XML_ID'];

						$start = false;
						$end = false;
						$description = false;

						$day = 86400;

						if($xml == 'SONET_INITIAL_TASK')
						{
							$start = $now;
							$end = $now + $day*5;

							$description = $fields['DESCRIPTION'];
							$description = str_replace(array('#ANCHOR_EDIT_PROFILE#', '#ANCHOR_END#'), array('[URL=/company/personal/user/'.intval($ID).'/edit/]', '[/URL]'), $description);
						}
						if($xml == 'SONET_INVITE_TASK')
						{
							if(!\CTasksTools::IsPortalB24Admin($ID))
							{
								return false; // no invite task for non-admins
							}

							$start = $now + $day*5;
							$end = $now + $day*10;
						}
						if($xml == 'SONTE_INSTALL_APP_TASK')
						{
							if(\CTasksTools::IsPortalB24Admin($ID))
							{
								$start = $now + $day*10;
								$end = $now + $day*15;
							}
							else
							{
								$start = $now + $day*5;
								$end = $now + $day*10;
							}
						}

						if($start !== false)
						{
							$fields['START_DATE_PLAN'] = ConvertTimeStamp($start, 'FULL');
						}

						if($end !== false)
						{
							$fields['END_DATE_PLAN'] = ConvertTimeStamp($end, 'FULL');
						}

						if($description !== false)
						{
							$fields['DESCRIPTION'] = $description;
						}

						$fields['XML_ID'] = md5($fields['TITLE'].$fields['DESCRIPTION'].SITE_ID);
						$fields['STATUS'] = \CTasks::STATE_PENDING;
						$fields['SITE_ID'] = SITE_ID;
					}
				));

				if(is_array($addResult) && !empty($addResult))
				{
					$taskInstance = array_shift($addResult);
					if($taskInstance instanceof \CTaskItem)
					{
						if($item['XML_ID'] == 'SONET_INITIAL_TASK')
						{
							$initialTask = $taskInstance;
						}
						elseif($item['XML_ID'] == 'SONET_INVITE_TASK')
						{
							$inviteTask = $taskInstance;
						}
						elseif($item['XML_ID'] == 'SONTE_INSTALL_APP_TASK')
						{
							$installTask = $taskInstance;
						}
					}
				}
			}

			try
			{
				// add dependences, if can
				if(is_object($initialTask) && is_object($inviteTask))
				{
					$inviteTask->addDependOn($initialTask->getId(), $linkType);
				}
				if(is_object($inviteTask) && is_object($installTask))
				{
					$installTask->addDependOn($inviteTask->getId(), $linkType);
				}
				if(is_object($initialTask) && is_object($installTask) && !is_object($inviteTask))
				{
					$installTask->addDependOn($initialTask->getId(), $linkType);
				}
			}
			catch(\Bitrix\Tasks\ActionNotAllowedException $e)
			{
			}

			if($wasImDisabled)
			{
				\CTaskNotifications::enableInstantNotifications();
			}
		}
	}

	public static function createDemoTemplates()
	{
		if (\CModule::IncludeModule("tasks"))
		{
			$adminId =	\CTasksTools::GetCommanderInChief();
			if(!$adminId)
			{
				return; // no admin
			}

			$xmlId =	'SONET_INITIAL_TASK';
			$item = \CTaskTemplates::GetList(false, array('XML_ID' => $xmlId, 'CREATED_BY' => $adminId), false, false, array('ID'))->fetch();

			if(!(is_array($item) && isset($item['ID'])))
			{
				$template = new \CTaskTemplates();
				$template->Add(array(
					"CREATED_BY" => $adminId,
					"TPARAM_TYPE" => \CTaskTemplates::TYPE_FOR_NEW_USER,
					"PRIORITY" => 1,
					"STATUS" => 2,
					"TITLE" => GetMessage("SONET_TASK_TITLE"),
					"DESCRIPTION" => GetMessage("SONET_TASK_DESCRIPTION"),
					"DESCRIPTION_IN_BBCODE" => "Y",
					"SITE_ID" => \CTaskTemplates::CURRENT_SITE_ID, // we got SITE_ID undefined here, so pass "any" site id
					"XML_ID" => $xmlId,
					"ALLOW_CHANGE_DEADLINE" => "Y",
				));
			}

			$xmlId =	'SONET_INVITE_TASK';
			$item = \CTaskTemplates::GetList(false, array('XML_ID' => $xmlId, 'CREATED_BY' => $adminId), false, false, array('ID'))->fetch();

			if(!(is_array($item) && isset($item['ID'])))
			{
				$template = new \CTaskTemplates();
				$template->Add(array(
					"CREATED_BY" => $adminId,
					"TPARAM_TYPE" => \CTaskTemplates::TYPE_FOR_NEW_USER,
					"PRIORITY" => 1,
					"STATUS" => 2,
					"TITLE" => GetMessage("SONET_INVITE_TASK_TITLE"),
					"DESCRIPTION" => GetMessage("SONET_INVITE_TASK_DESCRIPTION_V2"),
					"DESCRIPTION_IN_BBCODE" => "Y",
					"SITE_ID" => \CTaskTemplates::CURRENT_SITE_ID, // we got SITE_ID undefined here, so pass "any" site id
					"XML_ID" => $xmlId,
					"ALLOW_CHANGE_DEADLINE" => "Y",
				));
			}

			$xmlId =	'SONTE_INSTALL_APP_TASK';
			$item = \CTaskTemplates::GetList(false, array('XML_ID' => $xmlId, 'CREATED_BY' => $adminId), false, false, array('ID'))->fetch();

			if(!(is_array($item) && isset($item['ID'])))
			{
				$template = new \CTaskTemplates();
				$template->Add(array(
					"CREATED_BY" => $adminId,
					"TPARAM_TYPE" => \CTaskTemplates::TYPE_FOR_NEW_USER,
					"PRIORITY" => 1,
					"STATUS" => 2,
					"TITLE" => GetMessage("SONET_INSTALL_APP_TASK_TITLE"),
					"DESCRIPTION" => GetMessage("SONET_INSTALL_APP_TASK_DESCRIPTION"),
					"DESCRIPTION_IN_BBCODE" => "Y",
					"SITE_ID" => \CTaskTemplates::CURRENT_SITE_ID, // we got SITE_ID undefined here, so pass "any" site id
					"XML_ID" => $xmlId,
					"ALLOW_CHANGE_DEADLINE" => "Y",
				));
			}
		}
	}
}