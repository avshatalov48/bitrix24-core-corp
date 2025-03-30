<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Diag\SqlTrackerQuery;
use Bitrix\Main\SystemException;

final class Diag
{
	const SQL_SKIP        = 0x000;
	const SQL_COUNT       = 0x001;
	const SQL_PRINT_ALL   = 0x010;
	const SQL_DETECT_LIKE = 0x100;

	const MEMORY_SKIP             = 0x000;
	const MEMORY_PRINT_AMOUNT     = 0x001;
	const MEMORY_PRINT_DIFF       = 0x010;
	const MEMORY_PRINT_PEAK_USAGE = 0x100;

	private $sqlBehavior = 0;
	private $memoryBehavior = 0;
	private $enableTimeTracker = 0;
	private $enableErrorHandler = 0;
	private $showOnDisplay = 0;
	private $exclusiveUserId = null;
	private $filePathPatternToCatchError = '%/disk/%';

	/** @var  Diag */
	private static $instance;
	private $prevErrorReporting;
	private $levelReporting;
	private $stackSql = array();
	private $stackMemory = array();
	private $summarySqlCount = 0;
	private $summaryTime = 0;
	/** @var Connection connection */
	private $connection;

	private function __construct()
	{
		$this->sqlBehavior = self::SQL_SKIP;
		$this->memoryBehavior = self::MEMORY_SKIP;
		$this->levelReporting = E_ALL;
		$this->connection = Application::getInstance()->getConnection();
		$this->registerShutdownFunction();
	}

