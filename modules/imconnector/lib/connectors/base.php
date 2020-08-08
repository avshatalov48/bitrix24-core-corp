<?php
namespace Bitrix\ImConnector\Connectors;

use \Bitrix\Main\UserTable,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\Input\ReceivingMessage;

/**
 * Class Connector
 * @package Bitrix\ImConnector\Connectors
 */
class Base
{
	protected $idConnector = '';

	/**
	 * Connector constructor.
	 * @param $idConnector
	 */
	public function __construct($idConnector)
	{
		$this->idConnector = $idConnector;
	}

	//User
	/**
	 * Preparation of new user fields before saving or adding.
	 *
	 * @param array $user An array describing the user.
	 * @return array Given the right format array description user.
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	public function preparationNewUserFields($user): array
	{
		return array_merge($this->preparationUserFields($user), $this->getBasicFieldsNewUser($user));
	}

	/**
	 * Returns the base fields of the new user of open lines.
	 *
	 * @param $user
	 * @return mixed
	 */
	protected function getBasicFieldsNewUser($user)
	{
		$fields['LOGIN'] = Library::MODULE_ID . '_' . md5($user['id'] . '_' . randString(5));
		$fields['PASSWORD'] = md5($fields['LOGIN'].'|'.rand(1000,9999).'|'.time());
		$fields['CONFIRM_PASSWORD'] = $fields['PASSWORD'];
		$fields['EXTERNAL_AUTH_ID'] = Library::NAME_EXTERNAL_USER;
		$fields['XML_ID'] =  $this->idConnector . '|' . $user['id'];
		$fields['ACTIVE'] = 'Y';

		return $fields;
	}

	/**
	 * Preparation of user fields before saving or adding.
	 *
	 * @param array $user An array describing the user.
	 * @param $userId
	 * @return array Given the right format array description user.
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function preparationUserFields($user, $userId = 0): array
	{
		//The hash of the data
		$fields = [
			'UF_CONNECTOR_MD5' => md5(serialize($user))
		];

		//TODO: Hack to bypass the option of deleting the comment
		if(isset($user['name']))
		{
			//Name
			if(Library::isEmpty($user['name']))
			{
				$fields['NAME'] = '';
			}
			else
			{
				$fields['NAME'] = $user['name'];
			}
		}
		//Surname
		if(Library::isEmpty($user['last_name']))
		{
			$fields['LAST_NAME'] = '';
		}
		else
		{
			$fields['LAST_NAME'] = $user['last_name'];
		}

		if(Library::isEmpty($fields['NAME']) && Library::isEmpty($fields['LAST_NAME']))
		{
			if(Library::isEmpty($user['title']))
			{
				$fields['NAME'] = Loc::getMessage("IMCONNECTOR_GUEST_USER");
			}
			else
			{
				$fields['NAME'] = $user['title'];
			}
		}

		//The link to the profile
		if(empty($user['url']))
		{
			$fields['PERSONAL_WWW'] = '';
		}
		else
		{
			$fields['PERSONAL_WWW'] = $user['url'];
		}

		//Sex
		if(empty($user['gender']))
		{
			$fields['PERSONAL_GENDER'] = '';
		}
		else
		{
			if($user['gender'] == 'male')
			{
				$fields['PERSONAL_GENDER'] = 'M';
			}
			elseif($user['gender'] == 'female')
			{
				$fields['PERSONAL_GENDER'] = 'F';
			}
		}
		//Personal photo
		if(!empty($user['picture']))
		{
			$fields['PERSONAL_PHOTO'] = ReceivingMessage::downloadFile($user['picture']);

			if(
				!empty($fields['PERSONAL_PHOTO']) &&
				!empty($userId)
			)
			{
				$rowUser = UserTable::getList([
					'select' => ['PERSONAL_PHOTO'],
					'filter' => ['ID' => $userId]
				])->fetch();

				if(!empty($rowUser['PERSONAL_PHOTO']))
				{
					$fields['PERSONAL_PHOTO']['del'] = 'Y';
					$fields['PERSONAL_PHOTO']['old_file'] = $rowUser['PERSONAL_PHOTO'];
				}
			}
		}

		if(!Library::isEmpty($user['title']))
		{
			$fields['TITLE'] = $user['title'];
		}

		if (!Library::isEmpty($user['email']))
		{
			$fields['EMAIL'] = $user['email'];
		}

		if (!Library::isEmpty($user['phone']))
		{
			$fields['PERSONAL_MOBILE'] = $user['phone'];
		}

		return $fields;
	}

	//File

	/**
	 * Save file
	 *
	 * @param $file
	 * @return bool|int|mixed|string
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	public function saveFile($file)
	{
		$result = false;

		$file = ReceivingMessage::downloadFile($file);

		if($file)
		{
			$result = \CFile::SaveFile(
				$file,
				Library::MODULE_ID
			);
		}

		return $result;
	}
}