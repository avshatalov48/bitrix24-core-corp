<?php

namespace Bitrix\Crm\Activity\Settings\Section;

use Bitrix\Crm\Activity\Settings\OptionallyConfigurable;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use CCalendarEvent;

final class Calendar extends Base
{
	public const TYPE_NAME = 'calendar';
	private const DEFAULT_DURATION = 3600;
	private bool $isCalendarIncluded;

	public function __construct(array $data = [], array $activityData = [])
	{
		parent::__construct($data, $activityData);
		$this->isCalendarIncluded = Loader::includeModule('calendar');
	}

	public function apply(): bool
	{
		return false;
	}

	public function fetchSettings(): array
	{
		$result = [];

		$calendarEventId = $this->activityData['calendarEventId'];
		if ($calendarEventId)
		{
			$eventData = \CCalendarEvent::GetById($calendarEventId);
			if (is_array($eventData))
			{
				$result = [
					'id' => self::TYPE_NAME,
					'active' => true,
					'settings' => [
						'from' => DateTime::createFromUserTime($eventData['DATE_FROM'])->getTimestamp(),
						'to' => DateTime::createFromUserTime($eventData['DATE_TO'])->getTimestamp(),
						'duration' => $eventData['DT_LENGTH'],
					],
				];
			}
		}
		elseif ($this->activityData['deadline'])
		{
			$deadline = $this->activityData['deadline'];

			$from = $deadline->getTimestamp();

			$result = [
				'id' => self::TYPE_NAME,
				'active' => false,
				'settings' => [
					'from' => $from,
					'to' => $from + self::DEFAULT_DURATION,
					'duration' => self::DEFAULT_DURATION,
				],
			];
		}

		return $result;
	}

	public function prepareEntity(OptionallyConfigurable $entity, bool $skipActiveSectionCheck = false): void
	{
		if (isset($this->data['fromText']))
		{
			$start = (DateTime::createFromUserTime($this->data['fromText']))->toString();
			$end = (DateTime::createFromUserTime($this->data['toText']))->toString();

			$entity->setAdditionalFields([
				'DEADLINE' => $start,
				'START_TIME' => $start,
				'END_TIME' => $end,
			]);

			return;
		}

		$calendarEventId = $entity->getCalendarEventId();

		if (!$calendarEventId || !$this->isCalendarIncluded)
		{
			return;
		}

		if (!$skipActiveSectionCheck && empty($this->data['active']))
		{
			$this->unbindAndDeleteCalendarEvent($entity, $calendarEventId);

			return;
		}

		$eventData = CCalendarEvent::GetById($calendarEventId);
		$deadline = clone($entity->getDeadline());
		$start = $deadline->toString();
		$entity->setAdditionalFields([
			'DEADLINE' => $start,
			'START_TIME' => $start,
			'END_TIME' => $deadline->add('PT' . $eventData['DT_LENGTH'] . 'S')->toString(),
		]);
	}

	private function unbindAndDeleteCalendarEvent(OptionallyConfigurable $entity, int $calendarEventId): void
	{
		$entity->setCalendarEventId(0);
		$entity->save();

		CCalendarEvent::Delete([
			'id' => $calendarEventId,
			'bMarkDeleted' => true,
		]);
	}

	public function getOptions(OptionallyConfigurable $entity): array
	{
		return [
			'SKIP_CURRENT_CALENDAR_EVENT' => false,
		];
	}
}
