<?php
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Integration\Bitrix24Manager;
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

if (!\Bitrix\Main\Loader::includeModule('disk'))
{
	return;
}

Loc::loadMessages(__FILE__);

class DiskFileUploadAjaxController extends \Bitrix\Disk\Internals\Controller
{
	const ERROR_COULD_NOT_FIND_FOLDER = 'DISK_FUAC_22001';
	const ERROR_BAD_RIGHTS            = 'DISK_FUAC_22002';
	const ERROR_COULD_NOT_FIND_FILE   = 'DISK_FUAC_22003';
	const ERROR_COULD_UPLOAD_VERSION  = 'DISK_FUAC_22004';

	private static $uploader;

	protected function listActions()
	{
		return array(
			'upload' => array(
				'method' => array('POST', 'GET'),
				'check_csrf_token' => false,
			),
		);
	}

	protected function processActionUpload()
	{
		if (!isset(self::$uploader))
		{
			self::$uploader = new \Bitrix\Main\UI\Uploader\Uploader(array(
				"events" => array(
					"onFileIsUploaded" => array($this, "processActionHandleFile")
				),
				"storage" => array(
					"cloud" => true,
					"moduleId" => \Bitrix\Disk\Driver::INTERNAL_MODULE_ID
				)
			), "get");
		}
		self::$uploader->checkPost();
	}

	protected function processActionUploadFile($hash, &$file, &$package, &$upload, &$error)
	{
		$fileIdsToReplace = $this->request->getPost('REPLACE_FILE');
		$updateFile =
			$fileIdsToReplace &&
			is_array($fileIdsToReplace) &&
			in_array($file["id"], $fileIdsToReplace)
		;

		$createFile = !$updateFile;

		/** @var Folder $folder */
		$folder = Folder::loadById((int)$this->request->getPost('targetFolderId'), array('STORAGE'));
		if(!$folder)
		{
			$error = Loc::getMessage('DISK_FILE_UPLOAD_ERROR_COULD_NOT_FIND_FOLDER');

			return false;
		}
		unset($file["isNotUnique"]);
		if ($createFile)
		{
			if(!$folder->canAdd($folder->getStorage()->getCurrentUserSecurityContext()))
			{
				$error = Loc::getMessage('DISK_FILE_UPLOAD_ERROR_BAD_RIGHTS');

				return false;
			}

			$fileModel = $folder->uploadFile(
				$file["files"]["default"],
				array(
					'NAME' => $file['name'],
					'CREATED_BY' => $this->getUser()->getId(),
				)
			);

			if(!$fileModel)
			{
				$error = implode(' ', $folder->getErrors());
				if ($folder->getErrorByCode($folder::ERROR_NON_UNIQUE_NAME))
				{
					$file["isNotUnique"] = true;
				}

				return false;
			}

			$this->tryToRunWorkflow($fileModel);
			$file["fileId"] = $fileModel->getId();

			return true;
		}

		/** @var File $fileModel */
		$fileModel = File::load(array('NAME' => $file['name'], 'PARENT_ID' => $folder->getRealObjectId()));
		if (!$fileModel)
		{
			$error = Loc::getMessage('DISK_FILE_UPLOAD_ERROR_COULD_NOT_FIND_FILE');

			return false;
		}

		if(!$fileModel->canUpdate($fileModel->getStorage()->getCurrentUserSecurityContext()))
		{
			$error = Loc::getMessage('DISK_FILE_UPLOAD_ERROR_BAD_RIGHTS');

			return false;
		}

		if (!$fileModel->uploadVersion($file["files"]["default"], $this->getUser()->getId()))
		{
			$error = Loc::getMessage('DISK_FILE_UPLOAD_ERROR_COULD_UPLOAD_VERSION');

			return false;
		}

		$file["fileId"] = $fileModel->getId();

		return (empty($error));
	}

	protected function processActionUpdateFile($hash, &$fileData, &$package, &$upload, &$error)
	{
		if (!$this->checkRequiredPostParams(array('targetFileId')))
		{
			$error = implode(' ', $this->errorCollection->toArray());

			return false;
		}

		/** @var File $file */
		$file = File::loadById((int)$this->request->getPost('targetFileId'), array('STORAGE'));
		if(!$file)
		{
			$error = Loc::getMessage('DISK_FILE_UPLOAD_ERROR_COULD_NOT_FIND_FILE');

			return false;
		}

		if (!$file->canUpdate($file->getStorage()->getCurrentUserSecurityContext()))
		{
			$error = Loc::getMessage('DISK_FILE_UPLOAD_ERROR_BAD_RIGHTS2');

			return false;
		}

		if (!$file->uploadVersion($fileData["files"]["default"], $this->getUser()->getId()))
		{
			$error = Loc::getMessage('DISK_FILE_UPLOAD_ERROR_COULD_UPLOAD_VERSION');

			return false;
		}
		$fileData["fileId"] = $file->getId();
		$this->tryToRunWorkflow($file);

		return true;
	}

	public function processActionHandleFile($hash, &$file, &$package, &$upload, &$error)
	{
		if(
			Bitrix24Manager::isEnabled() &&
			!Bitrix24Manager::isAccessEnabled('disk', $this->getUser()->getId()))
		{
			$this->sendJsonResponse(array(
				'status' => $this::STATUS_RESTRICTION,
			));
		}

		return ($this->request->getPost('targetFileId') ?
			$this->processActionUpdateFile($hash, $file, $package, $upload, $error) :
			$this->processActionUploadFile($hash, $file, $package, $upload, $error)
		);
	}

	/**
	 * @param File $file
	 */
	private function tryToRunWorkflow(File $file)
	{
		if (!$this->request->getPost('checkBp'))
		{
			return;
		}

		$workflowParameters = array();
		$search = 'bizproc';
		foreach ($this->request->getPostList() as $idParameter => $valueParameter)
		{
			$res = strpos($idParameter, $search);
			if ($res === 0)
			{
				$workflowParameters[$idParameter] = $valueParameter;
			}
		}

		if($this->request->getPost('autoExecute') === 'create')
		{
			\Bitrix\Disk\BizProcDocument::runAfterCreateWithInputParameters(
				$file->getStorageId(),
				$file->getId(),
				$workflowParameters
			);
		}
		elseif($this->request->getPost('autoExecute') === 'edit')
		{
			\Bitrix\Disk\BizProcDocument::runAfterEditWithInputParameters(
				$file->getStorageId(),
				$file->getId(),
				$workflowParameters
			);
		}
	}
}
$controller = new DiskFileUploadAjaxController();
$controller
	->setActionName( "upload" )
	->exec()
;