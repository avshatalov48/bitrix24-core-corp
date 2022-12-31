<?php


namespace Bitrix\CrmMobile\Kanban\Dto;


use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Type;

class Field extends Dto
{
	/** @var string */
	public $name;

	/** @var string */
	public $title;

	/** @var string */
	public $type;

	/** @var bool */
	public $multiple;

	/** @var mixed */
	public $value;

	/** @var array|null */
	public $config;

	/** @var array|null */
	public $params;

	public function getCasts(): array
	{
		return [
			'name' => Type::string(),
			'title' => Type::string(),
			'type' => Type::string(),
			'multiple' => Type::bool(),
		];
	}
}
