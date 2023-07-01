<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Uri;
use Bitrix\Market\AppFavoritesTable;
use Bitrix\Market\Application\Action;
use Bitrix\Market\Application\License;
use Bitrix\Market\Application\Rights;
use Bitrix\Market\Application\Versions;
use Bitrix\Market\Menu;
use Bitrix\Market\Subscription\Status;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\Engine\Access;
use Bitrix\Rest\Marketplace\Client;
use Bitrix\Rest\Marketplace\Transport;
use Bitrix\Rest\Marketplace\Url;
use Bitrix\Rest\OAuthService;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

Loader::includeModule('market');

class RestMarketDetail extends CBitrixComponent
{
	private $appItem;
	private $version = 0;

	public function executeComponent()
	{
		if ($this->prepareInfo()) {
			$this->arResult['APP'] = $this->getApp();
			$this->prepareResult();
		}

		$this->includeComponentTemplate();
	}

	private function prepareInfo(): bool
	{
		if (Loader::includeModule('rest') && !OAuthService::getEngine()->isRegistered()) {
			try {
				OAuthService::register();
				OAuthService::getEngine()->getClient()->getApplicationList();
			} catch (SystemException $e) {
				ShowError($e->getMessage());
				return false;
			}

			AppTable::updateAppStatusInfo();
			Client::getNumUpdates();
		}

		$this->arParams['COMPONENT_NAME'] = 'bitrix:market.detail';

		$this->arResult['REST_ACCESS'] = Access::isAvailable($this->arParams['APP_CODE']) && Access::isAvailableCount(Access::ENTITY_TYPE_APP, $this->arParams['APP_CODE']);
		$this->arResult['CHECK_HASH'] = false;
		$this->arResult['INSTALL_HASH'] = false;
		$this->arResult['START_INSTALL'] = false;

		if (isset($_GET['ver']) && intval($_GET['ver']) && isset($_GET['check_hash']) && isset($_GET['install_hash'])) {
			$checkHash = $_GET['check_hash'];
			$check = md5(rtrim(CHTTP::URN2URI('/'), '/') . '|' . $_GET['ver'] . '|' . $this->arParams['APP_CODE']);

			if($checkHash === $check) {
				$this->version = (int)$_GET['ver'];

				$this->arResult['CHECK_HASH'] = $check;
				$this->arResult['INSTALL_HASH'] = $_GET['install_hash'];

				$this->arResult['START_INSTALL'] = isset($_GET['install']) && $_GET['install'] == 'Y';
			}
		}

		$dbApp = AppTable::getList([
			'filter' => [
				'=CODE' => $this->arParams['APP_CODE'],
			]
		]);
		$this->appItem = $dbApp->fetch();

		if($this->version > 0 && $this->appItem['ACTIVE'] === 'N' && $this->appItem['STATUS'] === AppTable::STATUS_PAID) {
			$this->version = (int)$this->appItem['VERSION'];
		} elseif (
			$this->arResult['START_INSTALL'] &&
			$this->appItem['ID'] > 0 &&
			$this->appItem['ACTIVE'] === AppTable::ACTIVE &&
			$this->appItem['INSTALLED'] === AppTable::INSTALLED &&
			(int)$this->appItem['VERSION'] === (int)$_GET['ver']
		) {
			$this->arResult['START_INSTALL'] = false;
		}

		return true;
	}

	private function getApp(): array
	{
		global $USER;

		$result = [];

		$queryFields = [
			'code' => $this->arParams['APP_CODE']
		];

		if($this->version > 0) {
			$queryFields['ver'] = $this->version;
		}

		if($this->arResult['CHECK_HASH'] !== false) {
			$queryFields['check_hash'] = $this->arResult['CHECK_HASH'];
			$queryFields['install_hash'] = $this->arResult['INSTALL_HASH'];
		}

		$batch = [
			Transport::METHOD_MARKET_APP => [
				Transport::METHOD_MARKET_APP,
				$queryFields,
			],
			Transport::METHOD_GET_REVIEWS => [
				Transport::METHOD_GET_REVIEWS,
				[
					'filter_app' => $this->arParams['APP_CODE'],
					'filter_user' => $USER->GetID(),
				],
			],
		];

		$response = Transport::instance()->batch($batch);
		if (isset($response[Transport::METHOD_MARKET_APP]['ITEMS']) && is_array($response[Transport::METHOD_MARKET_APP]['ITEMS'])) {
			$result = $response[Transport::METHOD_MARKET_APP]['ITEMS'];

			if (is_array($response[Transport::METHOD_GET_REVIEWS])) {
				$result['REVIEWS'] = $response[Transport::METHOD_GET_REVIEWS];
			}
		}

		return $result;
	}

