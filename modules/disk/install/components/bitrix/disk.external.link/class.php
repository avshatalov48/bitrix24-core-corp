<?php

use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Configuration;
use Bitrix\Disk\Document\DocumentEditorUser;
use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\Document\FileData;
use Bitrix\Disk\Document\GoogleViewerHandler;
use Bitrix\Disk\Document\OnlyOffice;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\DiskComponent;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Ui\FileAttributes;
use Bitrix\Disk\Ui\Icon;
use Bitrix\Disk\Ui;
use Bitrix\Disk\ZipNginx;
use Bitrix\Main\Config\Option;
use Bitrix\Disk\Internals\Grid;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Web\Uri;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!\Bitrix\Main\Loader::includeModule('disk'))
{
	return false;
}

Loc::loadMessages(__FILE__);

class CDiskExternalLinkComponent extends DiskComponent
{
	const PAGE_SIZE = 25;
	const MAX_SIZE_TO_PREVIEW = 15728640; //1024 * 1024 * 15 bytes

	/** @var \Bitrix\Disk\ExternalLink */
	protected $externalLink;
	/** @var string */
	protected $hash;
	/** @var string */
	protected $downloadToken;
	/** @var DocumentHandler  */
	protected $defaultHandlerForView;
	protected $langId;

	/**
	 * Common operations before run action.
	 * @param string $actionName Action name which will be run.
	 * @return bool If method will return false, then action will not execute.
	 */
	protected function processBeforeAction($actionName)
	{
		$this->defaultHandlerForView = Driver::getInstance()->getDocumentHandlersManager()->getDefaultHandlerForView();
		$this->findLink();

		if(
			($this->externalLink->isAutomatic() && !Configuration::isEnabledAutoExternalLink()) ||
			(!$this->externalLink->isAutomatic() && !Configuration::isEnabledManualExternalLink())
		)
		{
			$this->arResult = array(
				'ERROR_MESSAGE' => $this->getMessage('DISK_EXTERNAL_LINK_ERROR_DISABLED_MODE'),
			);
			$this->includeComponentTemplate('error');
			return false;
		}

		if (in_array($actionName, ['default', 'goToEdit'], true))
		{
			$this->downloadToken = Random::getString(12);
			$this->storeDownloadToken($this->downloadToken);
		}
		else
		{
			//so it means we work with action with file: download, show. We have to check download token.
			if (
				!$this->externalLink->isImage() &&
				!$this->externalLink->isAutomatic() &&
				!$this->checkDownloadToken($this->request->getQuery('token'))
			)
			{
				$link = \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlExternalLink([
					'hash' => $this->externalLink->getHash(),
					'action' => 'default',
					'session' => 'expired',
				]);

				$redirect = new \Bitrix\Main\Engine\Response\Redirect($link);
				\Bitrix\Main\Application::getInstance()->end(0, $redirect);
			}

			if ($this->externalLink->hasPassword() && !$this->checkPassword())
			{
				$this->showAccessDenied();

				return false;
			}
		}

		if ($this->externalLink->getObject()->isDeleted())
		{
			$this->showNotFoundPage();

			return false;
		}

		return true;
	}

	protected function listActions()
	{
		return [
			'goToEdit',
			'download',
			'downloadFolderArchive',
			'downloadFileUnderFolder',
			'showByGoogleViewer' => [
				'method' => ['GET', 'POST'],
			],
			'showByOnlyOfficeViewer' => [
				'method' => ['GET', 'POST'],
			],
			'showViewHtml',
			'showFile',
			'showPreview',
			'showView',
		];
	}

	protected function runProcessingExceptionComponent(Exception $e)
	{
		if ($e instanceof \Bitrix\Main\ObjectNotFoundException)
		{
			$this->showNotFoundPage();
		}
		else
		{
			$this->includeComponentTemplate('error');
		}
	}

	protected function prepareParams()
	{
		$hash = $this->request->get('hash');
		if(!$hash)
		{
			throw new \Bitrix\Main\ArgumentException('Empty hash');
		}

		if(!\Bitrix\Disk\ExternalLink::isValidValueForField('HASH', $hash, $this->errorCollection))
		{
			throw new \Bitrix\Main\ArgumentException('Hash contains invalid character');
		}

		$this->hash = $hash;
		$this->langId = $this->request->get('langId')?: LANGUAGE_ID;

		return $this;
	}

	private function isViewableDocument(string $ext): bool
	{
		return DocumentHandler::isEditable($ext) || (mb_strtolower($ext) === 'pdf');
	}

