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
	abstract protected function isWidgetSupported(): bool;
	abstract protected function getWidgetSettings(): array;

	/** @deprecated  */
	abstract public function getComponentCode(): string;
	/** @deprecated  */
	abstract protected function getComponentName(): string;
	/** @deprecated  */
	abstract protected function getParams(): array;

	public function isWidgetAvailable()
	{
		return $this->isAvailable() && $this->isWidgetSupported();
	}

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
			],
			'spotlightId' => $this->getId()
		];
	}
	
	public function getWidgetData(): ?array
	{
		if (!$this->isAvailable())
		{
			return null;
		}
		
		return [
			"id" => $this->getId(),
			"title" => $this->getTabTitle(),
			'widget' => [
				'name' => 'chat.recent',
				'code' => $this->getId(),
				'settings' => [
					...$this->getWidgetSettings(),
					'objectName'=> $this->getId(),
				],
			],
			'spotlightId' => $this->getId(),
		];
	}

	protected function getWidgetName(): string
	{
		return 'JSComponentChatRecent';
	}
}
