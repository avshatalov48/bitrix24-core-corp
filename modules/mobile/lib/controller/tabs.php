<?php

namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Error;
use Bitrix\Mobile\AppTabs\Menu;
use Bitrix\Mobile\AppTabs\MenuNew;
use Bitrix\Mobile\Config\Feature;
use Bitrix\Mobile\Feature\MenuFeature;
use Bitrix\Mobile\Tab\Manager;

class Tabs extends \Bitrix\Main\Engine\Controller
{
	public function setConfigAction(array $config = [])
	{
		$manager = new Manager();

		return $manager->setCustomConfig($config);
	}

	public function getDataAction()
	{
		$manager = new Manager();
		$result = [
			'presets' => [
				'current' => $manager->getPresetName(),
				'list' => $manager->getPresetList(),
			],
			'tabs' => [
				'list' => $manager->getAllTabIDs(),
			],
		];

		$activeTabs = $manager->getActiveTabs();
		$result['tabs']['current'] = array_reduce(
			array_keys($activeTabs),
			static function ($result, $tabId) use ($manager, $activeTabs) {
				$tabInstance = $manager->getTabInstance($tabId);
				$result[$tabId] = [
					'sort' => $activeTabs[$tabId],
					'title' => $tabInstance->getTitle(),
					'canBeRemoved' => $tabInstance->canBeRemoved(),
					'canChangeSort' => $tabInstance->canChangeSort(),
					'iconId' => $tabInstance->getIconId(),
				];

				return $result;
			}, []);

		$result['tabs']['list'] = array_reduce(
			$manager->getAllTabIDs(),
			static function ($result, $tabId) use ($manager)
			{
				$instance = $manager->getTabInstance($tabId);

				$result[$tabId] = [
					'title' => $instance->getTitle(),
					'shortTitle' => $instance->getShortTitle(),
					'iconId' => $instance->getIconId(),
				];

				$isMenuFeatureEnabled = Feature::isEnabled(MenuFeature::class);

				$menuId = $isMenuFeatureEnabled ? (new MenuNew())->getId() : (new Menu())->getId();

				if ($tabId === $menuId)
				{
					if ($isMenuFeatureEnabled)
					{
						$result[$tabId]['imageUrl'] = $instance->getImageUrl();
						$result[$tabId]['name'] = $instance->getLastAndSecondName();
					}

					$result[$tabId]['isAvatarEnabled'] = $isMenuFeatureEnabled;
				}

				return $result;
			}, []);


		return $result;
	}

	public function setPresetAction($name)
	{
		$manager = new Manager();
		$result = $manager->setPresetName($name);

		if ($result == null)
		{
			$this->addError(new Error('Preset not found', 404));
		}

		return $result;
	}
}