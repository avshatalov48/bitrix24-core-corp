<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Grid\FolderListOptions;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

Loc::loadMessages(__FILE__);

final class FocusController extends Internals\Controller
{
	const ERROR_COULD_NOT_FIND_FILE = 'DISK_FC_22004';
	const ERROR_COULD_NOT_READ_FILE = 'DISK_FC_22005';

	protected function listActions()
	{
		return array(
			'showObjectInGrid' => array(
				'method' => array('GET', 'POST'),
				'redirect_on_auth' => true,
				'close_session' => true,
				'check_csrf_token' => false,
			),
			'showObjectInTrashCanGrid' => array(
				'method' => array('GET', 'POST'),
				'redirect_on_auth' => true,
				'close_session' => true,
				'check_csrf_token' => false,
			),
			'openFileDetail' => array(
				'method' => array('GET', 'POST'),
				'redirect_on_auth' => true,
				'close_session' => true,
				'check_csrf_token' => false,
			),
			'openFolderList' => array(
				'method' => array('GET', 'POST'),
				'redirect_on_auth' => true,
				'close_session' => true,
				'check_csrf_token' => false,
			),
			'openTrashcanFileDetail' => array(
				'method' => array('GET', 'POST'),
				'redirect_on_auth' => true,
				'close_session' => true,
				'check_csrf_token' => false,
			),
		);
	}

	private function showNotFound()
	{
		require(Application::getDocumentRoot() . '/bitrix/header.php');

		global $APPLICATION;
		$APPLICATION->includeComponent(
			'bitrix:disk.error.page',
			'',
			array()
		);

		require(Application::getDocumentRoot() . '/bitrix/footer.php');
		die;
	}

	protected function processActionShowObjectInGrid($objectId)
	{
		/** @var Folder|File $object */
		$object = $this->findObject($objectId);
		if (!$object)
		{
			$this->showNotFound();

			return;
		}

		if (!$this->checkReadRights($object))
		{
			$this->showNotFound();

			return;
		}

		$gridOptions = new FolderListOptions($object->getStorage());
		$filter = array(
			'PARENT_ID' => $object->getParentId(),
			'DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
		);

		$finalPage = $this->getPageWithObject($object, $gridOptions, $filter);
		$urlManager = Driver::getInstance()->getUrlManager();

		LocalRedirect($this->buildUrlToFocus($urlManager->getPathInListing($object), $object->getId(), $finalPage, $gridOptions));
	}

	protected function processActionShowObjectInTrashCanGrid($objectId)
	{
		/** @var Folder|File $object */
		$object = $this->findObject($objectId);
		if (!$object || !$object->isDeleted())
		{
			$this->showNotFound();

			return;
		}

		if (!$this->checkReadRights($object))
		{
			$this->showNotFound();

			return;
		}

		$gridOptions = new Internals\Grid\TrashCanOptions($object->getStorage());
		if ($object->getDeletedType() == ObjectTable::DELETED_TYPE_ROOT)
		{
			$filter = array(
				'STORAGE_ID' => $object->getStorageId(),
				'DELETED_TYPE' => $object->getDeletedType(),
			);
		}
		else
		{
			$filter = array(
				'PARENT_ID' => $object->getParentId(),
			);
		}

		$finalPage = $this->getPageWithObject($object, $gridOptions, $filter);
		$urlManager = Driver::getInstance()->getUrlManager();

		LocalRedirect($this->buildUrlToFocus($urlManager->getPathInTrashcanListing($object), $object->getId(), $finalPage, $gridOptions));
	}

	private function buildUrlToFocus($listingPath, $objectId, $finalPage, FolderListOptions $gridOptions)
	{
		$command = $this->request->getQuery('cmd')?: '';
		if ($command)
		{
			$command = '!' . $command;
		}

		$urlManager = Driver::getInstance()->getUrlManager();
		$uri = new Uri($urlManager->encodeUrn($listingPath));
		$pathInListing = $gridOptions->getNavigation()->addParams($uri, false, $finalPage);

		if (Main\Grid\Context::isInternalRequest())
		{
			//append grid parameters to work with component ajax after 302 redirect
			$pathInListing->addParams(array(
				'internal' => $this->request->get('internal'),
				'grid_id' => $this->request->get('grid_id'),
				'grid_action' => $this->request->get('grid_action'),
				'bxajaxid' => $this->request->get('bxajaxid'),
				'resetFilter' => $this->request->get('resetFilter'),
			));
		}

		return $pathInListing->getUri() . "#hl-" . $objectId . $command;
	}

	private function findObject($objectId)
	{
		/** @var Folder|File $object */
		$object = BaseObject::loadById($objectId, array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection[] = new Error('Could not find file or folder', self::ERROR_COULD_NOT_FIND_FILE);

			return null;
		}

		return $object;
	}

	private function checkReadRights(BaseObject $object)
	{
		$storage = $object->getStorage();
		$securityContext = $storage->getCurrentUserSecurityContext();

		if (!$object->canRead($securityContext))
		{
			$this->errorCollection[] = new Error('Could not find file or folder', self::ERROR_COULD_NOT_READ_FILE);

			return false;
		}

		return true;
	}

