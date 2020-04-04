<?php
class CCrmActivityConverter
{
	public static function IsCalEventConvertigRequired()
	{
		if(!(IsModuleInstalled('calendar') && CModule::IncludeModule('calendar')))
		{
			return false;
		}

		$flag = COption::GetOptionString('crm', '~CRM_REQUIRE_CONVERT_CALENDAR_EVENTS', '');
		if($flag !== '')
		{
			return $flag === 'Y';
		}

		//TODO: Waiting for implementation of COUNT in CCalendarEvent::GetList
		$cacheTime = \CCalendar::CacheTime(0);
		$arEvents = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					'!UF_CRM_CAL_EVENT' => null,
					'DELETED' => 'N'
				),
				'setDefaultLimit' => true,
				'getUserfields' => true
			)
		);
		\CCalendar::CacheTime($cacheTime);

		$result = false;
		foreach($arEvents as $arEvent)
		{
			$count = CCrmActivity::GetCount(array('=CALENDAR_EVENT_ID' => $arEvent['ID']));
			if($count === 0)
			{
				$result = true;
				break;
			}
		}

		COption::SetOptionString('crm', '~CRM_REQUIRE_CONVERT_CALENDAR_EVENTS', $result ? 'Y' : 'N');
		return $result;
	}
	public static function ConvertCalEvents($checkPerms = true, $regEvent = true)
	{
		if(!(IsModuleInstalled('calendar') && CModule::IncludeModule('calendar')))
		{
			return 0;
		}

		$arEvents = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					'!UF_CRM_CAL_EVENT' => null,
					'DELETED' => 'N'
				),
				'getUserfields' => true
			)
		);

		$total = 0;
		foreach($arEvents as $arEvent)
		{
			$eventID = $arEvent['ID'];
			$count = CCrmActivity::GetCount(array('=CALENDAR_EVENT_ID' => $eventID));

			if($count === 0
				&& CCrmActivity::CreateFromCalendarEvent($eventID, $arEvent, $checkPerms, $regEvent) > 0)
			{
				$total++;
			}
		}
		return $total;
	}
	public static function IsTaskConvertigRequired()
	{
		if(!(IsModuleInstalled('tasks') && CModule::IncludeModule('tasks')))
		{
			return false;
		}

		$dbTask = CTasks::getCount(
			array('!UF_CRM_TASK' => null),
			array('bIgnoreDbErrors' => true, 'bSkipExtraTables' => true)
		);

		$task = $dbTask ? $dbTask->Fetch() : null;
		$taskCount = is_array($task) && isset($task['CNT']) ? intval($task['CNT']) : 0;
		$activityCount = CCrmActivity::GetCount(
			array(
				'=TYPE_ID' =>  CCrmActivityType::Task,
				'>ASSOCIATED_ENTITY_ID' => 0
			)
		);

		return $taskCount !== $activityCount;
	}
	public static function ConvertTasks($checkPerms = true, $regEvent = true)
	{
		if(!(IsModuleInstalled('tasks') && CModule::IncludeModule('tasks')))
		{
			return 0;
		}

		$taskEntity = new CTasks();
		$dbRes = $taskEntity->GetList(
			array(),
			array('!UF_CRM_TASK' => null),
			array(
				'ID',
				'TITLE',
				'DESCRIPTION',
				'RESPONSIBLE_ID',
				'PRIORITY',
				'STATUS',
				'CREATED_DATE',
				'DATE_START',
				'CLOSED_DATE',
				'START_DATE_PLAN',
				'END_DATE_PLAN',
				'DEADLINE',
				'UF_CRM_TASK'
			),
			false
		);

		$total = 0;
		while($arTask = $dbRes->GetNext())
		{
			$taskID = intval($arTask['ID']);
			$count = CCrmActivity::GetCount(
				array(
					'=TYPE_ID' =>  CCrmActivityType::Task,
					'=ASSOCIATED_ENTITY_ID' => $taskID
				)
			);

			if($count === 0
				&& CCrmActivity::CreateFromTask($taskID, $arTask, $checkPerms, $regEvent) > 0)
			{
				$total++;
			}
		}
		return $total;
	}
}
