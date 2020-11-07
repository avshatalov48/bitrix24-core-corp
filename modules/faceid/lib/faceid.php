<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2016 Bitrix
 */

namespace Bitrix\FaceId;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Application;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * @package    bitrix
 * @subpackage faceid
 */
class FaceId
{
	const CODE_OK = 'OK';
	const CODE_FAIL = 'FAIL';
	const CODE_FAIL_CONNECT = 'FAIL_CONNECT';
	const CODE_FAIL_RESPONSE = 'FAIL_RESPONSE';
	const CODE_FAIL_TEMPORARY_UNAVAILABLE = 'FAIL_TEMPORARY_UNAVAILABLE';
	const CODE_FAIL_UNKNOWN_COMMAND = 'FAIL_UNKNOWN_COMMAND';
	const CODE_FAIL_UNKNOWN_SERVICE = 'FAIL_UNKNOWN_SERVICE';
	const CODE_FAIL_GALLERY_CREATE = 'FAIL_GALLERY_CREATE';
	const CODE_FAIL_SAVE_LOCAL_PHOTO = 'FAIL_SAVE_LOCAL_PHOTO';
	const CODE_FAIL_PORTAL_REGISTER = 'FAIL_PORTAL_REGISTER';
	const CODE_FAIL_NO_CREDITS = 'FAIL_NO_CREDITS';
	const CODE_FAIL_DETECT_FACE = 'FAIL_DETECT_FACE';
	const CODE_FAIL_PROVIDER_RESPONSE = 'FAIL_PROVIDER_RESPONSE';
	const CODE_OK_UNKNOWN_PERSON = 'OK_UNKNOWN_PERSON';
	const CODE_OK_NOT_FOUND = 'OK_NOT_FOUND'; // when deleting user

	public static function isAvailable()
	{
		if (Loader::includeModule('bitrix24'))
		{
			// check for b24 conditions
			if (!Feature::isFaceIdAvailable())
			{
				return false;
			}

		}
		else
		{
			// check for standalone conditions
			if (!Directory::isDirectoryExists(Application::getDocumentRoot()."/bitrix/modules/main/lang/ru"))
			{
				return false;
			}

			if (Loader::includeModule('intranet'))
			{
				if (\CIntranetUtils::getPortalZone() !== 'ru')
				{
					return false;
				}
			}
		}

		return true;
	}

	public static function insertIntoCrmMenu(&$items)
	{
		// disabled for some portals
		if (!static::isAvailable())
		{
			return;
		}

		$newItems = array();

		foreach ($items as $item)
		{
			if ($item['ID'] == 'START')
			{
				$newItems[] = array(
					'ID' => 'FACETRACKER',
					'MENU_ID' => 'menu_crm_facetracker',
					'NAME' => Loc::getMessage('FACEID_TRACKER'),
					'TITLE' => Loc::getMessage('FACEID_TRACKER'),
					'URL' => '/crm/face-tracker/',
					'ICON' => 'settings',
					'IS_DISABLED' => true
				);
			}

			$newItems[] = $item;
		}

		$items = $newItems;
	}

	/**
	 * @param string $binaryImageContent in JPEG format
	 * @param string $service
	 *
	 * @return array [success => bool, result => [found => bool, items => array(face_id, confidence)]]
	 */
	public static function identify($binaryImageContent, $service = null)
	{
		return static::identifyInternal($binaryImageContent, 'identify', $service);
	}

	protected static function identifyInternal($binaryImageContent, $operation, $service)
	{
		$handler = new Http;

		$params = array(
			'image' => base64_encode($binaryImageContent)
		);

		if ($service !== null)
		{
			$params['service'] = $service;
		}

		$response = $handler->query($operation, $params);

		$result = array('found' => false, 'msg' => '');
		if ($response['success'])
		{
			$result = $response['result'];
		}

		// update balance
		if (isset($response['status']['balance']))
		{
			$currentBalance = (int) $response['status']['balance'];
			\Bitrix\Main\Config\Option::set('faceid', 'balance', $currentBalance);
		}

		// continue with faces
		if ($result['found'])
		{
			$newItems = array();

			foreach ($result['items'] as $item)
			{
				$newItem = array();

				// face id
				$meta = explode(':', $item['meta']);
				$newItem['face_id'] = intval($meta[1]);

				// confidence
				$newItem['confidence'] = $item['confidence'];

				$newItem['x'] = $item['x'];
				$newItem['y'] = $item['y'];
				$newItem['width'] = $item['width'];
				$newItem['height'] = $item['height'];

				$newItems[] = $newItem;
			}

			$response['result']['items'] = $newItems;
		}

		return $response;
	}

	/**
	 * @param string $binaryImageContent in JPEG format
	 * @param string $service
	 *
	 * @return array [success => bool, result => [added => bool, item => array(face_id, file_id)]]
	 */
	public static function add($binaryImageContent, $service = null)
	{
		return static::addInternal($binaryImageContent, 'add', '\Bitrix\FaceId\FaceTable', $service);
	}