	private function getPageWithObject(BaseObject $object, FolderListOptions $gridOptions, array $filter)
	{
		$storage = $object->getStorage();
		$securityContext = $storage->getCurrentUserSecurityContext();
		$pageSize = $gridOptions->getPageSize() ?: FolderListOptions::COUNT_ON_PAGE;

		$parameters = array(
			'select' => array('ID'),
			'filter' => $filter,
			'order' => $gridOptions->getOrderForOrm(),
			'limit' => $pageSize,
		);

		$countQuery = new Query(ObjectTable::getEntity());
		$countQuery->addSelect(new ExpressionField('CNT', 'COUNT(1)'));
		$countQuery->setFilter($parameters['filter']);
		$totalCount = $countQuery->setLimit(null)->setOffset(null)->exec()->fetch();
		$totalCount = $totalCount['CNT'];

		$pageCount = ceil($totalCount / $pageSize);

		$driver = Driver::getInstance();
		$finalPage = null;
		for($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++)
		{
			$fullParameters = $driver->getRightsManager()->addRightsCheck($securityContext, $parameters, array('ID', 'CREATED_BY'));
			$fullParameters['offset'] = $pageSize * ($pageNumber - 1);
			$query = ObjectTable::getList($fullParameters);
			while($row = $query->fetch())
			{
				if($row['ID'] == $object->getId())
				{
					$finalPage = $pageNumber;
					break;
				}
			}
			if($finalPage !== null)
			{
				break;
			}
		}

		return $finalPage?: 1;
	}

	protected function processActionOpenFileDetail()
	{
		if(!$this->checkRequiredGetParams(array('fileId')))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var File $file */
		$file = File::loadById((int)$this->request->getQuery('fileId'), array('STORAGE'));
		if(!$file)
		{
			$this->errorCollection->addOne(new Error('Could not find file', self::ERROR_COULD_NOT_FIND_FILE));
			$this->showNotFound();
		}
		if(!$file->canRead($file->getStorage()->getCurrentUserSecurityContext()))
		{
			$this->errorCollection->addOne(new Error('Could not find file', self::ERROR_COULD_NOT_READ_FILE));
			$this->showNotFound();
		}

		$urlManager = Driver::getInstance()->getUrlManager();
		$pathDetail = $urlManager->getPathFileDetail($file);
		$additionalParameters = [];
		if($this->request->getQuery('back'))
		{
			$additionalParameters['back'] = $this->request->getQuery('back');
		}
		if($this->request->getQuery('show'))
		{
			$additionalParameters['show'] = $this->request->getQuery('show');
		}
		if ($this->request->get('IFRAME') === 'Y' || $this->request->getPost('IFRAME') === 'Y')
		{
			$additionalParameters['IFRAME'] = 'Y';
		}

		if ($additionalParameters)
		{
			$pathDetail .= '?&' . http_build_query($additionalParameters);
		}

		LocalRedirect(
			$urlManager->encodeUrn($pathDetail)
		);
	}

	protected function processActionOpenFolderList()
	{
		if(!$this->checkRequiredGetParams(array('folderId')))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var Folder $folder */
		$folder = Folder::loadById((int)$this->request->getQuery('folderId'), array('STORAGE'));
		if(!$folder)
		{
			$this->errorCollection->addOne(new Error('Could not find folder', self::ERROR_COULD_NOT_FIND_FILE));
			$this->showNotFound();
		}
		if(!$folder->canRead($folder->getStorage()->getCurrentUserSecurityContext()))
		{
			$this->errorCollection->addOne(new Error('Could not find folder', self::ERROR_COULD_NOT_READ_FILE));
			$this->showNotFound();
		}

		$urlManager = Driver::getInstance()->getUrlManager();
		LocalRedirect(
			$urlManager->encodeUrn(
				$urlManager->getPathFolderList($folder)
			)
		);
	}

	protected function processActionOpenTrashcanFileDetail()
	{
		if(!$this->checkRequiredGetParams(array('fileId')))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var File $file */
		$file = File::loadById((int)$this->request->getQuery('fileId'), array('STORAGE'));
		if(!$file)
		{
			$this->errorCollection->addOne(new Error('Could not find file', self::ERROR_COULD_NOT_FIND_FILE));
			$this->showNotFound();
		}
		if(!$file->canRead($file->getStorage()->getCurrentUserSecurityContext()))
		{
			$this->errorCollection->addOne(new Error('Could not find file', self::ERROR_COULD_NOT_READ_FILE));
			$this->showNotFound();
		}

		$urlManager = Driver::getInstance()->getUrlManager();
		$pathDetail = $urlManager->getPathTrashcanFileDetail($file);
		if($this->request->getQuery('back'))
		{
			$pathDetail .= '?&' . http_build_query(array('back' => $this->request->getQuery('back')));
		}
		LocalRedirect(
			$urlManager->encodeUrn($pathDetail)
		);
	}
}