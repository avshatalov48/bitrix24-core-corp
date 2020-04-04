<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CWebDavDocEditLocalComponent extends CBitrixComponent
{
	const STATUS_SUCCESS   = 'success';
	const STATUS_DENIED    = 'denied';
	const STATUS_ERROR     = 'error';
	const STATUS_NOT_FOUND = 'not_found';

	private $action;

	/**
	 * @param string $action
	 */
	protected function setAction($action)
	{
		$this->action = $action;
	}

	/**
	 * @return string
	 */
	protected function getAction()
	{
		return $this->action;
	}

	/**
	 * @return CWebDavIblock
	 */
	protected function getWebdav()
	{
		//todo throw exception
		return $this->arParams['webdav'];
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->setAction($arParams['action']);

		return parent::onPrepareComponentParams($arParams);
	}

	public function sendJsonResponse($response)
	{
		CWebDavTools::sendJsonResponse($response);
	}

	protected function runAction()
	{
		$action = strtolower($this->getAction());
		switch($action)
		{
			case 'start':
			case 'commit':
				$actionName = 'processAction' . $action;
				$this->$actionName();
				break;
		}
	}

	protected function processActionStart()
	{
		if(!$this->checkPermission())
		{
			$this->sendJsonResponse(array('status' => self::STATUS_DENIED));
		}
		if(empty($this->getWebdav()->arParams['element_array']['ID']))
		{
			$this->sendJsonResponse(array('status' => self::STATUS_NOT_FOUND));
		}
		$this
			->getWebdav()
			->SendHistoryFile($this->getWebdav()->arParams['element_array']['ID'], 0);

	}

	protected function processActionCommit()
	{
		if(!$this->checkPermission())
		{
			$this->sendJsonResponse(array('status' => self::STATUS_DENIED));
		}
		if(!isset($_FILES['file']))
		{
			$this->sendJsonResponse(array('status' => self::STATUS_DENIED, 'message' => 'Upload file'));
		}
		$downloadedFile = $_FILES['file'];
		$webdav = $this->getWebdav();
		if(empty($webdav->arParams['element_array']['ID']))
		{
			$this->sendJsonResponse(array('status' => self::STATUS_NOT_FOUND));
		}
		$filename = CTempFile::GetFileName(uniqid('_wd'));
		$nameToSavedFile = $webdav->arParams['element_name'];
		CheckDirPath($filename);

		if (($downloadedFile['error'] = intval($downloadedFile['error'])) > 0)
		{
			if ($downloadedFile['error'] < 3)
			{
				$this->sendJsonResponse(array('status' => self::STATUS_ERROR, 'message' => "Upload error: {$downloadedFile['error']}"));
			}
			else
			{
				$this->sendJsonResponse(array('status' => self::STATUS_ERROR, 'message' => "Upload error: {$downloadedFile['error']}"));
			}
		}
		if(!is_uploaded_file($downloadedFile['tmp_name']))
		{
			$this->sendJsonResponse(array('status' => self::STATUS_ERROR, 'message' => "Upload error"));
		}

		if(!move_uploaded_file($downloadedFile['tmp_name'], $filename))
		{
			$this->sendJsonResponse(array('status' => self::STATUS_ERROR, 'message' => "Bad move after upload"));
		}

		$options = array(
			'new' => false,
			'FILE_NAME' => $nameToSavedFile,
			'ELEMENT_ID' => $webdav->arParams['element_array']['ID'],
			'arUserGroups' => $webdav->USER['GROUPS'],
			'TMP_FILE' => $filename,
		);

		$this->getDb()->startTransaction();
		if (!$this->getWebDav()->put_commit($options))
		{
			$this->getDb()->rollback();

			$this->sendJsonResponse(array('status' => self::STATUS_ERROR, 'message' => 'Error in commit.', 'description' => $webdav->LAST_ERROR));
		}
		$this->getDb()->commit();

		$this->sendJsonResponse(array('status' => self::STATUS_SUCCESS));
	}

	protected function checkPermission(array $wdElement = array())
	{
		if(!$wdElement)
		{
			$wdElement = $this->getWebdav()->getObject(array('check_permissions' => false), false);
		}
		else
		{
			$wdElement = $this->getWebdav()->getObject(array('check_permissions' => false, 'element_id' => $wdElement['elementId']), false);
		}

		if(!$wdElement || !$this->getWebdav()->CheckWebRights('',
			array('action' => 'edit', 'arElement' => $wdElement), false))
		{
			return false;
		}
		//webdav-webdav=0.
		$this->getWebdav()->arParams = $wdElement;

		return true;
	}

	/**
	 * @return CDatabase
	 */
	protected function getDb()
	{
		global $DB;

		return $DB;
	}

	public function executeComponent()
	{
		$this->runAction();
	}
}