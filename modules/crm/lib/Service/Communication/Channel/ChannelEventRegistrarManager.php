<?php

namespace Bitrix\Crm\Service\Communication\Channel;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\ItemIdentifierCollection;
use Bitrix\Crm\Service\Communication\Channel\Event\ChannelEvent;
use Bitrix\Crm\Service\Communication\Channel\Event\ChannelEventParam;
use Bitrix\Crm\Service\Communication\Channel\Event\ChannelEventParamSettings;
use Bitrix\Crm\Service\Communication\Channel\Event\ChannelEventPropertiesCollection;
use Bitrix\Crm\Service\Communication\Channel\Event\ChannelEventRegistrar;
use Bitrix\Crm\Service\Communication\Controller\ChannelController;
use Bitrix\Crm\Service\Communication\Controller\EventController;
use Bitrix\Crm\Service\Communication\Controller\RuleController;

final class ChannelEventRegistrarManager
{
	public static function createInstance(
		string $moduleId,
		string $channelCode,
		string $eventId,
		ChannelEventPropertiesCollection $eventPropertiesCollection
	): ChannelEventRegistrar
	{
		$channel = ChannelController::getInstance()->getChannel($moduleId, $channelCode);

		$channelEvent = new ChannelEvent($channel, $eventId, $eventPropertiesCollection);
		$ruleController = RuleController::getInstance();
		$eventController = EventController::getInstance();

		return new ChannelEventRegistrar($channelEvent, $ruleController, $eventController);
	}

	// @todo refactor this
	public static function getInstanceByEventId(string $moduleId, string $eventId):	?ChannelEventRegistrar
	{
		$eventEntity = EventController::getInstance()->getEventEntityByEventId($moduleId, $eventId);
		if ($eventEntity === null)
		{
			return null;
		}

		$data = $eventEntity->get('DATA');
		$channelEventData = $data['channelEvent'];
		$itemsEventData = $data['resultItems'];

		$channel = ChannelController::getInstance()->getChannel(
			$channelEventData['channel']['moduleId'],
			$channelEventData['channel']['code']
		);

		$eventParamsCollection = new ChannelEventPropertiesCollection([]);
		foreach ($channelEventData['propertiesCollection'] as $property)
		{
			$settings = new ChannelEventParamSettings();

			if (!empty($property['channelEventParamsSettings']))
			{
				if (isset($property['channelEventParamsSettings']['processAccordingType']))
				{
					$settings->setProcessAccordingType($property['channelEventParamsSettings']['processAccordingType']);
				}
				else
				{
					$settings->setProcessAccordingType(false);
				}
			}

			$eventParam = new ChannelEventParam(
				$property['code'],
				$property['value'],
				$settings
			);
			$eventParamsCollection->append($eventParam);
		}

		$channelEvent = new ChannelEvent($channel, $eventId, $eventParamsCollection);

		$resultItems = new ItemIdentifierCollection();
		foreach ($itemsEventData as $item)
		{
			$resultItems->append(ItemIdentifier::createFromArray($item['itemIdentifier']));
		}

		$ruleController = RuleController::getInstance();
		$eventController = EventController::getInstance();

		return (new ChannelEventRegistrar($channelEvent, $ruleController, $eventController))
			->setResultItems($resultItems)
		;
	}
}