	private function storeDownloadToken($token)
	{
		$_SESSION['DISK_PUBLIC_VERIFICATION'][$this->externalLink->getObject()->getId()] = $token;
	}

	private function checkDownloadToken($token)
	{
		if($token === null)
		{
			return false;
		}
		return $_SESSION['DISK_PUBLIC_VERIFICATION'][$this->externalLink->getObject()->getId()] === $token;
	}

	protected function processActionGoToEdit()
	{
		$file = $this->externalLink->getFile();
		if(!$file || !$this->externalLink->allowEdit())
		{
			$this->showNotFoundPage();

			return false;
		}

		$isDocument = $this->isViewableDocument($this->externalLink->getFile()->getExtension());
		if ($this->defaultHandlerForView instanceof OnlyOffice\OnlyOfficeHandler && $isDocument)
		{
			$documentSession = $this->generateDocumentSession($this->externalLink->getFile());
			if ($documentSession->canTransformUserToEdit(CurrentUser::get()))
			{
				$fieldsToCreateUser = [
					'NAME' => OnlyOffice\Models\GuestUser::create()->getName(),
				];

				if (DocumentEditorUser::login($fieldsToCreateUser))
				{
					$documentSession->setUserId(CurrentUser::get()->getId());
				}

				$createdSession = $documentSession->createEditSession();
				if ($createdSession->getId() != $documentSession->getId())
				{
					$documentSession->delete();
				}

				$documentSession = $createdSession;
				$this->arResult['DOCUMENT_SESSION'] = $documentSession;
				$this->arResult['LINK_TO_DOWNLOAD'] = $this->getDownloadUrl();
			}

			$this->includeComponentTemplate('onlyoffice');
		}
		else
		{
			$this->showNotFoundPage();
		}
	}

	protected function processActionDefault($path = '/')
	{
		$validPassword = true;
		if($this->externalLink->hasPassword())
		{
			$validPassword = $this->checkPassword();
		}
		if(!$validPassword && !$this->request->isPost())
		{
			$validPassword = null;
		}

		$isFolder = $this->externalLink->getObject() instanceof Folder;
		$isFile = !$isFolder;

		$server = \Bitrix\Main\Application::getInstance()->getContext()->getServer();
		$this->arResult = array(
			'PROTECTED_BY_PASSWORD' => $this->externalLink->hasPassword(),
			'VALID_PASSWORD' => $validPassword,
			'SESSION_EXPIRED' => $this->request->getQuery('session') === 'expired',
			'SITE_NAME' => Option::get('main', 'site_name', $server->getServerName()),
		);

		if ($isFile)
		{
			$passwordPassed = !$this->arResult['PROTECTED_BY_PASSWORD'] || $this->arResult['VALID_PASSWORD'];
			$isDocument = $this->isViewableDocument($this->externalLink->getFile()->getExtension());
			if ($this->defaultHandlerForView instanceof OnlyOffice\OnlyOfficeHandler && $passwordPassed && $isDocument)
			{
				$this->arResult['DOCUMENT_SESSION'] = $this->generateDocumentSession($this->externalLink->getFile());

				$linkToEdit = Driver::getInstance()->getUrlManager()->getUrlExternalLink(
					[
						'hash' => $this->externalLink->getHash(),
						'action' => 'goToEdit',
					]
				);
				$this->arResult['LINK_TO_EDIT'] = $linkToEdit;
				$this->arResult['LINK_TO_DOWNLOAD'] = $this->getDownloadUrl();
				$this->includeComponentTemplate('onlyoffice');

				return;
			}

			$this->arResult['FILE'] = $this->getResultByFile();
		}

		if($isFolder)
		{
			$rootFolder = $this->externalLink->getFolder();
			[$targetFolder, $relativeItems] = $this->getTargetFolderData($rootFolder, $path);
			if(!$targetFolder)
			{
				throw new \Bitrix\Main\SystemException('Wrong path');
			}

			$this->arResult['FOLDER'] = $this->getResultByFolder();
			$this->arResult['ENABLED_MOD_ZIP'] = \Bitrix\Disk\ZipNginx\Configuration::isEnabled();
			$this->arResult['VIEWER_CODE'] = Configuration::getDefaultViewerServiceCode();
			$this->arResult['DISABLE_DOCUMENT_VIEWER'] = !Configuration::canCreateFileByCloud();
			if ($this->arResult['VIEWER_CODE'] != GoogleViewerHandler::getCode())
			{
				$this->arResult['DISABLE_DOCUMENT_VIEWER'] = true;
			}

			$this->arResult['GRID'] = $this->getGridData($rootFolder, $targetFolder, $path, 'external_folder');
			$this->arResult['BREADCRUMBS'] = $this->getBreadcrumbs($path, $relativeItems);
			$this->arResult['BREADCRUMBS_ROOT'] = array(
				'NAME' => $rootFolder->getName(),
				'LINK' => $this->getUrlManager()->getUrlExternalLink(array(
					'hash' => $this->externalLink->getHash(),
					'action' => 'default',
				), true),
				'ID' => $this->externalLink->getObjectId(),
			);
		}

		$this->includeComponentTemplate($isFile? 'template' : 'folder');
	}

