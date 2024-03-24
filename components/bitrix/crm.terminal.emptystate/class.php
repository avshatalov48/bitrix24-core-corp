<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Terminal\Config\TerminalPaysystemManager;
use Bitrix\Main;

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
			$this->arResult['SBERBANK_PAY_SYSTEM_PATH'] = TerminalPaysystemManager::getInstance()->getSberQrPaysystemPath();
			$this->arResult['SBP_PAY_SYSTEM_PATH'] = TerminalPaysystemManager::getInstance()->getSbpPaysystemPath();
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