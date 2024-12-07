<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\HumanResources\Internals\HumanResourcesBaseComponent;

Bitrix\Main\Loader::includeModule('humanresources');

class HumanResourcesStartComponent extends HumanResourcesBaseComponent
{
	private array $defaultUrlTemplates = [
		'main_page' => '',
		'config_permissions' => 'config/permission/',
	];

	private array $urlWithVariables = [
		'main_page' => '',
		'config_permissions' => [],
	];

	public function exec(): void
	{
		$this->prepareTemplate();
	}

	private function prepareTemplate(): void
	{
		$this->setParam('SEF_MODE', $this->getParam('SEF_MODE') ?? 'Y');
		$this->setParam('SEF_FOLDER', $this->getParam('SEF_FOLDER') ?? '');
		$this->setParam('SEF_URL_TEMPLATES', $this->getParam('SEF_URL_TEMPLATES') ?? []);
		$componentPage = null;

		if ($this->getParam('SEF_MODE') === 'Y')
		{
			$urlTemplates = array_merge(
				$this->defaultUrlTemplates,
				$this->getParam('SEF_URL_TEMPLATES')
			);

			$componentPage = \CComponentEngine::parseComponentPath(
				$this->getParam('SEF_FOLDER'),
				$urlTemplates,
				$variables
			);

			\CComponentEngine::initComponentVariables($componentPage, [], [], $variables);

			foreach ($this->urlWithVariables as $code => $var)
			{
				$this->setParam(
					'PAGE_URL_' . mb_strtoupper($code),
					$this->getParam('SEF_FOLDER') . $urlTemplates[$code]
				);
			}
		}
		else
		{
			//TODO
		}

		$this->setTemplatePage($componentPage ?: (array_keys($this->urlWithVariables)[0] ?? ''));
		$this->setTemplateTitle(Loc::getMessage('HUMAN_RESOURCES_START_MAIN_PAGE_TITLE'));
	}
}