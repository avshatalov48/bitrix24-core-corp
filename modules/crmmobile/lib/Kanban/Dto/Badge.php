<?php


namespace Bitrix\CrmMobile\Kanban\Dto;


use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Type;

class Badge extends Dto
{
	/** @var string */
	public $fieldName;

	/** @var string */
	public $value;

	/** @var string */
	public $textValue;

	/** @var string */
	public $textColor;

	/** @var string */
	public $backgroundColor;

	public function getCasts(): array
	{
		return [
			'fieldName' => Type::string(),
			'value' => Type::string(),
			'textValue' => Type::string(),
			'textColor' => Type::string(),
			'backgroundColor' => Type::string(),
		];
	}
}
