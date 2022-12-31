<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\UI\EntityEditor\BaseProvider;

class Provider extends BaseProvider
{
	protected Factory $factory;
	protected Item $entity;
	protected array $params;
	protected SupportsEditorProvider $component;

	public function __construct(Factory $factory, Item $entity, array $params = [])
	{
		$this->factory = $factory;
		$this->entity = $entity;
		$this->params = $params;

		$this->initializeComponent();
	}

	public function getConfigId(): string
	{
		return $this->component->prepareConfigId();
	}

	public function getEntityTypeId(): int
	{
		return $this->factory->getEntityTypeId();
	}

	public function getEntityTypeName(): string
	{
		return $this->factory->getEntityName();
	}

	public function getEntityId(): int
	{
		return $this->entity->getId();
	}

	public function isNewItem(): bool
	{
		return $this->entity->isNew();
	}

	public function getParams(): array
	{
		return $this->params;
	}

	private function getComponentName(): ?string
	{
		return
			Container::getInstance()
				->getRouter()
				->getItemDetailComponentName($this->getEntityTypeId())
		;
	}

	private function initializeComponent(): void
	{
		$componentName = $this->getComponentName();
		if ($componentName === null)
		{
			throw new \DomainException('Wrong component name');
		}

		$componentClass = \CBitrixComponent::includeComponentClass($componentName);

		$params = $this->getParams();
		$this->component = new $componentClass();
		$this->component->initComponent($componentName);
		$this->component->initializeParams($params);
		$this->component->setEntityID($this->getEntityId());

		$categoryId = $params['CATEGORY_ID'] ?? null;
		if ($categoryId !== null)
		{
			$this->component->setCategoryID($categoryId);
		}

		$enableSearchHistory = ($params['ENABLE_SEARCH_HISTORY'] ?? 'Y') === 'Y';
		$this->component->enableSearchHistory($enableSearchHistory);

		$this->component->initializeData();
	}

	public function getGUID(): string
	{
		return $this->component->getDefaultGuid();
	}

	public function getEntityFields(): array
	{
		return $this->component->prepareFieldInfos();
	}

	public function getEntityConfig(): array
	{
		return $this->component->prepareConfiguration();
	}

	public function getEntityData(): array
	{
		return $this->component->prepareEntityData();
	}

	public function getEntityControllers(): array
	{
		return $this->component->prepareEntityControllers();
	}

	private function getCategoryId()
	{
		return $this->component->getCategoryID();
	}

	public function isReadOnly(): bool
	{
		$userPermissions = Container::getInstance()->getUserPermissions();

		if ($this->isNewItem())
		{
			$hasAccess = $userPermissions->checkAddPermissions($this->getEntityTypeId(), $this->getCategoryId());
		}
		else
		{
			$hasAccess = $userPermissions->checkUpdatePermissions(
				$this->getEntityTypeId(),
				$this->getEntityId(),
				$this->getCategoryId()
			);
		}

		return !$hasAccess;
	}

	public function getModuleId(): ?string
	{
		return 'crm';
	}
}
