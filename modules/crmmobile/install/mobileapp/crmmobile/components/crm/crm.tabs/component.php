<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Error;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\CrmMobile\Controller\Kanban;

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
			Loader::requireModule('crm');
		}
		catch (LoaderException $exception)
		{
			$this->errorCollection[] = new Error($exception->getMessage(), $exception->getCode());
		}
	}

	/**
	 * @return array
	 */
	private function showErrors(): array
	{
		return ['errors' => $this->getErrors()];
	}

	public function execute(): array
	{
		$this->checkModules();
		if ($this->hasErrors())
		{
			return $this->showErrors();
		}

		return [
			'actions' => Kanban::getActionsList(),
		];
	}
};

return $component->execute();
