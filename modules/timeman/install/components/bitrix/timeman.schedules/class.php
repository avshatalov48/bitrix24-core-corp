<?php
namespace Bitrix\Timeman\Component\Schedule;

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Component\BaseComponent;
use Bitrix\Timeman\Integration\Intranet\Settings;
use CComponentEngine;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!Main\Loader::includeModule('timeman'))
{
	ShowError(Loc::getMessage('TIMEMAN_MODULE_NOT_INSTALLED'));
	return;
}

class Component extends BaseComponent
{
	private $componentPage;

	protected function initParams()
	{
		$defaultPage = 'schedules/';
		if (!isset($this->arParams['VARIABLE_ALIASES']))
		{
			$this->arParams['VARIABLE_ALIASES'] = [
				'schedules/' => [],
				'schedules/add/' => [],
				'schedules/update/' => ['SCHEDULE_ID' => 'SCHEDULE_ID',],
				'schedules/shiftplan/' => ['SCHEDULE_ID' => 'SCHEDULE_ID',],

				'schedules/shifts/add/' => ['SCHEDULE_ID' => 'SCHEDULE_ID',],
				'schedules/shifts/update/' => ['SHIFT_ID' => 'SHIFT_ID', 'SCHEDULE_ID' => 'SCHEDULE_ID',],
			];
		}

		$defaultUrlTemplates = [
			'schedules/' => 'schedules/',
			'schedules/add/' => 'schedules/add/',
			'schedules/update/' => 'schedules/#SCHEDULE_ID#/update/',
			'schedules/shiftplan/' => 'schedules/#SCHEDULE_ID#/shiftplan/',

			'schedules/shifts/add/' => 'schedules/#SCHEDULE_ID#/shifts/add/',
			'schedules/shifts/update/' => 'schedules/#SCHEDULE_ID#/shifts/#SHIFT_ID#/update/',
		];

		if ($this->arParams['SEF_MODE'] == 'Y')
		{
			$defaultVariableAliases = [];
			$componentVariables = ['id'];
			$variables = [];
			$urlTemplates = CComponentEngine::makeComponentUrlTemplates(
				$defaultUrlTemplates,
				($this->arParams['SEF_URL_TEMPLATES'] ?? '')
			);
			$variableAliases = CComponentEngine::makeComponentVariableAliases($defaultVariableAliases, $this->arParams['VARIABLE_ALIASES']);
			$componentPage = CComponentEngine::parseComponentPath($this->arParams['SEF_FOLDER'], $urlTemplates, $variables);

			if (!(is_string($componentPage) && !empty($componentPage) && isset($defaultUrlTemplates[$componentPage])))
			{
				$componentPage = $defaultPage;
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
		if (!$this->isToolAvailable())
		{
			$this->includeComponentTemplate('tool-disabled');

			return;
		}

		$this->arParams['SEF_MODE'] = isset($this->arParams['SEF_MODE']) ? $this->arParams['SEF_MODE'] : 'Y';
		$this->arParams['SEF_FOLDER'] = isset($this->arParams['SEF_FOLDER']) ? $this->arParams['SEF_FOLDER'] : '/timeman/';
		$this->arParams['ELEMENT_ID'] = isset($this->arParams['ELEMENT_ID']) ? $this->arParams['ELEMENT_ID'] : $this->request->get('id');

		$this->arParams['IFRAME'] = isset($this->arParams['IFRAME']) ? $this->arParams['IFRAME'] : true;

		$this->initParams();

		if (empty($this->componentPage))
		{
			$this->componentPage = 'list/';
		}

		$this->includeComponentTemplateByName($this->componentPage);
	}

	private function isToolAvailable(): bool
	{
		return (new Settings())->isToolAvailable(Settings::TOOLS['worktime']);
	}

	private function includeComponentTemplateByName($componentPage)
	{
		switch ($componentPage)
		{
			case 'schedules/':
				$this->includeSchedulesList();
				break;
			case 'schedules/shiftplan/':
				$this->includeSchedulesShiftPlan();
				break;
			case 'schedules/add/':
			case 'schedules/update/':
				$this->includeSchedule();
				break;
			case 'schedules/shifts/add/':
			case 'schedules/shifts/update/':
				$this->includeShift();
				break;
			default:
				break;
		}
	}

	private function includeSchedulesList()
	{
		$this->getApplication()->IncludeComponent('bitrix:timeman.schedule.list', '', []);
	}

	private function includeSchedule()
	{
		$this->getApplication()->IncludeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'POPUP_COMPONENT_NAME' => 'bitrix:timeman.schedule.edit',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => [
					'SCHEDULE_ID' => isset($this->arResult['VARIABLES']['SCHEDULE_ID']) ? $this->arResult['VARIABLES']['SCHEDULE_ID'] : '',
				],
			]
		);
	}

	private function includeShift()
	{
		$this->getApplication()->IncludeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'POPUP_COMPONENT_NAME' => 'bitrix:timeman.schedule.shift.edit',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => [
					'SCHEDULE_ID' => isset($this->arResult['VARIABLES']['SCHEDULE_ID']) ? $this->arResult['VARIABLES']['SCHEDULE_ID'] : '',
					'SHIFT_ID' => isset($this->arResult['VARIABLES']['SHIFT_ID']) ? $this->arResult['VARIABLES']['SHIFT_ID'] : '',
				],
			]
		);
	}

	private function includeSchedulesShiftPlan()
	{
		$this->getApplication()->IncludeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'POPUP_COMPONENT_NAME' => 'bitrix:timeman.schedule.shiftplan',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => [
					'SCHEDULE_ID' => isset($this->arResult['VARIABLES']['SCHEDULE_ID']) ? $this->arResult['VARIABLES']['SCHEDULE_ID'] : '',
				],
				'USE_UI_TOOLBAR' => 'Y',
			]
		);
	}
}