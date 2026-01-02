<?php

namespace Bitrix\Crm\Copilot\AiQueueBuffer\Entity;

use Bitrix\Crm\Copilot\AiQueueBuffer\Enum\Status;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\Result;

/**
 * Class AiQueueBufferTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_AiQueueBuffer_Query query()
 * @method static EO_AiQueueBuffer_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_AiQueueBuffer_Result getById($id)
 * @method static EO_AiQueueBuffer_Result getList(array $parameters = [])
 * @method static EO_AiQueueBuffer_Entity getEntity()
 * @method static \Bitrix\Crm\Copilot\AiQueueBuffer\Entity\AiQueueBuffer createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Copilot\AiQueueBuffer\Entity\EO_AiQueueBuffer_Collection createCollection()
 * @method static \Bitrix\Crm\Copilot\AiQueueBuffer\Entity\AiQueueBuffer wakeUpObject($row)
 * @method static \Bitrix\Crm\Copilot\AiQueueBuffer\Entity\EO_AiQueueBuffer_Collection wakeUpCollection($rows)
 */
final class AiQueueBufferTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_ai_queue_buffer';
	}

	public static function getObjectClass(): string
	{
		return AiQueueBuffer::class;
	}

	public static function getMap(): array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		return [
			$fieldRepository->getId(),
			(new IntegerField('PROVIDER_ID'))
				->configureRequired()
			,
			(new IntegerField('STATUS'))
				->configureRequired()
				->configureDefaultValue(Status::Waiting->value)
			,
			(new ArrayField('PROVIDER_DATA'))
				->configureSerializationJson()
			,
			(new IntegerField('RETRY_COUNT'))
				->configureRequired()
				->configureDefaultValue(0)
			,
			$fieldRepository->getCreatedTime('CREATED_AT'),
			$fieldRepository->getUpdatedTime('UPDATED_AT'),
			$fieldRepository->getCreatedBy('CREATED_BY_ID'),
		];
	}

	public static function deleteByIds(array $ids): Result
	{
		if (!empty($ids))
		{
			$sqlQuery = new SqlExpression(
				/** @lang text */
				'DELETE FROM ?# WHERE ID IN (' . implode(',', $ids) . ')',
				self::getTableName()
			);

			Application::getConnection()->query((string)$sqlQuery);

			self::cleanCache();
		}

		return new Result();
	}

	public static function deleteAll(): Result
	{
		$sqlQuery = new SqlExpression('DELETE FROM ?#', self::getTableName());

		Application::getConnection()->query((string)$sqlQuery);

		self::cleanCache();
	}
}