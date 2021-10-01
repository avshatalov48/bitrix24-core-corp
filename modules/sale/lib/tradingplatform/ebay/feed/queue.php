<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class QueueTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> QUEUE_TYPE string(50) mandatory
 * <li> DATA string optional
 * </ul>
 *
 * @package Bitrix\Sale\TradingPlatform\Ebay\Feed;
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Queue_Query query()
 * @method static EO_Queue_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Queue_Result getById($id)
 * @method static EO_Queue_Result getList(array $parameters = array())
 * @method static EO_Queue_Entity getEntity()
 * @method static \Bitrix\Sale\TradingPlatform\Ebay\Feed\EO_Queue createObject($setDefaultValues = true)
 * @method static \Bitrix\Sale\TradingPlatform\Ebay\Feed\EO_Queue_Collection createCollection()
 * @method static \Bitrix\Sale\TradingPlatform\Ebay\Feed\EO_Queue wakeUpObject($row)
 * @method static \Bitrix\Sale\TradingPlatform\Ebay\Feed\EO_Queue_Collection wakeUpCollection($rows)
 */


class QueueTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_tp_ebay_fq';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_FEED_QUEUE_ENTITY_ID_FIELD'),
			),
			'FEED_TYPE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateFeedType'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_FEED_QUEUE_ENTITY_FEED_TYPE_FIELD'),
			),
			'DATA' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_FEED_QUEUE_ENTITY_DATA_FIELD'),
			),
		);
	}
	public static function validateFeedType()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
}