	protected static function addInternal($binaryImageContent, $operation, $faceTableClass, $service = null)
	{
		// save face locally
		$response = array('success' => false, 'result' => array());

		/** @var DataManager $faceTableClass */
		$addResult = $faceTableClass::add(array('ID' => null));

		if ($addResult->isSuccess())
		{
			$faceId = $addResult->getId();

			// save photo locally
			$fileId = \CFile::SaveFile(array(
				'MODULE_ID' => 'faceid',
				'name' => 'face_'.$faceId.'.jpg',
				'type' => 'image/jpeg',
				'content' => $binaryImageContent
			), 'faceid');

			if (empty($fileId))
			{
				// rollback
				static::rollbackAddInternal($faceId, $faceTableClass);

				$response['result']['code'] = static::CODE_FAIL_SAVE_LOCAL_PHOTO;
			}
			else
			{
				// save photo to the cloud
				$handler = new Http;

				$params = array(
					'image' => base64_encode($binaryImageContent),
					'meta' => 'faceid:'.$faceId
				);

				if ($service !== null)
				{
					$params['service'] = $service;
				}

				$response = $handler->query($operation, $params);

				$result = array('added' => false, 'msg' => '');
				if ($response['success'])
				{
					$result = $response['result'];
				}

				// update balance
				if (isset($response['status']['balance']))
				{
					$currentBalance = (int)$response['status']['balance'];
					\Bitrix\Main\Config\Option::set('faceid', 'balance', $currentBalance);
				}

				if (empty($result['added']))
				{
					static::rollbackAddInternal($faceId, $faceTableClass, $fileId);
				}
				else
				{
					// update face with fileID and cloudID
					$faceTableClass::update($faceId, array(
						'FILE_ID' => $fileId,
						'CLOUD_FACE_ID' => $result['face_id']
					))->getId();

					$response['result']['item'] = array(
						'face_id' => $faceId,
						'file_id' => $fileId,
						'x' => $result['x'],
						'y' => $result['y'],
						'width' => $result['width'],
						'height' => $result['height']
					);

					// not to confuse with local face id
					unset($response['result']['face_id']);
				}
			}
		}
		else
		{
			$response['result']['code'] = static::CODE_FAIL;
		}

		return $response;
	}

	protected static function rollbackAddInternal($faceId, $faceTableClass, $fileId = null)
	{
		/** @var DataManager $faceTableClass */
		$faceTableClass::delete($faceId);

		if (!empty($fileId))
		{
			\CFile::Delete($fileId);
		}
	}

	public static function identifyVk($binaryImageContent)
	{
		$handler = new \Bitrix\FaceId\Http;

		$response = $handler->query("identify_vk", array(
			'image' => base64_encode($binaryImageContent)
		));

		// update balance
		if (isset($response['status']['balance']))
		{
			$currentBalance = (int) $response['status']['balance'];
			\Bitrix\Main\Config\Option::set('faceid', 'balance', $currentBalance);
		}

		return $response;
	}

	/**
	 * The same as identify, but works with dedicated gallery for portal users.
	 *
	 * @param string $binaryImageContent in JPEG format
	 *
	 * @return array [success => bool, result => [found => bool, items => array(face_id, confidence)]]
	 */
	public static function identifyUser($binaryImageContent)
	{
		return static::identifyInternal($binaryImageContent, 'identify_user', 'b24time');
	}

	/**
	 * @param string $binaryImageContent in JPEG format
	 *
	 * @return array [success => bool, result => [added => bool, item => array(face_id, file_id)]]
	 */
	public static function addUser($binaryImageContent, $userId)
	{
		$response = static::addInternal($binaryImageContent, 'add_user', '\Bitrix\FaceId\UsersTable');

		if (!empty($response['success']) && !empty($response['result']['added']))
		{
			$faceId = $response['result']['item']['face_id'];
			UsersTable::update($faceId, array('USER_ID' => $userId));
		}

		return $response;
	}

	/**
	 * @param string $binaryImageContent in JPEG format
	 *
	 * @return array [success => bool, result => [deleted => bool]
	 */
	public static function deleteUser($userFace)
	{
		if (!is_array($userFace))
		{
			$userFace = UsersTable::getById($userFace)->fetch();
		}

		if (empty($userFace))
		{
			return false;
		}

		$handler = new Http;

		$response = $handler->query('delete_user', array(
			'cloud_face_id' => $userFace['CLOUD_FACE_ID']
		));

		// update balance
		if (isset($response['status']['balance']))
		{
			$currentBalance = (int)$response['status']['balance'];
			\Bitrix\Main\Config\Option::set('faceid', 'balance', $currentBalance);
		}

		return $response;
	}

	public static function getBalance()
	{
		$handler = new \Bitrix\FaceId\Http;

		$response = $handler->query("balance");

		// update balance
		if (isset($response['status']['balance']))
		{
			$currentBalance = (int) $response['status']['balance'];
			\Bitrix\Main\Config\Option::set('faceid', 'balance', $currentBalance);
		}

		return $response;
	}

	public static function getUsageStats()
	{
		$handler = new \Bitrix\FaceId\Http;

		$response = $handler->query("stats");

		// update balance
		if (isset($response['status']['balance']))
		{
			$currentBalance = (int) $response['status']['balance'];
			\Bitrix\Main\Config\Option::set('faceid', 'balance', $currentBalance);
		}

		return $response;
	}

	public static function getErrorMessage($errorCode)
	{
		$msg = \Bitrix\Main\Localization\Loc::getMessage('FACEID_CLOUD_ERR_'.$errorCode);

		if ($msg == '')
		{
			$msg = \Bitrix\Main\Localization\Loc::getMessage('FACEID_CLOUD_ERR_'.static::CODE_FAIL);
		}

		return $msg;
	}
}
