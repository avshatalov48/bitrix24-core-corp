<?

namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Error;
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
			"presets" => [
				"current" => $manager->getPresetName(),
				"list" => $manager->getPresetList()
			],
			"tabs" => [
				"list"=>$manager->getAllTabIDs()
			],
		];

		$activeTabs = $manager->getActiveTabs();
		$result["tabs"]["current"] = array_reduce(
			array_keys($activeTabs),
			function ($result, $tabId) use ($manager, $activeTabs) {
				$result[$tabId] = [
					"sort" => $activeTabs[$tabId],
					"title" => $manager->getTabInstance($tabId)->getTitle(),
					"canBeRemoved" => $manager->getTabInstance($tabId)->canBeRemoved(),
					"canChangeSort" =>$manager->getTabInstance($tabId)->canChangeSort(),
				];
				return $result;
			}, []);

		$result["tabs"]["list"] = array_reduce(
			$manager->getAllTabIDs(),
			function ($result, $tabId) use ($manager)
			{
				$instance = $manager->getTabInstance($tabId);
				$result[$tabId] = [
					"title" => $instance->getTitle(),
					"shortTitle" => $instance->getShortTitle(),
				];
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