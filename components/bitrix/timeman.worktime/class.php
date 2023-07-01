<?php
namespace Bitrix\Timeman\Component\Schedule;

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Timeman\Component\BaseComponent;
use CComponentEngine;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!Main\Loader::includeModule('timeman'))
{
	ShowError(htmlspecialcharsbx(Loc::getMessage('TIMEMAN_MODULE_NOT_INSTALLED')));
	return;
}

class WorktimeComponent extends BaseComponent
{
	private $componentPage;
	private $defaultPage;

	protected function initParams()
	{
		$this->defaultPage = 'worktime/stats/';
		if (!isset($this->arParams['VARIABLE_ALIASES']))
		{
			$this->arParams['VARIABLE_ALIASES'] = [
				'worktime/stats/' => [],
				'worktime/records/report/' => ['RECORD_ID' => 'RECORD_ID',],
			];
		}

		$defaultUrlTemplates = [
			'worktime/stats/' => 'worktime/stats/',
			'worktime/records/report/' => 'worktime/records/#RECORD_ID#/report/',
		];

		if ($this->arParams['SEF_MODE'] == 'Y')
		{
			$defaultVariableAliases = [];
			$componentVariables = ['id'];
			$variables = [];
			$urlTemplates = CComponentEngine::makeComponentUrlTemplates(
				$defaultUrlTemplates,
				$this->arParams['SEF_URL_TEMPLATES'] ?? null
			);
			$variableAliases = CComponentEngine::makeComponentVariableAliases(
				$defaultVariableAliases,
				$this->arParams['VARIABLE_ALIASES']
			);
			$componentPage = CComponentEngine::parseComponentPath(
				$this->arParams['SEF_FOLDER'],
				$urlTemplates,
				$variables
			);

			if (!(is_string($componentPage) && !empty($componentPage) && isset($defaultUrlTemplates[$componentPage])))
			{
				$componentPage = $this->defaultPage;
			}

			CComponentEngine::initComponentVariables($componentPage, $componentVariables, $variableAliases, $variables);
			foreach ($urlTemplates as $url => $value)
			{
				$key = 'PATH_TO_'.mb_strtoupper($url);
				$this->arResult[$key] = isset($this->arParams[$key][0]) ? $this->arParams[$key] : $this->arParams['SEF_FOLDER'] . $value;
			}
		}
		else
		{
			$componentVariables = [
				isset($this->arParams['VARIABLE_ALIASES']['id']) ? $this->arParams['VARIABLE_ALIASES']['id'] : 'id',
			];

			$defaultVariableAliases = [];
			$variables = [];
			$variableAliases = CComponentEngine::makeComponentVariableAliases($defaultVariableAliases, $this->arParams['VARIABLE_ALIASES']);
			CComponentEngine::initComponentVariables(false, $componentVariables, $variableAliases, $variables);
		}

		$this->arResult = array_merge(
			[
				'COMPONENT_PAGE' => $componentPage,
				'VARIABLES' => $variables,
				'ALIASES' => $this->arParams['SEF_MODE'] == 'Y' ? [] : $variableAliases,
				'ID' => isset($variables['id']) ? strval($variables['id']) : '',
				'PATH_TO_USER_PROFILE' => $this->arParams['PATH_TO_USER_PROFILE'] ?? '',
			],
			is_array($this->arResult) ? $this->arResult : []
		);
		$this->componentPage = $componentPage;
	}

	public function executeComponent()
	{
		$this->arParams['SEF_MODE'] = isset($this->arParams['SEF_MODE']) ? $this->arParams['SEF_MODE'] : 'Y';
		$this->arParams['SEF_FOLDER'] = isset($this->arParams['SEF_FOLDER']) ? $this->arParams['SEF_FOLDER'] : '/timeman/';
		$this->arParams['ELEMENT_ID'] = isset($this->arParams['ELEMENT_ID']) ? $this->arParams['ELEMENT_ID'] : $this->request->get('id');

		$this->arParams['IFRAME'] = isset($this->arParams['IFRAME']) ? $this->arParams['IFRAME'] : true;

		$this->initParams();

		if (empty($this->componentPage))
		{
			$this->componentPage = $this->defaultPage;
		}
		if ($this->componentPage === 'worktime/stats/')
		{
			$uri = new Uri('timeman/timeman.php');
			if ($this->request->get('SCHEDULE_ID'))
			{
				$uri->addParams(['SCHEDULE_ID' => $this->request->get('SCHEDULE_ID')]);
			}

			LocalRedirect('/' . $uri->getLocator());
		}
		$this->includeComponentTemplateByName($this->componentPage);
	}

	private function includeComponentTemplateByName($componentPage)
	{
		switch ($componentPage)
		{
			case 'worktime/stats/':
				$this->includeWorktimeStats();
				break;
			case 'worktime/records/report/':
				$this->includeRecordReport();
				break;
			default:
				break;
		}
	}

	private function includeRecordReport()
	{
		$this->getApplication()->IncludeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'POPUP_COMPONENT_NAME' => 'bitrix:timeman.worktime.record.report',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => [
					'RECORD_ID' => isset($this->arResult['VARIABLES']['RECORD_ID']) ? $this->arResult['VARIABLES']['RECORD_ID'] : '',
				],
			]
		);
	}

	private function includeWorktimeStats()
	{
		$this->getApplication()->IncludeComponent('bitrix:timeman.worktime.stats', '', []);
	}
}