<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$component = new class {
	use \Bitrix\Main\ErrorableImplementation;

	public function __construct()
	{
		$this->errorCollection = new \Bitrix\Main\ErrorCollection();
	}

	public function execute()
	{
		$this->check();
		if ($this->hasErrors())
		{
			return $this->showErrors();
		}

		return [];
	}

	private function showErrors(): array
	{
		return ['errors' => $this->getErrors()];
	}

	private function check(): void
	{
		$this->checkModules();
	}

	private function checkModules(): void
	{
		try
		{
			\Bitrix\Main\Loader::requireModule('bizproc');
		}
		catch (\Bitrix\Main\LoaderException $exception)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error($exception->getMessage(), $exception->getCode());
		}
	}
};

return $component->execute();
