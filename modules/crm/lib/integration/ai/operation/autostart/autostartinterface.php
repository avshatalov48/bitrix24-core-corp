<?php

namespace Bitrix\Crm\Integration\AI\Operation\Autostart;

interface AutoStartInterface
{
	public function shouldAutostart(int $operationType, int $callDirection): bool;

	public function isAutostartTranscriptionOnlyOnFirstCallWithRecording(): bool;
}
