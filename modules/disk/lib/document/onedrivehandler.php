<?php

namespace Bitrix\Disk\Document;

use Bitrix\Disk\Document\Contract\CloudImportInterface;
use Bitrix\Disk\Document\Contract\FileCreatable;
use Bitrix\Disk\Document\Upload\OneDriveResumableUpload;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\SpecificFolder;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\IO;

Loc::loadMessages(__FILE__);

class OneDriveHandler extends DocumentHandler implements FileCreatable, CloudImportInterface
{
	const API_URL_V1                      = 'https://api.onedrive.com/v1.0';
	const PREFIX_TO_CREATE_FILE           = '/drive/special/approot:/';
	const SUFFIX_TO_CREATE_LINK           = 'action.createLink';
	const SUFFIX_TO_CREATE_UPLOAD_SESSION = 'upload.createSession';

	const SPECIFIC_FOLDER_CODE = SpecificFolder::CODE_FOR_IMPORT_ONEDRIVE;

	const SHARED_LINK_TYPE_VIEW = 'view';
	const SHARED_LINK_TYPE_EDIT = 'edit';

	const MAX_SIZE_OF_SIMPLE_UPLOAD = 4194304; //4MB

	const ERROR_NOT_INSTALLED_SOCSERV     = 'DISK_ONEDRIVE_HANDLER_22002';
	const ERROR_BAD_JSON                  = 'DISK_ONEDRIVE_HANDLER_22005';
	const ERROR_HTTP_DELETE_FILE          = 'DISK_ONEDRIVE_HANDLER_22006';
	const ERROR_HTTP_DOWNLOAD_FILE        = 'DISK_ONEDRIVE_HANDLER_22007';
	const ERROR_HTTP_GET_METADATA         = 'DISK_ONEDRIVE_HANDLER_22008';
	const ERROR_HTTP_FILE_INTERNAL        = 'DISK_ONEDRIVE_HANDLER_22009';
	const ERROR_COULD_NOT_VIEW_FILE       = 'DISK_ONEDRIVE_HANDLER_22013';
	const ERROR_SHARED_EDIT_LINK          = 'DISK_ONEDRIVE_HANDLER_22014';
	const ERROR_SHARED_EMBED_LINK         = 'DISK_ONEDRIVE_HANDLER_22015';
	const ERROR_COULD_NOT_FIND_EMBED_LINK = 'DISK_ONEDRIVE_HANDLER_22016';
	const ERROR_HTTP_LIST_FOLDER          = 'DISK_ONEDRIVE_HANDLER_22017';

	/**
	 * @inheritdoc
	 */
	public static function getCode()
	{
		return 'onedrive';
	}

	/**
	 * @inheritdoc
	 */
	public static function getName()
	{
		return Loc::getMessage('DISK_ONE_DRIVE_HANDLER_NAME');
	}

	/**
	 * Public name storage of documents. May show in user interface.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getStorageName()
	{
		return Loc::getMessage('DISK_ONE_DRIVE_HANDLER_NAME_STORAGE');
	}

	/**
	 * Execute this method for check potential possibility get access token.
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function checkAccessibleTokenService()
	{
		if(!Loader::includeModule('socialservices'))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_ONEDRIVE_HANDLER_ERROR_NOT_INSTALLED_SOCSERV'), self::ERROR_NOT_INSTALLED_SOCSERV)));
			return false;
		}
		$authManager = new \CSocServAuthManager();
		$socNetServices = $authManager->getActiveAuthServices(array());
		$oauthService = $this->getOAuthService();

		return !empty($socNetServices[$oauthService::ID]);
	}


	/**
	 * Return link for authorize user in external service.
	 * @param string $mode
	 * @return string
	 */
	public function getUrlForAuthorizeInTokenService($mode = 'modal')
	{
		if(!Loader::includeModule('socialservices'))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_ONEDRIVE_HANDLER_ERROR_NOT_INSTALLED_SOCSERV'), self::ERROR_NOT_INSTALLED_SOCSERV)));
			return false;
		}

		$oauthService = $this->getOAuthService();
		if($mode === 'opener')
		{
			return $oauthService->getUrl(
				'opener', $this->getScopes(),
				array('BACKURL' => '#external-auth-ok')
			);
		}

