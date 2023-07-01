<?

namespace Bitrix\Mobile\Tab;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\FileNotFoundException;
use Bitrix\Main\IO\FileSystemEntry;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Json;
use Bitrix\Mobile\Context;

class Manager
{
	const tabDirectoryPath = "/bitrix/modules/mobile/tabs/";
	const configPath = "/bitrix/modules/mobile/.tab_config.php";
	const maxCount = 5;
	const maxSortValue = 1000;


	/** @type array */
	private $tabList;
	/** @type Context */
	private $context;
	private $config;

	/**
	 * Manager constructor.
	 * @param {Context} $options
	 * @throws FileNotFoundException
	 * @throws LoaderException
	 * @throws SystemException
	 */
	public function __construct($context = null)
	{
		Loader::includeModule("mobileapp");
		if ($context == null)
		{
			$this->context = new Context();
		}
		else
		{
			$this->context = $context;
		}

		$this->tabList = [];

		$this->config = include(Application::getDocumentRoot() . self::configPath);
		Loc::loadLanguageFile(Application::getDocumentRoot() . self::tabDirectoryPath . "/tabs.php");
		$tabDirectory = new Directory(Application::getDocumentRoot() . self::tabDirectoryPath);
		$tabsFiles = $tabDirectory->getChildren();
		/**
		 * @var FileSystemEntry $tab
		 */
		foreach ($tabsFiles as $tab)
		{
			if ($tab->isFile() && $tab->getExtension() == "php")
			{
				require_once($tab->getPath());
			}
		}

		$tabsDescription = $this->config["tabs"];
		foreach ($tabsDescription as $tabData)
		{
			$class = $tabData["class"] ?? null;
			$file = $tabData["file"] ?? null;

			if ($file)
			{
				require_once(Application::getDocumentRoot() . $file);
			}

			if (class_exists($class))
			{
				if (array_search("\\Bitrix\\Mobile\\Tab\\Tabable", class_implements($class)) !== false)
				{
					throw new SystemException("Tab class '{$class}' not implements \\Bitrix\\Mobile\\Tab\\Tabable");
				}
			}
			else
			{
				continue;
			}

			/**
			 * @var Tabable $instance
			 */
			$instance = new $class();
			$instance->setContext($this->context);
			$this->tabList[$tabData["code"]] = $instance;
		}

		if ($savedConfig = $this->getUserPresetConfig())
		{
			$this->config["presets"]["manual"] = $savedConfig;
		}
	}

	/**
	 * Return data of active tabs
	 * @param array $ids
	 * @return array
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 */
	public function getActiveTabsData(array $ids = [])
	{
		$tabList = [];
		$tabConfig = null;
		if (empty($ids))
		{
			$ids = array_keys($this->getActiveTabs());
			$tabConfig = $this->getActiveTabs();
		}

		/**
		 * @var FileSystemEntry $tab
		 * @var Tabable $tab
		 */

		foreach ($ids as $tabId)
		{
			if ($this->getTabAvailabilityState($tabId))
			{
				$tabInstance = $this->getTabInstance($tabId);
				$tab = array_merge(["id"=>$tabInstance->getId(), "title"=>$tabInstance->getShortTitle()], $tabInstance->getData());
				if ($tabConfig != null && array_key_exists($tabId, $tabConfig))
				{
					$tab["sort"] = $tabConfig[$tabId];
				}
				$tabList[] = $tab;
			}
		}

		return $tabList;
	}

	/**
	 * Return instance of tab class
	 * @param string $id
	 * @return Tabable
	 */
	public function getTabInstance($id = null)
	{
		return $this->tabList[$id] ?? null;
	}

	/**
	 * Return list of active tabs
	 * @return array
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 */
	public function getActiveTabs()
	{
		$permanentTabs = $this->getRequiredTabs();
		$presetConfig = $this->getPresetConfig();
		if (empty($presetConfig))
		{
			$presetConfig = $this->setDefaultUserPreset();
		}
		return $this->resolveTabs($presetConfig, $permanentTabs);
	}

	public function getAllTabIDs($includeUnavailable = false)
	{
		return array_filter(array_keys($this->tabList), function ($tabId) use ($includeUnavailable) {
			return $this->getTabInstance($tabId)->isAvailable() || $includeUnavailable;
		});
	}

	/**
	 * Set up custom preset of tab list
	 * @param array $config
	 * @return array|SystemException
	 * @throws ArgumentException
	 * @throws ArgumentOutOfRangeException
	 */
	public function setCustomConfig(array $config = [])
	{
		global $CACHE_MANAGER;

		if (!is_array($config))
		{
			return new  SystemException("Tab config must be array", 400);
		}

		$config = array_map(function ($value) {
			if ($value >= self::maxSortValue)
			{
				$value = self::maxSortValue - 1;
			}
			return (int)$value;

		}, $config);
		Option::set("mobile", "tabs_{$this->context->userId}", Json::encode($config), $this->context->siteId);//set custom config
		Option::set("mobile", "tabs_preset_{$this->context->userId}", "manual", $this->context->siteId);//set reset preset
		$this->config["presets"]["manual"] = $config;
		$CACHE_MANAGER->ClearByTag('mobile_custom_menu' . $this->context->userId);

		return $this->getActiveTabs();
	}

