<?php

namespace Bitrix\CrmMobile\Dto;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;

final class DocumentField extends Dto
{
	/** @var string */
	public $id;

	/** @var string */
	public $name;

	/** @var string */
	public $type;

	/** @var string */
	public $baseType;

	/** @var string */
	public $expression;

	/** @var string */
	public $systemExpression;

	/** @var bool */
	public $multiple;

	/** @var DocumentFieldOption[]|null */
	public $options;

	public function getCasts(): array
	{
		return [
			'id' => Type::string(),
			'name' => Type::string(),
			'type' => Type::string(),
			'baseType' => Type::string(),
			'expression' => Type::string(),
			'systemExpression' => Type::string(),
			'multiple' => Type::bool(),
			'options' => Type::collection(DocumentFieldOption::class),
		];
	}
}