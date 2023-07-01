<?php

declare(strict_types = 1);

namespace Bitrix\Crm\Integration\UI\EntityEditor;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\UI\EntityEditor\ReturnsEditorFields;

final class Provider implements ReturnsEditorFields
{
	protected Item $item;
	protected array $params;

	protected SupportsEditorProvider $component;

	public function __construct(Item $item, array $params = [])
	{
		$this->item = $item;
		$this->params = $params;
	}

	public function getFields(): array
	{
		return $this->getComponent()->getEditorConfig();
	}

	private function getComponent(): SupportsEditorProvider
	{
		if (!isset($this->component))
		{
			$this->initializeComponent();
		}

		return $this->component;
	}

	private function initializeComponent(): void
	{
		$componentName = $this->getDetailComponentName();
		if ($componentName === null)
		{
			throw new \DomainException('Wrong component name');
		}

		$componentClass = \CBitrixComponent::includeComponentClass($componentName);

		$this->component = new $componentClass();
		$this->component->initComponent($componentName);
		$this->component->initializeParams($this->params);
		$this->component->setEntityId($this->item->getId());
		$this->component->initializeEditorData();
	}

	private function getDetailComponentName(): ?string
	{
		return
			Container::getInstance()
				->getRouter()
				->getItemDetailComponentName($this->item->getEntityTypeId())
		;
	}
}
