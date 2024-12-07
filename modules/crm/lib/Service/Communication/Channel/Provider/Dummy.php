<?php

namespace Bitrix\Crm\Service\Communication\Channel\Provider;

use Bitrix\Crm\Service\Communication\Channel\ChannelInterface;
use Bitrix\Crm\Service\Communication\Channel\Event\ChannelEventRegistrar;
use Bitrix\Crm\Service\Communication\Channel\Property\PropertiesCollection;
use Bitrix\Crm\Service\Communication\Channel\Property\PropertiesManager;
use Bitrix\Crm\Service\Communication\Channel\Property\Property;
use Bitrix\Crm\Service\Communication\Channel\Queue\Interface\MemberRequestDistributionEvenlyInterface;
use Bitrix\Crm\Service\Communication\Channel\Queue\Interface\TimeBeforeRequestNextMemberInterface;
use Bitrix\Crm\Service\Communication\Channel\Queue\Interface\TimeTrackingInterface;
use Bitrix\Main\Localization\Loc;

final class Dummy implements ChannelInterface, TimeTrackingInterface, MemberRequestDistributionEvenlyInterface, TimeBeforeRequestNextMemberInterface
{
	public const PROPERTY_FROM = 'from';
	public const PROPERTY_TO = 'to';
	public const PROPERTY_DIRECTION = 'direction';

	public const DIRECTION_INCOMING = 1;
	public const DIRECTION_OUTGOING = 2;

	public static function createInstance(string $channelCode): ChannelInterface
	{
		return new self();
	}

	public static function getCode(): string
	{
		return 'dummy';
	}

	public function getTitle(): string
	{
		return 'Dummy channel';
	}

	public function isActive(): bool
	{
		return true;
	}

	public function getPropertiesCollection(): PropertiesCollection
	{
		$properties = [
			new Property(
				self::PROPERTY_DIRECTION,
				Loc::getMessage('CRM_COMMUNICATION_CHANNEL_DUMMY_PROPERTY_DIRECTION_NAME'),
				PropertiesManager::TYPE_ENUMERATION,
				[
					'list' => [
						self::DIRECTION_INCOMING => Loc::getMessage('CRM_COMMUNICATION_CHANNEL_DUMMY_PROPERTY_DIRECTION_INCOMING'),
						self::DIRECTION_OUTGOING => Loc::getMessage('CRM_COMMUNICATION_CHANNEL_DUMMY_PROPERTY_DIRECTION_OUTGOING'),
					],
				]
			),
			new Property(
				self::PROPERTY_FROM,
				Loc::getMessage('CRM_COMMUNICATION_CHANNEL_DUMMY_PROPERTY_FROM_NAME'),
				PropertiesManager::TYPE_CLIENT_PHONE
			),
			new Property(
				self::PROPERTY_TO,
				Loc::getMessage('CRM_COMMUNICATION_CHANNEL_DUMMY_PROPERTY_TO_NAME'),
				PropertiesManager::TYPE_CLIENT_PHONE
			),
		];

		return new PropertiesCollection($properties);
	}

	public function getTimeOffsetVariants(): array
	{
		return [
			1,
			3,
			5,
			10
		];
	}

	public function onSetNextMember(ChannelEventRegistrar $eventRegistrar): void
	{
		// TODO: Implement onSetNextMember() method.
	}
}
