<?
/**
 * Class implements all further interactions with "forum"
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Forum;

abstract class Comment extends \Bitrix\Tasks\Integration\Forum
{
	public static function makeUrlHash($commentId)
	{
		$commentId = intval($commentId);
		if($commentId)
		{
			return static::getUrlHashPrefix().$commentId;
		}

		return '';
	}

	public static function getUrlParameters($commentId)
	{
		$result = array();

		$commentId = intval($commentId);
		if($commentId)
		{
			$result['MID'] = $commentId;
		}

		return $result;
	}

	public static function makeUrl($url, $commentId)
	{
		$commentId = intval($commentId);
		if($commentId)
		{
			$url = \Bitrix\Tasks\Util::replaceUrlParameters($url, static::getUrlParameters($commentId))."#".static::makeUrlHash($commentId);
		}

		return $url;
	}

	protected static function getOccurAsId($authorId)
	{
		$id = \Bitrix\Tasks\Util\User::getOccurAsId();
		if (!$id)
		{
			$id = $authorId ? $authorId : \Bitrix\Tasks\Util\User::getAdminId();
		}

		return $id;
	}

	private static function getUrlHashPrefix()
	{
		return 'com';
	}
}