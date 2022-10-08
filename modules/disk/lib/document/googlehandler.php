<?php

namespace Bitrix\Disk\Document;

use Bitrix\Disk\Document\Contract\CloudImportInterface;
use Bitrix\Disk\Document\Contract\FileCreatable;
use Bitrix\Disk\Document\Upload\GoogleResumableUpload;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\ShowSession;
use Bitrix\Disk\SpecificFolder;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\IO;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

class GoogleHandler extends DocumentHandler implements IViewer, FileCreatable, CloudImportInterface
{
	const API_URL_V2        = 'https://www.googleapis.com/drive/v2';
	const API_URL_V3        = 'https://www.googleapis.com/drive/v3';
	const API_URL_UPLOAD_V3 = 'https://www.googleapis.com/upload/drive/v3';

	const PERMISSION_ROLE_OWNER     = 'owner';
	const PERMISSION_ROLE_WRITER    = 'writer';
	const PERMISSION_ROLE_COMMENTER = 'commenter';
	const PERMISSION_ROLE_READER    = 'reader';

	const PERMISSION_TYPE_USER   = 'user';
	const PERMISSION_TYPE_GROUP  = 'group';
	const PERMISSION_TYPE_DOMAIN = 'domain';
	const PERMISSION_TYPE_ANYONE = 'anyone';

	const SPECIFIC_FOLDER_CODE = SpecificFolder::CODE_FOR_IMPORT_GDRIVE;

	const ERROR_NOT_INSTALLED_SOCSERV        = 'DISK_GOOGLE_HANDLER_22002';
	const ERROR_UNSUPPORTED_FILE_FORMAT      = 'DISK_GOOGLE_HANDLER_22003';
	const ERROR_HTTP_CREATE_BLANK            = 'DISK_GOOGLE_HANDLER_22004';
	const ERROR_BAD_JSON                     = 'DISK_GOOGLE_HANDLER_22005';
	const ERROR_HTTP_DELETE_FILE             = 'DISK_GOOGLE_HANDLER_22006';
	const ERROR_HTTP_DOWNLOAD_FILE           = 'DISK_GOOGLE_HANDLER_22007';
	const ERROR_HTTP_GET_METADATA            = 'DISK_GOOGLE_HANDLER_22008';
	const ERROR_HTTP_GET_LOCATION_FOR_UPLOAD = 'DISK_GOOGLE_HANDLER_22009';
	const ERROR_HTTP_INSERT_PERMISSION       = 'DISK_GOOGLE_HANDLER_22010';
	const ERROR_HTTP_RESUMABLE_UPLOAD        = 'DISK_GOOGLE_HANDLER_22012';
	const ERROR_COULD_NOT_VIEW_FILE          = 'DISK_GOOGLE_HANDLER_22013';
	const ERROR_COULD_NOT_FIND_ID            = 'DISK_GOOGLE_HANDLER_22014';
	const ERROR_HTTP_LIST_FOLDER             = 'DISK_GOOGLE_HANDLER_22015';
	const ERROR_EMBED_FILE_LINK              = 'DISK_GOOGLE_HANDLER_22016';
	const ERROR_HTTP_PATCH                   = 'DISK_GOOGLE_HANDLER_22017';

	/**
	 * @inheritdoc
	 */
	public static function getCode()
	{
		return 'gdrive';
	}

	/**
	 * @inheritdoc
	 */
	public static function getName()
	{
		return Loc::getMessage('DISK_GOOGLE_HANDLER_NAME');
	}

