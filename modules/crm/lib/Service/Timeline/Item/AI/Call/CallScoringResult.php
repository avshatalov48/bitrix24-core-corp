<?php

namespace Bitrix\Crm\Service\Timeline\Item\AI\Call;

use Bitrix\Crm\Copilot\AiQualityAssessment\Controller\AiQualityAssessmentController;
use Bitrix\Crm\Copilot\AiQualityAssessment\Entity\AiQualityAssessmentTable;
use Bitrix\Crm\Integration\AI\Dto\ScoreCallPayload;
use Bitrix\Crm\Integration\AI\JobRepository;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Common;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;

final class CallScoringResult extends Base
{
	protected function getAICallTypeId(): string
	{
		return 'CallScoringResult';
	}

	protected function getAdditionalIconCode(): string
	{
		return 'ai-scoring';
	}

	protected function getOpenButtonTitle(): string
	{
		return Loc::getMessage('CRM_TIMELINE_ACTIVITY_AI_CALL_SCORING_OPEN_BTN');
	}

	protected function getOpenAction(): ?Action
	{
		$communication = $this->getAssociatedEntityModel()?->get('COMMUNICATION') ?? [];
		$userData = $this->getResponsibleUser();
		$jobId = $this->getModel()->getSettings()['JOB_ID'] ?? null;
		$createdTimestamp = (new DateTime($this->getAssociatedEntityModel()->get('CREATED')))->getTimestamp();

		return (new Action\JsEvent('CallScoringResult:Open'))
			->addActionParamInt('activityId', $this->getActivityId())
			->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
			->addActionParamInt('ownerId', $this->getContext()->getEntityId())
			->addActionParamString('clientDetailUrl', isset($communication['SHOW_URL']) ? new Uri($communication['SHOW_URL']) : null)
			->addActionParamString('clientFullName', $communication['TITLE'] ?? '')
			->addActionParamInt('activityCreated', $createdTimestamp)
			->addActionParamString('userPhotoUrl', $userData['PHOTO_URL'] ?? '')
			->addActionParamInt('jobId', $jobId)
		;
	}

	protected function getJobResult(): ?Result
	{
		$activityId = $this->getAssociatedEntityModel()?->get('ID');
		if ($activityId === null)
		{
			return null;
		}

		return JobRepository::getInstance()->getCallScoringResult(
			$activityId,
			$this->getModel()->getSettings()['JOB_ID'] ?? null
		);
	}

	protected function buildJobLanguageBlock(): ?ContentBlock
	{
		return null;
	}

	public function getIconCode(): ?string
	{
		return Common\Icon::AI_COPILOT;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ACTIVITY_AI_CALL_SCORING_RESULT_TITLE');
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$block = $this->buildCallScoringBlock();
		if (isset($block))
		{
			$result['callScoring'] = $block;
		}

		return $result;
	}

	public function getButtons(): ?array
	{
		$buttons = parent::getButtons();

		// @todo: not implemented yet
		/** @var Result<ScoreCallPayload>|null $job */
		/*$job = $this->getJobResult();
		if ($job?->getJobId())
		{
			$result = AiQualityAssessmentController::getInstance()->getList([
				'select' => ['ASSESSMENT_SETTING_ID'],
				'filter' => [
					'=JOB_ID' => $job?->getJobId(),
				],
				'limit' => 1,
			])->current();

			if ($result?->getAssessmentSettingId())
			{
				$buttons['editPromptButton'] =
					(new Button(Loc::getMessage('CRM_TIMELINE_ACTIVITY_AI_CALL_SCORING_OPEN_SETTINGS_BTN'), Button::TYPE_SECONDARY))
						->setAction(
							(new Action\JsEvent('CallScoringResult:EditPrompt'))
								->addActionParamInt('assessmentSettingId', $result?->getAssessmentSettingId())
								->setAnimation(Action\Animation::disableBlock())
						)
						->setScopeWeb()
				;
			}
		}
		*/

		// click to button - show hint
		$buttons['editPromptButton'] =
			(new Button(Loc::getMessage('CRM_TIMELINE_ACTIVITY_AI_CALL_SCORING_OPEN_SETTINGS_BTN'), Button::TYPE_SECONDARY))
				->setAction(
					(new Action\JsEvent('CallScoringResult:EditPrompt'))
						->setAnimation(Action\Animation::disableBlock())
				)
				->setScopeWeb()
		;

		return $buttons;
	}

	public function getTags(): ?array
	{
		$activityId = $this->getAssociatedEntityModel()?->get('ID');
		if ($activityId === null)
		{
			return null;
		}

		$scoringResult = AiQualityAssessmentController::getInstance()->getByActivityIdAndJobId(
			$activityId,
			$this->getModel()->getSettings()['JOB_ID'] ?? null
		);
		if (!$scoringResult)
		{
			return null;
		}

		$numberOfScore = AiQualityAssessmentController::getInstance()->getCountByFilter([
			'=ACTIVITY_ID' => $activityId,
			'=ACTIVITY_TYPE' => AiQualityAssessmentTable::ACTIVITY_TYPE_CALL,
			'=RATED_USER_ID' => $this->getAssociatedEntityModel()?->get('RESPONSIBLE_ID'),
		]);

		if ($scoringResult['USE_IN_RATING'] === 'Y' && $numberOfScore > 1)
		{
			return [
				'use_in_rating' => new Tag(
					Loc::getMessage('CRM_TIMELINE_ACTIVITY_AI_CALL_SCORING_TAG_USE_IN_RATING'),
					Tag::TYPE_PRIMARY
				),
			];
		}

		return null;
	}

	private function buildCallScoringBlock(): ?ContentBlock
	{
		$activityId = $this->getAssociatedEntityModel()?->get('ID');
		if ($activityId === null)
		{
			return null;
		}

		$result = AiQualityAssessmentController::getInstance()
			->getByActivityIdAndJobId($activityId, $this->getModel()->getSettings()['JOB_ID'] ?? null)
		;
		if ($result)
		{
			$userData = $this->getResponsibleUser();

			return (new ContentBlock\Copilot\CallScoring())
				->setUserName($userData['FORMATTED_NAME'] ?? '')
				->setUserAvatarUrl($userData['PHOTO_URL'] ?? '')
				->setScoringData($result)
				->setAction($this->getOpenAction())
			;
		}

		return null;
	}

	private function getResponsibleUser(): array
	{
		return $this->getUserData($this->getAssociatedEntityModel()?->get('RESPONSIBLE_ID'));
	}
}
