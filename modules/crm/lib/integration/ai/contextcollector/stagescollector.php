<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\EO_Status_Collection;
use Bitrix\Crm\Integration\AI\ContextCollector\EntityCollector\StageSettings;
use Bitrix\Crm\Integration\AI\ContextCollector\StagesQueryModificator\Executor;
use Bitrix\Crm\Integration\AI\ContextCollector\StagesQueryModificator\ExecutorResultValues;
use Bitrix\Crm\Integration\AI\ContextCollector\StagesQueryModificator\ItemsCountQueryModificator;
use Bitrix\Crm\Integration\AI\ContextCollector\StagesQueryModificator\ItemsSumQueryModificator;
use Bitrix\Crm\Integration\AI\Contract\ContextCollector;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use CCrmOwnerType;

final class StagesCollector implements ContextCollector
{
	private readonly UserPermissions $permissions;
	private readonly PermissionEntityTypeHelper $permissionHelper;
	private readonly ?Factory $factory;
	private StageSettings $settings;

	private static array $additionalStageData = [];

	public function __construct(
		private readonly int $entityTypeId,
		private readonly ?int $categoryId,
		private readonly Context $context,
	)
	{
		$this->permissions = Container::getInstance()->getUserPermissions($this->context->userId());
		$this->permissionHelper = new PermissionEntityTypeHelper($this->entityTypeId);

		$this->factory = Container::getInstance()->getFactory($this->entityTypeId);

		$this->settings = new StageSettings();
	}

	public function setSettings(StageSettings $stageSettings): self
	{
		$this->settings = $stageSettings;

		return $this;
	}

	public function collect(): array
	{
		if ($this->factory === null || !$this->factory->isStagesEnabled())
		{
			return [];
		}

		$additionalData = $this->getAdditionalData();

		$result = [];
		foreach ($this->stages() as $stage)
		{
			$info = [
				'id' => $stage->getStatusId(),
				'name' => $stage->getName(),
				'semantics' => PhaseSemantics::isDefined($stage->getSemantics())
					? $stage->getSemantics()
					: PhaseSemantics::PROCESS
				,
				'sort' => $stage->getSort(),
			];

			if ($this->settings->isCollectItemsCount())
			{
				$info['items_count'] = $additionalData->getItemsCount($stage->getStatusId());
			}

			if ($this->settings->isCollectItemsSum())
			{
				$info['items_sum'] = $additionalData->getItemsSum($stage->getStatusId())?->toArray();
			}

			$result[] = $info;
		}

		return $result;
	}

	private function stages(): EO_Status_Collection
	{
		return $this->factory->getStages($this->categoryId);
	}

	private function getAdditionalData(): ExecutorResultValues
	{
		if (isset(self::$additionalStageData[$this->entityTypeId]))
		{
			return self::$additionalStageData[$this->entityTypeId];
		}

		$queryModificators = $this->getAdditionalDataQueryModificators();
		if (empty($queryModificators))
		{
			self::$additionalStageData[$this->entityTypeId] = new ExecutorResultValues();

			return self::$additionalStageData[$this->entityTypeId];
		}

		$query = $this->factory->getDataClass()::query();
		$query = $this->permissions
			->itemsList()
			->applyAvailableItemsQueryParameters($query, $this->getPermissionEntityTypes());

		self::$additionalStageData[$this->entityTypeId] = (new Executor($queryModificators))
			->execute($query);

		return self::$additionalStageData[$this->entityTypeId];
	}

	private function getPermissionEntityTypes(): array
	{
		if (!$this->factory->isCategoriesSupported())
		{
			return [
				CCrmOwnerType::ResolveName($this->entityTypeId),
			];
		}

		$categories = $this->factory->getCategories();
		$categoryIds = array_map(static fn (Category $category) => $category->getId(), $categories);

		return array_map([$this->permissionHelper, 'getPermissionEntityTypeForCategory'], $categoryIds);
	}

	private function getAdditionalDataQueryModificators(): array
	{
		$result = [];
		if ($this->settings->isCollectItemsCount())
		{
			$result[] = new ItemsCountQueryModificator($this->factory);
		}

		if ($this->settings->isCollectItemsSum())
		{
			$result[] = new ItemsSumQueryModificator($this->factory);
		}

		return $result;
	}
}
