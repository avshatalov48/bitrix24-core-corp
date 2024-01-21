<?php

namespace Bitrix\Crm\Integrity;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class DuplicateIndexTypeSettingsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DuplicateIndexTypeSettings_Query query()
 * @method static EO_DuplicateIndexTypeSettings_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DuplicateIndexTypeSettings_Result getById($id)
 * @method static EO_DuplicateIndexTypeSettings_Result getList(array $parameters = [])
 * @method static EO_DuplicateIndexTypeSettings_Entity getEntity()
 * @method static \Bitrix\Crm\Integrity\EO_DuplicateIndexTypeSettings createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Integrity\EO_DuplicateIndexTypeSettings_Collection createCollection()
 * @method static \Bitrix\Crm\Integrity\EO_DuplicateIndexTypeSettings wakeUpObject($row)
 * @method static \Bitrix\Crm\Integrity\EO_DuplicateIndexTypeSettings_Collection wakeUpCollection($rows)
 */
class DuplicateIndexTypeSettingsTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_dp_index_type_settings';
	}

	public static function getMap(): array
	{
		return [
			new IntegerField('ID', ['primary' => true]),
			new BooleanField('ACTIVE', ['values' => ['N', 'Y'], 'default_value' => 'N']),
			new StringField('DESCRIPTION', ['size' => 256]),
			new IntegerField('ENTITY_TYPE_ID'),
			new IntegerField('STATE_ID'),
			new StringField('FIELD_PATH', ['size' => 255]),
			new StringField('FIELD_NAME', ['size' => 50]),
			new StringField('PROGRESS_DATA'),
		];
	}

	public static function getProgressData(int $volatileTypeId): array
	{
		if (!DuplicateVolatileCriterion::isSupportedType($volatileTypeId))
		{
			throw new ArgumentException('Unsupported duplacate index type', 'volatileTypeId');
		}

		$row = static::getList(
			[
				'filter' => ['=ID' => $volatileTypeId],
				'select' => ['PROGRESS_DATA'],
			]
		)->fetch();

		$result = [];
		if (isset($row['PROGRESS_DATA']) && is_string($row['PROGRESS_DATA']) && $row['PROGRESS_DATA'] !== '')
		{
			$result = unserialize($row['PROGRESS_DATA'], ['allowed_classes' => false]);
			if (!is_array($result))
			{
				$result = [];
			}
		}

		return $result;
	}

	public static function setProgressData(int $volatileTypeId, array $data)
	{
		if (!DuplicateVolatileCriterion::isSupportedType($volatileTypeId))
		{
			throw new ArgumentException('Unsupported duplacate index type', 'volatileTypeId');
		}

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$sql = $sqlHelper->prepareMerge(
			'b_crm_dp_index_type_settings',
			[
				'ID',
			],
			[
				'ID' => $volatileTypeId,
				'ACTIVE' => 'N',
				'DESCRIPTION' => '',
				'ENTITY_TYPE_ID' => 0,
				'STATE_ID' => 0,
				'FIELD_PATH' => '',
				'FIELD_NAME' => '',
				'PROGRESS_DATA' => serialize($data),
			],
			[
				'PROGRESS_DATA' => serialize($data),
			]
		);
		$connection->queryExecute($sql[0]);
	}
}
