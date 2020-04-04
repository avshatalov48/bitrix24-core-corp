<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item;

use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

final class Context
{
	protected $userId = null;
	protected $now = null;
	protected $immutable = false;
	protected static $defaultContexts = array();

	/**
	 * Who
	 *
	 * @return int
	 */
	public function getUserId()
	{
		if(intval($this->userId))
		{
			return intval($this->userId);
		}

		return User::getId();
	}

	/**
	 * On which site
	 *
	 * @return DateTime|null
	 */
	public function getSiteId()
	{
		return SITE_ID;
	}

	/**
	 * At which time
	 *
	 * @return DateTime|null
	 */
	public function getNow()
	{
		if($this->now !== null)
		{
			return $this->now;
		}

		return new DateTime();
	}

	public function setUserId($userId)
	{
		$this->userId = intval($userId);
	}

	public function setNow(DateTime $now)
	{
		if($this->isImmutable())
		{
			return; // todo: throw NotAllowedException?
		}

		// todo: it is better to make $now immutable...
		$this->now = $now;
	}

	/**
	 * Normally you SHOULD NOT be able to modify default context, so immutable flag is at our rescue
	 */
	public function setImmutable()
	{
		$this->immutable = true;
	}

	public function isImmutable()
	{
		return $this->immutable;
	}

	/**
	 * @return Context
	 */
	public function spawn()
	{
		return new static();
	}

	/**
	 * @return static mixed
	 */
	public static function getDefault()
	{
		$class = static::getClass();

		if(!static::$defaultContexts[$class])
		{
			// default context should be immutable, or else we will face disaster!
			$ctx = new static();
			$ctx->setImmutable();

			static::$defaultContexts[$class] = $ctx;
		}

		return static::$defaultContexts[$class];
	}

	public static function getClass()
	{
		return get_called_class();
	}

	public static function isA($object)
	{
		return is_a($object, static::getClass());
	}
}