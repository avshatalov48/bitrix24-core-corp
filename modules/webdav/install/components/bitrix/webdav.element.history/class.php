<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

//error_reporting(E_ALL);
class CWebDavElementHistoryComponent extends CBitrixComponent
{
	/**
	 * Fetch from DB
	 */
	const COUNT_HISTORY_ELEMENT_ON_PAGE = 4;

	/**
	 * @throws Exception
	 * @return CWebDavIblock
	 */
	public function getWebdav()
	{
		//todo throw exception
		if(!($this->arParams['webdav'] instanceof CWebDavIblock))
		{
			throw new Exception('Set arParams webdav');
		}
		return $this->arParams['webdav'];
	}

	public function isAjax()
	{
		return !empty($this->arParams['ajax']);
	}

	public function isDownloadOriginal()
	{
		return !empty($_GET['original']) && !empty($_GET['from']);
	}

	public function isDownloadVersion()
	{
		return isset($this->arParams['versionId']) && ($this->arParams['versionId'] >= 0);
	}

	public function isDownloadFileVersion()
	{
		return isset($this->arParams['fileId']) && ($this->arParams['fileId'] > 0) && empty($this->arParams['versionId']);
	}

	public function isDownloadLastVersion()
	{
		return !empty($_GET['lastVersion']);
	}

	protected function checkPermission($action = 'read')
	{
		$options = array(
			'check_permissions' => false,
		);
		if(!empty($this->arParams['elementId']))
		{
			$options['element_id'] = $this->arParams['elementId'];
		}
		$wdElement = $this->getWebdav()->GetObject($options, false);

		if(!$this->getWebdav()->CheckWebRights('',
			array('action' => $action, 'arElement' => $wdElement), false))
		{
			ShowError(GetMessage("WD_ACCESS_DENIED"));
			die;
		}
		//webdav-webdav=0.
		$this->getWebdav()->arParams = $wdElement;
	}

	protected function getApplication()
	{
		/** @var $APPLICATION CMain */
		global $APPLICATION;
		return $APPLICATION;
	}

	protected function getUriAjaxRequest()
	{
		return $this->getApplication()->GetCurPageParam("", array('history', 'from'));
	}

	protected function getUriDownloadOriginal()
	{
		return $this->getApplication()->GetCurPageParam('original=1');
	}

	protected function getUriDownloadLasVersion()
	{
		return $this->getApplication()->GetCurPageParam('lastVersion=1', array('from'));
	}

