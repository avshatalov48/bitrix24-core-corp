<?php

namespace Bitrix\Crm\Copilot\AiQualityAssessment\Entity;

final class AiQualityAssessmentItem
{
	private ?int $id;
	private int $activityType;
	private int $activityId;
	private int $assessmentSettingId;
	private int $jobId;
	private string $prompt;
	private int $assessment;
	private int $assessmentAvg;
	private bool $useInRating = true;
	private int $ratedUserId;
	private int $managerUserId;
	private int $ratedUserChatId;
	private int $managerUserChatId;

	public function __construct()
	{
		$this->activityType = AiQualityAssessmentTable::ACTIVITY_TYPE_CALL;
	}

	public static function createFromEntityFields(array $fields): self
	{
		$instance = new self();

		$instance->id = $fields['ID'] ?? null;
		$instance->activityType = $fields['ACTIVITY_TYPE'] ?? AiQualityAssessmentTable::ACTIVITY_TYPE_CALL;
		$instance->activityId = $fields['ACTIVITY_ID'];
		$instance->assessmentSettingId = $fields['ASSESSMENT_SETTING_ID'];
		$instance->jobId = $fields['JOB_ID'];
		$instance->prompt = $fields['PROMPT'] ?? '';
		$instance->assessment = $fields['ASSESSMENT'] ?? 0;
		$instance->assessmentAvg = $fields['ASSESSMENT_AVG'] ?? 0;
		$instance->useInRating = $fields['USE_IN_RATING'] ?? true;
		$instance->ratedUserId = $fields['RATED_USER_ID'] ?? 0;
		$instance->managerUserId = $fields['MANAGER_USER_ID'] ?? 0;
		$instance->ratedUserChatId = $fields['RATED_USER_CHAT_ID'] ?? 0;
		$instance->managerUserChatId = $fields['MANAGER_USER_CHAT_ID'] ?? 0;

		return $instance;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getActivityType(): int
	{
		return $this->activityType;
	}

	public function getActivityId(): int
	{
		return $this->activityId;
	}

	public function getAssessmentSettingId(): int
	{
		return $this->assessmentSettingId;
	}

	public function getJobId(): int
	{
		return $this->jobId;
	}

	public function getPrompt(): string
	{
		return $this->prompt;
	}

	public function getAssessment(): int
	{
		return $this->assessment;
	}

	public function getAssessmentAvg(): int
	{
		return $this->assessmentAvg;
	}

	public function isUseInRating(): bool
	{
		return $this->useInRating;
	}

	public function getRatedUserId(): int
	{
		return $this->ratedUserId;
	}

	public function getManagerUserId(): int
	{
		return $this->managerUserId;
	}

	public function getRatedUserChatId(): int
	{
		return $this->ratedUserChatId;
	}

	public function getManagerUserChatId(): int
	{
		return $this->managerUserChatId;
	}

	public function setUseInRating(bool $value): self
	{
		$this->useInRating = $value;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'activityType' => $this->activityType,
			'activityId' => $this->activityId,
			'assessmentSettingId' => $this->assessmentSettingId,
			'jobId' => $this->jobId,
			'prompt' => $this->prompt,
			'assessment' => $this->assessment,
			'assessmentAvg' => $this->assessmentAvg,
			'useInRating' => $this->useInRating,
			'ratedUserId' => $this->ratedUserId,
			'managerUserId' => $this->managerUserId,
			'ratedUserChatId' => $this->ratedUserChatId,
			'managerUserChatId' => $this->managerUserChatId,
		];
	}
}