	private function generateDocumentSession(File $file): ?OnlyOffice\Models\DocumentSession
	{
		$documentSessionContext = new OnlyOffice\Models\DocumentSessionContext($file->getId(), null, $this->externalLink->getId());
		$sessionManager = new OnlyOffice\DocumentSessionManager();
		$sessionManager
			->setUserId($this->getUser()->getId() ?: OnlyOffice\Models\GuestUser::GUEST_USER_ID)
			->setSessionType(OnlyOffice\Models\DocumentSession::TYPE_VIEW)
			->setSessionContext($documentSessionContext)
			->setFile($file)
		;

		return $sessionManager->findOrCreateSession();
	}

	private function getTargetFolderData(Folder $rootFolder, $path)
	{
		$data = Driver::getInstance()->getUrlManager()->resolvePathUnderRootObject($rootFolder->getRealObject(), $path);
		if (!$data)
		{
			return null;
		}

		return array(Folder::loadById($data['OBJECT_ID']), $data['RELATIVE_ITEMS']);
	}

	protected function getBreadcrumbs($path, array $relativeItems)
	{
		$crumbs = array();

		$serverName = (Context::getCurrent()->getRequest()->isHttps()? "https" : "http") . "://" . Context::getCurrent()->getServer()->getHttpHost();
		$uri = new Uri($serverName . $this->request->getRequestUri());
		$parts = explode('/', trim($path, '/'));

		foreach ($relativeItems as $i => $item)
		{
			if (empty($item))
			{
				continue;
			}

			$uri->deleteParams(
				array_merge(
					\Bitrix\Main\HttpRequest::getSystemParameters(),
					array('path')
				)
			);
			$uri->addParams(
				array(
					'path' => implode('/', (array_slice($parts, 0, $i + 1))) ? : '',
				)
			);

			$crumbs[] = array(
				'ID' => $item['ID'],
				'NAME' => $item['NAME'],
				'ENCODED_LINK' => $uri->getLocator(),
			);
		}
		unset($i, $item);

		return $crumbs;
	}

