<?php

declare(strict_types = 1);

namespace Bitrix\Mobile\UI\DetailCard;

use Bitrix\Main\Engine\Resolver;
use Bitrix\Main\Web\Json;

final class Configurator
{
	private Controller $controller;
	private array $tabs = [];
	private bool $editMode = true;
	private array $dynamicTabOptions = [];
	private ?string $activeTabId = null;

	public function __construct(Controller $controller)
	{
		$this->controller = $controller;
	}

	public function setDynamicTabOptions(array $options): self
	{
		$this->dynamicTabOptions = $options;

		return $this;
	}

	private function getActionsList(): array
	{
		static $actions = null;

		if ($actions === null)
		{
			$actions = array_fill_keys($this->controller->listNameActions(), true);
		}

		return $actions;
	}

	private function getEndpoint(): string
	{
		$fullName = Resolver::getNameByController($this->controller);

		return explode(':', $fullName)[1];
	}

	public function setEditMode(bool $mode): self
	{
		$this->editMode = $mode;

		return $this;
	}

	public function isEditMode(): bool
	{
		return $this->editMode && $this->isSaveable();
	}

	private function isSaveable(): bool
	{
		return $this->hasControllerAction('save');
	}

	private function isCountersLoadSupported(): bool
	{
		return $this->hasControllerAction('loadTabCounters');
	}

	private function checkTabAction(Tabs\Base $tab): bool
	{
		return $this->hasControllerAction($this->getTabActionName($tab));
	}

	private function getTabActionName(Tabs\Base $tab): string
	{
		return $this->controller::getTabActionName($tab->getId());
	}

	private function hasControllerAction(string $actionName): bool
	{
		return isset($this->getActionsList()[$actionName]);
	}

	public function addTab(Tabs\Base $tab): self
	{
		if (!$this->checkTabAction($tab))
		{
			$controllerClass = get_class($this->controller);
			throw new \DomainException(
				"Tab action {{$controllerClass}::{$this->getTabActionName($tab)}} not found."
			);
		}

		$this->tabs[] = $tab;

		return $this;
	}

	public function setActiveTabId(?string $id): self
	{
		$this->activeTabId = $id;

		return $this;
	}

	public function getActiveTabId(): ?string
	{
		if ($this->activeTabId)
		{
			return $this->activeTabId;
		}

		$firstTab = $this->tabs[0] ?? null;
		if ($firstTab)
		{
			return $firstTab->getId();
		}

		return null;
	}

	public function toArray(): array
	{
		return [
			'endpoint' => $this->getEndpoint(),
			'isEditMode' => $this->isEditMode(),
			'dynamicTabOptions' => $this->dynamicTabOptions,
			'tabs' => array_map(static function (Tabs\Base $tab) {
				return $tab->jsonSerialize();
			}, $this->tabs),
			'activeTab' => $this->getActiveTabId(),
			'isCountersLoadSupported' => $this->isCountersLoadSupported(),
		];
	}

	public function toJson(): string
	{
		return Json::encode($this->toArray());
	}

	public function mapTabs(\Closure $handler): array
	{
		return array_map(fn ($item) => $handler($item), $this->tabs);
	}
}