	public function onPrepareComponentParams($arParams)
	{
		$arParams['MODIFIED_FROM'] = !empty($_GET['from'])? intval($_GET['from']) : false;

		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		if(!($this->arParams['webdav'] instanceof CWebDavIblock))
		{
			ShowError('Invalid webdav property.');
			return;
		}
		CUtil::JSPostUnescape();
		$this->checkPermission();
		if($this->getWebdav()->workflow != 'bizproc' && $this->getWebdav()->workflow != 'bizproc_limited')
		{
			return;
		}
		if(!CModule::IncludeModule('bizproc'))
		{
			return;
		}
		$entityType = $this->getEntityType();
		list($entityId, $documentUrl, $documentId) = $this->getEntityIdDocumentData($entityType);

		if($this->isDownloadOriginal())
		{
			$filter  = array(
				"DOCUMENT_ID" => $documentId,
			);
			if ($this->arParams['MODIFIED_FROM'])
			{
				$filter['>=MODIFIED'] = ConvertTimeStamp($this->arParams['MODIFIED_FROM'], 'FULL');
			}

			$originalHistory = array();
			//if exist history document with date modified > than post create, then get first from this list.
			if($this->getCountHistoryElementByDocument($filter) > 0)
			{
				$originalHistory = $this->getOriginalHistoryDocument(array(
					'id' => $documentId,
					'url' => $documentUrl,
					'entity' => $entityType,
					'entityId' => $entityId,
				), $filter);
			}
			else
			{

			}

			if(!$originalHistory)
			{
				$this
					->getWebdav()
					->SendHistoryFile($this->getWebdav()->arParams['element_array']['ID'], 0);
			}
			else
			{
				$this
					->getWebdav()
					->SendHistoryFile($this->getWebdav()->arParams['element_array']['ID'], $originalHistory['ID']);
			}

		}
		elseif($this->isDownloadFileVersion())
		{
			$document = array('ID' => 0);
			if($this->arParams['fileId'] != $this->getWebdav()->arParams['file_array']['ID'])
			{
				$document = $this
					->getWebdav()
					->findHistoryDocumentByFileId($this->getWebdav()->arParams['element_array']['ID'], $this->arParams['fileId'], $documentId);
			}
			$this
				->getWebdav()
				->SendHistoryFile($this->getWebdav()->arParams['element_array']['ID'], $document['ID']);
		}
		elseif($this->isDownloadVersion())
		{
			$this
				->getWebdav()
				->SendHistoryFile($this->getWebdav()->arParams['element_array']['ID'], $this->arParams['versionId']);
		}
		elseif($this->isDownloadLastVersion())
		{
				$this
					->getWebdav()
					->SendHistoryFile($this->getWebdav()->arParams['element_array']['ID'], 0);
		}
		elseif($this->isAjax())
		{
			$this->checkPermission('edit');
			$filter  = array(
				"DOCUMENT_ID" => $documentId,
			);
			if ($this->arParams['MODIFIED_FROM'])
			{
				$filter['>=MODIFIED'] = ConvertTimeStamp($this->arParams['MODIFIED_FROM'], 'FULL');
			}
			$document = array(
				'id' => $documentId,
				'url' => $documentUrl,
				'entity' => $entityType,
				'entityId' => $entityId,
			);
			$history = $this->getHistoryByDocument($document, $filter);
			$history = $this->runCorrectionDateHistoryByDocument($history, count($history) < (static::COUNT_HISTORY_ELEMENT_ON_PAGE+1), $this->getWebdav()->arParams['element_array']["DATE_CREATE"]);
			$history = $this->cleanHistoryList($history);

			$this->arResult['count_history_items'] = $this->getCountHistoryElementByDocument($filter);
			$this->arResult['webdav'] = $this->getWebdav();
			$this->arResult['creator'] = CUser::GetByID($this->getWebdav()->arParams['element_array']['CREATED_BY'])->fetch();
			$this->arResult['creator_name'] = CUser::FormatName(CSite::GetNameFormat(false), $this->arResult['creator'], true, false);
			$this->arResult['modifier'] = CUser::GetByID($this->getWebdav()->arParams['element_array']['MODIFIED_BY'])->fetch();
			$this->arResult['modifier_name'] = CUser::FormatName(CSite::GetNameFormat(false), $this->arResult['creator'], true, false);
			$this->arResult['date_modify'] = FormatDate('x', MakeTimeStamp($this->getWebdav()->arParams['element_array']["TIMESTAMP_X"]));
			$this->arResult['date_create'] = FormatDate('x', MakeTimeStamp($this->getWebdav()->arParams['element_array']["DATE_CREATE"]));
			$this->arResult['uri_download_original'] = $this->getUriDownloadOriginal();
			if(count($history) != (static::COUNT_HISTORY_ELEMENT_ON_PAGE + 1))
			{
				$page = $history[0]['DETAIL_PAGE_URL'];
				//append original in history list
				 array_unshift($history, array(
					'URL_DOWNLOAD' => $this->getUriDownloadLasVersion(),
					'HISTORY_PAGE_URL' => '',
					'DETAIL_PAGE_URL' => $page,
					'ID' => false,
					'MODIFIED' => $this->getWebdav()->arParams['element_array']["TIMESTAMP_X"],
					'DOCUMENT_ID' => 'DOCUMENT_ID',
					'NAME' => 'NAME',
					'USER_ID' => $this->arResult['modifier']['ID'],
					'USER_NAME' => $this->arResult['modifier']['NAME'],
					'USER_LAST_NAME' => $this->arResult['modifier']['LAST_NAME'],
					'USER_SECOND_NAME' => $this->arResult['modifier']['SECOND_NAME'],
					'USER_LOGIN' => $this->arResult['modifier']['LOGIN'],
					'FILE_SIZE' => CFile::FormatSize(intval($this->getWebdav()->arParams["file_size"])),
				));
			}
			$this->arResult['history'] = $history;

			$this->arResult['editService'] = CWebDavLogOnlineEdit::getOnlineService(array(
				'IBLOCK_ID' => $this->getWebdav()->arParams['element_array']['IBLOCK_ID'],
				'SECTION_ID' => $this->getWebdav()->arParams['element_array']['IBLOCK_SECTION_ID'],
				'ELEMENT_ID' => $this->getWebdav()->arParams['element_array']['ID'],
			));
			$this->arResult['editUsers'] = $this->getOnlineUsers();

			if(!empty($filter['>=MODIFIED']))
			{
				$filter['<=MODIFIED'] = $filter['>=MODIFIED'];
				unset($filter['>=MODIFIED']);
			}

			$this->includeComponentTemplate('ajax');

			return;
		}

		return;
	}

