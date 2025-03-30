<?php

namespace Bitrix\Crm\Security\Role\Manage;

use Bitrix\Crm\Feature;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionConfig;
use Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionList;
use Bitrix\Crm\Security\Role\Manage\Entity\Button;
use Bitrix\Crm\Security\Role\Manage\Entity\ButtonConfig;
use Bitrix\Crm\Security\Role\Manage\Entity\Company;
use Bitrix\Crm\Security\Role\Manage\Entity\Contact;
use Bitrix\Crm\Security\Role\Manage\Entity\CopilotCallAssessment;
use Bitrix\Crm\Security\Role\Manage\Entity\CrmConfig;
use Bitrix\Crm\Security\Role\Manage\Entity\Deal;
use Bitrix\Crm\Security\Role\Manage\Entity\DynamicItem;
use Bitrix\Crm\Security\Role\Manage\Entity\Exclusion;
use Bitrix\Crm\Security\Role\Manage\Entity\FilterableByAutomatedSolution;
use Bitrix\Crm\Security\Role\Manage\Entity\FilterableByCategory;
use Bitrix\Crm\Security\Role\Manage\Entity\FilterableByTypes;
use Bitrix\Crm\Security\Role\Manage\Entity\Lead;
use Bitrix\Crm\Security\Role\Manage\Entity\OldInvoice;
use Bitrix\Crm\Security\Role\Manage\Entity\Order;
use Bitrix\Crm\Security\Role\Manage\Entity\PermissionEntity;
use Bitrix\Crm\Security\Role\Manage\Entity\Quote;
use Bitrix\Crm\Security\Role\Manage\Entity\SaleTarget;
use Bitrix\Crm\Security\Role\Manage\Entity\SmartInvoice;
use Bitrix\Crm\Security\Role\Manage\Entity\WebForm;
use Bitrix\Crm\Security\Role\Manage\Entity\WebFormConfig;
use Bitrix\Crm\Security\Role\Manage\Enum\Permission;
use CCrmSaleHelper;
use Bitrix\Main\Loader;

final class PermissionEntityBuilder
{
	/** @var array<PermissionEntity|FilterableByTypes|FilterableByCategory> */
	private array $entities = [];

	public function build(): array
	{
		return array_values($this->entities);
	}

	public function buildOfMade(): array
	{
		$result = [];

		foreach ($this->entities as $entity)
		{
			array_push($result, ...$entity->make());
		}

		return $result;
	}

	public function includeAll(): self
	{
		array_map([$this, 'include'], Permission::cases());

		return $this;
	}

	public function include(Permission $permission): self
	{
		$entity = match ($permission) {
			Permission::Contact => new Contact(),
			Permission::Company => new Company(),
			Permission::Lead => new Lead(),
			Permission::Deal => new Deal(),
			Permission::Quote => new Quote(),
			Permission::OldInvoice => new OldInvoice(),
			Permission::SmartInvoice => new SmartInvoice(),
			Permission::Dynamic => new DynamicItem(),
			Permission::Order => new Order(),
			Permission::WebForm => new WebForm(),
			Permission::WebFormConfig => new WebFormConfig(),
			Permission::Button => new Button(),
			Permission::ButtonConfig => new ButtonConfig(),
			Permission::SaleTarget => new SaleTarget(),
			Permission::Exclusion => new Exclusion(),
			Permission::CopilotCallAssessment => new CopilotCallAssessment(),
			Permission::AutomatedSolutionConfig => new AutomatedSolutionConfig(),
			Permission::AutomatedSolutionList => new AutomatedSolutionList(),
			Permission::CrmConfig => new CrmConfig(),
		};

		if (!$this->isPermissionAvailable($permission))
		{
			$entity = null;
		}

		if ($entity !== null)
		{
			$this->entities[$permission->value] = $entity;
		}

		return $this;
	}

	private function isPermissionAvailable(Permission $permission): bool
	{
		return match ($permission) {
			Permission::Order => Loader::includeModule('sale') && CCrmSaleHelper::isWithOrdersMode(),
			default => true,
		};
	}

	public function exclude(Permission $permission): self
	{
		unset($this->entities[$permission->value]);

		return $this;
	}

	public function get(Permission $permission): ?PermissionEntity
	{
		return $this->entities[$permission->value] ?? null;
	}

	public function filterByEntityTypeIds(Permission $permission, array|int|null $ids = null): self
	{
		$entity = $this->get($permission);
		if ($entity instanceof FilterableByTypes)
		{
			$entity->filterByEntityTypeIds($ids);
		}

		return $this;
	}

	public function excludeEntityTypeIds(Permission $permission, array|int|null $ids = null): self
	{
		$entity = $this->get($permission);
		if ($entity instanceof FilterableByTypes)
		{
			$entity->excludeEntityTypeIds($ids);
		}

		return $this;
	}

	public function filterByCategory(Permission $permission, ?int $categoryId = null): self
	{
		$entity = $this->get($permission);
		if ($entity instanceof FilterableByCategory)
		{
			$entity->filterByCategory($categoryId);
		}

		return $this;
	}

	public function filterByAutomatedSolution(Permission $permission, ?int $id): self
	{
		$entity = $this->get($permission);
		if ($entity instanceof FilterableByAutomatedSolution)
		{
			$entity->filterByAutomatedSolution($id);
		}

		return $this;
	}
}
