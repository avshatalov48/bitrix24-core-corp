<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\Main;

/**
 * Class RegionPhraseTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RegionPhrase_Query query()
 * @method static EO_RegionPhrase_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_RegionPhrase_Result getById($id)
 * @method static EO_RegionPhrase_Result getList(array $parameters = array())
 * @method static EO_RegionPhrase_Entity getEntity()
 * @method static \Bitrix\DocumentGenerator\Model\EO_RegionPhrase createObject($setDefaultValues = true)
 * @method static \Bitrix\DocumentGenerator\Model\EO_RegionPhrase_Collection createCollection()
 * @method static \Bitrix\DocumentGenerator\Model\EO_RegionPhrase wakeUpObject($row)
 * @method static \Bitrix\DocumentGenerator\Model\EO_RegionPhrase_Collection wakeUpCollection($rows)
 */
class RegionPhraseTable extends Main\ORM\Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_documentgenerator_region_phrase';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Main\Entity\IntegerField('REGION_ID', [
				'required' => true,
			]),
			new Main\Entity\StringField('CODE', [
				'required' => true,
			]),
			new Main\Entity\StringField('PHRASE'),
			new Main\Entity\ReferenceField('REGION', '\Bitrix\DocumentGenerator\Model\Region',
				['=this.REGION_ID' => 'ref.ID']
			),
		];
	}

	/**
	 * @param int $regionId
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws \Exception
	 */
	public static function deleteByRegionId($regionId)
	{
		$data = static::getList(['filter' => ['=REGION_ID' => $regionId]]);
		while($record = $data->fetch())
		{
			static::delete($record['ID']);
		}
	}
}