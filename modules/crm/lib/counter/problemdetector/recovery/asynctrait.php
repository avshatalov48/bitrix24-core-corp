<?php

namespace Bitrix\Crm\Counter\ProblemDetector\Recovery;

trait AsyncTrait
{
	public function planAsyncFix(): void
	{
		\CAgent::AddAgent(
			static::class . '::run();',
			'crm',
			'N',
			60
		);
	}

	public static function run()
	{
		$result = (new static())->fixStepByStep();
		return $result ? static::class.'::run();' : '';
	}

	protected function checkIfDone(array $badRecords, int $limit): bool
	{
		return count($badRecords) < $limit
			? AsyncRecovery::ASYNC_DONE
			: AsyncRecovery::ASYNC_CONTINUE;
	}
}