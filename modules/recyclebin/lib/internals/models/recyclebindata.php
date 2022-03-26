<?php

namespace Bitrix\Recyclebin\Internals\Models;

use Bitrix\Main\Entity;

/**
 * Class RecyclebinDataTable
 * @package Bitrix\Recyclebin\Model
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RecyclebinData_Query query()
 * @method static EO_RecyclebinData_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_RecyclebinData_Result getById($id)
 * @method static EO_RecyclebinData_Result getList(array $parameters = array())
 * @method static EO_RecyclebinData_Entity getEntity()
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData createObject($setDefaultValues = true)
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData_Collection createCollection()
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData wakeUpObject($row)
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData_Collection wakeUpCollection($rows)
 */
final class RecyclebinDataTable extends Entity\DataManager
{
	/**
	 * @inheritdoc
	 */
	public static function getMap()
	{
		return [
			new Entity\IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
				]
			),
			new Entity\IntegerField('RECYCLEBIN_ID'),
			new Entity\ReferenceField(
				'RECYCLEBIN',
				'Bitrix\Recyclebin\Model\Recyclebin',
				['=this.RECYCLEBIN_ID' => 'ref.ID']
			),
			new Entity\StringField('ACTION'),
			new Entity\TextField(
				'DATA',
				[
					'save_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getSaveModificator'],
					'fetch_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getFetchModificator'],
				]
			)
		];
	}

	public static function deleteByRecyclebinId($recyclebinId)
	{
		$connection = self::getEntity()->getConnection();
		$sql = "DELETE FROM ".self::getTableName()." WHERE `RECYCLEBIN_ID` = ".(int)$recyclebinId;

		return $connection->query($sql);
	}

	/**
	 * @inheritdoc
	 */
	public static function getTableName()
	{
		return 'b_recyclebin_data';
	}
}