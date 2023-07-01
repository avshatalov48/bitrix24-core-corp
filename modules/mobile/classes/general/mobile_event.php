<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Mobile\Tab\Manager;
use Bitrix\MobileApp\Janative\Entity\Component;

IncludeModuleLangFile(__FILE__);

class CMobileEvent
{
	public static function PullOnGetDependentModule()
	{
		return [
			'MODULE_ID' => "mobile",
			'USE' => ["PUBLIC_SECTION"],
		];
	}

	/**
	 * @param $message
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function shouldSendNotification($message)
	{
		if (empty($message["USER_ID"]))
		{
			return false;
		}

		$energySave = Option::get("mobile", "push_save_energy_" . $message["USER_ID"], false);
		$isMessageEmpty = empty($message["MESSAGE"]) && empty($message["ADVANCED_PARAMS"]["senderMessage"]);

		if ($energySave == true && $isMessageEmpty)
		{
			$lastTimePushOption = "last_time_push_" . $message["USER_ID"];
			$lastEmptyMessageTime = Option::get("mobile", $lastTimePushOption, 0);
			$throttleTimeout = Option::get("mobile", "push_throttle_timeout", 20);
			$now = time();
			if (($now - $lastEmptyMessageTime) < $throttleTimeout)
			{
				return false;
			}
			else
			{
				Option::set("mobile", $lastTimePushOption, $now);
			}
		}

		return true;
	}

	public static function getJNWorkspace()
	{
		return "/bitrix/mobileapp/mobile/";
	}

	public static function getKernelCheckPath()
	{
		return [
			"install/mobileapp/mobile/components/bitrix" => "/bitrix/mobileapp/mobile/components/bitrix/",
			"install/mobileapp/mobile/extensions/bitrix" => "/bitrix/mobileapp/mobile/erxtensions/bitrix/",
		];
	}

	public static function onMobileMenuBuilt($data, $eventProvider = null)
	{
		/**
		 * Tabs are not supported with web-version of menu
		 */
		if (!($eventProvider instanceof Component))
		{
			return $data;
		}

		/**
		 * @var  $eventProvider Component
		 */
		$imageDir = $eventProvider->getPath() . "/images/";
		$manager = new Manager();
		$active = array_keys($manager->getActiveTabs());
		$all = $manager->getAllTabIDs(true);
		$diff = array_diff($all, $active);
		$favorite = &$data[0]["items"];
		foreach ($diff as $tabId)
		{
			$tab = $manager->getTabInstance($tabId);
			if ($tab->shouldShowInMenu())
			{
				$item = $tab->getMenuData();
				if ($item["imageUrl"])
				{
					$item["imageUrl"] = $imageDir . $item["imageUrl"];
				}
				if (isset($item["sectionCode"]))
				{
					$count = count($data);
					for ($i = 0; $i < $count; $i++)
					{
						$section = &$data[$i];
						if (isset($section["code"]) && $section['code'] === $item['sectionCode'])
						{
							if (!isset($section["items"]))
							{
								$section["items"] = [];
							}

							array_unshift($section["items"], $item);
							break;
						}
					}
				}
				else
				{
					array_unshift($favorite, $item);
				}

			}
		}

		return $data;
	}
}

class MobileApplication extends Bitrix\Main\Authentication\Application
{
	protected $validUrls = [
		"/mobile/",
		"/bitrix/tools/check_appcache.php",
		"/bitrix/tools/disk/uf.php",
		"/bitrix/tools/rest_control.php",
		"/bitrix/services/disk/index.php",
		"/bitrix/groupdav.php",
		"/bitrix/tools/composite_data.php",
		"/bitrix/tools/crm_show_file.php",
		"/bitrix/tools/dav_profile.php",
		"/bitrix/tools/crm_lead_mode.php",
		"/bitrix/components/bitrix/crm.lead.list/list.ajax.php",
		"/bitrix/components/bitrix/disk.folder.list/ajax.php",
		"/bitrix/services/mobile/jscomponent.php",
		"/bitrix/services/mobile/webcomponent.php",
		"/bitrix/services/rest/index.php",
		"/bitrix/services/main/ajax.php",
		"/bitrix/services/mobileapp/jn.php",
		"/bitrix/components/bitrix/main.urlpreview/",
		"/bitrix/components/bitrix/main.file.input/",
		"/mobileapp/",
		"/rest/",
	];

	public function __construct()
	{
		$diskEnabled = \Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false)
			&& CModule::includeModule('disk');

		if (!$diskEnabled)
		{
			$this->validUrls = array_merge(
				$this->validUrls,
				[
					"/company/personal.php",
					"/docs/index.php",
					"/docs/shared/index.php",
					"/workgroups/index.php",
				]);
		}

		if (ModuleManager::isModuleInstalled('extranet'))
		{
			$extranetSiteId = \Bitrix\Main\Config\Option::get('extranet', 'extranet_site', false);
			if ($extranetSiteId)
			{
				$res = \Bitrix\Main\SiteTable::getList([
					'filter' => ['=LID' => $extranetSiteId],
					'select' => ['DIR'],
				]);
				if ($site = $res->fetch())
				{
					$this->validUrls = array_merge(
						$this->validUrls,
						[
							$site['DIR'] . "mobile/",
							$site['DIR'] . "contacts/personal.php",
						]);
				}
			}
		}

		// We should add cloud bucket prefixes
		// to allow URLs that cloud services redirected to
		if (Loader::includeModule('clouds'))
		{
			$buckets = CCloudStorageBucket::getAllBuckets();
			foreach ($buckets as $bucket)
			{
				if ($bucket["PREFIX"])
				{
					$this->validUrls[] = "/" . $bucket["PREFIX"] . "/";
				}
			}
		}

		/*
		 * @todo need only one endpoint for files in a crm entities
		 * It's temporary fix of ticket #136389
		 */
		if (ModuleManager::isModuleInstalled('crm'))
		{
			$this->validUrls = array_merge(
				$this->validUrls,
				[
					'/bitrix/components/bitrix/crm.company.show/show_file.php',
					'/bitrix/components/bitrix/crm.contact.show/show_file.php',
					'/bitrix/components/bitrix/crm.deal.show/show_file.php',
					'/bitrix/components/bitrix/crm.lead.show/show_file.php',
					'/bitrix/components/bitrix/crm.invoice.show/show_file.php',
					'/bitrix/components/bitrix/crm.quote.show/show_file.php',
				]);
		}
	}

	public static function OnApplicationsBuildList()
	{
		return [
			"ID" => "mobile",
			"NAME" => GetMessage("MOBILE_APPLICATION_NAME"),
			"DESCRIPTION" => GetMessage("MOBILE_APPLICATION_DESC"),
			"SORT" => 90,
			"CLASS" => "MobileApplication",
		];
	}
}
