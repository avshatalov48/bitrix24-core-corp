<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration;

use Bitrix\Tasks\Integration\Disk;

abstract class Mail extends \Bitrix\Tasks\Integration
{
	const MODULE_NAME = 'mail';

	public static function getSubjectPrefix()
	{
		return 'Re: ';
	}

	public static function formatThreadId($tag, $siteId = '')
	{
		$site = \Bitrix\Tasks\Util\Site::get($siteId);

		return '<TASKS_'.trim($tag).'@'.$site["SERVER_NAME"].'>';
	}

	public static function getDefaultEmailFrom($siteId = '')
	{
		if(!static::includeModule())
		{
			return '';
		}

		$site = \Bitrix\Tasks\Util\Site::get($siteId);

		return \Bitrix\Mail\User::getDefaultEmailFrom($site['SERVER_NAME']);
	}

	public static function stopMailEventCompiler()
	{
		if(static::includeModule())
		{
			\Bitrix\Main\Mail\EventMessageThemeCompiler::stop();
		}
	}

	/**
	 * @param $message
	 * @param array $attachments
	 * @param $userId
	 * @return array
	 */
	protected static function processAttachments($message, array $attachments, $userId): array
	{
		// save attachments
		$files = [];
		$relations = [];

		if (is_array($attachments))
		{
			foreach ($attachments as $key => $file)
			{
				if (!is_array($file) || empty($file))
				{
					continue;
				}

				$uploadResult = Disk::uploadFile($file, $userId);
				if ($uploadResult->isSuccess())
				{
					$uploadData = $uploadResult->getData();
					$files[] = $relations[$key] = $uploadData['ATTACHMENT_ID'];
				}
			}
		}

		// also, translate possible [DISK FILE] tags in the message, if any
		$message = static::translateRawAttachments($message, $relations);

		return [$message, $files];
	}

	private static function translateRawAttachments($message, $attachmentRelations)
	{
		if((string) $message == '')
		{
			return $message;
		}

		return preg_replace_callback(
			"/\[ATTACHMENT\s*=\s*([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER,
			function ($matches) use ($attachmentRelations)
			{
				if (isset($attachmentRelations[$matches[1]]))
				{
					return "[DISK FILE ID=".$attachmentRelations[$matches[1]]."]";
				}
			},
			$message
		);
	}
}