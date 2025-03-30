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
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Tokens_Query query()
 * @method static EO_Tokens_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Tokens_Result getById($id)
 * @method static EO_Tokens_Result getList(array $parameters = [])
 * @method static EO_Tokens_Entity getEntity()
 * @method static \Bitrix\Dav\EO_Tokens createObject($setDefaultValues = true)
 * @method static \Bitrix\Dav\EO_Tokens_Collection createCollection()
 * @method static \Bitrix\Dav\EO_Tokens wakeUpObject($row)
 * @method static \Bitrix\Dav\EO_Tokens_Collection wakeUpCollection($rows)
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
		return [
			new Entity\StringField('TOKEN', [
				'primary' => true,
				'default_value' => Random::getString(static::DEFAULT_TOKEN_LENGTH),
				'title' => Loc::getMessage('TOKEN_FIELD'),
			]),
			new Entity\IntegerField('USER_ID', [
				'required' => true,
				'title' => Loc::getMessage('TOKEN_OWNER_FIELD'),
			]),
			new Entity\DatetimeField('EXPIRED_AT', [
				'default_value' => self::getTokenNewValidTimeInterval(),
				'title' => Loc::getMessage('TOKEN_EXPIRED_AT_FIELD'),
			]),
			new Entity\ReferenceField(
				'USER',
				'Bitrix\Main\UserTable',
				['=this.USER_ID' => 'ref.ID']
			)
		];
	}


	/**
	 * @param int $userId User id.
	 * @return array|false
	 */
	public static function getToken($userId)
	{
		$result = static::getList([
			'filter' => array('USER_ID' => $userId),
		])->fetchRaw();

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
		{
			$params['TOKEN'] = $token;
		}
		if ($expiredAt)
		{
			$params['EXPIRED_AT'] = $expiredAt;
		}

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
		$token = $newToken ?: Random::getString(static::DEFAULT_TOKEN_LENGTH);
		$expiredAt = $expiredAt ?: self::getTokenNewValidTimeInterval();
		static::delete($oldToken);

		return static::createToken($userId, $token, $expiredAt);
	}

	/**
	 * @param string $token Token passed from user for checking.
	 * @return bool
	 */
	public static function isTokenValid($token)
	{
		if (!$token)
		{
			return false;
		}
		$result = static::getById($token)->fetch();

		return $result && $result['EXPIRED_AT'] >= self::getTokenLastValidTime();
	}

	/**
	 * Delete all deprecated tokens, can use in agents
	 * @return void
	 */
	public static function clearDeprecatedTokens()
	{
		$deprecatedTokens = static::getList([
			'select' => ['TOKEN'],
			'filter' => ['<EXPIRED_AT' => self::getTokenLastValidTime()]
		]);
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
		return new DateTime();
	}

	/**
	 * @return \Bitrix\Main\Type\DateTime
	 */
	private static function getTokenNewValidTimeInterval()
	{
		return (new DateTime())->add(static::TOKEN_AVAILABLE_INTERVAL);
	}
}
