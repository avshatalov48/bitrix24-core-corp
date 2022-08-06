<?php

namespace Bitrix\Crm\Requisite\Conversion;

use Bitrix\Main\Type\DateTime;

class Logger
{
	const TYPE_INFO = 'INFO';
	const TYPE_WARNING = 'WARNING';
	const TYPE_ERROR = 'ERROR';

	public static function log(array $params = [])
	{
		list($msec, $sec) = explode(' ', microtime());
		$msec = (int)mb_substr((string)round((float)$msec, 6), 2);
		$data = [
			'CREATED' => DateTime::createFromTimestamp($sec),
			'MSEC' => $msec,
			'TYPE' => (isset($params['TYPE']) && in_array($params['TYPE'], self::getTypes())) ?
				$params['TYPE'] : self::TYPE_INFO,
			'TAG' => isset($params['TAG']) && is_string($params['TAG']) ? $params['TAG'] : null,
			'MESSAGE' => (isset($params['MESSAGE']) && is_string($params['MESSAGE']) && $params['MESSAGE'] !== '') ?
				$params['MESSAGE'] : null
		];

		$GLOBALS['DB']->StartUsingMasterOnly();

		$result = LogTable::add($data);

		$GLOBALS['DB']->StopUsingMasterOnly();

		return $result;
	}

	public static function clearLog()
	{
		LogTable::deleteAll();
	}

	/**
	 * Returns logger types
	 * @return array
	 */
	public static function getTypes()
	{
		static $types = null;
		if ($types !== null)
		{
			return $types;
		}

		$types = array();
		$refClass = new \ReflectionClass(__CLASS__);
		foreach ($refClass->getConstants() as $name => $value)
		{
			if (mb_substr($name, 0, 4) === 'TYPE')
			{
				$types[] = $value;
			}
		}

		return $types;
	}
}
