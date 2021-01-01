<?php

namespace Bitrix\Crm\Integration\BizProc\Document;

use Bitrix\Main;
use Bitrix\Main\NotImplementedException;

if (!Main\Loader::includeModule('bizproc'))
{
	return;
}

/**
 * Class TmpDoc
 * @package Bitrix\Crm\Integration\BizProc\Document
 * @internal
 */
final class TmpDoc implements \IBPWorkflowDocument
{
	private static $documents = [];

	public static function createNewDocument(array $fields): array
	{
		$uid = uniqid();
		self::$documents[$uid] = $fields;

		return ['crm', __CLASS__, $uid];
	}

	public static function getDocumentFields($documentType)
	{
		return self::$documents[$documentType] ?? [];
	}

	public static function GetDocument($documentId)
	{
		$fields = self::$documents[$documentId] ?? [];
		return array_fill_keys(array_keys($fields), null);
	}

	public static function CreateDocument($parentDocumentId, $arFields)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	public static function UpdateDocument($documentId, $arFields)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	public static function DeleteDocument($documentId)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	public static function PublishDocument($documentId)
	{
		return true;
	}

	public static function UnpublishDocument($documentId)
	{
		return true;
	}

	public static function LockDocument($documentId, $workflowId)
	{
		return true;
	}

	public static function UnlockDocument($documentId, $workflowId)
	{
		return true;
	}

	public static function IsDocumentLocked($documentId, $workflowId)
	{
		return false;
	}

	public static function CanUserOperateDocument($operation, $userId, $documentId, $arParameters = [])
	{
		return true;
	}

	public static function CanUserOperateDocumentType($operation, $userId, $documentType, $arParameters = [])
	{
		return true;
	}

	public static function GetDocumentAdminPage($documentId)
	{
		return '/dev/null';
	}

	public static function GetDocumentForHistory($documentId, $historyIndex)
	{
		return [];
	}

	public static function RecoverDocumentFromHistory($documentId, $arDocument)
	{
		return true;
	}

	public static function GetAllowableOperations($documentType)
	{
		return [];
	}

	public static function GetAllowableUserGroups($documentType)
	{
		return [];
	}

	public static function GetUsersFromUserGroup($group, $documentId)
	{
		return [];
	}
}