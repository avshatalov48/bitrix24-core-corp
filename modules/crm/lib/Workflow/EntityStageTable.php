<?php

namespace Bitrix\Crm\Workflow;

use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\Type;

/**
 * ORM class represents current entity stages
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_EntityStage_Query query()
 * @method static EO_EntityStage_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_EntityStage_Result getById($id)
 * @method static EO_EntityStage_Result getList(array $parameters = [])
 * @method static EO_EntityStage_Entity getEntity()
 * @method static \Bitrix\Crm\Workflow\EO_EntityStage createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Workflow\EO_EntityStage_Collection createCollection()
 * @method static \Bitrix\Crm\Workflow\EO_EntityStage wakeUpObject($row)
 * @method static \Bitrix\Crm\Workflow\EO_EntityStage_Collection wakeUpCollection($rows)
 */
final class EntityStageTable extends ORM\Data\DataManager
{
	/**
	 * Returns table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_workflow_entity_stage';
	}

	/**
	 * Returns table structure
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new IntegerField('ENTITY_ID', [
				'required' => true,
			]),
			new StringField('WORKFLOW_CODE', [
				'required' => true,
			]),
			new StringField('STAGE', [
				'required' => true,
			]),
			new DatetimeField('UPDATED_AT', [
				'default_value' => new Type\DateTime(),
			]),
			new IntegerField('UPDATED_BY', [
				'default_value' => static::getCurrentUserId(),
			]),
		];
	}

	/**
	 * Simple helper creates new record or updates existing.
	 * Returns successfull UpdateResult with zero-changed rows if payment already in needed stage.
	 *
	 * @param int $entityId
	 * @param string $workflowCode
	 * @param string $nextStage
	 * @return \Bitrix\Main\ORM\Data\Result
	 */
	public static function setStage(int $entityId, string $workflowCode, string $nextStage)
	{
		$queryParams = [
			'select' => ['ID', 'STAGE'],
			'filter' => ['=ENTITY_ID' => $entityId, '=WORKFLOW_CODE' => $workflowCode],
			'order' => ['ID' => 'DESC'],
		];
		$row = static::getList($queryParams)->fetch();
		if ($row)
		{
			if ($row['STAGE'] !== $nextStage)
			{
				return static::update($row['ID'], ['STAGE' => $nextStage]);
			}
			else
			{
				return new UpdateResult();
			}
		}
		else
		{
			return static::add([
				'ENTITY_ID' => $entityId,
				'WORKFLOW_CODE' => $workflowCode,
				'STAGE' => $nextStage,
			]);
		}
	}

	/**
	 * Helper method fetches stage by combined key
	 * @return string|null
	 */
	public static function getStage(int $entityId, string $workflowCode): ?string
	{
		$stage = '';
		$queryParams = [
			'select' => ['ID', 'STAGE'],
			'filter' => ['=ENTITY_ID' => $entityId, '=WORKFLOW_CODE' => $workflowCode],
			'order' => ['ID' => 'DESC'],
		];

		$row = static::getList($queryParams)->fetch();
		if ($row)
		{
			$stage = (string)$row['STAGE'];
		}

		return $stage !== '' ? $stage : null;
	}

	/**
	 * Returns ID of current user if authenticated, null otherwize
	 * @return int|null
	 */
	private static function getCurrentUserId(): ?int
	{
		/** @var \CUser */
		global $USER;

		$userId = 0;

		if (is_object($USER) && $USER instanceof \CUser)
		{
			if ($USER->IsAuthorized())
			{
				$userId = (int)$USER->GetID();
			}
		}

		return $userId > 0 ? $userId : null;
	}
}
