<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\Error;

$component = new class {
	use ErrorableImplementation;
	public function __construct()
	{
		$this->errorCollection = new ErrorCollection();
	}

	private function checkModules()
	{
		try
		{
			Loader::includeModule('calendar');
			Loader::includeModule('mobile');
		}
		catch (\Bitrix\Main\LoaderException $exception)
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

	public function execute()
	{
		$this->checkModules();

		if ($this->hasErrors())
		{
			return $this->showErrors();
		}

		return [];
	}
};

return $component->execute();