	protected function getOnlineUsers()
	{
		$users = CWebDavLogOnlineEdit::getOnlineUsers(array(
				'IBLOCK_ID' => $this->getWebdav()->arParams['element_array']['IBLOCK_ID'],
				'SECTION_ID' => $this->getWebdav()->arParams['element_array']['IBLOCK_SECTION_ID'],
				'ELEMENT_ID' => $this->getWebdav()->arParams['element_array']['ID'],
		));
		$uniqueList = array();
		foreach ($users as $k => $user)
		{
			if(empty($user['USER_ID']))
			{
				continue;
			}
			if(isset($uniqueList[$user['USER_ID']]))
			{
				unset($users[$k]);
				continue;
			}
			$uniqueList[$user['USER_ID']] = true;
		}
		unset($user);

		return $users;
	}

	public static function getUserGender($gender)
	{
		return CWebDavTools::getUserGender($gender);
	}

	/**
	 * Get numeric case for lang messages
	 * @param $number
	 * @param $once
	 * @param $multi21
	 * @param $multi2_4
	 * @param $multi5_20
	 * @return string
	 */
	public static function getNumericCase($number, $once, $multi21, $multi2_4, $multi5_20)
	{
		return CWebDavTools::getNumericCase($number, $once, $multi21, $multi2_4, $multi5_20);
	}


	/**
	 * @return string
	 */
	protected function getEntityType()
	{
		$entityType = explode('_', $this->getWebdav()->arParams['element_array']['IBLOCK_CODE']);
		$entityType = strtolower(array_shift($entityType));

		return $entityType;
	}

	protected function getOriginalHistoryDocument(array $document, array $filter)
	{
		$documentId = $document['id'];
		$documentUrl = $document['url'];

		$by      = "modified";
		$order   = "asc";
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
			if (isset($res["DOCUMENT"]["PROPERTIES"]['WEBDAV_SIZE']['VALUE']))
			{
				$res['FILE_SIZE'] = CFile::FormatSize($res['DOCUMENT']['PROPERTIES']['WEBDAV_SIZE']['VALUE']);
			}
			$replace = array(
				'#ELEMENT_ID#' => $res['DOCUMENT']['FIELDS']['ID'],
				'#ELEMENT_NAME#' => urlencode($res['NAME']),
				'#ID#' => $res['ID'],
			);
			$res['URL_DOWNLOAD'] = str_replace(array_keys($replace), array_values($replace), $documentUrl);

			return $res;
		}
		else
		{
			//if not exists second entry, then original is same webdav element.
		}

