<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Main\ORM;

class PermissionTable extends ORM\Data\DataManager
{
	public static function getTableName(): string
	{
		return 'b_rpa_permission';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new ORM\Fields\StringField('ENTITY'))
				->configureRequired()
				->configureSize(50),
			(new ORM\Fields\IntegerField('ENTITY_ID'))
				->configureRequired(),
			(new ORM\Fields\StringField('ACCESS_CODE'))
				->configureRequired()
				->configureSize(100),
			(new ORM\Fields\StringField('ACTION'))
				->configureRequired()
				->configureSize(50),
			(new ORM\Fields\StringField('PERMISSION'))
				->configureRequired()
				->configureSize(1),
		];
	}

	public static function deleteByEntity(string $entity, int $entityId): void
	{
		$list = static::getList([
			'filter' => [
				'=ENTITY' => $entity,
				'=ENTITY_ID' => $entityId,
			],
		]);

		while($item = $list->fetch())
		{
			static::delete($item['ID']);
		}
	}
}