<?php
namespace Bitrix\Crm\Order\SendingChannels\Internals;

use Bitrix\Crm\Order\SendingChannels;
use Bitrix\Main\Entity;
use Bitrix\Sale\Registry;

/**
 * Class SendingChannelsTable
 * @package Bitrix\Crm\Order\SendingChannels\Internals
 */
class SendingChannelsTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_order_sending_channels';
	}

	public static function getMap()
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'ENTITY_TYPE' => [
				'data_type' => 'string',
				'values' => self::getSupportedEntityTypes(),
				'required' => true,
			],
			'ENTITY_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'CHANNEL_TYPE' => [
				'data_type' => 'string',
				'values' => self::getSupportedChannelTypes(),
				'required' => true,
			],
			'CHANNEL_NAME' => [
				'data_type' => 'string',
			],
		];
	}

	protected static function getSupportedEntityTypes()
	{
		return [
			Registry::ENTITY_ORDER
		];
	}

	protected static function getSupportedChannelTypes()
	{
		return [
			SendingChannels\Chat::getType(),
			SendingChannels\Sms::getType(),
		];
	}

}
