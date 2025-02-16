<?php

namespace Bitrix\Sign\Internal;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Sign\Type\BlockType;

/**
 * Class BlockTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Block_Query query()
 * @method static EO_Block_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Block_Result getById($id)
 * @method static EO_Block_Result getList(array $parameters = [])
 * @method static EO_Block_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\Block createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\BlockCollection createCollection()
 * @method static \Bitrix\Sign\Internal\Block wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\BlockCollection wakeUpCollection($rows)
 */
class BlockTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_sign_block';
	}

	public static function getObjectClass(): string
	{
		return Block::class;
	}

	public static function getCollectionClass(): string
	{
		return BlockCollection::class;
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			'ID' => new Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
				'title' => 'ID'
			]),
			'CODE' => new Entity\StringField('CODE', [
				'title' => 'Symbolic code',
				'required' => true
			]),
			'TYPE' => (new Entity\EnumField('TYPE'))
				->configureNullable()
				->configureValues(BlockType::getAll())
			,
			'BLANK_ID' => new Entity\IntegerField('BLANK_ID', [
				'title' => 'Blank id',
				'required' => true
			]),
			'POSITION' => (new \Bitrix\Main\ORM\Fields\ArrayField('BLANK_POSITION', [
				'title' => 'Block position within blank',
			]))->configureSerializationJson(),
			'STYLE' => (new \Bitrix\Main\ORM\Fields\ArrayField('BLANK_STYLE', [
				'title' => 'Block style'
			]))->configureSerializationJson(),
			'DATA' => (new \Bitrix\Main\ORM\Fields\ArrayField('BLANK_DATA', [
				'title' => 'Block data',
			]))->configureSerializationJson(),
			'PART' => new Entity\IntegerField('PART', [
				'title' => 'Sign part number',
				'required' => true
			]),
			'CREATED_BY_ID' => new Entity\IntegerField('CREATED_BY_ID', [
				'title' => 'Created by user ID',
				'required' => true
			]),
			'MODIFIED_BY_ID' => new Entity\IntegerField('MODIFIED_BY_ID', [
				'title' => 'Modified by user ID',
				'required' => true
			]),
			'DATE_CREATE' => new Entity\DatetimeField('DATE_CREATE', [
				'title' => 'Created on',
				'required' => true
			]),
			'DATE_MODIFY' => new Entity\DatetimeField('DATE_MODIFY', [
				'title' => 'Modified on',
				'required' => true
			]),
			'ROLE' => (new IntegerField('ROLE'))
				->configureTitle('Role')
				->configureNullable()
			,
		];
	}
}
