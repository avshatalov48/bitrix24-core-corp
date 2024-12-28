<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Crm\Feature\Category\Activities;
use Bitrix\Crm\Feature\Category\BaseCategory;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

final class CopilotInCallGrading extends BaseFeature
{
	public static function getCopilotInCallGradingTs(): int
	{
		return (int)Option::get('crm', 'b_crm_copilot_in_cal_grading_ts', 0);
	}

	public static function setCopilotInCallGradingTs(int $value): void
	{
		Option::set('crm', 'b_crm_copilot_in_cal_grading_ts', $value);
	}

	public function getName(): string
	{
		return Loc::getMessage('COPILOT_IN_CALL_GRADING_NAME');
	}

	public function getCategory(): BaseCategory
	{
		return Activities::getInstance();
	}

	protected function getOptionName(): string
	{
		return 'COPILOT_IN_CALL_GRADING_ALLOWED';
	}

	protected function getEnabledValue(): mixed
	{
		return true;
	}

	public function enable(): void
	{
		if (!$this->isEnabled())
		{
			parent::enable();

			Option::set('crm', 'waiting_call_assessment_rules_filling', 'Y');

			\CAgent::AddAgent(
				'Bitrix\Crm\Agent\Copilot\CallAssessmentsPrefillAgent::run();',
				'crm'
			);

			if (self::getCopilotInCallGradingTs())
			{
				return;
			}

			self::setCopilotInCallGradingTs(time());

			try
			{
				$defaultPermissions = Json::decode(Option::get('crm', 'default_permissions'));
			}
			catch (ArgumentException)
			{
				$defaultPermissions = [];
			}

			if (!is_array($defaultPermissions))
			{
				$defaultPermissions = [];
			}

			$defaultPermissions[] = [
				'permissionClass' => 'Bitrix\\Crm\\Security\\Role\\Manage\\Permissions\\CopilotCallAssessment\\Write',
				'permissionType' => 'WRITE',
				'attr' => 'X',
				'roleGroups' => ['CRM'], // also can be AUTOMATED_SOLUTION, CRM_BUTTON, CRM_WEBFORM
			];

			$defaultPermissions[] = [
				'permissionClass' => 'Bitrix\\Crm\\Security\\Role\\Manage\\Permissions\\CopilotCallAssessment\\Read',
				'permissionType' => 'READ',
				'attr' => 'X',
				'roleGroups' => ['CRM'],
			];

			Option::set('crm', 'default_permissions', Json::encode($defaultPermissions));

			\CAgent::AddAgent(
				'Bitrix\Crm\Agent\Security\ApproveCustomPermsToExistRoleAgent::run();',
				'crm',
				'N',
				1
			);
		}
	}
}
