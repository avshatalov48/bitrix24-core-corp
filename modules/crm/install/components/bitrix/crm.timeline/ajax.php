<?php

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\BindingsMaker\ActivityBindingsMaker;

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('DisableEventsCheck', true);
define('DisableMessageServiceCheck', false);

$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
/**
 * AGENTS ARE REQUIRED FOR FOLLOWING ACTIONS:
 * 	BUILD_TIMELINE_SEARCH_CONTENT
 */
define(
	'NO_AGENT_CHECK',
	!in_array($action, array('REBUILD_TIMELINE_SEARCH_CONTENT'), true)
);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}

IncludeModuleLangFile(__FILE__);

/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'MARK_AS_DONE' - mark as done
 */


if(!function_exists('__CrmTimelineEndResponse'))
{
	function __CrmTimelineEndResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/json');
		if(!empty($result))
		{
			echo \Bitrix\Main\Web\Json::encode($result);
		}
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

$currentUser = CCrmSecurityHelper::GetCurrentUser();
if (!$currentUser || !$currentUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
CUtil::JSPostUnescape();
$GLOBALS['APPLICATION']->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
if($action == '')
{
	__CrmTimelineEndResponse(array('ERROR' => 'Invalid data.'));
}

CBitrixComponent::includeComponentClass("bitrix:crm.timeline");
$component = new CCrmTimelineComponent();

if($action == 'SAVE_COMMENT') // OBSOLETE: new API 'crm.timeline.comment.add' is used
{
	$ownerTypeID = isset($_POST['OWNER_TYPE_ID']) ? (int)$_POST['OWNER_TYPE_ID'] : 0;
	$ownerID = isset($_POST['OWNER_ID']) ? (int)$_POST['OWNER_ID'] : 0;
	$text = isset($_POST['TEXT']) ? $_POST['TEXT'] : '';
	if($text === '')
	{
		__CrmTimelineEndResponse(array('ERROR' => 'Empty comment message.'));
	}

	if (!\Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($ownerTypeID, $ownerID))
	{
		__CrmTimelineEndResponse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	$authorID = CCrmSecurityHelper::GetCurrentUserID();
	$attachments = isset($_POST['ATTACHMENTS']) && is_array($_POST['ATTACHMENTS']) ? $_POST['ATTACHMENTS'] : array();

	if (!empty($attachments))
		$settings = array('HAS_FILES' => 'Y');
	else
		$settings = array('HAS_FILES' => 'N');

	$entryID = \Bitrix\Crm\Timeline\CommentEntry::create(
		array(
			'TEXT' => $text,
			'FILES' => $attachments,
			'SETTINGS' => $settings,
			'AUTHOR_ID' => $authorID,
			'BINDINGS' => array(array('ENTITY_TYPE_ID' => $ownerTypeID, 'ENTITY_ID' => $ownerID))
		)
	);

	if($entryID <= 0)
	{
		__CrmTimelineEndResponse(array('ERROR' => 'Could not create comment.'));
	}
	$saveData = array(
		'COMMENT' => $text,
		'ENTITY_TYPE_ID' => $ownerTypeID,
		'ENTITY_ID' => $ownerID,
	);
	$item = Bitrix\Crm\Timeline\CommentController::getInstance()->onCreate($entryID, $saveData);

	__CrmTimelineEndResponse(array('HISTORY_ITEM' => $item));
}
elseif($action == 'SAVE_WAIT')
{
	$siteID = !empty($_REQUEST['siteID']) ? $_REQUEST['siteID'] : SITE_ID;

	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : array();
	if(count($data) == 0)
	{
		__CrmTimelineEndResponse(array('ERROR'=>'SOURCE DATA ARE NOT FOUND!'));
	}

	$ID = isset($data['ID']) ? (int)$data['ID'] : 0;
	$arActivity = null;
	if($ID > 0 && !\Bitrix\Crm\Pseudoactivity\WaitEntry::exists($ID))
	{
		__CrmTimelineEndResponse(array('ERROR'=>'IS NOT EXISTS!'));
	}

	$ownerTypeName = isset($data['ownerType'])? mb_strtoupper(strval($data['ownerType'])) : '';
	if($ownerTypeName === '')
	{
		__CrmTimelineEndResponse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
	}

	$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
	if(!CCrmOwnerType::IsDefined($ownerTypeID))
	{
		__CrmTimelineEndResponse(array('ERROR'=>'OWNER TYPE IS NOT SUPPORTED!'));
	}

	$ownerID = isset($data['ownerID']) ? intval($data['ownerID']) : 0;
	if($ownerID <= 0)
	{
		__CrmTimelineEndResponse(array('ERROR'=>'OWNER ID IS NOT DEFINED!'));
	}

	if(!\Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($ownerTypeID, $ownerID))
	{
		__CrmTimelineEndResponse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	$responsibleID = isset($data['responsibleID']) ? intval($data['responsibleID']) : 0;
	if($responsibleID <= 0)
	{
		$responsibleID = $currentUser->GetID();
	}

	$duration = isset($data['duration']) ? (int)$data['duration'] : 0;
	if($duration <= 0)
	{
		$duration = 1;
	}

	$typeId = isset($data['typeId']) ? (int)$data['typeId'] : 0;
	$targetFieldName = isset($data['targetFieldName']) ? $data['targetFieldName'] : '';
	$effectiveFieldName = '';

	if($targetFieldName !== '')
	{
		$fieldInfos = null;
		if($ownerTypeID === CCrmOwnerType::Deal)
		{
			$fieldInfos = \CCrmDeal::GetFieldsInfo();
			$userType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], \CCrmDeal::GetUserFieldEntityID());
			$userType->PrepareFieldsInfo($fieldInfos);
		}

		if(is_array($fieldInfos) && isset($fieldInfos[$targetFieldName]))
		{
			$fieldInfo = $fieldInfos[$targetFieldName];
			$fieldType = isset($fieldInfo['TYPE']) ? $fieldInfo['TYPE'] : '';
			if($fieldType === 'date')
			{
				$effectiveFieldName = $targetFieldName;
			}
		}
	}

	$now = new \Bitrix\Main\Type\DateTime();
	$start = $now;
	$end = null;

	if($typeId === 2 && $effectiveFieldName !== '')
	{
		$time = 0;
		$fields = null;
		if($ownerTypeID === CCrmOwnerType::Deal)
		{
			$dbResult = \CCrmDeal::GetListEx(
				array(),
				array('CHECK_PERMISSIONS' => 'N', '=ID' => $ownerID),
				false,
				false,
				array('ID', $effectiveFieldName)
			);
			$fields = $dbResult->Fetch();
		}
		else if($ownerTypeID === CCrmOwnerType::Lead)
		{
			$dbResult = \CCrmLead::GetListEx(
				array(),
				array('CHECK_PERMISSIONS' => 'N', '=ID' => $ownerID),
				false,
				false,
				array('ID', $effectiveFieldName)
			);
			$fields = $dbResult->Fetch();
		}

		if(is_array($fields))
		{
			$targetDate = isset($fields[$effectiveFieldName]) ? $fields[$effectiveFieldName] : '';
			if($targetDate !== '')
			{
				$time = MakeTimeStamp($targetDate);
				$endTime = $time - ($duration * 86400) - CTimeZone::GetOffset();

				$currentDate = new \Bitrix\Main\Type\Date();
				$endDate = \Bitrix\Main\Type\Date::createFromTimestamp($endTime);
				$end = \Bitrix\Main\Type\Date::createFromTimestamp($endTime);

				if($endDate->getTimestamp() <= $currentDate->getTimestamp())
				{
					__CrmTimelineEndResponse(
						array('ERROR' => GetMessage("CRM_WAIT_ACTION_INVALID_BEFORE_PARAMS"))
					);
				}
			}
		}
	}

	if($end === null)
	{
		$end = new \Bitrix\Main\Type\DateTime();
		$end->add("{$duration}D");
	}

	$descr = isset($data['description']) ? strval($data['description']) : '';

	$arFields = array(
		'OWNER_TYPE_ID' => $ownerTypeID,
		'OWNER_ID' => $ownerID,
		'AUTHOR_ID' => $responsibleID,
		'START_TIME' => $start,
		'END_TIME' => $end,
		'COMPLETED' => 'N',
		'DESCRIPTION' => $descr
	);

	if($ID <= 0)
	{
		$result = \Bitrix\Crm\Pseudoactivity\WaitEntry::add($arFields);
		if($result->isSuccess())
		{
			$arFields['ID'] = $result->getId();
		}
		else
		{
			__CrmTimelineEndResponse(
				array('ERROR' => implode("\n", $result->getErrorMessages()))
			);
		}
	}
	else
	{
	}

	__CrmTimelineEndResponse(array('WAIT' => $arFields));
}
elseif($action == 'COMPLETE_WAIT')
{
	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : array();
	if(count($data) == 0)
	{
		__CrmTimelineEndResponse(array('ERROR'=>'SOURCE DATA ARE NOT FOUND!'));
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	if($ID <= 0)
	{
		__CrmTimelineEndResponse(array('ERROR' => GetMessage('CRM_WAIT_ACTION_INVALID_REQUEST_DATA')));
	}

	$ownerTypeID = isset($data['OWNER_TYPE']) ? (int)$data['OWNER_TYPE_ID'] : 0;
	$ownerID = isset($data['OWNER_ID']) ? (int)$data['OWNER_ID'] : 0;

	if(!CCrmOwnerType::IsDefined($ownerTypeID) || $ownerID < 0)
	{
		$fields = \Bitrix\Crm\Pseudoactivity\WaitEntry::getByID($ID);
		if(!is_array($fields))
		{
			__CrmTimelineEndResponse(array('ERROR' => GetMessage('CRM_WAIT_ACTION_ITEM_NOT_FOUND')));
		}

		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? (int)$fields['OWNER_TYPE_ID'] : 0;
		$ownerID = isset($data['OWNER_ID']) ? (int)$data['OWNER_ID'] : 0;
	}

	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	if(!\Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($ownerTypeID, $ownerID))
	{
		__CrmTimelineEndResponse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	if(!CCrmOwnerType::IsDefined($ownerTypeID) || $ownerID > 0)
	{
		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? intval($fields['OWNER_TYPE_ID']) : CCrmOwnerType::Undefined;
		$ownerID = isset($fields['OWNER_ID']) ? intval($fields['OWNER_ID']) : 0;
	}

	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	if(!\Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($ownerTypeID, $ownerID))
	{
		__CrmTimelineEndResponse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	$completed = isset($data['COMPLETED']) && mb_strtoupper($data['COMPLETED']) === 'Y';
	$result = \Bitrix\Crm\Pseudoactivity\WaitEntry::complete($ID, $completed);
	if($result->isSuccess())
	{
		$responseData = array('ID'=> $ID, 'COMPLETED'=> $completed);
		__CrmTimelineEndResponse($responseData);
	}
	else
	{
		__CrmTimelineEndResponse(
			array('ERROR' => implode("\n", $result->getErrorMessages()))
		);
	}
}
elseif($action == 'POSTPONE_WAIT')
{
	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : array();
	if(count($data) == 0)
	{
		__CrmTimelineEndResponse(array('ERROR'=>'SOURCE DATA ARE NOT FOUND!'));
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	if($ID <= 0)
	{
		__CrmTimelineEndResponse(array('ERROR' => 'Invalid data!'));
	}

	$ownerTypeID = isset($data['OWNER_TYPE']) ? (int)$data['OWNER_TYPE_ID'] : 0;
	$ownerID = isset($data['OWNER_ID']) ? (int)$data['OWNER_ID'] : 0;

	if(!CCrmOwnerType::IsDefined($ownerTypeID) || $ownerID < 0)
	{
		$fields = \Bitrix\Crm\Pseudoactivity\WaitEntry::getByID($ID);
		if(!is_array($fields))
		{
			__CrmTimelineEndResponse(array('ERROR' => GetMessage('CRM_WAIT_ACTION_ITEM_NOT_FOUND')));
		}

		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? (int)$fields['OWNER_TYPE_ID'] : 0;
		$ownerID = isset($data['OWNER_ID']) ? (int)$data['OWNER_ID'] : 0;
	}

	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	if(!\Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($ownerTypeID, $ownerID))
	{
		__CrmTimelineEndResponse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	$offset = isset($data['OFFSET']) ? (int)$data['OFFSET'] : 0;
	if($offset <= 0)
	{
		__CrmTimelineEndResponse(array('ERROR' => 'Invalid offset'));
	}

	$result = \Bitrix\Crm\Pseudoactivity\WaitEntry::postpone($ID, $offset);
	if($result->isSuccess())
	{
		__CrmTimelineEndResponse(array('ID' => $ID, 'POSTPONED' => $offset));
	}
	else
	{
		__CrmTimelineEndResponse(array('ERROR' => 'Postpone denied.'));
	}
}
elseif($action == 'GET_HISTORY_ITEMS')
{
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();

	$guid = isset($params['GUID']) ? $params['GUID'] : '';
	$ownerTypeID = isset($params['OWNER_TYPE_ID']) ? (int)$params['OWNER_TYPE_ID'] : 0;
	$ownerID = isset($params['OWNER_ID']) ? (int)$params['OWNER_ID'] : 0;
	$categoryId = isset($params['CATEGORY_ID']) ? (int)$params['CATEGORY_ID'] : 0;

	if(!\Bitrix\Crm\Security\EntityAuthorization::checkReadPermission($ownerTypeID, $ownerID))
	{
		__CrmTimelineEndResponse(array('ERROR' => 'Access denied.'));
	}

	$component->setGuid($guid);
	$component->setEntityTypeID($ownerTypeID);
	$component->setEntityID($ownerID);
	$component->setExtras(['CATEGORY_ID' => $categoryId]);

	$component->prepareHistoryFilter();

	$navigation = isset($params['NAVIGATION']) ? $params['NAVIGATION'] : array();
	$offsetTime = null;
	if(isset($navigation['OFFSET_TIMESTAMP']))
	{
		$offsetTime = \Bitrix\Main\Type\DateTime::tryParse($navigation['OFFSET_TIMESTAMP'], 'Y-m-d H:i:s');
	}
	$offsetID = isset($navigation['OFFSET_ID']) ? (int)$navigation['OFFSET_ID'] : 0;

	$component->prepareHistoryItems($offsetTime, $offsetID);

	__CrmTimelineEndResponse(
		array(
			'HISTORY_ITEMS' => $component->arResult['HISTORY_ITEMS'],
			'HISTORY_NAVIGATION' => $component->arResult['HISTORY_NAVIGATION']
		)
	);
}
elseif($action == 'SAVE_SMS_MESSAGE')
{
	$ownerTypeId = (int)($_REQUEST['OWNER_TYPE_ID'] ?? 0);
	if($ownerTypeId <= 0)
	{
		__CrmTimelineEndResponse(['ERROR'=>'OWNER TYPE IS NOT SUPPORTED!']);
	}

	$ownerId = (int)($_REQUEST['OWNER_ID'] ?? 0);
	if($ownerId <= 0)
	{
		__CrmTimelineEndResponse(['ERROR'=>'OWNER ID IS NOT DEFINED!']);
	}

	$owner = new ItemIdentifier($ownerTypeId, $ownerId);

	$senderId = isset($_REQUEST['SENDER_ID']) ? (string)$_REQUEST['SENDER_ID'] : null;
	$messageFrom = isset($_REQUEST['MESSAGE_FROM']) ? (string)$_REQUEST['MESSAGE_FROM'] : null;
	$messageTo = isset($_REQUEST['MESSAGE_TO']) ? (string)$_REQUEST['MESSAGE_TO'] : null;
	$messageBody = isset($_REQUEST['MESSAGE_BODY']) ? (string)$_REQUEST['MESSAGE_BODY'] : null;
	$messageTemplate = $_REQUEST['MESSAGE_TEMPLATE'] ?? null;
	$message = new \Bitrix\Crm\Activity\Provider\Sms\MessageDto([
		'senderId' => $senderId,
		'from' => $messageFrom,
		'to' => $messageTo,
		'body' => $messageBody,
		'template' => $messageTemplate,
	]);

	$sender = (new \Bitrix\Crm\Activity\Provider\Sms\Sender($owner, $message));

	if (isset($_REQUEST['PAYMENT_ID']))
	{
		$sender->setPaymentId((int) $_REQUEST['PAYMENT_ID']);
	}

	if (isset($_REQUEST['SHIPMENT_ID']))
	{
		$sender->setShipmentId((int) $_REQUEST['SHIPMENT_ID']);
	}

	if (isset($_REQUEST['source']))
	{
		$sender->setSource($_REQUEST['source']);
	}

	if (isset($_REQUEST['COMPILATION_PRODUCT_IDS']) && is_array($_REQUEST['COMPILATION_PRODUCT_IDS']))
	{
		$sender->setCompilationProductIds($_REQUEST['COMPILATION_PRODUCT_IDS']);
	}

	$comEntityTypeId = (int)($_REQUEST['TO_ENTITY_TYPE_ID'] ?? 0);
	$comEntityId = (int)($_REQUEST['TO_ENTITY_ID'] ?? 0);
	if ($comEntityTypeId && $comEntityId)
	{
		$entity = new ItemIdentifier($comEntityTypeId, $comEntityId);
		$sender->setEntityIdentifier($entity);
	}

	$result = $sender->send();

	__CrmTimelineEndResponse(
		$result->isSuccess()
			? []
			: ['ERROR' => implode(PHP_EOL, $result->getErrorMessages())]
	);
}
/**
 * @deprecated
 * @use crm.timeline.item.pin / crm.timeline.item.unpin actions instead
 * @see \Bitrix\Crm\Controller\Timeline\Item::pinAction
 */
elseif($action == 'CHANGE_FASTEN_ITEM')
{
	$ownerTypeID = isset($_POST['OWNER_TYPE_ID']) ? (int)$_POST['OWNER_TYPE_ID'] : 0;
	$ownerID = isset($_POST['OWNER_ID']) ? (int)$_POST['OWNER_ID'] : 0;
	$id = isset($_POST['ID']) ? (int)($_POST['ID']) : 0;
	$value = ($_POST['VALUE'] === "Y") ? "Y" : "N";

	if(!\Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($ownerTypeID, $ownerID))
	{
		__CrmTimelineEndResponse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	$checkData = Bitrix\Crm\Timeline\Entity\TimelineTable::getList(
		array(
			'select' => array('ID'),
			'filter' => array(
				'=ID' => $id,
				'=BINDING.ENTITY_ID' => $ownerID ,
				'=BINDING.ENTITY_TYPE_ID' => $ownerTypeID
			),
			'runtime' => array(
				new \Bitrix\Main\Entity\ReferenceField(
					'BINDING',
					'\Bitrix\Crm\Timeline\Entity\TimelineBindingTable',
					array("=ref.OWNER_ID" => "this.ID"),
					array("join_type"=>"INNER")
				)
			),
			'limit' => 1
		)
	);

	if (!$checkData->fetch())
	{
		__CrmTimelineEndResponse(array('ERROR' => 'Entity is not found'));
	}

	$resultUpdating = Bitrix\Crm\Timeline\Entity\TimelineBindingTable::update(
		array('OWNER_ID' => $id, 'ENTITY_ID' => $ownerID , 'ENTITY_TYPE_ID' => $ownerTypeID),
		array('IS_FIXED' => $value));

	if ($resultUpdating->isSuccess())
	{
		\Bitrix\Crm\Timeline\Controller::getInstance()->sendPullEventOnPin(
			new ItemIdentifier($ownerTypeID, $ownerID),
			$id,
			$value == 'Y'
		);

		__CrmTimelineEndResponse(array('ID' => $id));
	}
	else
	{
		__CrmTimelineEndResponse(array('ERROR' => 'Could not fasten item.'));
	}

	__CrmTimelineEndResponse(array('ID' => $id));
}
elseif($action == 'UPDATE_COMMENT') // OBSOLETE: new API 'crm.timeline.comment.update' is used
{
	$id =  isset($_POST['ID']) ? (int)$_POST['ID'] : 0;
	$text = isset($_POST['TEXT']) ? $_POST['TEXT'] : '';
	$ownerTypeID = isset($_POST['OWNER_TYPE_ID']) ? (int)$_POST['OWNER_TYPE_ID'] : 0;
	$ownerID = isset($_POST['OWNER_ID']) ? (int)$_POST['OWNER_ID'] : 0;
	$attachments = isset($_POST['ATTACHMENTS']) && is_array($_POST['ATTACHMENTS']) ? $_POST['ATTACHMENTS'] : array();

	if ($id <= 0)
	{
		__CrmTimelineEndResponse(array('ERROR' => "Entity is not found"));
	}

	if(!\Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($ownerTypeID, $ownerID))
	{
		__CrmTimelineEndResponse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	$resultCheck = Bitrix\Crm\Timeline\Entity\TimelineTable::getList(
		array(
			'filter' => array('=ID' => $id, '=TYPE_ID' => Bitrix\Crm\Timeline\TimelineType::COMMENT),
		)
	);
	$oldMentions = array();
	if ($commentData = $resultCheck->fetch())
	{
		$oldMentions = \Bitrix\Crm\Timeline\CommentController::getMentionIds($commentData['COMMENT']);
	}
	else
	{
		__CrmTimelineEndResponse(array('ERROR' => 'Comment is not found.'));
	}

	$resultBind = Bitrix\Crm\Timeline\Entity\TimelineBindingTable::getList(
		array(
			'filter' => array('=OWNER_ID' => $id)
		)
	);

	$isExistBinding = false;
	$bindings =  array();
	while ($bindData = $resultBind->fetch())
	{
		if ((int)$bindData['ENTITY_TYPE_ID'] === $ownerTypeID && (int)$bindData['ENTITY_ID'] === $ownerID)
		{
			$isExistBinding = true;
		}
		$bindings[] = $bindData;
	}

	if (!$isExistBinding)
		__CrmTimelineEndResponse(array('ERROR' => 'Could not update comment.'));

	if (!empty($attachments))
		$settings = array('HAS_FILES' => 'Y');
	else
		$settings = array('HAS_FILES' => 'N');

	if (count($bindings) > 1)
	{
		$newId = \Bitrix\Crm\Timeline\CommentEntry::create(array(
			'CREATED' => $commentData['CREATED'],
			'AUTHOR_ID' => $commentData['AUTHOR_ID'],
			'SETTINGS' => $commentData['SETTINGS'],
			'TEXT' => $commentData['COMMENT'],
			'FILES' => $attachments,
			'BINDINGS' => array(array('ENTITY_TYPE_ID' => $ownerTypeID, 'ENTITY_ID' => $ownerID))
		));

		$bindingDelete = \Bitrix\Crm\Timeline\Entity\TimelineBindingTable::delete(array(
			'OWNER_ID' => $id,
			'ENTITY_ID' => $ownerID,
			'ENTITY_TYPE_ID' => $ownerTypeID,
		));

		if ($bindingDelete->isSuccess())
		{
			if (\Bitrix\Main\Loader::includeModule('pull'))
			{
				$tag = \Bitrix\Crm\Timeline\TimelineEntry::prepareEntityPushTag($ownerTypeID, $ownerID);
				\CPullWatch::AddToStack(
					$tag,
					array(
						'module_id' => 'crm',
						'command' => 'timeline_changed_binding',
						'params' => array('OLD_ID' => $id, 'NEW_ID' => $newId),
					)
				);
			}

			$id = $newId;
		}
	}

	$resultUpdating = \Bitrix\Crm\Timeline\CommentEntry::update($id, [
		'COMMENT' => $text,
		'SETTINGS' => $settings,
		'FILES' => $attachments
	]);

	if ($resultUpdating->isSuccess())
	{
		$saveData = array(
			'COMMENT' => $text,
			'ENTITY_TYPE_ID' => $ownerTypeID,
			'ENTITY_ID' => $ownerID,
			'OLD_MENTION_LIST' => $oldMentions
		);
		$item = Bitrix\Crm\Timeline\CommentController::getInstance()->onModify($id, $saveData);
		__CrmTimelineEndResponse(array('HISTORY_ITEM' => $item));
	}
	else
	{
		__CrmTimelineEndResponse(array('ERROR' => 'Could not update comment.'));
	}
}
elseif($action == 'DELETE_COMMENT') // OBSOLETE: new API 'crm.timeline.comment.delete' is used
{
	$ownerTypeID = isset($_POST['OWNER_TYPE_ID']) ? (int)$_POST['OWNER_TYPE_ID'] : 0;
	$ownerID = isset($_POST['OWNER_ID']) ? (int)$_POST['OWNER_ID'] : 0;
	$id =  isset($_POST['ID']) ? (int)$_POST['ID'] : 0;

	if ($id <= 0)
	{
		__CrmTimelineEndResponse(array('ERROR' => "Entity is not found"));
	}

	$resultBind = Bitrix\Crm\Timeline\Entity\TimelineBindingTable::getList(
		array(
			'filter' => array('=OWNER_ID' => $id),
		)
	);

	$isExistBinding = false;
	$bindings = array();
	while ($bindData = $resultBind->fetch())
	{
		if ((int)$bindData['ENTITY_TYPE_ID'] === $ownerTypeID && (int)$bindData['ENTITY_ID'] === $ownerID)
		{
			$isExistBinding = true;
		}
		$bindings[] = $bindData;
	}

	if (!$isExistBinding)
		__CrmTimelineEndResponse(array('ERROR' => 'Could not delete comment.'));

	if(!\Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($ownerTypeID, $ownerID))
	{
		__CrmTimelineEndResponse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	if (count($bindings) > 1)
	{
		\Bitrix\Crm\Timeline\Entity\TimelineBindingTable::delete(array(
			'OWNER_ID' => $id,
			'ENTITY_ID' => $ownerID,
			'ENTITY_TYPE_ID' => $ownerTypeID,
		));
	}
	else
	{
		Bitrix\Crm\Timeline\CommentEntry::delete($id);
	}

	Bitrix\Crm\Timeline\CommentController::getInstance()->onDelete($id, array(
		'ENTITY_TYPE_ID' => $ownerTypeID,
		'ENTITY_ID' => $ownerID,
	));

	__CrmTimelineEndResponse(array('ID' => $id));
}
elseif($action == 'GET_COMMENT_CONTENT') // OBSOLETE: new API 'crm.timeline.comment.load' is used
{
	$entityTypeID = isset($_REQUEST['ENTITY_TYPE_ID']) ? (int)$_REQUEST['ENTITY_TYPE_ID'] : 0;
	$id = isset($_POST['ID']) ? (int)$_POST['ID'] : 0;
	$entityID = isset($_POST['ENTITY_ID']) ? (int)$_POST['ENTITY_ID'] : 0;

	if (!\Bitrix\Crm\Security\EntityAuthorization::checkReadPermission($entityTypeID, $entityID))
	{
		__CrmTimelineEndResponse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	$resultBind = Bitrix\Crm\Timeline\Entity\TimelineBindingTable::getList(
		array(
			'filter' => array('=OWNER_ID' => $id, "=ENTITY_TYPE_ID" => $entityTypeID, "=ENTITY_ID" => $entityID,),
			'limit' => 1
		)
	);

	if (!$resultBind->fetch())
		__CrmTimelineEndResponse(array('ERROR' => "Entity is not found"));

	if ($_POST['TYPE'] === 'GET_FILE_BLOCK')
	{
		$html = \Bitrix\Crm\Timeline\CommentController::getFileBlock($id);
	}
	else
	{
		$commentData = \Bitrix\Crm\Timeline\TimelineEntry::getByID($id);
		$data = \Bitrix\Crm\Timeline\CommentController::convertToHtml($commentData, array("INCLUDE_FILES" => 'Y'));
		$html = $data['COMMENT'];
	}

	if (empty($html))
		__CrmTimelineEndResponse(array('ERROR' => "Content is empty"));

	__CrmTimelineEndResponse(array('BLOCK' => $html));
}
elseif($action == 'DELETE_DOCUMENT')
{
	$ownerTypeID = isset($_POST['OWNER_TYPE_ID']) ? (int)$_POST['OWNER_TYPE_ID'] : 0;
	$ownerID = isset($_POST['OWNER_ID']) ? (int)$_POST['OWNER_ID'] : 0;
	$id =  isset($_POST['ID']) ? (int)$_POST['ID'] : 0;

	if ($id <= 0)
	{
		__CrmTimelineEndResponse(['ERROR' => "Entity is not found"]);
	}

	$resultBind = Bitrix\Crm\Timeline\Entity\TimelineBindingTable::getList(
		[
			'filter' => ['=OWNER_ID' => $id],
		]
	);

	$isExistBinding = false;
	$bindings = [];
	while($bindData = $resultBind->fetch())
	{
		if((int)$bindData['ENTITY_TYPE_ID'] === $ownerTypeID && (int)$bindData['ENTITY_ID'] === $ownerID)
		{
			$isExistBinding = true;
		}
		$bindings[] = $bindData;
	}

	if(!$isExistBinding)
	{
		__CrmTimelineEndResponse(['ERROR' => 'Could not delete document.']);
	}

	if(!\Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($ownerTypeID, $ownerID))
	{
		__CrmTimelineEndResponse(['ERROR' => GetMessage('CRM_PERMISSION_DENIED')]);
	}

	if(count($bindings) > 1)
	{
		\Bitrix\Crm\Timeline\Entity\TimelineBindingTable::delete([
			'OWNER_ID' => $id,
			'ENTITY_ID' => $ownerID,
			'ENTITY_TYPE_ID' => $ownerTypeID,
		]);
	}
	else
	{
		$result = new \Bitrix\Main\Result();
		if(\Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->isEnabled())
		{
			$entry = \Bitrix\Crm\Timeline\DocumentEntry::getByID($id);
			if(is_array($entry) && isset($entry['SETTINGS']) && isset($entry['SETTINGS']['DOCUMENT_ID']))
			{
				$documentId = $entry['SETTINGS']['DOCUMENT_ID'];
				if(\Bitrix\DocumentGenerator\Driver::getInstance()->getUserPermissions()->canModifyDocument($documentId))
				{
					$result = \Bitrix\DocumentGenerator\Model\DocumentTable::delete($entry['SETTINGS']['DOCUMENT_ID']);
				}
				else
				{
					$result->addError(new \Bitrix\Main\Error(GetMessage('CRM_PERMISSION_DENIED')));
				}
			}
		}
		if($result->isSuccess())
		{
			Bitrix\Crm\Timeline\DocumentEntry::delete($id);
			Bitrix\Crm\Timeline\DocumentController::getInstance()->onDelete($id, [
				'ENTITY_TYPE_ID' => $ownerTypeID,
				'ENTITY_ID' => $ownerID,
				'COMMENT' => GetMessage('CRM_TIMELINE_DOCUMENT_DELETED'),
			]);
			__CrmTimelineEndResponse(['ID' => $id]);
		}
		else
		{
			__CrmTimelineEndResponse(['ERROR' => join("\n", $result->getErrorMessages())]);
		}
	}
}
elseif($action === 'GET_PERMISSIONS')
{
	$ID =  isset($_POST['ID']) ? (int)$_POST['ID'] : 0;
	$typeID =  isset($_POST['TYPE_ID']) ? (int)$_POST['TYPE_ID'] : \Bitrix\Crm\Timeline\TimelineType::UNDEFINED;

	if($typeID === \Bitrix\Crm\Timeline\TimelineType::ACTIVITY)
	{
		$dbResult = CCrmActivity::GetList(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'TYPE_ID', 'PROVIDER_ID', 'PROVIDER_TYPE_ID', 'ASSOCIATED_ENTITY_ID')
		);

		$fields = $dbResult->Fetch();
		if(!is_array($fields))
		{
			__CrmTimelineEndResponse(array('ERROR' => 'Not found'));
		}

		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		__CrmTimelineEndResponse(
			array(
				'PERMISSIONS' => array(
					'USER_ID' => \CCrmSecurityHelper::GetCurrentUserID(),
					'POSTPONE' => \CCrmActivity::CheckItemPostponePermission($fields, $userPermissions),
					'COMPLETE' => \CCrmActivity::CheckItemCompletePermission($fields, $userPermissions)
				)
			)
		);
	}
	elseif($typeID === \Bitrix\Crm\Timeline\TimelineType::WAIT)
	{
		$fields = \Bitrix\Crm\Pseudoactivity\WaitEntry::getByID($ID);
		if(!is_array($fields))
		{
			__CrmTimelineEndResponse(array('ERROR' => 'Not found'));
		}

		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? (int)$fields['OWNER_TYPE_ID'] : 0;
		$ownerID = isset($fields['OWNER_ID']) ? (int)$fields['OWNER_ID'] : 0;

		$canUpdate = \Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission(
			$ownerTypeID,
			$ownerID,
			\CCrmPerms::GetCurrentUserPermissions()
		);

		__CrmTimelineEndResponse(
			array(
				'PERMISSIONS' => array(
					'USER_ID' => \CCrmSecurityHelper::GetCurrentUserID(),
					'POSTPONE' => $canUpdate,
					'COMPLETE' => $canUpdate
				)
			)
		);
	}
	__CrmTimelineEndResponse(array('ERROR' => 'Type is not supported'));
}
elseif($action === 'REBUILD_TIMELINE_SEARCH_CONTENT')
{
	$agent = \Bitrix\Crm\Agent\Search\TimelineSearchContentRebuildAgent::getInstance();
	if($agent->isEnabled() && !$agent->isActive())
	{
		$agent->enable(false);
	}
	if(!$agent->isEnabled())
	{
		__CrmTimelineEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmTimelineEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS']
		)
	);
}
