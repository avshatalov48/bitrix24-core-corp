<?php
namespace Bitrix\Dav;
use Bitrix\Main\Entity;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;

/**
 * Class TokensTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> APP_CODE string(255) optional
 * <li> PLATFORM string(255) optional
 * <li> PARAMS string optional
 * <li> DATE_CREATE datetime optional
 * </ul>
 *
 * @package Bitrix\Dav
 */
class TokensTable extends Entity\DataManager
{
	/**
	 * After this interval token will unavailable,
	 * format of this param is equal to param which passing  to Date::add()
	 */
	const TOKEN_AVAILABLE_INTERVAL = 'T5M';

	const DEFAULT_TOKEN_LENGTH = 45;

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_dav_tokens';
	}

	/**
	 * Returns entity map definition.
	 * To get initialized fields @see \Bitrix\Main\Entity\Base::getFields() and \Bitrix\Main\Entity\Base::getField()
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new Entity\StringField('TOKEN',array(
				'primary' => true,
				'default_value' => Random::getString(static::DEFAULT_TOKEN_LENGTH),
				'title' => Loc::getMessage('TOKEN_FIELD'),
			)),
			new Entity\IntegerField('USER_ID',array(
				'required' => true,
				'title' => Loc::getMessage('TOKEN_OWNER_FIELD'),
			)),
			new Entity\DatetimeField('EXPIRED_AT',array(
				'default_value' => self::getTokenNewValidTimeInterval(),
				'title' => Loc::getMessage('TOKEN_EXPIRED_AT_FIELD'),
			)),
			new Entity\ReferenceField(
				'USER',
				'Bitrix\Main\UserTable',
				array('=this.USER_ID' => 'ref.ID')
			)
		);
	}


	/**
	 * @param int $userId User id.
	 * @return array|false
	 */
	public static function getToken($userId)
	{
		$result = static::getList(array(
			'filter' => array('USER_ID' => $userId),
		))->fetchRaw();

		return !empty($result['TOKEN']) ? $result['TOKEN']: null;
	}

	/**
	 * @param int $userId User id.
	 * @param null|string $token Token to set to user.
	 * @param null|DateTime $expiredAt If set this parameter token will expired in that time, else after TOKEN_AVAILABLE_INTERVAL.
	 * @return array
	 */
	public static function createToken($userId, $token = null, DateTime $expiredAt = null)
	{
		$params['USER_ID'] = $userId;
		if ($token)
			$params['TOKEN'] = $token;
		if ($expiredAt)
			$params['EXPIRED_AT'] = $expiredAt;
		return static::add($params)->getData();
	}

	/**
	 * @param string $oldToken Old token for finding what we want update.
	 * @param int $userId User id.
	 * @param string|null $newToken New token to set for user.
	 * @param DateTime|null $expiredAt If set this parameter token will expired in that time, else after TOKEN_AVAILABLE_INTERVAL.
	 * @return array
	 */
	public static function updateToken($oldToken, $userId, $newToken = null, DateTime $expiredAt = null)
	{
		$params['USER_ID'] = $userId;
		$params['TOKEN'] = $newToken ?: Random::getString(static::DEFAULT_TOKEN_LENGTH);
		$params['EXPIRED_AT'] = $expiredAt ?: self::getTokenNewValidTimeInterval();

		return static::update($oldToken, $params)->getData();
	}

	/**
	 * @param string $token Token passed from user for checking.
	 * @return bool
	 */
	public static function isTokenValid($token)
	{
		if (!$token)
			return false;
		$result = static::getById($token)->fetch();
		if ($result && $result['EXPIRED_AT'] >= self::getTokenLastValidTime())
		{
			return true;
		}
		return false;
	}

	/**
	 * Delete all deprecated tokens, can use in agents
	 * @return void
	 */
	public static function clearDeprecatedTokens()
	{
		$deprecatedTokens = static::getList(array(
			'select' => array('TOKEN'),
			'filter' => array('<EXPIRED_AT' => self::getTokenLastValidTime())
		));
		while ($result = $deprecatedTokens->fetch())
		{
			static::delete($result['TOKEN']);
		}
	}

	/**
	 * @return \Bitrix\Main\Type\Date
	 */
	private static function getTokenLastValidTime()
	{
		$currentTime = new DateTime();
		return $currentTime;
	}

	/**
	 * @return \Bitrix\Main\Type\Date
	 */
	private static function getTokenNewValidTimeInterval()
	{
		$currentTime = new DateTime();
		return $currentTime->add(static::TOKEN_AVAILABLE_INTERVAL);
	}
}