<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2017 Bitrix
 */

namespace Bitrix\FaceId\Api;

use Bitrix\Faceid\UsersTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\AuthTypeException;
use Bitrix\Rest\OAuth\Auth;
use Bitrix\Rest\RestException;

Loader::includeModule('rest');

/**
 * @package    bitrix
 * @subpackage faceid
 */
final class Face extends \IRestService
{
	const SCOPE = 'faceid';
	const FACEID_DEFAULT_CLIENT_SERVICE = 'ftracker';

	/**
	 * Rest event `onRestServiceBuildDescription` handler
	 * @return array
	 */
	public static function onRestServiceBuildDescription()
	{
		return array(
			static::SCOPE => array(
				'face.client.identify' => array(
					'callback' => array(__CLASS__, 'identify'),
					'options' => array()
				),
				'face.client.add' => array(
					'callback' => array(__CLASS__, 'add'),
					'options' => array()
				),
				/*'face.client.delete' => array(
					'callback' => array(__CLASS__, 'delete'),
					'options' => array()
				),*/
				'face.user.identify' => array(
					'callback' => array(__CLASS__, 'identifyUser'),
					'options' => array()
				),
				'face.user.add' => array(
					'callback' => array(__CLASS__, 'addUser'),
					'options' => array()
				),
				'face.user.delete' => array(
					'callback' => array(__CLASS__, 'deleteUser'),
					'options' => array()
				),
			),
		);
	}

	/**
	 * Adds face to client gallery.
	 *
	 * @param              $parameters
	 * @param              $n
	 * @param \CRestServer $server
	 *
	 * @return array
	 * @throws AccessException
	 * @throws ArgumentException
	 * @throws ArgumentNullException
	 * @throws AuthTypeException
	 * @throws RestException
	 */
	public static function add($parameters, $n, \CRestServer $server)
	{
		// permissions
		static::checkPermission($server);

		// parameters
		$parameters = array_change_key_case($parameters, CASE_LOWER);
		static::checkIssetParameters(['photo'], $parameters);

		$photo = $parameters['photo'];
		static::validatePhoto($photo);

		// add photo
		$response = \Bitrix\FaceId\FaceId::add($photo, static::FACEID_DEFAULT_CLIENT_SERVICE);

		if (!empty($response['success']) && !empty($response['result']['added']))
		{
			$item = $response['result']['item'];

			$faceId = $item['face_id'];
			$result = [
				'ID' => $faceId,
				'FACE_X' => $item['x'],
				'FACE_Y' => $item['y'],
				'FACE_WIDTH' => $item['width'],
				'FACE_HEIGHT' => $item['height']
			];
		}
		else
		{
			throw new RestException($response['result']['code']);
		}

		return $result;
	}

