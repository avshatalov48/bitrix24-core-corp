<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Sale;

class CrmTerminalEmptyState extends \CBitrixComponent implements Main\Engine\Contract\Controllerable, Main\Errorable
{
	/** @var Main\ErrorCollection */
	protected $errorCollection;

	public function configureActions()
	{
		Main\Loader::includeModule('sale');

		return [];
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->errorCollection = new Main\ErrorCollection();

		return parent::onPrepareComponentParams($arParams);
	}

	private function initResult(): void
	{
		$this->arResult = [
			'ZONE' => '',

			'SBERBANK_PAY_SYSTEM_PATH' => '',
			'SBP_PAY_SYSTEM_PATH' => '',

			'ERROR_MESSAGES' => [],
		];
	}

	private function prepareResult(): void
	{
		$this->arResult['ZONE'] = $this->getZone();

		if ($this->arResult['ZONE'] === 'ru')
		{
			$this->arResult['SBERBANK_PAY_SYSTEM_PATH'] = $this->getPaySystemPath($this->getSberQrPaySystem());
			$this->arResult['SBP_PAY_SYSTEM_PATH'] = $this->getPaySystemPath($this->getSpbPaySystem());
		}
	}

	private function getZone(): string
	{
		if (Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			return \CBitrix24::getPortalZone();
		}

		$iterator = Main\Localization\LanguageTable::getList(
			[
				'select' => ['ID'],
				'filter' => ['=DEF' => 'Y', '=ACTIVE' => 'Y'],
			]
		);
		if ($row = $iterator->fetch())
		{
			return (string)$row['ID'];
		}

		return 'en';
	}

	private function getSpbPaySystem(): array
	{
		$actionFile = $this->getYandexCheckoutHandlerCode();

		$paySystem = $this->getPaySystem($actionFile, \Sale\Handlers\PaySystem\YandexCheckoutHandler::MODE_SBP);
		if (!$paySystem)
		{
			$paySystem = [
				'ACTION_FILE' => $actionFile,
				'PS_MODE' => \Sale\Handlers\PaySystem\YandexCheckoutHandler::MODE_SBP,
			];
		}

		return $paySystem;
	}

	private function getSberQrPaySystem(): array
	{
		$actionFile = $this->getYandexCheckoutHandlerCode();

		$paySystem = $this->getPaySystem($actionFile, \Sale\Handlers\PaySystem\YandexCheckoutHandler::MODE_SBERBANK_QR);
		if (!$paySystem)
		{
			$paySystem = [
				'ACTION_FILE' => $actionFile,
				'PS_MODE' => \Sale\Handlers\PaySystem\YandexCheckoutHandler::MODE_SBERBANK_QR,
			];
		}

		return $paySystem;
	}

	private function getPaySystem(string $actionFile, string $psMode): ?array
	{
		$paySystem = Sale\PaySystem\Manager::getList([
			'filter' => [
				'=ACTION_FILE' => $actionFile,
				'=PS_MODE' => $psMode,
				'=ACTIVE' => 'Y',
				'=ENTITY_REGISTRY_TYPE' => Sale\Registry::REGISTRY_TYPE_ORDER,
			],
			'select' => ['ID','ACTION_FILE', 'PS_MODE'],
			'order' => ['ID' => 'DESC'],
			'limit' => 1,
		])->fetch();

		return $paySystem ?: null;
	}

	private function getYandexCheckoutHandlerCode(): string
	{
		static $result = null;
		if (!is_null($result))
		{
			return $result;
		}

		$result = (string)Sale\PaySystem\Manager::getFolderFromClassName(
			\Sale\Handlers\PaySystem\YandexCheckoutHandler::class
		);
		Sale\PaySystem\Manager::includeHandler($result);

		return $result;
	}

	private function getPaySystemPath(array $queryParams): string
	{
		$paySystemPath = $this->getPaySystemComponentPath();
		$paySystemPath->addParams($queryParams);

		return $paySystemPath->getLocator();
	}

	private function getPaySystemComponentPath(): Main\Web\Uri
	{
		$paySystemPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem');
		$paySystemPath = getLocalPath('components' . $paySystemPath . '/slider.php');

		return new Main\Web\Uri($paySystemPath);
	}

	private function checkModules(): bool
	{
		Main\Loader::includeModule('sale');

		if (!Main\Loader::includeModule('mobile'))
		{
			$this->arResult['ERROR_MESSAGES'][] = Main\Localization\Loc::getMessage('CRM_TERMINAL_EMPTY_STATE_COMPONENT_MODULE_MOBILE_NOT_FOUND');
			return false;
		}

		return true;
	}

	public function prepareResultAction(): array
	{
		$this->prepareResult();
		return [
			'sberbankPaySystemPath' => $this->arResult['SBERBANK_PAY_SYSTEM_PATH'],
			'spbPaySystemPath' => $this->arResult['SBP_PAY_SYSTEM_PATH'],
		];
	}

	/**
	 * Getting array of errors.
	 * @return Main\Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Main\Error
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function executeComponent()
	{
		if ($this->checkModules())
		{
			$this->initResult();
			$this->prepareResult();
		}

		$this->includeComponentTemplate();
	}
}