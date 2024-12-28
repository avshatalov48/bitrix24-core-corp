<?php

namespace Bitrix\HumanResources\Internals;

use Bitrix\Main\Localization\Loc;
use Bitrix\HumanResources;

Loc::loadMessages(__FILE__);

abstract class HumanResourcesBaseComponent extends \CBitrixComponent
{
	private string $templatePage = '';
	private \CMain $application;


	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->application = $GLOBALS['APPLICATION'];
	}

	protected function exec(): void {}

	public function executeComponent(): void
	{
		if (!$this->isAvailable())
		{
			LocalRedirect('/company/vis_structure.php');
			ShowError('Feature not available.');

			return;
		}

		$this->exec();
		$this->includeComponentTemplate($this->templatePage);
	}

	protected function getParam(string $code): mixed
	{
		return $this->arParams[$code] ?? null;
	}

	protected function setParam(string $code, mixed $value): void
	{
		$this->arParams[$code] = $value;
	}

	protected function setTemplatePage(string $templatePage): void
	{
		$this->templatePage = $templatePage;
	}

	protected function setTemplateTitle(?string $templateName): void
	{
		$this->application->SetTitle($templateName);
	}

	private function isAvailable(): bool
	{
		return HumanResources\Config\Storage::instance()->isPublicStructureAvailable();
	}
}