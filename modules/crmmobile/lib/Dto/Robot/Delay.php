<?php

namespace Bitrix\CrmMobile\Dto\Robot;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;

final class Delay extends Dto
{
	/** @var string */
	public $type;

	/** @var int */
	public $value;

	/** @var string */
	public $valueType;

	/** @var string */
	public $basis;

	/** @var bool */
	public $workTime;

	/** @var bool */
	public $localTime;

	/** @var string */
	public $basisName;

	public function getCasts(): array
	{
		return [
			'type' => Type::string(),
			'value' => Type::int(),
			'valueType' => Type::string(),
			'basis' => Type::string(),
			'workTime' => Type::bool(),
			'localTime' => Type::bool(),
			'basisName' => Type::string(),
		];
	}
}