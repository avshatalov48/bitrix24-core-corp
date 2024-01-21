<?php

namespace Bitrix\Crm\Counter\ProblemDetector\Recovery;

interface AsyncRecovery
{
	public const ASYNC_DONE = false;
	public const ASYNC_CONTINUE = true;

	public function supportedType(): string;

	public function planAsyncFix(): void;

	public function fixStepByStep(): bool;
}