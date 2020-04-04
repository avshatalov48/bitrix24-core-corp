<?php

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Disk\Internals\FolderTable;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\Ui\Text;
use Bitrix\Main\Application;
use Bitrix\Disk\ProxyType;
use Bitrix\Main\Config\Option;
use Bitrix\Disk\RightsManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SiteTable;
use Bitrix\Main\IO;

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');
/** @var CAllUser $USER */
/** @var CAllMain $APPLICATION */
global $USER, $APPLICATION;

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}

class SmartMigrationWebdavLogger extends \Bitrix\Main\Diag\FileExceptionHandlerLog
{

	const MAX_LOG_SIZE = 10000000;
	const DEFAULT_LOG_FILE = "bitrix/modules/disk_convertor.log";

	private $logFile;
	private $logFileHistory;

	private $maxLogSize;
	private $level;

	public function initialize(array $options)
	{
		$this->logFile = static::DEFAULT_LOG_FILE;
		if (isset($options["file"]) && !empty($options["file"]))
			$this->logFile = $options["file"];

		$this->logFile = preg_replace("'[\\\\/]+'", "/", $this->logFile);
		if ((substr($this->logFile, 0, 1) !== "/") && !preg_match("#^[a-z]:/#", $this->logFile))
			$this->logFile = Application::getDocumentRoot()."/".$this->logFile;

		$this->logFileHistory = $this->logFile.".old." . time();

		$this->maxLogSize = static::MAX_LOG_SIZE;
		if (isset($options["log_size"]) && ($options["log_size"] > 0))
			$this->maxLogSize = intval($options["log_size"]);

		if (isset($options["level"]) && ($options["level"] > 0))
			$this->level = intval($options["level"]);
	}

	public function writeToLog($text)
	{
		if (empty($text))
			return;

		$logFile = $this->logFile;
		$logFileHistory = $this->logFileHistory;

		$oldAbortStatus = ignore_user_abort(true);

		if ($fp = @fopen($logFile, "ab"))
		{
			if (@flock($fp, LOCK_EX))
			{
				$logSize = @filesize($logFile);
				$logSize = intval($logSize);

				if ($logSize > $this->maxLogSize)
				{
					$this->logFileHistory = $this->logFile.".old." . time();
					$logFileHistory = $this->logFileHistory;
					@copy($logFile, $logFileHistory);
					ftruncate($fp, 0);
				}

				@fwrite($fp, $text);
				@fflush($fp);
				@flock($fp, LOCK_UN);
				@fclose($fp);
			}
		}

		ignore_user_abort($oldAbortStatus);
	}
}

class SmartMigrationWebdav
{
	const RUN_STEPS_WITH_MODIFY_DATA = true;

	const DENY_TASK                  = PHP_INT_MAX;
	const COULD_NOT_FIND_IBLOCK_TASK = -2;

	const STATUS_FINISH       = 2;
	const STATUS_TIME_EXPIRED = 3;
	const STATUS_ERROR        = 4;

	const UF_DISK_FILE_ID     = 'UF_DISK_FILE_ID';
	const UF_DISK_FILE_STATUS = 'UF_DISK_FILE_STATUS';

	const UF_DISK_FOLDER_ID      = 'UF_DISK_FOLDER_ID';
	const UF_DISK_INTO_TRASH     = 'UF_DISK_INTO_TRASH';
	const UF_DISK_STATUS_MIGRATE = 'UF_DISK_ST_MIGRATE';

	const STATUS_MIGRATE_FINAL             = 2;
	const STATUS_MIGRATE_WITHOUT_TRASH     = 3;
	const STATUS_MIGRATE_WITHOUT_STRUCTURE = 4;
	const STATUS_MIGRATE_SKIP              = 5;
	const STATUS_MIGRATE_WITH_FILES        = 6;

	public static $countConvertElements = 0;
	public static $countConvertSections = 0;

	public static $countSuccessfulSteps = 0;

	protected $currentIblockId;
	protected $timeStart = 0;
	/** @var  SmartMigrationWebdavLogger */
	protected $logger;

	/** @var array|null */
	protected $iblockTasks = null;
	/** @var array|null */
	protected $diskTasks = null;
	protected $iblockOperationsByTask;
	protected $diskOperationsByTask;
	/**
	 * Seconds
	 * @var int
	 */
	protected $maxExecution = 38;

	/** \Bitrix\Disk\Storage */
	protected $currentStorage;
	/** @var  Folder[] */
	protected $currentFolderMap;
	protected $runWorkWithBizproc = false;
	/** @var \Bitrix\Main\DB\Connection  */
	protected $connection;
	protected $isOracle = false;
	protected $isMysql = false;
	protected $isMssql = false;
	/** @var \Bitrix\Main\DB\SqlHelper */
	protected $sqlHelper;
	protected $useGZipCompression;
	protected $publishDocs = false;

	public function __construct(array $options = array())
	{
		$this->setTimeStart(time());
		$this->logger = new SmartMigrationWebdavLogger();
		$this->logger->initialize(array());
		$this->connection = Application::getInstance()->getConnection();
		$this->sqlHelper = $this->connection->getSqlHelper();

		$this->isOracle = $this->connection instanceof \Bitrix\Main\DB\OracleConnection;
		$this->isMysql = $this->connection instanceof \Bitrix\Main\DB\MysqlCommonConnection;
		$this->isMssql = $this->connection instanceof \Bitrix\Main\DB\MssqlConnection;

		if(!empty($options['publishDocs']))
		{
			$this->publishDocs = $options['publishDocs'];
		}
		$this->log(array(
			'PublishDocs',
			(bool)$this->publishDocs,
		));
	}


	public function getConcatFunction()
	{
		if(!$this->isMssql)
		{
			return call_user_func_array(array($this->sqlHelper, 'getConcatFunction'), func_get_args());
		}
		$data = array();
		foreach(func_get_args() as $value)
		{
			$data[] = "CAST($value as VARCHAR(255))";
		}
		unset($value);

		return implode(' + ', $data);
	}

	public function log($data)
	{
		if(!is_string($data))
		{
			$data = print_r($data, true);
		}
		$this->logger->writeToLog('Date:' . date('r') . "\n" . $data . "\n\n");
	}

	/**
	 * @param int $timeStart
	 */
	public function setTimeStart($timeStart)
	{
		$this->timeStart = $timeStart;
	}

	/**
	 * @return int
	 */
	public function getTimeStart()
	{
		return $this->timeStart;
	}

	protected function isTimeExpired()
	{
		return (time() - $this->getTimeStart()) > $this->maxExecution;
	}

	/**
	 * Check expired by time and throw exception
	 * @param bool $force
	 * @throws TimeExecutionException
	 */
	protected function abortIfNeeded($force = false)
	{
		if($force || $this->isTimeExpired())
		{
			throw new TimeExecutionException();
		}
	}

	protected function checkRequired()
	{
		if(!CModule::includeModule('disk'))
		{
			throw new Exception('Bad include disk');
		}
		if(!CModule::includeModule('iblock'))
		{
			throw new Exception('Bad include iblock');
		}
		if(!CModule::includeModule('webdav'))
		{
			throw new Exception('Bad include webdav');
		}
		$this->runWorkWithBizproc = CModule::includeModule('bizproc');
	}

	public function setStepFinished($stepName, $description = '')
	{
		$stepName = strtr($stepName, array(':' => ''));
		COption::SetOptionString(
			'disk',
			'~sF' . md5($stepName),
			'Y'
		);

		self::$countSuccessfulSteps++;
		$this->log(array(
			"finished",
			"Step {$stepName}",
			$description,
		));

		return $this;
	}

	public function isStepFinished($stepName, $description = '')
	{
		$stepName = strtr($stepName, array(':' => ''));
		$finished = COption::GetOptionString('disk', '~sF' . md5($stepName), 'N') == 'Y';

		if(!$finished)
		{
			$this->log(array(
				"Start",
				"Step {$stepName}",
				$description,
			));
		}
		else
		{
			self::$countSuccessfulSteps++;
			$this->log(array(
				"Skip",
				"Step {$stepName}",
			));
		}

		return $finished;
	}

