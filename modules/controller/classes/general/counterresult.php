<?php

class CControllerCounterResult extends CDBResult
{
	public function Fetch()
	{
		$res = parent::Fetch();
		if ($res)
		{
			switch ($res['COUNTER_TYPE'])
			{
				case 'I':
					$res['VALUE'] = (string)$res['VALUE_INT'];
					break;
				case 'F':
					$res['VALUE'] = (string)$res['VALUE_FLOAT'];
					break;
				case 'D':
					$res['VALUE'] = (string)$res['VALUE_DATE'];
					break;
				default:
					$res['VALUE'] = $res['VALUE_STRING'];
					break;
			}
			unset($res['VALUE_INT']);
			unset($res['VALUE_INT']);
			unset($res['VALUE_FLOAT']);
			unset($res['VALUE_DATE']);
			unset($res['VALUE_STRING']);
			$res['DISPLAY_VALUE'] = CControllerCounter::FormatValue($res['VALUE'], $res['COUNTER_FORMAT']);
		}
		return $res;
	}
}
