<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Call;

use Bitrix\AI\Config;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Operation\OperationState;
use Bitrix\Crm\Integration\AI\Operation\Scenario;
use Bitrix\Crm\Integration\AI\SuitableAudiosChecker;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item\AssociatedEntityModel;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItem;
use Bitrix\Crm\Settings\Crm;
use Bitrix\Main\Localization\Loc;

final class CopilotButton extends Button
{
	private readonly OperationState $operationState;
	private ?JsEvent $jsEventAction;

	public function __construct(
		private readonly Context $context,
		private readonly ?AssociatedEntityModel $model,
		private readonly int $activityId,
		private readonly bool $isMultiScenario
	)
	{
		parent::__construct(
			Loc::getMessage('CRM_COMMON_COPILOT'),
			Button::TYPE_AI,
			Button::TYPE_AI
		);

		$this->operationState = new OperationState(
			$this->activityId,
			$this->context->getEntityTypeId(),
			$this->context->getEntityId(),
		);
		$this->jsEventAction = (new JsEvent('Call:LaunchCopilot'))
			->addActionParamInt('activityId', $this->activityId)
			->addActionParamInt('ownerTypeId', $this->context->getEntityTypeId())
			->addActionParamInt('ownerId', $this->context->getEntityId())
			->addActionParamString('scenario', $this->isMultiScenario ? Scenario::FULL_SCENARIO : Scenario::FILL_FIELDS_SCENARIO)
			->addActionParamBoolean('isCopilotBannerNeedShow', $this->isCopilotBannerNeedShow())
		;

		$buttonState = Layout\Button::STATE_DEFAULT;
		$buttonTooltip = null;

		if ($this->operationState->isLaunchOperationsPending())
		{
			$buttonState = Layout\Button::STATE_AI_LOADING;
		}
		elseif ($this->isDisabled())
		{
			$buttonState = Layout\Button::STATE_DISABLED;
			$buttonTooltip = $this->buildTooltip();
		}

		$this
			->fillAILicenceAttributes()
			->setAction($buttonState === Layout\Button::STATE_DEFAULT ? $this->jsEventAction : null)
			->setState($buttonState)
			->setMenuItems($this->buildMenu())
			->setTooltip($buttonTooltip)
			->setScopeWeb()
		;
	}

	private function fillAILicenceAttributes(): self
	{
		$this->props = [
			'data-activity-id' => $this->activityId,
		];

		if (!AIManager::isAILicenceAccepted($this->context->getUserId()))
		{
			if (Crm::isBox())
			{
				$this->jsEventAction->addActionParamBoolean('isCopilotAgreementNeedShow', true);
			}
			else if (!$this->context->getUserPermissions()->isAdmin())
			{
				$this->props = [
					'data-bitrix24-license-feature' => AIManager::AI_LICENCE_FEATURE_NAME,
				];
			}
		}

		return $this;
	}

	private function buildMenu(): array
	{
		if (!$this->isMultiScenario)
		{
			return [];
		}

		$fillFieldsScenarioState = Layout\Button::STATE_DEFAULT;
		$callScoringScenarioState = Layout\Button::STATE_DEFAULT;
		$fillFieldsScenarioAction = (clone $this->jsEventAction)
			->addActionParamString('scenario', Scenario::FILL_FIELDS_SCENARIO)
		;
		$callScoringScenarioAction  = (clone $this->jsEventAction)
			->addActionParamString('scenario', Scenario::CALL_SCORING_SCENARIO)
		;

		if ($this->operationState->isFillFieldsScenarioPending())
		{
			$fillFieldsScenarioState = Layout\Button::STATE_AI_LOADING;
			$fillFieldsScenarioAction = null;
		}
		elseif (
			$this->operationState->isFillFieldsScenarioSuccess()
			|| $this->operationState->isFillFieldsScenarioErrorsLimitExceeded()
		)
		{
			$fillFieldsScenarioState = Layout\Button::STATE_AI_SUCCESS;
			$fillFieldsScenarioAction = null;
		}

		if ($this->operationState->isCallScoringScenarioPending())
		{
			$callScoringScenarioState = Layout\Button::STATE_AI_LOADING;
			$callScoringScenarioAction  = null;
		}
		elseif (
			$this->operationState->isCallScoringScenarioSuccess()
			|| $this->operationState->isCallScoringScenarioErrorsLimitExceeded()
		)
		{
			$callScoringScenarioState = Layout\Button::STATE_AI_SUCCESS;
			$callScoringScenarioAction  = null;
		}

		if (!$this->isAudiosValid())
		{
			$fillFieldsScenarioState = Layout\Button::STATE_DISABLED;
			$fillFieldsScenarioAction = null;
			$callScoringScenarioState = Layout\Button::STATE_DISABLED;
			$callScoringScenarioAction  = null;
		}

		if (
			$fillFieldsScenarioState === Layout\Button::STATE_DEFAULT
			&& !Scenario::isEnabledScenario(Scenario::FILL_FIELDS_SCENARIO)
		)
		{
			$fillFieldsScenarioState = Layout\Button::STATE_LOCKED;
		}

		if (
			$callScoringScenarioState === Layout\Button::STATE_DEFAULT
			&& !Scenario::isEnabledScenario(Scenario::CALL_SCORING_SCENARIO)
		)
		{
			$callScoringScenarioState = Layout\Button::STATE_LOCKED;
		}

		$result['fillFields'] = (new MenuItem(Loc::getMessage('CRM_TIMELINE_BUTTON_COPILOT_MENU_FILL_FIELDS')))
			->setAction($fillFieldsScenarioAction)
			->setState($fillFieldsScenarioState)
			->setScopeWeb()
		;

		$result['callScoring'] = (new MenuItem(Loc::getMessage('CRM_TIMELINE_BUTTON_COPILOT_MENU_CALL_SCORING')))
			->setAction($callScoringScenarioAction)
			->setState($callScoringScenarioState)
			->setScopeWeb()
		;

		return $result;
	}

