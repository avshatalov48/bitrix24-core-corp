<?php

namespace Bitrix\Crm\Integration\AI\Model;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\JobRepository;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\Result;

/**
 * Class QueueTable
 *
 * @internal Don't access this table explicitly. Use JobRepository instead
 * @see JobRepository
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Queue_Query query()
 * @method static EO_Queue_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Queue_Result getById($id)
 * @method static EO_Queue_Result getList(array $parameters = [])
 * @method static EO_Queue_Entity getEntity()
 * @method static \Bitrix\Crm\Integration\AI\Model\EO_Queue createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Integration\AI\Model\EO_Queue_Collection createCollection()
 * @method static \Bitrix\Crm\Integration\AI\Model\EO_Queue wakeUpObject($row)
 * @method static \Bitrix\Crm\Integration\AI\Model\EO_Queue_Collection wakeUpCollection($rows)
 */
final class QueueTable extends DataManager
{
	public const EXECUTION_STATUS_PENDING = 'PENDING';
	public const EXECUTION_STATUS_SUCCESS = 'SUCCESS';
	public const EXECUTION_STATUS_ERROR = 'ERROR';

	public static function getTableName()
	{
		return 'b_crm_ai_queue';
	}

	public static function getMap()
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new IntegerField('ENTITY_TYPE_ID'))
				->configureRequired()
			,
			(new IntegerField('ENTITY_ID'))
				->configureRequired()
			,
			(new StringField('HASH'))
				->configureRequired()
				->configureSize(32)
				->configureFormat('#^[a-fA-F0-9]{32}$#i') // md5 hash regex
			,
			(new EnumField('TYPE_ID'))
				->configureRequired()
				->configureValues(AIManager::getAllOperationTypes())
			,
			(new IntegerField('PARENT_ID'))
				->configureRequired()
				->configureDefaultValue(0)
			,
			(new IntegerField('STORAGE_TYPE_ID'))
				->configureRequired()
				->configureDefaultValue(0)
			,
			(new IntegerField('STORAGE_ELEMENT_ID'))
				->configureRequired()
				->configureDefaultValue(0)
			,
			(new IntegerField('USER_ID'))
				->configureRequired()
				->configureDefaultValue(0)
			,
			(new EnumField('EXECUTION_STATUS'))
				->configureRequired()
				->configureValues([
					self::EXECUTION_STATUS_PENDING,
					self::EXECUTION_STATUS_SUCCESS,
					self::EXECUTION_STATUS_ERROR,
				])
				->configureDefaultValue(self::EXECUTION_STATUS_PENDING)
			,
			(new StringField('ERROR_CODE'))
				->configureSize(255)
				->configureNullable()
			,
			(new TextField('ERROR_MESSAGE'))
				->configureNullable()
			,
			(new IntegerField('RETRY_COUNT'))
				->configureRequired()
				->configureDefaultValue(0)
			,
			(new EnumField('OPERATION_STATUS'))
				->configureValues(\Bitrix\Crm\Integration\AI\Result::ALL_OPERATION_STATUSES)
				->configureNullable()
			,
			// serialized json
			(new TextField('RESULT'))
				->configureNullable()
				->configureLong()
			,
			(new BooleanField('IS_FEEDBACK_CONSENT_GRANTED'))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false)
			,
			(new BooleanField('IS_FEEDBACK_SENT'))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false)
			,
		];
	}

	public static function cleanCache(): void
	{
		parent::cleanCache();

		JobRepository::getInstance()->cleanRuntimeCache();
	}

	public static function deletePending(ItemIdentifier $target): Result
	{
		$sqlQuery = new SqlExpression(
			/** @lang text */
			'DELETE FROM ?# WHERE ENTITY_TYPE_ID=?i AND ENTITY_ID=?i AND EXECUTION_STATUS=?s',
			self::getTableName(),
			$target->getEntityTypeId(),
			$target->getEntityId(),
			self::EXECUTION_STATUS_PENDING
		);

		Application::getConnection()->query((string)$sqlQuery);

		self::cleanCache();

		return new Result();
	}

	public static function rebind(ItemIdentifier $src, ItemIdentifier $dst): Result
	{
		$sqlQuery = new SqlExpression(
			/** @lang text */
			'UPDATE ?# SET ENTITY_TYPE_ID=?i, ENTITY_ID=?i WHERE ENTITY_TYPE_ID=?i AND ENTITY_ID=?i',
			self::getTableName(),
			$dst->getEntityTypeId(),
			$dst->getEntityId(),
			$src->getEntityTypeId(),
			$src->getEntityId(),
		);

		Application::getConnection()->query((string)$sqlQuery);

		self::cleanCache();

		return new Result();
	}

	public static function deleteByItem(ItemIdentifier $target): Result
	{
		$sqlQuery = new SqlExpression(
			/** @lang text */
			'DELETE FROM ?# WHERE ENTITY_TYPE_ID=?i AND ENTITY_ID=?i',
			self::getTableName(),
			$target->getEntityTypeId(),
			$target->getEntityId(),
		);

		Application::getConnection()->query((string)$sqlQuery);

		self::cleanCache();

		return new Result();
	}
}
