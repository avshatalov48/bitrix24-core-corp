<?php

namespace Bitrix\Disk\Document;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\SpecificFolder;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

class YandexDiskHandler extends DocumentHandler
{
	const API_URL_V1 = 'https://cloud-api.yandex.net/v1/disk';

	const SPECIFIC_FOLDER_CODE = SpecificFolder::CODE_FOR_IMPORT_YANDEX;

	const ERROR_METHOD_IS_NOT_SUPPORTED   = 'DISK_YD_HANDLER_22001';
	const ERROR_NOT_INSTALLED_SOCSERV     = 'DISK_YD_HANDLER_22002';
	const ERROR_BAD_JSON                  = 'DISK_YD_HANDLER_22005';
	const ERROR_HTTP_DELETE_FILE          = 'DISK_YD_HANDLER_22006';
	const ERROR_HTTP_DOWNLOAD_FILE        = 'DISK_YD_HANDLER_22007';
	const ERROR_HTTP_GET_METADATA         = 'DISK_YD_HANDLER_22008';
	const ERROR_HTTP_FILE_INTERNAL        = 'DISK_YD_HANDLER_22009';
	const ERROR_COULD_NOT_VIEW_FILE       = 'DISK_YD_HANDLER_22013';
	const ERROR_SHARED_EDIT_LINK          = 'DISK_YD_HANDLER_22014';
	const ERROR_SHARED_EMBED_LINK         = 'DISK_YD_HANDLER_22015';
	const ERROR_COULD_NOT_FIND_EMBED_LINK = 'DISK_YD_HANDLER_22016';
	const ERROR_HTTP_LIST_FOLDER          = 'DISK_YD_HANDLER_22017';

	/**
	 * @inheritdoc
	 */
	public static function getCode()
	{
		return 'yandexdisk';
	}

	/**
	 * @inheritdoc
	 */
	public static function getName()
	{
		return Loc::getMessage('DISK_YANDEX_DISK_HANDLER_NAME');
	}

	/**
	 * Public name storage of documents. May show in user interface.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getStorageName()
	{
		return static::getName();
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
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_YANDEX_HANDLER_ERROR_NOT_INSTALLED_SOCSERV'), self::ERROR_NOT_INSTALLED_SOCSERV)));
			return false;
		}
		$authManager = new \CSocServAuthManager();
		$socNetServices = $authManager->getActiveAuthServices(array());

		return !empty($socNetServices[\CSocServYandexAuth::ID]);
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
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_YANDEX_HANDLER_ERROR_NOT_INSTALLED_SOCSERV'), self::ERROR_NOT_INSTALLED_SOCSERV)));
			return false;
		}

		$yandexOAuth = new \CSocServYandexAuth($this->userId);
		if($mode === 'opener')
		{
			return $yandexOAuth->getUrl(
				'opener',
				null,
				array('BACKURL' => '#external-auth-ok')
			);
		}

		return $yandexOAuth->getUrl('modal');
	}

	/**
	 * Request and store access token (self::accessToken) for self::userId
	 * @return $this
	 */
	public function queryAccessToken()
	{
		if(!Loader::includeModule('socialservices'))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_YANDEX_HANDLER_ERROR_NOT_INSTALLED_SOCSERV'), self::ERROR_NOT_INSTALLED_SOCSERV)));
			return false;
		}

		$yandexOAuth = new \CSocServYandexAuth($this->userId);
		//this bug. SocServ fill entityOAuth in method getUrl.....
		$yandexOAuth->getUrl('modal');
		$this->accessToken = $yandexOAuth->getStorageToken();

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
		$this->errorCollection->add(array(
			new Error(Loc::getMessage('DISK_YANDEX_DISK_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		));
		return null;
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
		$this->errorCollection->add(array(
			new Error(Loc::getMessage('DISK_YANDEX_DISK_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		));
		return null;
	}

	/**
	 * Download file from cloud service by FileData::id, put contents in FileData::src
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	public function downloadFile(FileData $fileData)
	{
		$this->errorCollection->add(array(
			new Error(Loc::getMessage('DISK_YANDEX_DISK_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		));
		return null;
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

		$fileId = $this->getForApiDecodedId($fileData->getId());

		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "OAuth {$this->getAccessToken()}");

		if($http->get(self::API_URL_V1 . "/resources?path={$fileId}") === false)
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

		return $this->normalizeMetadata($metaData);
	}

	public function getDownloadUrl(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}

		$fileId = $this->getForApiDecodedId($fileData->getId());

		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "OAuth {$this->getAccessToken()}");

		if($http->get(self::API_URL_V1 . "/resources/download?path={$fileId}") === false)
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

		$downloadData = Json::decode($http->getResult());
		if($downloadData === null || empty($downloadData['href']))
		{
			$this->errorCollection->add(array(
				new Error('Could not decode response as json', self::ERROR_BAD_JSON)
			));
			return null;
		}

		return $downloadData['href'];
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

		$http->setHeader('Authorization', "OAuth {$accessToken}");

		$endRange = $startRange + $chunkSize - 1;
		$http->setHeader('Range', "bytes={$startRange}-{$endRange}");

		if($http->download($this->getDownloadUrl($fileData), $fileData->getSrc()) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_DOWNLOAD_FILE)
			));
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
		$this->errorCollection->add(array(
			new Error(Loc::getMessage('DISK_YANDEX_DISK_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		));
		return null;
	}


	/**
	 * Get url for showing preview file.
	 * @param FileData $fileData
	 * @return string|null
	 */
	public function getDataForViewFile(FileData $fileData)
	{
		$this->errorCollection->add(array(
			new Error(Loc::getMessage('DISK_YANDEX_DISK_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		));
		return null;
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
			$folderId = urlencode('/');
		}
		else
		{
			$folderId = $this->getForApiDecodedId($folderId);
		}

		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "OAuth {$this->getAccessToken()}");

		if($http->get(self::API_URL_V1 . "/resources?path={$folderId}&limit=200") === false)
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
		if(!isset($items['_embedded']['items']))
		{
			$this->errorCollection->add(array(
				new Error('Could not find items in response', self::ERROR_HTTP_LIST_FOLDER)
			));
			return null;
		}

		$reformatItems = array();
		foreach($items['_embedded']['items'] as $item)
		{
			$isFolder = $item['type'] === 'dir';
			$dateTime = new \DateTime($item['modified']);
			$itemPath = substr($item['path'], 5); //disk:
			$pseudoId = base64_encode($itemPath);
			$reformatItems[$pseudoId] = array(
				'id' => $pseudoId,
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
				$reformatItems[$pseudoId]['storage'] = '';
				$reformatItems[$pseudoId]['ext'] = getFileExtension($item['name']);
			}
		}
		unset($item);

		return $reformatItems;
	}

	protected function getEncodedId($path)
	{
		return base64_encode($path);
	}

	protected function getDecodedId($path)
	{
		return base64_decode($path);
	}

	protected function getForApiDecodedId($path)
	{
		return Driver::getInstance()->getUrlManager()->encodeUrn($this->getDecodedId($path));
	}

	/**
	 * Returns normalized metadata.
	 *
	 * @param array $metaData
	 * @return array
	 */
	protected function normalizeMetadata($metaData)
	{
		return array(
			'id' => $this->getEncodedId($metaData['path']),
			'name' => $metaData['name'],
			'size' => $metaData['size'],
			'mimeType' => $metaData['mime_type'],
			'etag' => $metaData['md5'],
			'original' => $metaData,
		);
	}
}