<?php

namespace Bitrix\Crm\Security\Controller;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\ArgumentException;
use CCrmOwnerType;

class DynamicItem extends Base
{
	protected static string $progressRegex = '/^STAGE_ID([0-9A-Z\:\_\-]+)$/i';

	private Factory\Dynamic $factory;

	public function __construct(int $entityTypeId)
	{
		if (!self::isSupportedType($entityTypeId))
		{
			throw new ArgumentException('Not Supported entity type ' . $entityTypeId);
		}

		$factory = Container::getInstance()->getFactory($entityTypeId);

		if (!$factory instanceof Factory\Dynamic)
		{
			throw new ArgumentException('Not Supported entity type ' . $entityTypeId);
		}

		$this->factory = $factory;

		parent::__construct();
	}

	public static function isSupportedType(int $entityTypeId): bool
	{
		return CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId);
	}

	public function hasCategories(): bool
	{
		return true;
	}

	public function getEntityTypeId(): int
	{
		return $this->factory->getEntityTypeId();
	}

	protected function getSelectFields(): array
	{
		return [
			Item::FIELD_NAME_ID,
			Item::FIELD_NAME_ASSIGNED,
			Item::FIELD_NAME_OPENED,
			Item::FIELD_NAME_CATEGORY_ID,
			Item::FIELD_NAME_STAGE_ID,
		];
	}

	public function hasProgressSteps(): bool
	{
		return true;
	}

	protected function extractProgressStepFromFields(array $fields): string
	{
		return $fields[Item::FIELD_NAME_STAGE_ID] ?? '';
	}

	public function getProgressSteps($permissionEntityType): array
	{
		$categoryId = (new PermissionEntityTypeHelper($this->factory->getEntityTypeId()))
			->extractCategoryFromPermissionEntityType((string)$permissionEntityType);

		$result = [];
		foreach ($this->factory->getStages($categoryId) as $stage)
		{
			$result[] = $stage->getStatusId();
		}

		return $result;
	}

	public function tryParseProgressStep($attribute, &$value): bool
	{
		if (preg_match(self::$progressRegex, $attribute, $m) !== 1)
		{
			return false;
		}

		$value = $m[1] ?? '';

		return true;
	}

	public function prepareProgressStepAttribute(array $fields): string
	{
		return isset($fields['STAGE_ID']) && $fields['STAGE_ID'] !== ''
			? "STAGE_ID{$fields['STAGE_ID']}" : '';
	}

	public function isObservable(): bool
	{
		return $this->factory->isObserversEnabled();
	}

	protected function extractCategoryFromFields(array $fields): int
	{
		if ($this->factory->isCategoriesSupported())
		{
			return $fields[Item::FIELD_NAME_CATEGORY_ID] ?? 0;
		}

		return 0;
	}

	public function isEntityTypeSupported(int $entityTypeId): bool
	{
		return self::isSupportedType($entityTypeId);
	}


}