<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 *
 * @deprecated
 */


IncludeModuleLangFile(__FILE__); // todo: relocate translations from here

use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DeleteResult;
use \Bitrix\Tasks\Integration\Mail;
use Bitrix\Tasks\V2\Infrastructure\Agent\Reminder;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Repository\ReminderRepositoryInterface;

Loc::loadMessages(__FILE__);

class CTaskReminders
{
	const REMINDER_TRANSPORT_JABBER = "J";
	const REMINDER_TRANSPORT_EMAIL = "E";

	const REMINDER_TYPE_DEADLINE = "D";
	const REMINDER_TYPE_COMMON = "A";

	const RECEPIENT_TYPE_SELF = "S";
	const RECEPIENT_TYPE_RESPONSIBLE = "R";
	const RECEPIENT_TYPE_ORIGINATOR = "O";

	protected $userId = false;
	protected $errors = array();

	public function __construct ($arParams = array())
	{
		if (isset($arParams['USER_ID']))
			$this->userId = $arParams['USER_ID'];
	}

	public function getErrors()
	{
		return $this->errors;
	}

	function CheckFields(&$arFields,
		/** @noinspection PhpUnusedParameterInspection */ $ID = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$arMsg = Array();

		if (!is_set($arFields, "USER_ID"))
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_USER_ID"), "id" => "ERROR_TASKS_BAD_USER_ID");
		}
		else
		{

			$r = CUser::GetByID($arFields["USER_ID"]);
			if (!$r->Fetch())
			{
				$arMsg[] = array("text" => GetMessage("TASKS_BAD_USER_ID_EX"), "id" => "ERROR_TASKS_BAD_USER_ID_EX");
			}
		}

