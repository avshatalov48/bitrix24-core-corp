<?php

namespace Bitrix\Rpa\Components;

use Bitrix\Main\Localization\Loc;
use Bitrix\Rpa\Integration\Bizproc\TaskManager;
use Bitrix\Rpa\UserField\Display;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Buttons;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Model\TypeTable;
use Bitrix\Rpa\Filter;

abstract class ItemList extends Base
{
	/** @var \Bitrix\Rpa\Model\Type */
	protected $type;
	/** @var \Bitrix\Main\Filter\Filter */
	protected $filter;
	/** @var Filter\Item\Provider */
	protected $itemProvider;
	/** @var Filter\Item\UfProvider */
	protected $itemUfProvider;
	protected $display;

	protected $eventIds = [];

	public function onPrepareComponentParams($arParams): array
	{
		$this->fillParameterFromRequest('typeId', $arParams);

		return parent::onPrepareComponentParams($arParams);
	}

	protected function init(): void
	{
		parent::init();
		if($this->getErrors())
		{
			return;
		}

		$typeId = (int) $this->arParams['typeId'];
		if($typeId > 0)
		{
			$this->type = Driver::getInstance()->getType($typeId);
		}
		if(!$this->type)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('RPA_NOT_FOUND_ERROR'));
			return;
		}

		$this->display = new Display($this->type);

		if(!Driver::getInstance()->getUserPermissions()->canViewType($this->type->getId()))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('RPA_ACCESS_DENIED'));
			return;
		}

		if(!$this->getErrors())
		{
			$director = Driver::getInstance()->getDirector();
			$scenarios = $director->getScenariosForType($this->type);
			if($scenarios->count() > 0)
			{
				$result = $scenarios->playAll();
				if(!$result->isSuccess())
				{
					$this->errorCollection->add($result->getErrors());
				}
				else
				{
					$resultData = $result->getData();
					if(isset($resultData['eventIds']) && is_array($resultData['eventIds']))
					{
						$this->eventIds = array_merge($this->eventIds, $resultData['eventIds']);
					}
				}
			}
		}

		if(!$this->getErrors())
		{
			$this->setLastVisitedTypeId($this->getTypeId());
		}

		$settings = new Filter\Item\Settings([
			'ID' => $this->getFilterId(),
		], $this->type);

		$this->itemProvider = new Filter\Item\Provider($settings);
		$this->itemUfProvider = new Filter\Item\UfProvider($settings);

		$this->filter = new \Bitrix\Main\Filter\Filter($settings->getID(), $this->itemProvider, [$this->itemUfProvider]);
	}

	protected function getDisplay(): Display
	{
		return $this->display;
	}

	protected function getFilterId(): ?string
	{
		if($this->type)
		{
			return 'rpa_items_filter_'.$this->type->getId();
		}

		return null;
	}

	protected function getGridId(): ?string
	{
		if($this->type)
		{
			return 'rpa_items_grid_'.$this->type->getId();
		}

		return null;
	}

	protected function prepareFilter(): array
	{
		return [
			'FILTER_ID' => $this->getFilterId(),
			'GRID_ID' => $this->getGridId(),
			'FILTER' => $this->getDefaultFilterFields(),
			'FILTER_PRESETS' => $this->getDefaultFilterPresets(),
			'DISABLE_SEARCH' => false,
			'ENABLE_LABEL' => true,
			'RESET_TO_DEFAULT_MODE' => true,
			'ENABLE_LIVE_SEARCH' => true,
		];
	}

	protected function getDefaultFilterFields(): array
	{
		return $this->filter->getFieldArrays();
	}

	protected function getDefaultFilterPresets(): array
	{
		return [
			'inWork' => [
				'name' => Loc::getMessage('RPA_FILTER_PRESET_IN_WORK'),
				'default' => true,
				'fields' => [
					'STAGE_SEMANTIC' => [
						'STAGE_SEMANTIC_WORK',
					],
				],
			],
			'my' => [
				'name' => Loc::getMessage('RPA_FILTER_PRESET_MY'),
				'fields' => [
					'CREATED_BY' => Driver::getInstance()->getUserId(),
				],
			],
			'final' => [
				'name' => Loc::getMessage('RPA_FILTER_PRESET_FINAL'),
				'fields' => [
					'STAGE_SEMANTIC' => [
						'STAGE_SEMANTIC_SUCCESS',
						'STAGE_SEMANTIC_FAIL'
					]
				],
			],
		];
	}

	protected function getListFilter(): array
	{
		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->getFilterId());
		$filterFields = $this->filter->getFieldArrays();
		$requestFilter = $filterOptions->getFilter($this->getDefaultFilterFields());

		$filter = [];
		$this->itemProvider->prepareListFilter($filter, $requestFilter);
		$this->itemUfProvider->prepareListFilter($filter, $filterFields, $requestFilter);

		$filter = array_merge($filter, Driver::getInstance()->getUserPermissions()->getFilterForViewableItems($this->type));

		return $filter;
	}

	protected function getTypeId(): ?int
	{
		if($this->type)
		{
			return $this->type->getId();
		}

		return null;
	}

	protected function getToolbarParameters(): array
	{
		if(!$this->type)
		{
			return [];
		}

		\Bitrix\UI\Toolbar\Facade\Toolbar::setTitleMinWidth(158);

		$urlManager = Driver::getInstance()->getUrlManager();
		$userPermissions = Driver::getInstance()->getUserPermissions();

		$buttons = [];

		$buttons[ButtonLocation::AFTER_FILTER][] = new Buttons\Button([
			'color' => Buttons\Color::LIGHT_BORDER,
			'className' => 'ui-btn ui-btn-themes ui-btn-light-border ui-btn-dropdown ui-toolbar-btn-dropdown',
			'text' => $this->type->getTitle(),
			'menu' => [
				'items' => $this->getToolbarTypes(),
			],
			'maxWidth' => '400px',
		]);

		$isTypeSettingsRestricted = Driver::getInstance()->getBitrix24Manager()->isTypeSettingsRestricted($this->type->getId());

		if($userPermissions->canAddItemsToType($this->type->getId()))
		{
			$buttons[ButtonLocation::AFTER_TITLE][] = new Buttons\Button(
				[
					'color' => Buttons\Color::PRIMARY,
					'className' => 'ui-btn ui-btn-themes',
					'text' => Loc::getMessage('RPA_COMMON_ADD'),
					'link' => $isTypeSettingsRestricted ? null : $urlManager->getItemDetailUrl($this->type->getId(), 0)->getPath(),
					'onclick' => $isTypeSettingsRestricted ? new Buttons\JsHandler('BX.Rpa.Manager.Instance.showFeatureSlider') : null,
					'maxWidth' => '400px',
				]
			);
		}

		if($userPermissions->canModifyType($this->type->getId()))
		{
			$onClick = $isTypeSettingsRestricted ? new Buttons\JsHandler('BX.Rpa.Manager.Instance.showFeatureSlider') : new Buttons\JsHandler('BX.Rpa.Manager.Instance.closeSettingsMenu');
			$buttons[ButtonLocation::AFTER_FILTER][] = new Buttons\SettingsButton([
				'menu' => [
					'id' => 'rpa-toolbar-settings-menu',
					'items' => [
						[
							'text' => Loc::getMessage('RPA_COMMON_TYPE_SETTINGS'),
							'href' => $isTypeSettingsRestricted ? null : $urlManager->getTypeDetailUrl($this->type->getId()),
							'onclick' => $onClick,
						],
						[
							'text' => Loc::getMessage('RPA_COMMON_STAGES_SETTINGS'),
							'href' => $isTypeSettingsRestricted ? null : $urlManager->getStageListUrl($this->type->getId()),
							'onclick' => $onClick,
						],
						[
							'text' => Loc::getMessage('RPA_COMMON_FIELDS_SETTINGS'),
							'href' => $isTypeSettingsRestricted ? null : $urlManager->getTypeFieldsListUrl($this->type->getId()),
							'onclick' => $onClick,
						],
					],
				],
			]);
		}

		$tasks = 0;
		$taskManager = Driver::getInstance()->getTaskManager();
		if($taskManager)
		{
			$tasks = count($taskManager->getUserIncompleteTasksForType($this->getTypeId()));
		}

		return array_merge(parent::getToolbarParameters(), [
			'buttons' => $buttons,
			'filter' => $this->prepareFilter(),
			'robotsUrl' => $urlManager->getAutomationUrl($this->type->getId()),
			'views' => [
				'kanban' => [
					'title' => Loc::getMessage('RPA_COMMON_KANBAN'),
					'url' => $urlManager->getKanbanUrl($this->type->getId())
				],
				'list' => [
					'title' => Loc::getMessage('RPA_COMMON_LIST'),
					'url' => $urlManager->getItemsListUrl($this->type->getId()),
				],
			],
			'tasks' => $tasks,
			'tasksUrl' => $urlManager->getUserItemsUrlWithTasks($this->type->getId()),
			'tasksFilter' => [
				'filterId' => $this->filter->getID(),
				'fields' => [
					TaskManager::TASKS_FILTER_FIELD => TaskManager::TASKS_FILTER_HAS_TASKS_VALUE,
				],
			],
		]);
	}

	protected function getToolbarTypes(): array
	{
		$types = [];

		$list = TypeTable::getList([
			'filter' => Driver::getInstance()->getUserPermissions()->getFilterForViewableTypes(),
		]);
		while($type = $list->fetchObject())
		{
			$types[] = [
				'text' => htmlspecialcharsbx($type->getTitle()),
				'href' => Driver::getInstance()->getUrlManager()->getUserItemsUrl($type->getId()),
			];
		}

		return $types;
	}
}