		return $oauthService->getUrl('modal', $this->getScopes());
	}

	/**
	 * Request and store access token (self::accessToken) for self::userId
	 * @return $this
	 */
	public function queryAccessToken()
	{
		if(!Loader::includeModule('socialservices'))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_ONEDRIVE_HANDLER_ERROR_NOT_INSTALLED_SOCSERV'), self::ERROR_NOT_INSTALLED_SOCSERV)));
			return false;
		}

		$oauthService = $this->getOAuthService();
		$this->accessToken = $oauthService->getStorageToken();

		return $this;
	}

	protected function getOAuthServiceClass(): string
	{
		return \CSocServLiveIDOAuth::class;
	}

	/**
	 * Returns scopes.
	 *
	 * @return array
	 */
	protected function getScopes(): array
	{
		return array(
			'wl.contacts_skydrive',
			'wl.skydrive_update',
			'wl.skydrive',
			'onedrive.appfolder',
			'onedrive.readwrite',
		);
	}

	/**
	 * Create new blank file in cloud service.
	 * It is not necessary set shared rights on file.
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	public function createBlankFile(FileData $fileData)
	{
		return $this->createFile($fileData);
	}

	/**
	 * @param FileData $fileData
	 * @return FileData
	 */
	public function createFile(FileData $fileData)
	{
		$fileData = $this->createFileInternal($fileData);
		if($fileData === null)
		{
			return null;
		}

		/** @see \Bitrix\Disk\Document\OneDriveHandler::getSharedLink() */
		$link = $this->retryMethod('getSharedLink', array($fileData, self::SHARED_LINK_TYPE_EDIT));
		if($link === null)
		{
			return null;
		}
		$fileData->setLinkInService($link);

		return $fileData;
	}

	protected function createFileInternal(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'name', 'src',
		)))
		{
			return null;
		}

		if (!$fileData->getSize())
		{
			$fileData->setSize(filesize($fileData->getSrc()));
		}

		if ($fileData->getSize() < self::MAX_SIZE_OF_SIMPLE_UPLOAD)
		{
			return $this->createFileBySimpleUpload($fileData);
		}

		return $this->createByResumableUpload($fileData);
	}

	protected function instantiateResumableUpload(FileData $fileData)
	{
		return new OneDriveResumableUpload($this, $fileData);
	}

	protected function createByResumableUpload(FileData $fileData)
	{
		$resumableUpload = $this->instantiateResumableUpload($fileData);
		$resumableUpload->setUploadPath($this->getUploadPath($fileData));
		if (!$resumableUpload->upload())
		{
			$this->errorCollection->add($resumableUpload->getErrors());

			return null;
		}

		return $resumableUpload->getFileData();
	}

	protected function getUploadPath(FileData $fileData)
	{
		$fileName = $fileData->getName();
		$fileName = 'document.' . getFileExtension($fileName);
		$fileName = $this->convertToUtf8($fileName);
		$fileName = rawurlencode($fileName);

		return $this->getApiUrlRoot() . static::PREFIX_TO_CREATE_FILE . "{$fileName}:/";
	}

	protected function createFileBySimpleUpload(FileData $fileData)
	{
		$http = new HttpClient(array(
			'redirect' => false,
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Authorization', "bearer {$this->getAccessToken()}");
		$http->setHeader('Content-type', $fileData->getMimeType());

		if (
			$http->query(
				'PUT',
				$this->getUploadPath($fileData) . 'content?' . http_build_query(array('@name.conflictBehavior' => 'rename')),
				IO\File::getFileContents(IO\Path::convertPhysicalToLogical($fileData->getSrc()))
			) === false
		)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_FILE_INTERNAL)
			));
			return null;
		}

		if (!$this->checkHttpResponse($http))
		{
			return null;
		}

		$finalOutput = Json::decode($http->getResult());
		if ($finalOutput === null)
		{
			$this->errorCollection->add(array(
				new Error('Could not decode response as json', self::ERROR_BAD_JSON)
			));
			return null;
		}

		$fileData
			->setId($finalOutput['id'])
			->setLinkInService($finalOutput['webUrl'])
			->setMetaData($this->normalizeMetadata($finalOutput))
		;

		return $fileData;
	}

	protected function getFileNameToQuery(FileData $fileData)
	{
		$fileName = $fileData->getName();
		$fileName = 'document.' . getFileExtension($fileName);
		$fileName = $this->convertToUtf8($fileName);

		return rawurlencode($fileName);
	}

	/**
	 * Returns url root for API.
	 *
	 * @return string
	 */
	protected function getApiUrlRoot()
	{
		return static::API_URL_V1;
	}

	/**
	 * Get shared edit link on file
	 * @param FileData $fileData
	 * @param string   $type The type of link to create (view, edit).
	 * @return null|string
	 */
	protected function getSharedLink(FileData $fileData, $type = self::SHARED_LINK_TYPE_VIEW)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}

		if($type !== self::SHARED_LINK_TYPE_VIEW && $type !== self::SHARED_LINK_TYPE_EDIT)
		{
			$this->errorCollection[] = new Error('Invalid value for type', self::ERROR_REQUIRED_PARAMETER);
			return null;
		}

		$http = new HttpClient(array(
			'redirect' => false,
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));

		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "bearer {$this->getAccessToken()}");

		$postFields = "{\"type\":\"{$type}\"}";
		if($http->post($this->getApiUrlRoot() . "/drive/items/{$fileData->getId()}/" . static::SUFFIX_TO_CREATE_LINK, $postFields) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_SHARED_EDIT_LINK)
			));
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		$responseData = Json::decode($http->getResult());
		if($responseData === null)
		{
			$this->errorCollection->add(array(
				new Error('Could not decode response as json', self::ERROR_BAD_JSON)
			));
			return null;
		}

		if(empty($responseData['link']['webUrl']))
		{
			$this->errorCollection->add(array(
				new Error('Could not find webUrl in response', self::ERROR_SHARED_EDIT_LINK)
			));
			return null;
		}

		return $responseData['link']['webUrl'];
	}

	/**
	 * Download file from cloud service by FileData::id, put contents in FileData::src
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	public function downloadFile(FileData $fileData)
	{
		return $this->downloadFileContent($fileData);
	}

	private function downloadFileContent(FileData $fileData, $startRange = null, $chunkSize = null)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id', 'src',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();

		@set_time_limit(0);
		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));

		$http->setHeader('Authorization', "bearer {$accessToken}");

		if($startRange !== null && $chunkSize !== null)
		{
			$endRange = $startRange + $chunkSize - 1;
			$http->setHeader('Range', "bytes={$startRange}-{$endRange}");
		}

		if($http->download($this->getApiUrlRoot() . "/drive/items/{$fileData->getId()}/content", $fileData->getSrc()) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_DOWNLOAD_FILE)
			));
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		$filename = $http->getHeaders()->getFilename();
		if ($filename)
		{
			$fileData->setName($filename);
		}

		return $fileData;
	}

	/**
	 * Gets a file's metadata by ID.
	 *
	 * @param FileData $fileData
	 * @return array|null Describes file (id, title, size)
	 */
	public function getFileMetadata(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}

		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "bearer {$this->getAccessToken()}");

		if($http->get($this->getApiUrlRoot() . "/drive/items/{$fileData->getId()}") === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_GET_METADATA)
			));
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		$metaData = Json::decode($http->getResult());
		if($metaData === null)
		{
			$this->errorCollection->add(array(
				new Error('Could not decode response as json', self::ERROR_BAD_JSON)
			));
			return null;
		}

		if(!isset($metaData['file']))
		{
			$this->errorCollection->add(array(
				new Error('Could not get meta-data by folder', self::ERROR_HTTP_GET_METADATA)
			));
			return null;
		}

		return $this->normalizeMetadata($metaData);
	}

	/**
	 * Downloads part of file from cloud service by FileData::id, put contents in FileData::src
	 * @param FileData $fileData
	 * @param          $startRange
	 * @param          $chunkSize
	 * @return FileData|null
	 */
	public function downloadPartFile(FileData $fileData, $startRange, $chunkSize)
	{
		return $this->downloadFileContent($fileData, $startRange, $chunkSize);
	}

	/**
	 * Delete file from cloud service by FileData::id
	 * @param FileData $fileData
	 * @return bool
	 */
	public function deleteFile(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}

		$http = new HttpClient(array(
			'redirect' => false,
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));

		$http->setHeader('Authorization', "bearer {$this->getAccessToken()}");

		if($http->query('DELETE', $this->getApiUrlRoot() . "/drive/items/{$fileData->getId()}") === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_DELETE_FILE)
			));
			return false;
		}

		if(!$this->checkHttpResponse($http))
		{
			return false;
		}

		return true;
	}

	/**
	 * Get data for showing preview file.
	 * Array must be contains keys: id, viewUrl, neededDelete, neededCheckView
	 * @param FileData $fileData
	 * @return array|null
	 */
	public function getDataForViewFile(FileData $fileData)
	{
		$fileData = $this->createFileInternal($fileData);
		if($fileData === null)
		{
			return null;
		}
		$link = $this->getSharedEmbedLink($fileData);
		if($link === null)
		{
			$this->errorCollection->add(array(
				new Error(Loc::getMessage('DISK_ONE_DRIVE_HANDLER_ERROR_COULD_NOT_VIEW_FILE'), self::ERROR_COULD_NOT_VIEW_FILE)
			));
			return null;
		}
		$this->errorCollection->clear();

		return array(
			'id' => $fileData->getId(),
			'viewUrl' => $link,
			'neededDelete' => true,
			'neededCheckView' => false,
		);
	}

	/**
	 * Tells if file in cloud service was changed. For example, the method compares created date and modified date.
	 *
	 * @param array $currentMetadata Metadata (@see \Bitrix\Disk\Document::getFileMetadata());
	 * @param array $oldMetadata Old metadata.
	 * @return bool
	 */
	public function wasChangedAfterCreation(array $currentMetadata, array $oldMetadata = array())
	{
		$wasChangedAfterCreation = parent::wasChangedAfterCreation($currentMetadata, $oldMetadata);
		if(!$wasChangedAfterCreation && $this->getErrorByCode(self::ERROR_CODE_NOT_FOUND_ETAG))
		{
			return
				isset($currentMetadata['original']['createdDateTime'], $currentMetadata['original']['lastModifiedDateTime']) &&
				$currentMetadata['original']['createdDateTime'] !== $currentMetadata['original']['lastModifiedDateTime'];
		}

		return $wasChangedAfterCreation;
	}

	/**
	 * Lists folder contents
	 * @param $path
	 * @param $folderId
	 * @return mixed
	 */
	public function listFolder($path, $folderId)
	{
		if($path === '/')
		{
			$folderId = 'root';
		}

		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "bearer {$this->getAccessToken()}");

		if($http->get($this->getApiUrlRoot() . "/drive/items/{$folderId}?expand=children") === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_LIST_FOLDER)
			));
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		$items = Json::decode($http->getResult());
		if($items === null)
		{
			$this->errorCollection->add(array(
				new Error('Could not decode response as json', self::ERROR_BAD_JSON)
			));
			return null;
		}
		if(!isset($items['children']))
		{
			$this->errorCollection->add(array(
				new Error('Could not find items in response', self::ERROR_HTTP_LIST_FOLDER)
			));
			return null;
		}

		$reformatItems = array();
		foreach($items['children'] as $item)
		{
			$isFolder = isset($item['folder']);
			$dateTime = new \DateTime($item['lastModifiedDateTime']);
			$reformatItems[$item['id']] = array(
				'id' => $item['id'],
				'name' => $item['name'],
				'type' => $isFolder? 'folder' : 'file',

				'size' => $isFolder? '' : \CFile::formatSize($item['size']),
				'sizeInt' => $isFolder? '' : $item['size'],
				'modifyBy' => '',
				'modifyDate' => $dateTime->format('d.m.Y'),
				'modifyDateInt' => $dateTime->getTimestamp(),
				'provider' => static::getCode(),
			);
			if(!$isFolder)
			{
				$reformatItems[$item['id']]['storage'] = '';
				$reformatItems[$item['id']]['ext'] = getFileExtension($item['name']);
			}
		}
		unset($item);

		return $reformatItems;
	}

	protected function getSharedEmbedLink(FileData $fileData)
	{
		/** @see \Bitrix\Disk\Document\OneDriveHandler::getSharedLink() */
		$link = $this->retryMethod('getSharedLink', array($fileData, self::SHARED_LINK_TYPE_VIEW));
		if($link === null)
		{
			return null;
		}

		parse_str(parse_url($link, PHP_URL_QUERY), $queryParams);
		if(empty($queryParams['authkey']))
		{
			$this->errorCollection[] = new Error(
				Loc::getMessage('DISK_ONE_DRIVE_HANDLER_ERROR_COULD_NOT_FIND_EMBED_LINK'),
				self::ERROR_COULD_NOT_FIND_EMBED_LINK
			);
			return null;
		}

		return "https://onedrive.live.com/embed?resid={$fileData->getId()}&authkey={$queryParams['authkey']}&em=2&wdStartOn=1";
	}

	/**
	 * Returns normalized metadata.
	 *
	 * @param array $metaData
	 * @return array
	 */
	public function normalizeMetadata($metaData)
	{
		return array(
			'id' => $metaData['id'],
			'name' => $metaData['name'],
			'size' => $metaData['size'],
			'mimeType' => isset($metaData['file']['mimeType']) ? $metaData['file']['mimeType'] : '',
			'etag' => $metaData['eTag'],
			'original' => $metaData,
		);
	}

	public function checkHttpResponse(HttpClient $http)
	{
		$status = (int)$http->getStatus();

		if($status === 403 && mb_strpos($http->getHeaders()->get('content-type'), 'application/json') !== false)
		{
			$result = Json::decode($http->getResult());
			if(!empty($result['error']['code']) && $result['error']['code'] === 'accessDenied')
			{
				$this->errorCollection[] = new Error(
					'Insufficient scope (403)',
					self::ERROR_CODE_INSUFFICIENT_SCOPE
				);

				return false;
			}
		}

		return parent::checkHttpResponse($http);
	}
}