	/**
	 * Adds face to user gallery.
	 *
	 * @param              $parameters
	 * @param              $n
	 * @param \CRestServer $server
	 *
	 * @return array
	 * @throws AccessException
	 * @throws ArgumentException
	 * @throws ArgumentNullException
	 * @throws AuthTypeException
	 * @throws RestException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function addUser($parameters, $n, \CRestServer $server)
	{
		// permissions
		static::checkPermission($server);

		// parameters
		$parameters = array_change_key_case($parameters, CASE_LOWER);
		static::checkIssetParameters(['photo', 'user_id'], $parameters);

		$photo = $parameters['photo'];
		static::validatePhoto($photo);

		$userId = $parameters['user_id'];
		static::validateUserId($userId);

		// add photo
		$response = \Bitrix\FaceId\FaceId::addUser($photo, $userId);

		if (!empty($response['success']) && !empty($response['result']['added']))
		{
			$item = $response['result']['item'];

			$faceId = $item['face_id'];
			$result = [
				'ID' => $faceId,
				'FACE_X' => $item['x'],
				'FACE_Y' => $item['y'],
				'FACE_WIDTH' => $item['width'],
				'FACE_HEIGHT' => $item['height']
			];
		}
		else
		{
			throw new RestException($response['result']['code']);
		}

		return $result;
	}

	/**
	 * Identifies face in client gallery.
	 *
	 * @param              $parameters
	 * @param              $n
	 * @param \CRestServer $server
	 *
	 * @return array
	 * @throws AccessException
	 * @throws ArgumentException
	 * @throws ArgumentNullException
	 * @throws AuthTypeException
	 * @throws RestException
	 */
	public static function identify($parameters, $n, \CRestServer $server)
	{
		// permissions
		static::checkPermission($server);

		// parameters
		$parameters = array_change_key_case($parameters, CASE_LOWER);

		static::checkIssetParameters(['photo'], $parameters);
		$photo = $parameters['photo'];
		static::validatePhoto($photo);

		if (isset($parameters['force_add']))
		{
			$forceAdd = strtolower($parameters['force_add']);
			static::validateForceAdd($forceAdd);
		}
		else
		{
			$forceAdd = 'n';
		}

		// identify photo
		$forceAdded = false;

		$response = \Bitrix\FaceId\FaceId::identify($photo, static::FACEID_DEFAULT_CLIENT_SERVICE);
		$responseResult = $response['result'];

		if (!empty($response['success']) && !empty($responseResult['found']))
		{
			$item = $responseResult['items'][0];

			$faceId = $item['face_id'];
			$result = [
				'ID' => $faceId,
				'FACE_X' => $item['x'],
				'FACE_Y' => $item['y'],
				'FACE_WIDTH' => $item['width'],
				'FACE_HEIGHT' => $item['height'],
				'CONFIDENCE' => $item['confidence']
			];
		}
		elseif ($forceAdd == 'y' && !$responseResult['found'] && $responseResult['code'] == \Bitrix\FaceId\FaceId::CODE_OK_UNKNOWN_PERSON)
		{
			$response = \Bitrix\FaceId\FaceId::add($photo, static::FACEID_DEFAULT_CLIENT_SERVICE);

			if (!empty($response['success']) && !empty($response['result']['added']))
			{
				$item = $response['result']['item'];

				$faceId = $item['face_id'];
				$result = [
					'ID' => $faceId,
					'FACE_X' => $item['x'],
					'FACE_Y' => $item['y'],
					'FACE_WIDTH' => $item['width'],
					'FACE_HEIGHT' => $item['height'],
					'CONFIDENCE' => 0
				];

				$forceAdded = true;
			}
			else
			{
				throw new RestException($response['result']['code']);
			}
		}
		else
		{
			throw new RestException($response['result']['code']);
		}

		// set `recently added` flag
		if ($forceAdd == 'y')
		{
			$result['IS_NEW'] = $forceAdded;
		}

		return $result;
	}

