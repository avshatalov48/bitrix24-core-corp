<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Copilot\CallAssessment\FillPreliminaryCallAssessments;
use Bitrix\Crm\Feature;
use Bitrix\Crm\Feature\CopilotInCallGrading;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\Crm;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class CopilotCallAssessment extends Base
{
	protected const OPTION_NAME = 'copilot-call-assessment';

	protected function canShow(): bool
	{
		if (
			$this->isUserSeenTour()
			|| !Crm::isPortalCreatedBefore(strtotime('2024-11-01'))
		)
		{
			return false;
		}

		$bitrixPaid = true;
		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
		{
			$bitrixPaid = \CBitrix24::IsLicensePaid() || \CBitrix24::IsNfrLicense();
		}

		$isAiModuleInstalled = ModuleManager::isModuleInstalled('ai');

		return (
			!FillPreliminaryCallAssessments::isWaiting()
			&& $isAiModuleInstalled
			&& Feature::enabled(CopilotInCallGrading::class)
			&& Container::getInstance()->getUserPermissions()->canReadCopilotCallAssessmentSettings()
			&& $bitrixPaid
		);
	}

	protected function getSteps(): array
	{
		return [
			[
				'id' => 'copilot-call-assessment',
				'title' => Loc::getMessage('CRM_TOUR_COPILOT_CALL_ASSESSMENT_MESSAGE_TITLE'),
				'text' => Loc::getMessage('CRM_TOUR_COPILOT_CALL_ASSESSMENT_MESSAGE_TEXT'),
				'position' => 'top',
				'target' => '#crm_control_panel_menu_crm_sales',
				'reserveTargets' => ['#crm_control_panel_menu_more_button'],
				'article' => 23240682,
			],
		];
	}

	protected function getOptions(): array
	{
		return [
			'hideTourOnMissClick' => true,
			'steps' => [
				'popup' => [
					'width' => 400,
				],
			],
		];
	}
}