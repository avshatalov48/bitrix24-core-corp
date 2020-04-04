<?php
use Bitrix\Disk\ExternalLink;
use Bitrix\Disk\File;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Localization\Loc;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define("NOT_CHECK_PERMISSIONS", true);

$siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID'])? $_REQUEST['SITE_ID'] : '';
$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if(!empty($siteId) && is_string($siteId))
{
	define('SITE_ID', $siteId);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('disk') || !\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
{
	return;
}

Loc::loadMessages(__FILE__);

class DiskExternalLinkListAjaxController extends \Bitrix\Disk\Internals\Controller
{
	const ERROR_COULD_NOT_FIND_OBJECT = 'DISK_ELLAC_22001';

	protected function listActions()
	{
		return array(
			'disableExternalLink' => array(
				'method' => array('POST'),
			),
		);
	}

	private function getFileAndExternalLink()
	{
		if(!$this->checkRequiredPostParams(array('externalId', 'objectId')))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var File $file */
		$file = File::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$file)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_BREADCRUMBS_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
		if(!$file->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}
		$extLinks = $file->getExternalLinks(array(
			'filter' => array(
				'ID' => (int)$this->request->getPost('externalId'),
				'OBJECT_ID' => $file->getId(),
				'CREATED_BY' => $this->getUser()->getId(),
				'TYPE' => \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_MANUAL,
				'IS_EXPIRED' => false,
			),
			'limit' => 1,
		));

		return array($file, array_pop($extLinks));
	}

	protected function processActionDisableExternalLink()
	{
		/** @var File $file */
		/** @var ExternalLink $extLink */
		list($file, $extLink) = $this->getFileAndExternalLink();
		if(!$extLink || $extLink->delete())
		{
			$this->sendJsonSuccessResponse();
		}
		$this->sendJsonErrorResponse();
	}
}
$controller = new DiskExternalLinkListAjaxController();
$controller
	->setActionName(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
	->exec()
;