	/**
	 * Identifies face in user gallery.
	 *
	 * @param              $parameters
	 * @param              $n
	 * @param \CRestServer $server
	 *
	 * @return array
	 * @throws AccessException
	 * @throws ArgumentException
	 * @throws ArgumentNullException
	 * @throws AuthTypeException
	 * @throws RestException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function identifyUser($parameters, $n, \CRestServer $server)
	{
		// permissions
		static::checkPermission($server);

		// parameters
		$parameters = array_change_key_case($parameters, CASE_LOWER);

		static::checkIssetParameters(['photo'], $parameters);
		$photo = $parameters['photo'];
		static::validatePhoto($photo);

		// identify photo
		$response = \Bitrix\FaceId\FaceId::identifyUser($photo);
		$responseResult = $response['result'];

		if (!empty($response['success']) && !empty($responseResult['found']))
		{
			$item = $responseResult['items'][0];

			$faceId = $item['face_id'];
			$userFace = UsersTable::getById($faceId)->fetch();
			$userId = $userFace['USER_ID'];

			$result = [
				'ID' => $faceId,
				'FACE_X' => $item['x'],
				'FACE_Y' => $item['y'],
				'FACE_WIDTH' => $item['width'],
				'FACE_HEIGHT' => $item['height'],
				'USER_ID' => $userId,
				'CONFIDENCE' => $item['confidence']
			];
		}
		else
		{
			throw new RestException($response['result']['code']);
		}

		return $result;
	}

	/**
	 * Deletes face from user gallery.
	 *
	 * @param              $parameters
	 * @param              $n
	 * @param \CRestServer $server
	 *
	 * @return bool
	 * @throws AccessException
	 * @throws ArgumentException
	 * @throws ArgumentNullException
	 * @throws AuthTypeException
	 * @throws RestException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Exception
	 */
	public static function deleteUser($parameters, $n, \CRestServer $server)
	{
		// permissions
		static::checkPermission($server);

		// parameters
		$parameters = array_change_key_case($parameters, CASE_LOWER);
		static::checkIssetParameters(['face_id'], $parameters);
		$faceId = $parameters['face_id'];

		// get face
		$userFace = UsersTable::getRowById($faceId);
		if (empty($userFace))
		{
			throw new ArgumentException('Face not found in database');
		}

		// delete
		$response = \Bitrix\FaceId\FaceId::deleteUser($userFace);

		if ($response['success'] && !empty($response['result']['deleted']))
		{
			UsersTable::delete($userFace['ID']);
			\CFile::Delete($userFace['FILE_ID']);

			return true;
		}
		else
		{
			throw new RestException($response['result']['code']);
		}
	}

	/**
	 * @param $keys
	 * @param $parameters
	 *
	 * @throws ArgumentNullException
	 */
	protected static function checkIssetParameters($keys, $parameters)
	{
		foreach ($keys as $key)
		{
			if (!isset($parameters[$key]))
			{
				throw new ArgumentNullException($key);
			}
		}
	}

	/**
	 * @param $photo
	 *
	 * @throws ArgumentException
	 */
	protected static function validatePhoto(&$photo)
	{
		$photo = str_replace('data:image/jpeg', 'data://image/jpeg', $photo);
		$photo = base64_decode(str_replace('data://image/jpeg;base64,', '', $photo));

		if (!empty($photo))
		{
			// check image format
			$imageMeta = getimagesizefromstring($photo);
			if ($imageMeta !== false)
			{
				$format = $imageMeta[2];

				if ($format === IMAGETYPE_JPEG)
				{
					// check image size
					$width = $imageMeta[0];
					$height = $imageMeta[1];

					if ($width < 80 || $height < 80)
					{
						throw new ArgumentException('Minimum photo should be 80x80px');
					}
				}
				else
				{
					throw new ArgumentException('Unknown photo format, should be jpeg');
				}
			}
			else
			{
				throw new ArgumentException('Unknown photo format, should be jpeg encoded with base64');
			}
		}
		else
		{
			throw new ArgumentException('Unknown photo format, should be jpeg encoded with base64');
		}
	}

	/**
	 * @param $userId
	 *
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected static function validateUserId($userId)
	{
		$user = UserTable::getByPrimary($userId, ['select' => ['ID']])->fetch();
		if (empty($user))
		{
			throw new ArgumentException('User not found');
		}
	}

	/**
	 * @param $value
	 *
	 * @throws ArgumentException
	 */
	protected static function validateForceAdd($value)
	{
		if ($value !== 'y' && $value !== 'n')
		{
			throw new ArgumentException('Unknown autoAdd value, should be "y" or "n"');
		}
	}

	/**
	 * @param \CRestServer $server
	 *
	 * @throws AccessException
	 */
	protected static function checkPermission(\CRestServer $server)
	{
		global $USER;

		if (!$USER->IsAdmin())
		{
			throw new AccessException('Admin permission required');
		}

		if (!\Bitrix\Faceid\AgreementTable::checkUser($USER->getId()))
		{
			throw new AccessException('License agreement should be accepted');
		}
	}
}
