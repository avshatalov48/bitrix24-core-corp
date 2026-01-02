<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;
use Bitrix\Main\Result;

final class ActionSliderParamsDto extends \Bitrix\Crm\Dto\Dto
{
	public ?int $width = null;
	public ?int $leftBoundary = null;
	public ?string $labelBgColor = null;
	public ?string $labelColor = null;
	public ?string $labelText = null;
	public ?string $title = null;

	public const ALLOWED_LABEL_BG_COLORS = [
		'aqua',
		'green',
		'orange',
		'brown',
		'pink',
		'blue',
		'grey',
		'violet',
	];

	protected function getValidators(array $fields): array
	{
		$validators = [];
		$validators[] = new \Bitrix\Crm\Dto\Validator\EnumField($this, 'labelBgColor', self::ALLOWED_LABEL_BG_COLORS);

		$validators[] = new class ($this) extends \Bitrix\Crm\Dto\Validator
		{
			public function validate(array $fields): Result
			{
				$fieldName = 'labelColor';
				$result = new Result();

				if (isset($fields[$fieldName]) && !preg_match('/^#[a-fA-F0-9]{6}$/', $fields[$fieldName]))
				{
					$result->addError($this->getWrongFieldError($fieldName, $this->dto->getName()));
				}

				return $result;
			}
		};

		return $validators;
	}
}
