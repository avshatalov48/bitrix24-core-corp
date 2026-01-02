<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config;

class UpdateConfig
{
	private int $userId;
	private bool $needCorrectDatePlan;
	private bool $checkFileRights;
	private bool $correctDatePlanDependent;
	private bool $needAutoclose;
	private bool $skipNotifications;
	private bool $skipRecount;
	private array $byPassParameters;
	private bool $skipComments;
	private bool $skipPush;
	private bool $skipBP;
	private ?string $eventGuid;
	private RuntimeData $runtime;

	public function __construct(
		int         $userId,
		bool        $needCorrectDatePlan = true,
		bool        $checkFileRights = false,
		bool        $correctDatePlanDependent = true,
		bool        $needAutoclose = true,
		bool        $skipNotifications = false,
		bool        $skipRecount = false,
		array       $byPassParameters = [],
		bool        $skipComments = false,
		bool        $skipPush = false,
		bool        $skipBP = false,
		?string     $eventGuid = null,
		RuntimeData $runtime = new RuntimeData()
	)
	{
		$this->userId = $userId;
		$this->needCorrectDatePlan = $needCorrectDatePlan;
		$this->checkFileRights = $checkFileRights;
		$this->correctDatePlanDependent = $correctDatePlanDependent;
		$this->needAutoclose = $needAutoclose;
		$this->skipNotifications = $skipNotifications;
		$this->skipRecount = $skipRecount;
		$this->byPassParameters = $byPassParameters;
		$this->skipComments = $skipComments;
		$this->skipPush = $skipPush;
		$this->skipBP = $skipBP;
		$this->eventGuid = $eventGuid;
		$this->runtime = $runtime;
	}

	public static function createFromArray(
		int $userId,
		array $parameters = [
			'CORRECT_DATE_PLAN_DEPENDENT_TASKS' => true,
			'CORRECT_DATE_PLAN' => true,
			'THROTTLE_MESSAGES' => false
		]
	): static
	{
		$config = new static($userId);

		if (!isset($parameters['THROTTLE_MESSAGES']))
		{
			$parameters['THROTTLE_MESSAGES'] = false;
		}

		$config->setByPassParameters($parameters);

		if (isset($parameters['META::EVENT_GUID']))
		{
			$config->setEventGuid($parameters['META::EVENT_GUID']);
		}

		if (
			isset($parameters['CORRECT_DATE_PLAN'])
			&& (
				$parameters['CORRECT_DATE_PLAN'] === false
				|| $parameters['CORRECT_DATE_PLAN'] === 'N'
			)
		)
		{
			$config->setNeedCorrectDatePlan(false);
		}

		if (
			isset($parameters['CORRECT_DATE_PLAN_DEPENDENT_TASKS'])
			&& (
				$parameters['CORRECT_DATE_PLAN_DEPENDENT_TASKS'] === false
				|| $parameters['CORRECT_DATE_PLAN_DEPENDENT_TASKS'] === 'N'
			)
		)
		{
			$config->setCorrectDatePlanDependent(false);
		}

		if (
			isset($parameters['AUTO_CLOSE'])
			&& $parameters['AUTO_CLOSE'] === false
		)
		{
			$config->setNeedAutoclose(false);
		}

		if (
			isset($parameters['SKIP_NOTIFICATION'])
			&& $parameters['SKIP_NOTIFICATION']
		)
		{
			$config->setSkipNotifications(true);
		}

		if (isset($parameters['FIELDS_FOR_COMMENTS']))
		{
			$config->setSkipComments(true);
		}

		if (
			isset($parameters['SEND_UPDATE_PULL_EVENT'])
			&& !$parameters['SEND_UPDATE_PULL_EVENT']
		)
		{
			$config->setSkipPush(true);
		}

		return $config;
	}

	public function isSkipBP(): bool
	{
		return $this->skipBP;
	}

	public function isNeedCorrectDatePlan(): bool
	{
		return $this->needCorrectDatePlan;
	}

	public function isCheckFileRights(): bool
	{
		return $this->checkFileRights;
	}

	public function isCorrectDatePlanDependent(): bool
	{
		return $this->correctDatePlanDependent;
	}

	public function isNeedAutoclose(): bool
	{
		return $this->needAutoclose;
	}

	public function isSkipNotifications(): bool
	{
		return $this->skipNotifications;
	}

	public function isSkipRecount(): bool
	{
		return $this->skipRecount;
	}

	public function getByPassParameters(): array
	{
		return $this->byPassParameters;
	}

	public function isSkipComments(): bool
	{
		return $this->skipComments;
	}

	public function isSkipPush(): bool
	{
		return $this->skipPush;
	}

	public function getEventGuid(): ?string
	{
		return $this->eventGuid;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getRuntime(): RuntimeData
	{
		return $this->runtime;
	}

	public function setNeedCorrectDatePlan(bool $needCorrectDatePlan): static
	{
		$this->needCorrectDatePlan = $needCorrectDatePlan;

		return $this;
	}

	public function setCheckFileRights(bool $checkFileRights): static
	{
		$this->checkFileRights = $checkFileRights;

		return $this;
	}

	public function setCorrectDatePlanDependent(bool $correctDatePlanDependent): static
	{
		$this->correctDatePlanDependent = $correctDatePlanDependent;

		return $this;
	}

	public function setNeedAutoclose(bool $needAutoclose): static
	{
		$this->needAutoclose = $needAutoclose;

		return $this;
	}

	public function setSkipNotifications(bool $skipNotifications): static
	{
		$this->skipNotifications = $skipNotifications;

		return $this;
	}

	public function setSkipRecount(bool $skipRecount): static
	{
		$this->skipRecount = $skipRecount;

		return $this;
	}

	public function setByPassParameters(array $byPassParameters): static
	{
		$this->byPassParameters = $byPassParameters;

		return $this;
	}

	public function setSkipComments(bool $skipComments): static
	{
		$this->skipComments = $skipComments;

		return $this;
	}

	public function setSkipPush(bool $skipPush): static
	{
		$this->skipPush = $skipPush;

		return $this;
	}

	public function setEventGuid(?string $eventGuid): static
	{
		$this->eventGuid = $eventGuid;

		return $this;
	}

	public function setUserId(int $userId): static
	{
		$this->userId = $userId;

		return $this;
	}
}