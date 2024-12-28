<?php

namespace Bitrix\Crm\Copilot\AiQualityAssessment\Entity;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Result;

final class AiQualityAssessmentTable extends DataManager
{
	public const ACTIVITY_TYPE_CALL = 1;

	public static function getTableName(): string
	{
		return 'b_crm_ai_quality_assessment';
	}

	public static function getMap(): array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		return [
			$fieldRepository->getId(),
			$fieldRepository->getCreatedTime('CREATED_AT'),
			$fieldRepository->getUpdatedTime('UPDATED_AT'),
			(new IntegerField('ACTIVITY_TYPE'))
				->configureRequired()
				->configureSize(1)
				->configureDefaultValue(self::ACTIVITY_TYPE_CALL)
			,
			(new IntegerField('ACTIVITY_ID'))
				->configureRequired()
			,
			(new IntegerField('ASSESSMENT_SETTING_ID'))
				->configureRequired()
			,
			(new IntegerField('JOB_ID'))
				->configureRequired()
			,
			(new StringField('PROMPT')),
			(new IntegerField('ASSESSMENT'))
				->configureRequired()
				->configureSize(3)
				->configureDefaultValue(0)
			,
			(new IntegerField('ASSESSMENT_AVG'))
				->configureRequired()
				->configureSize(3)
				->configureDefaultValue(0)
			,
			(new BooleanField('USE_IN_RATING'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('Y')
				->configureRequired()
			,
			(new IntegerField('RATED_USER_ID'))
				->configureRequired()
				->configureDefaultValue(0)
			,
			(new IntegerField('MANAGER_USER_ID'))
				->configureRequired()
				->configureDefaultValue(0)
			,
			(new IntegerField('RATED_USER_CHAT_ID'))
				->configureRequired()
				->configureDefaultValue(0)
			,
			(new IntegerField('MANAGER_USER_CHAT_ID'))
				->configureRequired()
				->configureDefaultValue(0)
			,
		];
	}

	public static function getAllowedTypes(): array
	{
		return [
			self::ACTIVITY_TYPE_CALL,
		];
	}

	public static function deleteByJobIds(array $jobIds): Result
	{
		if (!empty($jobIds))
		{
			$sqlQuery = new SqlExpression(
			/** @lang text */
				'DELETE FROM ?# WHERE JOB_ID IN (' . implode(',', $jobIds) . ')',
				self::getTableName()
			);

			Application::getConnection()->query((string)$sqlQuery);

			self::cleanCache();
		}

		return new Result();
	}
}
