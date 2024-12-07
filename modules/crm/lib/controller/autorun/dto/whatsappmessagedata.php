<?php

namespace Bitrix\Crm\Controller\Autorun\Dto;

use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\RequiredField;

final class WhatsappMessageData extends PreparedData
{
	public ?string $messageBody;

	public ?string $messageTemplate;

	public ?string $fromPhone = null;

	protected function getValidators(array $fields): array
	{
		$validators = parent::getValidators($fields);

		$validators[] = new RequiredField($this, 'messageBody');
		$validators[] = new NotEmptyField($this, 'messageBody');

		$validators[] = new RequiredField($this, 'messageTemplate');
		$validators[] = new NotEmptyField($this, 'messageTemplate');

		return $validators;
	}
}