	private function prepareResult()
	{
		if (empty($this->arResult['APP'])) {
			return;
		}

		global $APPLICATION;
		if (isset($this->arResult['APP']['NAME'])) {
			$APPLICATION->SetTitle(htmlspecialcharsbx($this->arResult['APP']['NAME']));
		}

		$this->arResult['APP']['IS_FAVORITE'] = in_array($this->arParams['APP_CODE'], AppFavoritesTable::getUserFavorites()) ? 'Y' : 'N';

		if (!empty($this->appItem)) {
			$this->arResult['APP']['ID'] = $this->appItem['ID'];
			$this->arResult['APP']['INSTALLED'] = $this->appItem['INSTALLED'];
			$this->arResult['APP']['ACTIVE'] = $this->appItem['ACTIVE'];
			$this->arResult['APP']['STATUS'] = $this->appItem['STATUS'];
			$this->arResult['APP']['DATE_FINISH'] = $this->appItem['DATE_FINISH'];
			$this->arResult['APP']['IS_TRIALED'] = $this->appItem['IS_TRIALED'];
			$this->arResult['APP']['WAS_INSTALLED'] = 'Y';
		}

		if (
			isset($this->arResult['APP']['ACTIVE'])
			&& $this->arResult['APP']['ACTIVE'] === 'Y'
			&& isset($this->arResult['APP']['TYPE'])
			&& $this->arResult['APP']['TYPE'] !== AppTable::TYPE_CONFIGURATION
		) {
			$this->arResult['APP']['UPDATES'] = Client::getAvailableUpdate($this->arResult['APP']['CODE']);
		}

		if (isset($this->arResult['APP']['DATE_PUBLIC']) && $this->arResult['APP']['DATE_PUBLIC']) {
			$timestamp = MakeTimeStamp($this->arResult['APP']['DATE_PUBLIC'], 'DD.MM.YYYY');
			$this->arResult['APP']['DATE_PUBLIC'] = ConvertTimeStamp($timestamp);
		}

		if (isset($this->arResult['APP']['DATE_UPDATE']) && $this->arResult['APP']['DATE_UPDATE']) {
			$timestamp = MakeTimeStamp($this->arResult['APP']['DATE_UPDATE'], 'DD.MM.YYYY');
			$this->arResult['APP']['DATE_UPDATE'] = ConvertTimeStamp($timestamp);
		}

		$this->arResult['ACCESS_HELPER_CODE'] = '';

		$appBySubscription = isset($this->arResult['APP']['BY_SUBSCRIPTION']) && $this->arResult['APP']['BY_SUBSCRIPTION'] === 'Y';

		if (!$this->arResult['REST_ACCESS'] || ($appBySubscription && !Client::isSubscriptionAvailable())) {
			$this->arResult['ACCESS_HELPER_CODE'] = Access::getHelperCode(Access::ACTION_INSTALL, Access::ENTITY_TYPE_APP, $this->arResult['APP']);
		}

		$this->arResult['PRICE_POLICY_SLIDER'] =  $appBySubscription ? Status::getSlider() : '';

		$this->arResult['APP']['SLIDER_IMAGES'] = [];
		if (isset($this->arResult['APP']['IMAGES']) && (is_array($this->arResult['APP']['IMAGES'])))
		{
			foreach ($this->arResult['APP']['IMAGES'] as $image) {
				$this->arResult['APP']['SLIDER_IMAGES'][] = [
					'IMG' => $image,
					'LINK' => "#",
				];
			}
		}

		$this->arResult['APP']['VER_TO_INSTALL'] = $this->arResult['APP']['VER'] ?? '';
		$this->arResult['APP']['VERSIONS_FORMAT'] = Versions::getTextChanges((array)($this->arResult['APP']['VERSIONS'] ?? []));

		$this->arResult['APP']['SLIDER_ARROWS'] = (count($this->arResult['APP']['SLIDER_IMAGES']) > 2);

		$rights = new Rights($this->arResult['APP']['RIGHTS'] ?? []);
		$this->arResult['APP']['SCOPES'] = $rights->getInfo();
		$this->arResult['APP']['SCOPES_TO_SHOW'] = $rights->getQuantityToShow();
		$this->arResult['APP']['SCOPES_MORE_BUTTON'] = $rights->isShowMoreButton() ? 'Y' : 'N';

		$this->arResult['APP']['BUTTONS'] = Action::list($this->arResult['APP'], $this->arResult['START_INSTALL']);

		if (!empty($this->appItem)) {
			$installedApps = Menu::getInstalledApps((int)$this->appItem['ID']);
			$this->arResult['APP']['BUTTON_OPEN_APP'] = '';
			if (count($installedApps) === 1) {
				$uri = new Uri($installedApps[0]['PATH']);
				$uri->addParams(['from' => 'market_detail']);
				$this->arResult['APP']['BUTTON_OPEN_APP'] = $uri->getUri();
			}
		}

		$this->arResult['APP']['MENU_ITEMS'] = $this->getMenuItems();

		$this->arResult['APP']['INSTALL_INFO'] = Action::getJsAppData(
			$this->arResult['APP'],
			$this->arResult['CHECK_HASH'],
			$this->arResult['INSTALL_HASH']
		);

		$this->arResult['LICENSE'] = License::getInfo($this->arResult['APP']);

		if((isset($this->arResult['APP']['TYPE'])) && $this->arResult['APP']['TYPE'] == AppTable::TYPE_CONFIGURATION) {
			$this->arResult['IMPORT_PAGE'] = Url::getConfigurationImportAppUrl($this->arResult['APP']['CODE']);
			$this->arResult['OPEN_IMPORT'] = $this->needOpenImport() ? 'Y' : 'N';

			if($this->arResult['CHECK_HASH']) {
				$uri = new Bitrix\Main\Web\Uri($this->arResult['IMPORT_PAGE']);
				$uri->addParams([
					'check_hash' => $this->arResult['CHECK_HASH'],
					'install_hash' => $this->arResult['INSTALL_HASH']
				]);
				$this->arResult['IMPORT_PAGE'] = $uri->getUri();
			}
		}

	}

