<?

/**
 * Class CSecuritySessionRedis
 * @deprecated
 * @see \Bitrix\Main\Session\Handlers\RedisSessionHandler
 */
class CSecuritySessionRedis
{
	/** @var Redis $connection*/
	protected static $connection = null;
	protected static $sessionId = null;
	protected static $isReadOnly = false;
	protected static $isSessionReady = false;
	protected static $hasFailedRead = false;
	/**
	 * @return bool
	 */
	public static function Init()
	{
		if(self::isConnected())
		{
			return true;
		}

		self::$isReadOnly = defined('BX_SECURITY_SESSION_READONLY');

		return self::newConnection();
	}

	/**
	 * @param string $savePath - unused on this handler
	 * @param string $sessionName - unused on this handler
	 * @return bool
	 */
	public static function open($savePath, $sessionName)
	{
		return CSecuritySessionRedis::Init();
	}

	/**
	 * @return bool
	 */
	public static function close()
	{
		if(!self::isConnected() || !self::isValidId(self::$sessionId))
			return false;

		if (!self::$isReadOnly && self::$isSessionReady)
		{
			if(isSessionExpired())
			{
				self::destroy(self::$sessionId);
			}

			self::$connection->delete(self::getPrefix().self::$sessionId.".lock");
		}

		self::$sessionId = null;
		self::closeConnection();
		return true;
	}

	/**
	 * @param string $id - session id, must be valid hash
	 * @return string
	 */
	public static function read($id)
	{
		if(!self::isConnected() || !self::isValidId($id))
		{
			return "";
		}

		$sid = self::getPrefix();

		if (!self::$isReadOnly)
		{
			$lockTimeout = 55;//TODO: add setting
			$lockWait = 59000000;//micro seconds = 60 seconds TODO: add setting
			$waitStep = 100;

			if (defined('BX_SECURITY_SESSION_REDIS_EXLOCK') && BX_SECURITY_SESSION_REDIS_EXLOCK)
			{
				$lock = Bitrix\Main\Context::getCurrent()->getRequest()->getRequestedPage();
			}
			else
			{
				$lock = 1;
			}

			while(!self::$connection->setnx($sid.$id.'.lock', $lock))
			{
				usleep($waitStep);
				$lockWait -= $waitStep;
				if($lockWait < 0)
				{
					$errorText = 'Unable to get session lock within 60 seconds.';
					if ($lock !== 1)
					{
						$lockedUri = self::$connection->get($sid.$id.".lock");
						if ($lockedUri && $lockedUri != 1)
							$errorText .= sprintf(' Locked by "%s".', $lockedUri);
					}

					CSecuritySession::triggerFatalError($errorText);
				}

				if($waitStep < 1000000)
					$waitStep *= 2;
			}
			self::$connection->expire($sid.$id.".lock", $lockTimeout);
		}

		self::$sessionId = $id;
		self::$isSessionReady = true;
		$res = self::$connection->get($sid.$id);

		if($res === false)
		{
			if (!self::$hasFailedRead)
			{
				AddEventHandler("main", "OnPageStart", array("CSecuritySession", "UpdateSessID"));
				self::$hasFailedRead = true;
			}
			$res = "";
		}

		return $res;
	}

	/**
	 * @param string $id - session id, must be valid hash
	 * @param string $sessionData
	 * @return bool
	 */
	public static function write($id, $sessionData)
	{
		if(!self::isConnected() || !self::isValidId($id))
			return false;

		if (!self::$isSessionReady)
			return false;

		if (self::$isReadOnly)
		{
			if (!CSecuritySession::isOldSessionIdExist())
			{
				return true;
			}
		}

		$sid = self::getPrefix();
		$maxLifetime = intval(ini_get("session.gc_maxlifetime"));

		if(CSecuritySession::isOldSessionIdExist())
		{
			$oldSessionId = CSecuritySession::getOldSessionId(true);
			self::$connection->delete($sid.$oldSessionId);
		}

		self::$connection->setex($sid.$id, $maxLifetime, $sessionData);

		return true;
	}

	/**
	 * @param string $id - session id, must be valid hash
	 * @return bool
	 */
	public static function destroy($id)
	{
		if(!self::isValidId($id))
			return false;

		if (!self::$isSessionReady)
			return false;

		if (self::$isReadOnly)
			return false;

		$isConnectionRestored = false;
		if(!self::isConnected())
			$isConnectionRestored = self::newConnection();

		if(!self::isConnected())
			return false;

		$sid = self::getPrefix();
		self::$connection->delete($sid.$id);

		if(CSecuritySession::isOldSessionIdExist())
			self::$connection->delete($sid.CSecuritySession::getOldSessionId(true));

		if($isConnectionRestored)
			self::closeConnection();

		return true;
	}

	/**
	 * @param int $maxLifeTime - unused on this handler
	 * @return bool
	 */
	public static function gc($maxLifeTime)
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public static function isStorageEnabled()
	{
		return defined("BX_SECURITY_SESSION_REDIS_HOST");
	}

	/**
	 * @return bool
	 */
	protected static function isConnected()
	{
		return self::$connection !== null;
	}

	/**
	 * @param string $pId
	 * @return bool
	 */
	protected static function isValidId($pId)
	{
		return (
			$pId
			&& is_string($pId)
			&& preg_match('/^[\da-z\-,]{6,}$/iD', $pId)
		);
	}

	/**
	 * @return string
	 */
	protected static function getPrefix()
	{
		return defined("BX_CACHE_SID")? BX_CACHE_SID: "BX";
	}

	/**
	 * Connect to redis server
	 * @return bool
	 */
	protected static function newConnection()
	{
		$result = false;
		$exception = null;

		if (!extension_loaded('redis'))
		{
			$result = false;
			$exception = new \ErrorException("redis extention not loaded.", 0, E_USER_ERROR, __FILE__, __LINE__);
		}

		if (!$exception)
		{
			if (!self::isStorageEnabled())
			{
				$result = false;
				$exception = new \ErrorException("BX_SECURITY_SESSION_REDIS_HOST constant is not defined.", 0, E_USER_ERROR, __FILE__, __LINE__);
			}
		}

		if (!$exception)
		{
			$port = defined("BX_SECURITY_SESSION_REDIS_PORT")? intval(BX_SECURITY_SESSION_REDIS_PORT): 11211;
			self::$connection = new \Redis();
			$result = self::$connection->pconnect(BX_SECURITY_SESSION_REDIS_HOST, $port);

			if ($result)
			{
				self::$connection->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY);
			}
			else
			{
				$error = error_get_last();
				if ($error && $error["type"] == E_WARNING)
				{
					$exception = new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
				}
			}
		}

		if ($exception)
		{
			$application = \Bitrix\Main\Application::getInstance();
			$exceptionHandler = $application->getExceptionHandler();
			$exceptionHandler->writeToLog($exception);
		}

		return $result;
	}

	protected static function closeConnection()
	{
		self::$connection->close();
		self::$connection = null;
	}

}