	/**
	 * Public name storage of documents. May show in user interface.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getStorageName()
	{
		return Loc::getMessage('DISK_GOOGLE_HANDLER_NAME_STORAGE');
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
			$this->errorCollection[] = new Error(
				Loc::getMessage('DISK_GOOGLE_HANDLER_ERROR_NOT_INSTALLED_SOCSERV'), self::ERROR_NOT_INSTALLED_SOCSERV
			);
			return false;
		}
		$authManager = new \CSocServAuthManager();
		$socNetServices = $authManager->getActiveAuthServices(array());

		return !empty($socNetServices[\CSocServGoogleOAuth::ID]);
	}


	/**
	 * Return link for authorize user in external service.
	 * @param string $mode
	 * @return string
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getUrlForAuthorizeInTokenService($mode = 'modal')
	{
		if(!Loader::includeModule('socialservices'))
		{
			$this->errorCollection[] = new Error(
				Loc::getMessage('DISK_GOOGLE_HANDLER_ERROR_NOT_INSTALLED_SOCSERV'), self::ERROR_NOT_INSTALLED_SOCSERV
			);
			return false;
		}

		$socGoogleOAuth = new \CSocServGoogleOAuth($this->userId);
		if($mode === 'opener')
		{
			return $socGoogleOAuth->getUrl(
				'opener',
				$this->getScopes(),
				array('BACKURL' => '#external-auth-ok')
			);
		}

		return $socGoogleOAuth->getUrl('modal', $this->getScopes());
	}

	protected function getScopes(): array
	{
		return array(
			'https://www.googleapis.com/auth/drive'
		);
	}

	protected function getOAuthServiceClass(): string
	{
		return \CSocServGoogleOAuth::class;
	}

	/**
	 * Request and store access token (self::accessToken) for self::userId
	 * @return $this
	 */
	public function queryAccessToken()
	{
		if(!Loader::includeModule('socialservices'))
		{
			$this->errorCollection[] = new Error(
				Loc::getMessage('DISK_GOOGLE_HANDLER_ERROR_NOT_INSTALLED_SOCSERV'), self::ERROR_NOT_INSTALLED_SOCSERV
			);
			return false;
		}

		$this->accessToken = $this->getOAuthService()->getStorageToken();

		return $this;
	}


	/**
	 * Create new blank file in cloud service.
	 * It is not necessary set shared rights on file.
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	public function createBlankFile(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'name',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();

		$googleMimeType = static::getInternalMimeTypeByExtension(getFileExtension($fileData->getName()));
		$fileName = getFileNameWithoutExtension($fileData->getName());
		$fileName = $this->convertToUtf8($fileName);

		if(!$googleMimeType)
		{
			$this->errorCollection[] = new Error(
				"Unsupported file format with name {$fileData->getName()}", self::ERROR_UNSUPPORTED_FILE_FORMAT
			);
			return null;
		}

		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "Bearer {$accessToken}");

		$postFields = "{\"name\":\"{$fileName}\",\"mimeType\":\"{$googleMimeType}\"}";
		if($http->post(self::API_URL_V3 . '/files?fields=id,webViewLink', $postFields) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection[] = new Error(
				$errorString, self::ERROR_HTTP_CREATE_BLANK
			);
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		$fileMetadata = Json::decode($http->getResult());
		if($fileMetadata === null)
		{
			$this->errorCollection[] = new Error(
				'Could not decode response as json', self::ERROR_BAD_JSON
			);
			return null;
		}

		if(empty($fileMetadata['id']) || empty($fileMetadata['webViewLink']))
		{
			$this->errorCollection[] = new Error(
				'Could not find id or webViewLink in response from Google.', self::ERROR_COULD_NOT_FIND_ID
			);
			return null;
		}

		$fileData->setLinkInService($fileMetadata['webViewLink']);
		$fileData->setId($fileMetadata['id']);

		//last signed user must delete file from google drive
		$this->insertPermission($fileData);

		return $fileData;
	}

	/**
	 * Create file in cloud service by upload from us server.
	 * Necessary set shared rights on file for common work.
	 *
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	public function createFile(FileData $fileData)
	{
		$newFile = $this->createByResumableUpload($fileData, $lastStatus, $metadata);
		if(!$newFile)
		{
			//retry upload, but not convert content
			if($lastStatus == '500')
			{
				$fileData->setNeedConvert(false);
				$newFile = $this->createByResumableUpload($fileData, $lastStatus, $metadata);
			}
		}
		if(!$newFile)
		{
			return null;
		}
		//last signed user must delete file from google drive
		$this->insertPermission($newFile);

		return $newFile;
	}

	/**
	 * @param FileData $fileData
	 * @param string $lastStatus
	 * @param array $fileMetadata
	 * @return FileData|null
	 */
	protected function createByResumableUpload(FileData $fileData, &$lastStatus, &$fileMetadata)
	{
		$resumableUpload = new GoogleResumableUpload($this, $fileData);
		if (!$resumableUpload->upload())
		{
			$this->errorCollection->add($resumableUpload->getErrors());

			return null;
		}
		$lastStatus = $resumableUpload->getLastStatus();
		$fileMetadata = $resumableUpload->getLastResponse();

		return $resumableUpload->getFileData();
	}

