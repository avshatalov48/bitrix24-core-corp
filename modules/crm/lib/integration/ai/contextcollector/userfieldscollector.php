<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector;

use Bitrix\Crm\Integration\AI\ContextCollector\EntityCollector\UserFieldsSettings;
use Bitrix\Crm\Integration\AI\Contract\ContextCollector;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;

final class UserFieldsCollector implements ContextCollector
{
	private UserPermissions\EntityPermissions\Type $permissions;
	private ?Factory $factory;
	private UserFieldsSettings $settings;
	private UserFieldsReceiveStrategy $userFieldsReceiveStrategy;

	public function __construct(
		private readonly int $entityTypeId,
		private readonly Context $context,
	)
	{
		$this->factory = Container::getInstance()->getFactory($this->entityTypeId);
		$this->permissions = Container::getInstance()->getUserPermissions($this->context->userId())->entityType();

		$this->settings = new UserFieldsSettings();
		$this->userFieldsReceiveStrategy = new UserFieldsReceiveStrategy\ViaFactory($this->factory);
	}

	public function setSettings(UserFieldsSettings $settings): self
	{
		$this->settings = $settings;

		return $this;
	}

	public function setUserFieldsReceiveStrategy(UserFieldsReceiveStrategy $strategy): self
	{
		$this->userFieldsReceiveStrategy = $strategy;

		return $this;
	}

	public function collect(): array
	{
		if ($this->factory === null || !$this->permissions->canReadItems($this->entityTypeId))
		{
			return [];
		}

		$result = [];
		foreach ($this->userFieldsReceiveStrategy->getAll() as $field)
		{
			$info = [
				'title' => $field->getTitle(),
				'type' => $field->getType(),
			];

			if ($this->settings->isCollectName())
			{
				$info['name'] = $field->getName();
			}

			$result[] = $info;
		}

		return $result;
	}
}
