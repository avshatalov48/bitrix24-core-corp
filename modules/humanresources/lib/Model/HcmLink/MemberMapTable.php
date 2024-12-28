<?php

namespace Bitrix\HumanResources\Model\HcmLink;

use Bitrix\Main\Entity;


/**
 * Class MemberMapTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_MemberMap_Query query()
 * @method static EO_MemberMap_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_MemberMap_Result getById($id)
 * @method static EO_MemberMap_Result getList(array $parameters = [])
 * @method static EO_MemberMap_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\HcmLink\MemberMap createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\HcmLink\MemberMapCollection createCollection()
 * @method static \Bitrix\HumanResources\Model\HcmLink\MemberMap wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\HcmLink\MemberMapCollection wakeUpCollection($rows)
 */
class MemberMapTable extends Entity\DataManager
{
	public static function getObjectClass(): string
	{
		return MemberMap::class;
	}

	public static function getCollectionClass(): string
	{
		return MemberMapCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_hcmlink_entity_member_map';
	}

	public static function getMap(): array
	{
		return [
			'ID' => new Entity\IntegerField('ID', [
				'title' => 'ID',
				'required' => false,
				'primary' => true,
				'autocomplete' => true,
			]),
			'ENTITY_ID' => new Entity\IntegerField('ENTITY_ID', [
				'title' => 'ENTITY ID',
				'required' => true,
			]),
			'COMPANY_ID' => new Entity\IntegerField('COMPANY_ID', [
				'title' => 'COMPANY ID',
				'required' => true,
			]),
			'EXTERNAL_TITLE' => new Entity\StringField('EXTERNAL_TITLE', [
				'external title',
				'required' => true,
			]),
			'EXTERNAL_ID' => new Entity\StringField('EXTERNAL_ID', [
				'title' => 'EXTERNAL ID',
				'required' => true,
			]),
			'CREATED_BY' => new Entity\IntegerField('CREATED_BY', [
				'title' => 'Created by user ID',
				'required' => true
			]),
			'MODIFIED_BY' => new Entity\IntegerField('MODIFIED_BY', [
				'title' => 'Modified by user ID',
				'required' => true
			]),
			'CREATED_AT' => new Entity\DatetimeField('CREATED_AT', [
				'title' => 'Created on',
				'required' => true
			]),
			'UPDATED_AT' => new Entity\DatetimeField('UPDATED_AT', [
				'title' => 'Modified on',
				'required' => true
			])
		];
	}
}