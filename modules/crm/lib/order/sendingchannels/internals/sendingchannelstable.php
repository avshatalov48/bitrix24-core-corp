<?php
namespace Bitrix\Crm\Order\SendingChannels\Internals;

use Bitrix\Crm\Order\SendingChannels;
use Bitrix\Main\Entity;
use Bitrix\Sale\Registry;

/**
 * Class SendingChannelsTable
 * @package Bitrix\Crm\Order\SendingChannels\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SendingChannels_Query query()
 * @method static EO_SendingChannels_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SendingChannels_Result getById($id)
 * @method static EO_SendingChannels_Result getList(array $parameters = [])
 * @method static EO_SendingChannels_Entity getEntity()
 * @method static \Bitrix\Crm\Order\SendingChannels\Internals\EO_SendingChannels createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Order\SendingChannels\Internals\EO_SendingChannels_Collection createCollection()
 * @method static \Bitrix\Crm\Order\SendingChannels\Internals\EO_SendingChannels wakeUpObject($row)
 * @method static \Bitrix\Crm\Order\SendingChannels\Internals\EO_SendingChannels_Collection wakeUpCollection($rows)
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
