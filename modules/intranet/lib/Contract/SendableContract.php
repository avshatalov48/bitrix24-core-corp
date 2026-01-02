<?php

namespace Bitrix\Intranet\Contract;

interface SendableContract
{
	public function send(): void;

	public function sendImmediately(): void;
}