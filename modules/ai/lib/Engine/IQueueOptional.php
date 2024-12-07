<?php

namespace Bitrix\AI\Engine;

interface IQueueOptional
{
	/**
	 * Makes request to AI Engine throw the queue.
	 *
	 * @return void
	 */
	public function completionsInQueue(): void;
}
