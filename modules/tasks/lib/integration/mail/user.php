<?
/**
 * Class implements all further interactions with "mail" module
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Mail;

use Bitrix\Tasks\Util\Error\Collection;

final class User extends \Bitrix\Tasks\Integration\Mail
{
	public static function getExternalCode()
	{
		return 'email';
	}

	public static function getData(array $ids, $siteId = '')
	{
		if(!static::includeModule())
		{
			return array();
		}

		$data = \Bitrix\Mail\User::getUserData($ids, \Bitrix\Tasks\Util\Site::getUserNameFormat($siteId));
		if (empty($data))
		{
			return array();
		}

		return $data;
	}

	public static function create(array $data, Collection $errors = null)
	{
		if(!static::includeModule())
		{
			if($errors)
			{
				$errors->add('MODULE_NOT_INSTALLED', 'Module not installed: ' . static::MODULE_NAME);
			}
			return false;
		}

		static $users = array();

		$email = (string) $data['EMAIL'];

		if($email == '' || !\check_email($email))
		{
			if($errors)
			{
				$errors->add('BAD_EMAIL', 'Bad user email');
			}
			return false;
		}

		if($users[$email])
		{
			return $users[$email];
		}

		$item = \CUser::GetList(
			$o = "ID",
			$b = "ASC",
			array("=EMAIL" => $email),
			array("FIELDS" => array("ID", "EXTERNAL_AUTH_ID", "ACTIVE"))
		)->fetch();

		if(
			$item && ($id = intval($item['ID']))
			&& ($item["ACTIVE"] == "Y" || $item["EXTERNAL_AUTH_ID"] == static::getExternalCode())
		)
		{
			if ($item["ACTIVE"] == "N") // email only
			{
				$user = new \CUser;
				$user->Update($id, array("ACTIVE" => "Y"));
			}
			$users[$email] = $id;

			return $id;
		}

		// create "extranet" user by email
		$id = \Bitrix\Mail\User::create(array(
			'EMAIL' => $email,
			'NAME' => (string) $data['NAME'],
			'LAST_NAME' => (string) $data['LAST_NAME']
		));

		if(!$id)
		{
			if($errors)
			{
				$errors->add('CREATION_FAIL', 'User creation failed');
			}
			return false;
		}
		else
		{
			$users[$email] = $id;
		}

		return $id;
	}

	/**
	 * Return true if user is email user
	 *
	 * @param mixed $data
	 * @return bool
	 */
	public static function isEmail($data)
	{
		if(!static::includeModule())
		{
			return false;
		}

		return is_array($data) && isset($data["EXTERNAL_AUTH_ID"]) && $data["EXTERNAL_AUTH_ID"] == static::getExternalCode();
	}
}