	private function getGridData(Folder $rootFolder, Folder $targetFolder, $path, $gridId)
	{
		$grid = array(
			'ID' => $gridId,
			'MODE' => Grid\FolderListOptions::VIEW_MODE_GRID,
			'SORT_MODE' => Grid\FolderListOptions::SORT_MODE_ORDINARY,
		);

		$driver = Driver::getInstance();
		$storage = $rootFolder->getStorage();
		$securityContext = $storage->getSecurityContext($this->externalLink->getCreatedBy());
		$parameters = array(
			'filter' => array(
				'PARENT_ID' => $targetFolder->getRealObjectId(),
				'DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
			),
		);

		$parameters = $driver->getRightsManager()->addRightsCheck($securityContext, $parameters, array('ID', 'CREATED_BY'));

		$pageSize = self::PAGE_SIZE;
		$pageNumber = (int)$this->request->getQuery('pageNumber');
		if ($pageNumber <= 0)
		{
			$pageNumber = 1;
		}
		$parameters['count_total'] = true;
		$parameters['limit'] = $pageSize + 1; // +1 because we want to know about existence next page
		$parameters['offset'] = $pageSize * ($pageNumber - 1);

		$nowTime = time() + CTimeZone::getOffset();
		$fullFormatWithoutSec = preg_replace('/:s$/', '', CAllDatabase::dateFormatToPHP(CSite::GetDateFormat("FULL")));

		$urlManager = $driver->getUrlManager();
		$rows = array();

		$countObjectsOnPage = 0;
		$needShowNextPagePagination = false;
		$cursor = $rootFolder->getList($parameters);
		foreach ($cursor as $row)
		{
			$countObjectsOnPage++;

			if ($countObjectsOnPage > $pageSize)
			{
				$needShowNextPagePagination = true;
				break;
			}

			$object = BaseObject::buildFromArray($row);
			/** @var File|Folder $object */
			$name = $object->getName();
			$objectId = $object->getId();
			$exportData = array(
				'TYPE' => $object->getType(),
				'NAME' => $name,
				'ID' => $objectId,
			);

			$relativePath = trim($path, '/');

			$isFolder = $object instanceof Folder;
			$isFile = $object instanceof File;
			$actions = $tileActions = $columns = array();

			if ($isFolder)
			{
				$uri = new Uri($this->request->getRequestUri());
				$uri->deleteParams(array_merge(
					\Bitrix\Main\HttpRequest::getSystemParameters(),
					array('path', 'pageNumber')
				));
				$uri->addParams(array(
					'path' => $relativePath . '/' . $name . '/',
				));

				$exportData['OPEN_URL'] = $uri->getPathQuery();
				$actions[] = array(
					"PSEUDO_NAME" => "open",
					"DEFAULT" => true,
					"ICONCLASS" => "show",
					"TEXT" => $this->getMessage('DISK_EXTERNAL_OBJECT_ACT_OPEN'),
					"ONCLICK" => "jsUtils.Redirect(arguments, '" . $exportData['OPEN_URL'] . "')",
				);
			}

			if ($isFile)
			{
				$downloadUrl = $urlManager->getUrlExternalLink(
					array(
						'hash' => $this->externalLink->getHash(),
						'action' => 'downloadFileUnderFolder',
						'token' => $this->downloadToken,
						'path' => $relativePath?: '/',
						'fileId' => $object->getId(),
					)
				);

				$exportData['OPEN_URL'] = $downloadUrl;
				$actions[] = array(
					"PSEUDO_NAME" => "download",
					"DEFAULT" => true,
					"ICONCLASS" => "download",
					"TEXT" => $this->getMessage('DISK_EXTERNAL_OBJECT_ACT_DOWNLOAD'),
					"ONCLICK" => "jsUtils.Redirect(arguments, '" . $downloadUrl . "')",
				);
			}

			$iconClass = Ui\Icon::getIconClassByObject($object, !empty($sharedObjectIds[$objectId]));
			if ($isFolder)
			{
				$nameSpecialChars = htmlspecialcharsbx($name);
				$columnName = "
					<table class=\"bx-disk-object-name\"><tr>
							<td style=\"width: 45px;\"><div data-object-id=\"{$objectId}\" class=\"bx-file-icon-container-small {$iconClass}\"></div></td>
							<td><a class=\"bx-disk-folder-title\" id=\"disk_obj_{$objectId}\" href=\"{$exportData['OPEN_URL']}\">{$nameSpecialChars}</a></td>
					</tr></table>
				";
			}
			else
			{
				$viewUrl = $urlManager->getUrlExternalLink(
					[
						'hash' => $this->externalLink->getHash(),
						'action' => 'showByOnlyOfficeViewer',
						'token' => $this->downloadToken,
						'path' => $relativePath?: '/',
						'fileId' => $object->getId(),
					]
				);

				$attr = Ui\ExternalLinkAttributes::tryBuildByFileId($object->getFileId(), new Uri($exportData['OPEN_URL']))
					->setTitle($object->getName())
					->setDocumentViewUrl($viewUrl)
					->setGroupBy($this->componentId)
				;

				$nameSpecialChars = htmlspecialcharsbx($name);
				$columnName = "
					<table class=\"bx-disk-object-name\"><tr>
						<td style=\"width: 45px;\"><div data-object-id=\"{$objectId}\" class=\"bx-file-icon-container-small {$iconClass}\"></div></td>
						<td><a class=\"bx-disk-folder-title\" id=\"disk_obj_{$objectId}\" href=\"{$exportData['OPEN_URL']}\" {$attr}>{$nameSpecialChars}</a></td>
						<td></td>
					</tr></table>
				";
			}

			$timestampCreate = $object->getCreateTime()->toUserTime()->getTimestamp();
			$timestampUpdate = $object->getUpdateTime()->toUserTime()->getTimestamp();
			$columns = array(
				'CREATE_TIME' => ($nowTime - $timestampCreate > 158400)? formatDate($fullFormatWithoutSec, $timestampCreate, $nowTime) : formatDate('x', $timestampCreate, $nowTime),
				'UPDATE_TIME' => ($nowTime - $timestampCreate > 158400)? formatDate($fullFormatWithoutSec, $timestampUpdate, $nowTime) : formatDate('x', $timestampUpdate, $nowTime),
				'NAME' => $columnName,
				'FORMATTED_SIZE' => $isFolder? '' : CFile::formatSize($object->getSize()),
			);

			$exportData['ICON_CLASS'] = $iconClass;
			$tildaExportData = array();
			foreach ($exportData as $exportName => $exportValue)
			{
				$tildaExportData['~' . $exportName] = $exportValue;
			}

			$rows[] = array(
				'data' => array_merge($exportData, $tildaExportData),
				'columns' => $columns,
				'actions' => $actions,
				'tileActions' => $tileActions,
			);
		}

		$grid['HEADERS'] = array(
			array(
				'id' => 'ID',
				'name' => 'ID',
				'sort' => false,
				'default' => false,
			),
			array(
				'id' => 'NAME',
				'name' => $this->getMessage('DISK_EXTERNAL_OBJECT_COLUMN_NAME'),
				'sort' => false,
				'default' => true,
			),
			array(
				'id' => 'CREATE_TIME',
				'name' => $this->getMessage('DISK_EXTERNAL_OBJECT_COLUMN_CREATE_TIME'),
				'sort' => false,
				'default' => false,
			),
			array(
				'id' => 'UPDATE_TIME',
				'name' => $this->getMessage('DISK_EXTERNAL_OBJECT_COLUMN_UPDATE_TIME'),
				'sort' => false,
				'order' => 'desc',
				'default' => true,
			),
			array(
				'id' => 'FORMATTED_SIZE',
				'name' => $this->getMessage('DISK_EXTERNAL_OBJECT_COLUMN_FORMATTED_SIZE'),
				'sort' => false,
				'default' => true,
			),
		);
		$grid['DATA_FOR_PAGINATION'] = array(
			'ENABLED' => true,
			'SHOW_NEXT_PAGE' => $needShowNextPagePagination,
			'CURRENT_PAGE' => $pageNumber,
		);
		$grid['COLUMN_FOR_SORTING'] = array();
		$grid['ROWS'] = $rows;
		$grid['ROWS_COUNT'] = $cursor->getCount();

		return $grid;
	}

