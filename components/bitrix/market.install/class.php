<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Market\Application\Rights;
USE Bitrix\Rest\Marketplace\Client;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\Engine\ScopeManager;
use Bitrix\Rest\Marketplace;
use Bitrix\Rest\Engine\Access;
use Bitrix\Market\Link;

Loc::loadMessages(__FILE__);

class MarketInstallComponent extends CBitrixComponent
{
	/** @var ErrorCollection $errors */
	protected $errors;

	protected function checkRequiredParams(): bool
	{
		$this->errors = new ErrorCollection();
		if (!Loader::includeModule('rest'))
		{
			return false;
		}

		return true;
	}

	protected function listKeysSignedParameters(): array
	{
		return [
			'APP_CODE',
			'VERSION',
			'CHECK_HASH',
			'INSTALL_HASH',
			'IFRAME',
			'FROM',
		];
	}

	protected function canInstall($app = null)
	{
		return CRestUtil::canInstallApplication($app);
	}

	protected function getFormData()
	{
		$result = [];
		$res = AppTable::getList(
			[
				'filter' => [
					'=CODE' => $this->arParams['APP_CODE']
				],
			]
		);
		$app = $res->fetch();

		$appExternal = Client::getApp(
			$this->arParams['APP_CODE'],
			$this->arParams['VERSION'],
			$this->arParams['CHECK_HASH'],
			$this->arParams['INSTALL_HASH']
		);

		if ($appExternal)
		{
			$appData = $appExternal['ITEMS'];
			$appData['SILENT_INSTALL'] = $appData['SILENT_INSTALL'] !== 'Y' ? 'N' : 'Y';
			$appData['REDIRECT_PRIORITY'] = $appData['TYPE'] === AppTable::TYPE_CONFIGURATION;
			$appData['VERSION'] = $appData['VER'] ?? $appData['VERSION'];

			if ($appData['BY_SUBSCRIPTION'] === 'Y' && !Client::isSubscriptionAvailable())
			{
				$result['HELPER_DATA'] = [];
				$code = Access::getHelperCode(
					Access::ACTION_INSTALL,
					Access::ENTITY_TYPE_APP,
					$appData
				);
				if ($code !== '' && Loader::includeModule('ui'))
				{
					$appData['SILENT_INSTALL'] = 'N';
					$result['HELPER_DATA']['TEMPLATE_URL'] = \Bitrix\UI\InfoHelper::getUrl();
					$result['HELPER_DATA']['URL'] = str_replace(
						'/code/',
						'/' . $code . '/',
						$result['HELPER_DATA']['TEMPLATE_URL']
					);
				}
			}

			if ($app)
			{
				$appData['ID'] = $app['ID'];
				$appData['INSTALLED'] = $app['INSTALLED'];
				$appData['VERSION'] = $app['VERSION'];
				$appData['ACTIVE'] = $app['ACTIVE'];
				$appData['STATUS'] = $app['STATUS'];
				$appData['DATE_FINISH'] = $app['DATE_FINISH'];
				$appData['IS_TRIALED'] = $app['IS_TRIALED'];
			}

			$result['APP'] = $appData;
		}

		if (!$this->canInstall($result['APP']))
		{
			ShowError(Loc::getMessage('MARKET_INSTALL_ACCESS_DENIED'));
			return false;
		}

		if (isset($result['APP']['SILENT_INSTALL']) && $result['APP']['SILENT_INSTALL'] === 'Y')
		{
			$result['INSTALL_FINISH'] = Marketplace\Application::install(
				$result['APP']['CODE'],
				$result['APP']['VERSION'],
				!empty($this->arParams['CHECK_HASH']) ? $this->arParams['CHECK_HASH'] : false,
				!empty($this->arParams['INSTALL_HASH']) ? $this->arParams['INSTALL_HASH'] : false,
				$this->arParams['FROM'] ?? null
			);
		}

		$scopeList = ScopeManager::getInstance()->listScope();
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/rest/scope.php');
		$result['SCOPE_DENIED'] = [];
		if (is_array($result['APP']['RIGHTS']))
		{
			foreach ($result['APP']['RIGHTS'] as $key => $scope)
			{
				$result['APP']['RIGHTS'][$key] = [
					'TITLE' => Loc::getMessage('REST_SCOPE_' . mb_strtoupper($key)) ?: $scope,
					'DESCRIPTION' => Loc::getMessage('REST_SCOPE_' . mb_strtoupper($key) . '_DESCRIPTION')
				];
				if (!in_array($key, $scopeList, true))
				{
					$result['SCOPE_DENIED'][$key] = 1;
				}
			}
		}

		if (
			Loader::IncludeModule('bitrix24')
			&& !in_array(\CBitrix24::getLicensePrefix(), ['ru', 'ua', 'kz', 'by'])
		)
		{
			$result['TERMS_OF_SERVICE_LINK'] = Loc::getMessage('REST_MARKETPLACE_TERMS_OF_SERVICE_LINK');
		}

		$result['IS_HTTPS'] = Context::getCurrent()->getRequest()->isHttps();

		return $result;
	}

	protected function prepareResult() : bool
	{
		$result = $this->getFormData();
		if (!$result)
		{
			return false;
		}

		$this->arResult = $result;
		return true;
	}

	protected function printErrors() : void
	{
		foreach ($this->errors as $error)
		{
			ShowError($error);
		}
	}

	private function prepareParams(): void
	{
		if (!$this->arParams['FROM'])
		{
			$this->arParams['FROM'] = Link::getFrom();
		}
	}

	public function executeComponent()
	{
		$this->prepareParams();
		if ($this->getTemplateName() === 'scope_list')
		{
			$this->arParams['RIGHTS'] = Rights::prepare($this->arParams['RIGHTS']);
			$this->includeComponentTemplate();
			return;
		}

		if (!$this->checkRequiredParams())
		{
			$this->printErrors();
			return;
		}

		if (!$this->prepareResult())
		{
			$this->printErrors();
			return;
		}

		$page = '';
		if (!empty($this->arResult['HELPER_DATA']['URL']))
		{
			$page = 'helper';
		}

		$this->includeComponentTemplate($page);
	}
}
