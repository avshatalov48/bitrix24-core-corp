<?php

namespace Bitrix\Crm\Ml;

class TrainingState
{
	const PENDING_CREATION = "pending_creation";
	const IDLE = "idle";
	const GATHERING = "gathering";
	const TRAINING = "training";
	const EVALUATING = "evaluating";
	const FINISHED = "finished";
	const CANCELED = "canceled";
}