	private function getResultByFolder()
	{
		$rootFolder = $this->externalLink->getFolder();
		if (!$rootFolder)
		{
			return null;
		}

		return array(
			'ID' => $rootFolder->getId(),
			'STORAGE_ID' => $rootFolder->getStorageId(),
			'NAME' => $rootFolder->getName(),
			'CREATED_BY' => $this->externalLink->getCreatedBy(),
			'UPDATE_TIME' => $rootFolder->getUpdateTime(),
			'SIZE' => $rootFolder->getRealObject()->countSizeOfFiles(),
			'DOWNLOAD_URL' => $this->getUrlManager()->getUrlExternalLink(array(
				'fileId' => $this->externalLink->getId(),
				'folderId' => $this->externalLink->getId(),
				'hash' => $this->externalLink->getHash(),
				'action' => 'downloadFolderArchive',
				'token' => $this->downloadToken,
				'path' => '/',
			)),
			'VIEW_URL' => $this->getUrlManager()->getShortUrlExternalLink(array(
				'hash' => $this->externalLink->getHash(),
				'action' => 'default',
			), true),
		);
	}

	private function getDownloadUrl()
	{
		$file = $this->externalLink->getFile();
		if (!$file)
		{
			return null;
		}

		return $this->getUrlManager()->getUrlExternalLink([
			'hash' => $this->externalLink->getHash(),
			'action' => 'download',
			'token' => $this->downloadToken,
		]);
	}

