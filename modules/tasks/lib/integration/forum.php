<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */

namespace Bitrix\Tasks\Integration;

abstract class Forum extends \Bitrix\Tasks\Integration
{
	const MODULE_NAME = 'forum';

	public static function getParser(array $parameters = array())
	{
		if(!static::includeModule())
		{
			return null;
		}

		$languageId = (string) $parameters['LANGUAGE_ID'] != '' ? $parameters['LANGUAGE_ID'] : LANGUAGE_ID;

		static $parser;
		if($parser == null)
		{
			$parser = new \forumTextParser($languageId);
		}

		if((string) $parameters['PATH_TO_USER_PROFILE'] != '')
		{
			$parser->pathToUser = $parameters['PATH_TO_USER_PROFILE'];
		}

		return $parser;
	}
}