<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Main\ORM;

class StageToStageTable extends ORM\Data\DataManager
{
	public static function getTableName(): string
	{
		return 'b_rpa_stage_to_stage';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new ORM\Fields\IntegerField('STAGE_ID'))
				->configureRequired(),
			(new ORM\Fields\Relations\Reference(
				'STAGE',
				StageTable::class,
				['=this.STAGE_ID' => 'ref.ID']
			)),
			(new ORM\Fields\IntegerField('STAGE_TO_ID'))
				->configureRequired(),
			(new ORM\Fields\Relations\Reference(
				'STAGE_TO',
				StageTable::class,
				['=this.STAGE_TO_ID' => 'ref.ID']
			)),
		];
	}

	public static function deleteByStageId(int $stageId): void
	{
		$list = static::getList([
			'filter' => [
				'=STAGE_ID' => $stageId,
			],
		]);
		while($item = $list->fetch())
		{
			static::delete($item['ID']);
		}
	}
}