	private function getResultByFile()
	{
		$file = $this->externalLink->getFile();
		if (!$file)
		{
			return null;
		}

		$result = array(
			'ID' => $file->getId(),
			'IS_IMAGE' => TypeFile::isImage($file->getName()),
			'IS_DOCUMENT' => TypeFile::isDocument($file->getName()),
			'ICON_CLASS' => Icon::getIconClassByObject($file),
			'UPDATE_TIME' => $file->getUpdateTime(),
			'NAME' => $file->getName(),
			'SIZE' => $file->getSize(),
			'DOWNLOAD_URL' => $this->getDownloadUrl(),
			'ABSOLUTE_SHOW_FILE_URL' => $this->getUrlManager()->getUrlExternalLink(array(
				'hash' => $this->externalLink->getHash(),
				'action' => 'showFile',
				'token' => $this->downloadToken,
			), true),
			'SHOW_PREVIEW_URL' => $this->getUrlManager()->getUrlExternalLink(array(
				'hash' => $this->externalLink->getHash(),
				'action' => 'showPreview',
				'token' => $this->downloadToken,
			)),
			'SHOW_FILE_URL' => $this->getUrlManager()->getUrlExternalLink(array(
				'hash' => $this->externalLink->getHash(),
				'action' => 'showFile',
				'token' => $this->downloadToken,
			)),
			'VIEW_URL' => $this->getUrlManager()->getShortUrlExternalLink(array(
				'hash' => $this->externalLink->getHash(),
				'action' => 'default',
			), true),
			'VIEW_FULL_URL' => $this->getUrlManager()->getUrlExternalLink(array(
				'hash' => $this->externalLink->getHash(),
				'action' => 'default',
			), true),
		);

		if ($result['IS_IMAGE'])
		{
			$fileData = $file->getFile();
			if ($fileData)
			{
				$result['IMAGE_DIMENSIONS'] = array(
					'WIDTH' => $fileData['WIDTH'],
					'HEIGHT' => $fileData['HEIGHT'],
				);
			}
		}
		elseif ($file->getView()->getData())
		{
			CJSCore::Init('disk');
			$viewUrl = array(
				$this->getUrlManager()->getUrlExternalLink(array(
					'hash' => $this->externalLink->getHash(),
					'action' => 'showView',
					'token' => $this->downloadToken,
					'ts' => $file->getUpdateTime()->getTimestamp(),
					'ncc' => 1,
				), true),
				$this->getUrlManager()->getUrlExternalLink(array(
					'hash' => $this->externalLink->getHash(),
					'action' => 'showFile',
					'token' => $this->downloadToken,
				), true)
			);

			$height = 520;
			$width = 720;
			if ($file->getView() instanceof \Bitrix\Disk\View\Video && !$file->getView()->getPreviewData())
			{
				$height = 400;
				$width = 600;
			}
			$sourceUri = $this->getUrlManager()->getUrlExternalLink(array(
				'hash' => $this->externalLink->getHash(),
				'action' => 'showFile',
				'token' => $this->downloadToken,
			));

			if ($file->getView() instanceof \Bitrix\Disk\View\Document)
			{
				$attributes = FileAttributes::tryBuildByFileId($file->getFileId(), $sourceUri);
				$attributes
					->unsetAttribute('data-viewer')
					->setAttribute('data-inline-viewer')
					->setAttribute('data-disable-annotation-layer')
				;


				$result['VIEWER'] = "<div id=\"test-content\" style=\"width: 50vw;\" class=\"disk-external-link-wrapper\" {$attributes}></div>";
			}
			else
			{
				$result['VIEWER'] = $file->getView()->render(array(
					'PATH' => $viewUrl,
					'HEIGHT' => $height,
					'WIDTH' => $width,
					'SIZE_TYPE' => 'absolute',
				));
			}

		}
		elseif ($result['IS_DOCUMENT'] && $this->canMakePreview($file))
		{
			$result['PREVIEW'] = array(
				'VIEW_URL' => $this->getDocumentPreviewUrl($file),
			);
		}

		return $result;
	}

	private function getDocumentPreviewData(File $file)
	{
		$fileData = new \Bitrix\Disk\Document\FileData();
		$fileData
			->setFile($file)
			->setName($file->getName())
			->setMimeType(TypeFile::getMimeTypeByFilename($file->getName()))
		;

		$dataForViewFile = $this->defaultHandlerForView->getDataForViewFile($fileData);
		if(!$dataForViewFile)
		{
			return null;
		}

		return $dataForViewFile;
	}

	private function getDocumentPreviewUrl(File $file)
	{
		$documentPreviewData = $this->getDocumentPreviewData($file);
		if (!$documentPreviewData)
		{
			return null;
		}

		return $documentPreviewData['viewUrl'];
	}

	private function canMakePreview(File $file)
	{
		if ($file->getSize() > self::MAX_SIZE_TO_PREVIEW)
		{
			return false;
		}

		return !$this->externalLink->hasPassword() && $this->defaultHandlerForView instanceof \Bitrix\Disk\Document\GoogleViewerHandler;
	}

	protected function checkPassword()
	{
		$password = null;
		if (isset($_POST['PASSWORD']))
		{
			$password = $_POST['PASSWORD'];
		}
		elseif (isset($_SESSION["DISK_DATA"]["EXT_LINK_PASSWORD"]) && $_SESSION["DISK_DATA"]["EXT_LINK_PASSWORD"] <> '')
		{
			$password = $_SESSION["DISK_DATA"]["EXT_LINK_PASSWORD"];
		}

		if ($password === null)
		{
			return null;
		}

		if ($this->externalLink->checkPassword($password))
		{
			if (!isset($_SESSION["DISK_DATA"]))
			{
				$_SESSION["DISK_DATA"] = array();
			}
			$_SESSION["DISK_DATA"]["EXT_LINK_PASSWORD"] = $password;

			return true;
		}

		return false;
	}