	private function buildTooltip(): ?string
	{
		if (!$this->isAudiosValid())
		{
			return Loc::getMessage('CRM_TIMELINE_ITEM_COPILOT_ERROR_TOOLTIP');
		}

		if (
			$this->operationState->isFillFieldsScenarioSuccess()
			|| $this->operationState->isCallScoringScenarioSuccess()
		)
		{
			return Loc::getMessage('CRM_TIMELINE_BUTTON_TIP_COPILOT');
		}

		return null;
	}

	private function isDisabled(): bool
	{
		if (!$this->isAudiosValid())
		{
			return true;
		}

		$isFillFieldsScenarioSuccess = $this->operationState->isFillFieldsScenarioSuccess();
		$isCallScoringScenarioSuccess = $this->operationState->isCallScoringScenarioSuccess();
		$isAllSuccess = $this->isMultiScenario
			? $isFillFieldsScenarioSuccess && $isCallScoringScenarioSuccess
			: $isFillFieldsScenarioSuccess;
		if ($isAllSuccess)
		{
			return true;
		}

		if (!Scenario::isEnabledScenario(Scenario::FULL_SCENARIO))
		{
			return true;
		}

		if (
			$isCallScoringScenarioSuccess
			&& !Scenario::isEnabledScenario(Scenario::FILL_FIELDS_SCENARIO)
		)
		{
			return true;
		}

		if (
			$isFillFieldsScenarioSuccess
			&& !Scenario::isEnabledScenario(Scenario::CALL_SCORING_SCENARIO)
		)
		{
			return true;
		}

		$isFillFieldsScenarioError = $this->operationState->isFillFieldsScenarioErrorsLimitExceeded();
		$isCallScoringScenarioError = $this->operationState->isCallScoringScenarioErrorsLimitExceeded();
		$isAllError = $this->isMultiScenario
			? $isFillFieldsScenarioError && $isCallScoringScenarioError
			: $isFillFieldsScenarioError
		;
		if ($isAllError)
		{
			return true;
		}

		return ($isFillFieldsScenarioSuccess && $isCallScoringScenarioError)
			|| ($isCallScoringScenarioSuccess && $isFillFieldsScenarioError)
		;
	}

	private function isAudiosValid(): bool
	{
		static $isAudiosValidList = [];

		$originId = (string)$this->model?->get('ORIGIN_ID');

		if (!isset($isAudiosValidList[$originId]))
		{
			$audiosCheckResult = (new SuitableAudiosChecker(
				$originId,
				(int)$this->model?->get('STORAGE_TYPE_ID'),
				(string)$this->model?->get('STORAGE_ELEMENT_IDS')
			))->run();

			$isSuccess = $audiosCheckResult->isSuccess();
			$isAudiosValidList[$originId] = $isSuccess;
		}

		return $isAudiosValidList[$originId];
	}

	private function isCopilotBannerNeedShow(): bool
	{
		return Config::getPersonalValue('first_launch') !== 'N';
	}
}