	/**
	 * Resolve and return final configuration
	 * @param $config
	 * @param array $required
	 * @return array
	 */
	private function resolveTabs($config, $required = [])
	{
		$result = array_keys($required);
		$unchangeable = is_array($this->config["unchangeable"]) ? $this->config["unchangeable"] : [];
		$configKeys = array_diff(array_keys($config), $result);
		$sorts = array_merge($required, $config, $unchangeable);

		$tabs = array_reduce($configKeys, function ($result, $tabId) {
			if (count($result) < Manager::maxCount)
			{
				$result[] = $tabId;
			}

			return $result;
		}, $result);

		$result = array_filter($sorts, function ($tabId) use ($tabs) {
			return in_array($tabId, $tabs) && $this->getTabAvailabilityState($tabId);
		}, ARRAY_FILTER_USE_KEY);

		asort($result);
		return $result;
	}

	public function defaultConfig()
	{
		return $this->config["presets"]["default"];
	}

	/**
	 * Set up preset by name
	 * @param string $name
	 * @return array|null
	 * @throws ArgumentOutOfRangeException
	 * @throws ArgumentNullException
	 */
	public function setPresetName($name = "default")
	{
		global $CACHE_MANAGER;

		if (array_key_exists($name, $this->config["presets"]))
		{
			Option::set("mobile", "tabs_preset_{$this->context->userId}", $name, $this->context->siteId);//set preset name
			Option::set("mobile", "tabs_{$this->context->userId}", "", $this->context->siteId);//reset custom config
			$CACHE_MANAGER->ClearByTag('mobile_custom_menu' . $this->context->userId);
			return $this->getActiveTabs();
		}

		return null;
	}

	/**
	 * Return name of current preset
	 * @return string
	 * @throws ArgumentOutOfRangeException
	 * @throws ArgumentNullException
	 */
	public function getPresetName()
	{
		if (!($preset = Option::get("mobile", "tabs_preset_{$this->context->userId}", false, $this->context->siteId)))
		{
			$preset = "default";
		}

		return $preset;
	}

	/**
	 * Return configuration of current preset
	 * @return array|null
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 */
	public function getPresetConfig()
	{
		$preset = $this->getPresetName();
		if (isset($this->config["presets"][$preset]) && is_array($this->config["presets"][$preset]))
		{
			return $this->config["presets"][$preset];
		}

		return $this->config["defaultUserPreset"];
	}

	/**
	 * Return configuration of custom preset
	 * @return bool|mixed
	 * @throws ArgumentException
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 */
	private function getUserPresetConfig()
	{
		$option = Option::get("mobile", "tabs_{$this->context->userId}", false, $this->context->siteId);
		$result = false;
		if ($option)
		{
			$result = Json::decode($option);
		}

		return $result;
	}

	/**
	 * Return list of required tabs, which must be always active
	 * @return mixed
	 */
	public function getRequiredTabs()
	{
		return array_reduce(array_keys($this->tabList),
			function ($result, $tabId) {
				/**
				 * @type Tabable $tab
				 */
				$tab = $this->tabList[$tabId];
				if ($tab->canBeRemoved() === false && $tab->isAvailable())
				{
					$result[$tabId] = $tab->defaultSortValue();
				}

				return $result;
			}, []);
	}

	/**
	 * Return available state by tab identifier
	 * @param null $tabId
	 * @return bool
	 */
	public function getTabAvailabilityState($tabId = null)
	{
		return $this->tabList[$tabId] && $this->tabList[$tabId]->isAvailable($this->context);
	}

	/**
	 * Return list of available presets
	 * @return array
	 */
	public function getPresetList()
	{
		$result = [];
		$presets = $this->config["presets"];
		$optionalTabs = $this->config["presetOptionalTabs"] ?? [];
		foreach ($presets as $presetId => $tabs) {
			$tabsIDs = array_keys($tabs);
			$available = true;
			$unavailableOptionalTabs = [];
			if (count($tabsIDs) === 0)
			{
				$available = false;
			}
			else
			{
				foreach ($tabsIDs as $tabId)
				{
					if ($this->getTabInstance($tabId) == null)
					{
						break;
					}

					if (!$this->getTabInstance($tabId)->isAvailable())
					{
						if (isset($optionalTabs[$presetId]) && is_array($optionalTabs[$presetId])) {
							if (in_array($tabId, $optionalTabs[$presetId])) {
								$unavailableOptionalTabs[] = $tabId;
								continue;
							}
						}

						$available = false;
						break;
					}
				}
			}

			if ($available)
			{
				$tabs = array_filter($tabs, function( $sort, $id) use ($unavailableOptionalTabs) {
					return !in_array($id, $unavailableOptionalTabs);
				}, ARRAY_FILTER_USE_BOTH);

				$result[$presetId] = [
					"tabs" => $tabs,
					"title" => Loc::getMessage(mb_strtoupper("TAB_PRESET_NAME_$presetId"))
				];
			}

		}

		return $result;
	}

	private function setDefaultUserPreset()
	{
		$defaultTabs = $this->config["defaultUserPreset"];
		$this->setCustomConfig($defaultTabs);
		return $defaultTabs;
	}
}