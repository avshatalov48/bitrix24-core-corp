<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\WebForm\Callback;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Voximplant\Security\Permissions;

Loc::loadMessages(__FILE__);


class VoximplantStartComponent extends \CBitrixComponent
{
	const INTEGRATIONS_URL = "https://integrations.bitrix24.site/";

	protected $account = null;
	protected $permissions = null;

	public function __construct(CBitrixComponent $component = null)
	{
		parent::__construct($component);

		if (\Bitrix\Main\Loader::includeModule("voximplant"))
		{
			$this->account = new CVoxImplantAccount();
			$this->permissions = Permissions::createWithCurrentUser();
		}
	}

	public function prepareResult()
	{
		$result = [];

		$result['SHOW_LINES'] = $this->permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE, \Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY);
		$result['SHOW_STATISTICS'] = $this->permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL_DETAIL, \Bitrix\Voximplant\Security\Permissions::ACTION_VIEW);
		$result['SHOW_PAY_BUTTON'] = \Bitrix\Voximplant\Security\Helper::canUpdateBalance();
		$result['SHOW_VOXIMPLANT'] = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion() !== 'by';
		$result['LINK_TO_BUY_SIP'] = CVoxImplantSip::getBuyLink();
		$result['LINK_TO_TARIFFS'] = CVoxImplantMain::getTariffsUrl();
		$result['IS_REST_ONLY'] = \Bitrix\Voximplant\Limits::isRestOnly();
		$result["MARKETPLACE_DETAIL_URL_TPL"] = $this->arParams["MARKETPLACE_DETAIL_URL_TPL"] ?: SITE_DIR . "marketplace/detail/#app#/";
		$result['NUMBERS_LIST'] = [];
		$userOptions = CUserOptions::GetOption("voximplant", "start", []);
		$result['BALANCE_TYPE'] = $userOptions["balance_type"] === "sip" ? "sip" : "balance";
		$result['RECORD_LIMIT'] = \CVoxImplantAccount::GetRecordLimit();
		$result['TELEPHONY_AVAILABLE'] = \Bitrix\Voximplant\Limits::canManageTelephony();
		$result['CRM_CALLBACK_FORM_CREATE_URL'] = $this->getCrmFormCreateUri();
		$result['CRM_CALLBACK_FORM_LIST_URL'] = $this->getCrmFormListUri();
		$result['IS_SHOWN_PRIVACY_POLICY'] = $this->isShownPrivacyPolicy();

		if(!$this->isRestOnly())
		{
			$apiClient = new CVoxImplantHttp();
			$accountInfo = $apiClient->GetAccountInfo([
				'withNumbers' => true,
				'withCallerIds' => true,
				'withSipStatus' => true
			]);

			if(!$accountInfo)
			{
				$result['ERROR_MESSAGE'] = $apiClient->GetError()->msg;
				return $result;
			}
			$this->account->UpdateAccountInfo($accountInfo);
			$sip = new CVoxImplantSip();

			$sip->updateSipRegistrations([
				'sipRegistrations' => $accountInfo->sip_status->result
			]);

			$rentedNumbers = CVoxImplantPhone::PrepareNumberFields($accountInfo->numbers);

			$numbersNumbersKeys = array_keys($rentedNumbers);
			sort($numbersNumbersKeys, SORT_STRING);
			$hash = md5(join("", $numbersNumbersKeys));
			if($hash != CVoxImplantPhone::getRentedNumbersHash())
			{
				CVoxImplantPhone::syncWithController([
					'numbers' => $rentedNumbers,
					'create' => true,
					'delete' => true
				]);
			}

			$callerIds = CVoxImplantPhone::PrepareCallerIdFields($accountInfo->caller_ids);
			CVoxImplantPhone::syncCallerIds(['callerIds' => $callerIds]);

			$sipConnections = \Bitrix\Voximplant\ConfigTable::getList([
				'select' => [
					'ID',
					'SEARCH_ID',
					'PORTAL_MODE',
					'PHONE_NAME',
					'SIP_SERVER' => 'SIP_CONFIG.SERVER',
					'SIP_LOGIN' => 'SIP_CONFIG.LOGIN'
				],
				'filter' => [
					'=PORTAL_MODE' => CVoxImplantConfig::MODE_SIP
				]
			])->fetchAll();

			if ($result['SHOW_LINES'])
			{
				foreach ($callerIds as $callerId)
				{
					$result['NUMBERS_LIST'][] = [
						'NUMBER' => $callerId['NUMBER'],
						'TYPE' => CVoxImplantConfig::MODE_LINK,
						'NAME' => $callerId['FORMATTED_NUMBER'],
						'DESCRIPTION' => CVoxImplantPhone::getCallerIdDescription($callerId)
					];
				}
				foreach ($rentedNumbers as $rentedNumber)
				{
					$result['NUMBERS_LIST'][] = [
						'NUMBER' => $rentedNumber['NUMBER'],
						'TYPE' => CVoxImplantConfig::MODE_RENT,
						'NAME' => $rentedNumber['FORMATTED_NUMBER'],
						'DESCRIPTION' => CVoxImplantPhone::getNumberDescription($rentedNumber)
					];
				}
				foreach ($sipConnections as $sipConnection)
				{
					$result['NUMBERS_LIST'][] = [
						'NUMBER' => $sipConnection['SEARCH_ID'],
						'TYPE' => CVoxImplantConfig::MODE_SIP,
						'NAME' => $sipConnection['PHONE_NAME'] ?: CVoxImplantConfig::GetDefaultPhoneName($sipConnection),
						'DESCRIPTION' => CVoxImplantSip::getConnectionDescription($sipConnection)
					];
				}
			}

			$result['LANG'] = $this->account->GetAccountLang();
			$result['CURRENCY'] = $this->account->GetAccountCurrency();

			$result['SIP'] = [
				'PAID' => $accountInfo->sip_paid == 'Y',
				'PAID_UNTIL' => $accountInfo->sip_paid_until ? (new \Bitrix\Main\Type\Date($accountInfo->sip_paid_until, 'Y-m-d'))->toString() : '',
				'FREE_MINUTES' => $accountInfo->sip_free_seconds ? (int)($accountInfo->sip_free_seconds / 60) : 0
			];
		}

		if (in_array($result['LANG'], Array('ua', 'kz')) || $this->isRestOnly())
		{
			$result['HAS_BALANCE'] = false;
			$result['ACCOUNT_BALANCE'] = 0;
			$result['BALANCE_CURRENCY'] = "";
		}
		else
		{
			$result['HAS_BALANCE'] = true;
			$result['ACCOUNT_BALANCE'] = $this->account->GetAccountBalance();
			$result['BALANCE_CURRENCY'] = $this->account->GetAccountCurrency();
		}

		if(\Bitrix\Main\Loader::includeModule('currency'))
		{
			$result['ACCOUNT_BALANCE_FORMATTED'] = \CCurrencyLang::CurrencyFormat($result['ACCOUNT_BALANCE'], $result['CURRENCY'], false);
		}
		else
		{
			$result['ACCOUNT_BALANCE_FORMATTED'] = number_format($result['ACCOUNT_BALANCE'], 2);
		}

		$result['MENU'] = [
			'MAIN' => $this->getMenuItems(),
			'SETTINGS' => $this->getSettingsItems(!empty($result['NUMBERS_LIST'])),
			'PARTNERS' => $this->getPartnerItems(),
			'CRM' => $this->getCrmMenuItems(),
		];

		return $result;
	}

	public function executeComponent()
	{
		if (!\Bitrix\Main\Loader::includeModule("voximplant"))
		{
			ShowError('voximplant module is not installed');
			return false;
		}
		$this->arResult = $this->prepareResult();

		if(Loader::includeModule("pull"))
		{
			\CPullWatch::Add($this->permissions->getUserId(), \Bitrix\Voximplant\Integration\Pull::BALANCE_PUSH_TAG);
		}
		$this->includeComponentTemplate();
	}


	public function hasCallerIds()
	{
		$row = \Bitrix\Voximplant\Model\CallerIdTable::getRow([
			'select' => ['ID'],
			'limit' => 1
		]);

		return $row != false;
	}

	public function hasSipOffice()
	{
		$row = \Bitrix\Voximplant\SipTable::getRow([
			'select' => ['ID'],
			'filter' => [
				'=TYPE' => CVoxImplantSip::TYPE_OFFICE
			],
			'limit' => 1
		]);

		return $row != false;
	}

	public function hasSipCloud()
	{
		$row = \Bitrix\Voximplant\SipTable::getRow([
			'select' => ['ID'],
			'filter' => [
				'=TYPE' => CVoxImplantSip::TYPE_CLOUD
			],
			'limit' => 1
		]);

		return $row != false;
	}

	public function isRestOnly()
	{
		return \Bitrix\Voximplant\Limits::isRestOnly();
	}

	public function canAddCallerId()
	{
		return false;
	}

	public function getMenuItems()
	{
		$result = [];
		if($this->isRestOnly() || !$this->permissions->canModifyLines())
		{
			return $result;
		}

		if ($this->account->GetAccountLang() !== 'ua')
		{
			$result[] = [
				'id' => 'rent-number',
				'title' => Loc::getMessage("VOX_START_NUMBER_RENT"),
				'className' => 'voximplant-start-logo-number-rental',
				'selected' => (CVoxImplantPhone::hasRentedNumber() ? ' voximplant-tile-item-selected' : ''),
				'onclick' => 'BX.Voximplant.Start.onRentButtonClick();'
			];
		}

		if(\Bitrix\Voximplant\Limits::canRentMultiple())
		{
			$result[] = [
				'id' => 'rent-number-5',
				'title' => Loc::getMessage("VOX_START_5_NUMBER_RENT"),
				'className' => 'voximplant-start-logo-package-of-numbers-5',
				'selected' => (CVoxImplantPhone::hasRentedNumberPacket(5) ? ' voximplant-tile-item-selected' : ''),
				'onclick' => 'BX.Voximplant.Start.onRentButtonClick(5);'
			];

			$result[] = [
				'id' => 'rent-number-10',
				'title' => Loc::getMessage("VOX_START_10_NUMBER_RENT"),
				'className' => 'voximplant-start-logo-package-of-numbers-10',
				'selected' => (CVoxImplantPhone::hasRentedNumberPacket(10) ? ' voximplant-tile-item-selected' : ''),
				'onclick' => 'BX.Voximplant.Start.onRentButtonClick(10);'
			];
		}

		$result[] = [
			'id' => 'sip-office',
			'title' => Loc::getMessage("VOX_START_SIP_PBX_OFFICE"),
			'className' => 'voximplant-start-logo-sip-connector-box',
			'selected' => ($this->hasSipOffice() ? ' voximplant-tile-item-selected' : ''),
			'onclick' => 'BX.Voximplant.Start.onSipButtonClick("office");'
		];

		$result[] = [
			'id' => 'sip-cloud',
			'title' => Loc::getMessage("VOX_START_SIP_PBX_CLOUD"),
			'className' => 'voximplant-start-logo-sip-connector-cloud',
			'selected' => ($this->hasSipCloud() ? ' voximplant-tile-item-selected' : ''),
			'onclick' => 'BX.Voximplant.Start.onSipButtonClick("cloud");'
		];

		if($this->canAddCallerId())
		{
			$result[] = [
				'id' => 6,
				'title' => Loc::getMessage("VOX_START_CALLER_ID"),
				'className' => 'voximplant-start-logo-bind-number',
				'selected' => ($this->hasCallerIds() ? ' voximplant-tile-item-selected' : ''),
				'onclick' => 'BX.Voximplant.Start.onAddCallerIdButtonClick();'
			];
		}

		/*if($this->areContractorDocumentsAvailable())
		{
			$result[] = [
				'id' => 7,
				'title' => Loc::getMessage("VOX_START_CONTRACTOR_DOCUMENTS"),
				'className' => 'voximplat-start-logo-contractor-documents',
				'onclick' => 'BX.Voximplant.Start.onShowInvoicesButtonClick();'
			];
		}*/

		$marketplaceItems = $this->getInstalledApps();
		$i = 0;
		foreach ($marketplaceItems as $item)
		{
			$icon = $this->getAppIcon($item["CODE"]);
			$result[] = [
				'id' => $item['ID'],
				'title' => $item['NAME'],
				'className' => 'voximplant-start-logo-rest-app ' . ($icon ? 'voximplant-start-logo-rest-app-with-icon' :  'voximplant-start-logo-rest-app-' . ($i++ % 6 + 1)),
				'selected' => ' voximplant-tile-item-selected',
				//'description' => $item['SHORT_DESC'],
				'image' => $icon,
				'onclick' => 'BX.Voximplant.Start.openRestAppLayout(\''. $item['ID'] .'\', \''. $item['CODE'] .'\')'
			];
		}

		return $result;
	}

	public function getSettingsItems($hasNumbers)
	{
		$result = [];

		if(!in_array($this->account->GetAccountLang(), ['ua', 'kz']) && !$this->isRestOnly() && $this->permissions->canModifySettings())
		{
			$result[] = [
				'id' => 'documents',
				'title' => Loc::getMessage("VOX_START_UPLOAD_DOCUMENTS"),
				'className' => 'voximplant-start-logo-documents-download',
				'onclick' => 'BX.Voximplant.Start.onUploadDocumentsButtonClick()'
			];
		}

		if(!$this->isRestOnly() && $this->permissions->canModifyLines() && $hasNumbers)
		{
			$result[] = [
				'id' => 'numberSettings',
				'title' => Loc::getMessage("VOX_START_CONFIGURE_NUMBERS"),
				'className' => 'voximplant-start-logo-number-settings',
				'onclick' => 'BX.Voximplant.Start.onConfigureNumbersButtonClick()'
			];
		}

		if($this->permissions->canModifySettings())
		{
			$result[] = [
				'id' => 'telephonySettings',
				'title' => Loc::getMessage("VOX_START_CONFIGURE_TELEPHONY"),
				'className' => 'voximplant-start-logo-settings',
				'onclick' => 'BX.Voximplant.Start.onConfigureTelephonyButtonClick()'
			];

			$result[] = [
				'id' => 'access',
				'title' => Loc::getMessage("VOX_START_ACCESS_CONTROL"),
				'className' => 'voximplant-start-logo-access-rights',
				'onclick' => 'BX.Voximplant.Start.onAccessControlButtonClick()'
			];
		}

		if(!\Bitrix\Voximplant\Limits::isRestOnly())
		{
			$result[] = [
				'id' => 'sipPhones',
				'title' => Loc::getMessage("VOX_START_SIP_PHONES"),
				'className' => 'voximplant-start-logo-device-connection',
				'onclick' => 'BX.Voximplant.Start.onSipPhonesButtonClick()'
			];
		}

		return $result;
	}

	public function getCrmMenuItems(): array
	{
		$result = [];
		if (Loader::includeModule('crm') && Callback::hasPhoneNumbers())
		{
			$result[] = [
				'id' => 'crmFormCallback',
				'title' => Loc::getMessage("VOX_START_CRM_CALLBACK"),
				'className' => 'voximplant-start-icon-service-callback',
				'onclick' => "BX.Voximplant.Start.onCrmCallbackFormClick();"
			];
		}

		return $result;
	}

	public function getCrmFormCreateUri(): ?string
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		global $USER;
		$CrmPerms = new CCrmPerms($USER->GetID());
		if ($CrmPerms->HavePerm('WEBFORM', BX_CRM_PERM_NONE, 'WRITE'))
		{
			return \CUtil::JSEscape(
				\Bitrix\Crm\WebForm\Manager::getCallbackListUrl([
					'show_permission_error' => 'Y'
				])
			);
		}

		return \CUtil::JSEscape(
			\Bitrix\Crm\WebForm\Manager::getCallbackNewFormEditUrl()
		);
	}

	public function getCrmFormListUri(): ?string
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}
		return \CUtil::JSEscape(
			\Bitrix\Crm\WebForm\Manager::getCallbackListUrl()
		);
	}

	public function areContractorDocumentsAvailable()
	{
		$account = new CVoxImplantAccount();

		return $account->GetAccountLang(false) === 'ru';
	}

	public function getInstalledApps()
	{
		if(!\Bitrix\Main\Loader::includeModule('rest'))
		{
			return [];
		}

		static $result = null;
		if(!is_null($result))
		{
			return $result;
		}
		$result = [];
		$cursor = \Bitrix\Rest\AppTable::getList([
			'select' => [
				'*',
				'MENU_NAME' => 'LANG.MENU_NAME',
				'MENU_NAME_DEFAULT' => 'LANG_DEFAULT.MENU_NAME',
				'MENU_NAME_LICENSE' => 'LANG_LICENSE.MENU_NAME',
			],
			'filter' => [
				'SCOPE' => '%telephony%',
				'=ACTIVE' => 'Y',
			]
		]);
		while ($row = $cursor->fetch())
		{
			$name = $row['MENU_NAME'] ?: $row['MENU_NAME_DEFAULT'] ?: $row['MENU_NAME_LICENSE'] ?: $row['APP_NAME'];
			$row['NAME'] = $name;
			$result[$row['CODE']] = $row;
		}

		return $result;
	}

	public function getPartnerApps()
	{
		if(!\Bitrix\Main\Loader::includeModule('rest'))
		{
			return [];
		}

		static $result = null;
		if($result !== null)
		{
			return $result;
		}

		$cacheTTL = 43200; // 12 hours
		$cacheId = 'voximplant_start_partner_items';
		$cachePath = '/voximplant/start/rest_partners/';
		$cache = \Bitrix\Main\Application::getInstance()->getCache();
		if($cache->initCache($cacheTTL, $cacheId, $cachePath))
		{
			$marketplaceItems = $cache->getVars();
		}
		else
		{
			$tag = array("telephony", "partners");
			if (\Bitrix\Main\Loader::includeModule("bitrix24"))
			{
				$tag[] = \CBitrix24::getLicensePrefix();
			}

			$marketplaceItems = \Bitrix\Rest\Marketplace\Client::getByTag($tag);

			if(is_array($marketplaceItems))
			{
				$cache->startDataCache();
				$cache->endDataCache($marketplaceItems);
			}
		}

		if(!$marketplaceItems || !is_array($marketplaceItems["ITEMS"]))
		{
			return [];
		}

		$installedApps = $this->getInstalledApps();
		foreach ($marketplaceItems["ITEMS"] as $k => $item)
		{
			$marketplaceItems["ITEMS"][$k]["INSTALLED_APP"] = $installedApps[$item["CODE"]] ?: null;
		}

		$result = $marketplaceItems;
		return $marketplaceItems;
	}

	public function getAppIcon($appCode)
	{
		$marketplaceItems = $this->getPartnerApps();
		if($marketplaceItems === null || !is_array($marketplaceItems["ITEMS"]))
		{
			return "";
		}

		foreach ($marketplaceItems["ITEMS"] as $k => $item)
		{
			if ($item["CODE"] === $appCode)
			{
				return $item["ICON_PRIORITY"];
			}
		}

		return "";
	}

	public function getTelephonyAppsCount()
	{
		if(!\Bitrix\Main\Loader::includeModule('rest'))
		{
			return 0;
		}

		$cacheTTL = 43200; // 12 hours
		$cacheId = 'voximplant_start_rest_app_count';
		$cachePath = '/voximplant/start/rest_partners/';
		$cache = \Bitrix\Main\Application::getInstance()->getCache();

		if($cache->initCache($cacheTTL, $cacheId, $cachePath))
		{
			$categoryItems = $cache->getVars();
		}
		else
		{
			$categoryItems = \Bitrix\Rest\Marketplace\Client::getCategory("telephony", 0, 1);
			if(is_array($categoryItems))
			{
				$cache->startDataCache();
				$cache->endDataCache($categoryItems);
			}
		}

		return $categoryItems["PAGES"];
	}

	public function getPartnerItems()
	{
		$result = [];
		$marketplaceItems = $this->getPartnerApps();

		if (is_array($marketplaceItems['ITEMS']))
		{
			foreach ($marketplaceItems['ITEMS'] as $item)
			{
				if($item['INSTALLED_APP'] === null)
				{
					$result[] = [
						'id' => $item['ID'],
						//'title' => $item['NAME'],
						'description' => $item['SHORT_DESC'],
						'image' => $item['ICON_PRIORITY'] ?: '',
						'onclick' => 'BX.Voximplant.Start.showRestApplication(\''. $item['CODE'] .'\')'
					];
				}
			}
		}

		$telephonyAppsCount = $this->getTelephonyAppsCount();
		$result[] = [
			'id' => 'counter',
			'counter' => true,
			'count' => $telephonyAppsCount,
			'title' => Loc::getMessage("VOX_START_TOTAL_APPLICATIONS"),
			'description' => Loc::getMessage("VOX_START_SEE_ALL"),
			'onclick' => "BX.SidePanel.Instance.open('/marketplace/?category=telephony&from=voip_start')"
		];

		$integrationsUrl = static::INTEGRATIONS_URL;
		if(\Bitrix\Main\Context::getCurrent()->getLanguage() !== "en")
		{
			$integrationsUrl .= \Bitrix\Main\Context::getCurrent()->getLanguage() . "/";
		}

		$result[] = [
			'id' => 'integration',
			'integration' => true,
			'description' => Loc::getMessage("VOX_START_INTEGRATION_REQUIRED"),
			'onclick' => "window.open('" . $integrationsUrl . "')"
		];

		$items = [];
		$userLang = LANGUAGE_ID ?? 'en';
		$cache = \Bitrix\Main\Data\Cache::createInstance();
		$cache_time = 86400;
		$cache_id = 'tpActionsRest' . $userLang;
		$cache_path = 'restItems';

		if ($cache_time > 0 && $cache->InitCache($cache_time, $cache_id, $cache_path))
		{
			$res = $cache->GetVars();

			if (!empty($res) && is_array($res))
			{
				$items = $res;
			}
		}

		if (count($items) <= 0)
		{
			$marketplace = new \Bitrix\Rest\Marketplace\MarketplaceActions();
			$restItems = $marketplace->getItems('telephony', $userLang);

			if ($restItems)
			{
				$items = $this->prepareRestItems($restItems);

				if (!is_null($items))
				{
					$cache->startDataCache($cache_time, $cache_id, $cache_path);
					$cache->endDataCache($items);
				}
			}
		}

		$result = array_merge($result, $items);

		return $result;
	}

	private function prepareRestItems(array $items)
	{
		$result = [];

		foreach ($items as $key => $item)
		{
			if ($item['SLIDER'] == "Y")
			{
				$frame = '<iframe src=\"'.$item['HANDLER'].'\" style=\"width: 100%;height: -webkit-calc(100vh - 10px);height: calc(100vh - 10px);\"></iframe>';
				$onclick = preg_match("/^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)/i", $item['HANDLER'])
					? "BX.SidePanel.Instance.open('voximplant', {
						contentCallback: function () {return \"".$frame."\";}})"
					: "BX.SidePanel.Instance.open('/marketplace/?category=".$item['HANDLER']."')";
			}
			else
			{
				$onclick = "window.open ('".$item['HANDLER']."', '_blank')";
			}


			$result[] = [
				'id' => $key,
				'description' => $item['NAME'],
				'image' => $item['IMAGE'] ?: '',
				'color' => $item['COLOR'],
				'onclick' => $onclick,
				'restItem' => true,
			];
		}

		return $result;
	}

	private function isShownPrivacyPolicy()
	{
		return !in_array($this->account->GetAccountLang(false), ['ru', 'kz', 'by']);
	}
}