		return array();
	}

	/**
	 * @param $document
	 * @param $filter
	 * @return array
	 */
	protected function getHistoryByDocument(array $document, array $filter)
	{
		$documentId = $document['id'];
		$documentUrl = $document['url'];

		$by      = "modified";
		$order   = "desc";
		$history = new CBPHistoryService();

		//we get static::COUNT_HISTORY_ELEMENT_ON_PAGE + 1. Modified date of changes store in prev. file.
		$dbDocumentHistory = $history->GetHistoryList(
			array(strtoupper($by) => strtoupper($order)),
			$filter,
			false,
			array('nTopCount' => (static::COUNT_HISTORY_ELEMENT_ON_PAGE + 1)),
			array(
				"ID",
				"DOCUMENT_ID",
				"NAME",
				"MODIFIED",
				"USER_ID",
				"USER_NAME",
				"USER_LAST_NAME",
				"USER_SECOND_NAME",
				"USER_LOGIN",
				"DOCUMENT",
			)
		);

		$historyList = array();
		while ($res = $dbDocumentHistory->fetch())
		{
			if (isset($res["DOCUMENT"]["PROPERTIES"]['WEBDAV_SIZE']['VALUE']))
			{
				$res['FILE_SIZE'] = CFile::FormatSize($res['DOCUMENT']['PROPERTIES']['WEBDAV_SIZE']['VALUE']);
			}
			$res["USER"] = CUser::FormatName(CSite::GetNameFormat(false), array(
				"NAME" => $res["USER_NAME"],
				"LAST_NAME" => $res["USER_LAST_NAME"],
				"SECOND_NAME" => $res["USER_SECOND_NAME"],
				"LOGIN" => $res["USER_LOGIN"]
			), true);

			$replace = array(
				'#ELEMENT_ID#' => $res['DOCUMENT']['FIELDS']['ID'],
				'#ELEMENT_NAME#' => urlencode($res['NAME']),
				'#ID#' => $res['ID'],
			);
			$res['URL_DOWNLOAD'] = str_replace(array_keys($replace), array_values($replace), $documentUrl);
			$res['DETAIL_PAGE_URL'] = str_replace(array('#USER_ID#', '#GROUP_ID#', '#ID#'), $document['entityId'] ,$res['DOCUMENT']['FIELDS']['DETAIL_PAGE_URL']);
			$res['HISTORY_PAGE_URL'] = $res['DETAIL_PAGE_URL'] . '?webdavForm' . $res['DOCUMENT']['FIELDS']['IBLOCK_ID'] . '_active_tab=tab_history&select=' . $res['ID'];

			$historyList[] = $res;
		}

		return $historyList;
	}

	/**
	 * Clean fields
	 */
	protected function cleanHistoryList(array $historyList)
	{
		$saveFields = array(
			'URL_DOWNLOAD' => 'URL_DOWNLOAD',
			'HISTORY_PAGE_URL' => 'HISTORY_PAGE_URL',
			'DETAIL_PAGE_URL' => 'DETAIL_PAGE_URL',
			'ID' => 'ID',
			'MODIFIED' => 'MODIFIED',
			'DOCUMENT_ID' => 'DOCUMENT_ID',
			'NAME' => 'NAME',
			'USER_ID' => 'USER_ID',
			'USER_NAME' => 'USER_NAME',
			'USER_LAST_NAME' => 'USER_LAST_NAME',
			'USER_SECOND_NAME' => 'USER_SECOND_NAME',
			'USER_LOGIN' => 'USER_LOGIN',
			'FILE_SIZE' => 'FILE_SIZE',
		);
		foreach ($historyList as &$document)
		{
			$document = array_intersect_key($document, $saveFields);
		}
		unset($document);

		return $historyList;
	}

	protected function runCorrectionDateHistoryByDocument(array $historyList, $replaceFirstDate = false, $firstDate = null)
	{
		$dateModified = false;
		$historyList = array_reverse($historyList);
		foreach ($historyList as $k => &$doc)
		{
			if(!$dateModified)
			{
				$dateModified = $doc['MODIFIED'];
				if($replaceFirstDate)
				{
					$doc['MODIFIED'] = $firstDate;
				}
				else
				{
					unset($historyList[$k]);
				}
				continue;
			}
			$tmpDate = $doc['MODIFIED'];
			$doc['MODIFIED'] = $dateModified; //set previous date.

			$dateModified = $tmpDate;
		}
		unset($doc);

		$historyList = array_reverse($historyList);

		return $historyList;
	}

	/**
	 * @param array $filter
	 * @return integer
	 */
	protected function getCountHistoryElementByDocument(array $filter)
	{
		$history = new CBPHistoryService();

		return $history->GetHistoryList(array(), $filter, array());
	}

	/**
	 * @param $entityType
	 * @return array
	 */
	protected function getEntityIdDocumentData($entityType)
	{
		if ($entityType == 'group')
		{
			$entityId        = 0;
			$dbSocNetSection = CIBlockSection::GetList(array(), array('ID' => $this->getWebdav()->arParams['element_array']['IBLOCK_SECTION_ID']));
			if ($dbSocNetSection && $arSocNetSection = $dbSocNetSection->Fetch())
			{
				$entityId = $arSocNetSection['SOCNET_GROUP_ID'];
			}
			$documentUrl = "/workgroups/group/{$entityId}/files/bizproc/historyget/#ELEMENT_ID#/#ID#/#ELEMENT_NAME#";
			$documentId  = array(
				'webdav',
				'CIBlockDocumentWebdavSocnet',
				$this->getWebdav()->arParams['element_array']['ID']
			);

			return array($entityId, $documentUrl, $documentId);
		}
		elseif ($entityType == 'shared')
		{
			$documentUrl = "/docs/shared/webdav_bizproc_history_get/#ELEMENT_ID#/#ID#/";
			$documentId  = array(
				'webdav',
				'CIBlockDocumentWebdav',
				$this->getWebdav()->arParams['element_array']['ID']
			);

			return array(0, $documentUrl, $documentId);
		}
		else
		{
			$entityId    = $this->getWebdav()->arParams['element_array']['CREATED_BY'];
			$documentUrl = "/company/personal/user/{$entityId}/files/bizproc/historyget/#ELEMENT_ID#/#ID#/#ELEMENT_NAME#";
			$documentId  = array(
				'webdav',
				'CIBlockDocumentWebdavSocnet',
				$this->getWebdav()->arParams['element_array']['ID']
			);

			return array($entityId, $documentUrl, $documentId);
		}
	}
}