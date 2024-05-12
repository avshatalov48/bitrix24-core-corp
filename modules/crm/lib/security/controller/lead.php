<?php

namespace Bitrix\Crm\Security\Controller;

use Bitrix\Crm;

class Lead extends Base
{
	/** @var string */
	protected static $permissionEntityType = 'LEAD';
	/** @var string */
	protected static $progressRegex = '/^STATUS_ID([0-9A-Z\:\_\-]+)$/i';

	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Lead;
	}

	protected function getSelectFields(): array
	{
		return 	[
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

	//region Progressable
	public function hasProgressSteps(): bool
	{
		return true;
	}

	public function getProgressSteps($permissionEntityType): array
	{
		return $this->controllerQueries->getLeadProgressSteps($permissionEntityType);
	}

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
		return isset($fields['STATUS_ID']) && $fields['STATUS_ID'] !== '' ? "STATUS_ID{$fields['STATUS_ID']}" : '';
	}
	//endregion

	//region Observable
	public function isObservable(): bool
	{
		return true;
	}

	//endregion
}