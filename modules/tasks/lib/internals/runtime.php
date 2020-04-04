<?
/**
 * @internal
 * @access private
 */

namespace Bitrix\Tasks\Internals;

use Bitrix\Main\Entity;
use Bitrix\Tasks\Util\Assert;
use Bitrix\Main\Entity\Query;

abstract class Runtime
{
	protected static function checkParameters(array $parameters = array())
	{
		if(!array_key_exists('USER_ID', $parameters))
		{
			$parameters['USER_ID'] = \Bitrix\Tasks\Util\User::getId();
		}

		$parameters['USER_ID'] = intval($parameters['USER_ID']);

		return $parameters;
	}

	public static function getRecordCount(array $parameters = array())
	{
		$result = array();

		if(!array_key_exists('NAME', $parameters))
		{
			$parameters['NAME'] = 'RECORD_COUNT';
		}

		$parameters = static::checkParameters($parameters);
		$result[] = new Entity\ExpressionField(
			$parameters['NAME'],
			'COUNT(*)',
			array()
		);

		return array('runtime' => $result);
	}

	public static function apply($query, array $runtimes)
	{
		$isArray = is_array($query);
		$isQuery = $query instanceOf Query;

		foreach($runtimes as $runtime)
		{
			if(array_key_exists('runtime', $runtime) && is_array($runtime['runtime']) && !empty($runtime['runtime']))
			{
				foreach($runtime['runtime'] as $k => $field)
				{
					// $field could be array or instance of Field
					$fieldIsObject = is_subclass_of($field, '\Bitrix\Main\Entity\Field');

					if($fieldIsObject)
					{
						$field = clone $field;
					}

					if($isArray)
					{
						if(is_array($field))
						{
							$query['runtime'][$k] = $field;
						}
						else
						{
							$query['runtime'][] = $field;
						}
					}
					elseif($isQuery)
					{
						$query->registerRuntimeField(
							$fieldIsObject ? '' : $k,
							$field
						);
					}
				}
			}

			if(array_key_exists('filter', $runtime) && is_array($runtime['filter']) && !empty($runtime['filter']))
			{
				if($isArray)
				{
					if(!array_key_exists('filter', $query))
					{
						$query['filter'] = array();
					}

					$query['filter'] = $query['filter'] + $runtime['filter'];
				}
				elseif($isQuery)
				{
					$query->setFilter($query->getFilter() + $runtime['filter']);
				}
			}
		}

		if($isArray && array_key_exists('runtime', $query))
		{
			// move runtime on top of the query array, or else orm will crush
			$runtime = $query['runtime'];
			unset($query['runtime']);

			$query = array('runtime' => $runtime) + $query;
		}

		return $query;
	}

	public static function cloneFields(array $parameters = array())
	{
		if(is_array($parameters['runtime']) && !empty($parameters['runtime']))
		{
			$runtimes = array();
			foreach($parameters['runtime'] as $k => $runtime)
			{
				if(is_object($runtime))
				{
					$runtimes[$k] = clone $runtime;
				}
			}

			$parameters['runtime'] = $runtimes;
		}

		return $parameters;
	}
}