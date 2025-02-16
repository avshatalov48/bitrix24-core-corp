<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item;

use Bitrix\Crm\CallList\CallList;
use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\CallList\Group\CreateCallListChildAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group\AssignChildAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group\ConvertChildAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group\CreateTaskChildAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group\ExcludeChildAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group\MergeChildAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group\ObserversChildAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group\RefreshAccountingDataChildAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group\RestartAutomationChildAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group\SetCategoryChildAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group\SetExportChildAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group\SetOpenedChildAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group\SetStageChildAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Sender\Group\AddItemsToSegmentChildAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Sender\Group\AddLetterChildAction;
use Bitrix\Crm\Exclusion;
use Bitrix\Crm\Merger\EntityMergerFactory;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UserField\UserFieldManager;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\ModuleManager;

final class ItemGroupAction extends BaseItemGroupAction
{
	protected function prepareChildItems(): array
	{
		$actions = [];

		if ($this->canUpdateItemsInCategory())
		{
			$actions = array_merge($actions, $this->getActionsForUserWithUpdateAccess());
		}

		if (
			Exclusion\Manager::isEntityTypeSupported($this->factory->getEntityTypeId())
			&& Exclusion\Manager::checkCreatePermission()
		)
		{
			$actions[] = new ExcludeChildAction($this->factory->getEntityTypeId());
		}

		return $actions;
	}

	private function getActionsForUserWithUpdateAccess(): array
	{
		$actions = [];

		if (\Bitrix\Crm\Integration\Sender\GridPanel::isEntityTypeSupported($this->factory->getEntityTypeId()))
		{
			if (\Bitrix\Crm\Integration\Sender\GridPanel::canCurrentUserAddLetters())
			{
				$actions[] = new AddLetterChildAction($this->factory->getEntityTypeId());
			}
			if (\Bitrix\Crm\Integration\Sender\GridPanel::canCurrentUserModifySegments())
			{
				$actions[] = new AddItemsToSegmentChildAction($this->factory->getEntityTypeId());
			}
		}

		if (
			ModuleManager::isModuleInstalled('tasks')
			&& $this->factory->isUseInUserfieldEnabled()
			&& UserFieldManager::isEnabledInTasksUserField($this->factory->getEntityName())
		)
		{
			$actions[] = new CreateTaskChildAction($this->factory->getEntityTypeId());
		}

		if (!$this->gridSettings->isAllItemsCategory())
		{
			if ($this->factory->isStagesEnabled())
			{
				$actions[] = new SetStageChildAction($this->factory, $this->gridSettings->getCategoryId());
			}

			if (
				$this->factory->isCategoriesEnabled()
				&& !$this->isInSystemCategory() // don't show on sign contr-agents and catalog suppliers
			)
			{
				$actions[] = new SetCategoryChildAction($this->factory);
			}
		}

		$actions[] = new AssignChildAction($this->factory->getEntityTypeId());

		if (
			ObserversChildAction::isEntityTypeSupported($this->factory)
			&& ObserversChildAction::isChangeObserverPermitted($this->factory->getEntityTypeId(), $this->userPermissions, $this->gridSettings->getCategoryId())
		)
		{
			$actions[] = new ObserversChildAction($this->factory->getEntityTypeId());
		}

		if (
			ConvertChildAction::isEntityTypeSupported($this->factory->getEntityTypeId())
			&& ConvertChildAction::isConversionPermitted($this->factory->getEntityTypeId(), $this->userPermissions)
		)
		{
			$actions[] = new ConvertChildAction($this->factory->getEntityTypeId(), $this->userPermissions);
		}

		if (RefreshAccountingDataChildAction::isEntityTypeSupported($this->factory->getEntityTypeId()))
		{
			$actions[] = new RefreshAccountingDataChildAction($this->factory->getEntityTypeId());
		}

		if (
			$this->canDeleteItemsInCategory() // if the user has both update and delete permissions
			&& EntityMergerFactory::isEntityTypeSupported($this->factory->getEntityTypeId())
		)
		{
			$entityTypeId = $this->factory->getEntityTypeId();
			$mergerUrl = Container::getInstance()->getRouter()->getEntityMergeUrl($entityTypeId);

			$actions[] = new MergeChildAction($entityTypeId, $mergerUrl);
		}

		if (
			ModuleManager::isModuleInstalled('voximplant')
			&& CallList::isEntityTypeSupported($this->factory->getEntityTypeId())
		)
		{
			$actions[] = new CreateCallListChildAction($this->factory->getEntityTypeId());
		}

		$actions[] = new SetOpenedChildAction($this->factory->getEntityTypeId());

		if (SetExportChildAction::isEntityTypeSupported($this->factory->getEntityTypeId()))
		{
			$actions[] = new SetExportChildAction($this->factory->getEntityTypeId());
		}

		if ($this->factory->isAutomationEnabled() && \Bitrix\Crm\Automation\Factory::isAutomationRunnable($this->factory->getEntityTypeId()))
		{
			$actions[] = new RestartAutomationChildAction($this->factory->getEntityTypeId());
		}

		return $actions;
	}

	private function isInSystemCategory(): bool
	{
		$category = $this->getCategory();

		return $category && $category->getIsSystem();
	}

	private function getCategory(): ?Category
	{
		if (!$this->factory->isCategoriesEnabled())
		{
			throw new InvalidOperationException();
		}

		if ($this->gridSettings->isAllItemsCategory() || $this->gridSettings->getCategoryId() === null)
		{
			return null;
		}

		return $this->factory->getCategory($this->gridSettings->getCategoryId());
	}
}
