<?php

namespace Bitrix\Intranet\Controller;

use Bitrix\Intranet\Settings\Tools\Crm;
use Bitrix\Intranet\Settings\Tools\Sites;
use Bitrix\Intranet\Settings\Tools\Tasks;
use Bitrix\Intranet\Settings\Tools\TeamWork;
use Bitrix\Intranet\Portal\FirstPage;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Error;
use Bitrix\Main\EventManager;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Type\Collection;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialnetwork\WorkgroupFavoritesTable;
use Bitrix\Socialnetwork\WorkgroupSiteTable;
use Bitrix\Socialnetwork\WorkgroupTable;

class LeftMenu extends \Bitrix\Main\Engine\Controller
{
	protected function getDefaultPreFilters()
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
			new ActionFilter\Csrf(),
			new ActionFilter\CloseSession()
		];
	}

	public function configureActions(): array
	{
		return [
			'setPreset' => [
				'-prefilters' => [ActionFilter\CloseSession::class],
			],
		];
	}

	protected function processBeforeAction(\Bitrix\Main\Engine\Action $action)
	{
		parent::processBeforeAction($action);

		if (Loader::includeModule('intranet'))
		{
			\Bitrix\Intranet\Composite\CacheProvider::deleteUserCache();
		}

		FirstPage::getInstance()->clearCache();

		return true;
	}

	protected function isCurrentUserAdmin():bool
	{
		global $USER;

		return (
			$USER->isAdmin()
			|| (
				Loader::includeModule("bitrix24")
				&& \CBitrix24::IsPortalAdmin($USER->getID())
			)
		);
	}

	protected function getItemsSortOptionName():string
	{
		return 'left_menu_sorted_items_' . SITE_ID;
	}

	protected function setGroupToFavorites($groupId, $value = 'Y')
	{
		if (intval($groupId) && $GLOBALS['USER']->getId() && Loader::includeModule('socialnetwork'))
		{
			try
			{
				\Bitrix\Socialnetwork\Item\WorkgroupFavorites::set(array(
					'GROUP_ID' => intval($groupId),
					'USER_ID' => $GLOBALS['USER']->getId(),
					'VALUE' => $value === 'Y' ? 'Y' : 'N'
				));
			}
			catch (\Exception $e) {}
		}
	}

	public function addSelfItemAction()
	{
		$itemLink = $itemText = '';

		if (!isset($_POST['itemData']))
			$error = Loc::getMessage('INTRANET_LEFT_MENU_SELF_ITEM_UNKNOWN_ERROR');

		if (isset($_POST['itemData']['text']))
		{
			$itemText = trim($_POST['itemData']['text']);
			$itemText = \Bitrix\Main\Text\Emoji::encode($itemText);
		}
		if (empty($itemText))
		{
			$error = Loc::getMessage('INTRANET_LEFT_MENU_SELF_ITEM_EMPTY_ERROR');
		}

		if (isset($_POST['itemData']['link']))
		{
			$itemLink = trim($_POST['itemData']['link']);
			if (!preg_match('~^[/|http]~i', $itemLink))
				$error = Loc::getMessage('INTRANET_LEFT_MENU_SELF_ITEM_LINK_ERROR');
		}

		if (!empty($error))
		{
			$this->addError(new Error($error));
			return null;
		}

		$itemID = crc32($itemLink);
		$newItem = array(
			'TEXT' => $itemText,
			'LINK' => $itemLink,
			'ID' => $itemID,
			'NEW_PAGE' => (
				isset($_POST['itemData']['openInNewPage']) && $_POST['itemData']['openInNewPage'] == 'Y'
					? 'Y'
					: 'N'
			)
		);
		$selfItems = \CUserOptions::GetOption('intranet', 'left_menu_self_items_' . SITE_ID);

		if (is_array($selfItems) && !empty($selfItems))
		{
			foreach ($selfItems as $item)
			{
				if ($item["LINK"] == $newItem["LINK"])
				{
					$this->addError(new Error(Loc::getMessage("INTRANET_LEFT_MENU_SELF_ITEM_DUBLICATE_ERROR")));
					return null;
				}
			}
			$selfItems[] = $newItem;
		}
		else
		{
			$selfItems = array($newItem);
		}
		\CUserOptions::SetOption("intranet", "left_menu_self_items_" . SITE_ID, $selfItems);

		return [
			"itemId" => crc32($itemLink),
		];

	}

	public function updateSelfItemAction()
	{
		if (!isset($_POST["itemData"]))
			$error = Loc::getMessage("INTRANET_LEFT_MENU_SELF_ITEM_UNKNOWN_ERROR");

		$itemData = array(
			"ID" => $_POST["itemData"]["id"],
			"NEW_PAGE" => isset($_POST["itemData"]["openInNewPage"]) && $_POST["itemData"]["openInNewPage"] == "Y" ? "Y" : "N"
		);

		if (isset($_POST["itemData"]["text"]))
		{
			$itemData["TEXT"] = trim($_POST["itemData"]["text"]);
			$itemData["TEXT"] = \Bitrix\Main\Text\Emoji::encode($itemData["TEXT"]);
		}
		if (empty($itemData["TEXT"]))
		{
			$error = Loc::getMessage("INTRANET_LEFT_MENU_SELF_ITEM_EMPTY_ERROR");
		}

		if (isset($_POST["itemData"]["link"]))
		{
			$itemData["LINK"] = trim($_POST["itemData"]["link"]);
			if (!preg_match("~^[/|http]~i", $itemData["LINK"]))
				$error = Loc::getMessage("INTRANET_LEFT_MENU_SELF_ITEM_LINK_ERROR");
		}

		if (!empty($error))
		{
			$this->addError(new Error($error));
			return null;
		}

		$selfItems = \CUserOptions::GetOption("intranet", "left_menu_self_items_" . SITE_ID);
		if (is_array($selfItems) && !empty($selfItems))
		{
			foreach ($selfItems as $key => $item)
			{
				if ($item["ID"] == $_POST["itemData"]["id"])
				{
					$selfItems[$key] = $itemData;

					\CUserOptions::SetOption("intranet", "left_menu_self_items_". SITE_ID, $selfItems);
					break;
				}
			}
		}
	}

	public function deleteSelfItemAction($menuItemId)
	{
		if (!$menuItemId)
		{
			return;
		}

		$selfItems = \CUserOptions::GetOption('intranet', 'left_menu_self_items_' . SITE_ID);
		if (is_array($selfItems))
		{
			foreach ($selfItems as $key => $item)
			{
				if ($item['ID'] == $menuItemId)
				{
					unset($selfItems[$key]);
					break;
				}
			}

			if (!empty($selfItems))
			{
				\CUserOptions::SetOption('intranet', 'left_menu_self_items_' . SITE_ID, $selfItems);
			}
			else
			{
				\CUserOptions::DeleteOption("intranet", "left_menu_self_items_" . SITE_ID);
			}
		}
	}

	public function addStandartItemAction()
	{
		$itemLink = $itemText = '';

		if (isset($_POST['itemData']['text']))
		{
			$itemText = trim($_POST['itemData']['text']);
		}
		if (empty($itemText))
		{
			$error = Loc::getMessage('INTRANET_LEFT_MENU_SELF_ITEM_TEXT_ERROR');
		}

		if (isset($_POST['itemData']['link']))
		{
			$itemLink = trim($_POST['itemData']['link']);
			if (!preg_match('~^/~i', $itemLink))
				$error = Loc::getMessage('INTRANET_LEFT_MENU_SELF_ITEM_LINK_ERROR');
		}

		if (isset($_POST['itemData']['id']))
		{
			$itemId = trim($_POST['itemData']['id']);
		}
		else
		{
			$itemId = crc32($itemLink);
		}

		if (!empty($error))
		{
			$this->addError(new Error($error));
			return null;
		}

		$newItem = array(
			'TEXT' => $itemText,
			'LINK' => $itemLink,
			'ID' => $itemId
		);

		if (isset($_POST['itemData']['counterId']) && $_POST['itemData']['counterId'])
		{
			$newItem['COUNTER_ID'] = $_POST['itemData']['counterId'];
		}

		if (isset($_POST['itemData']['subLink']) && is_array($_POST['itemData']['subLink']))
		{
			$newItem['SUB_LINK'] = $_POST['itemData']['subLink']['URL'];
		}

		$standardItems = \CUserOptions::GetOption('intranet', 'left_menu_standard_items_' . SITE_ID);

		if (is_array($standardItems) && !empty($standardItems))
		{
			foreach ($standardItems as $item)
			{
				if ($item['LINK'] == $newItem['LINK'])
				{
					$this->addError(new Error(Loc::getMessage('INTRANET_LEFT_MENU_SELF_ITEM_DUBLICATE_ERROR')));
					return null;
				}
			}
			$standardItems[$itemId] = $newItem;
		}
		else
		{
			$standardItems = array($itemId => $newItem);
		}

		\CUserOptions::SetOption('intranet', 'left_menu_standard_items_' . SITE_ID, $standardItems);

		if (preg_match('~^/workgroups/group/([0-9]+)/$~i', $itemLink, $match))
		{
			$this->setGroupToFavorites($match[1], 'Y');
		}

		return [
			'itemId' => $itemId,
		];
	}

	public function updateStandartItemAction()
	{
		if (isset($_POST['itemId']))
		{
			$itemId = $_POST['itemId'];
		}
		else
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_LEFT_MENU_SELF_ITEM_UNKNOWN_ERROR')));
			return null;
		}

		$itemText = '';
		if (isset($_POST['itemText']))
		{
			$itemText = trim($_POST['itemText']);
		}
		if (empty($itemText))
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_LEFT_MENU_SELF_ITEM_TEXT_ERROR')));
			return null;
		}

		$standardItems = \CUserOptions::GetOption('intranet', 'left_menu_standard_items_' . SITE_ID);
		if (is_array($standardItems))
		{
			foreach($standardItems as $key => $item)
			{
				if ($item['ID'] == $itemId)
				{
					$standardItems[$key]['TEXT'] = $itemText;
					break;
				}
			}

			if (!empty($standardItems))
			{
				\CUserOptions::SetOption('intranet', 'left_menu_standard_items_' . SITE_ID, $standardItems);
			}
			else
			{
				\CUserOptions::DeleteOption('intranet', 'left_menu_standard_items_' . SITE_ID);
			}
		}
	}

	public function deleteStandartItemAction()
	{
		$standardItems = \CUserOptions::GetOption('intranet', 'left_menu_standard_items_' . SITE_ID);

		if (is_array($standardItems))
		{
			$itemId = '';
			if (isset($_POST['itemData']['link']))
			{
				$itemId = crc32($_POST['itemData']['link']);
			}
			else if (isset($_POST['itemData']['id']))
			{
				$itemId = $_POST['itemData']['id'];
			}

			if (!$itemId)
			{
				$this->addError(new Error(Loc::getMessage('INTRANET_LEFT_MENU_SELF_ITEM_UNKNOWN_ERROR')));
				return null;
			}

			$itemLink = '';
			foreach($standardItems as $key => $item)
			{
				if ($item['ID'] == $itemId)
				{
					$itemLink = $item['LINK'];
					unset($standardItems[$key]);
					break;
				}
			}

			if (preg_match('~^/workgroups/group/([0-9]+)/$~i', $itemLink, $match))
			{
				$this->setGroupToFavorites($match[1], 'N');
			}

			if (!empty($standardItems))
			{
				\CUserOptions::SetOption('intranet', 'left_menu_standard_items_' . SITE_ID, $standardItems);
			}
			else
			{
				\CUserOptions::DeleteOption('intranet', 'left_menu_standard_items_' . SITE_ID);
			}

			return [
				'itemId' => $itemId,
			];
		}
	}

	public function addItemToAllAction()
	{
		if (!$this->isCurrentUserAdmin())
		{
			return null;
		}

		if (isset($_POST['itemInfo']) && is_array($_POST['itemInfo']))
		{
			$itemText = trim($_POST['itemInfo']['text']);
			$itemText = \Bitrix\Main\Text\Emoji::encode($itemText);

			$itemData = array(
				'TEXT' => $itemText,
				'LINK' => $_POST['itemInfo']['link'],
				'ID' => $_POST['itemInfo']['id'],
			);
			if (isset($_POST['itemInfo']['openInNewPage']) && $_POST['itemInfo']['openInNewPage'] == 'Y')
			{
				$itemData['NEW_PAGE'] = 'Y';
			}

			if (!empty($_POST['itemInfo']['counterId']))
				$itemData['COUNTER_ID'] = $_POST['itemInfo']['counterId'];

			$adminOption = Option::get('intranet', 'left_menu_items_to_all_' . SITE_ID, '', SITE_ID);

			if (!empty($adminOption))
			{
				$adminOption = unserialize($adminOption, ['allowed_classes' => false]);
				foreach ($adminOption as $item)
				{
					if ($item['ID'] == $itemData['ID'])
						break;
				}
				$adminOption[] = $itemData;
			}
			else
			{
				$adminOption = array($itemData);
			}

			Option::set('intranet', 'left_menu_items_to_all_' . SITE_ID, serialize($adminOption), false, SITE_ID);
		}
	}

	public function deleteItemFromAllAction()
	{
		if (!$this->isCurrentUserAdmin())
		{
			return null;
		}

		if (!isset($_POST['menu_item_id']))
		{
			return null;
		}

		foreach ([
			'left_menu_items_to_all_' . SITE_ID,
			'left_menu_items_marketplace_' . SITE_ID
			] as $optionName)
		{
			if (($adminOption = Option::get('intranet', $optionName, '', SITE_ID))
				&& !empty($adminOption)
				&& ($adminOption = unserialize($adminOption, ['allowed_classes' => false]))
			)
			{
				foreach ($adminOption as $key => $item)
				{
					if ($item['ID'] == $_POST['menu_item_id'])
					{
						unset($adminOption[$key]);
						if (empty($adminOption))
						{
							\COption::RemoveOption('intranet', $optionName);
						}
						else
						{
							Option::set('intranet', $optionName, serialize($adminOption), SITE_ID);
						}
						break 2;
					}
				}
			}
		}
	}

	public function deleteCustomItemFromAllAction()
	{
		if (!$this->isCurrentUserAdmin())
		{
			return null;
		}

		if (!isset($_POST['menu_item_id']))
		{
			return null;
		}

		$customItems = Option::get('intranet', 'left_menu_custom_preset_items', '', SITE_ID);

		if (!empty($customItems))
		{
			$customItems = unserialize($customItems, ['allowed_classes' => false]);
			foreach ($customItems as $key => $item)
			{
				if ($item['ID'] == $_POST['menu_item_id'])
				{
					unset($customItems[$key]);
					if (empty($customItems))
					{
						\COption::RemoveOption('intranet', 'left_menu_custom_preset_items', SITE_ID);
					}
					else
					{
						Option::set('intranet', 'left_menu_custom_preset_items', serialize($customItems), false, SITE_ID);
					}

					break;
				}
			}
		}

		$customItemsSort = Option::get('intranet', 'left_menu_custom_preset_sort', '', SITE_ID);
		if (!empty($customItemsSort))
		{
			$customItemsSort = unserialize($customItemsSort, ['allowed_classes' => false]);
			foreach (array('show', 'hide') as $status)
			{
				foreach ($customItemsSort[$status] as $key=>$itemId)
				{
					if ($itemId == $_POST['menu_item_id'])
					{
						unset($customItemsSort[$status][$key]);
					}
				}
			}

			Option::set('intranet', 'left_menu_custom_preset_sort', serialize($customItemsSort), false, SITE_ID);
		}
	}

	public function saveItemsSortAction()
	{
		if (!isset($_POST['items']))
		{
			return null;
		}

		\CUserOptions::SetOption(
			'intranet',
			$this->getItemsSortOptionName(),
			self::convertItemsSortFromJSToDB($_POST['items'], $_POST['version'])
		);

		if (isset($_POST['firstItemLink']))
		{
			\CUserOptions::SetOption('intranet', 'left_menu_first_page_' . SITE_ID, $_POST['firstItemLink']);
		}
	}

	private function enablePresetTool($preset): void
	{
		switch ($preset)
		{
			case 'tasks':
				$taskMenu = new Tasks();

				if (!$taskMenu->isEnabled())
				{
					$taskMenu->enableAllSubgroups();
					$taskMenu->enable();
				}

				break;

			case 'crm':
				$crmMenu = new Crm();

				if (!$crmMenu->isEnabled())
				{
					$crmMenu->enableAllSubgroups();
					$crmMenu->enable();
				}

				break;

			case 'sites':
				$sitesMenu = new Sites();

				if (!$sitesMenu->isEnabled())
				{
					$sitesMenu->enable();
				}

				break;

			case 'social':
				$socialMenu = new TeamWork();

				if (!$socialMenu->isEnabled())
				{
					$socialMenu->enableAllSubgroups();
					$socialMenu->enable();
				}

				break;
		}
	}

	public function setPresetAction()
	{
		global $USER;

		if (
			!isset($_POST['preset'])
			|| !in_array($_POST['preset'], array('social', 'crm', 'tasks', 'sites'))
			|| !isset($_POST['mode'])
		)
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_LEFT_MENU_PRESET_ERROR')));
			return null;
		}

		$res = [];

		if ($_POST['mode'] == 'global' && $this->isCurrentUserAdmin())
		{
			Option::set('intranet', 'left_menu_preset', $_POST['preset'], false, SITE_ID);

			$event = new Event(
				'intranet',
				'onAfterChangeLeftMenuPreset',
				[
					'SITE_ID' => SITE_ID,
					'VALUE' => $_POST['preset'],
				]
			);
			EventManager::getInstance()->send($event);

			$this->enablePresetTool($_POST['preset']);
		}
		else
		{
			\CUserOptions::SetOption('intranet', 'left_menu_preset_' . SITE_ID, $_POST['preset']);

			\CUserOptions::DeleteOption('intranet', 'left_menu_first_page_' . SITE_ID);
			//	CUserOptions::DeleteOption('intranet', 'left_menu_self_items_'.$siteID);
			//	CUserOptions::DeleteOption('intranet', 'left_menu_standard_items_'.$siteID);
			\CUserOptions::DeleteOption('intranet', $this->getItemsSortOptionName());
		}

		if ($this->isCurrentUserAdmin())
		{
			$this->enablePresetTool($_POST['preset']);
		}

		$firstPageUrl = SITE_DIR . 'stream/';
		switch ($_POST['preset'])
		{
			case 'tasks':
				$firstPageUrl = SITE_DIR . 'company/personal/user/'
					. ($_POST['mode'] == 'global' ? '#USER_ID#' : $USER->GetID()) . '/tasks/'
				;
				break;

			case 'crm':
				if (Loader::includeModule('crm'))
				{
					$firstPageUrl = \Bitrix\Crm\Settings\EntityViewSettings::getDefaultPageUrl();
				}
				break;

			case 'sites':
				$firstPageUrl = SITE_DIR . 'sites/';

				break;

			case 'social':
				$firstPageUrl = (new TeamWork())->getLeftMenuPath();

				break;
		}

		if ($firstPageUrl)
		{
			if ($_POST['mode'] == 'global' && $this->isCurrentUserAdmin())
			{
				Option::set('intranet', 'left_menu_first_page', $firstPageUrl, false, SITE_ID);
			}
			else
			{
				\CUserOptions::SetOption('intranet', 'left_menu_first_page_' . SITE_ID, $firstPageUrl);
			}

			$res['url'] = str_replace('#USER_ID#', $USER->GetID(), $firstPageUrl);
		}

		if($_POST['mode'] === 'global' && ModuleManager::isModuleInstalled('bitrix24'))
		{
			$_SESSION['B24_SHOW_DEMO_LICENSE_HINT'] = 1;
		}

		$showPresetPopup = Option::get('intranet', 'show_menu_preset_popup', 'N') == 'Y';
		if ($showPresetPopup)
		{
			Option::set('intranet', 'show_menu_preset_popup', 'N');
		}

		return $res;
	}

	public function delaySetPresetAction()
	{
		$showPresetPopup = Option::get('intranet', 'show_menu_preset_popup', 'N') == 'Y';
		if ($showPresetPopup)
		{
			Option::set('intranet', 'show_menu_preset_popup', 'N');
		}
	}

	private static function convertItemsSortFromJSToDB($itemsFromPost, $version = null): array
	{
		$userOption = ['show' => [], 'hide' => []];
		if ($version === null)
		{
			foreach ($userOption as $key => $val)
			{
				if (isset($itemsFromPost[$key]) && is_array($itemsFromPost[$key]))
				{
					$userOption[$key] = $itemsFromPost[$key];
				}
			}
		}
		else
		{
			$convert = function($res, &$itemsPointer) use (&$convert) {
				foreach ($res as $item)
				{
					if (is_string($item))
					{
						$itemsPointer[] = $item;
					}
					else if (is_array($item) && isset($item['group_id']))
					{
						if (!empty($item['items']))
						{
							$itemsPointer[$item['group_id']] = [];
							$convert($item['items'], $itemsPointer[$item['group_id']]);
						}
						else
						{
							$itemsPointer[] = $item;
						}
					}
				}
			};
			$convert($itemsFromPost['show'] ?? [], $userOption['show']);
			$convert($itemsFromPost['hide'] ?? [], $userOption['hide']);
			unset($convert);
			$userOption['version'] = $version;
		}
		return $userOption;
	}

	public function saveCustomPresetAction()
	{
		if (!$this->isCurrentUserAdmin())
		{
			return null;
		}

		if (isset($_POST['userApply']) && $_POST['userApply'] === 'currentUser')
		{
			\CUserOptions::DeleteOptionsByName('intranet', 'left_menu_sorted_items_' . SITE_ID);
			\CUserOptions::DeleteOptionsByName('intranet', 'left_menu_preset_' . SITE_ID);
			\CUserOptions::DeleteOptionsByName('intranet', 'left_menu_first_page_' . SITE_ID);
			FirstPage::getInstance()->clearCacheForAll();
		}

		if (isset($_POST['itemsSort']))
		{
			Option::set(
				'intranet',
				'left_menu_custom_preset_sort',
				serialize(self::convertItemsSortFromJSToDB($_POST['itemsSort'], $_POST['version'])),
				SITE_ID
			);
		}

		if (isset($_POST['customItems']))
		{
			Option::set('intranet', 'left_menu_custom_preset_items', serialize($_POST['customItems']), false, SITE_ID);
		}

		Option::set('intranet', 'left_menu_preset', 'custom', false, SITE_ID);
		if (isset($_POST['firstItemLink']))
		{
			$firstPageUrl = $_POST['firstItemLink'];
			if (preg_match('~company/personal/user/\d+/tasks/$~i', $firstPageUrl, $match))
			{
				$firstPageUrl = $_POST["siteDir"] . 'company/personal/user/#USER_ID#/tasks/';
			}

			Option::set('intranet', 'left_menu_first_page', $firstPageUrl, false, SITE_ID);
		}
	}

	public function setFirstPageAction()
	{
		if (!isset($_POST['firstPageUrl']))
		{
			return null;
		}

		\CUserOptions::SetOption('intranet', 'left_menu_first_page_' . SITE_ID, $_POST['firstPageUrl']);
	}

	public function clearCacheAction()
	{
		//This action only for a composite cache
	}

	public function setDefaultMenuAction()
	{
		\CUserOptions::DeleteOption('intranet', 'left_menu_first_page_' . SITE_ID);
		\CUserOptions::DeleteOption('intranet', 'left_menu_self_items_' . SITE_ID);
		\CUserOptions::DeleteOption('intranet', 'left_menu_standard_items_' . SITE_ID);
		\CUserOptions::DeleteOption('intranet', $this->getItemsSortOptionName());
		\CUserOptions::DeleteOption('intranet', 'left_menu_groups_' . SITE_ID);

		if (Option::get('intranet', 'left_menu_preset', '', SITE_ID) === 'custom')
		{
			\CUserOptions::DeleteOptionsByName('intranet', 'left_menu_preset_' . SITE_ID);
		}
	}

	public function collapseMenuGroupAction($id)
	{
		$groups = \CUserOptions::GetOption('intranet', 'left_menu_groups_' . SITE_ID);
		$groups = is_array($groups) ? $groups : [];
		$groups[$id] = 'collapsed';
		\CUserOptions::SetOption('intranet', 'left_menu_groups_' . SITE_ID, $groups);
	}

	public function expandMenuGroupAction($id)
	{
		$groups = \CUserOptions::GetOption('intranet', 'left_menu_groups_' . SITE_ID);
		$groups = is_array($groups) ? $groups : [];
		$groups[$id] = 'expanded';
		\CUserOptions::SetOption('intranet', 'left_menu_groups_' . SITE_ID, $groups);
	}

	public function collapseMenuAction(string $context = '')
	{
		if ($context === 'online')
		{
			\CUserOptions::SetOption('intranet', 'left_menu_collapsed:online', 'Y');
		}
		else
		{
			\CUserOptions::SetOption('intranet', 'left_menu_collapsed', 'Y');
		}
	}

	public function expandMenuAction(string $context = '')
	{
		if ($context === 'online')
		{
			\CUserOptions::SetOption('intranet', 'left_menu_collapsed:online', 'N');
		}
		else
		{
			\CUserOptions::SetOption('intranet', 'left_menu_collapsed', 'N');
		}
	}

	public function setGroupFilterAction()
	{
		if (isset($_POST['filter']) && in_array($_POST['filter'], array('all', 'extranet', 'favorites')))
		{
			\CUserOptions::SetOption('intranet', 'left_menu_group_filter_' . SITE_ID, $_POST['filter']);
		}
	}

	public function addToFavoritesAction()
	{
		if (isset($_POST['groupId']) && intval($_POST['groupId']))
		{
			$this->setGroupToFavorites(
				intval($_POST['groupId']),
				'Y'
			);
		}
	}

	public function removeFromFavoritesAction()
	{
		if (isset($_POST['groupId']) && intval($_POST['groupId']))
		{
			$this->setGroupToFavorites(
				intval($_POST['groupId']),
				'N'
			);
		}
	}

	public function getGroupsAction(): array
	{
		$filter = \CUserOptions::getOption('intranet', 'left_menu_group_filter_' . SITE_ID, 'all');;

		return [
			'filter' => $filter,
			'groups' => static::getMyGroups(),
		];
	}

	public static function getMyGroups(): array
	{
		$userId = $GLOBALS['USER']->getId();
		$cacheTtl = defined('BX_COMP_MANAGED_CACHE') ? 2592000 : 600;
		$cacheId = 'bitrix24_group_list_v2' . $userId . '_' . SITE_ID . '_' . isModuleInstalled('extranet');
		$cacheDir = '/bx/bitrix24_group_list/' . substr(md5($userId), 0, 2) . '/' . $userId . '/';

		$cache = new \CPHPCache();

		if ($cache->initCache($cacheTtl, $cacheId, $cacheDir))
		{
			return $cache->getVars();
		}

		$cache->startDataCache();
		if (defined('BX_COMP_MANAGED_CACHE'))
		{
			$GLOBALS['CACHE_MANAGER']->startTagCache($cacheDir);
			$GLOBALS['CACHE_MANAGER']->registerTag('sonet_user2group_U' . $userId);
			$GLOBALS['CACHE_MANAGER']->registerTag('sonet_group');
			$GLOBALS['CACHE_MANAGER']->registerTag('sonet_group_favorites_U' . $userId);
			$GLOBALS['CACHE_MANAGER']->endTagCache();
		}

		$groups = [];
		if (!Loader::includeModule('socialnetwork') || $userId <= 0)
		{
			return $groups;
		}

		$extranetSiteId = \COption::getOptionString('extranet', 'extranet_site');
		$getGroups = function($siteId, $limit, $ids = []) use ($userId, $extranetSiteId) {
			$groups = WorkgroupTable::getList([
				'filter' => [
					'=ACTIVE' => 'Y',
					'!=CLOSED' => 'Y',
					'=GS.SITE_ID' => $siteId,
					'<=UG.ROLE' => UserToGroupTable::ROLE_USER,
					'!=TYPE' => \Bitrix\Socialnetwork\Item\Workgroup\Type::Collab->value,
				] + (empty($ids) ? [] : ['!@ID' => $ids]),
				'order' => [
					'NAME' => 'ASC',
				],
				'select' => ['ID', 'NAME'],
				'count_total' => false,
				'offset' => 0,
				'limit' => $limit,
				'runtime' => [
					new ReferenceField(
						'UG',
						UserToGroupTable::getEntity(),
						['=ref.GROUP_ID' => 'this.ID', '=ref.USER_ID' => new SqlExpression($userId)],
						['join_type' => 'INNER'],
					),
					new ReferenceField(
						'GS',
						WorkgroupSiteTable::getEntity(),
						['=ref.GROUP_ID' => 'this.ID'],
						['join_type' => 'INNER'],
					),
				],
			]);

			$result = [];
			while ($group = $groups->fetch())
			{
				$result[$group['ID']] = [
					'id' => $group['ID'],
					'title' => $group['NAME'],
					'url' => SITE_DIR . 'workgroups/group/' . $group["ID"] . '/',
					'extranet' => $siteId === $extranetSiteId,
					'favorite' => false,
				];
			}

			return $result;
		};

		$getFavorites = function($siteId, $limit, $ids = []) use ($userId, $extranetSiteId) {
			$groups = WorkgroupTable::getList([
				'filter' => [
					'=GF.USER_ID' => $userId,
					'=GS.SITE_ID' => $siteId,
					[
						'LOGIC' => 'OR',
						'=VISIBLE' => 'Y',
						'<=UG.ROLE' => UserToGroupTable::ROLE_USER,
					],
				] + (empty($ids) ? [] : ['!@ID' => $ids]),
				'order' => [
					'NAME' => 'ASC',
				],
				'select' => ['ID', 'NAME'],
				'count_total' => false,
				'offset' => 0,
				'limit' => $limit,
				'runtime' => [
					new ReferenceField(
						'UG',
						UserToGroupTable::getEntity(),
						['=ref.GROUP_ID' => 'this.ID', '=ref.USER_ID' => new SqlExpression($userId)],
						['join_type' => 'LEFT'],
					),
					new ReferenceField(
						'GS',
						WorkgroupSiteTable::getEntity(),
						['=ref.GROUP_ID' => 'this.ID'],
						['join_type' => 'INNER'],
					),
					new ReferenceField(
						'GF',
						WorkgroupFavoritesTable::getEntity(),
						['=ref.GROUP_ID' => 'this.ID'],
						['join_type' => 'INNER']
					),
				  ],
			  ]);

			$result = [];
			while ($group = $groups->fetch())
			{
				$result[$group['ID']] = [
					'id' => $group['ID'],
					'title' => $group['NAME'],
					'url' => SITE_DIR . 'workgroups/group/' . $group["ID"] . '/',
					'extranet' => $siteId === $extranetSiteId,
					'favorite' => true,
				];
			}

			return $result;
		};

		// Main\ModuleManager::isModuleInstalled($module_id);

		$extranetGroups = isModuleInstalled('extranet') ? $getGroups($extranetSiteId, 150) : [];
		$intranetGroups = $getGroups(SITE_ID, 150, array_keys($extranetGroups));
		$favoriteExtranetGroups = isModuleInstalled('extranet') ? $getFavorites($extranetSiteId, 100) : [];
		$favoriteIntranetGroups = $getFavorites(SITE_ID, 100, array_keys($favoriteExtranetGroups));

		$groups = array_replace(
			$extranetGroups,
			$intranetGroups,
			$favoriteExtranetGroups,
			$favoriteIntranetGroups
		);

		Collection::sortByColumn($groups, 'title');
		$cache->endDataCache($groups);

		return $groups;
	}

	public function resetAllAction()
	{
		if (!$this->isCurrentUserAdmin())
		{
			return null;
		}
		$sites = \CSite::getList();

		while ($site = $sites->Fetch())
		{
			$this->resetAction($site['SITE_ID']);
		}
	}

	public function resetAction(string $siteId)
	{
		if (!$this->isCurrentUserAdmin())
		{
			return null;
		}

		\CUserOptions::DeleteOptionsByName('intranet', 'left_menu_first_page_' . $siteId);
		\CUserOptions::DeleteOptionsByName('intranet', 'left_menu_preset_' . $siteId);
		\CUserOptions::DeleteOptionsByName('intranet', 'left_menu_self_items_' . $siteId);
		\CUserOptions::DeleteOptionsByName('intranet', 'left_menu_preset_' . $siteId);
		\CUserOptions::DeleteOptionsByName('intranet', 'left_menu_standard_items_' . $siteId);
		\CUserOptions::DeleteOptionsByName('intranet', 'left_menu_sorted_items_' . $siteId);
		\CUserOptions::DeleteOptionsByName('intranet', 'left_menu_groups_' . $siteId);
		\CUserOptions::DeleteOptionsByName('intranet', 'left_menu_collapsed');
		\CUserOptions::DeleteOptionsByName('intranet', 'left_menu_collapsed:online');

		\COption::RemoveOption('intranet', 'left_menu_preset');
		\COption::RemoveOption('intranet', 'show_menu_preset_popup');

		\COption::RemoveOption('intranet', 'left_menu_items_to_all_' . $siteId);
		\COption::RemoveOption('intranet', 'left_menu_custom_preset_items', $siteId);
		\COption::RemoveOption('intranet', 'left_menu_custom_preset_sort', $siteId);
	}
}
