<?php

namespace Bitrix\Disk\Rest;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Disk\SystemUser;
use Bitrix\Main\Loader;
use Bitrix\Rest\RestException;
use IRestService;


if(!Loader::includeModule('rest'))
{
	return;
}

final class RestManager extends IRestService implements IErrorable
{
	/** @var  ErrorCollection */
	protected $errorCollection;

	/**
	 * Constructor of RestManager.
	 */
	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	private function getMethods()
	{
		return array_filter([
			'disk.storage.getFields',
			'disk.storage.get',
			'disk.storage.rename',
			'disk.storage.getList',
			'disk.storage.getTypes',
			'disk.storage.addFolder',
			'disk.storage.getChildren',
			'disk.storage.uploadFile',
			'disk.storage.getForApp',

			'disk.folder.getFields',
			'disk.folder.get',
			'disk.folder.getChildren',
			'disk.folder.addSubFolder',
			'disk.folder.copyTo',
			'disk.folder.moveTo',
			'disk.folder.rename',
			'disk.folder.deleteTree',
			'disk.folder.markDeleted',
			'disk.folder.restore',
			'disk.folder.uploadFile',
			'disk.folder.getExternalLink',
			'disk.folder.shareToUser',
			'disk.folder.listAllowedOperations',

			'disk.file.getFields',
			'disk.file.get',
			'disk.file.copyTo',
			'disk.file.moveTo',
			'disk.file.rename',
			'disk.file.delete',
			'disk.file.markDeleted',
			'disk.file.restore',
			'disk.file.uploadVersion',
			'disk.file.getExternalLink',
			Bitrix24Manager::isFeatureEnabled('disk_file_history')? 'disk.file.getVersions' : null,
			Bitrix24Manager::isFeatureEnabled('disk_file_history')? 'disk.file.restoreFromVersion': null,
			'disk.file.listAllowedOperations',

			'disk.version.get',

			'disk.rights.getTasks',

			'disk.attachedObject.get',

			\CRestUtil::METHOD_DOWNLOAD, //'disk.file.download' 'disk.version.download'
			\CRestUtil::METHOD_UPLOAD, //'disk.folder.upload'
		]);
	}

	/**
	 * Builds list of REST methods which provides module disk.
	 * @return array
	 */
	public static function onRestServiceBuildDescription()
	{
		$methods = array();
		$restManager = Driver::getInstance()->getRestManager();
		foreach($restManager->getMethods() as $methodName)
		{
			$methods[$methodName] = array(
				$restManager,
				'processMethodRequest'
			);
		}
		unset($methodName);

		return array(
			Driver::INTERNAL_MODULE_ID => $methods
		);
	}

	public static function onRestGetModule()
	{
		return array(
			'MODULE_ID' => Driver::INTERNAL_MODULE_ID,
		);
	}

	/**
	 * Deletes application storage.
	 * @param array $fields Fields describes application.
	 * @return void
	 */
	public static function onRestAppDelete(array $fields)
	{
		if(empty($fields['APP_ID']) || empty($fields['CLEAN']))
		{
			return;
		}
		$storage = Driver::getInstance()->getStorageByRestApp($fields['APP_ID']);
		if(!$storage)
		{
			return;
		}
		$storage->delete(SystemUser::SYSTEM_USER_ID);
	}

	/**
	 * Gets offset and limit by start parameter.
	 * @param string $start Start position.
	 * @param bool   $isOrm Use ORM format.
	 * @return array
	 */
	public static function getNavData($start, $isOrm = false)
	{
		return parent::getNavData($start, true);
	}

	public static function setNavData($result, $dbRes)
	{
		return parent::setNavData($result, $dbRes);
	}

	/**
	 * Processes method to services.
	 * @param array        $params Input parameters ($_GET, $_POST).
	 * @param     string   $start Start position.
	 * @param \CRestServer $restServer REST server.
	 * @return array
	 * @throws RestException
	 */
	public function processMethodRequest(array $params, $start, \CRestServer $restServer)
	{
		$service = $this
			->checkMethodName($restServer->getMethod())
			->buildService($params, $start, $restServer)
		;
		$data = $service->processMethodRequest();

		if($data === null)
		{
			$this->errorCollection->add($service->getErrors());
			$errors = $this->getErrors();
			if($errors)
			{
				throw $this->createExceptionFromErrors($errors);
			}
		}

		$externalizer = new Externalizer($service, $restServer);
		return $externalizer->getExternalData($data);
	}

	/**
	 * @param Error[] $errors
	 * @return RestException
	 */
	private function createExceptionFromErrors(array $errors)
	{
		if(!$errors)
		{
			return null;
		}

		$description = array();
		/** @var Error $lastError */
		$lastError = array_pop($errors);
		$description[] = $lastError->getMessage() . " ({$lastError->getCode()}).";

		foreach($errors as $error)
		{
			$description[] = $error->getMessage() . " ({$error->getCode()}).";
		}
		unset($error);

		return new RestException(implode(' ', $description), $lastError->getCode());
	}

	private function checkMethodName($methodName)
	{
		if($methodName === \CRestUtil::METHOD_DOWNLOAD || $methodName === \CRestUtil::METHOD_UPLOAD)
		{
			return $this;
		}
		$parts = explode('.', $methodName);
		if($parts[0] !== Driver::INTERNAL_MODULE_ID || count($parts) !== 3)
		{
			throw new RestException(
				"Method '{$methodName}' is not supported in current context.",
				RestException::ERROR_METHOD_NOT_FOUND
			);
		}

		return $this;
	}

	private function getServiceClassByEntity($entityName)
	{
		$map = array(
			'storage' => Service\Storage::className(),
			'folder' => Service\Folder::className(),
			'file' => Service\File::className(),
			'version' => Service\Version::className(),
			'rights' => Service\Rights::className(),
			'attachedobject' => Service\AttachedObject::className(),
		);

		if(!isset($map[$entityName]))
		{
			throw new RestException(
				"Entity '{$entityName} is not supported."
			);
		}

		return $map[$entityName];
	}

	/**
	 * @param array        $params
	 * @param              $start
	 * @param \CRestServer $restServer
	 * @return Service\Base
	 * @throws RestException
	 */
	private function buildService(array $params, $start, \CRestServer $restServer)
	{
		list($prefix, $entityName, $method) = explode('.', $restServer->getMethod());

		if($prefix === \CRestUtil::METHOD_DOWNLOAD)
		{
			//by service we are trying to route download action to service.
			if (!isset($params['service']))
			{
				$params['service'] = 'file';
			}

			$entityName = $params['service']; //file or version
			$method = \CRestUtil::METHOD_DOWNLOAD;
		}
		if($prefix === \CRestUtil::METHOD_UPLOAD)
		{
			$entityName = 'folder';
			$method = \CRestUtil::METHOD_UPLOAD;
		}

		$serviceClass = $this->getServiceClassByEntity($entityName);

		$reflection = new \ReflectionClass($serviceClass);
		return $reflection->newInstanceArgs(array($method, $params, $start, $restServer));
	}

	/**
	 * @return string the fully qualified name of this class.
	 */
	public static function className()
	{
		return get_called_class();
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}