	protected function processActionShowViewHtml($path, $fileId, $pathToView, $mode = '', $print = '', $preview = '', $sizeType = '', $printUrl = '')
	{
		$file = $this->getTargetFile($path, $fileId);
		if (!$file)
		{
			$this->includeComponentTemplate('error');
			return false;
		}

		$printParam = $iframe = 'N';
		if($mode === 'iframe')
		{
			$iframe = 'Y';
			if($print === 'Y')
			{
				$printParam = 'Y';
			}
		}

		$elementId = 'bx_ajaxelement_' . $file->getId() . '_' . randString(4);
		$view = $file->getView();

		$html = $view->render(array(
			'PATH' => $pathToView,
			'IFRAME' => $iframe,
			'ID' => $elementId,
			'PRINT' => $printParam,
			'PREVIEW' => $preview,
			'SIZE_TYPE' => $sizeType,
			'PRINT_URL' => $printUrl,
		));

		if($iframe == 'Y')
		{
			echo $html;
		}
		else
		{
			$result = array('html' => $html, 'innerElementId' => $elementId);
			$result = array_merge($result, $view->getJsViewerAdditionalJsonParams());
			$this->sendJsonResponse($result);
		}

		$this->end();
	}

	protected function processActionShowByOnlyOfficeViewer(string $path, int $fileId, string $token): void
	{
		$file = $this->getTargetFile($path, $fileId);
		if (!$file)
		{
			$this->sendJsonErrorResponse();
		}

		$passwordPassed = !$this->arResult['PROTECTED_BY_PASSWORD'] || $this->arResult['VALID_PASSWORD'];
		$isDocument = $this->isViewableDocument($file->getExtension());
		if (!$passwordPassed || !$isDocument || !($this->defaultHandlerForView instanceof OnlyOffice\OnlyOfficeHandler))
		{
			$this->sendJsonErrorResponse();
		}

		$documentSession = $this->generateDocumentSession($file);
		if (!$documentSession)
		{
			$this->sendJsonErrorResponse();
		}

		$downloadUrl = Driver::getInstance()->getUrlManager()->getUrlExternalLink(
			[
				'hash' => $this->externalLink->getHash(),
				'action' => 'downloadFileUnderFolder',
				'token' => $token,
				'path' => $path,
				'fileId' => $fileId,
			]
		);

		$this->arResult['DOCUMENT_SESSION'] = $documentSession;
		$this->arResult['LINK_TO_EDIT'] = '';
		$this->arResult['LINK_TO_DOWNLOAD'] = $downloadUrl;

		$this->includeComponentTemplate('onlyoffice');
	}

