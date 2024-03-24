<?php

use Bitrix\Tasks\Control\Tag;
use Bitrix\Tasks\Internals\Task\LabelTable;
use Bitrix\Tasks\Internals\Task\TaskTagTable;
use Bitrix\Tasks\Provider\Tag\TagList;
use Bitrix\Tasks\Provider\Tag\TagQuery;

/**
 * @deprecated
 * @use Tag
 * @use LabelTable
 * Bitrix Framework
 *
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */
class CTaskTags
{
	function CheckFields(&$arFields, /** @noinspection PhpUnusedParameterInspection */ $ID = false,
		$effectiveUserId = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$arMsg = [];

		if (!is_set($arFields, "TASK_ID"))
		{
			$arMsg[] = ["text" => GetMessage("TASKS_BAD_TASK_ID"), "id" => "ERROR_TASKS_BAD_TASK_ID"];
		}
		else
		{
			$arParams = [];
			if ($effectiveUserId !== null)
			{
				$arParams['USER_ID'] = $effectiveUserId;
			}

			$r = CTasks::GetByID($arFields["TASK_ID"], true, $arParams);
			if (!$r->Fetch())
			{
				$arMsg[] = ["text" => GetMessage("TASKS_BAD_TASK_ID_EX"), "id" => "ERROR_TASKS_BAD_TASK_ID_EX"];
			}
		}

		if ($effectiveUserId !== null && !isset($arFields['USER_ID']))
		{
			$arFields['USER_ID'] = $effectiveUserId;
		}

		if (!is_set($arFields, "USER_ID"))
		{
			$arMsg[] = ["text" => GetMessage("TASKS_BAD_USER_ID"), "id" => "ERROR_TASKS_BAD_USER_ID"];
		}
		else
		{
			$r = CUser::GetByID($arFields["USER_ID"]);
			if (!$r->Fetch())
			{
				$arMsg[] = ["text" => GetMessage("TASKS_BAD_USER_ID_EX"), "id" => "ERROR_TASKS_BAD_USER_ID_EX"];
			}
		}

		if (!is_set($arFields, "NAME") || trim($arFields["NAME"]) == '')
		{
			$arMsg[] = ["text" => GetMessage("TASKS_BAD_NAME"), "id" => "ERROR_BAD_TASKS_NAME"];
		}

		if (!isset($arFields['GROUP_ID']))
		{
			$arFields['GROUP_ID'] = 0;
		}

		if (!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		return true;
	}

	public function Add($arFields, $effectiveUserId = null)
	{
		if ($this->CheckFields($arFields, false, $effectiveUserId))
		{
			$result = LabelTable::add([
				'NAME' => $arFields['NAME'],
				'USER_ID' => $arFields['USER_ID'],
				'GROUP_ID' => $arFields['GROUP_ID'] ?? 0,
			]);

			$tagId = LabelTable::getList([
				'select' => [
					'ID',
				],
				'filter' => [
					'=NAME' => $arFields['NAME'],
					'=GROUP_ID' => $arFields['GROUP_ID'] ?? 0,
					'=USER_NAME' => $arFields['USER_ID'],
				]
			])->fetchAll();

			$tagId = array_map(static function($el): int{
				return (int)$el['ID'];
			}, $tagId);

			if (count($tagId) !== 1)
			{
				return false;
			}

			$tagId = $tagId[0];

			$finalResult = TaskTagTable::add([
				'TAG_ID' => $tagId,
				'TASK_ID' => $arFields["TASK_ID"],
			]);

			if ($result->isSuccess() && $finalResult->isSuccess())
			{
				return $result->getId();
			}
		}
		return false;
	}

	public static function GetFilter($arFilter)
	{
		if (!is_array($arFilter))
		{
			$arFilter = [];
		}

		$arSqlSearch = [];

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
					$arSqlSearch[] = CTasks::FilterCreate("TT." . $key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "NAME":
					$arSqlSearch[] = CTasks::FilterCreate("TT." . $key, $val, "string_equal", $bFullJoin,
						$cOperationType);
					break;
			}
		}

		return $arSqlSearch;
	}

	public static function GetList($arOrder, $arFilter)
	{
		$select = [
			'ID',
			'NAME',
			'USER_ID',
			LabelTable::getRelationAlias() . '.TASK_ID' => 'TASK_ID',
			LabelTable::getRelationAlias() . '.ID' => 'LINK_ID',
		];
		$query = new TagQuery();
		$query
			->setSelect($select)
			->setWhere($arFilter)
			->setOrderBy($arOrder)
		;

		return (new TagList())->getList($query);
	}

	public function DeleteByName($NAME)
	{
		return self::Delete(["=NAME" => $NAME]);
	}

	public static function DeleteByTaskID($TASK_ID)
	{
		return self::Delete(["=TASK_ID" => (int)$TASK_ID]);
	}

	public function DeleteByUserID($USER_ID)
	{
		return self::Delete(["=USER_ID" => (int)$USER_ID]);
	}

	public static function Rename($OLD_NAME, $NEW_NAME, $USER_ID)
	{
		// $tasks = [];
		$list = LabelTable::getList([
			"select" => ['ID', "USER_ID", "NAME", 'GROUP_ID'],
			"filter" => [
				"=USER_ID" => intval($USER_ID),
				"=NAME" => $OLD_NAME,
				'=GROUP_ID' => 0,
			],
		])->fetchAll();
		$id = array_map(static function($el): int{
			return (int)$el['ID'];
		}, $list);

		if (count($id) !== 1)
		{
			return false;
		}
		$id = $id[0];

		$result = LabelTable::update($id,[
			'NAME' => $NEW_NAME,
		]);

		return $result->isSuccess();
	}

	public static function Delete(array $filter): bool
	{
		return LabelTable::deleteByFilter($filter)->isSuccess();
	}
}