	/**
	 * Gets instance of Diag.
	 * @return Diag
	 */
	public static function getInstance()
	{
		if(!isset(self::$instance))
		{
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Sets sql behavior.
	 * @param int $sqlBehavior Sql behavior.
	 * @return $this
	 */
	public function setSqlBehavior($sqlBehavior)
	{
		$this->sqlBehavior = $sqlBehavior;

		return $this;
	}

	/**
	 * Sets memory behavior.
	 * @param int $memoryBehavior Memory behavior.
	 * @return $this
	 */
	public function setMemoryBehavior($memoryBehavior)
	{
		$this->memoryBehavior = $memoryBehavior;

		return $this;
	}

	/**
	 * Sets value to time tracker.
	 * @param bool $enableTimeTracker Value.
	 * @return $this
	 */
	public function setEnableTimeTracker($enableTimeTracker)
	{
		$this->enableTimeTracker = $enableTimeTracker;

		return $this;
	}

	/**
	 * Sets value to status of enabling error handler.
	 * @param bool $enableErrorHandler Value.
	 * @return $this
	 */
	public function setEnableErrorHandler($enableErrorHandler)
	{
		$this->enableErrorHandler = $enableErrorHandler;

		return $this;
	}

	/**
	 * Sets pattern which uses to catch error in error handler.
	 * @param string $filePathPatternToCatchError Pattern.
	 */
	public function setFilePathPatternToCatchError($filePathPatternToCatchError)
	{
		$this->filePathPatternToCatchError = $filePathPatternToCatchError;
	}

	/**
	 * Sets value to status of show log message on display.
	 * @param bool $showOnDisplay Value.
	 * @return $this
	 */
	public function setShowOnDisplay($showOnDisplay)
	{
		$this->showOnDisplay = $showOnDisplay;

		return $this;
	}

	/**
	 * Sets user id who can use class Diag. If set null then everybody can use it.
	 * @param int $exclusiveUserId Id of user.
	 * @return $this
	 */
	public function setExclusiveUserId($exclusiveUserId)
	{
		$this->exclusiveUserId = $exclusiveUserId;

		return $this;
	}

	/**
	 * Collects debug info (sql queries, errors, etc).
	 * @param mixed $uniqueId Id of segment.
	 * @param null  $label Label for human.
	 * @return void
	 */
	public function collectDebugInfo($uniqueId, $label = null)
	{
		if($this->exclusiveUserId !== null && $this->getUser()->getId() != $this->exclusiveUserId)
		{
			return;
		}
		if($this->enableTimeTracker)
		{
			Debug::startTimeLabel($uniqueId);
		}
		if($this->enableErrorHandler)
		{
			$this->prevErrorReporting = error_reporting();
			error_reporting($this->levelReporting);
			set_error_handler(function ($code, $message, $file, $line, $context = null)
			{
				if($this->filePathPatternToCatchError && preg_match($this->filePathPatternToCatchError, $file))
				{
					if(preg_match('%Non-static method C[A-Z][\w]+::%', $message))
					{
						//it's old style in old kernel. There aren't static in method.
						return;
					}
					$backtrace = debug_backtrace();
					$this->log(array(
						$code,
						$message,
						$file,
						$line
					));
				}
			}, $this->levelReporting);
		}
		if($this->sqlBehavior & (self::SQL_COUNT | self::SQL_DETECT_LIKE | self::SQL_PRINT_ALL))
		{
			if(empty($this->stackSql))
			{
				$this->connection->startTracker(true);
				array_push($this->stackSql, array($uniqueId, 0, array()));
			}
			else
			{
				list($prevLabel, $prevLabelCount, $prevSqlTrackerQueries) = array_pop($this->stackSql);
				list($countQueries, $sqlTrackerQueries) = $this->getDebugInfoSql();
				array_push($this->stackSql, array($prevLabel, $countQueries + $prevLabelCount, array_merge($prevSqlTrackerQueries, $sqlTrackerQueries)));

				$this->connection->startTracker(true);
				array_push($this->stackSql, array($uniqueId, 0, array()));
			}
		}
		if($this->memoryBehavior & self::MEMORY_PRINT_DIFF)
		{
			array_push($this->stackMemory, array($uniqueId, memory_get_usage(true)));
		}
	}

	private function getDebugInfoSql()
	{
		$tracker = $this->connection->getTracker();
		if(!$tracker)
		{
			return null;
		}

		$sqlTrackerQueries = $tracker->getQueries();

		return array(count($sqlTrackerQueries), $sqlTrackerQueries);
	}

	/**
	 * Logs debug info (sql queries, errors, etc).
	 * @param mixed $uniqueId Id of segment.
	 * @param null  $label Label for human.
	 * @throws SystemException
	 * @return void
	 */
	public function logDebugInfo($uniqueId, $label = null)
	{
		if($label === null)
		{
			$label = $uniqueId;
		}

		if($this->exclusiveUserId !== null && $this->getUser()->getId() != $this->exclusiveUserId)
		{
			return;
		}

		$debugData = array();
		if($this->enableTimeTracker)
		{
			Debug::endTimeLabel($uniqueId);
			$timeLabels = Debug::getTimeLabels();
			$debugData[] = "Time: {$timeLabels[$uniqueId]['time']}";
			$this->summaryTime += $timeLabels[$uniqueId]['time'];
		}
		if($this->sqlBehavior & (self::SQL_COUNT | self::SQL_DETECT_LIKE | self::SQL_PRINT_ALL))
		{
			list($prevLabel, $prevLabelCount, $prevSqlTrackerQueries) = array_pop($this->stackSql);

			list($countQueries, $sqlTrackerQueries) = $this->getDebugInfoSql();
			if($countQueries === null)
			{
				$sqlTrackerQueries = array();
				$debugData[] = 'COULD NOT GET SQL TRACKER!';
			}
			else
			{
				if($prevLabel === $uniqueId)
				{
					$countQueries += $prevLabelCount;
					$sqlTrackerQueries = array_merge($prevSqlTrackerQueries, $sqlTrackerQueries);
				}

				if($this->sqlBehavior & self::SQL_COUNT)
				{
					$this->summarySqlCount += $countQueries;
					$debugData[] = 'Count sql: ' . $countQueries;
				}
			}
		}
		if($this->sqlBehavior & (self::SQL_COUNT | self::SQL_DETECT_LIKE | self::SQL_PRINT_ALL))
		{
			/** @var SqlTrackerQuery[] $sqlTrackerQueries */
			foreach($sqlTrackerQueries as $query)
			{
				if($this->sqlBehavior & self::SQL_PRINT_ALL)
				{
					$debugData[] = array(
						$query->getTime(),
						$query->getSql(),
						$this->reformatBackTrace($query->getTrace())
					);
				}

				if(($this->sqlBehavior & self::SQL_DETECT_LIKE) && mb_stripos($query->getSql(), 'upper') !== false)
				{
					$this->log(array(
						'Oh... LIKE UPPER... Delete! Destroy!',
						$this->reformatBackTrace($query->getTrace()),
					));
					throw new SystemException('Oh... LIKE UPPER... Delete! Destroy!');
				}
			}
			unset($query);
		}
		if($this->enableErrorHandler)
		{
			error_reporting($this->prevErrorReporting);
			restore_error_handler();
		}
		if($this->memoryBehavior & self::MEMORY_PRINT_DIFF)
		{
			list($prevLabel, $prevMemoryStart) = array_pop($this->stackMemory);
			if($prevLabel === $uniqueId)
			{
				$debugData[] = 'Memory start: ' . $this->formatSize($prevMemoryStart);
				$debugData[] = 'Memory diff: ' . $this->formatSize(memory_get_usage(true) - $prevMemoryStart);
			}
			//$debugData[] = 'Memory: ' . round(memory_get_usage(true) / 1024, 2) . ' Kb';
		}
		if($this->memoryBehavior & self::MEMORY_PRINT_AMOUNT)
		{
			$debugData[] = 'Memory amount: ' . $this->formatSize(memory_get_usage(true));
		}
		if($this->memoryBehavior & self::MEMORY_PRINT_PEAK_USAGE)
		{
			$debugData[] = 'Memory peak usage: ' . $this->formatSize(memory_get_peak_usage(true));
		}
		if($debugData)
		{
			array_unshift($debugData, "Label: {$label}");
			$this->log($debugData);
		}
	}

	/**
	 * Logs data in common log (@see AddMessage2Log).
	 * @param mixed $data Mixed data to log.
	 * @return void
	 */
	public function log($data)
	{
		$this->showOnDisplay && var_dump($data);
		AddMessage2Log(var_export($data, true), 'disk', 0);
	}

	private function reformatBackTrace(array $backtrace)
	{
		$functionStack = $filesStack = '';
		for($i = 1; $i < count($backtrace); $i++)
		{
			if($functionStack <> '')
			{
				$functionStack .= " < ";
			}

			if(isset($backtrace[$i]["class"]))
			{
				$functionStack .= $backtrace[$i]["class"] . "::";
			}

			$functionStack .= $backtrace[$i]["function"];

			if(isset($backtrace[$i]["file"]))
			{
				$filesStack .= "\t" . $backtrace[$i]["file"] . ":" . $backtrace[$i]["line"] . "\n";
			}
		}

		return $functionStack . "\n" . $filesStack;
	}

	private function getLinkToEditor($filepath, $line = 0)
	{
		return "<a href=\"editor://open/?file=" . urlencode($filepath) . "&line={$line}\">{$filepath}</a>";
	}

	/**
	 * @param int $size
	 * @param int $precision
	 * @return string
	 */
	private function formatSize($size, $precision = 2)
	{
		$suffix = array('b', 'Kb', 'Mb', 'Gb', 'Tb');
		$pos = 0;
		while($size >= 1024 && $pos < 4)
		{
			$size /= 1024;
			$pos++;
		}

		return round($size, $precision) . ' ' . $suffix[$pos];
	}

	/**
	 * @return array|bool|\CUser
	 */
	private function getUser()
	{
		global $USER;
		return $USER;
	}

	private function registerShutdownFunction()
	{
		$shutdown = function() {
			if (
				(
					($this->sqlBehavior & (self::SQL_COUNT | self::SQL_DETECT_LIKE | self::SQL_PRINT_ALL)) ||
					($this->enableTimeTracker)
				) &&
				($this->summarySqlCount > 0 && $this->summaryTime > 0)
			)
			{
				$this->log(
					[
						'Sql count' => $this->summarySqlCount,
						'Total time' => $this->summaryTime,
					]
				);

			}
		};
		register_shutdown_function($shutdown->bindTo($this, $this));
	}
}