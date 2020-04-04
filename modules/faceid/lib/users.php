<?php

namespace Bitrix\Faceid;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

/**
 * Class UsersTable
 * Handles dedicated remote face gallery for identifying portal users
 * @package Bitrix\Faceid
 **/

class UsersTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_faceid_users';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			new Main\Entity\IntegerField('FILE_ID'),
			new Main\Entity\IntegerField('CLOUD_FACE_ID'),
			new Main\Entity\IntegerField('USER_ID')
		);
	}

	/**
	 * Listener to the events main:OnAfterUserUpdate and main:OnBeforeUserAdd
	 *
	 *@param $arFields
	 */
	public static function onUserPhotoChange($arFields)
	{
		if (!empty($arFields['PERSONAL_PHOTO']))
		{
			// delete existing photo for this user
			$userFaces = static::getList(array('filter' => array('=USER_ID' => $arFields['ID'])))->fetchAll();
			if (!empty($userFaces))
			{
				foreach ($userFaces as $userFace)
				{
					// delete user's face
					static::deleteUserFace($userFace);
				}
			}

			// index new avatar
			static::indexUser($arFields);
		}
	}

	// main:OnBeforeUserUpdate
	public static function onUserPhotoDelete($arFields)
	{
		// check if need to delete photo
		if (!empty($arFields['PERSONAL_PHOTO']) && isset($arFields['PERSONAL_PHOTO']["del"]) && $arFields['PERSONAL_PHOTO']["del"] <> '')
		{
			// okay
			$userFaces = static::getList(array('filter' => array('=USER_ID' => $arFields['ID'])))->fetchAll();
			if (!empty($userFaces))
			{
				foreach ($userFaces as $userFace)
				{
					// delete user's face
					static::deleteUserFace($userFace);
				}
			}
		}
	}

	// main:OnAfterUserDelete
	public static function onUserDelete($userId)
	{
		$userFaces = static::getList(array('filter' => array('=USER_ID' => $userId)))->fetchAll();
		if (!empty($userFaces))
		{
			foreach ($userFaces as $userFace)
			{
				// delete user's face
				static::deleteUserFace($userFace);
			}
		}
	}

	public static function indexUser($user)
	{
		if (!static::checkTimemanEnabled($user['ID']))
		{
			return false;
		}

		// add new face
		$file = \CFile::MakeFileArray($user['PERSONAL_PHOTO']);
		$io = \CBXVirtualIo::GetInstance();

		// handle b24 remote files
		$filePath = $io->GetPhysicalName($file['tmp_name']);

		if ($file['type'] == 'image/jpeg')
		{
			$imageContent = $io->GetFile($filePath)->GetContents();
		}
		else
		{
			// convert non-jpeg formats to jpeg
			$fileSizeTmp = \CFile::GetImageSize($filePath, true);

			switch ($fileSizeTmp[2])
			{
				case IMAGETYPE_GIF:
					$sourceImage = imagecreatefromgif($filePath);
					break;
				case IMAGETYPE_PNG:
					$sourceImage = imagecreatefrompng($filePath);
					break;
				case IMAGETYPE_BMP:
					$sourceImage = \CFile::ImageCreateFromBMP($filePath);
					break;
				default:
					return false;
			}

			ob_start();
			imagejpeg($sourceImage, null, 95);
			$imageContent = ob_get_clean();
		}

		// duplicate image for face database
		$response = FaceId::addUser($imageContent, $user['ID']);

		if (!empty($response['success']) && !empty($response['result']['added']))
		{
			// everything's ok
			return true;
		}
		else
		{
			//'msg' => \Bitrix\FaceId\FaceId::getErrorMessage($response['result']['code'])
			return false;
		}
	}

	public static function indexUsers()
	{
		$result = Main\UserTable::getList(array(
			'select' => array('ID', 'PERSONAL_PHOTO'),
			'filter' => array('=ACTIVE' => 'Y', '>PERSONAL_PHOTO' => 0)
		));

		while ($user = $result->fetch())
		{
			static::indexUser($user);
		}
	}

	public static function reindexUsers()
	{
		$result = static::getList();
		while ($userFace = $result->fetch())
		{
			static::deleteUserFace($userFace);
		}

		static::indexUsers();
	}

	public static function deleteUserFace($userFace)
	{
		if (!Main\Config\Option::get('faceid', 'user_index_enabled', 0))
		{
			return false;
		}

		$response = FaceId::deleteUser($userFace);

		if ($response['success'] && !empty($response['result']['deleted']))
		{
			static::delete($userFace['ID']);
			\CFile::Delete($userFace['FILE_ID']);
		}
	}

	public static function checkTimemanEnabled($userId)
	{
		if (!Main\Config\Option::get('faceid', 'user_index_enabled', 0))
		{
			return false;
		}

		if (\Bitrix\Main\Loader::includeModule('timeman'))
		{
			$tmUser = new \CTimeManUser($userId);
			$settings = $tmUser->GetSettings();
			return (bool)$settings['UF_TIMEMAN'];
		}

		return false;
	}

	public static function getExpiredReasonList()
	{
		Loc::loadMessages(__FILE__);

		return array(
			'forgot' => Loc::getMessage("FACEID_USERS_EXP_REASON_FORGOT"),
			'maintenance' => Loc::getMessage("FACEID_USERS_EXP_REASON_MAINTENANCE"),
			'other' => Loc::getMessage("FACEID_USERS_EXP_REASON_OTHER")
		);
	}
}