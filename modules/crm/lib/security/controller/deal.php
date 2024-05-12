<?php

namespace Bitrix\Crm\Security\Controller;

use Bitrix\Crm;

class Deal extends Base
{
	/** @var string */
	protected static $progressRegex = '/^STAGE_ID([0-9A-Z\:\_\-]+)$/i';

	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Deal;
	}

	protected function getSelectFields(): array
	{
		return [
			'ID',
			'CATEGORY_ID',
			'STAGE_ID',
			'ASSIGNED_BY_ID',
			'OPENED',
		];
	}

	protected function extractProgressStepFromFields(array $fields): string
	{
		return (isset($fields['STAGE_ID']) ? (string)$fields['STAGE_ID'] : '');
	}

	protected function extractCategoryFromFields(array $fields): int
	{
		return (isset($fields['CATEGORY_ID']) ? (int)$fields['CATEGORY_ID'] : 0);
	}

	public function hasCategories(): bool
	{
		return true;
	}

	//region ProgressSteps
	public function hasProgressSteps(): bool
	{
		return true;
	}

	public function getProgressSteps($permissionEntityType): array
	{
		return $this->controllerQueries->getDealProgressSteps($permissionEntityType);
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
		return isset($fields['STAGE_ID']) && $fields['STAGE_ID'] !== ''
			? "STAGE_ID{$fields['STAGE_ID']}" : '';
	}
	//endregion

	//region Observable
	public function isObservable(): bool
	{
		return true;
	}

	//endregion
}