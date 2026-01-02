<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector;

use Bitrix\Crm\Integration\AI\ContextCollector\EntityCollector\Settings;
use Bitrix\Crm\Integration\AI\Contract\ContextCollector;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use CCrmOwnerType;

final class EntityCollector implements ContextCollector
{
	private readonly Settings $settings;
	private readonly Factory $factory;

	public function __construct(
		private readonly int $entityTypeId,
		private readonly Context $context,
	)
	{
		$this->settings = new Settings();
		$this->factory = Container::getInstance()->getFactory($this->entityTypeId);
	}

	/**
	 * @param callable(Settings $settings): void $configurator
	 * @return $this
	 */
	public function configure(callable $configurator): self
	{
		$configurator($this->settings);

		return $this;
	}

	public function collect(): array
	{
		$collectorsResult = (new CollectionCollector($this->buildCollectors()))->collect();

		return [
			'name' => CCrmOwnerType::GetCategoryCaption($this->entityTypeId),
			'entity_type_id' => $this->entityTypeId,
			...$collectorsResult,
		];
	}

	/**
	 * @return array<string, ContextCollector>
	 */
	private function buildCollectors(): array
	{
		$collectors = [];

		if ($this->factory->isCategoriesSupported())
		{
			if ($this->settings->isCollectCategories())
			{
				$collectors['categories'] = (new CategoriesCollector($this->entityTypeId, $this->context))
					->setStageSettings($this->settings->stages())
					->setUserFieldsSettings($this->settings->userFields());
			}

			return $collectors;
		}

		if ($this->settings->userFields()->isCollect())
		{
			$collectors['user_fields'] = (new UserFieldsCollector($this->entityTypeId, $this->context))
				->setUserFieldsReceiveStrategy(new UserFieldsReceiveStrategy\ViaFactory($this->factory))
				->setSettings($this->settings->userFields());
		}

		if ($this->factory->isStagesSupported() && $this->settings->stages()->isCollect())
		{
			$collectors['stages'] = (new StagesCollector($this->entityTypeId, null, $this->context))
				->setSettings($this->settings->stages());
		}

		return $collectors;
	}
}
