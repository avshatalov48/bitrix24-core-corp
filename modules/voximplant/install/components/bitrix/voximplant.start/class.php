<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
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

		\Bitrix\Main\Loader::includeModule("voximplant");

		$this->account = new CVoxImplantAccount();
		$this->permissions = Permissions::createWithCurrentUser();
	}

	public function executeComponent()
	{
		$this->arResult['SHOW_LINES'] = $this->permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE, \Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY);
		$this->arResult['SHOW_STATISTICS'] = $this->permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL_DETAIL, \Bitrix\Voximplant\Security\Permissions::ACTION_VIEW);
		$this->arResult['SHOW_PAY_BUTTON'	] = \Bitrix\Voximplant\Security\Helper::isAdmin() && !\Bitrix\Voximplant\Limits::isRestOnly();
		$this->arResult['LINK_TO_BUY'] = CVoxImplantMain::GetBuyLink();
		$this->arResult['IS_REST_ONLY'] = \Bitrix\Voximplant\Limits::isRestOnly();
		$this->arResult["MARKETPLACE_DETAIL_URL_TPL"] = $this->arParams["MARKETPLACE_DETAIL_URL_TPL"] ?: SITE_DIR . "marketplace/detail/#app#/";
		$this->arResult['NUMBERS_LIST'] = [];

		if(!$this->isRestOnly())
		{
			$apiClient = new CVoxImplantHttp();
			$accountInfo = $apiClient->GetAccountInfo([
				'withNumbers' => true,
				'withCallerIds' => true
			]);

			if(!$accountInfo)
			{
				$arResult['ERROR_MESSAGE'] = $apiClient->GetError()->msg;

				$this->includeComponentTemplate();
				return false;
			}

			$this->account->UpdateAccountInfo($accountInfo);
			$rentedNumbers = CVoxImplantPhone::PrepareNumberFields($accountInfo->numbers);

			if(count($rentedNumbers) != CVoxImplantPhone::GetRentedNumbersCount())
			{
				CVoxImplantPhone::syncWithController([
					'numbers' => $rentedNumbers,
					'create' => true,
					'delete' => true
				]);
			}

			foreach ($rentedNumbers as $rentedNumber)
			{
				$this->arResult['NUMBERS_LIST'][] = [
					'NUMBER' => $rentedNumber['NUMBER'],
					'TYPE' => CVoxImplantConfig::MODE_RENT,
					'NAME' => $rentedNumber['FORMATTED_NUMBER'],
					'DESCRIPTION' => CVoxImplantPhone::getNumberDescription($rentedNumber)
				];
			}

			$callerIds = array_values(CVoxImplantPhone::PrepareCallerIdFields($accountInfo->caller_ids));
			foreach ($callerIds as $callerId)
			{
				$this->arResult['NUMBERS_LIST'][] = [
					'NUMBER' => $callerId['NUMBER'],
					'TYPE' => CVoxImplantConfig::MODE_LINK,
					'NAME' => $callerId['FORMATTED_NUMBER'],
					'DESCRIPTION' => CVoxImplantPhone::getCallerIdDescription($callerId)
				];
			}

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

			foreach ($sipConnections as $sipConnection)
			{
				$this->arResult['NUMBERS_LIST'][] = [
					'NUMBER' => $sipConnection['SEARCH_ID'],
					'TYPE' => CVoxImplantConfig::MODE_SIP,
					'NAME' => $sipConnection['PHONE_NAME'] ?: CVoxImplantConfig::GetDefaultPhoneName($sipConnection),
					'DESCRIPTION' => CVoxImplantSip::getConnectionDescription($sipConnection)
				];
			}

			$this->arResult['LANG'] = $this->account->GetAccountLang();
			$this->arResult['CURRENCY'] = $this->account->GetAccountCurrency();

			$this->arResult['SIP'] = [
				'PAID' => $accountInfo->sip_paid == 'Y',
				'PAID_UNTIL' => $accountInfo->sip_paid_until ? (new \Bitrix\Main\Type\Date($accountInfo->sip_paid_until, 'Y-m-d'))->toString() : '',
				'FREE_MINUTES' => $accountInfo->sip_free_seconds ? (int)($accountInfo->sip_free_seconds / 60) : 0
			];
		}

		if (in_array($this->arResult['LANG'], Array('ua', 'kz')) || $this->isRestOnly())
		{
			$this->arResult['HAS_BALANCE'] = false;
			$this->arResult['ACCOUNT_BALANCE'] = 0;
			$this->arResult['BALANCE_CURRENCY'] = "";
		}
		else
		{
			$this->arResult['HAS_BALANCE'] = true;
			$this->arResult['ACCOUNT_BALANCE'] = $this->account->GetAccountBalance();
			$this->arResult['BALANCE_CURRENCY'] = $this->account->GetAccountCurrency();
		}


		if(\Bitrix\Main\Loader::includeModule('currency'))
		{
			$this->arResult['ACCOUNT_BALANCE_FORMATTED'] = \CCurrencyLang::CurrencyFormat($this->arResult['ACCOUNT_BALANCE'], $this->arResult['CURRENCY'], false);
		}
		else
		{
			$this->arResult['ACCOUNT_BALANCE_FORMATTED'] = number_format($this->arResult['ACCOUNT_BALANCE'], 2);
		}

		$this->arResult['MENU'] = [
			'MAIN' => $this->getMenuItems(),
			'SETTINGS' => $this->getSettingsItems(),
			'PARTNERS' => $this->getPartnerItems()
		];

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
		return (!in_array($this->account->GetAccountLang(), ['ua', 'kz', 'by']));
	}

	public function getMenuItems()
	{
		$result = [];
		if($this->isRestOnly() || !$this->permissions->canModifyLines())
		{
			return $result;
		}

		$result[] = [
			'id' => 'rent-number',
			'title' => Loc::getMessage("VOX_START_NUMBER_RENT"),
			'className' => 'voximplant-start-logo-number-rental',
			'selected' => (CVoxImplantPhone::hasRentedNumber() ? ' voximplant-tile-item-selected' : ''),
			'onclick' => 'BX.Voximplant.Start.onRentButtonClick();'
		];

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

	public function getSettingsItems()
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

		if(!$this->isRestOnly() && $this->permissions->canModifyLines() && count($this->arResult['NUMBERS_LIST']) > 0)
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

			if(!$this->isRestOnly())
			{
				$result[] = [
					'id' => 'access',
					'title' => Loc::getMessage("VOX_START_ACCESS_CONTROL"),
					'className' => 'voximplant-start-logo-access-rights',
					'onclick' => 'BX.Voximplant.Start.onAccessControlButtonClick()'
				];
			}
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
		if(!is_null($result))
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

		foreach ($marketplaceItems['ITEMS'] as $item)
		{
			if(is_null($item['INSTALLED_APP']))
			$result[] = [
				'id' => $item['ID'],
				//'title' => $item['NAME'],
				'description' => $item['SHORT_DESC'],
				'image' => $item['ICON_PRIORITY'] ?: '',
				'onclick' => 'BX.Voximplant.Start.showRestApplication(\''. $item['CODE'] .'\')'
			];
		}

		$telephonyAppsCount = $this->getTelephonyAppsCount();
		$result[] = [
			'id' => 'counter',
			'counter' => true,
			'count' => $telephonyAppsCount,
			'title' => Loc::getMessage("VOX_START_TOTAL_APPLICATIONS"),
			'description' => Loc::getMessage("VOX_START_SEE_ALL"),
			'onclick' => "BX.SidePanel.Instance.open('/marketplace/?category=telephony')"
		];

		$integrationsUrl = static::INTEGRATIONS_URL;
		if(\Bitrix\Main\Context::getCurrent()->getLanguage() != "en")
		{
			$integrationsUrl .= \Bitrix\Main\Context::getCurrent()->getLanguage() . "/";
		}

		$result[] = [
			'id' => 'integration',
			'integration' => true,
			'description' => Loc::getMessage("VOX_START_INTEGRATION_REQUIRED"),
			'onclick' => "window.open('" . $integrationsUrl . "')"
		];

		return $result;
	}

}