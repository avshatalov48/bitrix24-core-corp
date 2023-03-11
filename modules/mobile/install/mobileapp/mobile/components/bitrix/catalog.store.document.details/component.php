<?php

use Bitrix\Main\Error;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Mobile\Controller\Catalog\StoreDocumentDetails;
use Bitrix\Mobile\UI\DetailCard\Tabs;
use Bitrix\Mobile\UI\DetailCard\Configurator;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$component = new class {
	use ErrorableImplementation;

	private $result = [];

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection();
	}

	private function checkModules(): void
	{
		try
		{
			Loader::requireModule('catalog');
			Loader::requireModule('crm');
			Loader::requireModule('currency');
			Loader::requireModule('mobile');
			Loader::requireModule('sale');
		}
		catch (LoaderException $exception)
		{
			$this->errorCollection[] = new Error($exception->getMessage(), $exception->getCode());
		}
	}

	private function showErrors(): array
	{
		return ['errors' => $this->getErrors()];
	}

	private function getDetailCard(): array
	{
		return
			(new Configurator(new StoreDocumentDetails()))
				->addTab((new Tabs\Editor('main')))
				->addTab(new Tabs\Product('products'))
				->toArray()
		;
	}

	public function execute(): array
	{
		$this->checkModules();
		if ($this->hasErrors())
		{
			return $this->showErrors();
		}

		if (!CAllCrmInvoice::installExternalEntities())
		{
			$this->errorCollection[] = new Error('Could not install external entities', 2494608);
		}
		elseif (!CCrmQuote::LocalComponentCausedUpdater())
		{
			$this->errorCollection[] = new Error('Could not install external entities', 2623264);
		}

		if ($this->hasErrors())
		{
			return $this->showErrors();
		}

		return [
			'card' => $this->getDetailCard(),
			'permissions' => \Bitrix\Mobile\Integration\Catalog\PermissionsProvider::getInstance()->getPermissions()
		];
	}
};

return $component->execute();
