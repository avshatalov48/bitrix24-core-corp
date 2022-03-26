<?php

namespace Bitrix\Crm\Security\Controller;

use Bitrix\Main;
use Bitrix\Crm;

class Quote extends Base
{
	/** @var string */
	protected static $progressRegex = '/^QUOTE_ID([0-9A-Z\:\_\-]+)$/i';

	/**
	 * Get Entity Type ID
	 * @return int
	 */
	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Quote;
	}

	protected function getSelectFields(): array
	{
		return [
			'ID',
			'ASSIGNED_BY_ID',
			'OPENED',
			'STATUS_ID',
		];
	}

	protected function extractProgressStepFromFields(array $fields): string
	{
		return (isset($fields['STATUS_ID']) ? (string)$fields['STATUS_ID'] : '');
	}

	//region ProgressSteps
	public function hasProgressSteps(): bool
	{
		return true;
	}

	public function getProgressSteps($permissionEntityType): array
	{
		return array_keys(\CCrmStatus::GetStatusList('QUOTE_STATUS'));
	}
	//endregion

	//region Parsing of attributes
	public function tryParseProgressStep($attribute, &$value): bool
	{
		if (preg_match(self::$progressRegex, $attribute, $m) !== 1)
		{
			return false;
		}

		$value = isset($m[1]) ? $m[1] : '';

		return true;
	}

	public function prepareProgressStepAttribute(array $fields): string
	{
		return isset($fields['STATUS_ID']) && $fields['STATUS_ID'] !== ''
			? "STATUS_ID{$fields['STATUS_ID']}" : '';
	}

	//endregion

	protected static function getEnabledFlagOptionName(): string
	{
		return '~CRM_SECURITY_QUOTE_CONTROLLER_ENABLED';
	}
}
