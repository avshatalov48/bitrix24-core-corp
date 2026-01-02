<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Mobile\Menu\Analytics;
use Bitrix\Mobile\Tab\Manager;
use Bitrix\MobileApp\Janative\Entity\Component;
use Bitrix\MobileApp\Janative\Entity\Extension;
use Bitrix\Mobile\Context;

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

	public static function getJNDevWorkspace()
	{
		return "/bitrix/mobileapp/dev/mobileapp";
	}

	public static function getJNWorkspace()
	{
		return "/bitrix/mobileapp/mobile/";
	}

	public static function getKernelCheckPath()
	{
		return [
			"install/mobileapp/mobile/components/bitrix" => "/bitrix/mobileapp/mobile/components/bitrix/",
			"install/mobileapp/mobile/extensions/bitrix" => "/bitrix/mobileapp/mobile/extensions/bitrix/",
		];
	}

	/**
	 * @param Component $component
	 * @return string|null
	 */
	public static function onBeforeComponentContentGet(Component $component): ?string
	{
		$content = "";
		if (defined('JN_HOTRELOAD_ENABLED') && defined('JN_HOTRELOAD_HOST'))
		{
			$hotreloadHost = JN_HOTRELOAD_HOST;
			$content = (Extension::getInstance("hotreload"))->getContent();
			$content .= "\n(()=>{ let wsclient = startHotReload(this.env.userId, '$hotreloadHost') })();\n";
		}

		$apptheme = (new Extension("apptheme"))->getContent();
		$apptheme .= "\nvar AppTheme = jn.require('apptheme')\n";
		$content .= $apptheme;

		return $content;
	}

	public static function onMobileMenuBuilt($data, $eventProvider = null)
	{
		/**
		 * Tabs are not supported with web-version of menu
		 */
		if (!($eventProvider instanceof Component) && !($eventProvider instanceof Context))
		{
			return $data;
		}

		if ($eventProvider instanceof Context)
		{
			$data = self::addDevelopmentItems($data);

			if (
				Loader::includeModule('voximplant')
				&& \Bitrix\Voximplant\Security\Helper::canCurrentUserPerformCalls()
			)
			{
				$sectionIndex = self::getSectionIndexByCode($data, 'crm');
				if ($sectionIndex !== null)
				{
					$data[$sectionIndex]['items'] = array_merge(
						$menu[$sectionIndex]['items'] ?? [],
							[
								[
									'id' => 'voximplant',
									'sort' => 200,
									'title' => Loc::getMessage('MENU_CRM_SECTION_CALL'),
									'imageName' => 'phone_up',
									'counter' => 'voximplant',
									'params' => [
										'onclick' => <<<JS
										BX.postComponentEvent("onNumpadRequestShow");
									JS,
										'analytics' => Analytics::telephony(),
									],
								],
							],
					);
				}
			}

			if (
				Loader::includeModule('landing')
				&& Loader::includeModule('intranet')
				&& !$eventProvider->extranet
				&& !$eventProvider->isCollaber
			)
			{
				$isKnowledgeAvailable = \Bitrix\Intranet\Settings\Tools\ToolsManager::getInstance()->checkAvailabilityByToolId('knowledge_base');
				if ($isKnowledgeAvailable && \Bitrix\Landing\Site\Type::isEnabled('knowledge'))
				{
					$sectionIndex = self::getSectionIndexByCode($data, 'teamwork');
					if ($sectionIndex !== null)
					{
						$componentId = 'knowledge.list';
						$componentVersion = \Bitrix\MobileApp\Janative\Manager::getComponentVersion(
							$componentId
						);
						$data[$sectionIndex]['items'] = array_merge(
							$data[$sectionIndex]['items'] ?? [],
							[
								[
									'id' => 'knowledge',
									'sectionCode' => 'teamwork',
									'sort' => 400,
									'title' => Loc::getMessage('MENU_TEAMWORK_KNOWLEDGE'),
									'imageUrl' => '/bitrix/images/landing/mobile/knowledge.png?4',
									'imageName' => 'knowledge_base',
									'color' => '#e597ba',
									'params' => [
										'onclick' => <<<JS
										ComponentHelper.openList({
											name: '{$componentId}',
											object: 'list',
											version: '{$componentVersion}',
											widgetParams: {titleParams: { text: this.title, type: 'section' } , useSearch:true}
										});
									JS,
										'analytics' => Analytics::knowledge(),
									],
								],
							],
						);
					}
				}
			}
		}

		$imageDir = '';
		if ($eventProvider instanceof Component)
		{
			$imageDir = $eventProvider->getPath() . "/images/";
		}

		$manager = new Manager();
		$active = array_keys($manager->getActiveTabs());
		$all = $manager->getAllTabIDs(true);
		$diff = array_diff($all, $active);
		$favorite = &$data[0]["items"];
		foreach ($diff as $tabId)
		{
			$tab = $manager->getTabInstance($tabId);

			if ($tab && $tab->isAvailable() && $tab->shouldShowInMenu())
			{
				$item = $tab->getMenuData();
				if ($item["imageUrl"])
				{
					$item["imageUrl"] = $imageDir . $item["imageUrl"];
				}
				$sectionCodeKey = $eventProvider instanceof Component ? 'sectionCode' : 'section_code';
				if (isset($item[$sectionCodeKey]))
				{
					$count = count($data);
					for ($i = 0; $i < $count; $i++)
					{
						$section = &$data[$i];

						if (isset($section["code"]) && $section['code'] === $item[$sectionCodeKey])
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
			else
			{
				foreach ($data as &$dataTab)
				{
					if (isset($dataTab['code']) && $tabId === $dataTab['code'])
					{
						$dataTab['hidden'] = true;
						break;
					}
				}
			}
		}

		return $data;
	}

	private static function addDevelopmentItems(array $menu): array
	{
		if (\Bitrix\Main\Config\Option::get('mobile', 'developers_menu_section', 'N') !== 'Y')
		{
			return $menu;
		}

		$sectionId = CMobileEvent::getSectionIndexByCode($menu, 'development');
		if ($sectionId === null)
		{
			return $menu;
		}


		$developerMenuItems = [];
		$isEnableStoryBook = false;

		foreach (\Bitrix\Main\EventManager::getInstance()->findEventHandlers("mobileapp", "onJNComponentWorkspaceGet", ['mobile']) as $event)
		{
			if ($event['TO_METHOD'] === 'getJNDevWorkspace')
			{
				$isEnableStoryBook = true;
			}
		}

		if ($isEnableStoryBook)
		{
			$developerMenuItems[] = [
				'id' => 'storybook',
				'title' => 'StoryBook',
				'imageName' => 'form',
				'path' => '/development/storybook',
			];
		}

		$developerMenuItems[] = [
			'id' => 'unit.tests',
			'title' => "Frontend Unit Tests",
			'highlighted' => true,
			'imageName' => 'form',
			'path' => '/development/unit.tests',
		];

		$developerMenuItems[] = [
			'id' => 'playground',
			'title' => 'Developer playground',
			'imageName' => 'form',
			'path' => '/development/playground',
		];

		$developerMenuItems[] = [
			'id' => 'fields.component',
			'title' => "Fields Test",
			'imageName' => 'form',
			'path' => '/development/fields.test',
		];

		$developerMenuItems[] = [
			'id' => 'listview.benchmark',
			'title' => 'ListView benchmark',
			'imageName' => 'form',
			'path' => '/development/listview.benchmark',
		];

		$developerMenuItems[] = [
			'id' => 'text-editor-demo',
			'title' => 'Rich-text editor sandbox',
			'imageName' => 'form',
			'path' => '/development/text-editor.demo',
		];

		$developerMenuItems[] = [
			'id' => 'formatter-sandbox',
			'title' => 'Formatter sandbox',
			'imageName' => 'form',
			'path' => '/development/formatter.sandbox',
		];

		$menu[$sectionId]['items'] = array_merge(
			$menu[$sectionId]['items'] ?? [],
				$developerMenuItems,
		);

		return $menu;
	}

	private static function getSectionIndexByCode(array $menu, string $code): ?int
	{
		foreach ($menu as $index => $section)
		{
			if (!is_array($section))
			{
				continue;
			}

			if (isset($section['code']) && $section['code'] === $code)
			{
				return $index;
			}
		}

		return null;
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
		"/_analytics/",
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

		if (ModuleManager::isModuleInstalled('mail'))
		{
			$this->validUrls = array_merge(
				$this->validUrls,
				[
					'/bitrix/tools/mobile_oauth.php',
					'/bitrix/tools/mail_oauth.php',
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
