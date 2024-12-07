<?php

namespace Bitrix\Crm\MessageSender\MassWhatsApp;

final class TplCreatorResult
{
	public function __construct(
		public readonly ?string $messageBody = null,
		public readonly ?string $preparedTemplate = null,
	)
	{
	}
}