		if (!is_set($arFields, "TASK_ID"))
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_TASK_ID"), "id" => "ERROR_TASKS_BAD_TASK_ID");
		}
		else
		{
			if ($this->userId !== false)
			{
				/** @noinspection PhpDeprecationInspection */
				$r = CTasks::GetByID($arFields["TASK_ID"], true, array('USER_ID' => (int) $this->userId));
			}
			else
			{
				/** @noinspection PhpDeprecationInspection */
				$r = CTasks::GetByID($arFields["TASK_ID"]);
			}

			if (!$r->Fetch())
			{
				$arMsg[] = array("text" => GetMessage("TASKS_BAD_TASK_ID_EX"), "id" => "ERROR_TASKS_BAD_TASK_ID_EX");
			}
		}

		if (!is_set($arFields, "REMIND_DATE") || !($arFields["REMIND_DATE"] = \Bitrix\Tasks\UI::checkDateTime($arFields["REMIND_DATE"])))
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_REMIND_DATE"), "id" => "ERROR_BAD_TASKS_REMIND_DATE");
		}

		if(array_key_exists('RECEPIENT_TYPE', $arFields) && !in_array($arFields['RECEPIENT_TYPE'], array(
			self::RECEPIENT_TYPE_SELF,
			self::RECEPIENT_TYPE_ORIGINATOR,
			self::RECEPIENT_TYPE_RESPONSIBLE
		)))
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_RECEPIENT_TYPE"), "id" => "ERROR_BAD_RECEPIENT_TYPE");
		}

		if (!empty($arMsg))
		{
			$this->errors = $arMsg;

			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		//Defaults
		if (!is_set($arFields, "TYPE") || $arFields["TYPE"] != self::REMINDER_TYPE_DEADLINE)
			$arFields["TYPE"] = self::REMINDER_TYPE_COMMON;

		if (!is_set($arFields, "TRANSPORT") || $arFields["TRANSPORT"] != self::REMINDER_TRANSPORT_JABBER)
			$arFields["TRANSPORT"] = self::REMINDER_TRANSPORT_EMAIL;

		return true;
	}


	public function Add($arFields)
	{
		if ($this->CheckFields($arFields))
		{
			$addResult = \Bitrix\Tasks\Internals\Task\ReminderTable::add(array(
				"USER_ID" => $arFields["USER_ID"],
				"TASK_ID" => $arFields["TASK_ID"],
				"REMIND_DATE" => Bitrix\Main\Type\DateTime::createFromUserTime($arFields['REMIND_DATE']),
				"TYPE" => $arFields["TYPE"],
				"TRANSPORT" => $arFields["TRANSPORT"],
				"RECEPIENT_TYPE" => $arFields["RECEPIENT_TYPE"],
			));
			$ID = $addResult->isSuccess()? $addResult->getId(): false;

			foreach(GetModuleEvents('tasks', 'OnTaskReminderAdd', true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));
			}

			return $ID;
		}

		return false;
	}


	private static function GetFilter($arFilter)
	{
		global $DB;

		if (!is_array($arFilter))
			$arFilter = Array();

		$arSqlSearch = Array();

		foreach ($arFilter as $key => $val)
		{
			$res = CTasks::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = mb_strtoupper($key);

			switch ($key)
			{
				case "TASK_ID":
				case "USER_ID":
					$arSqlSearch[] = CTasks::FilterCreate("TR.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "REMIND_DATE":
					$arSqlSearch[] = CTasks::FilterCreate("TR.".$key, \Bitrix\Tasks\Util\Db::charToDateFunction($val), "date", $bFullJoin, $cOperationType);
					break;
			}
		}

		return $arSqlSearch;
	}


	public static function GetList($arOrder, $arFilter)
	{
		/** @global CDatabase $DB */
		global $DB;

		$arSqlSearch = CTaskReminders::GetFilter($arFilter);

		$strSql = "
			SELECT
				TR.*,
				".$DB->DateToCharFunction("TR.REMIND_DATE")." AS REMIND_DATE
			FROM
				b_tasks_reminder TR
			".(sizeof($arSqlSearch) ? "WHERE ".implode(" AND ", $arSqlSearch) : "")."
		";

		if (!is_array($arOrder))
			$arOrder = Array();

		foreach ($arOrder as $by => $order)
		{
			$by = mb_strtolower($by);
			$order = mb_strtolower($order);
			if ($order != "asc")
				$order = "desc";

			if ($by == "task" || $by == 'TASK_ID')
				$arSqlOrder[] = " TR.TASK_ID ".$order." ";
			elseif ($by == "user" || $by == 'USER_ID')
				$arSqlOrder[] = " TR.USER_ID ".$order." ";
			elseif ($by == "date" || $by == 'REMIND_DATE')
				$arSqlOrder[] = " TR.REMIND_DATE ".$order." ";
			elseif ($by == 'RECEPIENT_TYPE')
				$arSqlOrder[] = " TR.RECEPIENT_TYPE ".$order." ";
			elseif ($by == "rand" || $by == 'RAND')
				$arSqlOrder[] = CTasksTools::getRandFunction();
			else
				$arSqlOrder[] = " TR.TASK_ID ".$order." ";
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);

		if(is_array($arSqlOrder))
		{
			$arSqlOrderCnt = count($arSqlOrder);
			for ($i = 0; $i < $arSqlOrderCnt; $i++)
			{
				if ($i == 0)
					$strSqlOrder = " ORDER BY ";
				else
					$strSqlOrder .= ",";

				$strSqlOrder .= $arSqlOrder[$i];
			}
		}

		$strSql .= $strSqlOrder;

		return $DB->Query($strSql);
	}

	/**
	 * @deprecated
	 * @TasksV2
	 * @use ReminderRepositoryInterface
	 */
	public static function DeleteByDate($REMIND_DATE)
	{
		$result = new DeleteResult();
		try
		{
			Container::getInstance()->getReminderRepository()->deleteByFilter(['=REMIND_DATE' => new \Bitrix\Main\Type\DateTime($REMIND_DATE)]);
		}
		catch (Exception $e)
		{
			$result->addError(\Bitrix\Main\Error::createFromThrowable($e));
		}

		return $result;
	}

	/**
	 * @deprecated
	 * @TasksV2
	 * @use ReminderRepositoryInterface
	 */
	public static function DeleteByTaskID($TASK_ID)
	{
		$result = new DeleteResult();
		try
		{
			Container::getInstance()->getReminderRepository()->deleteByFilter(['=TASK_ID' => (int)$TASK_ID]);
		}
		catch (Exception $e)
		{
			$result->addError(\Bitrix\Main\Error::createFromThrowable($e));
		}

		return $result;
	}

	/**
	 * @deprecated
	 * @TasksV2
	 * @use ReminderRepositoryInterface
	 */
	public static function DeleteByUserID($USER_ID)
	{
		$result = new DeleteResult();
		try
		{
			Container::getInstance()->getReminderRepository()->deleteByFilter(['=USER_ID' => (int)$USER_ID]);
		}
		catch (Exception $e)
		{
			$result->addError(\Bitrix\Main\Error::createFromThrowable($e));
		}

		return $result;
	}

	/**
	 * @deprecated
	 * @TasksV2
	 * @use ReminderRepositoryInterface
	 */
	public static function Delete($arFilter)
	{
		$result = new DeleteResult();
		try
		{
			Container::getInstance()->getReminderRepository()->deleteByFilter($arFilter);
		}
		catch (Exception $e)
		{
			$result->addError(\Bitrix\Main\Error::createFromThrowable($e));
		}

		return $result;
	}

	/**
	 * @deprecated
	 * @TasksV2
	 * @use Reminder
	 *
	 *  Don't remove it because agent with this name may be already registered in the system.
	 */
	public static function SendAgent()
	{
		return \Bitrix\Tasks\V2\Infrastructure\Agent\Reminder::execute();
	}
}