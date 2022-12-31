<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Settings\LayoutSettings;

Loc::loadMessages(__FILE__);

class CallTracker extends Base
{
	public const
		PROVIDER_ID = 'CRM_CALL_TRACKER',
		TYPE_ID = 'CALL_TRACKER';

	public static function getId(): string
	{
		return self::PROVIDER_ID;
	}

	public static function getName(): string
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_CALL_TRACKER_TITLE') ?: 'Call tracker';
	}

	public static function getTypes(): array
	{
		return [
			[
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_CALL_TRACKER_TYPE_TITLE'),
				'PROVIDER_ID' => static::PROVIDER_ID,
				'PROVIDER_TYPE_ID' => static::TYPE_ID,
				'DIRECTIONS' => [
					\CCrmActivityDirection::Incoming => Loc::getMessage('CRM_ACTIVITY_PROVIDER_CALL_TRACKER_TYPE_TITLE'),
					\CCrmActivityDirection::Outgoing => Loc::getMessage('CRM_ACTIVITY_PROVIDER_CALL_TRACKER_TYPE_TITLE'),
				],
			],
		];
	}

	public static function checkFields($action, &$fields, $id, $params = null)
	{
		$result = new \Bitrix\Main\Result();

		if (isset($fields['END_TIME']) && $fields['END_TIME'] != '')
		{
			$fields['DEADLINE'] = $fields['END_TIME'];
		}
		elseif (isset($fields['~END_TIME']) && $fields['~END_TIME'] !== '')
		{
			$fields['~DEADLINE'] = $fields['~END_TIME'];
		}

		return $result;
	}

	public static function tryPostpone($offset, array $fields, array &$updateFields, $checkPermissions = true)
	{
		if(!is_numeric($offset))
		{
			$offset = (int)$offset;
		}

		$nowInUserTimezone = time() + \CTimeZone::GetOffset();

		if(isset($fields['END_TIME']))
		{
			$endTimeTs = MakeTimeStamp($fields['END_TIME']);
			if ($endTimeTs <= $nowInUserTimezone)
			{
				$endTimeTs = $nowInUserTimezone;
			}
			$updateFields['END_TIME'] = FormatDate('FULL', $endTimeTs + $offset, $nowInUserTimezone);
		}

		return true;
	}

	public static function prepareHistoryItemData($historyFields): ?array
	{
		return isset($historyFields['SETTINGS']) && is_array($historyFields['SETTINGS']) ? $historyFields['SETTINGS'] : [];
	}

	public static function getCommunicationType($providerTypeId = null): string
	{
		return static::COMMUNICATION_TYPE_PHONE;
	}

	public static function generateSubject($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined, array $replace = null): string
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_CALL_TRACKER_TYPE_TITLE') ?? '';
	}

	public static function modifyTimelineEntityData($timelineId, array &$data, array $options = null)
	{
		self::modifyData($data);
	}

	public static function modifyScheduleEntityData(array &$data, array $options = null): void
	{
		self::modifyData($data);
	}

	public static function onAfterAdd($activityFields, array $params = null)
	{
		if(!is_array($params))
		{
			$params = [];
		}
		if(isset($params['IS_RESTORATION']) && $params['IS_RESTORATION'])
		{
			return;
		}

		$userId = isset($activityFields['RESPONSIBLE_ID']) ? (int)$activityFields['RESPONSIBLE_ID'] : 0;
		$deadline = isset($activityFields['DEADLINE']) ? new \Bitrix\Main\Type\DateTime($activityFields['DEADLINE']) : null;
		$completed = isset($activityFields['COMPLETED']) && $activityFields['COMPLETED'] === 'Y';
		$bindings = (isset($activityFields['BINDINGS']) && is_array($activityFields['BINDINGS']))
			? $activityFields['BINDINGS']
			: [];

		$isBoundToDeal = array_reduce(
			$bindings,
			function ($wasBound, $binding)
			{
				return $wasBound || ($binding['OWNER_TYPE_ID'] === \CCrmOwnerType::Deal);
			},
			false
		);

		if ($isBoundToDeal && self::needUpdateCounter($completed, $deadline))
		{
			$counter = \Bitrix\Crm\Counter\EntityCounterFactory::createCallTrackerCounter($userId);
			$counter->increase();
		}
	}

	public static function onAfterUpdate(
		int $id,
		array $changedFields,
		array $oldActivityFields,
		array $newActivityFields,
		array $params = null
	)
	{
		// counter should be updated only if deadline, completed flag or responsible was changed
		// also we try to guess, was the activity counted in counter before or not

		$prevDeadline = $oldActivityFields['DEADLINE'] ?? null;
		$curDeadline = $newActivityFields['DEADLINE'] ?? null;
		$deadlineChanged = ($prevDeadline !== $curDeadline);
		$prevDeadline = $prevDeadline ? new \Bitrix\Main\Type\DateTime($prevDeadline) : null;
		$curDeadline = $curDeadline ? new \Bitrix\Main\Type\DateTime($curDeadline) : null;

		$prevCompleted = ($oldActivityFields['COMPLETED'] ?? '') == 'Y';
		$curCompleted = ($newActivityFields['COMPLETED'] ?? '')  == 'Y';
		$completedChanged = ($prevCompleted !== $curCompleted);

		$prevResponsibleId = (int)($oldActivityFields['RESPONSIBLE_ID'] ?? 0);
		$curResponsibleId = (int)($newActivityFields['RESPONSIBLE_ID'] ?? 0);
		$responsibleIdChanged = ($prevResponsibleId !== $curResponsibleId);

		if (!$completedChanged && !$deadlineChanged && !$responsibleIdChanged)
		{
			// nothing important was changed
			return;
		}

		$wasCounterUpdatedEarlier = self::needUpdateCounter($prevCompleted, $prevDeadline);
		$needUpdateCounterNow = self::needUpdateCounter($curCompleted, $curDeadline);

		$needIncrease =
			($completedChanged || $deadlineChanged)
			&& !$wasCounterUpdatedEarlier
			&& $needUpdateCounterNow
		;

		$needDecrease =
			($completedChanged || $deadlineChanged)
			&& $wasCounterUpdatedEarlier
			&& !$needUpdateCounterNow
		;

		if ($responsibleIdChanged)
		{
			$needIncrease = false;
			$needDecrease = $wasCounterUpdatedEarlier;
		}

		if ($curResponsibleId > 0)
		{
			$counter = \Bitrix\Crm\Counter\EntityCounterFactory::createCallTrackerCounter($curResponsibleId);
			if ($needIncrease)
			{
				$counter->increase();
			}
			if ($needDecrease)
			{
				$counter->decrease();
			}
		}
		if ($responsibleIdChanged && $prevResponsibleId > 0 && $needUpdateCounterNow)
		{
			$prevUserCounter = \Bitrix\Crm\Counter\EntityCounterFactory::createCallTrackerCounter($prevResponsibleId);
			$prevUserCounter->increase();
		}
	}

	public static function onAfterDelete(
		int $id,
		array $activityFields,
		array $params = null
	)
	{
		$completed = isset($activityFields['COMPLETED']) && $activityFields['COMPLETED'] === 'Y';
		$deadline = isset($activityFields['DEADLINE']) ? new \Bitrix\Main\Type\DateTime($activityFields['DEADLINE']) : null;
		$userId = isset($activityFields['RESPONSIBLE_ID']) ? (int)$activityFields['RESPONSIBLE_ID'] : 0;

		if (self::needUpdateCounter($completed, $deadline) && $userId > 0)
		{
			$counter = \Bitrix\Crm\Counter\EntityCounterFactory::createCallTrackerCounter($userId);
			$counter->decrease();
		}
	}

	private static function needUpdateCounter(bool $completed, ?DateTime $deadline)
	{
		$highBound = new DateTime();
		$highBound->setTime(23, 59, 59);
		return (!$completed && $deadline && $deadline->getTimestamp() < $highBound->getTimestamp());
	}

	protected static function modifyData(array &$data): void
	{
		$callInfo = [
			'HAS_STATUS' => false,
		];
		if (isset($data['SETTINGS']['STARTED']) && $data['SETTINGS']['STARTED'])
		{
			if (isset($data['SETTINGS']['FINISHED']) && $data['SETTINGS']['FINISHED'])
			{
				$callInfo['HAS_STATUS'] = true;
				$callInfo['SUCCESSFUL'] = true;
				$callInfo['STATUS_TEXT'] = Loc::getMessage('CRM_ACTIVITY_PROVIDER_CALL_TRACKER_STATUS_SUCCESS');
			}
		}
		else
		{
			$callInfo['HAS_STATUS'] = true;
			$callInfo['SUCCESSFUL'] = false;
			$callInfo['STATUS_TEXT'] = Loc::getMessage('CRM_ACTIVITY_PROVIDER_CALL_TRACKER_STATUS_MISSED');
		}

		$data['CALL_INFO'] = $callInfo;

		if (isset($data['SETTINGS']['TIMESTAMP']) && $data['SETTINGS']['TIMESTAMP'] != '')
		{
			$date = DateTime::createFromTimestamp($data['SETTINGS']['TIMESTAMP']);
			$date->toUserTime();

			if ($data['DESCRIPTION_RAW'] != '')
			{
				$data['DESCRIPTION_RAW'] .= "\n";
			}
			$data['DESCRIPTION_RAW'] .= self::formatDate($date);
		}

		if (isset($data['SETTINGS']['DURATION']) && (int)$data['SETTINGS']['DURATION'] > 0)
		{
			if ($data['DESCRIPTION_RAW'] != '')
			{
				$data['DESCRIPTION_RAW'] .= "\n";
			}
			$data['DESCRIPTION_RAW'] .= Loc::getMessage(
				'CRM_ACTIVITY_PROVIDER_CALL_TRACKER_DURATION',
				[
					'#DURATION#' => self::formatDuration((int)$data['SETTINGS']['DURATION']),
				]
			);
		}
	}

	private static function formatDuration(int $duration)
	{
		$minutes = (int)($duration / 60);
		$seconds = (int)($duration % 60);

		if ($minutes > 0)
		{
			return Loc::getMessage(
				'CRM_ACTIVITY_PROVIDER_CALL_TRACKER_DURATION_LONG',
				[
					'#MIN#' => $minutes,
					'#SEC#' => $seconds,
				]
			);
		}
		else
		{
			return Loc::getMessage(
				'CRM_ACTIVITY_PROVIDER_CALL_TRACKER_DURATION_SHORT',
				[
					'#SEC#' => $seconds,
				]
			);
		}
	}

	private function formatDate(DateTime $date)
	{
		if (LayoutSettings::getCurrent()->isSimpleTimeFormatEnabled())
		{
			$format = [
				'tommorow' => 'tommorow',
				's' => 'x',
				'i' => 'x',
				'H3' => 'x',
				'today' => 'x',
				'yesterday' => 'x',
				'-' => DateTime::convertFormatToPhp(FORMAT_DATETIME),
			];
		}
		else
		{
			$format = preg_replace('/:s$/', '', DateTime::convertFormatToPhp(FORMAT_DATETIME));
		}
		$now = new DateTime();
		$now->toUserTime();

		return \FormatDate($format, $date, $now);
	}
}
