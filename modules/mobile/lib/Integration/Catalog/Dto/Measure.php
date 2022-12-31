<?php

namespace Bitrix\Mobile\Integration\Catalog\Dto;

use Bitrix\Mobile\Dto\Dto;

final class Measure extends Dto
{
	/** @var int */
	public $id;

	/** @var string */
	public $code;

	/** @var string */
	public $name;

	/** @var boolean */
	public $isDefault = false;
}
