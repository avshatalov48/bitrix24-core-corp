<?php

namespace Bitrix\Crm\MessageSender\MassWhatsApp;

class TemplateParams
{
	public function __construct(
		public readonly string $messageBody,
		public readonly string $messageTemplate,
		public readonly ?string $fromPhone,
	)
	{
	}
}