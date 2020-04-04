<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Disk\UserField;

use Bitrix\Tasks\Integration\Disk;
use Bitrix\Tasks\Util\User;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Tasks\Util;

class Type extends \Bitrix\Tasks\Util\UserField\Type
{
	public static function cloneValue($value, array &$entityData, array $fromField, array $toField, $userId = 0, array $parameters = array())
	{
		if(Util\Collection::isA($value))
		{
			$value = $value->toArray();
		}

		if(!Disk::includeModule() || empty($value) || !is_array($fromField) || !is_array($toField))
		{
			return $value;
		}

		$userId = intval($userId);
		if(!$userId)
		{
			$userId = User::getId();
		}

		$newValue = '';

		if(!Util\UserField::isValueEmpty($value))
		{
			$origValueData = Disk::getAttachmentData($value);

			if($parameters['SKIP'])
			{
				$entityData['DESCRIPTION'] = static::removeRawAttachments($entityData['DESCRIPTION'], $origValueData);
			}
			else
			{
				$newValue = static::translateValueByMultiple($value, $fromField, $toField);

				if(!is_array($newValue)) // since the disk module does not support single-mode fields, just exit
				{
					return '';
				}

				// clone each attachments
				// todo: create a table of temporal files, create an agent to clean those files up
				$newValue = Disk::cloneFileAttachmentHash($newValue, $userId);

				// also, we need to translate raw attachments
				$entityData['DESCRIPTION'] = static::translateRawAttachments($entityData['DESCRIPTION'], $newValue, $origValueData);
			}
		}

		return $newValue;
	}

	public static function cancelCloneValue($value, array $fromField, array $toField, $userId = false)
	{
		if(Disk::includeModule())
		{
			$userId = intval($userId);
			if(!$userId)
			{
				$userId = User::getId();
			}

			if(is_array($value) && !empty($value))
			{
				Disk::deleteUnattachedFiles($value, $userId);
			}
		}

		return '';
	}

	private static function removeRawAttachments($message, $attachmentData)
	{
		return preg_replace_callback(
			"/\[DISK FILE ID\s*=\s*([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER,
			function ($matches) use ($attachmentData)
			{
				if($matches[1])
				{
					if(array_key_exists($matches[1], $attachmentData))
					{
						return ''; // remove
					}
					else
					{
						return "[DISK FILE ID=".$matches[1]."]"; // attachment belongs to some other disk field
					}
				}

				return ''; // no match?
			},
			$message
		);
	}

	private static function translateRawAttachments($message, $map, $attachmentData)
	{
		if((string) $message == '')
		{
			return $message;
		}

		// make possible replacements
		$objMap = array();
		foreach($attachmentData as $id => $data)
		{
			$objMap[FileUserType::NEW_FILE_PREFIX.$data['OBJECT_ID']] = $map[$data['ID']];
		}

		return preg_replace_callback(
			"/\[DISK FILE ID\s*=\s*([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER,
			function ($matches) use ($map, $objMap)
			{
				$from = $matches[1];
				$to = false;

				if($from)
				{
					if(array_key_exists($from, $map)) // attachment id (number) => n+(object id)
					{
						$to = $map[$from];
					}
					elseif(array_key_exists($from, $objMap)) // n+(object id) => n+(object id)
					{
						$to = $objMap[$from];
					}

					if($to)
					{
						return "[DISK FILE ID=".$to."]";
					}
				}
			},
			$message
		);
	}
}