	protected function runResorting()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}
		$lastId = $this->getLastIblockId();
		$q = CIBlock::GetList(array("ID"=>"ASC"), array("TYPE" => "library", ">ID" => $lastId));
		if($q)
		{
			while($iblock = $q->fetch())
			{
				$this->abortIfNeeded();

				CIBlockSection::treeReSort($iblock['ID']);
				$this->storeIblockId($iblock['ID']);
			}
		}

		$this->storeIblockId(0);
		$this->setStepFinished(__METHOD__);
	}

	protected function getIblockWithUserFiles()
	{
		static $userIblockIds = array();
		if($userIblockIds)
		{
			return $userIblockIds;
		}

		$q = CIBlock::GetList(array("ID"=>"ASC"), array("CODE" => "user_files%", "TYPE" => "library"));
		if($q)
		{
			while($iblock = $q->fetch())
			{
				$userIblockIds[$iblock['ID']] = $iblock;
			}
		}

		return $userIblockIds;
	}

	protected function getIblockIdsWithGroupFiles()
	{
		static $groupIblockIds = array();
		if($groupIblockIds)
		{
			return $groupIblockIds;
		}

		$q = CIBlock::GetList(array("ID"=>"ASC"), array("CODE" => "group_files%", "TYPE" => "library"));
		if($q)
		{
			while($iblock = $q->fetch())
			{
				$groupIblockIds[$iblock['ID']] = $iblock;
			}
		}

		return $groupIblockIds;
	}

	protected function getIblockIdsWithCommonFiles()
	{
		static $commonIblockIds = array();
		if($commonIblockIds)
		{
			return $commonIblockIds;
		}

		$q = CIBlock::GetList(array('ID' => 'ASC'), array('!CODE' => array('group_files%', 'user_files%'), 'TYPE' => 'library'));
		if($q)
		{
			while($iblock = $q->fetch())
			{
				$commonIblockIds[$iblock['ID']] = $iblock;
			}
		}

		return $commonIblockIds;
	}

	protected function getLibraryIblocks()
	{
		static $iblocks = array();
		if($iblocks)
		{
			return $iblocks;
		}

		$q = CIBlock::GetList(array('ID' => 'ASC'), array('TYPE' => 'library'));
		if($q)
		{
			while($iblock = $q->fetch())
			{
				$iblocks[$iblock['ID']] = $iblock;
			}
		}

		return $iblocks;
	}

	protected function moveUserStorageFromIblock(array $iblock)
	{
		$this->log(array(
			__METHOD__,
			'start',
			$iblock['ID'],
		));

		$sqlHelper = $this->connection->getSqlHelper();

		$iblockId = (int)$iblock['ID'];
		$siteId = $sqlHelper->forSql($iblock['LID']);
		$proxyType = $sqlHelper->forSql(ProxyType\User::className());

		if($this->isMysql)
		{
			$sql = "
				INSERT IGNORE INTO b_disk_storage (NAME, MODULE_ID, ENTITY_TYPE, ENTITY_ID, ENTITY_MISC_DATA, ROOT_OBJECT_ID, USE_INTERNAL_RIGHTS, SITE_ID, XML_ID)
				SELECT NAME, 'disk', '{$proxyType}', " . $this->sqlHelper->getIsNullFunction('CREATED_BY', 0) . ", null, ID, 1, '{$siteId}', ID  FROM b_iblock_section WHERE IBLOCK_ID = {$iblockId} AND (IBLOCK_SECTION_ID = 0 OR IBLOCK_SECTION_ID IS NULL)
			";
		}
		if($this->isOracle || $this->isMssql)
		{
			$sql = "
				INSERT INTO b_disk_storage (NAME, MODULE_ID, ENTITY_TYPE, ENTITY_ID, ENTITY_MISC_DATA, ROOT_OBJECT_ID, USE_INTERNAL_RIGHTS, SITE_ID, XML_ID)
					SELECT NAME, 'disk', '{$proxyType}', " . $this->sqlHelper->getIsNullFunction('CREATED_BY', 0) . ", null, ID, 1, '{$siteId}', ID  FROM b_iblock_section IBLOCK
					WHERE
						IBLOCK_ID = {$iblockId} AND (IBLOCK_SECTION_ID = 0 OR IBLOCK_SECTION_ID IS NULL)
						AND NOT EXISTS(SELECT * FROM b_disk_storage WHERE MODULE_ID = 'disk' AND ENTITY_TYPE = '{$proxyType}' AND ENTITY_ID = " . $sqlHelper->getIsNullFunction('CREATED_BY', 0) . ")
			";
		}

		$this->connection->queryExecute($sql);

		if($this->isMssql)
		{
			$this->connection->queryExecute('SET IDENTITY_INSERT b_disk_object ON');
		}

		$this->connection->queryExecute("
			INSERT INTO b_disk_object (ID, REAL_OBJECT_ID, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_SECTION_ID, WEBDAV_IBLOCK_ID)
			SELECT sec.ID, sec.ID, sec.NAME, 2, null, storage.ID, null, sec.DATE_CREATE, sec.DATE_CREATE, sec.DATE_CREATE, sec.CREATED_BY, null, sec.ID, sec.ID, sec.IBLOCK_ID
			FROM b_iblock_section sec 
				INNER JOIN b_disk_storage storage ON storage.ROOT_OBJECT_ID = sec.ID 
			WHERE sec.IBLOCK_ID = {$iblockId} AND (sec.IBLOCK_SECTION_ID = 0 OR sec.IBLOCK_SECTION_ID IS NULL)
		");

		$this->connection->queryExecute("
			INSERT INTO b_disk_object (ID, REAL_OBJECT_ID, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_SECTION_ID, WEBDAV_IBLOCK_ID)
			SELECT sec.ID, sec.ID, " . $this->getConcatFunction('sec.NAME', "'_'", 'ib.LID') . ", 2, 'FROM_SITE_MOVED', ds.ID, ds.ROOT_OBJECT_ID, sec.DATE_CREATE, sec.DATE_CREATE, sec.DATE_CREATE, sec.CREATED_BY, null, sec.ID, sec.ID, sec.IBLOCK_ID
			FROM b_iblock_section sec 
				INNER JOIN b_disk_storage ds ON ds.ENTITY_ID = sec.CREATED_BY AND ds.ENTITY_TYPE='{$proxyType}'
				INNER JOIN b_iblock ib ON sec.IBLOCK_ID=ib.ID
			WHERE sec.IBLOCK_ID = {$iblockId} AND (sec.IBLOCK_SECTION_ID = 0 OR sec.IBLOCK_SECTION_ID IS NULL)
				AND  NOT EXISTS(SELECT 'x' FROM b_disk_storage ds1 WHERE ds1.ROOT_OBJECT_ID=sec.ID)
		");
		if($this->isMssql)
		{
			$this->connection->queryExecute('SET IDENTITY_INSERT b_disk_object OFF');
		}


		$this->log(array(
			__METHOD__,
			'finish',
			$iblock['ID'],
		));
	}

	protected function moveGroupStorageFromIblock(array $iblock)
	{
		$this->log(array(
			__METHOD__,
			'start',
			$iblock['ID'],
		));

		$iblockId = (int)$iblock['ID'];
		$sqlHelper = $this->sqlHelper;
		$siteId = $sqlHelper->forSql($iblock['LID']);
		$proxyType = $sqlHelper->forSql(ProxyType\Group::className());

		if($this->isMysql)
		{
			$sql = "
				INSERT IGNORE INTO b_disk_storage (NAME, MODULE_ID, ENTITY_TYPE, ENTITY_ID, ENTITY_MISC_DATA, ROOT_OBJECT_ID, USE_INTERNAL_RIGHTS, SITE_ID, XML_ID)
				SELECT NAME, 'disk', '{$proxyType}', SOCNET_GROUP_ID, null, ID, 1, null, ID
				FROM b_iblock_section sec
				WHERE IBLOCK_ID = {$iblockId} AND SOCNET_GROUP_ID IS NOT NULL AND (IBLOCK_SECTION_ID = 0 OR IBLOCK_SECTION_ID IS NULL)
					AND EXISTS(SELECT * FROM b_sonet_group sg WHERE sg.ID=sec.SOCNET_GROUP_ID )
			";
		}
		if($this->isOracle || $this->isMssql)
		{
			$sql = "
				INSERT INTO b_disk_storage (NAME, MODULE_ID, ENTITY_TYPE, ENTITY_ID, ENTITY_MISC_DATA, ROOT_OBJECT_ID, USE_INTERNAL_RIGHTS, SITE_ID, XML_ID)
				SELECT NAME, 'disk', '{$proxyType}', SOCNET_GROUP_ID, null, ID, 1, null, ID
				FROM b_iblock_section sec
				WHERE IBLOCK_ID = {$iblockId} AND SOCNET_GROUP_ID IS NOT NULL AND (IBLOCK_SECTION_ID = 0 OR IBLOCK_SECTION_ID IS NULL)
					AND EXISTS(SELECT * FROM b_sonet_group sg WHERE sg.ID=sec.SOCNET_GROUP_ID )
					AND NOT EXISTS(SELECT * FROM b_disk_storage WHERE MODULE_ID = 'disk' AND ENTITY_TYPE = '{$proxyType}' AND ENTITY_ID = " . $sqlHelper->getIsNullFunction('SOCNET_GROUP_ID', 0) . ")
			";
		}

		$this->connection->queryExecute($sql);

		if($this->isMssql)
		{
			$this->connection->queryExecute('SET IDENTITY_INSERT b_disk_object ON');
		}

		$this->connection->queryExecute("
			INSERT INTO b_disk_object (ID, REAL_OBJECT_ID, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_SECTION_ID, WEBDAV_IBLOCK_ID)
			SELECT sec.ID, sec.ID,  sec.NAME, 2, null, storage.ID, null, sec.DATE_CREATE, sec.DATE_CREATE, sec.DATE_CREATE, " . $sqlHelper->getIsNullFunction('sec.CREATED_BY', 0) . ", null, sec.ID, sec.ID, sec.IBLOCK_ID
			FROM b_iblock_section sec 
				INNER JOIN b_disk_storage storage ON storage.ROOT_OBJECT_ID = sec.ID 
			WHERE sec.IBLOCK_ID = {$iblockId} AND SOCNET_GROUP_ID IS NOT NULL AND (sec.IBLOCK_SECTION_ID = 0 OR sec.IBLOCK_SECTION_ID IS NULL)
		");

		$this->connection->queryExecute("
			INSERT INTO b_disk_object (ID, REAL_OBJECT_ID, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_SECTION_ID, WEBDAV_IBLOCK_ID)
			SELECT sec.ID, sec.ID, " . $this->getConcatFunction('sec.NAME', "'_'", 'ib.LID') . ", 2, 'FROM_SITE_MOVED', ds.ID, ds.ROOT_OBJECT_ID, sec.DATE_CREATE, sec.DATE_CREATE, sec.DATE_CREATE, " . $sqlHelper->getIsNullFunction('sec.CREATED_BY', 0) . ", null, sec.ID, sec.ID, sec.IBLOCK_ID
			FROM b_iblock_section sec 
				INNER JOIN b_disk_storage ds ON ds.ENTITY_ID = sec.SOCNET_GROUP_ID AND ds.ENTITY_TYPE='{$proxyType}'
				INNER JOIN b_iblock ib ON sec.IBLOCK_ID=ib.ID
				INNER JOIN b_sonet_group sg ON sg.ID=sec.SOCNET_GROUP_ID 
			WHERE sec.IBLOCK_ID = {$iblockId} AND sec.SOCNET_GROUP_ID IS NOT NULL AND (sec.IBLOCK_SECTION_ID = 0 OR sec.IBLOCK_SECTION_ID IS NULL)
				AND  NOT EXISTS(SELECT 'x' FROM b_disk_storage ds1 WHERE ds1.ROOT_OBJECT_ID=sec.ID)
		");

		if($this->isMssql)
		{
			$this->connection->queryExecute('SET IDENTITY_INSERT b_disk_object OFF');
		}

		if($this->runWorkWithBizproc)
		{
			$classDocument = $sqlHelper->forSql(\Bitrix\Disk\BizProcDocumentCompatible::className());
			if($this->isOracle)
			{
				$this->connection->queryExecute("
					INSERT INTO b_bp_workflow_template (ID, MODULE_ID, ENTITY, DOCUMENT_TYPE, AUTO_EXECUTE, NAME, DESCRIPTION, TEMPLATE, PARAMETERS, VARIABLES, MODIFIED, USER_ID, SYSTEM_CODE, ACTIVE)
					SELECT SQ_B_BP_WORKFLOW_TEMPLATE.nextval, 'disk', '{$classDocument}', " . $this->getConcatFunction("'STORAGE_'", 's.ID') . ", t.AUTO_EXECUTE, t.NAME, t.DESCRIPTION, t.TEMPLATE, t.PARAMETERS, t.VARIABLES, t.MODIFIED, t.USER_ID, t.SYSTEM_CODE, t.ACTIVE
						FROM b_bp_workflow_template t
						INNER JOIN b_disk_storage s ON s.ENTITY_TYPE = '{$proxyType}' AND t.DOCUMENT_TYPE = " . $this->getConcatFunction("'iblock_{$iblockId}_group_'", 's.ENTITY_ID') . "
						WHERE t.MODULE_ID = 'webdav' AND t.ENTITY = 'CIBlockDocumentWebdavSocnet'
				");
			}
			else
			{
				$this->connection->queryExecute("
					INSERT INTO b_bp_workflow_template (MODULE_ID, ENTITY, DOCUMENT_TYPE, AUTO_EXECUTE, NAME, DESCRIPTION, TEMPLATE, PARAMETERS, VARIABLES, MODIFIED, USER_ID, SYSTEM_CODE, ACTIVE)
					SELECT 'disk', '{$classDocument}', " . $this->getConcatFunction("'STORAGE_'", 's.ID') . ", t.AUTO_EXECUTE, t.NAME, t.DESCRIPTION, t.TEMPLATE, t.PARAMETERS, t.VARIABLES, t.MODIFIED, t.USER_ID, t.SYSTEM_CODE, t.ACTIVE
						FROM b_bp_workflow_template t
						INNER JOIN b_disk_storage s ON s.ENTITY_TYPE = '{$proxyType}' AND t.DOCUMENT_TYPE = " . $this->getConcatFunction("'iblock_{$iblockId}_group_'", 's.ENTITY_ID') . "
						WHERE t.MODULE_ID = 'webdav' AND t.ENTITY = 'CIBlockDocumentWebdavSocnet'
				");
			}

			$dbRes = CIBlockSection::GetList(array(), array(
				'IBLOCK_ID' => $iblockId,
				'!SOCNET_GROUP_ID' => false,
				'=UF_USE_BP' => 'Y',
				'SECTION_ID' => 0), false, array('ID', 'UF_USE_BP'));
			if ($dbRes)
			{
				$miscData = $this->connection->getSqlHelper()->forSql(serialize(array(
					'BIZPROC_ENABLED' => true,
				)));
				$rootObjectIdArray = array();
				while($res = $dbRes->Fetch())
				{
					$rootObjectIdArray[$res['ID']] = intval($res['ID']);
				}
				if($rootObjectIdArray)
				{
					$rootObjectIds = implode(",", $rootObjectIdArray);
					$this->connection->queryExecute("UPDATE b_disk_storage SET ENTITY_MISC_DATA='{$miscData}' WHERE ROOT_OBJECT_ID IN ({$rootObjectIds})");
				}
			}
		}

		$this->log(array(
			__METHOD__,
			'finish',
			$iblock['ID'],
		));
	}

	protected function moveSections()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$appendWhere = ' AND obj.PARENT_ID IS NULL ';
		$hasStorageFromDiffSites = $this->connection->query("SELECT * FROM b_disk_object WHERE CODE = 'FROM_SITE_MOVED' AND PARENT_ID IS NOT NULL")->fetch();
		if($hasStorageFromDiffSites)
		{
			$appendWhere = " AND (obj.PARENT_ID IS NULL OR obj.CODE = 'FROM_SITE_MOVED')";
		}
		$this->log(array(
			'appendWhere',
			$appendWhere
		));

		//move all desc of object. Object is root of storage(group, user)
		if($this->isMysql)
		{
			$sql = "
				INSERT IGNORE INTO b_disk_object (ID, REAL_OBJECT_ID, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_SECTION_ID, WEBDAV_IBLOCK_ID)
				SELECT child.ID, child.ID, child.NAME, 2, IF(child.XML_ID='CREATED_DOC_FOLDER', 'FOR_CREATED_FILES', child.CODE), obj.STORAGE_ID, child.IBLOCK_SECTION_ID, child.DATE_CREATE, " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID
				FROM b_iblock_section child
					INNER JOIN b_iblock_section sec ON child.LEFT_MARGIN > sec.LEFT_MARGIN AND child.RIGHT_MARGIN < sec.RIGHT_MARGIN AND child.IBLOCK_ID = sec.IBLOCK_ID
					INNER JOIN b_disk_object obj ON (obj.ID = sec.ID {$appendWhere})
			";
		}
		elseif($this->isOracle || $this->isMssql)
		{
			$sql = "
				INSERT INTO b_disk_object (ID, REAL_OBJECT_ID, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_SECTION_ID, WEBDAV_IBLOCK_ID)
				SELECT child.ID, child.ID, child.NAME, 2, (CASE WHEN child.XML_ID='CREATED_DOC_FOLDER' THEN 'FOR_CREATED_FILES' ELSE child.CODE END), obj.STORAGE_ID, child.IBLOCK_SECTION_ID, child.DATE_CREATE, " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID
				FROM b_iblock_section child
					INNER JOIN b_iblock_section sec ON child.LEFT_MARGIN > sec.LEFT_MARGIN AND child.RIGHT_MARGIN < sec.RIGHT_MARGIN AND child.IBLOCK_ID = sec.IBLOCK_ID
					INNER JOIN b_disk_object obj ON (obj.ID = sec.ID {$appendWhere})
				WHERE NOT EXISTS(SELECT 'x' FROM b_disk_object WHERE NAME = child.NAME AND PARENT_ID = child.IBLOCK_SECTION_ID AND child.IBLOCK_SECTION_ID IS NOT NULL)
			";
		}
		if($this->isMssql)
		{
			$this->connection->queryExecute('SET IDENTITY_INSERT b_disk_object ON');
		}

		$this->connection->queryExecute($sql);

		if($this->isMssql)
		{
			$this->connection->queryExecute('SET IDENTITY_INSERT b_disk_object OFF');
		}

		if($hasStorageFromDiffSites)
		{
			$this->connection->queryExecute("UPDATE b_disk_object SET CODE = null WHERE CODE = 'FROM_SITE_MOVED' AND PARENT_ID IS NOT NULL");
		}

		$this->setStepFinished(__METHOD__);
		$this->abortIfNeeded(true);
	}

	protected function moveNonUniqueSections()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}
		$sqlHelper = $this->connection->getSqlHelper();
		if($this->isMysql)
		{
			$sql = "
				INSERT INTO b_disk_object (ID, REAL_OBJECT_ID, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_SECTION_ID, WEBDAV_IBLOCK_ID)
				SELECT child.ID, child.ID, " . $this->getConcatFunction('child.NAME', "'_'", 'child.ID') . ", 2, IF(child.XML_ID='CREATED_DOC_FOLDER', 'FOR_CREATED_FILES', child.CODE), obj.STORAGE_ID, child.IBLOCK_SECTION_ID, child.DATE_CREATE, " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID
				FROM b_iblock_section child
					INNER JOIN b_iblock_section sec ON child.LEFT_MARGIN > sec.LEFT_MARGIN AND child.RIGHT_MARGIN < sec.RIGHT_MARGIN AND child.IBLOCK_ID = sec.IBLOCK_ID
					INNER JOIN b_disk_object obj ON (obj.ID = sec.ID AND obj.PARENT_ID is NULL)
				WHERE NOT EXISTS(SELECT 'x' FROM b_disk_object do WHERE do.ID=child.ID)
			";
		}
		elseif($this->isOracle || $this->isMssql)
		{
			$sql = "
				INSERT INTO b_disk_object (ID, REAL_OBJECT_ID, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_SECTION_ID, WEBDAV_IBLOCK_ID)
				SELECT child.ID, child.ID, " . $this->getConcatFunction('child.NAME', "'_'", 'child.ID') . ", 2, (CASE WHEN child.XML_ID='CREATED_DOC_FOLDER' THEN 'FOR_CREATED_FILES' ELSE child.CODE END), obj.STORAGE_ID, child.IBLOCK_SECTION_ID, child.DATE_CREATE, " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID
				FROM b_iblock_section child
					INNER JOIN b_iblock_section sec ON child.LEFT_MARGIN > sec.LEFT_MARGIN AND child.RIGHT_MARGIN < sec.RIGHT_MARGIN AND child.IBLOCK_ID = sec.IBLOCK_ID
					INNER JOIN b_disk_object obj ON (obj.ID = sec.ID AND obj.PARENT_ID is NULL)
				WHERE NOT EXISTS(SELECT 'x' FROM b_disk_object do WHERE do.ID=child.ID)
			";
		}
		if($this->isMssql)
		{
			$this->connection->queryExecute('SET IDENTITY_INSERT b_disk_object ON');
		}

		$this->connection->queryExecute($sql);
		if($this->isMssql)
		{
			$this->connection->queryExecute('SET IDENTITY_INSERT b_disk_object OFF');
		}

		$this->setStepFinished(__METHOD__);
	}

	protected function moveElements()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}
		$sqlHelper = $this->sqlHelper;

		$iblocksType2 = $iblocksType1 = array();
		foreach($this->getLibraryIblocks() as $iblock)
		{
			if($iblock['VERSION'] == 2)
			{
				$iblocksType2[$iblock['ID']] = $iblock;
			}
			else
			{
				$iblocksType1[$iblock['ID']] = $iblock;
			}
		}
		unset($iblock);

		if($iblocksType2)
		{
			$propsByIblockId = array();
			$propsIblock2 = $this->connection->query("
				SELECT prop.* FROM b_iblock_property prop
					INNER JOIN b_iblock iblock ON prop.IBLOCK_ID = iblock.ID
				WHERE
					iblock.IBLOCK_TYPE_ID = 'library' AND
					iblock.VERSION = 2 AND
					prop.VERSION = 2 AND
					prop.CODE IN ('WEBDAV_SIZE', 'FILE', 'WEBDAV_VERSION')
			")->fetchAll();

			foreach($propsIblock2 as $prop)
			{
				$propsByIblockId[$prop['IBLOCK_ID']][] = $prop;
			}
			unset($prop);

			foreach($propsByIblockId as $iblockId => $props)
			{
				$joinTable = 'b_iblock_element_prop_s' . $iblockId;
				$columnForFileId = $columnForSize = 'null';
				$columnForVersion = '1';

				foreach($props as $prop)
				{
					switch($prop['CODE'])
					{
						case 'WEBDAV_SIZE':
							$columnForSize = 'PROPERTY_' . $prop['ID'];
							break;
						case 'FILE':
							$columnForFileId = 'PROPERTY_' . $prop['ID'];
							break;
						case 'WEBDAV_VERSION':
							$columnForVersion = 'PROPERTY_' . $prop['ID'];
							break;
					}
				}
				unset($prop);

				if($this->isMysql)
				{
					$sql = "
						INSERT IGNORE INTO b_disk_object (FILE_ID, SIZE, GLOBAL_CONTENT_VERSION, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_ELEMENT_ID, WEBDAV_IBLOCK_ID)
						SELECT {$columnForFileId}, {$columnForSize}, {$columnForVersion}, child.NAME, 3, null, parent.STORAGE_ID, child.IBLOCK_SECTION_ID, child.DATE_CREATE, " . $sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID
						FROM b_iblock_element child
							INNER JOIN b_disk_object parent ON child.IBLOCK_SECTION_ID = parent.ID AND parent.TYPE = 2

							INNER JOIN {$joinTable} props ON props.IBLOCK_ELEMENT_ID = child.ID
					";
				}
				elseif($this->isOracle || $this->isMssql)
				{
					$sql = "
						INSERT INTO b_disk_object (FILE_ID, " . $this->sqlHelper->quote('SIZE') . ", GLOBAL_CONTENT_VERSION, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_ELEMENT_ID, WEBDAV_IBLOCK_ID)
						SELECT {$columnForFileId}, {$columnForSize}, {$columnForVersion}, child.NAME, 3, null, parent.STORAGE_ID, child.IBLOCK_SECTION_ID, child.DATE_CREATE, " . $sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID
						FROM b_iblock_element child
							INNER JOIN b_disk_object parent ON child.IBLOCK_SECTION_ID = parent.ID AND parent.TYPE = 2

							INNER JOIN {$joinTable} props ON props.IBLOCK_ELEMENT_ID = child.ID

						WHERE NOT EXISTS(SELECT 'x' FROM b_disk_object do WHERE do.NAME=child.NAME AND do.PARENT_ID = child.IBLOCK_SECTION_ID AND child.IBLOCK_SECTION_ID IS NOT NULL)

					";
				}
				$this->connection->queryExecute($sql);

				$this->connection->queryExecute("
					INSERT INTO b_disk_object (FILE_ID, " . $this->sqlHelper->quote('SIZE') . ", GLOBAL_CONTENT_VERSION, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_ELEMENT_ID, WEBDAV_IBLOCK_ID)
					SELECT {$columnForFileId}, {$columnForSize}, {$columnForVersion}, " . $this->getConcatFunction('child.ID', 'child.NAME') . ", 3, null, parent.STORAGE_ID, child.IBLOCK_SECTION_ID, child.DATE_CREATE, " . $sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID
					FROM b_iblock_element child
						INNER JOIN b_disk_object parent ON child.IBLOCK_SECTION_ID = parent.ID AND parent.TYPE = 2

						INNER JOIN {$joinTable} props ON props.IBLOCK_ELEMENT_ID = child.ID

					WHERE NOT EXISTS(SELECT 'x' FROM b_disk_object do WHERE do.WEBDAV_ELEMENT_ID=child.ID)

				");
			}
			unset($props);
		}

		if($iblocksType1)
		{
			$whereIgnoreIblocks = ' ';
			$conditionIgnoreIblocks = ' ';
			if($iblocksType2)
			{
				$whereIgnoreIblocks = ' WHERE child.IBLOCK_ID IN (' . implode(', ', array_keys($iblocksType1)) . ') ';
				$conditionIgnoreIblocks = ' AND child.IBLOCK_ID IN (' . implode(', ', array_keys($iblocksType1)) . ') ';
			}

			if($this->isMysql)
			{
				$sql = "
					INSERT IGNORE INTO b_disk_object (FILE_ID, SIZE, GLOBAL_CONTENT_VERSION, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_ELEMENT_ID, WEBDAV_IBLOCK_ID)
					SELECT PROP_FILE_EL.VALUE, PROP_SIZE_EL.VALUE, " . $this->connection->getSqlHelper()->getIsNullFunction('PPROP_VERSION_G_EL.VALUE', 1) . ", child.NAME, 3, null, parent.STORAGE_ID, child.IBLOCK_SECTION_ID, child.DATE_CREATE, " . $sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID
					FROM b_iblock_element child
						INNER JOIN b_disk_object parent ON child.IBLOCK_SECTION_ID = parent.ID AND parent.TYPE = 2

						INNER JOIN b_iblock_property PROP_SIZE ON PROP_SIZE.IBLOCK_ID = child.IBLOCK_ID AND PROP_SIZE.CODE = 'WEBDAV_SIZE'
						INNER JOIN b_iblock_element_property PROP_SIZE_EL ON PROP_SIZE_EL.IBLOCK_PROPERTY_ID = PROP_SIZE.ID AND PROP_SIZE_EL.IBLOCK_ELEMENT_ID = child.ID

						INNER JOIN b_iblock_property PROP_FILE ON PROP_FILE.IBLOCK_ID = child.IBLOCK_ID AND PROP_FILE.CODE = 'FILE'
						INNER JOIN b_iblock_element_property PROP_FILE_EL ON PROP_FILE_EL.IBLOCK_PROPERTY_ID = PROP_FILE.ID AND PROP_FILE_EL.IBLOCK_ELEMENT_ID = child.ID

						LEFT JOIN b_iblock_property PROP_VERSION_G ON PROP_VERSION_G.IBLOCK_ID = child.IBLOCK_ID AND PROP_VERSION_G.CODE = 'WEBDAV_VERSION'
						LEFT JOIN b_iblock_element_property PPROP_VERSION_G_EL ON PPROP_VERSION_G_EL.IBLOCK_PROPERTY_ID = PROP_VERSION_G.ID AND PPROP_VERSION_G_EL.IBLOCK_ELEMENT_ID = child.ID

					{$whereIgnoreIblocks}
				";
			}
			elseif($this->isOracle || $this->isMssql)
			{
				$sql = "
					INSERT INTO b_disk_object (FILE_ID, " . $this->sqlHelper->quote('SIZE') . ", GLOBAL_CONTENT_VERSION, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_ELEMENT_ID, WEBDAV_IBLOCK_ID)
					SELECT PROP_FILE_EL.VALUE, PROP_SIZE_EL.VALUE, " . $this->connection->getSqlHelper()->getIsNullFunction('PPROP_VERSION_G_EL.VALUE', 1) . ", child.NAME, 3, null, parent.STORAGE_ID, child.IBLOCK_SECTION_ID, child.DATE_CREATE, " . $sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID
					FROM b_iblock_element child
						INNER JOIN b_disk_object parent ON child.IBLOCK_SECTION_ID = parent.ID AND parent.TYPE = 2

						INNER JOIN b_iblock_property PROP_SIZE ON PROP_SIZE.IBLOCK_ID = child.IBLOCK_ID AND PROP_SIZE.CODE = 'WEBDAV_SIZE'
						INNER JOIN b_iblock_element_property PROP_SIZE_EL ON PROP_SIZE_EL.IBLOCK_PROPERTY_ID = PROP_SIZE.ID AND PROP_SIZE_EL.IBLOCK_ELEMENT_ID = child.ID

						INNER JOIN b_iblock_property PROP_FILE ON PROP_FILE.IBLOCK_ID = child.IBLOCK_ID AND PROP_FILE.CODE = 'FILE'
						INNER JOIN b_iblock_element_property PROP_FILE_EL ON PROP_FILE_EL.IBLOCK_PROPERTY_ID = PROP_FILE.ID AND PROP_FILE_EL.IBLOCK_ELEMENT_ID = child.ID

						LEFT JOIN b_iblock_property PROP_VERSION_G ON PROP_VERSION_G.IBLOCK_ID = child.IBLOCK_ID AND PROP_VERSION_G.CODE = 'WEBDAV_VERSION'
						LEFT JOIN b_iblock_element_property PPROP_VERSION_G_EL ON PPROP_VERSION_G_EL.IBLOCK_PROPERTY_ID = PROP_VERSION_G.ID AND PPROP_VERSION_G_EL.IBLOCK_ELEMENT_ID = child.ID
					WHERE NOT EXISTS(SELECT 'x' FROM b_disk_object do WHERE do.NAME=child.NAME AND do.PARENT_ID = child.IBLOCK_SECTION_ID AND child.IBLOCK_SECTION_ID IS NOT NULL) {$conditionIgnoreIblocks}

				";
			}
			$this->connection->queryExecute($sql);

			$this->connection->queryExecute("
				INSERT INTO b_disk_object (FILE_ID, " . $this->sqlHelper->quote('SIZE') . ", GLOBAL_CONTENT_VERSION, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_ELEMENT_ID, WEBDAV_IBLOCK_ID)
				SELECT PROP_FILE_EL.VALUE, PROP_SIZE_EL.VALUE, " . $this->connection->getSqlHelper()->getIsNullFunction('PPROP_VERSION_G_EL.VALUE', 1) . ", " . $this->getConcatFunction('child.ID', 'child.NAME') . ", 3, null, parent.STORAGE_ID, child.IBLOCK_SECTION_ID, child.DATE_CREATE, " . $sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID
				FROM b_iblock_element child
					INNER JOIN b_disk_object parent ON child.IBLOCK_SECTION_ID = parent.ID AND parent.TYPE = 2

					INNER JOIN b_iblock_property PROP_SIZE ON PROP_SIZE.IBLOCK_ID = child.IBLOCK_ID AND PROP_SIZE.CODE = 'WEBDAV_SIZE'
					INNER JOIN b_iblock_element_property PROP_SIZE_EL ON PROP_SIZE_EL.IBLOCK_PROPERTY_ID = PROP_SIZE.ID AND PROP_SIZE_EL.IBLOCK_ELEMENT_ID = child.ID

					INNER JOIN b_iblock_property PROP_FILE ON PROP_FILE.IBLOCK_ID = child.IBLOCK_ID AND PROP_FILE.CODE = 'FILE'
					INNER JOIN b_iblock_element_property PROP_FILE_EL ON PROP_FILE_EL.IBLOCK_PROPERTY_ID = PROP_FILE.ID AND PROP_FILE_EL.IBLOCK_ELEMENT_ID = child.ID

					LEFT JOIN b_iblock_property PROP_VERSION_G ON PROP_VERSION_G.IBLOCK_ID = child.IBLOCK_ID AND PROP_VERSION_G.CODE = 'WEBDAV_VERSION'
					LEFT JOIN b_iblock_element_property PPROP_VERSION_G_EL ON PPROP_VERSION_G_EL.IBLOCK_PROPERTY_ID = PROP_VERSION_G.ID AND PPROP_VERSION_G_EL.IBLOCK_ELEMENT_ID = child.ID

				WHERE NOT EXISTS(SELECT 'x' FROM b_disk_object do WHERE do.WEBDAV_ELEMENT_ID=child.ID) {$conditionIgnoreIblocks}
			");
		}

		$this->setStepFinished(__METHOD__);
		$this->abortIfNeeded(true);
	}

	protected function setSymbolicLinks()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		//We know ID folders (b_disk_object) equal Id b_iblock_section
		if($this->isMysql)
		{
			$sql = "
				UPDATE b_disk_object obj_link
				INNER JOIN b_webdav_folder_invite old ON obj_link.ID = old.LINK_SECTION_ID AND obj_link.WEBDAV_SECTION_ID IS NOT NULL
				SET obj_link.REAL_OBJECT_ID = old.SECTION_ID
				WHERE old.IS_APPROVED = 1 AND old.IS_DELETED = 0 AND old.LINK_SECTION_ID IS NOT NULL
			";
		}
		elseif($this->isOracle || $this->isMssql)
		{
			$sql = "
				UPDATE b_disk_object
					SET REAL_OBJECT_ID = (
						SELECT OLD.SECTION_ID FROM b_disk_object obj_link
							INNER JOIN b_webdav_folder_invite OLD ON obj_link.ID = OLD.LINK_SECTION_ID AND obj_link.WEBDAV_SECTION_ID IS NOT NULL
						WHERE
							b_disk_object.ID = obj_link.ID AND OLD.IS_APPROVED = 1 AND OLD.IS_DELETED = 0 AND OLD.LINK_SECTION_ID IS NOT NULL
				)
				WHERE EXISTS((SELECT OLD.SECTION_ID FROM b_disk_object obj_link
					INNER JOIN b_webdav_folder_invite OLD ON obj_link.ID = OLD.LINK_SECTION_ID AND obj_link.WEBDAV_SECTION_ID IS NOT NULL
					WHERE
						b_disk_object.ID = obj_link.ID AND OLD.IS_APPROVED = 1 AND OLD.IS_DELETED = 0 AND OLD.LINK_SECTION_ID IS NOT NULL
				))
			";
		}
		$this->connection->queryExecute($sql);

		if($this->isMysql)
		{
			$sql = "
				UPDATE b_disk_object obj_link
				INNER JOIN
					(
						SELECT t1.ID
						FROM b_disk_object t1
						LEFT JOIN b_disk_object t2 ON t2.ID = t1.REAL_OBJECT_ID
						WHERE t2.ID IS NULL
				  ) bad_link ON obj_link.ID = bad_link.ID
				SET obj_link.REAL_OBJECT_ID = obj_link.ID
			";
		}
		elseif($this->isOracle || $this->isMssql)
		{
			$sql = "
			UPDATE b_disk_object
				SET REAL_OBJECT_ID = (SELECT obj_link.ID FROM b_disk_object obj_link, (SELECT ID FROM b_disk_object l WHERE l.REAL_OBJECT_ID IS NOT NULL AND NOT EXISTS(SELECT 'x' FROM b_disk_object o WHERE o.ID = l.REAL_OBJECT_ID)) bad_link
					WHERE obj_link.ID = bad_link.ID AND b_disk_object.ID = obj_link.ID
				)

				WHERE EXISTS(SELECT obj_link.ID FROM b_disk_object obj_link, (SELECT ID FROM b_disk_object l WHERE l.REAL_OBJECT_ID IS NOT NULL AND NOT EXISTS(SELECT 'x' FROM b_disk_object o WHERE o.ID = l.REAL_OBJECT_ID)) bad_link
					WHERE obj_link.ID = bad_link.ID AND b_disk_object.ID = obj_link.ID
				)
			";
		}
		$this->connection->queryExecute($sql);

		$this->setStepFinished(__METHOD__);
	}

	protected function moveSharings()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}
		$sqlHelper = $this->connection->getSqlHelper();


		//we migrate only approved sharing
		$approved = SharingTable::STATUS_IS_APPROVED;
		$type = SharingTable::TYPE_TO_USER;
		//We know ID folders (b_disk_object) equal Id b_iblock_section

		$this->connection->queryExecute("
			INSERT INTO b_disk_sharing (PARENT_ID, CREATED_BY, FROM_ENTITY, TO_ENTITY, LINK_STORAGE_ID, LINK_OBJECT_ID, REAL_OBJECT_ID, REAL_STORAGE_ID, DESCRIPTION, CAN_FORWARD, STATUS, TYPE, TASK_NAME)
			SELECT null, old.USER_ID, " . $this->getConcatFunction("'U'", 'old.USER_ID') . ", " . $this->getConcatFunction("'U'", 'old.INVITE_USER_ID') . ", obj_link.STORAGE_ID, obj_link.ID, obj_real.ID, obj_real.STORAGE_ID, old.DESCRIPTION, 0, {$approved}, {$type}, 'disk_access_read' from b_webdav_folder_invite old
				INNER JOIN b_disk_object obj_real ON obj_real.ID = old.SECTION_ID AND obj_real.WEBDAV_SECTION_ID IS NOT NULL
				INNER JOIN b_disk_object obj_link ON obj_link.ID = old.LINK_SECTION_ID AND obj_link.WEBDAV_SECTION_ID IS NOT NULL
			WHERE old.IS_APPROVED = 1 AND old.IS_DELETED = 0 AND old.LINK_SECTION_ID IS NOT NULL
		");

		$this->setStepFinished(__METHOD__);
	}

	protected function moveExternalLinks()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}
		if($this->isMysql)
		{
			$sql = "
				INSERT INTO b_disk_external_link (OBJECT_ID, VERSION_ID, HASH, PASSWORD, SALT, DEATH_TIME, DESCRIPTION, DOWNLOAD_COUNT, TYPE, CREATE_TIME, CREATED_BY)
				SELECT obj.ID, null, old_ext.HASH, old_ext.PASSWORD, old_ext.SALT, IF(old_ext.LIFETIME - old_ext.CREATION_DATE <> 315360000, FROM_UNIXTIME(old_ext.LIFETIME), null), old_ext.DESCRIPTION, old_ext.DOWNLOAD_COUNT, 3, FROM_UNIXTIME(old_ext.CREATION_DATE), old_ext.USER_ID FROM b_webdav_ext_links old_ext
					INNER JOIN b_disk_object obj ON old_ext.ELEMENT_ID = obj.WEBDAV_ELEMENT_ID AND obj.WEBDAV_ELEMENT_ID IS NOT NULL
			";
		}
		elseif($this->isOracle)
		{
			$sql = "
				INSERT INTO b_disk_external_link (OBJECT_ID, VERSION_ID, HASH, PASSWORD, SALT, DEATH_TIME, DESCRIPTION, DOWNLOAD_COUNT, TYPE, CREATE_TIME, CREATED_BY)
				SELECT obj.ID, null, old_ext.HASH, old_ext.PASSWORD, old_ext.SALT,
				(CASE WHEN old_ext.LIFETIME - old_ext.CREATION_DATE <> 315360000 THEN to_char( to_date('01011970','ddmmyyyy') + 1/24/60/60 * old_ext.LIFETIME, 'dd-mon-yyyy hh24:mi:ss') ELSE null END), old_ext.DESCRIPTION, old_ext.DOWNLOAD_COUNT, 3, to_char( to_date('01011970','ddmmyyyy') + 1/24/60/60 * old_ext.CREATION_DATE, 'dd-mon-yyyy hh24:mi:ss'), old_ext.USER_ID FROM b_webdav_ext_links old_ext
					INNER JOIN b_disk_object obj ON old_ext.ELEMENT_ID = obj.WEBDAV_ELEMENT_ID AND obj.WEBDAV_ELEMENT_ID IS NOT NULL
			";
		}
		elseif($this->isMssql)
		{
			$sql = "
				INSERT INTO b_disk_external_link (OBJECT_ID, VERSION_ID, HASH, PASSWORD, SALT, DEATH_TIME, DESCRIPTION, DOWNLOAD_COUNT, TYPE, CREATE_TIME, CREATED_BY)
				SELECT obj.ID, null, old_ext.HASH, old_ext.PASSWORD, old_ext.SALT,
				(CASE WHEN old_ext.LIFETIME - old_ext.CREATION_DATE <> 315360000 THEN dateadd(s, old_ext.LIFETIME, '19700101 05:00:00:000') ELSE null END), old_ext.DESCRIPTION, old_ext.DOWNLOAD_COUNT, 3, dateadd(s, old_ext.CREATION_DATE, '19700101 05:00:00:000'), old_ext.USER_ID FROM b_webdav_ext_links old_ext
					INNER JOIN b_disk_object obj ON old_ext.ELEMENT_ID = obj.WEBDAV_ELEMENT_ID AND obj.WEBDAV_ELEMENT_ID IS NOT NULL
			";
		}

		$this->connection->queryExecute($sql);

		$this->setStepFinished(__METHOD__);
	}

	protected function migrateOptions()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return array(0, 0);
		}
		$successCount = $failedCount = 0;

		Option::set(
			'disk',
			'disk_allow_autoconnect_shared_objects',
			Option::get('webdav', 'webdav_allow_autoconnect_share_group_folder', 'Y')
		);

		Option::set(
			'disk',
			'disk_allow_create_file_by_cloud',
			Option::get('webdav', 'webdav_allow_ext_doc_services_global', 'Y')
		);

		$this->setStepFinished(__METHOD__);

		return array($successCount, $failedCount);
	}

	protected function storeConvertedUserFieldId($id)
	{
		COption::SetOptionString(
			'disk',
			'~migrateUf',
			$id
		);
	}

	protected function getLastConvertedUserFieldId()
	{
		return COption::getOptionString(
			'disk',
			'~migrateUf',
			0
		);
	}

	protected function storeConvertedUserFieldRowId($ufId, $id)
	{
		COption::SetOptionString(
			'disk',
			'~migrateUfRow' . $ufId,
			$id
		);
	}

	protected function getLastConvertedUserFieldRowId($ufId)
	{
		return COption::getOptionString(
			'disk',
			'~migrateUfRow' . $ufId,
			0
		);
	}

	protected function moveWebdavElement()
	{
		$this->abortIfNeeded();

		if($this->isStepFinished(__METHOD__))
		{
			return array(0, 0);
		}
		$sqlHelper = $this->connection->getSqlHelper();

		$successCount = $failedCount = 0;
		$this->log(array(
			'Start move uf webdav element',
		));
		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		$connection = Application::getInstance()->getConnection();

		$userTypeId = $connection->getSqlHelper()->forSql('webdav_element');
		$lastId = $this->getLastConvertedUserFieldId();
		$result = $connection->query("SELECT * from b_user_field WHERE USER_TYPE_ID = '{$userTypeId}' AND ID > {$lastId} ORDER BY ID ASC");
		while($userFieldRow = $result->fetch())
		{
			$this->abortIfNeeded();

			$entityName = $userFieldRow['ENTITY_ID'];
			if($userFieldRow['MULTIPLE'] == 'Y')
			{
				list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType($userFieldRow['ENTITY_ID']);

				$tableNameUf = "b_utm_".strtolower($entityName);
				$tableNameSingleUf = "b_uts_".strtolower($entityName);

				if($entityName == 'SONET_LOG')
				{
					if($this->isMssql)
					{
						$this->connection->queryExecute("
								SELECT * INTO b_disk_utm_sonet_log_crm FROM (SELECT so.* FROM {$tableNameUf} so
								INNER JOIN b_sonet_log log ON log.ID = so.VALUE_ID
								WHERE so.FIELD_ID = {$userFieldRow['ID']} AND log.EVENT_ID in ('crm_activity_add', 'crm_lead_message', 'crm_contact_message', 'crm_company_message', 'crm_deal_message', 'crm_activity_message', 'crm_invoice_message')) tmp
						");
					}
					else
					{
						$this->connection->queryExecute("
							 CREATE TABLE b_disk_utm_sonet_log_crm AS
								SELECT so.* FROM {$tableNameUf} so
								INNER JOIN b_sonet_log log ON log.ID = so.VALUE_ID
								WHERE so.FIELD_ID = {$userFieldRow['ID']} AND log.EVENT_ID in ('crm_activity_add', 'crm_lead_message', 'crm_contact_message', 'crm_company_message', 'crm_deal_message', 'crm_activity_message', 'crm_invoice_message')
						");
					}
					$tableNameUf = 'b_disk_utm_sonet_log_crm';
					$this->connection->query("CREATE INDEX IX_UTM_TMP_1 on {$tableNameUf}(FIELD_ID, VALUE_INT)");
				}
				if($entityName == 'SONET_COMMENT')
				{
					if($this->isMssql)
					{
						$this->connection->queryExecute("
								SELECT * INTO b_disk_utm_sonet_comment_crm FROM (SELECT so.* FROM {$tableNameUf} so
								INNER JOIN b_sonet_log_comment logc ON logc.ID = so.VALUE_ID
								WHERE so.FIELD_ID = {$userFieldRow['ID']} AND logc.EVENT_ID in ('crm_lead_message_comment', 'crm_contact_message_comment', 'crm_company_message_comment', 'crm_deal_message_comment', 'crm_activity_message_comment', 'crm_invoice_message_comment', 'crm_lead_add_comment', 'crm_contact_add_comment', 'crm_company_add_comment', 'crm_deal_add_comment', 'crm_activity_add_comment', 'crm_invoice_add_comment', 'crm_lead_progress_comment', 'crm_contact_progress_comment', 'crm_company_progress_comment', 'crm_deal_progress_comment', 'crm_activity_progress_comment', 'crm_invoice_progress_comment', 'crm_lead_denomination_comment', 'crm_contact_denomination_comment', 'crm_company_denomination_comment', 'crm_deal_denomination_comment', 'crm_activity_denomination_comment', 'crm_invoice_denomination_comment', 'crm_lead_client_comment', 'crm_contact_client_comment', 'crm_company_client_comment', 'crm_deal_client_comment', 'crm_activity_client_comment', 'crm_invoice_client_comment', 'crm_lead_owner_comment', 'crm_contact_owner_comment', 'crm_company_owner_comment', 'crm_deal_owner_comment', 'crm_activity_owner_comment', 'crm_invoice_owner_comment', 'crm_lead_custom_comment', 'crm_contact_custom_comment', 'crm_company_custom_comment', 'crm_deal_custom_comment', 'crm_activity_custom_comment', 'crm_invoice_custom_comment', 'crm_lead_responsible_comment', 'crm_contact_responsible_comment', 'crm_company_responsible_comment', 'crm_deal_responsible_comment', 'crm_activity_responsible_comment', 'crm_invoice_responsible_comment')) tmp
						");
					}
					else
					{
						$this->connection->queryExecute("
							 CREATE TABLE b_disk_utm_sonet_comment_crm AS
								SELECT so.* FROM {$tableNameUf} so
								INNER JOIN b_sonet_log_comment logc ON logc.ID = so.VALUE_ID
								WHERE so.FIELD_ID = {$userFieldRow['ID']} AND logc.EVENT_ID in ('crm_lead_message_comment', 'crm_contact_message_comment', 'crm_company_message_comment', 'crm_deal_message_comment', 'crm_activity_message_comment', 'crm_invoice_message_comment', 'crm_lead_add_comment', 'crm_contact_add_comment', 'crm_company_add_comment', 'crm_deal_add_comment', 'crm_activity_add_comment', 'crm_invoice_add_comment', 'crm_lead_progress_comment', 'crm_contact_progress_comment', 'crm_company_progress_comment', 'crm_deal_progress_comment', 'crm_activity_progress_comment', 'crm_invoice_progress_comment', 'crm_lead_denomination_comment', 'crm_contact_denomination_comment', 'crm_company_denomination_comment', 'crm_deal_denomination_comment', 'crm_activity_denomination_comment', 'crm_invoice_denomination_comment', 'crm_lead_client_comment', 'crm_contact_client_comment', 'crm_company_client_comment', 'crm_deal_client_comment', 'crm_activity_client_comment', 'crm_invoice_client_comment', 'crm_lead_owner_comment', 'crm_contact_owner_comment', 'crm_company_owner_comment', 'crm_deal_owner_comment', 'crm_activity_owner_comment', 'crm_invoice_owner_comment', 'crm_lead_custom_comment', 'crm_contact_custom_comment', 'crm_company_custom_comment', 'crm_deal_custom_comment', 'crm_activity_custom_comment', 'crm_invoice_custom_comment', 'crm_lead_responsible_comment', 'crm_contact_responsible_comment', 'crm_company_responsible_comment', 'crm_deal_responsible_comment', 'crm_activity_responsible_comment', 'crm_invoice_responsible_comment')
						");

					}
					$tableNameUf = 'b_disk_utm_sonet_comment_crm';
					$this->connection->query("CREATE INDEX IX_UTM_TMP_21 on {$tableNameUf}(FIELD_ID, VALUE_INT)");
				}

				$moduleId = $sqlHelper->forSql($moduleId);
				$connectorClass = $sqlHelper->forSql($connectorClass);

				//create new attached objects from old uf values
				$this->connection->queryExecute("
					INSERT INTO b_disk_attached_object (OBJECT_ID, VERSION_ID, IS_EDITABLE, MODULE_ID, ENTITY_TYPE, ENTITY_ID, CREATE_TIME, CREATED_BY)
					SELECT obj.ID, null, 2, '{$moduleId}', '{$connectorClass}', uf.VALUE_ID, " . $this->sqlHelper->getCurrentDateTimeFunction() . ", 0 FROM {$tableNameUf} uf
					INNER JOIN b_disk_object obj ON obj.WEBDAV_ELEMENT_ID = uf.VALUE_INT AND uf.FIELD_ID = {$userFieldRow['ID']} AND obj.WEBDAV_ELEMENT_ID IS NOT NULL
				");
				//replace old values new attached id (above sql)
				if($this->isMysql)
				{
					$sql = "
						UPDATE {$tableNameUf} uf
						INNER JOIN b_disk_object obj ON uf.VALUE_INT = obj.WEBDAV_ELEMENT_ID AND obj.WEBDAV_ELEMENT_ID IS NOT NULL
						INNER JOIN b_disk_attached_object attach ON attach.OBJECT_ID = obj.ID AND attach.ENTITY_ID = uf.VALUE_ID AND attach.ENTITY_TYPE = '{$connectorClass}' AND attach.MODULE_ID = '{$moduleId}'
						SET uf.VALUE_INT = attach.ID
						WHERE uf.FIELD_ID = {$userFieldRow['ID']}
					";
				}
				elseif($this->isOracle || $this->isMssql)
				{
					$sql = "
						UPDATE {$tableNameUf}
						SET VALUE_INT = (SELECT attach.ID FROM {$tableNameUf} uf
						INNER JOIN b_disk_object obj ON uf.VALUE_INT = obj.WEBDAV_ELEMENT_ID AND obj.WEBDAV_ELEMENT_ID IS NOT NULL
						INNER JOIN b_disk_attached_object attach ON attach.OBJECT_ID = obj.ID AND attach.ENTITY_ID = uf.VALUE_ID AND attach.ENTITY_TYPE = '{$connectorClass}' AND attach.MODULE_ID = '{$moduleId}'
						WHERE
							{$tableNameUf}.VALUE_ID = uf.VALUE_ID	AND uf.FIELD_ID = {$userFieldRow['ID']}
						)

						WHERE EXISTS (SELECT attach.ID FROM {$tableNameUf} uf
						INNER JOIN b_disk_object obj ON uf.VALUE_INT = obj.WEBDAV_ELEMENT_ID AND obj.WEBDAV_ELEMENT_ID IS NOT NULL
						INNER JOIN b_disk_attached_object attach ON attach.OBJECT_ID = obj.ID AND attach.ENTITY_ID = uf.VALUE_ID AND attach.ENTITY_TYPE = '{$connectorClass}' AND attach.MODULE_ID = '{$moduleId}'
						WHERE
							{$tableNameUf}.VALUE_ID = uf.VALUE_ID	AND uf.FIELD_ID = {$userFieldRow['ID']}
						)
					";
				}
				$this->connection->queryExecute($sql);

				//Done. But we don't forget to recalc b_uts_ where we should be serialized data ^(
				if($entityName != 'SONET_LOG' && $entityName != 'SONET_COMMENT')
				{
					$this->connection->queryExecute("UPDATE b_user_field SET USER_TYPE_ID = 'disk_file' WHERE ID = {$userFieldRow['ID']}");

					if($this->isMssql)
					{
						$this->connection->queryExecute("UPDATE {$tableNameSingleUf} SET {$userFieldRow['FIELD_NAME']} = CAST(VALUE_ID AS VARCHAR(255))");
					}
					else
					{
						$this->connection->queryExecute("UPDATE {$tableNameSingleUf} SET {$userFieldRow['FIELD_NAME']} = VALUE_ID");
					}
				}

			}
			else
			{
				list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType($userFieldRow['ENTITY_ID']);
				$tableNameSingleUf = "b_uts_".strtolower($entityName);
				$moduleId = $sqlHelper->forSql($moduleId);
				$connectorClass = $sqlHelper->forSql($connectorClass);

				//create new attached objects from old uf values
				$this->connection->queryExecute("
					INSERT INTO b_disk_attached_object (OBJECT_ID, VERSION_ID, IS_EDITABLE, MODULE_ID, ENTITY_TYPE, ENTITY_ID, CREATE_TIME, CREATED_BY)
					SELECT obj.ID, null, 2, '{$moduleId}', '{$connectorClass}', uf.VALUE_ID, " . $this->sqlHelper->getCurrentDateTimeFunction() . ", 0 FROM {$tableNameSingleUf} uf
					INNER JOIN b_disk_object obj ON obj.WEBDAV_ELEMENT_ID = uf.{$userFieldRow['FIELD_NAME']} AND obj.WEBDAV_ELEMENT_ID IS NOT NULL
				");
				//replace old values new attached id (above sql)
				if($this->isMysql)
				{
					$sql = "
						UPDATE {$tableNameSingleUf} uf
						INNER JOIN b_disk_object obj ON uf.{$userFieldRow['FIELD_NAME']} = obj.WEBDAV_ELEMENT_ID AND obj.WEBDAV_ELEMENT_ID IS NOT NULL
						INNER JOIN b_disk_attached_object attach ON attach.OBJECT_ID = obj.ID AND attach.ENTITY_ID = uf.VALUE_ID AND attach.ENTITY_TYPE = '{$connectorClass}' AND attach.MODULE_ID = '{$moduleId}'
						SET uf.{$userFieldRow['FIELD_NAME']} = attach.ID
					";
				}
				elseif($this->isOracle || $this->isMssql)
				{
					$sql = "
						UPDATE {$tableNameSingleUf}
						SET {$userFieldRow['FIELD_NAME']} = (SELECT attach.ID FROM {$tableNameSingleUf} uf
						INNER JOIN b_disk_object obj ON uf.{$userFieldRow['FIELD_NAME']} = obj.WEBDAV_ELEMENT_ID AND obj.WEBDAV_ELEMENT_ID IS NOT NULL
						INNER JOIN b_disk_attached_object attach ON attach.OBJECT_ID = obj.ID AND attach.ENTITY_ID = uf.VALUE_ID AND attach.ENTITY_TYPE = '{$connectorClass}' AND attach.MODULE_ID = '{$moduleId}'
						WHERE {$tableNameSingleUf}.VALUE_ID = uf.VALUE_ID )

						WHERE EXISTS (SELECT attach.ID FROM {$tableNameSingleUf} uf
						INNER JOIN b_disk_object obj ON uf.{$userFieldRow['FIELD_NAME']} = obj.WEBDAV_ELEMENT_ID AND obj.WEBDAV_ELEMENT_ID IS NOT NULL
						INNER JOIN b_disk_attached_object attach ON attach.OBJECT_ID = obj.ID AND attach.ENTITY_ID = uf.VALUE_ID AND attach.ENTITY_TYPE = '{$connectorClass}' AND attach.MODULE_ID = '{$moduleId}'
						WHERE {$tableNameSingleUf}.VALUE_ID = uf.VALUE_ID )
					";
				}
				$this->connection->queryExecute($sql);
				$this->connection->queryExecute("UPDATE b_user_field SET USER_TYPE_ID = 'disk_file' WHERE ID = {$userFieldRow['ID']}");
			}

			$this->storeConvertedUserFieldId($userFieldRow['ID']);
		}

		$this->setStepFinished(__METHOD__);
		$this->log(array(
			'Finish move uf webdav element',
		));
		//we store 0, and use this options in @migrateWebdavElement@
		$this->storeConvertedUserFieldId(0);

		return array($successCount, $failedCount);
	}

	protected function migrateWebdavElementInSocnet()
	{
		$this->abortIfNeeded();

		if($this->isStepFinished(__METHOD__))
		{
			return array(0, 0);
		}
		$successCount = $failedCount = 0;
		$this->log(array(
			'Start move uf webdav element sonet',
		));
		$connection = Application::getInstance()->getConnection();

		$userTypeId = $connection->getSqlHelper()->forSql('webdav_element');
		$lastId = $this->getLastConvertedUserFieldId();
		$result = $connection->query("SELECT * from b_user_field WHERE USER_TYPE_ID = '{$userTypeId}' AND ID > {$lastId} AND ENTITY_ID IN ('SONET_COMMENT', 'SONET_LOG') ORDER BY ID ASC");
		while($userFieldRow = $result->fetch())
		{
			$this->abortIfNeeded();

			if($userFieldRow['ENTITY_ID'] == 'SONET_LOG')
			{
				if($userFieldRow['MULTIPLE'] == 'Y')
				{
					$tableNameUf = "b_utm_sonet_log";
					$tableNameSingleUf = "b_uts_sonet_log";

					$this->connection->queryExecute("
						DELETE FROM {$tableNameUf} WHERE FIELD_ID = {$userFieldRow['ID']}
					");


					foreach(array(
						'tasks' => 'tasks_task',
						'blog_post' => 'blog_post',
						'blog_post_important' => 'blog_post',
						'calendar' => 'calendar_event',
						'forum' => 'forum_message',
					) as $eventId => $connectedEntityName)
					{
						$tableNameUfConnected = "b_utm_".strtolower($connectedEntityName);
						$eventId = $this->sqlHelper->forSql($eventId);

						if(!$this->connection->isTableExists($tableNameUfConnected))
						{
							continue;
						}

						$this->connection->queryExecute("
							INSERT INTO {$tableNameUf} (VALUE_ID, FIELD_ID, VALUE_INT)
							SELECT log.ID, {$userFieldRow['ID']}, origuf.VALUE_INT
							FROM b_sonet_log log
							INNER JOIN {$tableNameUfConnected} origuf ON origuf.VALUE_ID = log.SOURCE_ID
							WHERE " . ($this->isOracle? '' : " log.SOURCE_ID <> '' AND ") . " log.SOURCE_ID IS NOT NULL AND log.EVENT_ID = '{$eventId}'
						");
					}

 					$this->connection->queryExecute("
						INSERT INTO {$tableNameUf} (VALUE_ID, FIELD_ID, VALUE_INT)
						SELECT VALUE_ID, FIELD_ID, VALUE_INT
						FROM b_disk_utm_sonet_log_crm
					");

					$this->connection->queryExecute("UPDATE b_user_field SET USER_TYPE_ID = 'disk_file' WHERE ID = {$userFieldRow['ID']}");

					if($this->isMssql)
					{
						$this->connection->queryExecute("UPDATE {$tableNameSingleUf} SET {$userFieldRow['FIELD_NAME']} = CAST(VALUE_ID AS VARCHAR(255))");
					}
					else
					{
						$this->connection->queryExecute("UPDATE {$tableNameSingleUf} SET {$userFieldRow['FIELD_NAME']} = VALUE_ID");
					}

					$this->storeConvertedUserFieldId($userFieldRow['ID']);
				}
			}
			elseif($userFieldRow['ENTITY_ID'] == 'SONET_COMMENT')
			{
				if($userFieldRow['MULTIPLE'] == 'Y')
				{
					$tableNameUf = "b_utm_sonet_comment";
					$tableNameSingleUf = "b_uts_sonet_comment";

					$this->connection->queryExecute("
						DELETE FROM {$tableNameUf} WHERE FIELD_ID = {$userFieldRow['ID']}
					");

					foreach(array(
						'blog_comment' => 'blog_comment',
						'calendar_comment' => 'forum_message',
						'forum' => 'forum_message',
						'idea_comment' => 'blog_comment',
						'news_comment' => 'forum_message',
						'photo_comment' => 'forum_message',
						'report_comment' => 'forum_message',
						'tasks_comment' => 'forum_message',
						'wiki_comment' => 'forum_message',
					) as $eventId => $connectedEntityName)
					{
						$tableNameUfConnected = "b_utm_".strtolower($connectedEntityName);
						$eventId = $this->sqlHelper->forSql($eventId);

						if(!$this->connection->isTableExists($tableNameUfConnected))
						{
							continue;
						}

						$this->connection->queryExecute("
							INSERT INTO {$tableNameUf} (VALUE_ID, FIELD_ID, VALUE_INT)
							SELECT log.ID, {$userFieldRow['ID']}, origuf.VALUE_INT
							FROM b_sonet_log_comment log
							INNER JOIN {$tableNameUfConnected} origuf ON origuf.VALUE_ID = log.SOURCE_ID
							WHERE " . ($this->isOracle? '' : " log.SOURCE_ID <> '' AND ") . " log.SOURCE_ID IS NOT NULL AND log.EVENT_ID = '{$eventId}'
						");
					}

 					$this->connection->queryExecute("
						INSERT INTO {$tableNameUf} (VALUE_ID, FIELD_ID, VALUE_INT)
						SELECT VALUE_ID, FIELD_ID, VALUE_INT
						FROM b_disk_utm_sonet_comment_crm
					");

					$this->connection->queryExecute("UPDATE b_user_field SET USER_TYPE_ID = 'disk_file' WHERE ID = {$userFieldRow['ID']}");

					if($this->isMssql)
					{
						$this->connection->queryExecute("UPDATE {$tableNameSingleUf} SET {$userFieldRow['FIELD_NAME']} = CAST(VALUE_ID AS VARCHAR(255))");
					}
					else
					{
						$this->connection->queryExecute("UPDATE {$tableNameSingleUf} SET {$userFieldRow['FIELD_NAME']} = VALUE_ID");
					}

					$this->storeConvertedUserFieldId($userFieldRow['ID']);
				}
			}


			$this->storeConvertedUserFieldId($userFieldRow['ID']);
		}

		$this->setStepFinished(__METHOD__);
		$this->log(array(
			'Finish move uf webdav element sonet',
		));
		//we store 0, and use this options in @migrateWebdavElement@
		$this->storeConvertedUserFieldId(0);

		return array($successCount, $failedCount);
	}

	private function getLastVersionByElementId($elementId)
	{
		$elementId = (int)$elementId;
		$version = $this->connection->query("
			SELECT MAX(v.ID) LAST_VERSION, MAX(v.OBJECT_ID) OBJECT_ID FROM b_disk_version v
				INNER JOIN b_disk_object obj ON obj.ID = v.OBJECT_ID
			WHERE obj.WEBDAV_ELEMENT_ID = {$elementId}
		")->fetch();

		return $version? array($version['LAST_VERSION'], $version['OBJECT_ID']) : null;
	}

	private function getLastVersionByBpId($bpId)
	{
		$bpId = (int)$bpId;
		$version = $this->connection->query("
			SELECT v.ID LAST_VERSION, v.OBJECT_ID OBJECT_ID FROM b_disk_version v
			WHERE v.BP_VERSION_ID = {$bpId}
		")->fetch();

		return $version? array($version['LAST_VERSION'], $version['OBJECT_ID']) : null;
	}

	protected function migrateWebdavElementHistory()
	{
		$this->abortIfNeeded();

		if($this->isStepFinished(__METHOD__))
		{
			return;
		}
		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		$connection = $this->connection;

		$rsData = CUserTypeEntity::GetList(array(), array(
			"ENTITY_ID" => "BLOG_COMMENT",
			"XML_ID" => "UF_BLOG_COMMENT_FH",
		));
		if(!$rsData->fetch())
		{
			$this->setStepFinished(__METHOD__);
			$this->log(array(
				'Could not find UF_BLOG_COMMENT_FH in entity BLOG_COMMENT',
			));

			return;
		}
		unset($rsData);

		if(!$connection->isTableExists('b_uts_blog_comment') || !$connection->getTableField('b_uts_blog_comment', 'UF_BLOG_COMMENT_FH'))
		{
			$this->setStepFinished(__METHOD__);
			$this->log(array(
				'b_uts_blog_comment does not exist or column UF_BLOG_COMMENT_FH does not exist.',
			));

			return;
		}
		unset($rsData);

		list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType('BLOG_COMMENT');

		$lastId = $this->getStorageId();
		$result = $connection->query("SELECT * from b_uts_blog_comment WHERE VALUE_ID > {$lastId} AND UF_BLOG_COMMENT_FH <> '' ORDER BY VALUE_ID ASC");
		while($userFieldRow = $result->fetch())
		{
			$this->abortIfNeeded();

			$dataFromValue = CUserTypeWebdavElementHistory::getDataFromValue($userFieldRow['UF_BLOG_COMMENT_FH']);
			if(!$dataFromValue)
			{
				$this->storeStorageId($userFieldRow['VALUE_ID']);
				continue;
			}
			$dataFromValue = $dataFromValue[0];

			//this is head version
			if(empty($dataFromValue['v']))
			{
				list($newVersionId, $objectId) = $this->getLastVersionByElementId($dataFromValue['id']);
				if(!$newVersionId || !$objectId)
				{
					$this->storeStorageId($userFieldRow['VALUE_ID']);
					$this->log(array(
						'Could not find migrate Head Version from disk. Skip.',
						$userFieldRow,
					));
					continue;
				}

				$errorCollection = new ErrorCollection();
				$attachedData = array(
					'MODULE_ID' => $moduleId,
					'OBJECT_ID' => $objectId,
					'VERSION_ID' => $newVersionId,
					'ENTITY_ID' => $userFieldRow['VALUE_ID'],
					'ENTITY_TYPE' => $connectorClass,
					'IS_EDITABLE' => 2,
				);
				$attachedModel = AttachedObject::add($attachedData, $errorCollection);
				if(!$attachedModel || $errorCollection->hasErrors())
				{
					$this->storeStorageId($userFieldRow['VALUE_ID']);
					$this->log(array(
						'Could not created attached object',
					));
					continue;
				}
				$connection->queryExecute("UPDATE b_uts_blog_comment SET UF_BLOG_COMMENT_FH = '{$attachedModel->getId()}' WHERE VALUE_ID = {$userFieldRow['VALUE_ID']}");

				$this->storeStorageId($userFieldRow['VALUE_ID']);
				continue;
			}
			//this is specific version of file
			else
			{
				list($newVersionId, $objectId) = $this->getLastVersionByBpId($dataFromValue['v']);
				if(!$newVersionId || !$objectId)
				{
					$this->storeStorageId($userFieldRow['VALUE_ID']);
					$this->log(array(
						'Could not find migrate Version from disk. Skip.',
						$userFieldRow,
					));
					continue;
				}

				$errorCollection = new ErrorCollection();
				$attachedData = array(
					'MODULE_ID' => $moduleId,
					'OBJECT_ID' => $objectId,
					'VERSION_ID' => $newVersionId,
					'ENTITY_ID' => $userFieldRow['VALUE_ID'],
					'ENTITY_TYPE' => $connectorClass,
					'IS_EDITABLE' => 2,
				);
				$attachedModel = AttachedObject::add($attachedData, $errorCollection);
				if(!$attachedModel || $errorCollection->hasErrors())
				{
					$this->storeStorageId($userFieldRow['VALUE_ID']);
					$this->log(array(
						'Could not created attached object',
						$userFieldRow,
					));
					continue;
				}

				$connection->queryExecute("UPDATE b_uts_blog_comment SET UF_BLOG_COMMENT_FH = '{$attachedModel->getId()}' WHERE VALUE_ID = {$userFieldRow['VALUE_ID']}");

				$this->storeStorageId($userFieldRow['VALUE_ID']);
				continue;
			}
		}
		$connection->queryExecute("UPDATE b_user_field SET USER_TYPE_ID = 'disk_version' WHERE ENTITY_ID = 'BLOG_COMMENT' AND XML_ID = 'UF_BLOG_COMMENT_FH'");

		$this->storeStorageId(0);

		$this->setStepFinished(__METHOD__);

		return;
	}

	protected function migrateDataCommonStorages()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return array(0, 0);
		}

		$lastId = $this->getLastIblockId();
		if($this->isOracle && $lastId == 0)
		{
			//we support 10g : (
			$maxId = $this->connection->queryScalar('SELECT MAX(ID) MAX FROM b_disk_object');
			$maxId++;
			$seqId = $this->connection->query('SELECT sq_b_disk_object.NEXTVAL NEXTVAL FROM DUAL')->fetch();
			$seqId = $seqId['NEXTVAL'];
			$diffId = $maxId - $seqId;
			if($diffId > 0)
			{
				$this->connection->queryExecute("ALTER SEQUENCE sq_b_disk_object INCREMENT BY {$diffId}");
				$this->connection->queryExecute("SELECT sq_b_disk_object.NEXTVAL NEXTVAL FROM DUAL");
				$this->connection->queryExecute("ALTER SEQUENCE sq_b_disk_object INCREMENT BY 1");
			}
		}


		foreach($this->getIblockIdsWithCommonFiles() as $iblock)
		{
			if($lastId > $iblock['ID'])
			{
				continue;
			}
			$this->abortIfNeeded();

			$this->log(array(
				__METHOD__,
				'start',
				$iblock['ID'],
			));

			$iblockId = (int)$iblock['ID'];
			$sqlHelper = $this->connection->getSqlHelper();
			$name = $sqlHelper->forSql($iblock['NAME']);
			$proxyType = $sqlHelper->forSql(ProxyType\Common::className());

			$storageRow = $this->connection->query("SELECT * FROM b_disk_storage WHERE XML_ID = '{$iblockId}' AND ENTITY_TYPE = '{$proxyType}'")->fetch();
			if(!$storageRow)
			{
				$this->log(array(
					__METHOD__,
					'Error. Could not find storage by XML_ID',
					$iblock['ID'],
					"WHERE XML_ID = '{$iblockId}' AND ENTITY_TYPE = '{$proxyType}'",
				));
				$this->storeIblockId($iblock['ID']);
				continue;
			}
			$storageId = $storageRow['ID'];

			$this->connection->queryExecute("
				INSERT INTO b_disk_object (NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, WEBDAV_IBLOCK_ID)
				SELECT '{$name}', 2, null, {$storageId}, null, ib.TIMESTAMP_X, ib.TIMESTAMP_X, ib.TIMESTAMP_X, ib.ID FROM b_iblock ib WHERE ib.ID = {$iblockId}

			");

			$rootObjectId = null;
			if($this->isMysql || $this->isMssql)
			{
				$rootObjectId = (int)$this->connection->getInsertedId();
			}
			elseif($this->isOracle)
			{
				$rootObjectId = $this->connection->queryScalar('SELECT MAX(ID) MAX FROM b_disk_object');
			}
			if(!$rootObjectId)
			{
				$this->log(array(
					__METHOD__,
					'Error. Could not insert root object',
					array(
						$name, $storageId, $iblockId,
					)
				));
				$this->storeIblockId($iblock['ID']);
				continue;
			}

			if($this->isMysql)
			{
				$sql = "
					UPDATE b_disk_object do,
					(
						SELECT NAME, MIN(ID) ID, COUNT(*) C
						FROM b_disk_object
						WHERE STORAGE_ID = {$storageId} AND PARENT_ID IS NULL AND ID <> {$rootObjectId}
						GROUP BY NAME
						HAVING C>1
					) dbl
					SET do.NAME = " . $this->getConcatFunction('do.NAME', 'do.ID') . "
					WHERE do.STORAGE_ID = {$storageId} AND do.PARENT_ID IS NULL AND do.ID <> {$rootObjectId}
						AND do.ID > dbl.ID
						AND do.NAME=dbl.NAME
				";
			}
			elseif($this->isOracle || $this->isMssql)
			{
				$sql = "
					UPDATE b_disk_object
						SET NAME = (
							SELECT " . $this->getConcatFunction('do.NAME', 'do.ID') . " FROM b_disk_object do,
							(
								SELECT NAME, MIN(ID) ID, COUNT(*) C
								FROM b_disk_object
								WHERE STORAGE_ID = {$storageId} AND PARENT_ID IS NULL AND ID <> {$rootObjectId}
								GROUP BY NAME
								HAVING COUNT(*)>1
							) dbl
							WHERE do.STORAGE_ID = {$storageId} AND do.PARENT_ID IS NULL AND do.ID <> {$rootObjectId}
								AND do.ID > dbl.ID
								AND do.NAME=dbl.NAME
								AND b_disk_object.ID = do.ID
						)

						WHERE EXISTS (
							SELECT " . $this->getConcatFunction('do.NAME', 'do.ID') . " FROM b_disk_object do,
							(
								SELECT NAME, MIN(ID) ID, COUNT(*) C
								FROM b_disk_object
								WHERE STORAGE_ID = {$storageId} AND PARENT_ID IS NULL AND ID <> {$rootObjectId}
								GROUP BY NAME
								HAVING COUNT(*)>1
							) dbl
							WHERE do.STORAGE_ID = {$storageId} AND do.PARENT_ID IS NULL AND do.ID <> {$rootObjectId}
								AND do.ID > dbl.ID
								AND do.NAME=dbl.NAME
								AND b_disk_object.ID = do.ID
						)
				";
			}

			$this->connection->queryExecute($sql);

			$this->connection->queryExecute("
				UPDATE b_disk_object SET PARENT_ID = {$rootObjectId} WHERE STORAGE_ID = {$storageId} AND PARENT_ID IS NULL AND ID <> {$rootObjectId}
			");

			$this->connection->queryExecute("
				UPDATE b_disk_storage SET ROOT_OBJECT_ID = {$rootObjectId} WHERE ID = {$storageId}
			");

			$this->moveCommonElements($storageId, $rootObjectId, $iblock);

			if($this->runWorkWithBizproc)
			{
				$classDocument = $this->sqlHelper->forSql(\Bitrix\Disk\BizProcDocumentCompatible::className());
				if($this->isMssql || $this->isMysql)
				{
					$this->connection->queryExecute("
						INSERT INTO b_bp_workflow_template (MODULE_ID, ENTITY, DOCUMENT_TYPE, AUTO_EXECUTE, NAME, DESCRIPTION, TEMPLATE, PARAMETERS, VARIABLES, MODIFIED, USER_ID, SYSTEM_CODE, ACTIVE)
						SELECT 'disk', '{$classDocument}', 'STORAGE_{$storageId}', AUTO_EXECUTE, NAME, DESCRIPTION, TEMPLATE, PARAMETERS, VARIABLES, MODIFIED, USER_ID, SYSTEM_CODE, ACTIVE
							FROM b_bp_workflow_template
							WHERE MODULE_ID = 'webdav' AND ENTITY = 'CIBlockDocumentWebdav' AND DOCUMENT_TYPE = 'iblock_{$iblock['ID']}'
					");
				}
				elseif($this->isOracle)
				{
					$this->connection->queryExecute("
						INSERT INTO b_bp_workflow_template (ID, MODULE_ID, ENTITY, DOCUMENT_TYPE, AUTO_EXECUTE, NAME, DESCRIPTION, TEMPLATE, PARAMETERS, VARIABLES, MODIFIED, USER_ID, SYSTEM_CODE, ACTIVE)
						SELECT SQ_B_BP_WORKFLOW_TEMPLATE.nextval, 'disk', '{$classDocument}', 'STORAGE_{$storageId}', AUTO_EXECUTE, NAME, DESCRIPTION, TEMPLATE, PARAMETERS, VARIABLES, MODIFIED, USER_ID, SYSTEM_CODE, ACTIVE
							FROM b_bp_workflow_template
							WHERE MODULE_ID = 'webdav' AND ENTITY = 'CIBlockDocumentWebdav' AND DOCUMENT_TYPE = 'iblock_{$iblock['ID']}'
					");
				}
			}

			$this->log(array(
				__METHOD__,
				'finish',
				$iblock['ID'],
			));
			$this->storeIblockId($iblock['ID']);
		}

		$this->storeIblockId(0);
		$this->setStepFinished(__METHOD__);
	}

	protected function moveStructureCommonStorages()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return array(0, 0);
		}

		$storageIdToIblock = array();
		$lastId = $this->getLastIblockId();
		foreach($this->getIblockIdsWithCommonFiles() as $iblock)
		{
			if($lastId > $iblock['ID'])
			{
				continue;
			}
			$this->abortIfNeeded();

			$this->log(array(
				__METHOD__,
				'start',
				$iblock['ID'],
			));

			$iblockId = (int)$iblock['ID'];
			$sqlHelper = $this->connection->getSqlHelper();
			$name = $sqlHelper->forSql($iblock['NAME']);
			$siteId = $sqlHelper->forSql($iblock['LID']);
			$proxyType = $sqlHelper->forSql(ProxyType\Common::className());
			$entityId = $sqlHelper->forSql($iblock['CODE'] == 'shared_files' ? 'shared_files_s1' : ($iblock['CODE']?:$iblock['ID']));

			if(empty($entityId))
			{
				$entityId = 'iblock' . $iblockId;
			}

			$potentialNonUnique = $this->connection->query("SELECT ID FROM b_disk_storage WHERE ENTITY_TYPE='{$proxyType}' AND ENTITY_ID='{$entityId}'")->fetch();
			if($potentialNonUnique)
			{
				$entityId = $entityId . $iblockId;
			}

			$miscData = $sqlHelper->forSql(serialize(array(
				'BASE_URL' => $iblock['LIST_PAGE_URL'],
				'BIZPROC_ENABLED' => $iblock['BIZPROC'] == 'Y',
			)));

			$this->connection->queryExecute("
				INSERT INTO b_disk_storage (NAME, MODULE_ID, ENTITY_TYPE, ENTITY_ID, ENTITY_MISC_DATA, ROOT_OBJECT_ID, USE_INTERNAL_RIGHTS, SITE_ID, XML_ID)
				VALUES ('{$name}', 'disk', '{$proxyType}', '{$entityId}', '$miscData', null, 1, '{$siteId}', '{$iblockId}')
			");

			$storageId = null;
			if($this->isMysql || $this->isMssql)
			{
				$storageId = (int)$this->connection->getInsertedId();
			}
			elseif($this->isOracle)
			{
				$storageId = $this->connection->queryScalar('SELECT MAX(ID) MAX FROM b_disk_storage');
			}

			if(!$storageId)
			{
				$this->log(array(
					__METHOD__,
					'Error. Could not insert storage',
					"
				INSERT INTO b_disk_storage (NAME, MODULE_ID, ENTITY_TYPE, ENTITY_ID, ENTITY_MISC_DATA, ROOT_OBJECT_ID, USE_INTERNAL_RIGHTS, SITE_ID, XML_ID)
				VALUES ('{$name}', 'disk', '{$proxyType}', '{$entityId}', '$miscData', null, 1, '{$siteId}', '{$iblockId}')
			",
					array(
						"('{$name}', 'disk', '{$proxyType}', '{$entityId}', '$miscData', null, 1, '{$siteId}', '{$iblockId}')",
					)
				));
				$this->storeIblockId($iblock['ID']);
				continue;
			}

			$storageIdToIblock[$storageId] = $iblock;
			//I know: child.IBLOCK_SECTION_ID can be NULL. But it is possible - unique key (NAME, PARENT_ID)
			if($this->isMysql)
			{
				$sql = "
					INSERT IGNORE INTO b_disk_object (ID, REAL_OBJECT_ID, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_SECTION_ID, WEBDAV_IBLOCK_ID)
					SELECT child.ID, child.ID, child.NAME, 2, child.CODE, {$storageId}, child.IBLOCK_SECTION_ID, child.DATE_CREATE, " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID
						FROM b_iblock_section child
						WHERE child.IBLOCK_ID = {$iblockId}

				";
			}
			elseif($this->isOracle || $this->isMssql)
			{
				$sql = "
					INSERT INTO b_disk_object (ID, REAL_OBJECT_ID, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_SECTION_ID, WEBDAV_IBLOCK_ID)
					SELECT child.ID, child.ID, child.NAME, 2, child.CODE, {$storageId}, child.IBLOCK_SECTION_ID, child.DATE_CREATE, " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID
						FROM b_iblock_section child
						WHERE child.IBLOCK_ID = {$iblockId}
							AND NOT EXISTS(SELECT 'x' FROM b_disk_object WHERE NAME = child.NAME AND PARENT_ID = child.IBLOCK_SECTION_ID AND child.IBLOCK_SECTION_ID IS NOT NULL)
				";
			}
			if($this->isMssql)
			{
				$this->connection->queryExecute('SET IDENTITY_INSERT b_disk_object ON');
			}

			$this->connection->queryExecute($sql);

			$this->connection->queryExecute("
				INSERT INTO b_disk_object (ID, REAL_OBJECT_ID, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_SECTION_ID, WEBDAV_IBLOCK_ID)
				SELECT child.ID, child.ID, " . $this->getConcatFunction('child.NAME', 'child.ID') . ", 2, child.CODE, {$storageId}, child.IBLOCK_SECTION_ID, child.DATE_CREATE, " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID
				FROM b_iblock_section child
				WHERE child.IBLOCK_ID = {$iblockId} AND NOT EXISTS(SELECT 'x' FROM b_disk_object do WHERE do.ID=child.ID)
			");

			if($this->isMssql)
			{
				$this->connection->queryExecute('SET IDENTITY_INSERT b_disk_object OFF');
			}

			$this->log(array(
				__METHOD__,
				'finish',
				$iblock['ID'],
			));

			$this->storeIblockId($iblock['ID']);
		}
		unset($iblock);

		$this->storeIblockId(0);
		$this->setStepFinished(__METHOD__);
	}

	protected function storeConvertedExtLinkCreationDate($creationDate)
	{
		COption::SetOptionString(
			'disk',
			'~migrateExtLinkCD',
			$creationDate
		);
	}

	protected function getLastConvertedExtLinkCreationDate()
	{
		return COption::getOptionString(
			'disk',
			'~migrateExtLinkCD',
			0
		);
	}

	protected function convertBadNames()
	{
 		if($this->isStepFinished(__METHOD__))
		{
			return array(0, 0);
		}

		$sqlHelper = $this->connection->getSqlHelper();

		if($this->isMysql)
		{
			$sql = "
				SELECT NAME, ID
				FROM b_disk_object
				WHERE NAME REGEXP CONCAT('[', x'01', '-', x'1F', '" . $sqlHelper->forSql(Bitrix\Main\IO\Path::INVALID_FILENAME_CHARS) . "]')
			";
		}
		elseif($this->isOracle)
		{
			$sql = "
				SELECT NAME, ID
				FROM b_disk_object
				WHERE REGEXP_LIKE(NAME, '[" . $sqlHelper->forSql(Bitrix\Main\IO\Path::INVALID_FILENAME_CHARS) . "]')
			";
		}
		elseif($this->isMssql)
		{
			$sql = "
				SELECT NAME, ID
				FROM b_disk_object
				WHERE PATINDEX('%[" . $sqlHelper->forSql(Bitrix\Main\IO\Path::INVALID_FILENAME_CHARS) . "]%', NAME) != 0
			";
		}
		$query = $this->connection->query($sql);
		while($query && $row = $query->fetch())
		{
			try
			{
				$this->connection->queryExecute("UPDATE b_disk_object SET NAME='".$sqlHelper->forSql(\Bitrix\Disk\Ui\Text::correctFilename($row['NAME']))."' WHERE ID=".$row['ID']);
			}
			catch(Exception $e)
			{
				$this->connection->queryExecute("UPDATE b_disk_object SET NAME='".$sqlHelper->forSql(Bitrix\Main\IO\Path::randomizeInvalidFilename($row['NAME']))."' WHERE ID=".$row['ID']);
			}
		}

		$this->setStepFinished(__METHOD__);
	}

	protected function setRealObjectId()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$this->connection->queryExecute("
			UPDATE b_disk_object SET REAL_OBJECT_ID = ID WHERE REAL_OBJECT_ID IS NULL
		");

		$this->setStepFinished(__METHOD__);
	}

	protected function migrateExternalLinks()
	{
		$this->abortIfNeeded();

 		if($this->isStepFinished(__METHOD__))
		{
			return array(0, 0);
		}
		$successCount = $failedCount = 0;
		$this->log(array(
			'Start migrate external links',
		));

		$lastCreationDate = $this->getLastConvertedExtLinkCreationDate();
		$connection = Application::getInstance()->getConnection();

		$query = $connection->query("
			SELECT * from b_webdav_ext_links ext
			WHERE ext.LINK_TYPE = 'M' AND ext.RESOURCE_TYPE = 'IBLOCK' AND ext.CREATION_DATE > {$lastCreationDate} AND (ext.ELEMENT_ID IS NULL OR ext.ELEMENT_ID = 0)
			ORDER BY ext.CREATION_DATE
		");
		while($query && $extLinkRow = $query->fetch())
		{
			$this->abortIfNeeded();

			$extLinkData = $this->prepareDataFromOldExtLink($extLinkRow);

			if(empty($extLinkRow['ROOT_SECTION_ID']) && !empty($extLinkRow['URL']))
			{
				$this->log(array(
					'Migrate simple ext.link from common storage (without symbolic link)'
				));
				$success = true;
				$pathItems = explode('/', ltrim($extLinkRow['URL'], '/'));
				$nameOfElement = array_pop($pathItems);
				$prevSectionId = 0;
				$prevIblockId = $extLinkRow['IBLOCK_ID'];
				foreach($pathItems as $path)
				{
					$pathFilter = array(
						'=NAME' => $path,
						'IBLOCK_ID' => $prevIblockId,
						'SECTION_ID' => $prevSectionId,
					);
					$section = CIBlockSection::getList(array(), $pathFilter, false, array('ID', 'IBLOCK_ID',))->fetch();
					if(!$section)
					{
						$success = false;
						break;
					}
					$prevSectionId = $section['ID'];
					$prevIblockId = $section['IBLOCK_ID'];
				}
				unset($path);

				if(!$success)
				{
					$this->log(array(
						'Could not migrate ext.link (resolve path)',
						$extLinkRow,
					));
					$this->storeConvertedExtLinkCreationDate($extLinkRow['CREATION_DATE']);
					continue;
				}

				$targetElement = CIBlockElement::getList(array(), array(
					'=NAME' => $nameOfElement,
					'IBLOCK_ID' => $prevIblockId,
					'SECTION_ID' => $prevSectionId
				), false, false, array('ID',))->fetch();

				if(!$targetElement || empty($targetElement['ID']))
				{
					$this->log(array(
						'Could not migrate ext.link (find iblockElement)',
						$extLinkRow,
					));
					$this->storeConvertedExtLinkCreationDate($extLinkRow['CREATION_DATE']);
					continue;
				}

				$targetElement['ID'] = (int)$targetElement['ID'];
				$result = $this->connection->query("SELECT ID FROM b_disk_object WHERE WEBDAV_ELEMENT_ID = {$targetElement['ID']}")->fetch();

				if(!$result || empty($result['ID']))
				{
					$this->log(array(
						'Could not migrate ext.link (find b_disk_object)',
						$extLinkRow,
					));
					$this->storeConvertedExtLinkCreationDate($extLinkRow['CREATION_DATE']);
					continue;
				}
				$extLinkData['OBJECT_ID'] = $result['ID'];
			}
			elseif(!empty($extLinkRow['ROOT_SECTION_ID']) && !empty($extLinkRow['URL']))
			{
				$this->log(array(
					'Migrate ext.link from user storage (may contains symbolic link)'
				));
				$success = true;
				$pathItems = explode('/', ltrim($extLinkRow['URL'], '/'));
				$nameOfElement = array_pop($pathItems);
				$prevSectionId = $extLinkRow['ROOT_SECTION_ID'];
				$prevIblockId = $extLinkRow['IBLOCK_ID'];
				foreach($pathItems as $path)
				{
					$pathFilter = array(
						'=NAME' => $path,
						'IBLOCK_ID' => $prevIblockId,
						'SECTION_ID' => $prevSectionId,
					);
					$section = CIBlockSection::getList(array(), $pathFilter, false, array('ID', 'IBLOCK_ID', 'UF_LINK_IBLOCK_ID', 'UF_LINK_SECTION_ID'))->fetch();
					if(!$section)
					{
						$success = false;
						break;
					}
					$prevSectionId = empty($section['UF_LINK_SECTION_ID'])? $section['ID'] : $section['UF_LINK_SECTION_ID'];
					$prevIblockId = empty($section['UF_LINK_IBLOCK_ID'])? $section['IBLOCK_ID'] : $section['UF_LINK_IBLOCK_ID'];
				}
				unset($path);

				if(!$success)
				{
					$this->log(array(
						'Could not migrate ext.link (resolve symbolic path)',
						$extLinkRow,
					));
					$this->storeConvertedExtLinkCreationDate($extLinkRow['CREATION_DATE']);
					continue;
				}

				$targetElement = CIBlockElement::getList(array(), array(
					'=NAME' => $nameOfElement,
					'IBLOCK_ID' => $prevIblockId,
					'SECTION_ID' => $prevSectionId
				), false, false, array('ID',))->fetch();

				if(!$targetElement || empty($targetElement['ID']))
				{
					$this->log(array(
						'Could not migrate ext.link (find iblockElement)',
						$extLinkRow,
					));
					$this->storeConvertedExtLinkCreationDate($extLinkRow['CREATION_DATE']);
					continue;
				}
				$targetElement['ID'] = (int)$targetElement['ID'];
				$result = $this->connection->query("SELECT ID FROM b_disk_object WHERE WEBDAV_ELEMENT_ID = {$targetElement['ID']}")->fetch();

				if(!$result || empty($result['ID']))
				{
					$this->log(array(
						'Could not migrate ext.link (find b_disk_object)',
						$extLinkRow,
					));
					$this->storeConvertedExtLinkCreationDate($extLinkRow['CREATION_DATE']);
					continue;
				}

				$extLinkData['OBJECT_ID'] = $result['ID'];
			}

			$result = ExternalLinkTable::add($extLinkData);
			if(!$result->isSuccess())
			{
				$this->log(array(
					'Could not add new ext.link',
					$extLinkData,
					$result->getErrors(),
				));
				$this->storeConvertedExtLinkCreationDate($extLinkRow['CREATION_DATE']);
				continue;
			}
			$this->log(array(
				'Success attempt',
				$result->getId(),
			));
			$this->storeConvertedExtLinkCreationDate($extLinkRow['CREATION_DATE']);
		}

		$this->setStepFinished(__METHOD__);
		$this->log(array(
			'Finish migrate external links',
		));

		return array($successCount, $failedCount);
	}

	protected function prepareDataFromOldExtLink(array $extLinkRow)
	{
		$extLinkData = array(
			'OBJECT_ID' => !empty($extLinkRow['DISK_ID'])? $extLinkRow['DISK_ID'] : null,
			'CREATED_BY' => $extLinkRow['USER_ID'],
			'HASH' => substr($extLinkRow['HASH'], 0, 32),
			'DESCRIPTION' => $extLinkRow['DESCRIPTION'],
			'DOWNLOAD_COUNT' => $extLinkRow['DOWNLOAD_COUNT'],
			'TYPE' => ExternalLinkTable::TYPE_MANUAL,
			'CREATE_TIME' => DateTime::createFromTimestamp($extLinkRow['CREATION_DATE']),
		);
		//157680000 = 5*365*24*60*60
		if($extLinkRow['LIFETIME'] - time() < 157680000)
		{
			//limited life
			$extLinkData['DEATH_TIME'] = DateTime::createFromTimestamp($extLinkRow['LIFETIME']);
		}
		if(!empty($extLinkRow['PASSWORD']) && !empty($extLinkRow['SALT']))
		{
			$extLinkData['PASSWORD'] = $extLinkRow['PASSWORD'];
			$extLinkData['SALT'] = $extLinkRow['SALT'];

			return $extLinkData;
		}

		return $extLinkData;
	}

	protected function upDbHelpers()
	{
		if(!$this->connection->getTableField('b_disk_object', 'WEBDAV_ELEMENT_ID'))
		{
			$this->connection->queryExecute("ALTER TABLE b_disk_object ADD WEBDAV_ELEMENT_ID INT");
		}
		if(!$this->connection->getTableField('b_disk_object', 'WEBDAV_SECTION_ID'))
		{
			$this->connection->queryExecute("ALTER TABLE b_disk_object ADD WEBDAV_SECTION_ID INT");
		}
		if(!$this->connection->getTableField('b_disk_object', 'WEBDAV_IBLOCK_ID'))
		{
			$this->connection->queryExecute("ALTER TABLE b_disk_object ADD WEBDAV_IBLOCK_ID INT");
		}
		if(!$this->connection->getTableField('b_disk_version', 'BP_VERSION_ID'))
		{
			$this->connection->queryExecute("ALTER TABLE b_disk_version ADD BP_VERSION_ID INT");
		}

		try
		{
			$this->connection->createIndex('b_disk_storage', 'IX_TMP_DISK_S_1', 'ROOT_OBJECT_ID');
			$this->connection->createIndex('b_disk_object', 'IX_TMP_DISK_E_2', 'WEBDAV_ELEMENT_ID');
			$this->connection->createIndex('b_disk_object', 'IX_TMP_DISK_S_2', 'WEBDAV_SECTION_ID');
			$this->connection->createIndex('b_disk_object', 'IX_TMP_DISK_IIS_2', 'WEBDAV_IBLOCK_ID');
		}
		catch(Exception $e)
		{
		}
	}

	protected function downDbHelpers()
	{
		/*
		$this->connection->queryExecute("DROP INDEX IX_TMP_DISK_S_1 on b_disk_storage");


		$this->connection->queryExecute("DROP INDEX IX_TMP_DISK_E_2 on b_disk_object");
		$this->connection->queryExecute("DROP INDEX IX_TMP_DISK_S_2 on b_disk_object");
		$this->connection->queryExecute("DROP INDEX IX_TMP_DISK_IIS_2 on b_disk_object");

		$this->connection->queryExecute("ALTER TABLE b_disk_object DROP COLUMN WEBDAV_ELEMENT_ID");
		$this->connection->queryExecute("ALTER TABLE b_disk_object DROP COLUMN WEBDAV_IBLOCK_ID");
		$this->connection->queryExecute("ALTER TABLE b_disk_object DROP COLUMN WEBDAV_SECTION_ID");

		$this->connection->queryExecute("ALTER TABLE b_disk_version DROP COLUMN BP_VERSION_ID");
		*/
	}

	private function detectMigrationAfterDeletingModule()
	{
		$hasOption = $this->connection->query("SELECT * FROM b_option WHERE MODULE_ID='disk'")->fetch();
		$hasObject = $this->connection->query("SELECT * FROM b_disk_object")->fetch();
		return !$hasOption && $hasObject;
	}

	public function run()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return self::STATUS_FINISH;
		}

		define('DISK_MIGRATE_MODE', true);
		$this->checkRequired();

		if(\Bitrix\Disk\Configuration::isSuccessfullyConverted())
		{
			return self::STATUS_FINISH;
		}

		if(!Option::get('disk', 'process_converted', false))
		{
			try
			{
				$this->downDbHelpers();
			}
			catch(Exception $e){}

			$this->upDbHelpers();

			if($this->detectMigrationAfterDeletingModule())
			{
				$this->processFinallyActions();

				return self::STATUS_FINISH;
			}
			$this->registerHandlerToBlockIblock();

			Option::set(
				'disk',
				'successfully_converted',
				false
			);
			Option::set(
				'disk',
				'process_converted',
				'Y'
			);
			Option::set(
				'webdav',
				'successfully_converted',
				false
			);
			Option::set(
				'webdav',
				'process_converted',
				'Y'
			);
		}
		$this->runResorting();

		$this->migrateOptions();

		$this->abortIfNeeded();
		$this->moveUsers();

		$this->abortIfNeeded();
		$this->moveGroup();

		$this->abortIfNeeded();
		$this->moveSections();

		$this->abortIfNeeded();
		$this->moveNonUniqueSections();

		$this->abortIfNeeded();
		$this->moveStructureCommonStorages();

		$this->abortIfNeeded();
		$this->migrateDataCommonStorages();

		$this->abortIfNeeded();
		$this->moveSpecificXml();

		$this->abortIfNeeded();
		$this->moveElements();

		$this->abortIfNeeded();
		$this->setSymbolicLinks();

		$this->abortIfNeeded();
		$this->moveExternalLinks();

		$this->abortIfNeeded();
		$this->moveSharings();

		$this->abortIfNeeded();
		$this->migrateExternalLinks();

		$this->abortIfNeeded();
		self::RUN_STEPS_WITH_MODIFY_DATA && $this->moveWebdavElement();

		$this->abortIfNeeded();
		self::RUN_STEPS_WITH_MODIFY_DATA && $this->migrateWebdavElementInSocnet();

		$this->abortIfNeeded();
		$this->fillSelfObjectPath();

		$this->abortIfNeeded();
		$this->fillObjectPath();

		$this->abortIfNeeded();
		$this->migrateMetaFolders();

		$this->abortIfNeeded();
		//head
		self::RUN_STEPS_WITH_MODIFY_DATA && $this->migrateUfHead();

		$this->abortIfNeeded();
		$this->migrateTrashFiles();

		$this->abortIfNeeded();
		$this->migrateTrashFolders();

		$this->abortIfNeeded();
		$this->deleteTrashFolders();

		$this->abortIfNeeded();
		$this->fillObjectRights();

		$this->abortIfNeeded();
		$this->deletePairNegativeRights();

		$this->abortIfNeeded();
		!$this->publishDocs && $this->recalcRightsOnUnPublishObject();

		$this->abortIfNeeded();
		$this->migrateVersion();

		$this->abortIfNeeded();
		$this->migrateHeadVersion();

		$this->abortIfNeeded();
		$this->convertBadNames();

		$this->setRealObjectId();

		$this->abortIfNeeded();
		$this->generateEmptyUserStorages();

		$this->abortIfNeeded();
		$this->markPendingObjectAsDeleted();

		$this->abortIfNeeded();
		$this->repairStorageId();

		$this->abortIfNeeded();
		self::RUN_STEPS_WITH_MODIFY_DATA && $this->migrateWebdavElementHistory();

		$this->abortIfNeeded();
		self::RUN_STEPS_WITH_MODIFY_DATA && $this->migrateCrmData();

		$this->abortIfNeeded();
		self::RUN_STEPS_WITH_MODIFY_DATA && $this->migrateSearchData();

		$this->abortIfNeeded();
		self::RUN_STEPS_WITH_MODIFY_DATA && $this->disableIndexIblocks();

		$this->abortIfNeeded();
		$this->addMissedGroupStorages();

		$this->downDbHelpers();

		//patch links in public files
		if (!ModuleManager::isModuleInstalled('bitrix24'))
		{
			$extranetSiteId = (ModuleManager::isModuleInstalled("extranet") ? Option::get("extranet", "extranet_site") : false);

			$rsSite = SiteTable::getList();
			while ($arSite = $rsSite->Fetch())
			{
				$folder = "company";
				if (
					$extranetSiteId
					&& $arSite["LID"] == $extranetSiteId
				)
					$folder = "contacts";

				$siteDocRoot = (strlen($arSite["DOC_ROOT"]) > 0 ? $arSite["DOC_ROOT"] : $_SERVER["DOCUMENT_ROOT"]);
				$siteDir = (strlen($arSite["DIR"]) > 0 ? $arSite["DIR"] : "/");

				$filePath = $siteDocRoot.$siteDir.'.left.menu_ext.php';
				$fp = null;
				if(file_exists($filePath))
				{
					$fp = fopen($filePath, 'r');
				}

				if (
					$fp !== false
					&& $fp !== null
				)
				{
					$fileContents = fread($fp, filesize($filePath));
					fclose($fp);

					preg_match('/'.$folder.'\/personal\/user\/\"\.\$USER_ID\.\"\/files\/lib\//si', $fileContents, $matches);
					if (!empty($matches[0]))
					{
						$fileContentsNew = str_replace(
							$folder.'/personal/user/".$USER_ID."/files/lib/',
							$folder.'/personal/user/".$USER_ID."/disk/path/',
							$fileContents
						);

						if ($fileContentsNew != $fileContents)
						{
							$fp = fopen($filePath, 'w');
							fwrite($fp, $fileContentsNew);
							fclose($fp);
						}
					}
				}
			}

			//clean cached inline img (for resize with signature)
			$cache = new CPHPCache;
			$cache->CleanDir("/blog/comment");
			$cache->CleanDir("/blog/socnet_post");
			$cache->CleanDir("/sonet/log");

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->CleanDir("b_user_field");
			}
		}

		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			//clean left menu.
			global $CACHE_MANAGER;
			$CACHE_MANAGER->clearByTag('sonet_group');
		}

		$this->processFinallyActions();

		$this->setStepFinished(__METHOD__);

		return self::STATUS_FINISH;
	}


	protected function generateEmptyUserStorages()
	{
 		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$proxyType = $this->sqlHelper->forSql(ProxyType\User::className());
		$result = $this->connection->query("
				SELECT u.ID
				FROM b_user u
				WHERE NOT EXISTS(SELECT 'x' FROM b_disk_storage s WHERE u.ID=s.ENTITY_ID AND s.ENTITY_TYPE='{$proxyType}')
		");

		while($user = $result->fetch())
		{
			Driver::getInstance()->addUserStorage($user['ID']);
		}

		//
		/*
		if($this->getIblockWithUserFiles() as $iblock)
		{

			INSERT INTO b_disk_storage()
			SELECT ID,
			FROM b_user
			WHERE NOT EXISTS(SELECT 'x' FROM b_disk_storage WHERE u.ID=st.ENTITY_ID)

			INSERT INTO b_disk_object()
			SELECT
			FROM b_disk_storage
			WHERE

			INSERT INTO b_disk_path()
			SELECT
			FROM

			INSERT INTO b_disk_right()
			SELECT
			FROM

			INSERT INTO b_disk_simple_right()
			SELECT
			FROM

		}
		*/
		$this->setStepFinished(__METHOD__);
	}

	protected function fillObjectPath()
	{
 		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$maxInnerJoinDepth = 32;
		$currentDepth = $this->getStorageId();
		$emptyInsert = false;
		while($currentDepth < $maxInnerJoinDepth && !$emptyInsert)
		{
			$this->abortIfNeeded();

			$query = "
				INSERT INTO b_disk_object_path (OBJECT_ID, PARENT_ID, DEPTH_LEVEL)
					SELECT b.ID, t.ID, " . ($currentDepth+1) . " FROM b_disk_object t
			";

			$finalQuery = $query;
			for($i = 0;$i < $currentDepth;$i++)
			{
				$finalQuery .= " INNER JOIN b_disk_object t" . ($i+1) . " ON t" . ($i?: '') . ".ID=t" . ($i+1) . ".PARENT_ID ";

			}
			$lastJoin = " INNER JOIN b_disk_object b ON t" . ($currentDepth?:'' ) . ".ID=b.PARENT_ID ";
			$finalQuery = $finalQuery .$lastJoin;

			$this->connection->queryExecute($finalQuery);
			$emptyInsert = $this->connection->getAffectedRowsCount() <= 0;


			$currentDepth++;
			$this->storeStorageId($currentDepth);
		}


		//reset. Will be in future. Coming soon.
		$this->storeStorageId(0);
		$this->storeFillPathParentId(0);

		$this->setStepFinished(__METHOD__);
	}
	protected function moveSpecificXml()
	{
 		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$type = $this->sqlHelper->forSql(ProxyType\Common::className());
		if($this->isMysql)
		{
			$sql = "
				UPDATE b_disk_object obj
				INNER JOIN b_iblock_section sec ON sec.ID = obj.WEBDAV_ELEMENT_ID
				INNER JOIN b_disk_storage st ON st.ID = obj.STORAGE_ID
				SET obj.XML_ID = sec.XML_ID, obj.CODE = sec.XML_ID
				WHERE st.MODULE_ID = 'disk' AND st.ENTITY_TYPE = '{$type}' AND
				sec.XML_ID in ('CRM_CALL_RECORDS', 'CRM_EMAIL_ATTACHMENTS', 'VI_CALLS')
			";
		}
		elseif($this->isOracle || $this->isMssql)
		{
			$sql = "
				UPDATE b_disk_object
				SET XML_ID = (SELECT sec.XML_ID FROM b_disk_object obj
					INNER JOIN b_iblock_section sec ON sec.ID = obj.WEBDAV_ELEMENT_ID
					INNER JOIN b_disk_storage st ON st.ID = obj.STORAGE_ID
					WHERE st.MODULE_ID = 'disk' AND st.ENTITY_TYPE = '{$type}' AND
					sec.XML_ID in ('CRM_CALL_RECORDS', 'CRM_EMAIL_ATTACHMENTS', 'VI_CALLS')
					AND b_disk_object.ID = obj.ID
				), CODE = (SELECT sec.XML_ID FROM b_disk_object obj
					INNER JOIN b_iblock_section sec ON sec.ID = obj.WEBDAV_ELEMENT_ID
					INNER JOIN b_disk_storage st ON st.ID = obj.STORAGE_ID
					WHERE st.MODULE_ID = 'disk' AND st.ENTITY_TYPE = '{$type}' AND
					sec.XML_ID in ('CRM_CALL_RECORDS', 'CRM_EMAIL_ATTACHMENTS', 'VI_CALLS')
					AND b_disk_object.ID = obj.ID
				)
				WHERE EXISTS (SELECT sec.XML_ID FROM b_disk_object obj
					INNER JOIN b_iblock_section sec ON sec.ID = obj.WEBDAV_ELEMENT_ID
					INNER JOIN b_disk_storage st ON st.ID = obj.STORAGE_ID
					WHERE st.MODULE_ID = 'disk' AND st.ENTITY_TYPE = '{$type}' AND
					sec.XML_ID in ('CRM_CALL_RECORDS', 'CRM_EMAIL_ATTACHMENTS', 'VI_CALLS')
					AND b_disk_object.ID = obj.ID
				)
			";
		}
		$this->connection->queryExecute($sql);


		$this->setStepFinished(__METHOD__);
	}

	protected function migrateCrmData()
	{
 		if($this->isStepFinished(__METHOD__))
		{
			return;
		}
		//CCrmActivityStorageType::Webdav 2
		//CCrmActivityStorageType::Disk 3

		if(!$this->connection->isTableExists('b_crm_act'))
		{
			$this->setStepFinished(__METHOD__);
			return;
		}

		if($this->isMysql)
		{
			$sql = "
				UPDATE b_crm_act_elem crm
				INNER JOIN b_disk_object obj ON obj.WEBDAV_ELEMENT_ID = crm.ELEMENT_ID and crm.STORAGE_TYPE_ID = 2
				SET crm.ELEMENT_ID = obj.ID, crm.STORAGE_TYPE_ID = 3
			";
		}
		elseif($this->isOracle || $this->isMssql)
		{
			$sql = "
				UPDATE b_crm_act_elem
				SET STORAGE_TYPE_ID = 3,
				ELEMENT_ID = (SELECT obj.ID FROM b_crm_act_elem crm
						INNER JOIN b_disk_object obj ON obj.WEBDAV_ELEMENT_ID = crm.ELEMENT_ID and crm.STORAGE_TYPE_ID = 2
						WHERE b_crm_act_elem.ACTIVITY_ID = crm.ACTIVITY_ID AND b_crm_act_elem.STORAGE_TYPE_ID = crm.STORAGE_TYPE_ID AND b_crm_act_elem.ELEMENT_ID = crm.ELEMENT_ID
				)
				WHERE EXISTS (SELECT obj.ID FROM b_crm_act_elem crm
						INNER JOIN b_disk_object obj ON obj.WEBDAV_ELEMENT_ID = crm.ELEMENT_ID and crm.STORAGE_TYPE_ID = 2
						WHERE b_crm_act_elem.ACTIVITY_ID = crm.ACTIVITY_ID AND b_crm_act_elem.STORAGE_TYPE_ID = crm.STORAGE_TYPE_ID AND b_crm_act_elem.ELEMENT_ID = crm.ELEMENT_ID
				)
			";
		}
		$this->connection->queryExecute($sql);

		if($this->isMssql)
		{
			$this->connection->queryExecute("
				UPDATE b_crm_act
		            SET STORAGE_ELEMENT_IDS = CAST(ID as VARCHAR(255)), STORAGE_TYPE_ID = 3 WHERE STORAGE_TYPE_ID = 2
			");
		}
		else
		{
			$this->connection->queryExecute("
				UPDATE b_crm_act
		            SET STORAGE_ELEMENT_IDS = ID, STORAGE_TYPE_ID = 3 WHERE STORAGE_TYPE_ID = 2
			");
		}

		if($this->isMysql)
		{
			$sql = "
				UPDATE b_crm_quote_elem crm
				INNER JOIN b_disk_object obj ON obj.WEBDAV_ELEMENT_ID = crm.ELEMENT_ID and crm.STORAGE_TYPE_ID = 2
				SET crm.ELEMENT_ID = obj.ID, crm.STORAGE_TYPE_ID = 3
			";
		}
		elseif($this->isOracle || $this->isMssql)
		{
			$sql = "
				UPDATE b_crm_quote_elem
				SET STORAGE_TYPE_ID = 3,
				ELEMENT_ID = (SELECT obj.ID FROM b_crm_quote_elem crm
						INNER JOIN b_disk_object obj ON obj.WEBDAV_ELEMENT_ID = crm.ELEMENT_ID and crm.STORAGE_TYPE_ID = 2
						WHERE b_crm_quote_elem.QUOTE_ID = crm.QUOTE_ID AND b_crm_quote_elem.STORAGE_TYPE_ID = crm.STORAGE_TYPE_ID AND b_crm_quote_elem.ELEMENT_ID = crm.ELEMENT_ID
				)

				WHERE EXISTS (SELECT obj.ID FROM b_crm_quote_elem crm
						INNER JOIN b_disk_object obj ON obj.WEBDAV_ELEMENT_ID = crm.ELEMENT_ID and crm.STORAGE_TYPE_ID = 2
						WHERE b_crm_quote_elem.QUOTE_ID = crm.QUOTE_ID AND b_crm_quote_elem.STORAGE_TYPE_ID = crm.STORAGE_TYPE_ID AND b_crm_quote_elem.ELEMENT_ID = crm.ELEMENT_ID
				)

				";
		}
		$this->connection->queryExecute($sql);

		if($this->isMssql)
		{
			$this->connection->queryExecute("
				UPDATE b_crm_quote
		            SET STORAGE_ELEMENT_IDS = CAST(ID as VARCHAR(255)), STORAGE_TYPE_ID = 3 WHERE STORAGE_TYPE_ID = 2
			");
		}
		else
		{
			$this->connection->queryExecute("
				UPDATE b_crm_quote
				SET STORAGE_ELEMENT_IDS = ID, STORAGE_TYPE_ID = 3 WHERE STORAGE_TYPE_ID = 2
			");
		}

		$this->setStepFinished(__METHOD__);
	}


	protected function migrateSearchData()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		global $DB;
		$newScheme = $DB->Query('SELECT ENTITY_TYPE_ID FROM b_search_content WHERE 1=0', true);
		if($newScheme)
		{
			$this->migrateSearchDataNewScheme();
		}
		else
		{
			$this->migrateSearchDataOldScheme();
		}

		$this->setStepFinished(__METHOD__);
	}

	private function migrateSearchDataOldScheme()
	{
		$this->connection->queryExecute("
			UPDATE b_search_content_site SET URL = ''
			WHERE SEARCH_CONTENT_ID IN (SELECT c.ID FROM b_search_content c INNER JOIN b_disk_object d ON c.ITEM_ID = d.WEBDAV_ELEMENT_ID WHERE c.PARAM1 = 'library')
			");

		$this->connection->queryExecute("
				DELETE FROM b_search_content_param WHERE SEARCH_CONTENT_ID IN (SELECT c.ID FROM b_search_content c INNER JOIN b_disk_object d ON c.ITEM_ID = d.WEBDAV_ELEMENT_ID WHERE c.PARAM1 = 'library')
			");

		/*
		SELECT c.ID as SEARCH_CONTENT_ID, d.ID, d.STORAGE_ID, d.PARENT_ID
		FROM b_search_content c INNER JOIN b_disk_object d ON c.ITEM_ID = d.WEBDAV_ELEMENT_ID
		WHERE c.PARAM1 = 'library' AND c.MODULE_ID='iblock' AND ENTITY_TYPE_ID='IBLOCK_ELEMENT'
		*/

		if($this->isMysql)
		{
			$sql = "
				UPDATE IGNORE b_search_content c
					INNER JOIN b_disk_object d ON c.ITEM_ID = d.WEBDAV_ELEMENT_ID
				SET
					c.MODULE_ID = 'disk',
					c.ITEM_ID = " . $this->getConcatFunction("'FILE_'", 'd.ID') . ",
					c.URL = " . $this->getConcatFunction("'=ID='", 'd.ID') . ",
					c.PARAM1 = d.STORAGE_ID,
					c.PARAM2 = d.PARENT_ID
				WHERE c.PARAM1 = 'library'
				";
		}
		elseif($this->isOracle)
		{
			$sql = "
				UPDATE b_search_content c
				SET
					c.MODULE_ID = 'disk',
					c.ITEM_ID = (SELECT " . $this->getConcatFunction("'FILE_'", 'd.ID') . " FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = TO_CHAR(d.WEBDAV_ELEMENT_ID) WHERE cc.ID = c.ID AND c.PARAM1 = 'library'),
					c.URL = (SELECT " . $this->getConcatFunction("'=ID='", 'd.ID') . " FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = TO_CHAR(d.WEBDAV_ELEMENT_ID) WHERE cc.ID = c.ID AND c.PARAM1 = 'library'),
					c.PARAM1 = (SELECT d.STORAGE_ID FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = TO_CHAR(d.WEBDAV_ELEMENT_ID) WHERE cc.ID = c.ID AND c.PARAM1 = 'library'),
					c.PARAM2 = (SELECT d.PARENT_ID FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = TO_CHAR(d.WEBDAV_ELEMENT_ID) WHERE cc.ID = c.ID AND c.PARAM1 = 'library')

				WHERE c.PARAM1 = 'library' AND EXISTS (SELECT d.PARENT_ID FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = TO_CHAR(d.WEBDAV_ELEMENT_ID) WHERE cc.ID = c.ID AND c.PARAM1 = 'library')
				";
		}
		elseif($this->isMssql)
		{
			$sql = "
				UPDATE b_search_content
				SET
					MODULE_ID = 'disk',
					ITEM_ID = (SELECT " . $this->getConcatFunction("'FILE_'", 'd.ID') . " FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = CAST(d.WEBDAV_ELEMENT_ID as VARCHAR(255)) WHERE cc.ID = b_search_content.ID AND b_search_content.PARAM1 = 'library'),
					URL = (SELECT " . $this->getConcatFunction("'=ID='", 'd.ID') . " FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = CAST(d.WEBDAV_ELEMENT_ID as VARCHAR(255)) WHERE cc.ID = b_search_content.ID AND b_search_content.PARAM1 = 'library'),
					PARAM1 = (SELECT d.STORAGE_ID FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = CAST(d.WEBDAV_ELEMENT_ID as VARCHAR(255)) WHERE cc.ID = b_search_content.ID AND b_search_content.PARAM1 = 'library'),
					PARAM2 = (SELECT d.PARENT_ID FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = CAST(d.WEBDAV_ELEMENT_ID as VARCHAR(255)) WHERE cc.ID = b_search_content.ID AND b_search_content.PARAM1 = 'library')

				WHERE PARAM1 = 'library' AND EXISTS (SELECT d.PARENT_ID FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = CAST(d.WEBDAV_ELEMENT_ID as VARCHAR(255)) WHERE cc.ID = b_search_content.ID AND b_search_content.PARAM1 = 'library')
				";
		}
		$this->connection->queryExecute($sql);
	}

	private function migrateSearchDataNewScheme()
	{
		$this->connection->queryExecute("
			UPDATE b_search_content_site SET URL = ''
			WHERE SEARCH_CONTENT_ID IN (SELECT c.ID FROM b_search_content c INNER JOIN b_disk_object d ON c.ITEM_ID = d.WEBDAV_ELEMENT_ID WHERE c.PARAM1 = 'library' AND c.ENTITY_TYPE_ID='IBLOCK_ELEMENT')
			");

		$this->connection->queryExecute("
				DELETE FROM b_search_content_param WHERE SEARCH_CONTENT_ID IN (SELECT c.ID FROM b_search_content c INNER JOIN b_disk_object d ON c.ITEM_ID = d.WEBDAV_ELEMENT_ID WHERE c.PARAM1 = 'library' AND c.ENTITY_TYPE_ID='IBLOCK_ELEMENT')
			");

		/*
		SELECT c.ID as SEARCH_CONTENT_ID, d.ID, d.STORAGE_ID, d.PARENT_ID
		FROM b_search_content c INNER JOIN b_disk_object d ON c.ITEM_ID = d.WEBDAV_ELEMENT_ID
		WHERE c.PARAM1 = 'library' AND c.MODULE_ID='iblock' AND ENTITY_TYPE_ID='IBLOCK_ELEMENT'
		*/

		if($this->isMysql)
		{
			$sql = "
				UPDATE IGNORE b_search_content c
					INNER JOIN b_disk_object d ON c.ITEM_ID = d.WEBDAV_ELEMENT_ID
				SET
					c.MODULE_ID = 'disk',
					c.ITEM_ID = " . $this->getConcatFunction("'FILE_'", 'd.ID') . ",
					c.ENTITY_TYPE_ID = '',
					c.ENTITY_ID = '',
					c.URL = " . $this->getConcatFunction("'=ID='", 'd.ID') . ",
					c.PARAM1 = d.STORAGE_ID,
					c.PARAM2 = d.PARENT_ID
				WHERE c.PARAM1 = 'library' AND c.ENTITY_TYPE_ID='IBLOCK_ELEMENT'
				";
		}
		elseif($this->isOracle)
		{
			$sql = "
				UPDATE b_search_content c
				SET
					c.MODULE_ID = 'disk',
					c.ITEM_ID = (SELECT " . $this->getConcatFunction("'FILE_'", 'd.ID') . " FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = TO_CHAR(d.WEBDAV_ELEMENT_ID) WHERE cc.ID = c.ID AND c.PARAM1 = 'library' AND c.ENTITY_TYPE_ID='IBLOCK_ELEMENT'),
					c.ENTITY_TYPE_ID = '',
					c.ENTITY_ID = '',
					c.URL = (SELECT " . $this->getConcatFunction("'=ID='", 'd.ID') . " FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = TO_CHAR(d.WEBDAV_ELEMENT_ID) WHERE cc.ID = c.ID AND c.PARAM1 = 'library' AND c.ENTITY_TYPE_ID='IBLOCK_ELEMENT'),
					c.PARAM1 = (SELECT d.STORAGE_ID FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = TO_CHAR(d.WEBDAV_ELEMENT_ID) WHERE cc.ID = c.ID AND c.PARAM1 = 'library' AND c.ENTITY_TYPE_ID='IBLOCK_ELEMENT'),
					c.PARAM2 = (SELECT d.PARENT_ID FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = TO_CHAR(d.WEBDAV_ELEMENT_ID) WHERE cc.ID = c.ID AND c.PARAM1 = 'library' AND c.ENTITY_TYPE_ID='IBLOCK_ELEMENT')

				WHERE c.PARAM1 = 'library' AND c.ENTITY_TYPE_ID='IBLOCK_ELEMENT' AND EXISTS (SELECT d.PARENT_ID FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = TO_CHAR(d.WEBDAV_ELEMENT_ID) WHERE cc.ID = c.ID AND c.PARAM1 = 'library' AND c.ENTITY_TYPE_ID='IBLOCK_ELEMENT')
				";
		}
		elseif($this->isMssql)
		{
			$sql = "
				UPDATE b_search_content
				SET
					MODULE_ID = 'disk',
					ITEM_ID = (SELECT " . $this->getConcatFunction("'FILE_'", 'd.ID') . " FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = CAST(d.WEBDAV_ELEMENT_ID as VARCHAR(255)) WHERE cc.ID = b_search_content.ID AND b_search_content.PARAM1 = 'library' AND b_search_content.ENTITY_TYPE_ID='IBLOCK_ELEMENT'),
					ENTITY_TYPE_ID = '',
					ENTITY_ID = '',
					URL = (SELECT " . $this->getConcatFunction("'=ID='", 'd.ID') . " FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = CAST(d.WEBDAV_ELEMENT_ID as VARCHAR(255)) WHERE cc.ID = b_search_content.ID AND b_search_content.PARAM1 = 'library' AND b_search_content.ENTITY_TYPE_ID='IBLOCK_ELEMENT'),
					PARAM1 = (SELECT d.STORAGE_ID FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = CAST(d.WEBDAV_ELEMENT_ID as VARCHAR(255)) WHERE cc.ID = b_search_content.ID AND b_search_content.PARAM1 = 'library' AND b_search_content.ENTITY_TYPE_ID='IBLOCK_ELEMENT'),
					PARAM2 = (SELECT d.PARENT_ID FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = CAST(d.WEBDAV_ELEMENT_ID as VARCHAR(255)) WHERE cc.ID = b_search_content.ID AND b_search_content.PARAM1 = 'library' AND b_search_content.ENTITY_TYPE_ID='IBLOCK_ELEMENT')

				WHERE PARAM1 = 'library' AND ENTITY_TYPE_ID='IBLOCK_ELEMENT' AND EXISTS (SELECT d.PARENT_ID FROM b_search_content cc INNER JOIN b_disk_object d ON cc.ITEM_ID = CAST(d.WEBDAV_ELEMENT_ID as VARCHAR(255)) WHERE cc.ID = b_search_content.ID AND b_search_content.PARAM1 = 'library' AND b_search_content.ENTITY_TYPE_ID='IBLOCK_ELEMENT')
				";
		}
		$this->connection->queryExecute($sql);
	}

	protected function disableIndexIblocks()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$this->connection->queryExecute("
			UPDATE b_iblock
				SET
					INDEX_ELEMENT = 'N',
					INDEX_SECTION = 'N'
			WHERE IBLOCK_TYPE_ID  = 'library'
		");

		$query = $this->connection->query("
			SELECT ID FROM b_iblock WHERE IBLOCK_TYPE_ID  = 'library'
		");
		while($row = $query->fetch())
		{
			if(!empty($row['ID']))
			{
				CIBlock::CleanCache($row['ID']);
			}
		}

		$this->setStepFinished(__METHOD__);
	}

	protected function convertIblockTaskToDiskTask($taskId)
	{
		static $map = array();

		if(isset($map[$taskId]) || array_key_exists($taskId, $map))
		{
			return $map[$taskId];
		}

		$iblockTask = $this->getIblockTaskById($taskId);
		if($iblockTask === null)
		{
			return self::COULD_NOT_FIND_IBLOCK_TASK;
		}

		switch($iblockTask['NAME'])
		{
			case 'iblock_read':
			case 'iblock_admin_read':
				return $this->getDiskTaskIdByName(RightsManager::TASK_READ);
			case 'iblock_limited_edit':
			case 'iblock_full_edit':
				return $this->getDiskTaskIdByName(RightsManager::TASK_EDIT);
			case 'iblock_admin_add':
				return $this->getDiskTaskIdByName(RightsManager::TASK_ADD);
			case 'iblock_full':
				return $this->getDiskTaskIdByName(RightsManager::TASK_FULL);
		}

		$iblockOperations = $this->getIblockOperationsByTask($taskId);

		if(empty($iblockOperations))
		{
			$map[$taskId] = self::DENY_TASK;
			//deny task
			return $map[$taskId];
		}

		$iblockOperationsMapped = array();
		foreach($iblockOperations as $name)
		{
			$diskName = $this->mapRightOperation($name);
			if(is_array($diskName))
			{
				foreach($diskName as $item)
				{
					$iblockOperationsMapped[$item] = $item;
				}
				unset($item);
			}
			elseif($diskName)
			{
				$iblockOperationsMapped[$diskName] = $diskName;
			}
		}
		unset($name);
		if(!$iblockOperationsMapped)
		{
			$this->log(array(
				"Could not map operations in task ({$taskId})",
				"Skip"
			));
			return null;
		}
		$this->loadDiskTasks();
		foreach($this->diskTasks as $diskTask)
		{
			$diskOperationsByTask = $this->getDiskOperationsByTask($diskTask['ID']);
			if(array_diff($iblockOperationsMapped, $diskOperationsByTask) === array() && array_diff($diskOperationsByTask, $iblockOperationsMapped) === array())
			{
				$this->log(array(
					"Iblock task {$taskId} equals Disk task {$diskTask['ID']}",
				));
				$map[$taskId] = $diskTask['ID'];
				return $map[$taskId];
			}
		}
		unset($diskTask);
		$iblockTask = $this->getIblockTaskById($taskId);
		$taskFields = array(
			'NAME' => substr($iblockTask['TITLE'], 0, 80) . ' (custom)',
			'DESCRIPTION' => $iblockTask['DESC'],
			'BINDING' => 'module',
			'MODULE_ID' => Driver::INTERNAL_MODULE_ID,
		);

		$this->log(array(
			"Attempt to create new task (clone from iblock task)",
			$taskFields,
		));

		$newTaskId = CTask::add($taskFields);
		if(!$newTaskId)
		{
			$this->log(array(
				"Could not create new task ({$taskId})",
				$taskFields,
			));
			return null;
		}
		$this->log(array(
			"Attempt to set operations to task {$newTaskId}",
			$iblockOperationsMapped,
		));

		CTask::setOperations($newTaskId, $iblockOperationsMapped, true);
		$map[$taskId] = $newTaskId;

		return $map[$taskId];
	}

	protected function loadDiskTasks()
	{
		if($this->diskTasks !== null)
		{
			return;
		}

		$this->diskTasks = array();
		/** @noinspection PhpUndefinedClassInspection */
		$query = \CTask::getList(array('ID' => 'asc'), array('MODULE_ID' => 'disk',));
		while($task = $query->fetch())
		{
			$this->diskTasks[$task['ID']] = $task;
		}

		return;
	}

	protected function getDiskTaskById($id)
	{
		$this->loadDiskTasks();
		if(isset($this->diskTasks[$id]))
		{
			return $this->diskTasks[$id];
		}

		return null;
	}

	public function getDiskTaskIdByName($name)
	{
		$this->loadDiskTasks();
		foreach($this->diskTasks as $task)
		{
			if(isset($task['NAME']) && $task['NAME'] == $name)
			{
				return $task['ID'];
			}
		}
		unset($task);

		return null;
	}

	protected function getDiskOperationsByTask($taskId)
	{
		if(!isset($this->diskOperationsByTask[$taskId]))
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$this->diskOperationsByTask[$taskId] = \CTask::getOperations($taskId, true);
		}
		return $this->diskOperationsByTask[$taskId];
	}

	protected function getIblockOperationsByTask($taskId)
	{
		if(!isset($this->iblockOperationsByTask[$taskId]))
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$this->iblockOperationsByTask[$taskId] = \CTask::getOperations($taskId, true);
		}
		return $this->iblockOperationsByTask[$taskId];
	}

	protected function getIblockTaskById($id)
	{
		$this->loadIblockTasks();
		if(isset($this->iblockTasks[$id]))
		{
			return $this->iblockTasks[$id];
		}

		return null;
	}

	protected function loadIblockTasks()
	{
		if($this->iblockTasks !== null)
		{
			return;
		}
		$rs = CTask::GetList(
			array("LETTER"=>"asc"),
			array(
				"MODULE_ID" => "iblock",
				"BINDING" => "iblock",
			)
		);
		$this->iblockTasks = array();
		while($row = $rs->fetch())
		{
			$this->iblockTasks[$row["ID"]] = $row;
		}

		return;
	}

	protected function mapRightOperation($operationFromIblock)
	{
		$map = array(
			'iblock_admin_display' => null,
			'iblock_edit' => array(RightsManager::OP_EDIT, RightsManager::OP_SHARING),
			'iblock_delete' => array(RightsManager::OP_DELETE, RightsManager::OP_DESTROY, RightsManager::OP_RESTORE),
			'iblock_rights_edit' => array(RightsManager::OP_RIGHTS, RightsManager::OP_CREATE_WF, RightsManager::OP_SETTINGS),
			'iblock_export' => null,
			'section_read' => RightsManager::OP_READ,
			'section_edit' => RightsManager::OP_EDIT,
			'section_delete' => RightsManager::OP_DELETE,
			'section_element_bind' => RightsManager::OP_ADD,
			'section_section_bind' => RightsManager::OP_ADD,
			'section_rights_edit' => array(RightsManager::OP_RIGHTS, RightsManager::OP_CREATE_WF, RightsManager::OP_SETTINGS),
			'element_read' => RightsManager::OP_READ,
			'element_edit' => RightsManager::OP_EDIT,
			'element_edit_price' => null,
			'element_delete' => RightsManager::OP_DELETE,
			'element_edit_any_wf_status' => RightsManager::OP_EDIT,
			'element_bizproc_start' => RightsManager::OP_START_BP,
			'element_rights_edit' => RightsManager::OP_RIGHTS,
		);
		return isset($map[$operationFromIblock])? $map[$operationFromIblock] : null;
	}

	protected function markPendingObjectAsDeleted()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}
		if($this->isMysql)
		{
			$sql = "
				UPDATE b_disk_object target_object
					INNER JOIN (
						SELECT o.ID
						FROM b_disk_object o
							LEFT JOIN b_disk_object parent ON parent.ID = o.PARENT_ID
						WHERE parent.ID IS NULL AND o.PARENT_ID IS NOT NULL
					) pseudo ON pseudo.ID = target_object.ID
				SET target_object.DELETED_TYPE = 22
			";
		}
		elseif($this->isOracle || $this->isMssql)
		{
			$sql = "
				UPDATE
					b_disk_object
					SET DELETED_TYPE = 22
				where
					EXISTS(
						SELECT 'x'
						FROM b_disk_object child JOIN b_disk_object_path p ON p.OBJECT_ID = child.ID
						WHERE p.PARENT_ID IN (SELECT o.ID FROM b_disk_object o WHERE o.PARENT_ID IS NOT NULL AND NOT exists(SELECT * FROM b_disk_object WHERE o.PARENT_ID =ID))
							AND child.ID = b_disk_object.ID
					)
			";
		}

		$this->connection->queryExecute($sql);

		$this->setStepFinished(__METHOD__);
	}

	protected function repairStorageId()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}
		if($this->isMysql)
		{
			$hasProblem = $this->connection->query("
				SELECT object.STORAGE_ID, rootobj.STORAGE_ID FROM b_disk_object rootobj
					INNER JOIN b_disk_object_path path ON path.PARENT_ID = rootobj.ID
					INNER JOIN b_disk_object object ON object.ID = path.OBJECT_ID
				WHERE
					object.STORAGE_ID <> rootobj.STORAGE_ID
					AND rootobj.PARENT_ID IS NULL
				LIMIT 1
			")->fetch();
			if($hasProblem)
			{
				$this->connection->queryExecute("
					UPDATE b_disk_object rootobj
						INNER JOIN b_disk_object_path path ON path.PARENT_ID = rootobj.ID
						INNER JOIN b_disk_object object ON object.ID = path.OBJECT_ID
					SET object.STORAGE_ID = rootobj.STORAGE_ID
					WHERE
					  object.STORAGE_ID <> rootobj.STORAGE_ID
					  AND rootobj.PARENT_ID IS NULL
				");
			}
		}

		$this->setStepFinished(__METHOD__);
	}

	protected function addMissedGroupStorages()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$groupType = $this->sqlHelper->forSql(ProxyType\Group::className());
		$lastId = $this->getLastIblockId();

		$query = $this->connection->query("
			SELECT g.ID
			FROM b_sonet_group g
				LEFT JOIN b_disk_storage st
					ON st.ENTITY_ID = g.ID AND st.ENTITY_TYPE = '{$groupType}' AND st.MODULE_ID = 'disk'
			WHERE
				st.ENTITY_ID IS NULL AND
				g.ID > {$lastId}
			ORDER BY g.ID ASC
		");

		while($row = $query->fetch())
		{
			\Bitrix\Disk\Driver::getInstance()->addGroupStorage($row['ID']);
			$this->storeIblockId($row['ID']);

			$this->abortIfNeeded();
		}

		$this->storeIblockId(0);
		$this->setStepFinished(__METHOD__);
	}

	protected function deletePairNegativeRights()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		if($this->isMysql)
		{
			$this->connection->queryExecute("
				DELETE plus, minus
				FROM b_disk_right plus
					INNER JOIN b_disk_right minus ON plus.OBJECT_ID = minus.OBJECT_ID AND plus.ACCESS_CODE = minus.ACCESS_CODE AND plus.TASK_ID = minus.TASK_ID
				WHERE minus.NEGATIVE = 1 AND plus.NEGATIVE = 0
			");
		}
		elseif($this->isMssql)
		{
			$this->connection->queryExecute("
				DELETE plus
				FROM b_disk_right plus
					INNER JOIN b_disk_right minus ON plus.OBJECT_ID = minus.OBJECT_ID AND plus.ACCESS_CODE = minus.ACCESS_CODE AND plus.TASK_ID = minus.TASK_ID
				WHERE minus.NEGATIVE = 1 AND plus.NEGATIVE = 0
			");
		}
		elseif($this->isOracle)
		{
			$this->connection->queryExecute('
				DELETE FROM (SELECT *
				FROM b_disk_right plus
				WHERE plus.NEGATIVE = 0
					AND EXISTS(SELECT \'x\' FROM b_disk_right "minus" WHERE "minus".NEGATIVE = 1  AND plus.OBJECT_ID = "minus".OBJECT_ID AND plus.ACCESS_CODE = "minus".ACCESS_CODE AND plus.TASK_ID = "minus".TASK_ID)
				)
			');
		}

		$this->setStepFinished(__METHOD__);
	}

	protected function fillObjectRightsMoveElements()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$this->connection->queryExecute("
			INSERT INTO b_disk_right(OBJECT_ID, TASK_ID, ACCESS_CODE, DOMAIN, NEGATIVE)
			SELECT do.ID, r.TASK_ID, r.GROUP_CODE, '', 0
			FROM b_iblock_right r
				INNER JOIN b_disk_object do ON r.ENTITY_ID = do.WEBDAV_ELEMENT_ID
			WHERE r.ENTITY_TYPE='element' AND do.TYPE=3 AND r.GROUP_CODE<>'SA'
		"
		);

		$this->setStepFinished(__METHOD__);
	}

	protected function fillObjectRightsMoveSections()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$this->connection->queryExecute("
			INSERT INTO b_disk_right(OBJECT_ID, TASK_ID, ACCESS_CODE, DOMAIN, NEGATIVE)
			SELECT do.ID, r.TASK_ID, r.GROUP_CODE, '', 0
			FROM b_iblock_right r
				INNER JOIN b_disk_object do ON r.ENTITY_ID = do.WEBDAV_SECTION_ID
			WHERE r.ENTITY_TYPE='section' AND do.TYPE=2 AND r.GROUP_CODE<>'SA'
		"
		);

		$this->setStepFinished(__METHOD__);
	}

	protected function fillObjectRightsMoveIblocks()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$this->connection->queryExecute("
			INSERT INTO b_disk_right(OBJECT_ID, TASK_ID, ACCESS_CODE, DOMAIN, NEGATIVE)
			SELECT do.ID, r.TASK_ID, r.GROUP_CODE, '', 0
			FROM b_iblock_right r
				INNER JOIN b_disk_object do ON r.ENTITY_ID = do.WEBDAV_IBLOCK_ID
			WHERE r.ENTITY_TYPE='iblock'
				AND do.TYPE=2
				AND r.GROUP_CODE<>'SA'
				AND do.PARENT_ID IS NULL
		"
		);

		$this->setStepFinished(__METHOD__);
	}

	protected function fillObjectRightsSetNegative()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$lastId = $this->getLastIblockId();
		$query = $this->connection->query("SELECT ID FROM b_disk_storage WHERE ID > {$lastId} ORDER BY ID ASC");
		while($storageRow = $query->fetch())
		{
			$storageId = $storageRow['ID'];

			$this->abortIfNeeded();

			$this->connection->queryExecute("
				INSERT INTO b_disk_right(OBJECT_ID, TASK_ID, ACCESS_CODE, DOMAIN, NEGATIVE)
				SELECT DISTINCT rchild.OBJECT_ID, rtop.TASK_ID, rtop.ACCESS_CODE, '', 1
				FROM b_disk_right rtop
					INNER JOIN b_disk_object_path robj ON rtop.OBJECT_ID=robj.PARENT_ID AND robj.PARENT_ID<>robj.OBJECT_ID
					INNER JOIN b_disk_right rchild ON robj.OBJECT_ID=rchild.OBJECT_ID
					INNER JOIN b_disk_object object ON rtop.OBJECT_ID = object.ID
				WHERE rchild.ACCESS_CODE=rtop.ACCESS_CODE AND object.STORAGE_ID = {$storageId}
			"
			);

			$this->storeIblockId($storageId);
		}

		$this->storeIblockId(0);
		$this->setStepFinished(__METHOD__);
	}

	protected function fillObjectSimpleRightsMoveSections()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$this->connection->queryExecute("
			INSERT INTO b_disk_simple_right(OBJECT_ID, ACCESS_CODE)
			SELECT do.ID, ir.GROUP_CODE
			FROM b_disk_object do
				INNER JOIN b_iblock i ON do.WEBDAV_IBLOCK_ID=i.ID
				INNER JOIN b_iblock_section_right sr ON sr.SECTION_ID=do.WEBDAV_SECTION_ID
				INNER JOIN b_iblock_right ir ON sr.RIGHT_ID=ir.ID AND ir.OP_SREAD='Y'
			WHERE do.TYPE=2 AND i.RIGHTS_MODE='E'
		"
		);

		$this->setStepFinished(__METHOD__);
	}

	protected function fillObjectSimpleRightsMoveElements()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$this->connection->queryExecute("
			INSERT INTO b_disk_simple_right(OBJECT_ID, ACCESS_CODE)
			SELECT do.ID, ir.GROUP_CODE
			FROM b_disk_object do
				INNER JOIN b_iblock i ON do.WEBDAV_IBLOCK_ID=i.ID
				INNER JOIN b_iblock_element_right er ON er.ELEMENT_ID=do.WEBDAV_ELEMENT_ID
				INNER JOIN b_iblock_right ir ON er.RIGHT_ID=ir.ID AND ir.OP_EREAD='Y'
			WHERE do.TYPE=3 AND i.RIGHTS_MODE='E'
		"
		);

		$this->setStepFinished(__METHOD__);
	}

	protected function fillObjectSimpleRightsMoveIblocks()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		//set simple rights on root of storage (type common)
		$proxyType = $this->sqlHelper->forSql(ProxyType\Common::className());
		$this->connection->queryExecute("
			INSERT INTO b_disk_simple_right(OBJECT_ID, ACCESS_CODE)
			SELECT do.ID, ir.GROUP_CODE
			FROM b_disk_object do
				INNER JOIN b_iblock i ON do.WEBDAV_IBLOCK_ID=i.ID
				INNER JOIN b_iblock_right ir ON ir.IBLOCK_ID = i.ID AND ir.ENTITY_TYPE = 'iblock' AND ir.ENTITY_ID = i.ID AND ir.OP_EREAD='Y'
				INNER JOIN b_disk_storage st ON st.ROOT_OBJECT_ID = do.ID
			WHERE i.RIGHTS_MODE='E' AND st.ENTITY_TYPE = '{$proxyType}'
		"
		);

		$this->setStepFinished(__METHOD__);
	}

	protected function fillObjectMoveNonExtendedRights()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$rightsManager = Driver::getInstance()->getRightsManager();

		$caseStr = "CASE ig.PERMISSION
			WHEN 'R' THEN ".$rightsManager->getTaskIdByName(RightsManager::TASK_READ)."
			WHEN 'T' THEN ".$rightsManager->getTaskIdByName(RightsManager::TASK_ADD)."
			WHEN 'U' THEN ".$rightsManager->getTaskIdByName(RightsManager::TASK_EDIT)."
			WHEN 'W' THEN ".$rightsManager->getTaskIdByName(RightsManager::TASK_EDIT)."
			WHEN 'X' THEN ".$rightsManager->getTaskIdByName(RightsManager::TASK_FULL)."
			END";

		$this->connection->queryExecute("
			INSERT INTO b_disk_right(OBJECT_ID, ACCESS_CODE, TASK_ID, DOMAIN, NEGATIVE)
			SELECT do.ID, " . $this->getConcatFunction("'G'", 'ig.GROUP_ID') . ", $caseStr, '', 0
			FROM b_disk_object do
				INNER JOIN b_iblock i ON do.WEBDAV_IBLOCK_ID=i.ID
				INNER JOIN b_iblock_group ig ON ig.IBLOCK_ID=i.ID
			WHERE do.WEBDAV_SECTION_ID IS NULL
				AND do.TYPE=2
				AND do.PARENT_ID IS NULL
				AND ig.PERMISSION>='R'
				AND (i.RIGHTS_MODE<>'E' OR i.RIGHTS_MODE IS NULL)
				"
		);

		$this->setStepFinished(__METHOD__);
	}

	protected function fillObjectMoveNonExtendedSimpleRights()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$this->connection->queryExecute("
			INSERT INTO b_disk_simple_right(OBJECT_ID, ACCESS_CODE)
			SELECT p.OBJECT_ID, " . $this->getConcatFunction("'G'", 'ig.GROUP_ID') . "
			FROM b_disk_object do
				INNER JOIN b_iblock i ON do.WEBDAV_IBLOCK_ID=i.ID
				INNER JOIN b_iblock_group ig ON ig.IBLOCK_ID=i.ID
				INNER JOIN b_disk_object_path p ON p.PARENT_ID=do.ID
			WHERE do.WEBDAV_SECTION_ID IS NULL
				AND do.TYPE=2
				AND do.PARENT_ID IS NULL
				AND (i.RIGHTS_MODE<>'E' OR i.RIGHTS_MODE IS NULL)
				AND ig.PERMISSION>='R'
		"
		);

		$this->setStepFinished(__METHOD__);
	}

	protected function fillObjectRightsMoveTasks()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$defaultIblockPermissions = $defaultUserPermissions = array();
		foreach($this->getIblockWithUserFiles() as $iblock)
		{
			$res = $this->connection->query("
				SELECT r.TASK_ID, r.GROUP_CODE
				FROM b_iblock_right r
				WHERE r.ENTITY_TYPE='iblock'
					AND r.GROUP_CODE<>'SA'
					AND r.IBLOCK_ID=".$iblock['ID']
			);

			while($r = $res->fetch())
			{
				$defaultIblockPermissions[$r['TASK_ID']] = $r;
			}

			break;
		}

		$lastId = $this->getLastIblockId();
		$tasks = $this->connection->query("SELECT DISTINCT TASK_ID FROM b_disk_right")->fetchAll();
		\Bitrix\Main\Type\Collection::sortByColumn($tasks, 'TASK_ID');

		$defaultUserStorageRights = Option::get("disk", "default_user_storage_rights", array());
		if(is_string($defaultUserStorageRights))
		{
			$defaultUserStorageRights = unserialize($defaultUserStorageRights);
		}
		foreach($tasks as $row)
		{
			$taskId = $row['TASK_ID'];

			if($lastId > $taskId)
			{
				continue;
			}
			$this->abortIfNeeded();

			$newTaskId = $this->convertIblockTaskToDiskTask($taskId);
			if($newTaskId === self::COULD_NOT_FIND_IBLOCK_TASK)
			{
				$this->storeIblockId($taskId);
				continue;
			}

			if(!$newTaskId)
			{
				throw new Exception("Couldn't convert task $taskId");
			}

			if($newTaskId == self::DENY_TASK)
			{
				$this->connection->queryExecute("DELETE FROM b_disk_right WHERE TASK_ID={$taskId}");
			}
			else
			{
				if($defaultIblockPermissions[$taskId])
				{
					$defaultUserStorageRights[$newTaskId] = $defaultIblockPermissions[$taskId]['GROUP_CODE'];
					Option::set("disk", "default_user_storage_rights", serialize($defaultUserStorageRights));
				}

				$this->connection->queryExecute("UPDATE b_disk_right SET TASK_ID={$newTaskId} WHERE TASK_ID={$taskId}");
			}

			$this->storeIblockId($taskId);
		}
		unset($row);

		$this->storeIblockId(0);
		$this->setStepFinished(__METHOD__);
	}

	protected function fillObjectRights()
	{
		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$this->fillObjectRightsMoveElements();
		$this->abortIfNeeded();

		$this->fillObjectRightsMoveSections();
		$this->abortIfNeeded();

		$this->fillObjectRightsMoveIblocks();
		$this->abortIfNeeded();

		$this->fillObjectRightsSetNegative();
		$this->abortIfNeeded();

		$this->fillObjectRightsMoveTasks();
		$this->abortIfNeeded();

		$this->fillObjectSimpleRightsMoveSections();
		$this->abortIfNeeded();

		$this->fillObjectSimpleRightsMoveElements();
		$this->abortIfNeeded();

		$this->fillObjectSimpleRightsMoveIblocks();
		$this->abortIfNeeded();

		$this->fillObjectMoveNonExtendedRights();
		$this->abortIfNeeded();

		$this->fillObjectMoveNonExtendedSimpleRights();
		$this->abortIfNeeded();

		$this->setStepFinished(__METHOD__);
	}

	protected function storeFillPathParentId($id)
	{
		COption::SetOptionString(
			'disk',
			'~migrateFillParent',
			$id
		);
	}

	protected function getLastPathParentId()
	{
		return COption::getOptionString(
			'disk',
			'~migrateFillParent',
			0
		);
	}

	protected function storeStorageId($id)
	{
		COption::SetOptionString(
			'disk',
			'~migrateFillStorage',
			$id
		);
	}

	protected function getStorageId()
	{
		return COption::getOptionString(
			'disk',
			'~migrateFillStorage',
			0
		);
	}

	protected function storeIblockId($id)
	{
		COption::SetOptionString(
			'disk',
			'~migrateIblockId',
			$id
		);
	}

	protected function getLastIblockId()
	{
		return COption::getOptionString(
			'disk',
			'~migrateIblockId',
			0
		);
	}

	protected function fillSelfObjectPath()
	{
 		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$this->connection->queryExecute("TRUNCATE TABLE b_disk_object_path");

		//fill "self path" on elements
		$this->connection->queryExecute("
			INSERT INTO b_disk_object_path (PARENT_ID, OBJECT_ID, DEPTH_LEVEL)
			SELECT ID, ID, 0 FROM b_disk_object
		");

		$this->setStepFinished(__METHOD__);
	}

	protected function recalcRightsOnUnPublishObject()
	{
 		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		//delete simple right on files with WF_STATUS_ID = 2
		if($this->isMysql || $this->isMssql)
		{
			$this->connection->queryExecute("
				DELETE simple FROM b_disk_simple_right simple
				INNER JOIN b_disk_object obj ON obj.ID = simple.OBJECT_ID AND obj.TYPE=3
				INNER JOIN b_iblock_element el ON obj.WEBDAV_ELEMENT_ID = el.ID
				WHERE el.WF_STATUS_ID = 2 AND NOT EXISTS(SELECT 'x' FROM b_disk_right r WHERE r.OBJECT_ID = obj.ID)
			");
		}
		elseif($this->isOracle)
		{
			$this->connection->queryExecute("
				DELETE FROM (SELECT simple.* FROM b_disk_simple_right simple
				INNER JOIN b_disk_object obj ON obj.ID = simple.OBJECT_ID AND obj.TYPE=3
				INNER JOIN b_iblock_element el ON obj.WEBDAV_ELEMENT_ID = el.ID
				WHERE el.WF_STATUS_ID = 2 AND NOT EXISTS(SELECT 'x' FROM b_disk_right r WHERE r.OBJECT_ID = obj.ID))
			");
		}

		//insert simple rights on these unpublish files
		$this->connection->queryExecute("
			INSERT INTO b_disk_simple_right (OBJECT_ID, ACCESS_CODE)
			SELECT obj.ID, 'CR' FROM b_iblock_element el
				INNER JOIN b_disk_object obj ON obj.WEBDAV_ELEMENT_ID = el.ID AND obj.TYPE=3
			WHERE el.WF_STATUS_ID = 2 AND NOT EXISTS(SELECT 'x' FROM b_disk_right r WHERE r.OBJECT_ID = obj.ID)
		");

		//now we set negative=1 to all rights above these files
		$this->connection->queryExecute("
			INSERT INTO b_disk_right (OBJECT_ID, TASK_ID, ACCESS_CODE, DOMAIN, NEGATIVE)
			SELECT p.OBJECT_ID, r.TASK_ID, r.ACCESS_CODE, r.DOMAIN, 1
				FROM b_disk_right r JOIN b_disk_object_path p ON p.PARENT_ID = r.OBJECT_ID
				WHERE p.OBJECT_ID IN (SELECT simple.OBJECT_ID
					FROM b_disk_simple_right simple
						INNER JOIN b_disk_object obj ON obj.ID = simple.OBJECT_ID
						INNER JOIN b_iblock_element el ON obj.WEBDAV_ELEMENT_ID = el.ID AND el.WF_STATUS_ID = 2
					WHERE simple.ACCESS_CODE = 'CR')
		");

		$taskId = Driver::getInstance()->getRightsManager()->getTaskIdByName(RightsManager::TASK_FULL);
		//now we set full access on these files for CR
		$this->connection->queryExecute("
			INSERT INTO b_disk_right (OBJECT_ID, TASK_ID, ACCESS_CODE, NEGATIVE)
			SELECT simple.OBJECT_ID, {$taskId}, 'CR', 0
				FROM b_disk_simple_right simple
					INNER JOIN b_disk_object obj ON obj.ID = simple.OBJECT_ID
					INNER JOIN b_iblock_element el ON obj.WEBDAV_ELEMENT_ID = el.ID AND el.WF_STATUS_ID = 2
				WHERE simple.ACCESS_CODE = 'CR'
		");

		$this->setStepFinished(__METHOD__);
	}

	protected function migrateVersion()
	{
		if(!$this->runWorkWithBizproc)
		{
			return;
		}

 		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$uploadDir = COption::getOptionString("main", "upload_dir", "upload");
		$isCloud = CModule::IncludeModule("clouds");
		$useGZipCompressionOption = \Bitrix\Main\Config\Option::get("bizproc", "use_gzip_compression", "");

		$isBitrix24 = IsModuleInstalled('bitrix24');
		$bucket = null;
		if($isBitrix24 && $isCloud)
		{
			$bucket = new CCloudStorageBucket(1);
			$bucket->init();
		}

		if($useGZipCompressionOption === "Y")
		{
			$this->useGZipCompression = true;
		}
		elseif($useGZipCompressionOption === "N")
		{
			$this->useGZipCompression = false;
		}
		else
		{
			$this->useGZipCompression = (function_exists("gzcompress") && ($GLOBALS["DB"]->type != "ORACLE" || !defined('BX_UTF')));
		}
		$sqlHelper = $this->connection->getSqlHelper();


		$lastId = $this->getStorageId();
		$versionQuery = $this->connection->query("
			SELECT
				obj.*,
				h.ID VERSION_ID,
				h.NAME VERSION_NAME,
				h.DOCUMENT VERSION_DOC,
				h.USER_ID VERSION_USER_ID,
				h.MODIFIED VERSION_MODIFIED
			FROM b_disk_object obj
				INNER JOIN b_bp_history h ON h.DOCUMENT_ID = obj.WEBDAV_ELEMENT_ID AND h.MODULE_ID = 'webdav'

			WHERE obj.TYPE = 3 AND h.ID > {$lastId} ORDER BY h.ID
		"
		);

		while($version = $versionQuery->fetch())
		{
			$this->abortIfNeeded();

			if(strlen($version['VERSION_DOC']) > 0)
			{
				if($this->useGZipCompression)
				{
					$version['VERSION_DOC'] = gzuncompress($version['VERSION_DOC']);
				}

				$version['VERSION_DOC'] = unserialize($version['VERSION_DOC']);
				if(!is_array($version['VERSION_DOC']))
				{
					$version['VERSION_DOC'] = array();
				}
			}
			else
			{
				$version['VERSION_DOC'] = array();
			}
			if(empty($version['VERSION_DOC']) || empty($version['VERSION_DOC']['PROPERTIES']['WEBDAV_VERSION']['VALUE']) || empty($version['VERSION_DOC']['PROPERTIES']['FILE']['VALUE']))
			{
				$this->storeStorageId($version['VERSION_ID']);
				continue;
			}

			$version['VERSION_NAME'] = $sqlHelper->forSql($version['VERSION_NAME']);
			$version['VERSION_MODIFIED'] = $sqlHelper->getCharToDateFunction($version['VERSION_MODIFIED']->format("Y-m-d H:i:s"));
			$version['UPDATE_TIME'] = $sqlHelper->getCharToDateFunction($version['UPDATE_TIME']->format("Y-m-d H:i:s"));

			$fullPath = $version['VERSION_DOC']['PROPERTIES']['FILE']['VALUE'];
			$handlerId = '';
			$filename = bx_basename($fullPath);
			if(substr($fullPath, 0, 4) == "http")
			{
				if(!$isCloud)
				{
					$this->storeStorageId($version['VERSION_ID']);
					continue;
				}
				if(!$isBitrix24)
				{
					$bucket = CCloudStorage::findBucketByFile($fullPath);
					if(!$bucket)
					{
						$this->storeStorageId($version['VERSION_ID']);
						continue;
					}
				}

				$handlerId = $bucket->ID;
				$subDir = trim(substr(getDirPath($fullPath), strlen($bucket->getFileSRC('/'))), '/');
				$contentType = \Bitrix\Disk\TypeFile::getMimeTypeByFilename($filename);
			}
			else
			{
				$subDir = trim(substr(getDirPath($fullPath), strlen('/'. $uploadDir)), '/');
				$contentType = $this->getContentType($fullPath, $filename);
			}

			$webdavSize = $version['VERSION_DOC']['PROPERTIES']['WEBDAV_SIZE']['VALUE'];
			if(empty($webdavSize))
			{
				$webdavSize = 0;
			}
			$fileId = CFile::doInsert(array(
				'HEIGHT' => 0,
				'WIDTH' => 0,
				'FILE_SIZE' => $webdavSize,
				'CONTENT_TYPE' => $contentType,
				'SUBDIR' => $subDir,
				'FILE_NAME' => $filename,
				'MODULE_ID' => Driver::INTERNAL_MODULE_ID,
				'ORIGINAL_NAME' => $filename,
				'DESCRIPTION' => '',
				'HANDLER_ID' => $handlerId,
				'EXTERNAL_ID' => md5(mt_rand()),
			));

			if(!$fileId)
			{
				$this->storeStorageId($version['VERSION_ID']);
				continue;
			}


			$this->connection->queryExecute("
				INSERT INTO b_disk_version (OBJECT_ID, FILE_ID, " . $this->sqlHelper->quote('SIZE') . ", NAME, CREATE_TIME, CREATED_BY, MISC_DATA, OBJECT_CREATE_TIME, OBJECT_CREATED_BY, OBJECT_UPDATE_TIME, OBJECT_UPDATED_BY, GLOBAL_CONTENT_VERSION, BP_VERSION_ID)
				VALUES ({$version['ID']}, {$fileId}, {$webdavSize}, '{$version['VERSION_NAME']}', {$version['VERSION_MODIFIED']},  {$version['VERSION_USER_ID']}, null, {$version['VERSION_MODIFIED']}, {$version['CREATED_BY']}, {$version['UPDATE_TIME']}, {$version['UPDATED_BY']}, {$version['VERSION_DOC']['PROPERTIES']['WEBDAV_VERSION']['VALUE']}, {$version['VERSION_ID']})
			");


			$this->storeStorageId($version['VERSION_ID']);
		}

		$this->abortIfNeeded();

		$this->storeStorageId(0);

		$this->setStepFinished(__METHOD__);
	}

	private function getContentType($fullPath, $filename)
	{
		$contentType = 'application/octet-stream';

		$file = new IO\File($fullPath);
		if($file->isExists())
		{
			$contentType = CFile::getContentType($file->getPhysicalPath(), true);
		}
		else
		{
			try
			{
				$contentType = CFile::getContentType($_SERVER["DOCUMENT_ROOT"] . $fullPath);
			}
			catch(Exception $e)
			{}
		}

		return \Bitrix\Disk\TypeFile::normalizeMimeType($contentType, $filename);
	}

	protected function migrateHeadVersion()
	{
 		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$this->connection->queryExecute("
			INSERT INTO b_disk_version (OBJECT_ID, FILE_ID, " . $this->sqlHelper->quote('SIZE') . ", NAME, CREATE_TIME, CREATED_BY, MISC_DATA, OBJECT_CREATE_TIME, OBJECT_CREATED_BY, OBJECT_UPDATE_TIME, OBJECT_UPDATED_BY, GLOBAL_CONTENT_VERSION)
			SELECT obj.ID, obj.FILE_ID, " . $this->sqlHelper->quote('obj.SIZE') . ", obj.NAME, obj.UPDATE_TIME, obj.UPDATED_BY, null, obj.CREATE_TIME, obj.CREATED_BY, obj.UPDATE_TIME, obj.UPDATED_BY, obj.GLOBAL_CONTENT_VERSION FROM b_disk_object obj WHERE obj.TYPE=3
		");


		$this->setStepFinished(__METHOD__);
	}

	protected function deleteTrashFolders()
	{
 		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		if($this->isMysql || $this->isMssql)
		{
			$sql = "
				DELETE p FROM b_disk_object_path p
					INNER JOIN b_disk_object trash ON trash.ID=p.OBJECT_ID
				WHERE trash.NAME = '.Trash'
			";
		}
		elseif($this->isOracle)
		{
			$sql = "
				DELETE FROM (SELECT p.* FROM b_disk_object_path p
					INNER JOIN b_disk_object trash ON trash.ID=p.OBJECT_ID
				WHERE trash.NAME = '.Trash')
			";
		}
		$this->connection->queryExecute($sql);

		$this->connection->queryExecute("
			DELETE FROM b_disk_object WHERE NAME = '.Trash'
		");

		$this->setStepFinished(__METHOD__);
	}

	protected function migrateTrashFiles()
	{
 		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$lastId = $this->getStorageId();
		//first migrate folder from trash
		$trashQuery = $this->connection->query("
			SELECT
				obj.*,
				s.ROOT_OBJECT_ID ROOT_OBJECT_ID,
				PROP_INFO_EL.VALUE WEBDAV_INFO
			FROM b_disk_object obj
				INNER JOIN b_disk_storage s ON s.ID = obj.STORAGE_ID
				INNER JOIN b_disk_object trash ON trash.ID = obj.PARENT_ID AND trash.PARENT_ID = s.ROOT_OBJECT_ID
				INNER JOIN b_iblock_property PROP_WEBDAV_INFO ON PROP_WEBDAV_INFO.IBLOCK_ID = obj.WEBDAV_IBLOCK_ID AND PROP_WEBDAV_INFO.CODE = 'WEBDAV_INFO'
				INNER JOIN b_iblock_element_property PROP_INFO_EL ON PROP_INFO_EL.IBLOCK_PROPERTY_ID = PROP_WEBDAV_INFO.ID AND PROP_INFO_EL.IBLOCK_ELEMENT_ID = obj.WEBDAV_ELEMENT_ID
			WHERE obj.TYPE=3
				AND trash.NAME = '.Trash'
				AND obj.ID > {$lastId}
			ORDER BY obj.ID
		"
		);
		while($trashChild = $trashQuery->fetch())
		{
			$this->abortIfNeeded();

			$undeletePath = $this->getElementTrashPath($trashChild);
			if(empty($undeletePath))
			{
				$this->log(array(
					"Skip item {$trashChild['ID']} from .Trash",
					"Empty WEBDAV_INFO",
				));
				$this->storeStorageId($trashChild['ID']);
				continue;
			}

			$undeletePath = trim($undeletePath, '/');

			$filter = array(
				'TYPE' => \Bitrix\Disk\Internals\ObjectTable::TYPE_FOLDER,
				'STORAGE_ID' => $trashChild['STORAGE_ID'],
				'PARENT_ID' => $trashChild['ROOT_OBJECT_ID'],
			);
			$mustCreatePathPieces = array();
			$undeletePathPieces = explode('/', $undeletePath);
			$parentId = $filter['PARENT_ID'];
			$parentDeletedType = null;
			$filename = array_pop($undeletePathPieces);
			foreach($undeletePathPieces as $i => $pieceOfPath)
			{
				$filter['NAME'] = $this->sqlHelper->forSql($pieceOfPath);
				$folder = $this->connection->query("
					SELECT ID, NAME, REAL_OBJECT_ID, STORAGE_ID, PARENT_ID, DELETED_TYPE FROM b_disk_object obj
					WHERE obj.PARENT_ID = {$filter['PARENT_ID']} AND obj.STORAGE_ID = {$filter['STORAGE_ID']}  AND obj.TYPE = {$filter['TYPE']} AND obj.NAME = '{$filter['NAME']}'
				")->fetch();
				if($folder)
				{
					$filter['PARENT_ID'] = $parentId = $folder['ID'];
					if(!empty($folder['DELETED_TYPE']))
					{
						$parentDeletedType = $folder['DELETED_TYPE'];
					}
					continue;
				}

				if(!$folder)
				{
					$this->log(array(
						"Folder with name {$pieceOfPath} does not exist",
					));
					$mustCreatePathPieces = array_slice($undeletePathPieces, $i);
					break;
				}
			}
			unset($pieceOfPath);

			if($parentId)
			{
				$success = true;
				/** @var Folder $parentFolder */
				$parentFolder = Folder::loadById($parentId);
				$folderData = array(
					'CREATE_TIME' => $trashChild['CREATE_TIME'],
					'UPDATE_TIME' => $trashChild['UPDATE_TIME'],
					'CREATED_BY' => $trashChild['CREATED_BY'],
					'UPDATED_BY' => $trashChild['UPDATED_BY'],
				);
				foreach($mustCreatePathPieces as $i => $pieceOfPath)
				{
					$deletedType = FolderTable::DELETED_TYPE_CHILD;
					if($i == 0 && !$parentDeletedType)
					{
						$deletedType = FolderTable::DELETED_TYPE_ROOT;
					}
					$folderData['NAME'] = Text::correctFilename($pieceOfPath);
					if($deletedType == FolderTable::DELETED_TYPE_ROOT)
					{
						$folderData['NAME'] = self::appendTrashCanSuffix($folderData['NAME']);
					}
					$folder = $parentFolder->addSubFolder($folderData, array(), true);

					if(!$folder)
					{
						$this->log(array(
							"Skip item {$trashChild['ID']} from .Trash",
							"Could not create subfolder {$folderData['NAME']}",
						));
						$this->storeStorageId($trashChild['ID']);
						$success = false;
						break;
					}
					$updateResult = FolderTable::update($folder->getId(), array(
						'DELETED_TYPE' => $deletedType,
						'DELETE_TIME' => $folder->getUpdateTime(),
						'DELETED_BY' => $folder->getUpdatedBy(),
					));
					if(!$updateResult->isSuccess())
					{
						$this->log(array(
							"Skip item {$trashChild['ID']} from .Trash",
							"Could not markDeleted subfolder {$folder->getId()}",
						));
						$this->storeStorageId($trashChild['ID']);
						$success = false;
						break;
					}
					$parentFolder = $folder;
				}
				unset($pieceOfPath, $folder, $undeletePath);

				if($success)
				{
					//move trashChild into new folder
					if($this->isMysql || $this->isMssql)
					{
						$this->connection->queryExecute("
							DELETE a FROM b_disk_object_path a
								JOIN b_disk_object_path d
									ON a.OBJECT_ID = d.OBJECT_ID
								LEFT JOIN b_disk_object_path x
									ON x.PARENT_ID = d.PARENT_ID AND x.OBJECT_ID = a.PARENT_ID
								WHERE d.PARENT_ID = {$trashChild['ID']} AND x.PARENT_ID IS NULL;
						");
					}
					elseif($this->isOracle)
					{
						$this->connection->queryExecute("
							DELETE FROM b_disk_object_path WHERE ID IN (SELECT a.ID FROM b_disk_object_path a
								JOIN b_disk_object_path d
									ON a.OBJECT_ID = d.OBJECT_ID
								LEFT JOIN b_disk_object_path x
									ON x.PARENT_ID = d.PARENT_ID AND x.OBJECT_ID = a.PARENT_ID
								WHERE d.PARENT_ID = {$trashChild['ID']} AND x.PARENT_ID IS NULL)
						");
					}

					$this->connection->queryExecute("
						INSERT INTO b_disk_object_path (PARENT_ID, OBJECT_ID, DEPTH_LEVEL)
							SELECT stree.PARENT_ID, subtree.OBJECT_ID, stree.DEPTH_LEVEL+subtree.DEPTH_LEVEL+1
							FROM b_disk_object_path stree INNER JOIN b_disk_object_path subtree
								ON subtree.PARENT_ID = {$trashChild['ID']} AND stree.OBJECT_ID = {$parentFolder->getId()}
					");

					$deletedType = FolderTable::DELETED_TYPE_CHILD;
					if(!$mustCreatePathPieces)
					{
						$deletedType = FolderTable::DELETED_TYPE_ROOT;
					}

					$newName = Text::correctFilename($filename);
					if($deletedType == FolderTable::DELETED_TYPE_ROOT)
					{
						$newName = self::appendTrashCanSuffix($newName);
					}
					$newName = $this->sqlHelper->forSql($newName);

					$this->connection->queryExecute("
						UPDATE b_disk_object SET NAME='{$newName}', PARENT_ID = {$parentFolder->getId()}, DELETED_TYPE = {$deletedType}, DELETED_BY=UPDATED_BY, DELETE_TIME=UPDATE_TIME WHERE ID={$trashChild['ID']} AND STORAGE_ID={$trashChild['STORAGE_ID']}
					");

					$this->connection->queryExecute("
						INSERT INTO b_disk_deleted_log (STORAGE_ID, OBJECT_ID, TYPE, USER_ID, CREATE_TIME) VALUES
						({$trashChild['STORAGE_ID']}, {$trashChild['ID']}, {$trashChild['TYPE']}, {$trashChild['UPDATED_BY']}, " . $this->sqlHelper->getCurrentDateTimeFunction() . ")
					");

					$this->storeStorageId($trashChild['ID']);
				}
			}


			$this->storeStorageId($trashChild['ID']);
		}

		$this->abortIfNeeded();

		$this->storeStorageId(0);

		$this->setStepFinished(__METHOD__);
	}

	public static function appendTrashCanSuffix($string, $suffix = null)
	{
//		if($suffix === null)
//		{
//			// 14 length
//			$suffix = str_pad(time(), 11, '0', STR_PAD_LEFT) . chr(rand(97, 122)) . chr(rand(97, 122)) . chr(rand(97, 122));
//		}
//		else
//		{
			$suffix = str_pad(strtr(microtime(true), array('.' => '')), 14, chr(rand(97, 122)), STR_PAD_LEFT);
//		}
		return $string . 'i' . $suffix . 'i';
	}

	protected function migrateTrashFolders()
	{
 		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$lastId = $this->getStorageId();
		//first migrate folder from trash
		$trashQuery = $this->connection->query("
			SELECT
				obj.*,
				s.ROOT_OBJECT_ID ROOT_OBJECT_ID,
				secta.DESCRIPTION DESCRIPTION
			FROM b_disk_object obj
				INNER JOIN b_disk_storage s ON s.ID = obj.STORAGE_ID
				INNER JOIN b_disk_object trash ON trash.ID = obj.PARENT_ID AND trash.PARENT_ID = s.ROOT_OBJECT_ID
				INNER JOIN b_iblock_section secta ON secta.ID = obj.ID

			WHERE obj.TYPE=2 AND trash.NAME = '.Trash' AND obj.ID > {$lastId} ORDER BY obj.ID
		"
		);
		while($trashChild = $trashQuery->fetch())
		{
			$this->abortIfNeeded();

			$undeletePath = $this->getSectionInTrash($trashChild);
			if(empty($undeletePath))
			{
				$this->log(array(
					"Skip item {$trashChild['ID']} from .Trash",
					"Empty WEBDAV_INFO (DESCRIPTION)",
				));
				$this->storeStorageId($trashChild['ID']);
				continue;
			}

			$undeletePath = trim($undeletePath, '/');

			$filter = array(
				'TYPE' => \Bitrix\Disk\Internals\ObjectTable::TYPE_FOLDER,
				'STORAGE_ID' => $trashChild['STORAGE_ID'],
				'PARENT_ID' => $trashChild['ROOT_OBJECT_ID'],
			);
			$mustCreatePathPieces = array();
			$undeletePathPieces = explode('/', $undeletePath);
			$parentId = $filter['PARENT_ID'];
			$parentDeletedType = null;
			$filename = array_pop($undeletePathPieces);
			foreach($undeletePathPieces as $i => $pieceOfPath)
			{
				$filter['NAME'] = $this->sqlHelper->forSql($pieceOfPath);
				$folder = $this->connection->query("
					SELECT ID, NAME, REAL_OBJECT_ID, STORAGE_ID, PARENT_ID, DELETED_TYPE FROM b_disk_object obj
					WHERE obj.PARENT_ID = {$filter['PARENT_ID']} AND obj.STORAGE_ID = {$filter['STORAGE_ID']}  AND obj.TYPE = {$filter['TYPE']} AND obj.NAME = '{$filter['NAME']}'
				")->fetch();
				if($folder)
				{
					$filter['PARENT_ID'] = $parentId = $folder['ID'];
					if(!empty($folder['DELETED_TYPE']))
					{
						$parentDeletedType = $folder['DELETED_TYPE'];
					}
					continue;
				}

				if(!$folder)
				{
					$this->log(array(
						"Folder with name {$pieceOfPath} does not exist",
					));
					$mustCreatePathPieces = array_slice($undeletePathPieces, $i);
					break;
				}
			}
			unset($pieceOfPath);

			if($parentId)
			{
				$success = true;
				/** @var Folder $parentFolder */
				$parentFolder = Folder::loadById($parentId);
				$folderData = array(
					'CREATE_TIME' => $trashChild['CREATE_TIME'],
					'UPDATE_TIME' => $trashChild['UPDATE_TIME'],
					'CREATED_BY' => $trashChild['CREATED_BY'],
					'UPDATED_BY' => $trashChild['UPDATED_BY'],
				);
				foreach($mustCreatePathPieces as $i => $pieceOfPath)
				{
					$deletedType = FolderTable::DELETED_TYPE_CHILD;
					if($i == 0 && !$parentDeletedType)
					{
						$deletedType = FolderTable::DELETED_TYPE_ROOT;
					}
					$folderData['NAME'] = Text::correctFilename($pieceOfPath);
					if($deletedType == FolderTable::DELETED_TYPE_ROOT)
					{
						$folderData['NAME'] = self::appendTrashCanSuffix($folderData['NAME']);
					}
					$folder = $parentFolder->addSubFolder($folderData, array(), true);

					if(!$folder)
					{
						$this->log(array(
							"Skip item {$trashChild['ID']} from .Trash",
							"Could not create subfolder {$folderData['NAME']}",
						));
						$this->storeStorageId($trashChild['ID']);
						$success = false;
						break;
					}
					$updateResult = FolderTable::update($folder->getId(), array(
						'DELETED_TYPE' => $deletedType,
						'DELETE_TIME' => $folder->getUpdateTime(),
						'DELETED_BY' => $folder->getUpdatedBy(),
					));
					if(!$updateResult->isSuccess())
					{
						$this->log(array(
							"Skip item {$trashChild['ID']} from .Trash",
							"Could not markDeleted subfolder {$folder->getId()}",
						));
						$this->storeStorageId($trashChild['ID']);
						$success = false;
						break;
					}
					$parentFolder = $folder;
				}
				unset($pieceOfPath, $folder, $undeletePath);

				if($success)
				{
					//move trashChild into new folder
					if($this->isMysql || $this->isMssql)
					{
						$this->connection->queryExecute("
							DELETE a FROM b_disk_object_path a
								JOIN b_disk_object_path d
									ON a.OBJECT_ID = d.OBJECT_ID
								LEFT JOIN b_disk_object_path x
									ON x.PARENT_ID = d.PARENT_ID AND x.OBJECT_ID = a.PARENT_ID
								WHERE d.PARENT_ID = {$trashChild['ID']} AND x.PARENT_ID IS NULL;
						");
					}
					elseif($this->isOracle)
					{
						$this->connection->queryExecute("
							DELETE FROM b_disk_object_path WHERE ID IN (SELECT a.ID FROM b_disk_object_path a
								JOIN b_disk_object_path d
									ON a.OBJECT_ID = d.OBJECT_ID
								LEFT JOIN b_disk_object_path x
									ON x.PARENT_ID = d.PARENT_ID AND x.OBJECT_ID = a.PARENT_ID
								WHERE d.PARENT_ID = {$trashChild['ID']} AND x.PARENT_ID IS NULL)
						");
					}

					$this->connection->queryExecute("
						INSERT INTO b_disk_object_path (PARENT_ID, OBJECT_ID, DEPTH_LEVEL)
							SELECT stree.PARENT_ID, subtree.OBJECT_ID, stree.DEPTH_LEVEL+subtree.DEPTH_LEVEL+1
							FROM b_disk_object_path stree INNER JOIN b_disk_object_path subtree
								ON subtree.PARENT_ID = {$trashChild['ID']} AND stree.OBJECT_ID = {$parentFolder->getId()}
					");

					//update all objects under trashChild (DELETED_TYPE)
					$deletedType = FolderTable::DELETED_TYPE_CHILD;
					if($this->isMysql)
					{
						$sql = "
							UPDATE b_disk_object obj
								INNER JOIN b_disk_object_path p ON obj.ID = p.OBJECT_ID
								SET obj.DELETED_TYPE = {$deletedType}, obj.DELETED_BY=obj.UPDATED_BY, obj.DELETE_TIME=obj.UPDATE_TIME
							WHERE p.PARENT_ID = {$trashChild['ID']}
						";
					}
					elseif($this->isOracle || $this->isMssql)
					{
						$sql = "
							UPDATE b_disk_object
								SET
									DELETED_TYPE = {$deletedType},
									DELETED_BY=(SELECT obj.UPDATED_BY FROM b_disk_object obj INNER JOIN b_disk_object_path p ON obj.ID = p.OBJECT_ID
							WHERE p.PARENT_ID = {$trashChild['ID']} and obj.ID = b_disk_object.ID),

									DELETE_TIME=(SELECT obj.UPDATE_TIME FROM b_disk_object obj INNER JOIN b_disk_object_path p ON obj.ID = p.OBJECT_ID
							WHERE p.PARENT_ID = {$trashChild['ID']} and obj.ID = b_disk_object.ID)

							WHERE EXISTS((SELECT 'x' FROM b_disk_object obj INNER JOIN b_disk_object_path p ON obj.ID = p.OBJECT_ID
							WHERE p.PARENT_ID = {$trashChild['ID']} and obj.ID = b_disk_object.ID))
						";
					}

					$this->connection->queryExecute($sql);

					$deletedType = FolderTable::DELETED_TYPE_ROOT;

					$newName = $this->sqlHelper->forSql(self::appendTrashCanSuffix(Text::correctFilename($filename)));
					$this->connection->queryExecute("
						UPDATE b_disk_object SET NAME='{$newName}', PARENT_ID = {$parentFolder->getId()}, DELETED_TYPE = {$deletedType}, DELETED_BY=UPDATED_BY, DELETE_TIME=UPDATE_TIME
						WHERE ID={$trashChild['ID']} AND STORAGE_ID={$trashChild['STORAGE_ID']}
					");

					$this->connection->queryExecute("
						INSERT INTO b_disk_deleted_log (STORAGE_ID, OBJECT_ID, TYPE, USER_ID, CREATE_TIME)
						SELECT {$trashChild['STORAGE_ID']}, p.OBJECT_ID, obj.TYPE, obj.UPDATED_BY, " . $this->sqlHelper->getCurrentDateTimeFunction() . " FROM b_disk_object obj
							INNER JOIN b_disk_object_path p ON obj.ID = p.OBJECT_ID
						WHERE p.PARENT_ID = {$trashChild['ID']}
					");

					$this->storeStorageId($trashChild['ID']);
				}
			}


			$this->storeStorageId($trashChild['ID']);
		}

		$this->abortIfNeeded();

		$this->storeStorageId(0);

		$this->setStepFinished(__METHOD__);
	}

	protected function getSectionInTrash(array $section)
	{
		if(empty($section["DESCRIPTION"]) || !is_string($section["DESCRIPTION"]))
		{
			return false;
		}
		$props = @unserialize($section["DESCRIPTION"]);
		if(empty($props['PROPS']['BX:']['UNDELETE']) || !is_string($props['PROPS']['BX:']['UNDELETE']))
		{
			return false;
		}
		return $props['PROPS']['BX:']['UNDELETE'];
	}

	protected function getElementTrashPath(array $element)
	{
		if(empty($element["WEBDAV_INFO"]) || !is_string($element["WEBDAV_INFO"]))
		{
			return false;
		}
		$props = @unserialize($element["WEBDAV_INFO"]);
		if(empty($props['PROPS']['BX:']['UNDELETE']) || !is_string($props['PROPS']['BX:']['UNDELETE']))
		{
			return false;
		}
		return $props['PROPS']['BX:']['UNDELETE'];
	}

	protected function migrateUfHead()
	{
 		if($this->isStepFinished(__METHOD__))
		{
			return;
		}
		$sqlHelper = $this->connection->getSqlHelper();
		$proxyType = $sqlHelper->forSql(ProxyType\Common::className());


		$lastId = $this->getStorageId();
		$storageQuery = $this->connection->query("
			SELECT
				secta.IBLOCK_ID IBLOCK_ID,
				s.ID STORAGE_ID
			FROM b_disk_storage s
				INNER JOIN b_disk_object root ON root.ID = s.ROOT_OBJECT_ID
				INNER JOIN b_iblock_section secta ON root.WEBDAV_SECTION_ID = secta.ID
			WHERE s.ENTITY_TYPE <> '{$proxyType}' AND s.MODULE_ID = 'disk' AND s.ID > {$lastId} ORDER BY s.ID
		");
		while($storage = $storageQuery->fetch())
		{
			$this->abortIfNeeded();

			$this->migrateCustomUf("IBLOCK_{$storage['IBLOCK_ID']}_SECTION", "DISK_FOLDER_{$storage['STORAGE_ID']}");
			$this->migrateCustomUf("IBLOCK_{$storage['IBLOCK_ID']}_FILE", "DISK_FILE_{$storage['STORAGE_ID']}");
			// migrate property value to new uf
			$this->migrateCustomElementProperties($storage['IBLOCK_ID'], "DISK_FILE_{$storage['STORAGE_ID']}");

			$this->storeStorageId($storage['STORAGE_ID']);
		}

		$this->abortIfNeeded();

		//common
		$storageQuery = $this->connection->query("
			SELECT
				s.XML_ID IBLOCK_ID,
				s.ID STORAGE_ID
			FROM b_disk_storage s
			WHERE s.ENTITY_TYPE = '{$proxyType}' AND s.MODULE_ID = 'disk'
		");
		while($storage = $storageQuery->fetch())
		{
			$this->migrateCustomUf("IBLOCK_{$storage['IBLOCK_ID']}_SECTION", "DISK_FOLDER_{$storage['STORAGE_ID']}");
			$this->migrateCustomUf("IBLOCK_{$storage['IBLOCK_ID']}_FILE", "DISK_FILE_{$storage['STORAGE_ID']}");
			// migrate property value to new uf
			$this->migrateCustomElementProperties($storage['IBLOCK_ID'], "DISK_FILE_{$storage['STORAGE_ID']}");
		}

		$this->storeStorageId(0);

		$this->setStepFinished(__METHOD__);
	}

	protected function migrateCustomUf($oldName, $newName)
	{
		global $DB;
		$arUserFieldQuery = CUserTypeEntity::GetList(array(), array("ENTITY_ID"=> $oldName));
		if(!$arUserFieldQuery)
		{
			return;
		}
		$blackList = array(
			'UF_LINK_SECTION_ID' => true,
			'UF_LINK_IBLOCK_ID' => true,
			'UF_LINK_CAN_FORWARD' => true,
			'UF_LINK_RSECTION_ID' => true,
			'UF_USE_DOC_PREVIEW' => true,
			'UF_USE_EXT_SERVICES' => true,
			'UF_USE_BP' => true,
		);

		$oldTableNameUf = "b_utm_".strtolower($oldName);
		$oldTableNameSingleUf = "b_uts_".strtolower($oldName);
		$newTableNameUf = "b_utm_".strtolower($newName);
		$newTableNameSingleUf = "b_uts_".strtolower($newName);
		$isFolder = strpos($newName, 'FILE') === false;
		$externalColumn = $isFolder? 'WEBDAV_SECTION_ID' : 'WEBDAV_ELEMENT_ID';

		$columns = array();
		while($arUserField = $arUserFieldQuery->fetch())
		{
			if(isset($blackList[$arUserField['FIELD_NAME']]))
			{
				continue;
			}

			$enumValueInfos = array();
			$rs = $DB->Query("SELECT * FROM b_user_field_lang WHERE USER_FIELD_ID = ".intval($arUserField['ID']), false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
			while($ar = $rs->Fetch())
			{
				foreach(array("EDIT_FORM_LABEL", "LIST_COLUMN_LABEL", "LIST_FILTER_LABEL", "ERROR_MESSAGE", "HELP_MESSAGE") as $label)
				{
					$arUserField[$label][$ar["LANGUAGE_ID"]] = $ar[$label];
				}
			}
			if($arUserField['USER_TYPE_ID'] == 'enumeration')
			{
				$dbRes = CUserFieldEnum::GetList(array(), array('USER_FIELD_ID' => $arUserField['ID']));
				$key = 0;
				while($valueData = $dbRes->Fetch())
				{
					$enumValueInfos['n'.$key] = array(
						'VALUE' => $valueData['VALUE'],
						'XML_ID' => $valueData['XML_ID'],
						'DEF' => $valueData['DEF'],
						'SORT' => $valueData['SORT'],
					);
					$key++;
				}
			}

			$arUserField['ENTITY_ID'] = $newName;
			$CAllUserTypeEntity = new CUserTypeEntity();
			unset($arUserField['ID']);
			$id = $CAllUserTypeEntity->add($arUserField);

			if($id)
			{
				$id = (int)$id;
				$oldId = (int)$arUserField['ID'];
				$columns[] = $arUserField['FIELD_NAME'];

				if($arUserField['MULTIPLE'] == 'Y')
				{
					if($isFolder)
					{//id section == id folders
						$this->connection->queryExecute("
						INSERT INTO {$newTableNameUf} (VALUE_ID, FIELD_ID, VALUE, VALUE_INT, VALUE_DOUBLE, VALUE_DATE)
							SELECT obj.ID, {$id}, VALUE, VALUE_INT, VALUE_DOUBLE, VALUE_DATE FROM {$oldTableNameUf}
							WHERE FIELD_ID = {$oldId}
						");
					}
					else
					{
						$this->connection->queryExecute("
						INSERT INTO {$newTableNameUf} (VALUE_ID, FIELD_ID, VALUE, VALUE_INT, VALUE_DOUBLE, VALUE_DATE)
							SELECT obj.ID, {$id}, VALUE, VALUE_INT, VALUE_DOUBLE, VALUE_DATE FROM {$oldTableNameUf}
								INNER JOIN b_disk_object obj ON obj.{$externalColumn} = {$oldTableNameUf}.VALUE_ID
							WHERE FIELD_ID = {$oldId}
						");
					}
				}
			}


			if($id && $enumValueInfos)
			{
				$obEnum = new CUserFieldEnum();
				$res = $obEnum->SetEnumValues($id, $enumValueInfos);
			}
			if(!$id)
			{
				$this->log(array(
					'Could not add userType',
					$arUserField
				));
			}
		}

		if($columns)
		{
			if($isFolder)
			{//id section == id folders
				$this->connection->queryExecute("
				INSERT INTO {$newTableNameSingleUf} (VALUE_ID, " . implode(',', $columns) . ")
					SELECT VALUE_ID, " . implode(',', $columns) . " FROM {$oldTableNameSingleUf}
				");
			}
			else
			{
				$this->connection->queryExecute("
				INSERT INTO {$newTableNameSingleUf} (VALUE_ID, " . implode(',', $columns) . ")
					SELECT obj.ID, " . implode(',', $columns) . " FROM {$oldTableNameSingleUf}
						INNER JOIN b_disk_object obj ON obj.{$externalColumn} = {$oldTableNameSingleUf}.VALUE_ID
				");
			}
		}
	}

	protected function migrateCustomElementProperties($iblockId, $entityNewName)
	{
		$iblockId = (int)$iblockId;
		$VERSION = \CIBlockElement::GetIBVersion($iblockId);
		if($VERSION==2)
		{
			$strTable = "b_iblock_element_prop_m".$iblockId;
		}
		else
		{
			$strTable = "b_iblock_element_property";
		}
		$tableNameUf = "b_utm_".strtolower($entityNewName);
		$tableNameSingleUf = "b_uts_".strtolower($entityNewName);

		$sqlHelper = $this->connection->getSqlHelper();
		$listElementAll = array();
		$objectQuery = $this->connection->query("
			SELECT obj.ID, prop.VALUE, prop.IBLOCK_PROPERTY_ID
			FROM b_disk_object obj
			INNER JOIN {$strTable} prop ON obj.WEBDAV_ELEMENT_ID = prop.IBLOCK_ELEMENT_ID
			WHERE obj.WEBDAV_IBLOCK_ID = {$iblockId} AND obj.TYPE = 3
		");
		while($listObject = $objectQuery->fetch())
		{
			$listElementAll[$listObject['ID']][$listObject['IBLOCK_PROPERTY_ID']]['FIELD_VALUE'][] = $listObject['VALUE'];
		}

		$listElement = array();
		foreach($this->getIblockProperties($iblockId) as $prop)
		{
			$propId = $prop['ID'];
			$mappedUfType = $this->mapTypeElementPropertyToUfType($prop);
			if(!$mappedUfType)
			{
				$this->log(array(
					'Unknown property of element',
					$prop,
				));
				continue;
			}

			$userTypeEntity = new \CUserTypeEntity();
			$symbolicName = empty($prop['CODE'])? $propId : strtoupper($prop['CODE']);
			$xmlId = empty($prop['CODE'])? $propId : $prop['CODE'];
			$fieldName = substr('UF_' . $symbolicName, 0, 20);
			if($mappedUfType == 'iblock_section' || $mappedUfType == 'iblock_element')
			{
				$settingsArray = array(
					'IBLOCK_ID' => $prop['LINK_IBLOCK_ID'],
					'DISPLAY' => 'LIST'
				);
			}
			else
			{
				$settingsArray = array();
			}
			$id = $userTypeEntity->add(array(
				'ENTITY_ID' => $entityNewName,
				'FIELD_NAME' => $fieldName,
				'USER_TYPE_ID' => $mappedUfType,
				'XML_ID' => 'PROPERTY_' . $xmlId,
				'MULTIPLE' => $prop['MULTIPLE'],
				'MANDATORY' => $prop['IS_REQUIRED'],
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => null,
				'EDIT_IN_LIST' => null,
				'IS_SEARCHABLE' => $prop['SEARCHABLE'],
				'SETTINGS' => $settingsArray,
				'EDIT_FORM_LABEL' => array(
					'en' => $prop['NAME'],
					'ru' => $prop['NAME'],
					'de' => $prop['NAME'],
				)
			));

			if($id)
			{
				if($mappedUfType == 'enumeration')
				{
					$i = 0;
					$enumValues = array();
					$queryEnum = \CIBlockPropertyEnum::getlist(array("SORT" => "ASC", "VALUE" => "ASC"), array('PROPERTY_ID' => $propId));
					while($queryEnum && $rowEnum = $queryEnum->fetch())
					{
						$enumValues['n' . $i] = array(
							'SORT' => $rowEnum['SORT'],
							'VALUE' => $rowEnum['VALUE'],
							'XML_ID' => $rowEnum['XML_ID'],
							'DEF' => $rowEnum['DEF'],
						);
						$i++;
					}
					$userTypeEnum = new \CUserFieldEnum();
					$userTypeEnum->setEnumValues($id, $enumValues);
				}

				foreach($listElementAll as $newId => $propArray)
				{
					if(array_key_exists($propId, $propArray))
					{
						$listElement[$newId][$propId]['FIELD_VALUE'] = $listElementAll[$newId][$propId]['FIELD_VALUE'];
						$listElement[$newId][$propId]['FIELD_NAME'] = $fieldName;
						if($prop['MULTIPLE'] == 'Y')
						{
							$listElement[$newId][$propId]['FIELD_ID'] = $id;
							$listElement[$newId][$propId]['PROPERTY_TYPE'] = $mappedUfType;
						}
					}
				}
			}
		}

		if(!empty($listElement))
		{
			foreach($listElement as $newId => $propArray)
			{
				$fieldArray = array();
				$valueArray = array();
				foreach($propArray as $prop)
				{
					$fieldArray[] = $prop['FIELD_NAME'];
					if(count($prop['FIELD_VALUE']) > 1)
					{
						$valueArray[] = "'".$sqlHelper->forSql(serialize($prop['FIELD_VALUE']))."'";
						foreach($prop['FIELD_VALUE'] as $utmValue)
						{
							if($prop['PROPERTY_TYPE'] == 'integer')
							{
								$utmValue = (int)$utmValue;
								$this->connection->queryExecute("
									INSERT INTO {$tableNameUf} (VALUE_ID, FIELD_ID, VALUE_INT)
									VALUES ({$newId}, {$prop['FIELD_ID']}, {$utmValue})
								");
							}
							else
							{
								$utmValueStr = "'".$sqlHelper->forSql($utmValue)."'";
								$this->connection->queryExecute("
									INSERT INTO {$tableNameUf} (VALUE_ID, FIELD_ID, VALUE)
									VALUES ({$newId}, {$prop['FIELD_ID']}, {$utmValueStr})
								");
							}
						}
					}
					else
					{
						$valueArray[] = "'".$sqlHelper->forSql($prop['FIELD_VALUE'][0])."'";
					}
				}
				if(!empty($fieldArray))
				{
					if($this->isMysql)
					{
						$sql = "
							INSERT IGNORE INTO {$tableNameSingleUf} (VALUE_ID, " . implode(', ', $fieldArray) . ")
							VALUES ({$newId}, " . implode(', ', $valueArray) . ")
						";
					}
					elseif($this->isOracle)
					{
						$sql = "
							INSERT INTO {$tableNameSingleUf} (VALUE_ID, " . implode(', ', $fieldArray) . ")
							SELECT {$newId}, " . implode(', ', $valueArray) . " FROM dual
							WHERE NOT EXISTS(SELECT 'x' FROM {$tableNameSingleUf} WHERE VALUE_ID = {$newId})
						";
					}
					elseif($this->isMssql)
					{
						$sql = "
							INSERT INTO {$tableNameSingleUf} (VALUE_ID, " . implode(', ', $fieldArray) . ")
							SELECT * FROM (SELECT {$newId}, " . implode(', ', $valueArray) . ") AS tmp
							WHERE NOT EXISTS(SELECT 'x' FROM {$tableNameSingleUf} WHERE VALUE_ID = {$newId})
						";
					}
					$this->connection->queryExecute($sql);
				}
			}
		}
	}

	protected function getIblockProperties($iblockId)
	{
		static $iblockProps = array();

		if(isset($iblockProps[$iblockId]))
		{
			return $iblockProps[$iblockId];
		}
		$iblockProps[$iblockId] = array();
		$queryProps = \CIBlockProperty::GetList(array(
			"SORT" => "ASC",
			"NAME" => "ASC"
		), array(
			"ACTIVE" => "Y",
			"IBLOCK_ID" => $iblockId
		));
		$blackList = array(
			self::UF_DISK_FILE_ID => true,
			self::UF_DISK_FILE_STATUS => true,
			'FORUM_TOPIC_ID' => true,
			'FORUM_MESSAGE_CNT' => true,
			'WEBDAV_VERSION' => true,
			'WEBDAV_SIZE' => true,
			'WEBDAV_INFO' => true,
			'FILE' => true,
		);
		while($prop = $queryProps->fetch())
		{
			if(!empty($prop['CODE']) && isset($blackList[$prop['CODE']]))
			{
				continue;
			}
			$iblockProps[$iblockId][] = $prop;
		}

		return $iblockProps[$iblockId];
	}

	protected function mapTypeElementPropertyToUfType(array $prop)
	{
		$prop['PROPERTY_TYPE'] = strtoupper($prop['PROPERTY_TYPE']);
		switch($prop['PROPERTY_TYPE'])
		{
			case 'N':
				return 'integer';
			case 'S':
				return 'string';
			case 'L':
				return 'enumeration';
			case 'F':
				return 'file';
			case 'G':
				return 'iblock_section';
			case 'E':
				return 'iblock_element';
		}
		return null;
	}

	protected function migrateMetaFolders()
	{
 		if($this->isStepFinished(__METHOD__))
		{
			return;
		}
		$sqlHelper = $this->connection->getSqlHelper();

		$code = $sqlHelper->forSql(Folder::CODE_FOR_SAVED_FILES);
		$proxyType = $sqlHelper->forSql(Bitrix\Disk\ProxyType\User::className());
		$names = array();
		foreach($this->getNamesSavedSection() as $name)
		{
			$names[] = "'" . $sqlHelper->forSql($name) . "'";
		}
		unset($name);

		if($this->isMysql)
		{
			$sql = "
				UPDATE b_disk_object obj
				INNER JOIN b_disk_storage s ON s.ROOT_OBJECT_ID = obj.PARENT_ID AND s.MODULE_ID = 'disk' AND s.ENTITY_TYPE = '{$proxyType}'
				SET obj.CODE = '{$code}' WHERE obj.NAME IN (" . implode(', ', $names) . ")
			";
		}
		elseif($this->isOracle || $this->isMssql)
		{
			$sql = "
				UPDATE b_disk_object
				SET CODE = '{$code}'
				WHERE
					NAME IN (" . implode(', ', $names) . ")
					AND EXISTS (SELECT 'x' FROM b_disk_storage s WHERE s.ROOT_OBJECT_ID = PARENT_ID AND s.MODULE_ID = 'disk' AND s.ENTITY_TYPE = '{$proxyType}')
			";
		}
		$this->connection->queryExecute($sql);


		$code = $sqlHelper->forSql(Folder::CODE_FOR_UPLOADED_FILES);
		$names = array();
		foreach($this->getNamesDroppedSection() as $name)
		{
			$names[] = "'" . $sqlHelper->forSql($name) . "'";
		}
		unset($name);
		$names[] = "'" . $sqlHelper->forSql('.Dropped') . "'";


		Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/disk/lib/storage.php');
		$newNameDropped = $sqlHelper->forSql(Loc::getMessage('DISK_STORAGE_NAME_FOR_FOLDER_WITH_UPLOADED_FILES'));

		//in group we had .Dropped
		if($this->isMysql)
		{
			$sql = "
				UPDATE b_disk_object obj
				INNER JOIN b_disk_storage s ON s.ROOT_OBJECT_ID = obj.PARENT_ID AND s.MODULE_ID = 'disk'
				SET obj.CODE = '{$code}' WHERE obj.NAME IN (" . implode(', ', $names) . ")
			";
		}
		elseif($this->isOracle || $this->isMssql)
		{
			$sql = "
				UPDATE b_disk_object
				SET CODE = '{$code}'
				WHERE
					NAME IN (" . implode(', ', $names) . ")
					AND EXISTS (SELECT 'x' FROM b_disk_storage s WHERE s.ROOT_OBJECT_ID = PARENT_ID AND s.MODULE_ID = 'disk')
			";
		}
		$this->connection->queryExecute($sql);

		if($this->isMysql)
		{
			$sql = "
				UPDATE b_disk_object obj
				INNER JOIN b_disk_storage s ON s.ROOT_OBJECT_ID = obj.PARENT_ID AND s.MODULE_ID = 'disk' AND s.ENTITY_TYPE = '{$proxyType}'
				SET obj.NAME = '{$newNameDropped}_1' WHERE obj.NAME = '{$newNameDropped}'
			";
		}
		elseif($this->isOracle || $this->isMssql)
		{
			$sql = "
				UPDATE b_disk_object
				SET NAME = '{$newNameDropped}_1'
				WHERE
					NAME = '{$newNameDropped}'
					AND EXISTS (SELECT 'x' FROM b_disk_storage s WHERE s.ROOT_OBJECT_ID = PARENT_ID AND s.MODULE_ID = 'disk' AND s.ENTITY_TYPE = '{$proxyType}')
			";
		}
		$this->connection->queryExecute($sql);


		if($this->isMysql)
		{
			$sql = "
				UPDATE b_disk_object obj
				INNER JOIN b_disk_storage s ON s.ROOT_OBJECT_ID = obj.PARENT_ID AND s.MODULE_ID = 'disk' AND s.ENTITY_TYPE = '{$proxyType}'
				SET obj.NAME = '{$newNameDropped}' WHERE obj.NAME = '" . $sqlHelper->forSql('.Dropped') . "' AND obj.CODE = '{$code}'
			";
		}
		elseif($this->isOracle || $this->isMssql)
		{
			$sql = "
				UPDATE b_disk_object
				SET NAME = '{$newNameDropped}'
				WHERE
					NAME = '" . $sqlHelper->forSql('.Dropped') . "' AND CODE = '{$code}'
					AND EXISTS (SELECT 'x' FROM b_disk_storage s WHERE s.ROOT_OBJECT_ID = PARENT_ID AND s.MODULE_ID = 'disk' AND s.ENTITY_TYPE = '{$proxyType}')
			";
		}
		$this->connection->queryExecute($sql);



		$this->setStepFinished(__METHOD__);
	}

	protected function getLanguageList()
	{
		static $l = null;
		if($l !== null)
		{
			return $l;
		}
		$rsLanguage = CLanguage::GetList($by, $order, array());
		while($arLanguage = $rsLanguage->fetch())
		{
			$l[] = $arLanguage;
		}
		return $l;
	}

	protected function getNamesDroppedSection()
	{
		static $array = null;
		if($array !== null)
		{
			return $array;
		}
		$names = array();
		foreach($this->getLanguageList() as $lang)
		{
			\Bitrix\Main\Localization\Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/webdav/classes/general.php', $lang["LID"]);
			$names[$lang["LID"]] = \Bitrix\Main\Localization\Loc::getMessage('WD_DOWNLOADED', null, $lang["LID"]);
		}
		unset($lang);

		return $names;
	}

	protected function getNamesSavedSection()
	{
		static $array = null;
		if($array !== null)
		{
			return $array;
		}
		$names = array();
		foreach($this->getLanguageList() as $lang)
		{
			\Bitrix\Main\Localization\Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/webdav/classes/general.php', $lang["LID"]);
			$names[$lang["LID"]] = \Bitrix\Main\Localization\Loc::getMessage('WD_SAVED', null, $lang["LID"]);
		}
		unset($lang);

		return $names;
	}


	protected function moveUsers()
	{
 		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$lastId = $this->getLastIblockId();
		foreach($this->getIblockWithUserFiles() as $iblock)
		{
			if($lastId > $iblock['ID'])
			{
				continue;
			}
			$this->abortIfNeeded();
			$this->moveUserStorageFromIblock($iblock);
			$this->storeIblockId($iblock['ID']);
		}
		unset($iblock);

		$this->storeIblockId(0);
		$this->setStepFinished(__METHOD__);
	}

	protected function moveGroup()
	{
 		if($this->isStepFinished(__METHOD__))
		{
			return;
		}

		$lastId = $this->getLastIblockId();
		foreach($this->getIblockIdsWithGroupFiles() as $iblock)
		{
			if($lastId > $iblock['ID'])
			{
				continue;
			}
			$this->abortIfNeeded();
			$this->moveGroupStorageFromIblock($iblock);
			$this->storeIblockId($iblock['ID']);
		}
		unset($iblock);

		$this->storeIblockId(0);
		$this->setStepFinished(__METHOD__);
	}

	private function processFinallyActions()
	{
		Option::set(
			'disk',
			'disk_revision_api',
			\Bitrix\Disk\Configuration::REVISION_API
		);
		Option::set(
			'disk',
			'successfully_converted',
			'Y'
		);
		Option::set(
			'disk',
			'process_converted',
			false
		);
		Option::set(
			'webdav',
			'successfully_converted',
			'Y'
		);
		Option::set(
			'webdav',
			'process_converted',
			false
		);

		CAdminNotify::deleteByTag('disk_migrate_from_webdav');

		UnRegisterModuleDependences("main", "OnBeforeProlog", "main", "", "", 99, "/modules/webdav/prolog_before.php"); // before statistics
		UnRegisterModuleDependences("search", "BeforeIndex", "webdav", "CRatingsComponentsWebDav", "BeforeIndex");
		UnRegisterModuleDependences("main", "OnEventLogGetAuditTypes", "webdav", "CEventWebDav", "GetAuditTypes");
		UnRegisterModuleDependences("main", "OnEventLogGetAuditHandlers", "webdav", "CEventWebDav", "MakeWebDavObject");
		UnRegisterModuleDependences("bizproc", "OnAddToHistory", "webdav", "CIBlockDocumentWebdav", "OnAddToHistory");
		UnRegisterModuleDependences("socialnetwork", "OnFillSocNetAllowedSubscribeEntityTypes", "webdav", "CWebDavSocNetEvent", "OnFillSocNetAllowedSubscribeEntityTypes");
		UnRegisterModuleDependences("socialnetwork", "OnFillSocNetLogEvents", "webdav", "CWebDavSocNetEvent", "OnFillSocNetLogEvents");
		UnRegisterModuleDependences("iblock", "OnAfterIBlockElementDelete", "webdav", "CIBlockDocumentWebdav", "OnAfterIBlockElementDelete");
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetFeaturesAdd', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetFeaturesAdd');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetFeaturesUpdate', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetFeaturesUpdate');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetFeatures', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetFeatures');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupAdd', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetUserToGroupAdd');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupUpdate', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetUserToGroupUpdate');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupDelete', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetUserToGroupDelete');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetGroupDelete', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetGroupDelete');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetGroupAdd', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetGroupAdd');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetGroupUpdate', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetGroupUpdate');

		UnRegisterModuleDependences('socialnetwork', 'OnAfterSocNetLogCommentAdd', 'webdav', 'CIBlockWebdavSocnet', 'CopyCommentRights');

		UnRegisterModuleDependences('main', 'OnUserTypeBuildList', 'webdav', 'CUserTypeWebdavElement', 'GetUserTypeDescription');
		UnRegisterModuleDependences('main', 'OnUserTypeBuildList', 'webdav', 'CUserTypeWebdavElementHistory', 'GetUserTypeDescription');
		UnRegisterModuleDependences('blog', 'OnPostAdd', 'webdav', 'CUserTypeWebdavElement', 'OnPostAdd');
		UnRegisterModuleDependences('blog', 'OnPostUpdate', 'webdav', 'CUserTypeWebdavElement', 'OnPostUpdate');
		UnRegisterModuleDependences('blog', 'OnBeforePostDelete', 'webdav', 'CUserTypeWebdavElement', 'OnBeforePostDelete');
		UnRegisterModuleDependences("blog", "OnCommentAdd", 'webdav', 'CUserTypeWebdavElement', "OnCommentAdd");
		UnRegisterModuleDependences("blog", "OnCommentUpdate", 'webdav', 'CUserTypeWebdavElement', "OnCommentUpdate");
		UnRegisterModuleDependences("blog", "OnBeforeCommentDelete", 'webdav', 'CUserTypeWebdavElement', "OnBeforeCommentDelete");

		UnRegisterModuleDependences("im", "OnBeforeConfirmNotify", "webdav", "CWebDavSymlinkHelper", "OnBeforeConfirmNotify");

		CAgent::removeModuleAgents("webdav");

		$this->registerHandlerToBlockIblock();

		disk::RegisterModuleDependencies(true);
	}

	protected function registerHandlerToBlockIblock()
	{
		RegisterModuleDependences('iblock', 'OnBeforeIBlockElementAdd', 'webdav', 'CWebDavIblock', 'OnBeforeIBlockElementAdd');
		RegisterModuleDependences('iblock', 'OnBeforeIBlockElementUpdate', 'webdav', 'CWebDavIblock', 'OnBeforeIBlockElementUpdate');
		RegisterModuleDependences('iblock', 'OnBeforeIBlockElementDelete', 'webdav', 'CWebDavIblock', 'OnBeforeIBlockElementDelete');
		RegisterModuleDependences('iblock', 'OnBeforeIBlockSectionAdd', 'webdav', 'CWebDavIblock', 'OnBeforeIBlockSectionAdd');
		RegisterModuleDependences('iblock', 'OnBeforeIBlockSectionUpdate', 'webdav', 'CWebDavIblock', 'OnBeforeIBlockSectionUpdate');
		RegisterModuleDependences('iblock', 'OnBeforeIBlockSectionDelete', 'webdav', 'CWebDavIblock', 'OnBeforeIBlockSectionDelete');
	}

	private function moveCommonElements($storageId, $rootObjectId, $iblock)
	{
		$iblockId = $iblock['ID'];
		$sqlHelper = $this->connection->getSqlHelper();


		if($iblock['VERSION'] == 2)
		{
			$props = $this->connection->query("SELECT * FROM b_iblock_property WHERE VERSION = 2 AND IBLOCK_ID = {$iblockId} AND CODE IN ('WEBDAV_SIZE', 'FILE', 'WEBDAV_VERSION')")->fetchAll();

			$joinTable = 'b_iblock_element_prop_s' . $iblockId;
			$columnForFileId = $columnForSize = 'null';
			$columnForVersion = '1';

			foreach($props as $prop)
			{
				switch($prop['CODE'])
				{
					case 'WEBDAV_SIZE':
						$columnForSize = 'PROPERTY_' . $prop['ID'];
						break;
					case 'FILE':
						$columnForFileId = 'PROPERTY_' . $prop['ID'];
						break;
					case 'WEBDAV_VERSION':
						$columnForVersion = 'PROPERTY_' . $prop['ID'];
						break;
				}
			}
			unset($prop);


			if($this->isMysql)
			{
				$sql = "
						INSERT IGNORE INTO b_disk_object (FILE_ID, SIZE, GLOBAL_CONTENT_VERSION, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_ELEMENT_ID, WEBDAV_IBLOCK_ID)
						SELECT {$columnForFileId}, {$columnForSize}, {$columnForVersion}, child.NAME, 3, null, {$storageId}, " . $sqlHelper->getIsNullFunction('child.IBLOCK_SECTION_ID', $rootObjectId) . ", child.DATE_CREATE, " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID   FROM b_iblock_element child

						INNER JOIN {$joinTable} props ON props.IBLOCK_ELEMENT_ID = child.ID

						WHERE child.IBLOCK_ID = {$iblockId} AND child.IBLOCK_SECTION_ID IS NULL
					";
			}
			elseif($this->isOracle || $this->isMssql)
			{
				$sql = "
						INSERT INTO b_disk_object (FILE_ID, " . $this->sqlHelper->quote('SIZE') . ", GLOBAL_CONTENT_VERSION, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_ELEMENT_ID, WEBDAV_IBLOCK_ID)
						SELECT {$columnForFileId}, {$columnForSize}, {$columnForVersion}, child.NAME, 3, null, {$storageId}, " . $sqlHelper->getIsNullFunction('child.IBLOCK_SECTION_ID', $rootObjectId) . ", child.DATE_CREATE, " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID   FROM b_iblock_element child

						INNER JOIN {$joinTable} props ON props.IBLOCK_ELEMENT_ID = child.ID

						WHERE child.IBLOCK_ID = {$iblockId} AND child.IBLOCK_SECTION_ID IS NULL
							AND NOT EXISTS(SELECT 'x' FROM b_disk_object WHERE NAME = child.NAME AND PARENT_ID = child.IBLOCK_SECTION_ID AND child.IBLOCK_SECTION_ID IS NOT NULL)
					";
			}

			$this->connection->queryExecute($sql);

			$this->connection->queryExecute("
					INSERT INTO b_disk_object (FILE_ID, " . $this->sqlHelper->quote('SIZE') . ", GLOBAL_CONTENT_VERSION, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_ELEMENT_ID, WEBDAV_IBLOCK_ID)
					SELECT {$columnForFileId}, {$columnForSize}, {$columnForVersion}, " . $this->getConcatFunction('child.ID', 'child.NAME') . ", 3, null, {$storageId}, " . $sqlHelper->getIsNullFunction('child.IBLOCK_SECTION_ID', $rootObjectId) . ", child.DATE_CREATE, " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID   FROM b_iblock_element child

					INNER JOIN {$joinTable} props ON props.IBLOCK_ELEMENT_ID = child.ID

					WHERE
						child.IBLOCK_ID = {$iblockId} AND child.IBLOCK_SECTION_ID IS NULL
						AND NOT EXISTS(SELECT 'x' FROM b_disk_object do WHERE do.WEBDAV_ELEMENT_ID=child.ID)

				");
		}
		else
		{

			if($this->isMysql)
			{
				$sql = "
						INSERT IGNORE INTO b_disk_object (FILE_ID, SIZE, GLOBAL_CONTENT_VERSION, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_ELEMENT_ID, WEBDAV_IBLOCK_ID)
						SELECT PROP_FILE_EL.VALUE, PROP_SIZE_EL.VALUE, " . $sqlHelper->getIsNullFunction('PPROP_VERSION_G_EL.VALUE', 1) . ", child.NAME, 3, null, {$storageId}, " . $sqlHelper->getIsNullFunction('child.IBLOCK_SECTION_ID', $rootObjectId) . ", child.DATE_CREATE, " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID   FROM b_iblock_element child

						INNER JOIN b_iblock_property PROP_SIZE ON PROP_SIZE.IBLOCK_ID = child.IBLOCK_ID AND PROP_SIZE.CODE = 'WEBDAV_SIZE'
						INNER JOIN b_iblock_element_property PROP_SIZE_EL ON PROP_SIZE_EL.IBLOCK_PROPERTY_ID = PROP_SIZE.ID AND PROP_SIZE_EL.IBLOCK_ELEMENT_ID = child.ID

						INNER JOIN b_iblock_property PROP_FILE ON PROP_FILE.IBLOCK_ID = child.IBLOCK_ID AND PROP_FILE.CODE = 'FILE'
						INNER JOIN b_iblock_element_property PROP_FILE_EL ON PROP_FILE_EL.IBLOCK_PROPERTY_ID = PROP_FILE.ID AND PROP_FILE_EL.IBLOCK_ELEMENT_ID = child.ID

						LEFT JOIN b_iblock_property PROP_VERSION_G ON PROP_VERSION_G.IBLOCK_ID = child.IBLOCK_ID AND PROP_VERSION_G.CODE = 'WEBDAV_VERSION'
						LEFT JOIN b_iblock_element_property PPROP_VERSION_G_EL ON PPROP_VERSION_G_EL.IBLOCK_PROPERTY_ID = PROP_VERSION_G.ID AND PPROP_VERSION_G_EL.IBLOCK_ELEMENT_ID = child.ID

						WHERE child.IBLOCK_ID = {$iblockId} AND child.IBLOCK_SECTION_ID IS NULL
					";
			}
			elseif($this->isOracle || $this->isMssql)
			{
				$sql = "
						INSERT INTO b_disk_object (FILE_ID, " . $this->sqlHelper->quote('SIZE') . ", GLOBAL_CONTENT_VERSION, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_ELEMENT_ID, WEBDAV_IBLOCK_ID)
						SELECT PROP_FILE_EL.VALUE, PROP_SIZE_EL.VALUE, " . $sqlHelper->getIsNullFunction('PPROP_VERSION_G_EL.VALUE', 1) . ", child.NAME, 3, null, {$storageId}, " . $sqlHelper->getIsNullFunction('child.IBLOCK_SECTION_ID', $rootObjectId) . ", child.DATE_CREATE, " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID   FROM b_iblock_element child

						INNER JOIN b_iblock_property PROP_SIZE ON PROP_SIZE.IBLOCK_ID = child.IBLOCK_ID AND PROP_SIZE.CODE = 'WEBDAV_SIZE'
						INNER JOIN b_iblock_element_property PROP_SIZE_EL ON PROP_SIZE_EL.IBLOCK_PROPERTY_ID = PROP_SIZE.ID AND PROP_SIZE_EL.IBLOCK_ELEMENT_ID = child.ID

						INNER JOIN b_iblock_property PROP_FILE ON PROP_FILE.IBLOCK_ID = child.IBLOCK_ID AND PROP_FILE.CODE = 'FILE'
						INNER JOIN b_iblock_element_property PROP_FILE_EL ON PROP_FILE_EL.IBLOCK_PROPERTY_ID = PROP_FILE.ID AND PROP_FILE_EL.IBLOCK_ELEMENT_ID = child.ID

						LEFT JOIN b_iblock_property PROP_VERSION_G ON PROP_VERSION_G.IBLOCK_ID = child.IBLOCK_ID AND PROP_VERSION_G.CODE = 'WEBDAV_VERSION'
						LEFT JOIN b_iblock_element_property PPROP_VERSION_G_EL ON PPROP_VERSION_G_EL.IBLOCK_PROPERTY_ID = PROP_VERSION_G.ID AND PPROP_VERSION_G_EL.IBLOCK_ELEMENT_ID = child.ID

						WHERE child.IBLOCK_ID = {$iblockId} AND child.IBLOCK_SECTION_ID IS NULL
							AND NOT EXISTS(SELECT 'x' FROM b_disk_object WHERE NAME = child.NAME AND PARENT_ID = child.IBLOCK_SECTION_ID AND child.IBLOCK_SECTION_ID IS NOT NULL)
					";
			}

			$this->connection->queryExecute($sql);

			$this->connection->queryExecute("
					INSERT INTO b_disk_object (FILE_ID, " . $this->sqlHelper->quote('SIZE') . ", GLOBAL_CONTENT_VERSION, NAME, TYPE, CODE, STORAGE_ID, PARENT_ID, CREATE_TIME, UPDATE_TIME, SYNC_UPDATE_TIME, CREATED_BY, UPDATED_BY, XML_ID, WEBDAV_ELEMENT_ID, WEBDAV_IBLOCK_ID)
					SELECT PROP_FILE_EL.VALUE, PROP_SIZE_EL.VALUE, " . $sqlHelper->getIsNullFunction('PPROP_VERSION_G_EL.VALUE', 1) . ", " . $this->getConcatFunction('child.ID', 'child.NAME') . ", 3, null, {$storageId}, " . $sqlHelper->getIsNullFunction('child.IBLOCK_SECTION_ID', $rootObjectId) . ", child.DATE_CREATE, " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.TIMESTAMP_X', 'child.DATE_CREATE') . ", " . $this->sqlHelper->getIsNullFunction('child.CREATED_BY', 0) . ", child.MODIFIED_BY, child.ID, child.ID, child.IBLOCK_ID   FROM b_iblock_element child

					INNER JOIN b_iblock_property PROP_SIZE ON PROP_SIZE.IBLOCK_ID = child.IBLOCK_ID AND PROP_SIZE.CODE = 'WEBDAV_SIZE'
					INNER JOIN b_iblock_element_property PROP_SIZE_EL ON PROP_SIZE_EL.IBLOCK_PROPERTY_ID = PROP_SIZE.ID AND PROP_SIZE_EL.IBLOCK_ELEMENT_ID = child.ID

					INNER JOIN b_iblock_property PROP_FILE ON PROP_FILE.IBLOCK_ID = child.IBLOCK_ID AND PROP_FILE.CODE = 'FILE'
					INNER JOIN b_iblock_element_property PROP_FILE_EL ON PROP_FILE_EL.IBLOCK_PROPERTY_ID = PROP_FILE.ID AND PROP_FILE_EL.IBLOCK_ELEMENT_ID = child.ID

					LEFT JOIN b_iblock_property PROP_VERSION_G ON PROP_VERSION_G.IBLOCK_ID = child.IBLOCK_ID AND PROP_VERSION_G.CODE = 'WEBDAV_VERSION'
					LEFT JOIN b_iblock_element_property PPROP_VERSION_G_EL ON PPROP_VERSION_G_EL.IBLOCK_PROPERTY_ID = PROP_VERSION_G.ID AND PPROP_VERSION_G_EL.IBLOCK_ELEMENT_ID = child.ID

					WHERE
						child.IBLOCK_ID = {$iblockId} AND child.IBLOCK_SECTION_ID IS NULL
						AND NOT EXISTS(SELECT 'x' FROM b_disk_object do WHERE do.WEBDAV_ELEMENT_ID=child.ID)

				");
		}
	}
}