	private function insertPermission(FileData $fileData, $role = self::PERMISSION_ROLE_WRITER, $type = self::PERMISSION_TYPE_ANYONE)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();

		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "Bearer {$accessToken}");

		$postFields = Json::encode(array(
			'role' => $role,
			'type' => $type,
		));
		if($http->post(self::API_URL_V3 . "/files/{$fileData->getId()}/permissions", $postFields) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection[] = new Error(
				$errorString, self::ERROR_HTTP_INSERT_PERMISSION
			);
			return false;
		}

		return $this->checkHttpResponse($http);
	}

	/**
	 * Download file from cloud service by FileData::id, put contents in FileData::src
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	public function downloadFile(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id', 'mimeType', 'src',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();

		$fileMetaData = $this->getFileMetadataInternal($fileData);
		if($fileMetaData === null)
		{
			$this->errorCollection[] = new Error(
				'Could not decode response as json', self::ERROR_BAD_JSON
			);
			return null;
		}
		$link = $this->getDownloadUrl($fileData, $fileMetaData);
		if(!$link)
		{
			$this->errorCollection[] = new Error(
				'Could not get link for download', self::ERROR_BAD_JSON
			);
			return null;
		}

		@set_time_limit(0);
		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Authorization', "Bearer {$accessToken}");

		if($http->download($link, $fileData->getSrc()) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection[] = new Error(
				$errorString, self::ERROR_HTTP_DOWNLOAD_FILE
			);
			return null;
		}

		if (!$this->checkHttpResponse($http))
		{
			return null;
		}

		//$file['title'] = BaseComponent::convertFromUtf8($file['title']);
		$this->recoverExtensionInName($fileMetaData['name'], $fileData->getMimeType());
		$fileData->setName($fileMetaData['name']);

		return $fileData;
	}

	/**
	 * Repacks exported document from Google.Drive, which has wrong order files in archive to show preview
	 * in Google.Viewer. In Google.Viewer document should have file '[Content_Types].xml' on first position in archive.
	 * @param FileData $fileData
	 * @return FileData
	 * @throws IO\FileNotFoundException
	 * @throws IO\InvalidPathException
	 * @internal
	 */
	public function repackDocument(FileData $fileData)
	{
		if(!extension_loaded('zip'))
		{
			return null;
		}
		if(!TypeFile::isDocument($fileData->getName()))
		{
			return null;
		}
		$file = new IO\File($fileData->getSrc());
		if(!$file->isExists() || $file->getSize() > 15*1024*1024)
		{
			return null;
		}
		unset($file);

		$ds = DIRECTORY_SEPARATOR;
		$targetDir = \CTempFile::getDirectoryName(2, 'disk_repack' . $ds . md5(uniqid('di', true)));
		checkDirPath($targetDir);
		$targetDir = IO\Path::normalize($targetDir) . $ds;

		$zipOrigin = new \ZipArchive();
		if($zipOrigin->open($fileData->getSrc()) !== true)
		{
			return null;
		}
		if($zipOrigin->getNameIndex(0) === '[Content_Types].xml')
		{
			$zipOrigin->close();
			return null;
		}
		if(!$zipOrigin->extractTo($targetDir))
		{
			$zipOrigin->close();
			return null;
		}
		$zipOrigin->close();
		unset($zipOrigin);

		if(is_dir($targetDir) !== true)
		{
			return null;
		}

		$newName = md5(uniqid('di', true));
		$newFilepath = $targetDir . '..' . $ds . $newName;
		$repackedZip = new \ZipArchive;
		if(!$repackedZip->open($newFilepath, \ZipArchive::CREATE))
		{
			return null;
		}
		$source = realpath($targetDir);
		$repackedZip->addFile($source . $ds . '[Content_Types].xml', '[Content_Types].xml');
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST
		);
		foreach($files as $file)
		{
			if($file->getBasename() === '[Content_Types].xml')
			{
				continue;
			}
			$file = str_replace('\\', '/', $file);
			$file = realpath($file);

			if(is_dir($file) === true)
			{
				$repackedZip->addEmptyDir(str_replace('\\', '/', str_replace($source . $ds, '', $file . $ds)));
			}
			elseif(is_file($file) === true)
			{
				$repackedZip->addFile($file, str_replace('\\', '/', str_replace($source . $ds, '', $file)));
			}
		}
		$repackedZip->close();

		$newFileData = new FileData();
		$newFileData->setSrc($newFilepath);

		return $newFileData;
	}

	protected function getDownloadUrl(FileData $fileData, $fileMetaData = array())
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}

		if(!$fileMetaData)
		{
			$fileMetaData = $this->getFileMetadataInternal($fileData);
		}

		if(!$fileMetaData)
		{
			return null;
		}

		if(!$this->isGoogleDocument($fileMetaData['mimeType']))
		{
			return self::API_URL_V3 . "/files/{$fileData->getId()}?alt=media";
		}

		$exportMimeByInternalMimeType = $this->getExportMimeByInternalMimeType($fileMetaData['mimeType']);
		$extensionByMimeType = TypeFile::getExtensionByMimeType($exportMimeByInternalMimeType);
		if (in_array($extensionByMimeType, array('docx')))
		{
			return "https://docs.google.com/document/d/{$fileData->getId()}/export?format=docx";
		}
		if (in_array($extensionByMimeType, array('pptx')))
		{
			return "https://docs.google.com/presentation/d/{$fileData->getId()}/export/pptx";
		}
		if (in_array($extensionByMimeType, array('xlsx')))
		{
			return "https://docs.google.com/spreadsheets/d/{$fileData->getId()}/export?format=xlsx";
		}


		return
			self::API_URL_V3 .
			"/files/{$fileData->getId()}/export?" .
			http_build_query(array('mimeType' => $this->getExportMimeByInternalMimeType($fileMetaData['mimeType'])));
	}

	/**
	 * Download part of file from cloud service by FileData::id, put contents in FileData::src
	 * @param FileData $fileData
	 * @param          $startRange
	 * @param          $chunkSize
	 * @return FileData|null
	 */
	public function downloadPartFile(FileData $fileData, $startRange, $chunkSize)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id', 'mimeType', 'src',
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
		$http->setHeader('Authorization', "Bearer {$accessToken}");

		$endRange = $startRange + $chunkSize - 1;
		$http->setHeader('Range', "bytes={$startRange}-{$endRange}");

		if($http->download($this->getDownloadUrl($fileData), $fileData->getSrc()) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection[] = new Error(
				$errorString, self::ERROR_HTTP_DOWNLOAD_FILE
			);
			return null;
		}

		return $fileData;
	}

	/**
	 * Delete file from cloud service by FileData::id
	 * @param FileData $fileData
	 * @return bool
	 */
	public function deleteFile(FileData $fileData)
	{
		$this->errorCollection->clear();

		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();

		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Authorization', "Bearer {$accessToken}");

		if($http->query('DELETE', self::API_URL_V3 . '/files/' . $fileData->getId()) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection[] = new Error(
				$errorString, self::ERROR_HTTP_DELETE_FILE
			);
			return false;
		}

		return $this->checkHttpResponse($http);
	}

	/**
	 * Shares file to edit for anyone by id.
	 *
	 * @param FileData $fileData
	 * @internal
	 * @return bool
	 */
	public function shareFileToEdit(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return false;
		}

		return $this->insertPermission($fileData, self::PERMISSION_ROLE_WRITER, self::PERMISSION_TYPE_ANYONE);
	}

	/**
	 * Get data for showing preview file.
	 * Array must be contains keys: id, viewUrl, neededDelete, neededCheckView
	 * @param FileData $fileData
	 * @return array|null
	 */
	public function getDataForViewFile(FileData $fileData)
	{
		$newFile = $this->createByResumableUpload($fileData, $lastStatus, $metadata);
		if(!$newFile)
		{
			//retry upload, but not convert content
			if($lastStatus == '500')
			{
				$fileData->setNeedConvert(false);
				$newFile = $this->createByResumableUpload($fileData, $lastStatus, $metadata);
			}
		}

		if($newFile === null)
		{
			$this->errorCollection[] = new Error(
				Loc::getMessage('DISK_GOOGLE_HANDLER_ERROR_COULD_NOT_VIEW_FILE'), self::ERROR_COULD_NOT_VIEW_FILE
			);
			return null;
		}

		$this->insertPermission($newFile, self::PERMISSION_ROLE_READER, self::PERMISSION_TYPE_ANYONE);
		$embedFileLink = $this->getEmbedFileLink($fileData)?: $metadata['webViewLink'];

		if(!empty($metadata['createdTime']))
		{
			$this->patchFile($fileData, array('modifiedTime' => $metadata['createdTime']));
		}

		$updatedMetadata = $this->getFileMetadata($fileData);
		$fileData->setMetaData($updatedMetadata);

		ShowSession::register($this, $fileData, $this->errorCollection);

		return array(
			'id' => $newFile->getId(),
			'viewUrl' => $embedFileLink,
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
		//google changes etag (version or etag in api v2) every time and it doesn't matter content or permission.
		if (isset($currentMetadata['original']['createdTime'], $currentMetadata['original']['modifiedTime']))
		{
			$createdTime = new \DateTime($currentMetadata['original']['createdTime']);
			$modifiedTime = new \DateTime($currentMetadata['original']['modifiedTime']);

			return $modifiedTime->getTimestamp() - $createdTime->getTimestamp() > 3;
		}

		return false;
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
		$http->setHeader('Authorization', "Bearer {$this->getAccessToken()}");

		if(
			$http->get(
				self::API_URL_V3 . "/files?q='{$folderId}'+in+parents+and+trashed=false&" . http_build_query(array(
					'fields' => 'files/id,files/mimeType,files/webViewLink,files/size,files/name,files/version,files/modifiedTime'
				))
			) === false
		)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection[] = new Error(
				$errorString, self::ERROR_HTTP_LIST_FOLDER
			);
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		$items = Json::decode($http->getResult());
		if($items === null)
		{
			$this->errorCollection[] = new Error(
				'Could not decode response as json', self::ERROR_BAD_JSON
			);
			return null;
		}
		if(!isset($items['files']))
		{
			$this->errorCollection[] = new Error(
				'Could not find items in response', self::ERROR_HTTP_LIST_FOLDER
			);
			return null;
		}

		$reformatItems = array();
		foreach($items['files'] as $item)
		{
			$isFolder = $item['mimeType'] === 'application/vnd.google-apps.folder';
			$dateTime = new \DateTime($item['modifiedTime']);
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

			if(!$isFolder && empty($item['size']))
			{
				//Google.Drive doesn't show size of google documents. We should export docs
				$reformatItems[$item['id']]['size'] = $reformatItems[$item['id']]['sizeInt'] = '';
			}

			if(!$isFolder)
			{
				$reformatItems[$item['id']]['ext'] = getFileExtension($item['name']);
			}
		}
		unset($item);

		return $reformatItems;
	}

	public function checkHttpResponse(HttpClient $http)
	{
		$status = (int)$http->getStatus();
		if($status === 401)
		{
			$this->errorCollection[] = new Error(
				'Invalid credentials (401)', self::ERROR_CODE_INVALID_CREDENTIALS
			);
		}
		elseif($status === 403)
		{
			$headers = $http->getHeaders();
			$response = $http->getResult();
			$errorMessage = 'Unknown error';
			if($response && is_string($response))
			{
				$jsonResponse = Json::decode($response);
				if(isset($jsonResponse['error']['message']))
				{
					$errorMessage = $jsonResponse['error']['message'];
				}
				unset($jsonResponse, $response);
			}

			$headerAuthenticate = $headers->get('WWW-Authenticate');
			if(is_string($headerAuthenticate) && mb_strpos($headerAuthenticate, 'insufficient') !== false)
			{
				$this->errorCollection[] = new Error(
					'Insufficient scope (403)', self::ERROR_CODE_INSUFFICIENT_SCOPE
				);
				return false;
			}
			elseif(mb_strpos($errorMessage, 'The authenticated user has not installed the app with client') !== false)
			{
				$this->errorCollection[] = new Error(
					'The authenticated user has not installed the app (403)', self::ERROR_CODE_NOT_INSTALLED_APP
				);
			}
			elseif(mb_strpos($errorMessage, 'The authenticated user has not granted the app') !== false)
			{
				$this->errorCollection[] = new Error(
					'The authenticated user has not granted the app (403)', self::ERROR_CODE_NOT_GRANTED_APP
				);
			}
			elseif(mb_strpos($errorMessage, 'Invalid accessLevel') !== false)
			{
				$this->errorCollection[] = new Error(
					'Invalid accessLevel (403)', self::ERROR_CODE_INVALID_ACCESS_LEVEL
				);
			}
			elseif(mb_strpos($errorMessage, 'is not properly configured as a Google Drive app') !== false)
			{
				$this->errorCollection[] = new Error(
					'The app does not exist or is not properly configured as a Google Drive app (403)', self::ERROR_CODE_APP_NOT_CONFIGURED
				);
			}
			elseif(mb_strpos($errorMessage, 'is blacklisted') !== false)
			{
				$this->errorCollection[] = new Error(
					'The app is blacklisted as a Google Drive app. (403)', self::ERROR_CODE_APP_IN_BLACKLIST
				);
			}
			elseif($errorMessage)
			{
				$this->errorCollection[] = new Error(
					$errorMessage, self::ERROR_CODE_UNKNOWN
				);
			}
		}

		if($this->errorCollection->hasErrors())
		{
			return false;
		}

		return parent::checkHttpResponse($http);
	}

	/**
	 * Gets a file's metadata by ID
	 * @param FileData $fileData
	 * @return array|null
	 */
	private function getFileMetadataInternal(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();
		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "Bearer {$accessToken}");

		if(
			$http->get(
				self::API_URL_V3 . '/files/' . $fileData->getId() .
				'?' . http_build_query(array('fields' => 'id,mimeType,webViewLink,size,name,version,createdTime,modifiedTime'))
			) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection[] = new Error(
				$errorString, self::ERROR_HTTP_GET_METADATA
			);
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		$file = Json::decode($http->getResult());
		if($file === null)
		{
			$this->errorCollection[] = new Error(
				'Could not decode response as json', self::ERROR_BAD_JSON
			);
			return null;
		}

		return $file;
	}

	private function patchFile(FileData $fileData, array $fields)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}

		if(!$this->checkRequiredInputParams($fields, array(
			'modifiedTime',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();
		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "Bearer {$accessToken}");

		$patchData = Json::encode(array(
			'modifiedTime' => $fields['modifiedTime'],
		));
		if(
			$http->query(
				'PATCH',
				self::API_URL_V3 . '/files/' . $fileData->getId() . '?' . http_build_query(array('fields' => 'id,mimeType,webViewLink,size,name,version,createdTime,modifiedTime')),
				$patchData
			) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection[] = new Error(
				$errorString, self::ERROR_HTTP_PATCH
			);
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		$file = Json::decode($http->getResult());
		if($file === null)
		{
			$this->errorCollection[] = new Error(
				'Could not decode response as json', self::ERROR_BAD_JSON
			);
			return null;
		}

		return $file;
	}

	/**
	 * Returns link which we can embed in iframe to view file.
	 * Notice: the method uses api v2 to get embedLink field, because in v3 that field is deleted.
	 *
	 * @param FileData $fileData File data.
	 * @return string|null
	 */
	private function getEmbedFileLink(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();
		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "Bearer {$accessToken}");

		if(
			$http->get(
				self::API_URL_V2 . '/files/' . $fileData->getId() .
				'?' . http_build_query(array('fields' => 'embedLink'))
			) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection[] = new Error(
				$errorString, self::ERROR_HTTP_GET_METADATA
			);
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		$file = Json::decode($http->getResult());
		if($file === null)
		{
			$this->errorCollection[] = new Error(
				'Could not decode response as json', self::ERROR_BAD_JSON
			);
			return null;
		}

		if(empty($file['embedLink']))
		{
			if($fileData->getMimeType() === 'application/pdf')
			{
				return "https://drive.google.com/file/d/{$fileData->getId()}/preview";
			}

			$this->errorCollection[] = new Error(
				'Could not find {embedLink} in response', self::ERROR_EMBED_FILE_LINK
			);
			return null;
		}

		return $file['embedLink'];
	}

	private function getFileSizeInternal($downloadUrl)
	{
		$accessToken = $this->getAccessToken();
		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Authorization', "Bearer {$accessToken}");

		if($http->query('HEAD', $downloadUrl) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection[] = new Error(
				$errorString, self::ERROR_HTTP_GET_METADATA
			);
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		return $http->getHeaders()->get('Content-Length');
	}

	/**
	 * Gets a file's metadata by ID.
	 *
	 * @param FileData $fileData
	 * @return array|null Describes file (id, title, size, mimeType)
	 */
	public function getFileMetadata(FileData $fileData)
	{
		$metaData = $this->getFileMetadataInternal($fileData);
		if(!$metaData)
		{
			return null;
		}
		if(empty($metaData['size']))
		{
			$link = $this->getDownloadUrl($fileData, $metaData);
			if(!$link)
			{
				return null;
			}
			$metaData['size'] = $this->getFileSizeInternal($link);
			if(!$metaData['size'])
			{
				//todo Google is quite bad guy. He does not send Content-Length header when we try to export GoogleDocs.
				$metaData['size'] = 0;
			}
		}

		return $this->normalizeMetadata($metaData);
	}

	public static function getInternalMimeTypeByExtension($ext)
	{
		$ext = trim($ext, '.');
		$googleMimeTypes = array(
			'docx' => 'application/vnd.google-apps.document',
			'xlsx' => 'application/vnd.google-apps.spreadsheet',
			'pptx' => 'application/vnd.google-apps.presentation',
			'doc' => 'application/vnd.google-apps.document',
			'xls' => 'application/vnd.google-apps.spreadsheet',
			'ppt' => 'application/vnd.google-apps.presentation',
		);

		return isset($googleMimeTypes[$ext])? $googleMimeTypes[$ext] : null;
	}

	private function getExportMimeByInternalMimeType($internalMimeType)
	{
		if(!$this->isGoogleDocument($internalMimeType))
		{
			return $internalMimeType;
		}

		$googleMimeTypes = array(
			'application/vnd.google-apps.document' => 'docx',
			'application/vnd.google-apps.spreadsheet' => 'xlsx',
			'application/vnd.google-apps.presentation' => 'pptx',
		);

		$filename = 'f';
		if(isset($googleMimeTypes[$internalMimeType]))
		{
			$filename = $filename . '.' . $googleMimeTypes[$internalMimeType];
		}

		return TypeFile::getMimeTypeByFilename($filename);
	}

	private function isGoogleDocument($mimeType)
	{
		return mb_strpos($mimeType, 'application/vnd.google-apps.') !== false;
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
			'mimeType' => $metaData['mimeType'],
			'etag' => $metaData['version'],
			'original' => $metaData,
		);
	}
}