	protected function processActionShowByGoogleViewer($path, $fileId)
	{
		$file = $this->getTargetFile($path, $fileId);
		if (!$file)
		{
			$this->sendJsonErrorResponse();
		}

		if (!$this->canMakePreview($file))
		{
			$this->sendJsonAccessDeniedResponse('Could not make preview by module settings');
		}

		if ($this->request->get('document_action') === 'checkView')
		{
			$fileData = new FileData();
			$fileData->setId($this->request->get('id'));
			$result = $this->defaultHandlerForView->checkViewFile($fileData);
			if ($result === null)
			{
				$this->sendJsonErrorResponse();
			}

			$this->sendJsonSuccessResponse(array('viewed' => $result));
		}

		$documentPreviewData = $this->getDocumentPreviewData($file);
		if (!$documentPreviewData)
		{
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse($documentPreviewData);
	}

	protected function processActionDownload($showFile = false, $runResize = false)
	{
		$file = $this->externalLink->getFile();
		if(!$file)
		{
			$this->showNotFoundPage();

			return false;
		}

		$this->externalLink->incrementDownloadCount();
		if($this->externalLink->isSpecificVersion())
		{
			$version = $file->getVersion($this->externalLink->getVersionId());
			if(!$version)
			{
				$this->showNotFoundPage();

				return false;
			}
			$fileData = $version->getFile();
		}
		else
		{
			$fileData = $file->getFile();
		}

		if(!$fileData)
		{
			$this->showNotFoundPage();

			return false;
		}

		if($runResize && TypeFile::isImage($fileData['ORIGINAL_NAME']))
		{

			$tmpFile = \CFile::resizeImageGet($fileData, array("width" => 1920, "height" => 1080), BX_RESIZE_IMAGE_PROPORTIONAL, true, false, true);
			$fileData["FILE_SIZE"] = $tmpFile["size"];
			$fileData["SRC"] = $tmpFile["src"];
		}

		CFile::viewByUser($fileData, array('force_download' => !$showFile, 'attachment_name' => $file->getName()));
	}

	protected function getTargetFile($path, $fileId)
	{
		[$targetFolder,] = $this->getTargetFolderData($this->externalLink->getFolder(), $path);
		if (!$targetFolder)
		{
			return null;
		}

		$targetFile = File::load(array(
			'ID' => (int)$fileId,
			'PARENT_ID' => (int)$targetFolder->getRealObjectId(),
		));

		if (!$targetFile || !$targetFile->getFile())
		{
			return null;
		}

		return $targetFile;
	}

	protected function processActionDownloadFileUnderFolder($path, $fileId, $showFile = false, $runResize = false)
	{
		$targetFile = $this->getTargetFile($path, $fileId);
		if(!$targetFile)
		{
			$this->includeComponentTemplate('error');
			return false;
		}

		CFile::viewByUser($targetFile->getFile(), array('force_download' => !$showFile, 'attachment_name' => $targetFile->getName()));
	}

	protected function processActionShowFile()
	{
		$this->processActionDownload(true);
	}

	protected function processActionShowPreview()
	{
		$this->processActionDownload(true, true);
	}

	protected function processActionShowView($path = '/', $fileId = null)
	{
		if ($fileId && $path)
		{
			$file = $this->getTargetFile($path, $fileId);
		}
		else
		{
			$file = $this->externalLink->getFile();
		}

		if(!$file)
		{
			$this->showNotFoundPage();
			return false;
		}

		if(!$file->getView()->isHtmlAvailable() || !$this->checkDownloadToken($this->request->getQuery('token')))
		{
			$this->showNotFoundPage();
			return false;
		}

		if($this->externalLink->isSpecificVersion())
		{
			$version = $file->getVersion($this->externalLink->getVersionId());
			if(!$version)
			{
				$this->showNotFoundPage();
				return false;
			}
			$fileData = $version->getView()->getData();
		}
		else
		{
			$fileData = $file->getView()->getData();
		}

		if(!$fileData)
		{
			$this->showNotFoundPage();
			return false;
		}

		CFile::viewByUser($fileData, array('force_download' => false, 'attachment_name' => $file->getView()->getName(), 'cache_time' => 0));
	}

	protected function findLink()
	{
		$this->externalLink = \Bitrix\Disk\ExternalLink::load(array('=HASH' => $this->hash), array('OBJECT'));

		if(!$this->externalLink || $this->externalLink->isExpired() || !$this->externalLink->getObject())
		{
			throw new \Bitrix\Main\ObjectNotFoundException('Invalid external link');
		}

		return $this;
	}

	protected function showNotFoundPage()
	{
		\CHTTP::SetStatus('404 Not Found');

		$this->includeComponentTemplate('error');
	}

	protected function processActionDownloadFolderArchive()
	{
		$createdBy = $this->externalLink->getCreatedBy();
		if(!$createdBy)
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$folder = $this->externalLink->getFolder();
		if(!$folder)
		{
			$this->errorCollection[] = new Error("It's not folder.");
			$this->sendJsonErrorResponse();
		}

		if(!ZipNginx\Configuration::isEnabled())
		{
			$this->errorCollection[] = new Error('Work with mod_zip is disabled in module settings.');
			$this->sendJsonErrorResponse();
		}

		$storage = $folder->getStorage();
		if(!$storage)
		{
			$this->errorCollection[] = new Error("Could not find storage for folder.");
			$this->sendJsonErrorResponse();
		}

		$securityContext = $storage->getSecurityContext($createdBy);

		$zipArchive = ZipNginx\Archive::createFromFolder($folder, $securityContext);
		if($zipArchive->isEmpty())
		{
			$this->errorCollection[] = new Error('Archive is empty');
			$this->sendJsonErrorResponse();
		}

		$this->restartBuffer();
		$zipArchive->send();
		$this->end();
	}

	public function getLangId()
	{
		return $this->langId;
	}

	public function getMessage($id, $replace = null)
	{
		return Loc::getMessage($id, $replace, $this->langId);
	}
}
