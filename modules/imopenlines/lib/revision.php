<?php
namespace Bitrix\Imopenlines;

class Revision
{
	const WEB = 1;

	const MOBILE = 1;

	const REST = 2;

	public static function getWeb()
	{
		return static::WEB;
	}

	public static function getMobile()
	{
		return static::MOBILE;
	}

	public static function getRest()
	{
		return static::REST;
	}

	public static function get()
	{
		return [
			'rest' => static::getRest(),
			'web' => static::getWeb(),
			'mobile' => static::getMobile(),
		];
	}
}