<?php

namespace Bitrix\ImMobile\NavigationTab\Tab;

use Bitrix\MobileApp;

abstract class BaseRecent implements TabInterface
{
	protected array $params = [];
	
	public function __construct()
	{
		$this->params = $this->getParams();
	}

	abstract protected function getTabTitle(): ?string;
	abstract protected function getComponentCode(): string;
	abstract protected function getComponentName(): string;
	abstract protected function getParams(): array;
	abstract protected function getWidgetSettings(): array;
	
	public function isNeedMergeSharedParams(): bool
	{
		return true;
	}
	
	public function mergeParams(array $params): void
	{
		$this->params = array_merge($params, $this->params);
	}
	
	public function getComponentData(): ?array
	{
		if (!$this->isAvailable())
		{
			return null;
		}
		
		return [
			"id" => $this->getId(),
			"title" => $this->getTabTitle(),
			"component" => [
				"name" => $this->getWidgetName(),
				"componentCode" => $this->getComponentCode(),
				"scriptPath" => MobileApp\Janative\Manager::getComponentPath($this->getComponentName()),
				'params' => $this->params,
				'settings' => $this->getWidgetSettings(),
			]
		];
	}
	
	protected function getWidgetName(): string
	{
		return 'JSComponentChatRecent';
	}
}