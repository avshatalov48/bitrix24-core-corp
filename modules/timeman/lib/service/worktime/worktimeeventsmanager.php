<?php
namespace Bitrix\Timeman\Service\Worktime;

use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\ORM\EventResult;

class WorktimeEventsManager
{
	const EVENT_ON_BEFORE_RECORD_UPDATE = 'OnBeforeTMEntryUpdate';
	const EVENT_ON_AFTER_RECORD_UPDATE = 'OnAfterTMEntryUpdate';
	const EVENT_ON_BEFORE_RECORD_ADD = 'OnBeforeTMEntryAdd';
	const EVENT_ON_AFTER_RECORD_ADD = 'OnAfterTMEntryAdd';

	const EVENT_ON_BEFORE_REPORT_UPDATE = 'OnBeforeTMReportUpdate';
	const EVENT_ON_BEFORE_REPORT_ADD = 'OnBeforeTMReportAdd';
	const EVENT_ON_AFTER_REPORT_ADD = 'OnAfterTMReportAdd';

	const ERROR_CODE_CANCEL = 'ERROR_CODE_CANCEL';
	const EVENT_ON_AFTER_REPORT_UPDATE = 'OnAfterTMReportUpdate';

	/**
	 * @param $fields
	 * @param EventResult $result
	 */
	public function sendModuleEventsOnBeforeRecordUpdate($fields, $result)
	{
		$events = GetModuleEvents('timeman', static::EVENT_ON_BEFORE_RECORD_UPDATE);
		while ($eventData = $events->Fetch())
		{
			if (ExecuteModuleEventEx($eventData, [$fields]) === false)
			{
				$result->addError(new EntityError(static::EVENT_ON_BEFORE_RECORD_UPDATE, static::ERROR_CODE_CANCEL));
				return;
			}
		}
	}

	/**
	 * @param $fields
	 * @param EventResult $result
	 */
	public function sendModuleEventsOnAfterRecordUpdate($id, $fields)
	{
		$events = GetModuleEvents('timeman', static::EVENT_ON_AFTER_RECORD_UPDATE);
		while ($eventData = $events->Fetch())
		{
			ExecuteModuleEventEx($eventData, [$id, $fields]);
		}
	}

	public function sendModuleEventsOnBeforeAddRecord($data, EventResult $result)
	{
		$events = GetModuleEvents('timeman', static::EVENT_ON_BEFORE_RECORD_ADD);
		while ($eventData = $events->Fetch())
		{
			if (false === ExecuteModuleEventEx($eventData, [$data]))
			{
				$result->addError(new EntityError(static::EVENT_ON_BEFORE_RECORD_ADD, static::ERROR_CODE_CANCEL));
				return;
			}
		}
	}

	public function sendModuleEventsOnAfterRecordAdd($fields)
	{
		$events = GetModuleEvents('timeman', static::EVENT_ON_AFTER_RECORD_ADD);
		while ($eventData = $events->Fetch())
		{
			ExecuteModuleEventEx($eventData, [$fields]);
		}
	}

	public function sendModuleEventsOnBeforeReportAdd($data, EventResult $result)
	{
		$e = GetModuleEvents('timeman', static::EVENT_ON_BEFORE_REPORT_ADD);
		while ($a = $e->Fetch())
		{
			if (false === ExecuteModuleEventEx($a, [$data]))
			{
				$result->addError(new EntityError(static::EVENT_ON_BEFORE_REPORT_ADD, static::ERROR_CODE_CANCEL));
				return;
			}
		}
	}

	public function sendModuleEventsOnAfterReportAdd($fields)
	{
		$e = GetModuleEvents('timeman', static::EVENT_ON_AFTER_REPORT_ADD);
		while ($a = $e->Fetch())
		{
			ExecuteModuleEventEx($a, [$fields]);
		}
	}

	public function sendModuleEventsOnBeforeReportUpdate($data, EventResult $result)
	{
		$e = GetModuleEvents('timeman', static::EVENT_ON_BEFORE_REPORT_UPDATE);
		while ($a = $e->Fetch())
		{
			if (false === ExecuteModuleEventEx($a, [$data]))
			{
				$result->addError(new EntityError(static::EVENT_ON_BEFORE_REPORT_UPDATE, static::ERROR_CODE_CANCEL));
				return;
			}
		}
	}

	public function sendModuleEventsOnAfterReportUpdate($id, $fields)
	{
		$e = GetModuleEvents('timeman', static::EVENT_ON_AFTER_REPORT_UPDATE);
		while ($a = $e->Fetch())
		{
			ExecuteModuleEventEx($a, [$id, $fields]);
		}
	}

	public function extractIdFromEvent(\Bitrix\Main\ORM\Event $event)
	{
		return $event->getParameter('primary')['ID'];
	}
}