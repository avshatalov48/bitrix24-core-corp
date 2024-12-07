<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Intranet\Settings\Controls\Section;
use Bitrix\Intranet\Settings\Search\SearchEngine;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;

class ToolsSettings extends AbstractSettings
{
	public const TYPE = 'tools';

	private array $baseTools;

	public function __construct(array $data = [])
	{
		parent::__construct($data);
		$this->baseTools = Tools\ToolsManager::getInstance()->getToolList();
	}

	public function save(): Result
	{
		$isChanged = false;

		foreach ($this->baseTools as $tool)
		{
			if (isset($this->data[$tool->getOptionCode()]))
			{
				if ($this->data[$tool->getOptionCode()] === 'Y')
				{
					$tool->enable();
					$isChanged = true;
				}
				elseif ($this->data[$tool->getOptionCode()] === 'N')
				{
					$tool->disable();
					$isChanged = true;
				}
			}

			$subgroups = $tool->getSubgroups();

			foreach ($subgroups as $subgroup)
			{
				if (isset($this->data[$subgroup['code']]))
				{
					if ($this->data[$subgroup['code']] === 'Y')
					{
						$tool->enableSubgroup($subgroup['code']);
						$isChanged = true;
					}
					elseif ($this->data[$subgroup['code']] === 'N')
					{
						$tool->disableSubgroup($subgroup['code']);
						$isChanged = true;
					}
				}
			}
		}

		if (isset($this->data['tools-sort']))
		{
			try
			{
				$sortTools = Json::decode($this->data['tools-sort']);
				Tools\ToolsManager::getInstance()->getSorter()->saveSort($sortTools);
				$isChanged = true;
				$analyticEvent = new AnalyticsEvent('changed_position_tool_by_dragdrop', 'settings', 'tools');
				$toolSortByMenuId = [];

				foreach ($this->baseTools as $tool)
				{
					$toolSortByMenuId[] = $tool->getMenuItemId();
				}

				foreach ($sortTools as $index => $savedToolMenuId)
				{
					$startIndex = array_search($savedToolMenuId, $toolSortByMenuId, true);

					if (is_int($startIndex) && $startIndex !== $index)
					{
						$analyticEvent->setType($savedToolMenuId)
							->setP1((string)$index)
							->setP2((string)$startIndex)
							->send();
					}
				}
			}
			catch (ArgumentException){}
		}

		if ($isChanged)
		{
			Tools\ToolsManager::getInstance()->getFirstPageChanger()->changeForAllUsers();
		}

		return new Result();
	}

	public function get(): SettingsInterface
	{
		$data = [];

		$data['sectionTools'] = new Section(
			'settings-tools-section-tools',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_TOOLS_SHOW'),
			'ui-icon-set --service',
			true,
			canCollapse: false,
		);

		foreach ($this->baseTools as $tool)
		{
			$data['tools'][$tool->getId()] = [
				'enabled' => $tool->isEnabled() && $tool->isEnabledSubgroups(),
				'name' => $tool->getName(),
				'menuId' => $tool->getMenuItemId(),
				'code' => $tool->getOptionCode(),
				'subgroups' => $tool->getSubgroups(),
				'settings-path' => $tool->getSettingsPath() ? str_replace("#USER_ID#", CurrentUser::get()->getId(), $tool->getSettingsPath()) : null,
				'settings-title' => $tool->getSettingsTitle(),
				'infohelper-slider' => $tool->getInfoHelperSlider(),
				'default' => $tool->isDefault(),
			];
		}

		return new static($data);
	}

	public function find(string $query): array
	{
		$searchEngine = SearchEngine::initWithDefaultFormatter([
			'settings-tools-section-tools' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_TOOLS_SHOW')
		]);

		return $searchEngine->find($query);
	}
}