<?php

namespace Bitrix\Mobile\Profile\Tab;

use Bitrix\Main\Loader;
use Bitrix\Mobile\Profile\Enum\TabContextType;
use Bitrix\Mobile\Profile\Enum\TabType;
use Bitrix\MobileApp\Janative\Manager;

abstract class BaseProfileTab
{
	protected int $viewerId;
	protected int $ownerId;

	public function __construct(int $viewerId, int $ownerId)
	{
		$this->viewerId = $viewerId;
		$this->ownerId = $ownerId;
	}

	abstract public function getType(): TabType;

	abstract public function getContextType(): TabContextType;

	abstract public function isAvailable(): bool;

	abstract public function getTitle(): string;

	public function getParams(): array
	{
		return [];
	}

	public function getComponentName(): ?string
	{
		return null;
	}

	public function getComponent(): ?array
	{
		Loader::includeModule('mobileapp');

		return [
			'name' => 'JSStackComponent',
			'componentCode' => $this->getType()->value,
			'scriptPath' => Manager::getComponentPath($this->getComponentName()),
			'rootWidget' => [
				'name' => 'layout',
				'settings' => [
					'objectName' => 'layout',
				],
			],
			'params' => $this->getParams(),
		];
	}

	public function getWidget(): ?array
	{
		return [
			'name' => 'layout',
			'code' => $this->getType()->value,
			'settings' => [
				'objectName' => 'layout',
			],
			'params' => $this->getParams(),
		];
	}

	public function isComponent(): bool
	{
		return $this->getContextType() === TabContextType::COMPONENT;
	}

	public function isWidget(): bool
	{
		return $this->getContextType() === TabContextType::WIDGET;
	}
}
