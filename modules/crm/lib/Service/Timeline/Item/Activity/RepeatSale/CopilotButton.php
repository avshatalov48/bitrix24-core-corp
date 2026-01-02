<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\RepeatSale;

use Bitrix\Crm\Integration\AI\Dto\RepeatSale\FillRepeatSaleTipsPayload;
use Bitrix\Crm\Integration\AI\JobRepository;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item\AssociatedEntityModel;
use Bitrix\Crm\Service\Timeline\Item\Mixin\CopilotButtonTrait;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Main\Localization\Loc;

final class CopilotButton extends Button
{
	use CopilotButtonTrait;

	public const BUTTON_TARGET_ID = 'crm-timeline-activity-repeat-sale-copilot-button';

	/** @var Result<FillRepeatSaleTipsPayload>|null */
	private readonly ?Result $operationState;
	private ?JsEvent $jsEventAction;

	public function __construct(
		private readonly Context $context,
		private readonly ?AssociatedEntityModel $model,
		private readonly int $activityId
	)
	{
		parent::__construct(
			Loc::getMessage('CRM_COMMON_COPILOT'),
			Button::TYPE_AI,
			Button::TYPE_AI
		);

		$this->operationState = JobRepository::getInstance()
			->getFillRepeatSaleTipsByActivity($this->activityId)
		;
		$this->jsEventAction = (new JsEvent('Activity:RepeatSale:LaunchCopilot'))
			->addActionParamInt('activityId', $this->activityId)
			->addActionParamInt('ownerTypeId', $this->context->getEntityTypeId())
			->addActionParamInt('ownerId', $this->context->getEntityId())
		;

		$buttonState = Layout\Button::STATE_DEFAULT;
		$buttonTooltip = Loc::getMessage('CRM_TIMELINE_ACTIVITY_REPEAT_SALE_COPILOT_TOOLTIP');
		if ($this->operationState?->isPending())
		{
			$buttonState = Layout\Button::STATE_AI_LOADING;
			$buttonTooltip = null;
		}
		elseif ($this->isDisabled())
		{
			$buttonState = Layout\Button::STATE_DISABLED;
			$buttonTooltip = $this->operationState?->isSuccess()
				? Loc::getMessage('CRM_TIMELINE_ACTIVITY_REPEAT_SALE_COPILOT_TOOLTIP_SUCCESS')
				: null
			;
		}

		$this
			->fillAILicenceAttributes()
			->setAction($buttonState === Layout\Button::STATE_DEFAULT ? $this->jsEventAction : null)
			->setState($buttonState)
			->setTooltip($buttonTooltip)
			->setProps(['id' => self::BUTTON_TARGET_ID])
			->setScopeWeb()
		;
	}

	private function isDisabled(): bool
	{
		if ($this->operationState?->isSuccess())
		{
			return true;
		}

		if ($this->operationState?->isErrorsLimitExceeded())
		{
			return true;
		}

		return false;
	}
}
