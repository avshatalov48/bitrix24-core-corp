<?php
/** @var CWebDavIblock $ob */
/** @var CAllMain $APPLICATION */
/** @var CUser $USER */
global $APPLICATION, $USER, $DB;
if(!empty($_REQUEST['editIn']))
{
	$ob->_path = CHTTP::urnDecode($ob->_path);
	$serviceEditDoc = strtolower($_REQUEST['editIn']);
	switch($serviceEditDoc)
	{
		case 'g':
		case 'google':
		case 'gdrive':
			$serviceEditDoc = CWebDavLogOnlineEditBase::GOOGLE_SERVICE_NAME;
			break;
		case 's':
		case 'skydrive':
		case 'sky-drive':
		case 'onedrive':
			$serviceEditDoc = CWebDavLogOnlineEditBase::SKYDRIVE_SERVICE_NAME;
			break;
		case 'l':
		case 'local':
			$serviceEditDoc = CWebDavLogOnlineEditBase::LOCAL_SERVICE_NAME;
			break;
		default:
			$serviceEditDoc = CWebDavLogOnlineEditBase::GOOGLE_SERVICE_NAME;
			break;
	}


	//check: this document edit by another user (with another service?)
	$wdElement = $ob->GetObject(array('check_permissions' => false), false);
	$lastSession = CWebDavLogOnlineEdit::getOnlineLastSession(array(
		'IBLOCK_ID' => $wdElement['element_array']['IBLOCK_ID'],
		'SECTION_ID' => $wdElement['element_array']['IBLOCK_SECTION_ID'],
		'ELEMENT_ID' => $wdElement['element_array']['ID'],
	));
	unset($wdElement);
	if($lastSession && $lastSession['SERVICE'] != CWebDavLogOnlineEditBase::LOCAL_SERVICE_NAME)
	{
		$serviceEditDoc = $lastSession['SERVICE'];
		unset($lastSession);
	}

	if($serviceEditDoc == CWebDavLogOnlineEditBase::DEFAULT_SERVICE_NAME)
	{
		$serviceEditDoc = CWebDavTools::getServiceEditDocForCurrentUser();
	}
	$editComponentParams = array(
		'webdav' => $ob,
		'fileId' => (int)$_REQUEST['f'],
		'createDoc' => !empty($_REQUEST['createDoc']),
		'action' => $_REQUEST['action'],
	);
	if($serviceEditDoc == CWebDavLogOnlineEditBase::GOOGLE_SERVICE_NAME)
	{
		$APPLICATION->RestartBuffer();
		$APPLICATION->IncludeComponent('bitrix:webdav.doc.edit.google', '', $editComponentParams);
		include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
		die;
	}
	elseif($serviceEditDoc == CWebDavLogOnlineEditBase::SKYDRIVE_SERVICE_NAME)
	{
		$APPLICATION->RestartBuffer();
		$APPLICATION->IncludeComponent('bitrix:webdav.doc.edit.skydrive', '', $editComponentParams);
		include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
		die;
	}
	elseif($serviceEditDoc == CWebDavLogOnlineEditBase::LOCAL_SERVICE_NAME)
	{
		if(isset($_REQUEST['primaryAction']) && $_REQUEST['primaryAction'] == 'commit' && $editComponentParams['action'] == 'start')
		{
			$editComponentParams['action'] = 'commit';
		}
		$APPLICATION->RestartBuffer();
		$APPLICATION->IncludeComponent('bitrix:webdav.doc.edit.local', '', $editComponentParams);
		include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
		die;
	}
}
if(!empty($_REQUEST['createDoc']))
{
	$serviceEditDoc = empty($_REQUEST['createIn'])? '' : strtolower($_REQUEST['createIn']);
	if(empty($serviceEditDoc) || $serviceEditDoc == CWebDavLogOnlineEditBase::DEFAULT_SERVICE_NAME)
	{
		$serviceEditDoc = CWebDavTools::getServiceEditDocForCurrentUser();
	}

	$serviceEditDoc = strtolower($_REQUEST['createIn']);
	switch($serviceEditDoc)
	{
		case 'g':
		case 'google':
		case 'gdrive':
			$serviceEditDoc = CWebDavLogOnlineEditBase::GOOGLE_SERVICE_NAME;
			break;
		case 's':
		case 'skydrive':
		case 'sky-drive':
		case 'onedrive':
			$serviceEditDoc = CWebDavLogOnlineEditBase::SKYDRIVE_SERVICE_NAME;
			break;
		case 'l':
		case 'local':
			$serviceEditDoc = CWebDavLogOnlineEditBase::LOCAL_SERVICE_NAME;
			break;
		default:
			$serviceEditDoc = CWebDavLogOnlineEditBase::GOOGLE_SERVICE_NAME;
			break;
	}

	$createComponentParams = array(
		'webdav' => $ob,
		'createDoc' => true,
		'createType' => !empty($_REQUEST['type'])? $_REQUEST['type'] : false,
		'newFileName' => !empty($_REQUEST['newFileName'])? $_REQUEST['newFileName'] : false,
		'commitDoc' => !empty($_REQUEST['commit'])? $_REQUEST['commit'] : false
	);
	if($serviceEditDoc == CWebDavLogOnlineEditBase::GOOGLE_SERVICE_NAME)
	{
		$APPLICATION->RestartBuffer();
		$APPLICATION->IncludeComponent('bitrix:webdav.doc.edit.google', '', $createComponentParams);
		include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
		die;
	}
	elseif($serviceEditDoc == CWebDavLogOnlineEditBase::SKYDRIVE_SERVICE_NAME)
	{
		$APPLICATION->RestartBuffer();
		$APPLICATION->IncludeComponent('bitrix:webdav.doc.edit.skydrive', '', $createComponentParams);
		include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
		die;
	}
}
elseif(!empty($_REQUEST['history']))
{
	$wdElement = $ob->GetObject(array('check_permissions' => false), false);
	if(!$ob->CheckWebRights('',  array('action' => 'read', 'arElement' => $wdElement), false))
	{
		CWebDavTools::sendJsonResponse(array(
			'error' => 'access_denied',
		));
	}

	$APPLICATION->RestartBuffer();
	$APPLICATION->IncludeComponent('bitrix:webdav.element.history', '', array('webdav' => $ob, 'ajax' => true));
	include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");

	die;
}
elseif(!empty($_REQUEST['showInViewer']))
{
	CUtil::JSPostUnescape();
	CWebDavExtLinks::CheckSessID();
	//alert to check_permissions
	$wdElement = $ob->GetObject(array('check_permissions' => false), false);
	//todo hack by load from url with historyget (example)
	if($wdElement['not_found'] && $wdElement['basename'] && intval($wdElement['basename']) == $wdElement['basename'])
	{
		$wdElement = $ob->GetObject(array('check_permissions' => false, 'element_id' => $wdElement['basename']), false);
	}
	if(!$ob->CheckWebRights('',  array('action' => 'read', 'arElement' => $wdElement), false))
	{
		CWebDavTools::sendJsonResponse(array(
			'error' => 'access_denied',
			'message' => GetMessage("WD_ACCESS_DENIED"),
		));
	}

	if(!empty($_POST['checkViewByGoogle']))
	{
		CWebDavTools::sendJsonResponse(array(
			'viewByGoogle' => CWebDavExtLinks::getDownloadCountForLink($_POST['extLink']) > 0,
		));
	}

	$hash = CWebDavExtLinks::getHashLink(array(
				'IBLOCK_TYPE' => $ob->IBLOCK_TYPE,
				'IBLOCK_ID' => $wdElement['element_array']['IBLOCK_ID'],
				'ROOT_SECTION_ID' => $ob->arRootSection['ID']
			), array(
				'PASSWORD' => '',
				'LIFETIME_NUMBER' => CWebDavExtLinks::LIFETIME_TYPE_AUTO,
				'LIFETIME_TYPE' => 'minute',
				'URL' => $ob->_path,
				'BASE_URL' => $ob->base_url,
				'SINGLE_SESSION' => false,
				'LINK_TYPE' => CWebDavExtLinks::LINK_TYPE_AUTO,
				'VERSION_ID' => !empty($_GET['v'])? $_GET['v'] : null,
				'FILE_ID' => !empty($_GET['f'])? $_GET['f'] : null,
				'ELEMENT_ID' => $wdElement['item_id'],
			), null);

	if(!empty($_POST['json']))
	{
		CWebDavTools::sendJsonResponse(array(
			'file' => $hash,
			'viewerUrl' => CWebDavExtLinks::$urlGoogleViewer . urlencode($hash) . '&embedded=true',
		));
	}
}
elseif(!empty($_REQUEST['saveToDisk']))
{
	if($USER->GetId() && check_bitrix_sessid())
	{
		$data = CWebDavIblock::getRootSectionDataForUser($USER->GetID());
		if($data)
		{
			$savedFolderId = CIBlockWebdavSocnet::createSavedFolder($data['IBLOCK_ID'], $data['SECTION_ID'], $USER->GetID());
			if($savedFolderId)
			{
				$wdElement = $ob->GetObject(array('check_permissions' => false), false);
				//todo hack by load from url with historyget (example)
				if($wdElement['not_found'])
				{
					$partUri = explode('element/historyget/', $ob->uri);
					if(!empty($partUri[1]))
					{
						$elementId = (int)$partUri[1];
						$wdElement = $ob->GetObject(array('check_permissions' => false, 'element_id' => $elementId), false);
					}
				}

				if($ob->CheckWebRights('',  array('action' => 'read', 'arElement' => $wdElement), false))
				{
					$storage = new CWebDavStorageCore();
					$storage->setStorageId(array(
						'IBLOCK_ID' => $data['IBLOCK_ID'],
						'IBLOCK_SECTION_ID' => $data['SECTION_ID'],
					));
					$filename = $storage->regenerateNameIfNonUnique($wdElement['element_name'], $savedFolderId);
					$tmpFile = new CWebDavStubTmpFile;
					if($_REQUEST['v'])
					{
						list($arFileH, $optionsH, $fullpath, $filenameH) = $ob->getHistoryFileData($wdElement['element_array']['ID'], (int)$_REQUEST['v'], $p);
						$tmpFile->path = $fullpath;
					}
					else
					{
						$copyTmpFile = CFile::MakeFileArray($wdElement['element_array']['PROPERTY_FILE_VALUE']);
						$tmpFile->path = $copyTmpFile['tmp_name'];
					}

					$response = array();
					try
					{
						$fileData = $storage->addFile($filename, $savedFolderId, $tmpFile);

						$savedName = CWebDavIblock::getSavedMetaData();
						$pathToUserLib = str_replace(array('#USER_ID#', '#user_id#'), array($USER->GetID(), $USER->GetID()), CWebDavIblock::LibOptions('lib_paths', true, $data['IBLOCK_ID']));
						$pathToUserLib = strstr($pathToUserLib, 'files/element', true) . 'files/lib';
						$pathToUserLib = $pathToUserLib . '/' . $savedName['alias'] . '?result=doc' . $fileData['extra']['id'];

						$response = array(
							'status' => 'success',
							'newId' => $fileData['extra']['id'],
							'viewUrl' => $pathToUserLib,
						);
					}
					catch(Exception $e)
					{
						$response['status'] = 'error';
						$fileData = array();
					}

					CWebDavTools::sendJsonResponse($response);
				}
			}
		}
	}
}
elseif(!empty($_REQUEST['downloadHistory']) && !empty($_REQUEST['id']))
{
	$APPLICATION->RestartBuffer();
	$APPLICATION->IncludeComponent('bitrix:webdav.element.history', '', array(
		'webdav' => $ob,
		'ajax' => false,
		'elementId' => (int)$_REQUEST['id'],
		'versionId' => (int)$_REQUEST['v'],
		'fileId' => (int)$_REQUEST['f'],
	));
	include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");

	die;
}
elseif(!empty($_REQUEST['wdaction']))
{
	$wdAction = strtolower($_REQUEST['wdaction']);
	if($wdAction == 'connect' || $wdAction == 'disconnect')
	{
		$attachObjectType = null;
		$attachObjectId = null;
		if(!empty($_REQUEST['group']))
		{
			$attachObjectType = CWebDavSymlinkHelper::ENTITY_TYPE_GROUP;
			$attachObjectId = (int)$_REQUEST['group'];
		}
		elseif(!empty($_REQUEST['shareSectionId']))
		{
			$attachObjectType = CWebDavSymlinkHelper::ENTITY_TYPE_USER;
			$attachObjectId = (int)$_REQUEST['shareSectionId'];
		}

		$APPLICATION->RestartBuffer();
		$inviteComponentParams = array(
			'ajax' => true,
			'action' => $wdAction,
			'attachObject' => array(
				'id' => $attachObjectId,
				'type' => $attachObjectType,
			),
			'attachToUserId' => empty($_REQUEST['toUser']) ? $USER->getId() : $_REQUEST['toUser'],
			'inviteFromUserId' => $USER->getId(),
		);
		$APPLICATION->IncludeComponent('bitrix:webdav.invite', '', $inviteComponentParams);
		include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
		die;
	}
	elseif($wdAction == 'detail_group_connect')
	{
		$attachObjectType = null;
		$attachObjectId = null;
		if(!empty($_REQUEST['group']))
		{
			$attachObjectType = CWebDavSymlinkHelper::ENTITY_TYPE_GROUP;
			$attachObjectId = (int)$_REQUEST['group'];
		}

		$APPLICATION->RestartBuffer();
		$inviteComponentParams = array(
			'ajax' => true,
			'action' => $wdAction,
			'attachObject' => array(
				'id' => $attachObjectId,
				'type' => $attachObjectType,
			),
			'attachToUserId' => $USER->getId(),
		);
		$APPLICATION->IncludeComponent('bitrix:webdav.invite', '', $inviteComponentParams);
		include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
		die;
	}
	elseif(
		   $wdAction == 'detail_user_share'
		|| $wdAction == 'load_users_for_detail_user_share'
		|| $wdAction == 'info_user_share'
	)
	{
		$attachObjectType = null;
		$attachObjectId = null;
		if(!empty($_REQUEST['shareSectionId']))
		{
			$attachObjectType = CWebDavSymlinkHelper::ENTITY_TYPE_USER;
			$attachObjectId = (int)$_REQUEST['shareSectionId'];
		}
		if(!empty($_REQUEST['group']))
		{
			$attachObjectType = CWebDavSymlinkHelper::ENTITY_TYPE_GROUP;
			$attachObjectId = (int)$_REQUEST['group'];
		}


		$APPLICATION->RestartBuffer();
		$inviteComponentParams = array(
			'ajax' => true,
			'action' => $wdAction,
			'attachObject' => array(
				'id' => $attachObjectId,
				'type' => $attachObjectType,
			),
			'attachToUserId' => empty($_REQUEST['toUser']) ? $USER->getId() : $_REQUEST['toUser'],
			'inviteFromUserId' => $USER->getId(),
			'pathToUser' => $arResult["PATH_TO_USER"],
			'pathToGroup' => $arParams["PATH_TO_GROUP"],
			'page' => isset($_POST['page'])? $_POST['page'] : 0,
			'userListType' => isset($_POST['userListType'])? $_POST['userListType'] : 'cannot_edit',
			'currentUserCanUnshare' => ($wdAction != 'info_user_share') && ($USER->getId() == $ob->attributes['user_id'] || $USER->isAdmin()),
		);
		$APPLICATION->IncludeComponent('bitrix:webdav.invite', '', $inviteComponentParams);
		include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
		die;
	}
	elseif($wdAction == 'share' || $wdAction == 'unshare')
	{
		CUtil::JSPostUnescape();
		$attachObjectType = null;
		$attachObjectId = null;
		if(!empty($_REQUEST['shareSectionId']))
		{
			$attachObjectType = CWebDavSymlinkHelper::ENTITY_TYPE_USER;
			$attachObjectId = (int)$_REQUEST['shareSectionId'];
		}

		$APPLICATION->RestartBuffer();
		$inviteComponentParams = array(
			'ajax' => true,
			'action' => $wdAction,
			'attachObject' => array(
				'id' => $attachObjectId,
				'type' => $attachObjectType,
			),
			'pathToUser' => $arResult["PATH_TO_USER"],
			'pathToGroup' => $arParams["PATH_TO_GROUP"],
			'canForward' => !empty($_POST['canForward']),
			'canEdit' => !empty($_POST['canEdit']),
			'inviteDescription' => !empty($_POST['inviteDescription'])? $_POST['inviteDescription'] : '',
			'attachToUserIds' => empty($_POST['shareToUsers']) ? array() : $_POST['shareToUsers'],
			'unshareUserIds' => empty($_POST['unshareUsers']) ? array() : $_POST['unshareUsers'],
			'inviteFromUserId' => $USER->getId(),
			'currentUserCanUnshare' => $USER->getId() == $ob->attributes['user_id'] || $USER->isAdmin(),
		);
		$APPLICATION->IncludeComponent('bitrix:webdav.invite', '', $inviteComponentParams);
		include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
		die;
	}
}