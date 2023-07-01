<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Crm;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class ActivityElementTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ActivityElement_Query query()
 * @method static EO_ActivityElement_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ActivityElement_Result getById($id)
 * @method static EO_ActivityElement_Result getList(array $parameters = [])
 * @method static EO_ActivityElement_Entity getEntity()
 * @method static \Bitrix\Crm\EO_ActivityElement createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_ActivityElement_Collection createCollection()
 * @method static \Bitrix\Crm\EO_ActivityElement wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_ActivityElement_Collection wakeUpCollection($rows)
 */
class ActivityElementTable extends Entity\DataManager
{
	/**
	 * @inheritdoc
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_act_elem';
	}

	/**
	 * @inheritdoc
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ACTIVITY_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'ACTIVITY' => array(
				'data_type' => '\Bitrix\Crm\ActivityTable',
				'reference' => array('=this.ACTIVITY_ID' => 'ref.ID')
			),
			'STORAGE_TYPE_ID' => array(
				'data_type' => 'enum',
				'primary' => true,
				'values' => array(
					Crm\Integration\StorageType::Disk,
					Crm\Integration\StorageType::File,
					Crm\Integration\StorageType::WebDav,
					Crm\Integration\StorageType::Undefined,
				)
			),
			'ELEMENT_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			/*(new Entity\ReferenceField(
				'FILE',
				\Bitrix\Main\FileTable::class,
				Entity\Query\Join::on('this.ELEMENT_ID', 'ref.ID')->where('this.STORAGE_TYPE_ID', Crm\Integration\StorageType::File),
				array('join_type' => 'INNER')
			)),*/
		);
	}
}
