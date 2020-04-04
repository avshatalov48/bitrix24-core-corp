<?
/**
 * @access private
 */

namespace Bitrix\Tasks\Util;

class Entity
{
	public static function cloneRuntimeFields(array $parameters = array())
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