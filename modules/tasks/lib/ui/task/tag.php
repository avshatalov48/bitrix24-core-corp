<?php
/**
 * This class contains ui helper for task/tag entity
 *
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Tasks\UI\Task;

use Bitrix\Tasks\Util\Type;

final class Tag
{
	public static function formatTagString($tags)
	{
		if(Type::isIterable($tags) && count($tags))
		{
			$formatted = array();

			foreach ($tags as $tag)
			{
				if(Type::isIterable($tag) && $tag['NAME'])
				{
					$formatted[] = (string) $tag['NAME'];
				}
				elseif($tag !== '')
				{
					$formatted[] = (string) $tag;
				}
			}

			return implode(', ', $formatted);
		}

		return '';
	}
}