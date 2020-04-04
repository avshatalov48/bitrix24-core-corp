<?php

namespace Bitrix\Tasks\Util\Error;

/**
 * Class Filter
 * @package Bitrix\Tasks\Util\Error
 *
 * @deprecated This functionality was experimental but found useless
 */

class Filter
{
	/**
	 * @param array $values
	 * @return array
	 */
	public function process(array $values = array())
	{
		$result = array();

		foreach($values as $value)
		{
			$value = $value->toArray();

			$error = array(
				'CODE' => $value['CODE'],
				'MESSAGE' => $value['MESSAGE'],
				'TYPE' => $value['TYPE']
			);

			if(!empty($value['DATA']))
			{
				$error['DATA'] = $value['DATA'];
			}

			$result[] = $error;
		}

		return $result;
	}
}