	private function needOpenImport(): bool
	{
		if (
			isset ($this->arResult['APP']['INSTALLED']) && $this->arResult['APP']['INSTALLED'] === AppTable::NOT_INSTALLED &&
			isset ($this->arResult['APP']['TYPE']) && $this->arResult['APP']['TYPE'] === AppTable::TYPE_CONFIGURATION &&
			isset ($this->arResult['APP']['ACTIVE']) && $this->arResult['APP']['ACTIVE'] === AppTable::ACTIVE
		) {
			return true;
		}

		return false;
	}

	private function getMenuItems(): array
	{
		$menu = [];

		if (!empty($this->arResult['APP']['CONTACT_DEVELOPER'])) {
			$menu[] = [
				'text' => Loc::getMessage('MARKET_DETAIL_ITEM_CONTACT_DEVELOPERS'),
				'href' => $this->arResult['APP']['CONTACT_DEVELOPER'],
				'target' => '_blank',
			];
		}

		if (!empty($this->arResult['APP']['REQUEST_DEMO'])) {
			$menu[] = [
				'text' => Loc::getMessage('MARKET_DETAIL_ITEM_REQUEST_A_DEMO'),
				'href' => $this->arResult['APP']['REQUEST_DEMO'],
				'target' => '_blank',
			];
		}

		return $menu;
	}
}