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
			Loader::requireModule('mobile');
			Loader::requireModule('catalog');
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

		return [
			'card' => $this->getDetailCard(),
			'permissions' => \Bitrix\Mobile\Integration\Catalog\PermissionsProvider::getInstance()->getPermissions()
		];
	}
};

return $component->execute();
