<?php

use Bitrix\Main;
use Bitrix\BIConnector\Superset\ExternalSource\Source;
use Bitrix\BIConnector\Superset\ExternalSource\SourceRepository;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class ApacheSupersetSourceConnectListComponent extends CBitrixComponent
{
	use \Bitrix\Main\ErrorableImplementation;

	public function executeComponent()
	{
		$this->errorCollection = new \Bitrix\Main\ErrorCollection();

		if ($this->checkModules())
		{
			$this->initSourceList();
			$this->includeComponentTemplate();
		}

		if ($this->hasErrors())
		{
			$this->showErrors();
		}
	}

	private function checkModules(): bool
	{
		if (!Main\Loader::includeModule('biconnector'))
		{
			$this->errorCollection->setError(new \Bitrix\Main\Error("module 'biconnector' required"));

			return false;
		}

		return true;
	}

	private function showErrors(): void
	{
		$errors = $this->getErrors();
		if (count($errors) > 0)
		{
			$this->includeErrorComponent($errors[0]->getMessage());
		}
	}

	protected function includeErrorComponent(string $errorMessage, string $description = null): void
	{
		global $APPLICATION;

		$APPLICATION->IncludeComponent(
			'bitrix:ui.info.error',
			'',
			[
				'TITLE' => $errorMessage,
				'DESCRIPTION' => $description,
			]
		);
	}

	private function initSourceList(): void
	{
		$this->arResult['SOURCE_LIST'] = $this->getSourceList();
	}

	/**
	 * @return Source[]
	 */
	private function getSourceList(): array
	{
		$sources = SourceRepository::getSources();
		usort($sources, static fn(Source $source) => $source->isConnected());

		return $sources;
	}
}
