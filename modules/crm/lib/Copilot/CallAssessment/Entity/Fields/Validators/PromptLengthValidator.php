<?php

namespace Bitrix\Crm\Copilot\CallAssessment\Entity\Fields\Validators;

use Bitrix\Crm\Format\TextHelper;
use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

final class PromptLengthValidator extends LengthValidator
{
	private const MIN = 100;
	private const MAX = 5000;

	public function __construct()
	{
		parent::__construct(self::MIN, self::MAX);
	}

	public function validate($value, $primary, array $row, Field $field): bool|string
	{
		$value = TextHelper::sanitizeBbCode($value, TextHelper::SANITIZE_BB_CODE_WHITE_LIST);
		$value = html_entity_decode($value);
		$value = trim($value);

		return parent::validate($value, $primary, $row, $field);
	}
}
