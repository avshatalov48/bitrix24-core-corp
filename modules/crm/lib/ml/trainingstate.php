<?php

namespace Bitrix\Crm\Ml;

final class TrainingState
{
	public const PENDING_CREATION = "pending_creation";
	public const IDLE = "idle";
	public const GATHERING = "gathering";
	public const TRAINING = "training";
	public const EVALUATING = "evaluating";
	public const FINISHED = "finished";
	public const CANCELED = "canceled";
}
