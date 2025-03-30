<?php

namespace Bitrix\Crm\Security\Role\Manage\Manager;

use Bitrix\Crm\CategoryIdentifier;
use Bitrix\Crm\Security\Role\Manage\DTO\RoleSelectionManager\CreateSettingsDto;
use Bitrix\Crm\Security\Role\Manage\RoleSelectionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;
use CCrmOwnerType;

abstract class EntitySelection implements RoleSelectionManager
{
	private const SEPARATOR = '__';

	public function __construct(
		protected readonly int $entityTypeId,
		protected readonly ?int $categoryId = null,
	)
	{
	}

	final public static function create(?CreateSettingsDto $settingsDto): ?static
	{
		$criterion = $settingsDto?->getCriterion();
		$identifier = self::parseCriterion($criterion);
		if ($identifier === null)
		{
			return null;
		}

		$entityTypeId = $identifier->getEntityTypeId();
		$categoryId = $identifier->getCategoryId();

		if (!static::isSuitableEntity($entityTypeId))
		{
			return null;
		}

		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory === null)
		{
			return null;
		}

		if (!self::isSuitableCategoryId($factory, $categoryId))
		{
			$categoryId = null;
		}

		return static::doCreate(
			$entityTypeId,
			$categoryId,
			$settingsDto,
		);
	}

	protected static function doCreate(
		int $entityTypeId,
		?int $categoryId = null,
		?CreateSettingsDto $settingsDto = new CreateSettingsDto(),
	): ?static
	{
		return new static($entityTypeId, $categoryId);
	}

	public function preSaveChecks(array $userGroups): Result
	{
		return new Result();
	}

	public function hasPermissionsToEditRights(): bool
	{
		return Container::getInstance()->getUserPermissions()->isAdminForEntity($this->entityTypeId);
	}

	public function prohibitToSaveRoleWithoutAtLeastOneRight(): bool
	{
		return true;
	}

	public function needShowRoleWithoutRights(): bool
	{
		return false;
	}

	public function getSliderBackUrl(): ?Uri
	{
		return Container::getInstance()
			->getRouter()
			->getItemListUrl($this->entityTypeId, $this->categoryId)
		;
	}

	abstract public function getUrl(): ?Uri;

	protected function buildCriterion(): string
	{
		$parts = [];

		$entityName = CCrmOwnerType::ResolveName($this->entityTypeId);
		$parts[] = mb_strtolower($entityName);

		if ($this->categoryId !== null)
		{
			$parts[] = "c{$this->categoryId}";
		}

		return implode(self::SEPARATOR, $parts);
	}

	protected static function parseCriterion(?string $criterion): ?CategoryIdentifier
	{
		if ($criterion === null)
		{
			return null;
		}

		$criterion = mb_strtoupper($criterion);
		$values = explode(self::SEPARATOR, $criterion);

		$entityTypeId = CCrmOwnerType::ResolveID($values[0] ?? null);

		$categoryId = $values[1] ?? null;
		if ($categoryId !== null)
		{
			$categoryId = explode('C', $categoryId)[1] ?? null;
			$categoryId = is_numeric($categoryId) ? (int)$categoryId : null;
		}

		return CategoryIdentifier::createByParams($entityTypeId, $categoryId);
	}

	public static function isSuitableEntity(int $entityTypeId, ?int $categoryId = null): bool
	{
		return false;
	}

	public static function isSuitableCategoryId(Factory $factory, ?int $categoryId = null): bool
	{
		return
			$categoryId !== null
			&& $factory->isCategoriesEnabled()
			&& $factory->isCategoryExists($categoryId)
		;
	}

	public function getGroupCode(): ?string
	{
		return \Bitrix\Crm\Security\Role\GroupCodeGenerator::getGroupCodeByEntityTypeId($this->entityTypeId);
	}

	public function getMenuId(): ?string
	{
		return null;
	}
}
