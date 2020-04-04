<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * This class is experimental, and should not be used in the client-side code
 *
 * @internal
 */

namespace Bitrix\Tasks\Item\Converter;

use Bitrix\Tasks\Item;
use Bitrix\Tasks\Item\Converter;
use Bitrix\Tasks\Util\Collection;

final class Stub extends Converter
{
	public function convert($instance)
	{
		$result = new Result();

		if(Item::isA($instance))
		{
			$instance = clone $instance;
			$instance->setId(0);

			// mark that $instance as a "brand new"
			$cached = $instance->getCachedFields();
			foreach($cached as $field)
			{
				$instance->setFieldModified($field);
			}

			$result->setInstance($instance);
		}
		elseif(Collection::isA($instance))
		{
			$result->setInstance($instance);
		}
		else
		{
			$result->addError('ILLEGAL_SOURCE_INSTANCE', 'Illegal source instance');
		}

		return $result;
	}
}