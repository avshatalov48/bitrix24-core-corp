<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector;

use Bitrix\Crm\Integration\AI\ContextCollector\EntityCollector\StageSettings;
use Bitrix\Crm\Integration\AI\ContextCollector\EntityCollector\UserFieldsSettings;
use Bitrix\Crm\Integration\AI\Contract\ContextCollector;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;

final class CategoriesCollector implements ContextCollector
{
	private readonly UserPermissions\EntityPermissions\Category $permissions;
	private StageSettings $stageSettings;
	private UserFieldsSettings $userFieldsSettings;

	public function __construct(
		private readonly int $entityTypeId,
		private readonly Context $context,
	)
	{
		$this->stageSettings = new StageSettings();
		$this->userFieldsSettings = new UserFieldsSettings();

		$this->permissions = Container::getInstance()
			->getUserPermissions($this->context->userId())
			->category();
	}

	public function setStageSettings(StageSettings $settings): self
	{
		$this->stageSettings = $settings;

		return $this;
	}

	public function setUserFieldsSettings(UserFieldsSettings $settings): self
	{
		$this->userFieldsSettings = $settings;

		return $this;
	}

	public function collect(): array
	{
		$factory = Container::getInstance()->getFactory($this->entityTypeId);
		if ($factory === null || !$factory->isCategoriesSupported())
		{
			return [];
		}

		$result = [];
		foreach ($factory->getCategories() as $category)
		{
			if (!$this->permissions->canReadItems($category))
			{
				continue;
			}

			$info = [
				'id' => $category->getId(),
				'name' => $category->getName(),
			];

			if ($factory->isStagesSupported() && $this->stageSettings->isCollect())
			{
				$info['stages'] = (new StagesCollector($this->entityTypeId, $category->getId(), $this->context))
					->setSettings($this->stageSettings)
					->collect();
			}

			if ($this->userFieldsSettings->isCollect())
			{
				$info['user_fields'] = (new UserFieldsCollector($this->entityTypeId, $this->context))
					->setSettings($this->userFieldsSettings)
					->setUserFieldsReceiveStrategy(
						new UserFieldsReceiveStrategy\ViaCardView(
							$factory,
							$category->getId(),
							$this->context->userId(),
						),
					)
					->collect();
			}

			$result[] = $info;
		}

		return $result;
	}
}
