<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

abstract class CWebDavEditDocComponentBase extends CBitrixComponent
{
	/** @var CWebDavEditDocBase */
	protected $docHandler;
	protected $accessToken;
	protected $fileId = null;
	protected $versionId = null;
	protected $action = '';

	/**
	 * @param string $action
	 */
	public function setAction($action)
	{
		$this->action = $action;
	}

	/**
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @param null $versionId
	 */
	public function setVersionId($versionId)
	{
		$this->versionId = $versionId;
	}

	/**
	 * @return null
	 */
	public function getVersionId()
	{
		return $this->versionId;
	}

	/**
	 * @param string $fileId
	 * @return $this
	 */
	public function setFileId($fileId)
	{
		$this->fileId = $fileId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getFileId()
	{
		return $this->fileId;
	}

	/**
	 * @param \CWebDavEditDocBase $docHandler
	 * @return $this
	 */
	protected function setDocHandler($docHandler)
	{
		$this->docHandler = $docHandler;

		return $this;
	}

	/**
	 * @return $this
	 */
	abstract protected function initDocHandler();

	/**
	 * @return \CWebDavEditDocBase
	 */
	public function getDocHandler()
	{
		return $this->docHandler;
	}

	/**
	 * @param mixed $accessToken
	 * @return $this
	 */
	public function setAccessToken($accessToken)
	{
		$this->accessToken = $accessToken;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getAccessToken()
	{
		return $this->accessToken;
	}

	/**
	 * @return CWebDavIblock
	 */
	public function getWebdav()
	{
		//todo throw exception
		return $this->arParams['webdav'];
	}

	protected function convertExtension($ext)
	{
		if(isset(CWebDavExtLinks::$convertFormatInGoogle[$ext]))
		{
			return CWebDavExtLinks::$convertFormatInGoogle[$ext];
		}
		return $ext;
	}

	protected function getNameToSavedFile($oldName)
	{
		$ext = GetFileExtension($oldName);

		return GetFileNameWithoutExtension($oldName) . '.' . $this->convertExtension($ext);
	}

	/**
	 * @return array
	 */
	protected function commitFile()
	{
		$filename = CTempFile::GetFileName(uniqid('_wd'));
		$nameToSavedFile = $this->getNameToSavedFile($this->getWebdav()->arParams['element_name']);
		CheckDirPath($filename);
		$doc = $this
			->getDocHandler()
				->downloadFile(array(
					'id' => $this->getFileId(),
					'mimeType' => $this
							->getWebdav()
							->get_mime_type($nameToSavedFile)
				))
		;

		if(!$doc['content'])
		{
			return array('status' => 'error');
		}
		file_put_contents($filename, $doc['content']);
		$runRename = $nameToSavedFile != $this->getWebdav()->arParams['element_name'];
		$oldName = $this->getWebdav()->arParams['element_name'];
		$options = array(
			'new' => false,
			'FILE_NAME' => $nameToSavedFile,
			'NAME' => $nameToSavedFile,
			'ELEMENT_ID' => $this->getWebdav()->arParams['item_id'],
			'TMP_FILE' => $filename,
		);
		global $DB;
		$DB->startTransaction();
		if (!$this->getWebdav()->put_commit($options))
		{
			$DB->rollback();
			$response = array('status' => 'error');
		}
		else
		{
			$DB->commit();
			$webdav = $this->getWebdav();
			$response = array(
				'status' => 'success',
			);
			$response['elementId'] = $webdav->arParams['element_array']['ID'];
			//this is info for comment. And fileId == false,as this is last version and not save in BPhistory
			$response['cid'] = $this->generateCidForFile($response['elementId']);
			$response['serialize'] = CUserTypeWebdavElementHistory::genData(array(
				'ib' => $webdav->IBLOCK_ID,
				'ib_code' => $webdav->arParams['element_array']['IBLOCK_CODE'],
				'id' => $response['elementId'],
			), array(
				't_vers' => empty($options['THROUGH_VERSION'])? 0 : $options['THROUGH_VERSION'],
			));
			if($runRename)
			{
				CWebDavTools::clearByTag('webdav_element_internal_' . $webdav->arParams['element_array']['ID']);
				$response['newName'] = $options['NAME'];
				$response['oldName'] = $oldName;
			}
		}
		@unlink($filename);

		return $response;
	}

	protected function renameFile(array $fileData)
	{
		$webdav = $this->getWebDav();

		$storage = new CWebDavStorageCore();
		$storage->setWebDav($webdav);
		$storage->setStorageId(array(
			'IBLOCK_ID' => $webdav->IBLOCK_ID,
			'IBLOCK_SECTION_ID' => $fileData['sectionId'],
		));
		$nameToSavedFile = $storage->regenerateNameIfNonUnique($fileData['newName'], $fileData['sectionId']);
		$newFileData = $storage->moveFile($nameToSavedFile, $fileData['elementId'], $fileData['sectionId']);
		if(!$newFileData)
		{
			return array('status' => 'error');
		}

		return array(
			'status' => 'success',
		);
	}

	protected function saveNewFile(array $fileData)
	{
		$filename = CTempFile::GetFileName(uniqid('_wd'));
		CheckDirPath($filename);
		$doc = $this
			->getDocHandler()
				->downloadFile(array(
					'id' => $this->getFileId(),
					'mimeType' => $this
							->getWebdav()
							->get_mime_type('1.' . $fileData['createType'])
				))
		;

		file_put_contents($filename, $doc['content']);

		global $USER;
		$dataUserSection = CWebDavIblock::getRootSectionDataForUser($USER->GetID());
		if(!$dataUserSection)
		{
			return array('status' => 'error');
		}

		$createdDocFolderId = CIBlockWebdavSocnet::createCreatedDocFolder($dataUserSection['IBLOCK_ID'], $dataUserSection['SECTION_ID'], $USER->GetID());
		if(!$createdDocFolderId)
		{
			return array('status' => 'error');
		}
		$storage = new CWebDavStorageCore();
		$storage->setStorageId(array(
			'IBLOCK_ID' => $dataUserSection['IBLOCK_ID'],
			'IBLOCK_SECTION_ID' => $dataUserSection['SECTION_ID'],
		));
		$nameToSavedFile = $storage->regenerateNameIfNonUnique($doc['name'], $createdDocFolderId);
		$tmpFile = new CWebDavStubTmpFile;
		$tmpFile->path = $filename;

		try
		{
			$fileData = $storage->addFile($nameToSavedFile, $createdDocFolderId, $tmpFile);
			$response = array(
				'status' => 'success',
				'elementId' => $fileData['extra']['id'],
				'sectionId' => $fileData['extra']['sectionId'],
				'name' => $nameToSavedFile,
				'sizeInt' => $fileData['size'],
				'type' => CWebDavBase::get_mime_type($nameToSavedFile),
				'link' => str_replace('#element_id#', $fileData['extra']['id'], CWebDavSocNetEvent::getRuntime()->arPath['ELEMENT_EDIT_INLINE_URL']),
				'nameWithoutExtension' => GetFileNameWithoutExtension($nameToSavedFile),
			);
		}
		catch(Exception $e)
		{
			$response = array('status' => 'error');
		}

		return $response;
	}

	protected static function getIdHistoryDocument(array $filter)
	{
		$by      = "modified";
		$order   = "desc";
		$history = new CBPHistoryService();
		$dbDocumentHistory = $history->GetHistoryList(
			array(strtoupper($by) => strtoupper($order)),
			$filter,
			false,
			array('nTopCount' => 1),
			array(
				"ID",
				"DOCUMENT_ID",
				"NAME",
				"MODIFIED",
				"USER_ID",
				"USER_NAME",
				"USER_LAST_NAME",
				"USER_LOGIN",
				"DOCUMENT",
				"USER_SECOND_NAME"
			)
		);

		if($res = $dbDocumentHistory->fetch())
		{
			return $res;
		}
		else
		{
			//if not exists second entry, then original is same webdav element.
		}

		return array();
	}

	/**
	 * @param $iblockCode
	 * @return string
	 */
	protected static function getEntityType($iblockCode)
	{
		$entityType = explode('_', $iblockCode);
		$entityType = strtolower(array_shift($entityType));

		return $entityType;
	}

	protected static function getEntityIdDocumentData($entityType, $params = array())
	{
		if ($entityType == 'group')
		{
			$documentId  = array(
				'webdav',
				'CIBlockDocumentWebdavSocnet',
				$params['ELEMENT_ID']
			);

		}
		elseif ($entityType == 'shared')
		{
			$documentId  = array(
				'webdav',
				'CIBlockDocumentWebdav',
				$params['ELEMENT_ID']
			);
		}
		else
		{
			$documentId  = array(
				'webdav',
				'CIBlockDocumentWebdavSocnet',
				$params['ELEMENT_ID']
			);
		}
		return $documentId;

	}
	/**
	 * Fake gen CID for attach file to post or comment
	 * @param $fileId
	 * @return string
	 */
	protected function generateCidForFile($fileId)
	{
		$cid = substr(md5(rand(100,999999)), 0, 5);
		if (!isset($_SESSION["MFI_UPLOADED_FILES_".$cid]))
		{
			$_SESSION["MFI_UPLOADED_FILES_".$cid] = array($fileId);
		}
		else
		{
			$_SESSION["MFI_UPLOADED_FILES_".$cid][] = $fileId;
		}

		return $cid;
	}

	/**
	 * @param bool $returnSession  - return data of deleted session if delete success, else - false;
	 * @return bool|\CDBResult|array
	 */
	protected function deleteSession($returnSession = false)
	{
		if($this->isExclusiveEdit())
		{
			return $returnSession? array() : true;
		}
		//delete user session from document edit
		$webdav = $this->getWebdav();

		if($returnSession)
		{
			$onlineEditor = CWebDavLogOnlineEdit::getList(array(), array(
				'IBLOCK_ID' => $webdav->arParams['element_array']['IBLOCK_ID'],
				'SECTION_ID' => $webdav->arParams['element_array']['IBLOCK_SECTION_ID'],
				'ELEMENT_ID' => $webdav->arParams['element_array']['ID'],
				'SERVICE' => $this->getServiceName(),
				'SERVICE_FILE_ID' => $this->getFileId(),
				'USER_ID' => $this->getUser()->getId(),
			));

			if(($onlineEditor = $onlineEditor->fetch()) && empty($onlineEditor['ID']))
			{
				return false;
			}
			//todo delete session not by ID. This is mistake?
			return CWebDavLogOnlineEdit::delete(array(
				'IBLOCK_ID' => $webdav->arParams['element_array']['IBLOCK_ID'],
				'SECTION_ID' => $webdav->arParams['element_array']['IBLOCK_SECTION_ID'],
				'ELEMENT_ID' => $webdav->arParams['element_array']['ID'],
				'SERVICE' => $this->getServiceName(),
				'SERVICE_FILE_ID' => $this->getFileId(),
				'USER_ID' => $this->getUser()->getId(),
			))? $onlineEditor : false;
		}

		return CWebDavLogOnlineEdit::delete(array(
			'IBLOCK_ID' => $webdav->arParams['element_array']['IBLOCK_ID'],
			'SECTION_ID' => $webdav->arParams['element_array']['IBLOCK_SECTION_ID'],
			'ELEMENT_ID' => $webdav->arParams['element_array']['ID'],
			'SERVICE' => $this->getServiceName(),
			'SERVICE_FILE_ID' => $this->getFileId(),
			'USER_ID' => $this->getUser()->getId(),
		));
	}

	/**
	 * @return bool
	 */
	protected function isLastSession()
	{
		if($this->isExclusiveEdit())
		{
			return true;
		}
		//todo query count
		//check: this is last session?
		$webdav       = $this->getWebdav();
		$onlineEditor = CWebDavLogOnlineEdit::getList(array(), array(
			'IBLOCK_ID' => $webdav->arParams['element_array']['IBLOCK_ID'],
			'SECTION_ID' => $webdav->arParams['element_array']['IBLOCK_SECTION_ID'],
			'ELEMENT_ID' => $webdav->arParams['element_array']['ID'],
			'SERVICE' => $this->getServiceName(),
			'SERVICE_FILE_ID' => $this->getFileId(),
		));

		if($onlineEditor)
		{
			return !(bool)$onlineEditor->fetch();
		}
		return true;
	}

	/**
	 *
	 */
	protected function removeFile(array $onlineEditor = array())
	{
		if(!empty($onlineEditor['OWNER_ID']) && $onlineEditor['OWNER_ID'] != $this->getUser()->getId())
		{
			$accessTokenByCurrentUser = $this->getAccessToken();

			$oAuthUrl = CUtil::JSEscape($this->getOAuthUrlBySocServ());
			$accessTokenByOwnerDoc = $this->getAccessTokenByUserSocServ($onlineEditor['OWNER_ID']);
			$this
				->getDocHandler()
					->setAccessToken($accessTokenByOwnerDoc)
			;
		}

		//todo check permissions. Can we delete alien file?
		//empty result. Last session - delete doc from google docs
		$a = $this
			->getDocHandler()
				->removeFile(array('id' => $this->getFileId()))
		;
		//restore access token by current user
		if(!empty($accessTokenByOwnerDoc))
		{
			$this
				->getDocHandler()
					->setAccessToken($accessTokenByCurrentUser)
			;
		}
	}

	protected function addOnlineSessionByCurrentUser(array $onlineEditor)
	{
		if($this->isExclusiveEdit())
		{
			return true;
		}
		//todo make clone operation to editOnline
		$webdav = $this->getWebdav();

		return CWebDavLogOnlineEdit::add(array(
			'USER_ID' => $this->getUser()->getId(),
			'IBLOCK_ID' => $webdav->arParams['element_array']['IBLOCK_ID'],
			'SECTION_ID' => $webdav->arParams['element_array']['IBLOCK_SECTION_ID'],
			'ELEMENT_ID' => $webdav->arParams['element_array']['ID'],
			'SERVICE' => $this->getServiceName(),
			'SERVICE_FILE_ID' => $onlineEditor['SERVICE_FILE_ID'],
			'SERVICE_FILE_LINK' => $onlineEditor['SERVICE_FILE_LINK'],
			'OWNER_ID' => $onlineEditor['OWNER_ID'],
		));
	}

	protected function getOnlineLastSession()
	{
		if($this->isExclusiveEdit())
		{
			return array();
		}
		$webdav = $this->getWebdav();

		return CWebDavLogOnlineEdit::getOnlineLastSession(array(
				'IBLOCK_ID' => $webdav->arParams['element_array']['IBLOCK_ID'],
				'SECTION_ID' => $webdav->arParams['element_array']['IBLOCK_SECTION_ID'],
				'ELEMENT_ID' => $webdav->arParams['element_array']['ID'],
		), $this->getServiceName());
	}

	protected function publicBlankFile(CWebDavBlankDocument $doc)
	{
		$response = $this
			->getDocHandler()
			->createBlankFile(
				array(
					'name' => $doc->getNewFileName() . $doc->getExtension(),
					'mimeType' => $doc->getMimeType(),
					'src' => $doc->getSrc(),
					'size' => $doc->getFileSize(),
				),
				$this->getAccessToken()
		);

		if(empty($response))
		{
			return array(
				'error' => GetMessage('WD_DOC_EDIT_UNKNOWN_ERROR'),
 			);
		}
		//todo ERROR!!
		$uriToDoc = $this->generateUriToDoc();

		return array(
			'iframeSrc' => $response['link'],
			'uriToDoc' => $uriToDoc,
			'idDoc' => $response['id'],
		);
	}

	protected function publicFile()
	{
		$onlineEditor = $this->getOnlineLastSession();
		if($onlineEditor)
		{
			//create session on the basis of last session
			$this->addOnlineSessionByCurrentUser($onlineEditor);
			$idDoc = CUtil::JSEscape($onlineEditor['SERVICE_FILE_ID']);
			$linkDoc = CUtil::JSEscape($onlineEditor['SERVICE_FILE_LINK']);
		}
		else
		{
			if(substr($this->getWebdav()->arParams['file_array']['SRC'], 0, 1) == "/")
			{
				//from us server
			}
			else
			{
				//from cloud
			}

			$response = $this
				->getDocHandler()
				->publicFile(
					$this->getFileData(),
					$this->getAccessToken()
				);
			if(!$response)
			{
				return array(
					'error' => GetMessage('WD_DOC_EDIT_UNKNOWN_ERROR'),
				);
			}
			//if somebody public to google similar document
			$onlineEditor = $this->getOnlineLastSession();
			if(!$onlineEditor)
			{
				//current user is publisher of this document
				$onlineEditor['SERVICE_FILE_ID'] = $response['id'];
				$onlineEditor['SERVICE_FILE_LINK'] = $response['link'];
				$onlineEditor['OWNER_ID'] = $this->getUser()->getID();
			}
			$this->addOnlineSessionByCurrentUser($onlineEditor);

			$idDoc = CUtil::JSEscape($onlineEditor['SERVICE_FILE_ID']);
			$linkDoc = CUtil::JSEscape($onlineEditor['SERVICE_FILE_LINK']);
		}
		$uriToDoc = $this->generateUriToDoc();

		return array(
			'iframeSrc' => $linkDoc,
			'uriToDoc' => $uriToDoc,
			'idDoc' => $idDoc,
		);
	}

	protected function getFileData()
	{
		$webdav = $this->getWebdav();

		if(!empty($this->arParams['fileId']) && $this->arParams['fileId'] != $webdav->arParams['file_array']['ID'])
		{
			$entityType = static::getEntityType($webdav->arParams['element_array']['IBLOCK_CODE']);
			$document = $webdav->findHistoryDocumentByFileId(
				$webdav->arParams['element_array']['ID'],
				$this->arParams['fileId'],
				static::getEntityIdDocumentData($entityType, array('ELEMENT_ID' => $webdav->arParams['element_array']['ID']))
			);
			$this->setVersionId((int)$document['ID']);
		}

		list(
			$arFile,
			$options,
			$fullpath,
			$elementName
			) = $webdav->getHistoryFileData($webdav->arParams['element_array']['ID'], $this->getVersionId()?:0, $params);

		return array(
			'name' => $elementName,
			'mimeType' => $arFile['CONTENT_TYPE']?: $webdav->get_mime_type($elementName),
			'src' => $fullpath,
			'size' => $arFile['FILE_SIZE'],
		);
	}

	/**
	 * @return string
	 */
	abstract protected function generateUriToDoc();
	/**
	 * @return string
	 */
	abstract protected function getAccessTokenBySocServ();

	/**
	 * Get access token by another user (not current)
	 * @param $userId
	 * @return string
	 */
	abstract protected function getAccessTokenByUserSocServ($userId);
	/**
	 * @return string
	 */
	abstract protected function getOAuthUrlBySocServ();

	/**
	 * @return void
	 */
	abstract protected function checkActiveSocServ();

	public function onPrepareComponentParams($arParams)
	{
		$arParams['createType'] = !empty($arParams['createType'])? strtolower($arParams['createType']) : null;
		$arParams['newFileName'] = !empty($arParams['newFileName'])? $arParams['newFileName'] : '';
		$arParams['commitDoc'] = !empty($arParams['commitDoc']);

		return parent::onPrepareComponentParams($arParams);
	}

	protected function isPublicNewFile()
	{
		return isset($this->arParams['createType']) && !empty($this->arParams['createDoc']) && empty($this->arParams['commitDoc']);
	}

	protected function isSaveNewFile()
	{
		return isset($this->arParams['createType']) && !empty($this->arParams['createDoc']) && !empty($this->arParams['commitDoc']);
	}

	protected function isRenameFile()
	{
		return strtolower($this->getAction()) == 'rename';
	}

	protected function isCreationFile()
	{
		return !empty($this->arParams['createDoc']);
	}

	public function executeComponent()
	{
		try
		{
			CUtil::JSPostUnescape();
			$this->checkSessid();
			if(!CModule::IncludeModule('socialservices'))
			{
				$this->sendJsonResponse(array('error' => GetMessage('WD_DOC_INSTALL_SOCSERV')));
			}

			if(empty($_GET['proccess']))
			{
				$this->includeComponentTemplate('startpage');
				return;
			}

			$this->setFileId($_REQUEST['id']);
			$this->setVersionId($_REQUEST['v']);
			$this->setAction(empty($_REQUEST['action'])? '' : $_REQUEST['action']);

			if($this->isPublicNewFile())
			{
			}
			elseif($this->isSaveNewFile())
			{
			}
			else
			{
				$wdElement = array();
				if(!empty($_REQUEST['elementId']))
				{
					$wdElement = array('elementId' => (int)$_REQUEST['elementId']);
				}
				$this->checkPermission($wdElement);
				$lockInfo = $this->checkLock();

				if($lockInfo)
				{
					$this->sendJsonResponse(array('error' => GetMessage('WD_DOC_ATTEMPT_EDIT_LOCK_DOCUMENT')));
				}
			}

			$this->checkActiveSocServ();
			$oAuthUrl = CUtil::JSEscape($this->getOAuthUrlBySocServ());
			$accessToken = $this->getAccessTokenBySocServ();
			$this
				->setAccessToken($accessToken)
			;
			$this
				->initDocHandler()
			;
			$this
				->getDocHandler()
					->setAccessToken($this->getAccessToken())
			;

			if(empty($accessToken))
			{
				$this->sendJsonResponse(array(
					'authUrl' => $oAuthUrl,
				));
			}
			else
			{
				//todo hack. SocServ set backurl!
				if(strpos($_SERVER['HTTP_REFERER'], 'tools/oauth'))
				{
					$curPath = CHTTP::urlDeleteParams($_SERVER['REQUEST_URI'], array("proccess", "sessid",));
					$curPath = CHTTP::urlAddParams($curPath, array('sessid' => bitrix_sessid()));
					//restart
					LocalRedirect($curPath);
				}

				if($this->isPublicNewFile())
				{
					$response = $this->publicBlankFile(new CWebDavBlankDocument($this->arParams['createType']));
					//todo bad hack. bad hack
					if($this->getDocHandler()->isRequiredAuthorization())
					{
						$this->sendJsonResponse(array(
							'authUrl' => $oAuthUrl,
						));
					}
					$this->sendJsonResponse($response);
				}
				elseif($this->isSaveNewFile())
				{
					$response = $this->saveNewFile(array(
						'isDropped' => true,
						'createType' => $this->arParams['createType'],
//						'iblockId' => '',
//						'sectionId' => '',
					));
					$this->removeFile();
					$this->sendJsonResponse($response);
				}
				elseif($this->isRenameFile())
				{
					$response = $this->renameFile(array(
						'newName' => $_REQUEST['newName'],
						'elementId' => (int)$_REQUEST['elementId'],
						'sectionId' => (int)$_REQUEST['sectionId'],
					));
					$this->sendJsonResponse($response);
				}
				elseif(!empty($_REQUEST['commit']) && $this->getFileId())
				{
					$deletedSession = $this->deleteSession(true);
					$response = $this->commitFile();
					if(is_array($deletedSession) && $this->isLastSession())
					{
						$this->removeFile($deletedSession);
					}

					$this->sendJsonResponse($response);
				}
				elseif(!empty($_REQUEST['discard']) && $this->getFileId())
				{
					$deletedSession = $this->deleteSession(true);
					if(is_array($deletedSession) && $this->isLastSession())
					{
						$this->removeFile($deletedSession);
						$this->sendJsonResponse(array('status' => 'success'));
					}
					$this->sendJsonResponse(array('status' => 'error'));
				}
				else //publicDoc
				{
					$response = $this->publicFile();
					//todo bad hack. bad hack
					if($this->getDocHandler()->isRequiredAuthorization())
					{
						$this->sendJsonResponse(array(
							'authUrl' => $oAuthUrl,
						));
					}
					$this->sendJsonResponse($response);
				}
			}

			return;
		}
		catch(Exception $e)
		{
			//$this->sendJsonResponse(array('error' => $e->getMessage()));
		}

		return;
	}

	/**
	 * Now, we believe that the editing of versions not go together.
	 * @return bool
	 */
	protected function isExclusiveEdit()
	{
		return (bool)$this->getVersionId() || $this->isCreationFile();
	}


	/**
	 * @return bool|array
	 */
	protected function checkLock()
	{
		//only by element
		if(!empty($this->getWebdav()->arParams['element_array']))
		{
			return $this
				->getWebdav()
				->checkLock('', $this->getWebdav()->arParams['element_array']);
		}
		return false;
	}

	protected function checkPermission(array $wdElement = array())
	{
		if(!$wdElement)
		{
			$wdElement = $this->getWebdav()->GetObject(array('check_permissions' => false), false);
		}
		else
		{
			$wdElement = $this->getWebdav()->GetObject(array('check_permissions' => false, 'element_id' => $wdElement['elementId']), false);
		}

		if(!$wdElement || !$this->getWebdav()->CheckWebRights('',
			array('action' => 'edit', 'arElement' => $wdElement), false))
		{
			ShowError(GetMessage("WD_ACCESS_DENIED"));
			die;
		}
		//webdav-webdav=0.
		$this->getWebdav()->arParams = $wdElement;
	}

	/**
	 * @return CAllUser
	 */
	protected static function getUser()
	{
		global $USER;

		return $USER;
	}

	/**
	 * @return CAllMain
	 */
	protected static function getApplication()
	{
		global $APPLICATION;

		return $APPLICATION;
	}

	protected function checkSessid()
	{
		if(!check_bitrix_sessid())
		{
			$this->getApplication()->RestartBuffer();
			ShowError(GetMessage("WD_ACCESS_DENIED"));
			die;
		}
	}

	public function sendJsonResponse($response, $httpStatusCode = null)
	{
		CWebDavTools::sendJsonResponse($response, $httpStatusCode);
	}

	/**
	 * @return string
	 */
	abstract protected function getServiceName();

	protected function getLastException()
	{
		/** @var CAllMain */
		global $APPLICATION;

		$exception = $APPLICATION->GetException();
		if($exception instanceof CApplicationException)
		{
			return array(
				'code' => $exception->getId(),
			);
		}

		return false;
	}
}