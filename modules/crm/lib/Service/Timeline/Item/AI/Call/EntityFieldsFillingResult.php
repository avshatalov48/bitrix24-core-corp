<?php

namespace Bitrix\Crm\Service\Timeline\Item\AI\Call;

use Bitrix\Crm\Integration\AI\JobRepository;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

final class EntityFieldsFillingResult extends Base
{
	protected function getAICallTypeId(): string
	{
		return 'EntityFieldsFillingResult';
	}

	protected function getAdditionalIconCode(): string
	{
		return 'circle-check';
	}

	protected function getOpenButtonTitle(): string
	{
		return Loc::getMessage('CRM_TIMELINE_ACTIVITY_AI_FILLING_SUCCESS_OPEN_BTN');
	}

	public function getButtons(): ?array
	{
		$buttons = parent::getButtons();

		$buttons['sendFeedback'] =
			(new Button(Loc::getMessage('CRM_TIMELINE_ACTIVITY_AI_FILLING_SEND_FEEDBACK'), Button::TYPE_SECONDARY))
				->setAction(
					(new JsEvent('EntityFieldsFillingResult:OpenSendFeedbackPopup'))
						->addActionParamInt('mergeUuid', $this->getJobResult()?->getJobId())
						->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
						// analytic metrics
						->addActionParamInt('activityId', $this->getActivityId())
						->addActionParamString(
							'activityDirection',
							mb_strtolower(\CCrmActivityDirection::ResolveName($this->getAssociatedEntityModel()?->get('DIRECTION')))
						)
						->setAnimation(Action\Animation::disableBlock())
				)
				->setScopeWeb()
		;

		return $buttons;
	}

	protected function getOpenAction(): ?Action
	{
		$jobResult = $this->getJobResult();
		if ($jobResult?->isSuccess() === true)
		{
			return (new JsEvent('EntityFieldsFillingResult:OpenAiFormFill'))
				->addActionParamInt('mergeUuid', $jobResult?->getJobId())
				->addActionParamInt('activityId', $this->getActivityId())
				->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
				->addActionParamInt('ownerId', $this->getContext()->getEntityId())
				->addActionParamString('languageTitle', $this->getJobResultLanguageTitle())
				// for analytics
				->addActionParamString(
					'activityDirection',
					mb_strtolower(\CCrmActivityDirection::ResolveName($this->getAssociatedEntityModel()?->get('DIRECTION')))
				)
			;
		}

		return null;
	}

	public function getTitle(): ?string
	{
		$ownerTypeId = $this->getContext()->getEntityTypeId();
		$titleCode = 'CRM_TIMELINE_ACTIVITY_AI_FILLING_SUCCESS_RESULT';
		if ($ownerTypeId === CCrmOwnerType::Lead)
		{
			$titleCode = 'CRM_TIMELINE_ACTIVITY_AI_FILLING_SUCCESS_RESULT_LEAD';
		}
		elseif ($ownerTypeId === CCrmOwnerType::Deal)
		{
			$titleCode = 'CRM_TIMELINE_ACTIVITY_AI_FILLING_SUCCESS_RESULT_DEAL';
		}

		return Loc::getMessage(
			$this->isFieldsFillingWrong()
				? 'CRM_TIMELINE_ACTIVITY_AI_FILLING_WARNING_RESULT'
				: $titleCode
		);
	}

	public function getTags(): ?array
	{
		$statusTag = $this->isFieldsFillingWrong()
			? new Tag(Loc::getMessage('CRM_TIMELINE_ACTIVITY_AI_FILLING_TAG_WARNING'), Tag::TYPE_WARNING)
			: new Tag(Loc::getMessage('CRM_TIMELINE_ACTIVITY_AI_FILLING_TAG_SUCCESS'), Tag::TYPE_SUCCESS);

		return [
			'filling_result' => $statusTag,
		];
	}

	private function isFieldsFillingWrong(): bool
	{
		$result = $this->getJobResult();
		if (!$result)
		{
			return false;
		}

		return $result->getOperationStatus() === Result::OPERATION_STATUS_CONFLICT;
	}

	protected function getJobResult(): ?Result
	{
		$activityId = $this->getAssociatedEntityModel()?->get('ID');
		if (!isset($activityId))
		{
			return null;
		}

		return JobRepository::getInstance()
			->getFillItemFieldsFromCallTranscriptionResult(
				new ItemIdentifier($this->getContext()->getEntityTypeId(), $this->getContext()->getEntityId()),
				$activityId
			)
		;
	}
}
