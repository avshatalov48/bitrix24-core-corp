<?php

namespace Bitrix\Crm\Order\SendingChannels;

abstract class AbstractChannel
{
	private $name;

	public function __construct($name = '')
	{
		$this->name = $name;
	}

	public static function getType()
	{
		$class = new \ReflectionClass(static::class);

		return mb_strtolower($class->getShortName());
	}

	public function getName()
	{
		return $this->name;
	}
}