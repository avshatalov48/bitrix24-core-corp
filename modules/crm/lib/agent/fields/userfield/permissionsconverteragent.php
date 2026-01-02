<?php

namespace Bitrix\Crm\Agent\Fields\UserField;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Integration\HumanResources\HumanResources;
use Bitrix\Crm\UserField\Visibility\AccessCodesConverter;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\UserField\Access\Permission\UserFieldPermissionTable;
use COption;

class PermissionsConverterAgent extends AgentBase
{
	public const AGENT_DONE_STOP_IT = false;
	public const PERIODICAL_AGENT_RUN_LATER = true;

	private const MODULE_NAME = 'crm';

	public static function doRun(): bool
	{
		$instance = new self();

		$maxId = $instance->getIblockAccessCodeMaxId();
		if ($maxId <= 0)
		{
			$instance->cleanUp();

			return self::AGENT_DONE_STOP_IT;
		}

		if (!HumanResources::getInstance()->isUsed())
		{
			$instance->setExecutionPeriod(86400);

			return self::PERIODICAL_AGENT_RUN_LATER;
		}

		$instance->setIsConvertingOption();

		$accessCodesConverter = new AccessCodesConverter();
		if (!$accessCodesConverter->hasUnconvertedAccessCodes())
		{
			$instance->cleanUp();

			return self::AGENT_DONE_STOP_IT;
		}

		$accessCodesConverter->execute();

		return self::PERIODICAL_AGENT_RUN_LATER;
	}

	private function getIblockAccessCodeMaxId(): int
	{
		$result = UserFieldPermissionTable::query()
			->setSelect([
				'MAX_ID' => new ExpressionField('MAX_ID', 'MAX(%s)', ['ID']),
			])
			->fetch()
		;

		return $result['MAX_ID'] ?? 0;
	}

	private function setIsConvertingOption(): void
	{
		COption::SetOptionString(self::MODULE_NAME, AccessCodesConverter::IS_CONVERTING_OPTION_NAME, 'Y');
	}

	private function cleanUp(): void
	{
		COption::RemoveOption(self::MODULE_NAME, AccessCodesConverter::IS_CONVERTING_OPTION_NAME);
	}
}
