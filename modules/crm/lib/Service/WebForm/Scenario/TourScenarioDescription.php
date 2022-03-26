<?php

namespace Bitrix\Crm\Service\WebForm\Scenario;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class TourScenarioDescription
{
	public static function getScenarioSteps(string $scenario)
	{
		$scenarios =  [
			BaseScenario::SCENARIO_FORM_ON_SITE => [
				[
					'title' => Loc::getMessage('CRM_SERVICE_SCENARIO_TOUR_FORM_ON_SITE_STEP1_TITLE'),
					'text' => Loc::getMessage('CRM_SERVICE_SCENARIO_TOUR_FORM_ON_SITE_STEP1_DESCRIPTION'),
					'target' => '.landing-form-editor-share-button',
				],
			],
			BaseScenario::SCENARIO_FORM_IN_BUTTON => [
				[
					'title' => Loc::getMessage('CRM_SERVICE_SCENARIO_TOUR_FORM_IN_BUTTON_STEP1_TITLE'),
					'text' => Loc::getMessage('CRM_SERVICE_SCENARIO_TOUR_FORM_IN_BUTTON_STEP1_DESCRIPTION'),
					'target' => '.landing-form-editor-share-button',
				],
			],
			BaseScenario::SCENARIO_FORM_ON_PAGE => [
				[
					'title' => Loc::getMessage('CRM_SERVICE_SCENARIO_TOUR_FORM_ON_PAGE_STEP1_TITLE'),
					'text' => Loc::getMessage('CRM_SERVICE_SCENARIO_TOUR_FORM_ON_PAGE_STEP1_DESCRIPTION'),
					'target' => '.landing-form-editor-share-button',
				],
			],
			BaseScenario::SCENARIO_FORM_IN_LINK => [
				[
					'title' => Loc::getMessage('CRM_SERVICE_SCENARIO_TOUR_FORM_IN_LINK_STEP1_TITLE'),
					'text' => Loc::getMessage('CRM_SERVICE_SCENARIO_TOUR_FORM_IN_LINK_STEP1_DESCRIPTION'),
					'target' => '.landing-form-editor-share-button',
				],
			],
			BaseScenario::SCENARIO_FORM_IN_WIDGET => [
				[
					'title' => Loc::getMessage('CRM_SERVICE_SCENARIO_TOUR_FORM_IN_WIDGET_STEP1_TITLE'),
					'text' => Loc::getMessage('CRM_SERVICE_SCENARIO_TOUR_FORM_IN_WIDGET_STEP1_DESCRIPTION'),
					'target' => '.landing-form-editor-share-button',
				],
			],
			BaseScenario::SCENARIO_FORM_ON_TIMER => [
				[
					'title' => Loc::getMessage('CRM_SERVICE_SCENARIO_TOUR_FORM_ON_TIMER_STEP1_TITLE'),
					'text' => Loc::getMessage('CRM_SERVICE_SCENARIO_TOUR_FORM_ON_TIMER_STEP1_DESCRIPTION'),
					'target' => '.landing-form-editor-share-button',
				],
			],
		];

		$steps = $scenarios[$scenario];
		return $steps;
	}
}