<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Main\ORM;

/**
 * Class StageToStageTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_StageToStage_Query query()
 * @method static EO_StageToStage_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_StageToStage_Result getById($id)
 * @method static EO_StageToStage_Result getList(array $parameters = [])
 * @method static EO_StageToStage_Entity getEntity()
 * @method static \Bitrix\Rpa\Model\EO_StageToStage createObject($setDefaultValues = true)
 * @method static \Bitrix\Rpa\Model\EO_StageToStage_Collection createCollection()
 * @method static \Bitrix\Rpa\Model\EO_StageToStage wakeUpObject($row)
 * @method static \Bitrix\Rpa\Model\EO_StageToStage_Collection wakeUpCollection($rows)
 */
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