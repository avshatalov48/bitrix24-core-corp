<?php


namespace Bitrix\Market\ListTemplates;


use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Market\Application\Action;
use Bitrix\Market\Application\License;
use Bitrix\Market\Application\Versions;
use Bitrix\Market\NumberApps;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\Engine\Access;
use Bitrix\Rest\Marketplace\Client;
use Bitrix\Rest\Marketplace\Transport;
use Bitrix\Rest\Marketplace\Url;
use Bitrix\Rest\OAuthService;
use CRestUtil;

class Installed extends BaseTemplate
{
	private const FILTER_UPDATES = 'updates';

	public function setResult(bool $isAjax = false)
	{
		$title = Loc::getMessage('MARKETPLACE_INSTALLED');

		$this->result['TITLE'] = $title;

		global $APPLICATION;
		$APPLICATION->SetTitle($title);

		if(!Loader::includeModule('rest')) {
			die;
		}

		$this->result['ADMIN'] = CRestUtil::isAdmin();

		if(!$this->result['ADMIN']) {
			ShowError(Loc::getMessage('RMI_ACCESS_DENIED'));
			die;
		} else if (!OAuthService::getEngine()->isRegistered()) {
			try {
				OAuthService::register();
				OAuthService::getEngine()->getClient()->getApplicationList();
			} catch(SystemException $e) {
				ShowError($e->getMessage());
				die;
			}

			if (!OAuthService::getEngine()->isRegistered()) {
				ShowError(Loc::getMessage('RMI_ACCESS_DENIED_OAUTH_SERVICE_IS_NOT_REGISTERED'));
				die;
			}
		}

		$this->result['SUBSCRIPTION_BUY_URL'] = Url::getSubscriptionBuyUrl();

		if (!ModuleManager::isModuleInstalled('bitrix24')) {
			$this->result['POPUP_BUY_SUBSCRIPTION_PRIORITY'] = true;
		}

		AppTable::updateAppStatusInfo();
		Client::getNumUpdates();

		$this->result['FILTER_TAGS'] = $this->getFilterTags();

		$request = Context::getCurrent()->getRequest();
		$isFilterUpdates = $request->get('updates') == 'Y' || (isset($this->requestParams['updates']) && $this->requestParams['updates'] == 'Y');
		if ($isFilterUpdates) {
			$this->result['SELECTED_TAG'] = Installed::FILTER_UPDATES;
		}

		$filter = [
			'!=STATUS' => AppTable::STATUS_LOCAL,
			'=ACTIVE' => AppTable::ACTIVE,
		];
		if ((isset($this->filter['tag']) && $this->filter['tag'] == Installed::FILTER_UPDATES) || $isFilterUpdates) {
			$filter['=CODE'] = array_keys(Client::getAvailableUpdate());
		}

		$this->result['CURRENT_APPS_CNT'] = $this->getCountApps($filter);

		$navigation = new PageNavigation('market_installed_nav');
		$navigation->allowAllRecords(false)
			->setPageSize(20)
			->setCurrentPage($this->page)
			->setRecordCount($this->result['CURRENT_APPS_CNT']);

		$this->result['CUR_PAGE'] = $navigation->getCurrentPage();
		$this->result['PAGES'] = $navigation->getPageCount();

		$appCodes = [];
		$this->result['APPS'] = [];
		$dbApps = AppTable::getList([
			'filter' => $filter,
			'select' => [
				'*',
				'MENU_NAME' => 'LANG.MENU_NAME',
			],
			'offset' => $navigation->getOffset(),
			'limit' => $navigation->getLimit(),
		]);
		while ($app = $dbApps->Fetch()) {
			$appCodes[] = $app['CODE'];
			$app['APP_STATUS'] = AppTable::getAppStatusInfo($app, '');
			if(isset($app['APP_STATUS']['MESSAGE_REPLACE']['#DAYS#'])) {
				$app['APP_STATUS']['MESSAGE_REPLACE']['#DAYS#']++;
				$app['APP_STATUS']['MESSAGE_REPLACE']['#DAYS#'] = FormatDate('ddiff', time(), time() + 24 * 60 * 60 * $app['APP_STATUS']['MESSAGE_REPLACE']['#DAYS#']);
			}

			$this->result['APPS'][$app['CODE']] = $app;
		}

		$publishedApps = [];
		if (!empty($appCodes)) {
			$appsBuy = Client::getBuy($appCodes);
			$this->result['TOTAL_APPS'] = NumberApps::get($appsBuy);
			if (isset($appsBuy['ITEMS']) && is_array($appsBuy['ITEMS'])) {
				foreach ($appsBuy['ITEMS'] as $key => $app) {
					$publishedApps[] = $this->result['APPS'][$key]['CODE'];

					$this->result['APPS'][$key]['CODE'] = $app['CODE'] ?? null;
					$this->result['APPS'][$key]['VER'] = $app['VER'] ?? null;
					$this->result['APPS'][$key]['VER_TO_INSTALL'] = $app['VER'] ?? null;
					$this->result['APPS'][$key]['VERSIONS_FORMAT'] = Versions::getTextChanges((array)$app['VERSIONS']);
					$this->result['APPS'][$key]['NAME'] = $app['NAME'] ?? null;
					$this->result['APPS'][$key]['ICON'] = $app['ICON'] ?? null;
					$this->result['APPS'][$key]['DESC'] = $app['DESC'] ?? null;
					$this->result['APPS'][$key]['PUBLIC'] = $app['PUBLIC'] ?? null;
					$this->result['APPS'][$key]['DEMO'] = $app['DEMO'] ?? null;
					$this->result['APPS'][$key]['PARTNER_NAME'] = $app['PARTNER_NAME'] ?? null;
					$this->result['APPS'][$key]['PARTNER_URL'] = $app['PARTNER_URL'] ?? null;
					$this->result['APPS'][$key]['OTHER_REGION'] = $app['OTHER_REGION'] ?? null;
					$this->result['APPS'][$key]['VENDOR_SHOP_LINK'] = $app['VENDOR_SHOP_LINK'] ?? null;
					$this->result['APPS'][$key]['TYPE'] = $app['TYPE'];
					$this->result['APPS'][$key]['CAN_INSTALL'] = \CRestUtil::canInstallApplication($app);
					$this->result['APPS'][$key]['LANDING_TYPE'] = $app['LANDING_TYPE'] ?? null;
					$this->result['APPS'][$key]['SITE_PREVIEW'] = $app['SITE_PREVIEW'] ?? null;
					$this->result['APPS'][$key]['IS_SITE_TEMPLATE'] = $app['IS_SITE_TEMPLATE'] ?? null;
					$this->result['APPS'][$key]['RATING'] = $app['RATING'] ?? null;
					$this->result['APPS'][$key]['REVIEWS_NUMBER'] = $app['REVIEWS_NUMBER'] ?? null;
					$this->result['APPS'][$key]['NUM_INSTALLS'] = $app['NUM_INSTALLS'] ?? null;
					$this->result['APPS'][$key]['HOLD_INSTALL_BY_TRIAL'] = (isset($app['HOLD_INSTALL_BY_TRIAL']) && $app['HOLD_INSTALL_BY_TRIAL'] === 'Y') ? 'Y' : 'N';
				}
			}
		}

		if (empty($appCodes)) {
			$response = Transport::instance()->call(Transport::METHOD_TOTAL_APPS);
			$this->result['TOTAL_APPS'] = NumberApps::get($response);
		}

		$unpublishedApps = array_diff($appCodes, $publishedApps);

		foreach ($this->result['APPS'] as &$appItem) {
			$appItem['UNPUBLISHED'] = (in_array($appItem['CODE'], $unpublishedApps)) ? 'Y' : 'N';

			$appItem['REST_ACCESS'] = Access::isAvailable($appItem['CODE']) && Access::isAvailableCount(Access::ENTITY_TYPE_APP, $appItem['CODE']);
			if (!$appItem['REST_ACCESS']) {
				$appItem['REST_ACCESS_HELPER_CODE'] = Access::getHelperCode(Access::ACTION_INSTALL, Access::ENTITY_TYPE_APP, $appItem);
			}

			$appItem['BUTTONS'] = Action::list($appItem);

			if (isset($appItem['BUTTONS'][Action::UPDATE])) {
				$appItem['INSTALL_INFO'] = Action::getJsAppData($appItem);
				$appItem['LICENSE'] = License::getInfo($appItem);
			}

			if (isset($appItem['BUTTONS'][Action::RIGHTS_SETTING]) || isset($appItem['BUTTONS'][Action::DELETE])) {
				$appItem['SHOW_CONTEXT_MENU'] = 'Y';
			}
		}
		unset($appItem);

		$this->result['APPS'] = array_values($this->result['APPS']);
	}

	private function getFilterTags(): array
	{
		$numUpdates = Client::getAvailableUpdateNum();

		return [
			[
				'name' => Loc::getMessage('MARKET_NEED_UPDATING', ['#COUNT#' => $numUpdates]),
				'value' => Installed::FILTER_UPDATES,
			]
		];
	}

	private function getCountApps($filter): int
	{
		$dbApps = AppTable::getList([
			'filter' => $filter,
			'select' => ['ID'],
			"count_total" => true,
		]);

		return $dbApps->getCount();
	}
}