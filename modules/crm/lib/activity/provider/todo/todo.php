<?php

namespace Bitrix\Crm\Activity\Provider\ToDo;

use Bitrix\Crm\Activity\Provider\Base;
use Bitrix\Crm\Activity\Provider\EventRegistrarInterface;
use Bitrix\Crm\Activity\ToDo\ColorSettings\ColorSettingsProvider;
use Bitrix\Crm\Activity\TodoPingSettingsProvider;
use Bitrix\Crm\Badge\SourceIdentifier;
use Bitrix\Crm\Badge\Type\TodoStatus;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Model\ActivityPingOffsetsTable;
use Bitrix\Crm\Service\Communication\Channel\Event\ChannelEventRegistrar;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class ToDo extends Base implements EventRegistrarInterface
{
	public const PROVIDER_ID = 'CRM_TODO';
	public const PROVIDER_TYPE_ID_DEFAULT = 'TODO';

	public static function getId(): string
	{
		return self::PROVIDER_ID;
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_TODO_NAME');
	}

	public static function isTypeEditable($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined): bool
	{
		return false;
	}

	public static function getTypes()
	{
		return [
			[
				'NAME' => Loc::getMessage('CRM_ACTIVITY_TODO_NAME'),
				'PROVIDER_ID' => self::getId(),
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID_DEFAULT
			]
		];
	}

	public static function hasPlanner(array $activity): bool
	{
		return false;
	}

	public static function getAdditionalFieldsForEdit(array $activity)
	{
		return [
			['TYPE' => 'DESCRIPTION'],
			['TYPE' => 'FILE'],
		];
	}

	public static function checkFields($action, &$fields, $id, $params = null)
	{
		self::appendPrevDescription($fields, $fields);

		if (isset($fields['START_TIME']) && (string)$fields['START_TIME'] !== '')
		{
			$fields['DEADLINE'] = $fields['START_TIME'];
		}
		elseif (isset($fields['~START_TIME']) && (string)$fields['~START_TIME'] !== '')
		{
			$fields['~DEADLINE'] = $fields['~START_TIME'];
		}

		$isInitiatedByCalendar = !empty($params['INITIATED_BY_CALENDAR']);

		if (!$isInitiatedByCalendar)
		{
			return new Result();
		}

		if ($action === self::ACTION_UPDATE)
		{
			$prevDescription = trim($params['PREVIOUS_FIELDS']['DESCRIPTION'] ?? '');

			$fields['DESCRIPTION'] = $prevDescription;
		}

		if ($action === self::ACTION_ADD)
		{
			$fields['SETTINGS']['COLOR'] = ColorSettingsProvider::getDefaultColorId();
		}

		if ($action === self::ACTION_ADD || $action === self::ACTION_UPDATE)
		{
			$calendarEventId = $fields['CALENDAR_EVENT_ID'] ?? 0;
			if ($calendarEventId > 0)
			{
				$calendarEvent = \Bitrix\Crm\Integration\Calendar::getEvent($calendarEventId);
				if (is_array($calendarEvent))
				{
					$attendeesEntityList = $calendarEvent['attendeesEntityList'] ?? [];
					$fields['SETTINGS']['USERS'] = array_map(static fn($item) => $item['id'], $attendeesEntityList);

					if (
						!empty($calendarEvent['LOCATION'])
						&& Loader::includeModule('calendar')
					)
					{
						$location = \Bitrix\Calendar\Rooms\Util::parseLocation($calendarEvent['LOCATION']);
						if ($location['room_id'] > 0)
						{
							$fields['LOCATION'] = $location['str'];
							$fields['SETTINGS']['LOCATION'] = $location['str'];
						}
						else
						{
							$fields['LOCATION'] = '';
							$fields['SETTINGS']['ADDRESS_FORMATTED'] = $location['str'];
						}
					}
					else
					{
						if (isset($fields['SETTINGS']['LOCATION']))
						{
							unset($fields['SETTINGS']['LOCATION']);
						}
						if (isset($fields['SETTINGS']['ADDRESS_FORMATTED']))
						{
							unset($fields['SETTINGS']['ADDRESS_FORMATTED']);
						}
					}
				}
			}
		}

		return new Result();
	}

	protected static function getPreparedDescription(array $arFields): string
	{
		$description = $arFields['DESCRIPTION'] ?? '';
		$description = \Bitrix\Crm\Format\TextHelper::removeParagraphs($description);
		$additionalDescriptionData = $arFields['CALENDAR_ADDITIONAL_DESCRIPTION_DATA'] ?? null;

		$descriptionItemsStr = '';
		if (is_array($additionalDescriptionData))
		{
			$descriptionItemsStr = self::implodeAdditionalDescriptionData($additionalDescriptionData);
		}

		if (empty($description) && empty($descriptionItemsStr))
		{
			return '';
		}

		$descriptionWithTitle = Loc::getMessage('CRM_ACTIVITY_CALENDAR_DESCRIPTION_TITLE')
			. ' '
			. $description
		;

		if (empty($descriptionItemsStr))
		{
			return $descriptionWithTitle;
		}

		if (empty($description))
		{
			return $descriptionItemsStr;
		}

		return $descriptionWithTitle . PHP_EOL . $descriptionItemsStr;
	}

	protected static function implodeAdditionalDescriptionData(array $data): string
	{
		self::sortAdditionalDescriptionData($data);

		$str = [];
		foreach ($data as $blockId => $blockData)
		{
			$title = $blockData['TITLE'];
			$items = $blockData['ITEMS'];

			if (empty($items))
			{
				continue;
			}

			$separator = (count($items) > 1 ? PHP_EOL : ' ');

			$str[] = implode($separator, [$title, implode(', ' . PHP_EOL, $items)]);
		}

		return implode(PHP_EOL, $str);
	}

	protected static function sortAdditionalDescriptionData(array &$data): void
	{
		$blocks = [];
		foreach (BlocksManager::getBlocks() as $block)
		{
			$blocks[$block['id']] = $block['sort'];
		}

		uksort(
			$data,
			static fn(string $a, string $b) => $blocks[strtolower($a)] ?? null < $blocks[strtolower($b)] ?? null
		);
	}

	public static function getDefaultPingOffsets(array $params = []): array
	{
		if (isset($params['entityTypeId'], $params['categoryId']))
		{
			return (new TodoPingSettingsProvider($params['entityTypeId'], $params['categoryId']))
				->getCurrentOffsets()
			;
		}

		return TodoPingSettingsProvider::DEFAULT_OFFSETS;
	}

	public static function getPingOffsets(?int $activityId): array
	{
		if (isset($activityId))
		{
			return ActivityPingOffsetsTable::getOffsetsByActivityId($activityId);
		}

		return static::getDefaultPingOffsets();
	}

	public static function canUseCalendarEvents($providerTypeId = null): bool
	{
		return true;
	}

	public static function skipCalendarSync(array $activityFields, array $options = []): bool
	{
		if (!empty($activityFields['CALENDAR_EVENT_ID']))
		{
			return false;
		}

		return (bool) ($options['SKIP_CURRENT_CALENDAR_EVENT'] ?? true);
	}

	public static function getTypesFilterPresets()
	{
		return [
			[
				'NAME' => self::getName(),
			],
		];
	}

	public static function tryPostpone($offset, array $fields, array &$updateFields, $checkPermissions = true)
	{
		$parentResult = parent::tryPostpone($offset, $fields, $updateFields, $checkPermissions);
		if (!$parentResult)
		{
			return false;
		}

		if (isset($fields['START_TIME']) && (string)$fields['START_TIME'] !== '')
		{
			$updateFields['DEADLINE'] = $fields['START_TIME'];
		}

		self::appendPrevDescription($fields, $updateFields);

		return true;
	}

	protected static function appendPrevDescription(array $fields, array &$updateFields): void
	{
		$updateFields['ENRICHED_DESCRIPTION'] = self::getPreparedDescription($fields);

		$calendarEventId = $fields['CALENDAR_EVENT_ID'] ?? null;
		if ($calendarEventId > 0)
		{
			$calendarEvent = \Bitrix\Crm\Integration\Calendar::getEvent($calendarEventId);
			if (is_array($calendarEvent))
			{
				$updateFields['PREV_ENRICHED_DESCRIPTION'] = $calendarEvent['DESCRIPTION'];
			}
		}
	}

	public static function getActivityTitle(array $activity): string
	{
		if (!empty($activity['SUBJECT']) && $activity['SUBJECT'] !== '')
		{
			return parent::getActivityTitle($activity);
		}

		if ($activity['COMPLETED'] === 'Y')
		{
			return Loc::getMessage('CRM_ACTIVITY_TODO_EMPTY_SUBJECT');
		}

		if ($activity['COMPLETED'] === 'N')
		{
			return Loc::getMessage('CRM_ACTIVITY_TODO_UNCOMPLETED_EMPTY_SUBJECT');
		}

		return parent::getActivityTitle($activity);
	}

	public static function getTypeId(array $activity): string
	{
		return (string) ($activity['PROVIDER_TYPE_ID'] ?? self::PROVIDER_TYPE_ID_DEFAULT);
	}

	public static function needSynchronizePingQueue(array $activity): bool
	{
		return empty($activity['CALENDAR_EVENT_ID']);
	}

	public function createActivityFromChannelEvent(ChannelEventRegistrar $eventRegistrar): Result
	{
		// @todo support event creating from channel event
		return new Result();
	}

	public static function syncBadges(int $activityId, array $activityFields, array $bindings): void
	{
		$badge = Container::getInstance()->getBadge(
			TodoStatus::TODO_STATUS_TYPE,
			TodoStatus::OVERLAP_EVENT_VALUE,
		);

		$itemIdentifier = new ItemIdentifier(
			$activityFields['OWNER_TYPE_ID'],
			$activityFields['OWNER_ID']
		);

		$sourceIdentifier = new SourceIdentifier(
			SourceIdentifier::CRM_OWNER_TYPE_PROVIDER,
			\CCrmOwnerType::Activity,
			$activityId,
		);

		if (isset($activityFields['SETTINGS']['TAGS']['OVERLAP_EVENT']))
		{
			$badge->bind($itemIdentifier, $sourceIdentifier);
		}
		elseif ($badge->isBound($itemIdentifier, $sourceIdentifier))
		{
			$badge->unbind($itemIdentifier, $sourceIdentifier);
		}
	}
}
