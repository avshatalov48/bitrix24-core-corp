<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Util\Type;

final class StructureChecker
{
	const TYPE_INT = 8;
	const TYPE_STRING = 9;
	const TYPE_ARRAY_OF_STRING = 11;
	const TYPE_INT_POSITIVE = 10;
	const TYPE_ENUM = 12;
	const TYPE_BOOLEAN = 13;

	private $standard = array();

	public function __construct(array $standard)
	{
		$this->standard = $standard;
	}

	public function check($structure, $initial = false)
	{
		return $this->walk($structure, $this->standard, array(
			'initial' => $initial,
			'data' => $structure,
		));
	}

	private function walk($structure, array $standard, array $params = array(), $depth = 1)
	{
		if($depth >= 10) // protection
		{
			return false;
		}

		if(!is_array($structure))
		{
			$structure = array();
		}

		// remove illegal keys
		$structure = array_intersect_key($structure, $standard);

		foreach($standard as $k => $value)
		{
			if(!array_key_exists($k, $structure))
			{
				if(isset($value['INITIAL']) && $params['initial']) // pre-create initial
				{
					$structure[$k] = $this->evaluateValue($value['INITIAL']);
				}
				elseif(isset($value['DEFAULT'])) // pre-create absent fields with default values
				{
					$structure[$k] = $this->evaluateValue($value['DEFAULT']);
				}
			}
		}

		// check value of existed legal keys
		foreach($structure as $k => $value)
		{
			$sValue = $standard[$k]['VALUE'];

			// todo: refactor this, make easy-extendable map of standerd types
			if(is_array($sValue))
			{
				$value = $this->walk($value, $sValue, $params, $depth + 1);
			}
			elseif($sValue == 'integer' || $sValue == static::TYPE_INT)
			{
				$value = intval($value);
			}
			elseif($sValue == 'boolean' || $sValue == static::TYPE_BOOLEAN)
			{
				$value = !!$value;
			}
			elseif($sValue == static::TYPE_INT_POSITIVE)
			{
				$value = intval($value);
				if($value <= 0)
				{
					unset($structure[$k]);
					continue;
				}
			}
			elseif($sValue == static::TYPE_STRING)
			{
				$value = (string) $value;
			}
			elseif($sValue == static::TYPE_ARRAY_OF_STRING)
			{
				if(!is_array($value) && (string) $value != '')
				{
					if(is_callable($standard[$k]['CAST']))
					{
						$value = call_user_func_array($standard[$k]['CAST'], array($value));
					}
				}

				if(!is_array($value))
				{
					unset($structure[$k]);
					continue;
				}

				foreach($value as $m => $j)
				{
					$value[$m] = (string) $j;
				}
			}
			elseif($sValue == 'unique integer[]') // array of unique integer
			{
				if(!is_array($value))
				{
					$value = array();
				}
				$value = array_unique(array_filter($value, 'intval'));
			}
			elseif($sValue == static::TYPE_ENUM)
			{
				$allowed = $standard[$k]['VALUES'];
				if(!is_array($allowed) || empty($allowed) || !array_key_exists($value, $allowed))
				{
					unset($structure[$k]);
				}
			}
			elseif(is_callable($sValue))
			{
				$value = call_user_func_array($sValue, array($value, $params));
			}
			else
			{
				unset($structure[$k]); // we can not let unchecked alive
				continue;
			}

			$structure[$k] = $value;
		}

		return $structure;
	}

	protected function evaluateValue($value)
	{
		if(is_callable($value))
		{
			return call_user_func_array($value, array());
		}

		return $value;
	}
}