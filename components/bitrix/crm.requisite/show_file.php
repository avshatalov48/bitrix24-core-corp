<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

$oauthToken = isset($_REQUEST['auth']) ? $_REQUEST['auth'] : '';
if ($oauthToken !== '')
{
	define('NOT_CHECK_PERMISSIONS', true);
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

global $USER_FIELD_MANAGER;

$errors = array();
if (CModule::IncludeModule('crm'))
{
	$bSuccess = true;

	$ownerID = isset($_REQUEST['ownerId']) ? intval($_REQUEST['ownerId']) : 0;
	$fieldName = isset($_REQUEST['fieldName']) ? strval($_REQUEST['fieldName']) : '';
	$fileID = isset($_REQUEST['fileId']) ? intval($_REQUEST['fileId']) : 0;

	if ($ownerID > 0 && $fieldName !== '' && $fileID > 0)
	{
		$authData = array();
		if ($oauthToken === ''
			|| (CModule::IncludeModule('rest')
			&& CRestUtil::checkAuth($authToken, CCrmRestService::SCOPE_NAME, $authData)
			&& CRestUtil::makeAuth($authData)))
		{
			$requisite = new \Bitrix\Crm\EntityRequisite();
			$res = $requisite->getList(
				array(
					'filter' => array('=ID' => $ownerID),
					'select' => array('ID', 'ENTITY_TYPE_ID', 'ENTITY_ID')
				)
			);
			$row = $res->fetch();
			unset($res);
			if (!is_array($row))
				$row = array();
			$entityTypeId = isset($row['ENTITY_TYPE_ID']) ? intval($row['ENTITY_TYPE_ID']) : 0;
			$entityId = isset($row['ENTITY_ID']) ? intval($row['ENTITY_ID']) : 0;
			unset($row);
			if ($requisite->validateEntityExists($entityTypeId, $entityId))
			{
				if ($requisite->validateEntityReadPermission($entityTypeId, $entityId))
				{
					$userFields = $USER_FIELD_MANAGER->GetUserFields($requisite->getUfId(), $ownerID, LANGUAGE_ID);
					$field = is_array($userFields) && isset($userFields[$fieldName]) ? $userFields[$fieldName] : null;
					if (is_array($field) && $field['USER_TYPE_ID'] === 'file')
					{
						$fileIDs = isset($field['VALUE'])
							? (is_array($field['VALUE'])
								? $field['VALUE']
								: array($field['VALUE']))
							: array();
						if (in_array($fileID, $fileIDs, false))
						{
							$file = new CFile();
							$fileInfo = $file->GetFileArray($fileID);
							if (is_array($fileInfo))
							{
								// Crutch for CFile::ViewByUser. Waiting for main 14.5.2
								set_time_limit(0);
								CFile::ViewByUser($fileInfo, array('force_download' => true));
							}
							else
								$errors[] = 'File not found';
						}
						else
							$errors[] = 'File not found';
					}
					else
						$errors[] = 'File not found';
				}
				else
					$errors[] = 'Access denied.';
			}
			else
				$errors[] = 'File not found';
		}
		else
			$errors[] = 'Access denied.';
	}
	else
		$errors[] = 'File not found';
}
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_after.php");
if (!empty($errors))
{
	foreach($errors as $error)
	{
		echo $error;
	}
}
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog.php");
