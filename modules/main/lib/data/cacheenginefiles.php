<?php
namespace Bitrix\Main\Data;

use Bitrix\Main;

class CacheEngineFiles
	implements ICacheEngine, ICacheEngineStat
{
	private $TTL;

	//cache stats
	private $written = false;
	private $read = false;
	private $path = '';

	protected $useLock = false;
	protected static $lockHandles = array();

	/**
	 * Engine constructor.
	 * @param array $options Cache options.
	 */
	public function __construct($options = [])
	{
		$config = Main\Config\Configuration::getValue("cache");
		if ($config && is_array($config) && isset($config["use_lock"]))
		{
			$this->useLock = (bool)$config["use_lock"];
		}

		if (!empty($options) && isset($options['actual_data']))
		{
			$this->useLock = !((bool) $options['actual_data']);
		}
	}

	/**
	 * Returns number of bytes read from disk or false if there was no read operation.
	 *
	 * @return integer|false
	 */
	public function getReadBytes()
	{
		return $this->read;
	}

	/**
	 * Returns number of bytes written to disk or false if there was no write operation.
	 *
	 * @return integer|false
	 */
	public function getWrittenBytes()
	{
		return $this->written;
	}

	/**
	 * Returns physical file path after read or write operation.
	 *
	 * @return string
	 */
	public function getCachePath()
	{
		return $this->path;
	}

	/**
	 * Returns true if cache can be read or written.
	 *
	 * @return bool
	 */
	public function isAvailable()
	{
		return true;
	}

	/**
	 * Deletes physical file. Returns true on success.
	 *
	 * @param string $fileName Absolute physical path.
	 *
	 * @return boolean
	 */
	private static function unlink($fileName)
	{
		if (isset(self::$lockHandles[$fileName]) && self::$lockHandles[$fileName])
		{
			fclose(self::$lockHandles[$fileName]);
			unset(self::$lockHandles[$fileName]);
		}

		if (file_exists($fileName))
		{
			@chmod($fileName, BX_FILE_PERMISSIONS);
			if (@unlink($fileName))
				return true;
		}

		return false;
	}

	/**
	 * Adds delayed delete worker agent.
	 *
	 * @return void
	 */
	private static function addAgent()
	{
		global $APPLICATION;

		static $agentAdded = false;
		if (!$agentAdded)
		{
			$agentAdded = true;
			$agents = \CAgent::GetList(array("ID" => "DESC"), array("NAME" => "\\Bitrix\\Main\\Data\\CacheEngineFiles::delayedDelete(%"));
			if (!$agents->Fetch())
			{
				$res = \CAgent::AddAgent(
					"\\Bitrix\\Main\\Data\\CacheEngineFiles::delayedDelete();",
					"main", //module
					"Y", //period
					1 //interval
				);

				if (!$res)
					$APPLICATION->ResetException();
			}
		}
	}

	/**
	 * Generates very temporary file name by adding some random suffix to the file path.
	 * Returns empty string on failure.
	 *
	 * @param string $fileName File path within document root.
	 *
	 * @return string
	 */
	private static function randomizeFile($fileName)
	{
		$documentRoot = Main\Loader::getDocumentRoot();
		for ($i = 0; $i < 99; $i++)
		{
			$suffix = rand(0, 999999);
			if (!file_exists($documentRoot.$fileName.$suffix))
				return $fileName.$suffix;
		}
		return "";
	}

	/**
	 * Cleans (removes) cache directory or file.
	 *
	 * @param string $baseDir Base cache directory (usually /bitrix/cache).
	 * @param string $initDir Directory within base.
	 * @param string $filename File name.
	 *
	 * @return void
	 */
	public function clean($baseDir, $initDir = '', $filename = '')
	{
		$documentRoot = Main\Loader::getDocumentRoot();
		if (($filename !== false) && ($filename !== ""))
		{
			$result = static::unlink($documentRoot.$baseDir.$initDir.$filename);
			if ($result)
			{
				Main\Application::resetAccelerator();
			}
		}
		else
		{
			$initDir = trim($initDir, "/");
			if ($initDir == "")
			{
				$sourceDir = $documentRoot."/".trim($baseDir, "/");
				if (file_exists($sourceDir) && is_dir($sourceDir))
				{
					$dh = opendir($sourceDir);
					if (is_resource($dh))
					{
						while ($entry = readdir($dh))
						{
							if (preg_match("/^(\\.|\\.\\.|.*\\.~\\d+)\$/", $entry))
								continue;

							if (is_dir($sourceDir."/".$entry))
								static::clean($baseDir, $entry);
							elseif (is_file($sourceDir."/".$entry))
								static::unlink($sourceDir."/".$entry);
						}
					}
				}
			}
			else
			{
				$source = "/".trim($baseDir, "/")."/".$initDir;
				$source = rtrim($source, "/");
				$delayedDelete = false;

				if (!preg_match("/^(\\.|\\.\\.|.*\\.~\\d+)\$/", $source) && file_exists($documentRoot.$source))
				{
					if (is_file($documentRoot.$source))
					{
						static::unlink($documentRoot.$source);
					}
					else
					{
						$target = static::randomizeFile($source.".~");
						if ($target != '')
						{
							$con = Main\Application::getConnection();
							$con->queryExecute("INSERT INTO b_cache_tag (SITE_ID, CACHE_SALT, RELATIVE_PATH, TAG) VALUES ('*', '*', '".$con->getSqlHelper()->forSql($target)."', '*')");
							if (@rename($documentRoot.$source, $documentRoot.$target))
							{
								$delayedDelete = true;
							}
						}
					}
				}

				if ($delayedDelete)
					static::addAgent();
				else
					DeleteDirFilesEx($baseDir.$initDir);

				Main\Application::resetAccelerator();
			}
		}
	}

	/**
	 * Tries to put non blocking exclusive lock on the file.
	 * Returns true if file not exists, or lock was successfully got.
	 *
	 * @param string $fileName Absolute cache file path.
	 *
	 * @return boolean
	 */
	protected function lock($fileName)
	{

		$wouldBlock = 0;
		self::$lockHandles[$fileName] = @fopen($fileName, "r+");
		if (self::$lockHandles[$fileName])
		{
			flock(self::$lockHandles[$fileName], LOCK_EX | LOCK_NB, $wouldBlock);
			//$wouldBlock === 1 someone else has the lock.
		}
		return $wouldBlock !== 1;
	}

	/**
	 * Releases the lock obtained by lock method.
	 *
	 * @param string $fileName Absolute cache file path.
	 *
	 * @return void
	 */
	protected function unlock($fileName)
	{
		if (self::$lockHandles[$fileName])
		{
			fclose(self::$lockHandles[$fileName]);
			unset(self::$lockHandles[$fileName]);
		}
	}

	/**
	 * Reads cache from the file. Returns true if file exists, not expired, and successfully read.
	 *
	 * @param mixed &$allVars Cached result.
	 * @param string $baseDir Base cache directory (usually /bitrix/cache).
	 * @param string $initDir Directory within base.
	 * @param string $filename File name.
	 * @param integer $TTL Expiration period in seconds.
	 *
	 * @return boolean
	 */
	public function read(&$allVars, $baseDir, $initDir, $filename, $TTL)
	{
		$documentRoot = Main\Loader::getDocumentRoot();
		$fn = $documentRoot."/".ltrim($baseDir.$initDir, "/").$filename;

		if (!file_exists($fn))
			return false;

		$ser_content = "";
		$dateexpire = 0;
		$datecreate = 0;
		$zeroDanger = false;

		$handle = null;
		if (is_array($allVars))
		{
			$INCLUDE_FROM_CACHE = 'Y';

			if (!@include($fn))
				return false;
		}
		else
		{
			$handle = fopen($fn, "rb");
			if (!$handle)
				return false;

			$datecreate = fread($handle, 2);
			if ($datecreate == "BX")
			{
				$datecreate = fread($handle, 12);
				$dateexpire = fread($handle, 12);
			}
			else
			{
				$datecreate .= fread($handle, 10);
			}
		}

		/* We suppress warning here in order not to break the compression under Zend Server */
		$this->read = @filesize($fn);
		$this->path = $fn;

		$res = true;
		if (intval($datecreate) < (time() - $TTL))
		{
			if ($this->useLock)
			{
				if ($this->lock($fn))
				{
					$res = false;
				}
			}
			else
			{
				$res = false;
			}
		}

		if($res == true)
		{
			if (is_array($allVars))
			{
				$allVars = unserialize($ser_content);
			}
			else
			{
				$allVars = fread($handle, $this->read);
			}
		}

		if($handle)
		{
			fclose($handle);
		}

		return $res;
	}

	/**
	 * Writes cache into the file.
	 *
	 * @param mixed $allVars Cached result.
	 * @param string $baseDir Base cache directory (usually /bitrix/cache).
	 * @param string $initDir Directory within base.
	 * @param string $filename File name.
	 * @param integer $TTL Expiration period in seconds.
	 *
	 * @return void
	 */
	public function write($allVars, $baseDir, $initDir, $filename, $TTL)
	{
		static $search = array("\\", "'", "\0");
		static $replace = array("\\\\", "\\'", "'.chr(0).'");
		$documentRoot = Main\Loader::getDocumentRoot();
		$folder = $documentRoot."/".ltrim($baseDir.$initDir, "/");
		$fn = $folder.$filename;
		$fnTmp = $folder.md5(mt_rand()).".tmp";

		if (!CheckDirPath($fn))
			return;

		if ($handle = fopen($fnTmp, "wb+"))
		{
			if (is_array($allVars))
			{
				$contents = "<?";
				$contents .= "\nif(\$INCLUDE_FROM_CACHE!='Y')return false;";
				$contents .= "\n\$datecreate = '".str_pad(time(), 12, "0", STR_PAD_LEFT)."';";
				$contents .= "\n\$dateexpire = '".str_pad(time() + intval($TTL), 12, "0", STR_PAD_LEFT)."';";
				$contents .= "\n\$ser_content = '".str_replace($search, $replace, serialize($allVars))."';";
				$contents .= "\nreturn true;";
				$contents .= "\n?>";
			}
			else
			{
				$contents = "BX".str_pad(time(), 12, "0", STR_PAD_LEFT).str_pad(time() + intval($this->TTL), 12, "0", STR_PAD_LEFT);
				$contents .= $allVars;
			}

			$this->written = fwrite($handle, $contents);
			$this->path = $fn;
			$len = strlen($contents);

			fclose($handle);

			static::unlink($fn);

			if ($this->written === $len)
				rename($fnTmp, $fn);

			static::unlink($fnTmp);

			if ($this->useLock)
			{
				$this->unlock($fn);
			}
		}
	}

	/**
	 * Returns true if cache file has expired.
	 *
	 * @param string $path Absolute physical path.
	 *
	 * @return boolean
	 */
	public function isCacheExpired($path)
	{
		if (!file_exists($path))
		{
			return true;
		}

		$fileHandler = fopen($path, "rb");
		if ($fileHandler)
		{
			$header = fread($fileHandler, 150);
			fclose($fileHandler);
		}
		else
		{
			return true;
		}

		if (
			preg_match("/dateexpire\\s*=\\s*'([\\d]+)'/im", $header, $match)
			|| preg_match("/^BX\\d{12}(\\d{12})/", $header, $match)
			|| preg_match("/^(\\d{12})/", $header, $match)
		)
		{
			if ($match[1] == '' || doubleval($match[1]) < time())
				return true;
		}

		return false;
	}

	/**
	 * Deletes one cache directory. Works no longer than etime.
	 *
	 * @param integer $etime Timestamp when to stop working.
	 * @param boolean $ar Record from b_cache_tag.
	 *
	 * @return void
	 */
	protected static function deleteOneDir($etime = 0, $ar = false)
	{
		$deleteFromQueue = false;
		$dirName = Main\Loader::getDocumentRoot().$ar["RELATIVE_PATH"];
		if ($ar["RELATIVE_PATH"] != '' && file_exists($dirName))
		{
			if (is_file($dirName))
			{
				DeleteDirFilesEx($ar["RELATIVE_PATH"]);
				$deleteFromQueue = true;
			}
			else
			{
				$dh = opendir($dirName);
				if (is_resource($dh))
				{
					$counter = 0;
					while (($file = readdir($dh)) !== false)
					{
						if ($file != "." && $file != "..")
						{
							DeleteDirFilesEx($ar["RELATIVE_PATH"]."/".$file);
							$counter++;
							if (time() > $etime)
								break;
						}
					}
					closedir($dh);

					if ($counter == 0)
					{
						rmdir($dirName);
						$deleteFromQueue = true;
					}
				}
			}
		}
		else
		{
			$deleteFromQueue = true;
		}

		if ($deleteFromQueue)
		{
			$con = Main\Application::getConnection();
			$con->queryExecute("DELETE FROM b_cache_tag WHERE ID = ".intval($ar["ID"]));
		}
	}

	/**
	 * Agent function which deletes marked cache directories.
	 *
	 * @param integer $count Desired delete count.
	 *
	 * @return string
	 */
	public static function delayedDelete($count = 1)
	{
		$con = Main\Application::getConnection();

		$etime = time() + 2;
		if ($count > 0)
		{
			$rs = $con->query("SELECT * from b_cache_tag WHERE TAG='*'", 0, $count);
			while ($ar = $rs->fetch())
			{
				static::deleteOneDir($etime, $ar);
				if (time() > $etime)
					break;
			}
		}

		//try to adjust cache cleanup speed to cache cleanups
		$rs = $con->query("SELECT SITE_ID, CACHE_SALT, RELATIVE_PATH, TAG from b_cache_tag WHERE TAG='**'");
		if ($ar = $rs->fetch())
		{
			$statRecFound = true;
			$lastCount = intval($ar["RELATIVE_PATH"]);
			if (preg_match("/:(\\d+)$/", $ar["RELATIVE_PATH"], $m))
				$lastTime = intval($m[1]);
			else
				$lastTime = 0;
		}
		else
		{
			$statRecFound = false;
			$lastCount = 0;
			$lastTime = 0;
		}

		$toDeleteCount = $con->queryScalar("SELECT count(1) CNT from b_cache_tag WHERE TAG='*'");

		$delta = $toDeleteCount - $lastCount;
		if ($delta > 0)
		{
			$timeStep = ($lastTime > 0? time() - $lastTime: 0);
			if ($timeStep <= 0)
				$timeStep = 1;
			$count = intval($toDeleteCount * $timeStep / 3600) + 1; //Rest of the queue in an hour
		}
		elseif ($count < 1)
		{
			$count = 1;
		}

		if ($statRecFound)
		{
			if ($lastCount != $toDeleteCount)
				$con->queryExecute("UPDATE b_cache_tag SET RELATIVE_PATH='".$toDeleteCount.":".time()."' WHERE TAG='**'");
		}
		else
		{
			$con->queryExecute("INSERT INTO b_cache_tag (TAG, RELATIVE_PATH) VALUES ('**', '".$toDeleteCount.":".time()."')");
		}

		if ($toDeleteCount > 0)
			return "\\Bitrix\\Main\\Data\\CacheEngineFiles::delayedDelete(".$count.");";
		else
			return "";
	}
}