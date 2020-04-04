<?php

namespace Bitrix\Disk\Document;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\SpecificFolder;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\IO;

Loc::loadMessages(__FILE__);

class MyOfficeHandler extends DocumentHandler
{
	const API_URL_V1 = 'https://coapi.myoffice.ru/api/v1';

	const ERROR_NOT_INSTALLED_SOCSERV     = 'DISK_MYOFFICE_HANDLER_22002';
	const ERROR_BAD_JSON                  = 'DISK_MYOFFICE_HANDLER_22005';
	const ERROR_HTTP_DELETE_FILE          = 'DISK_MYOFFICE_HANDLER_22006';
	const ERROR_HTTP_DOWNLOAD_FILE        = 'DISK_MYOFFICE_HANDLER_22007';
	const ERROR_HTTP_GET_METADATA         = 'DISK_MYOFFICE_HANDLER_22008';
	const ERROR_HTTP_FILE_INTERNAL        = 'DISK_MYOFFICE_HANDLER_22009';
	const ERROR_COULD_NOT_VIEW_FILE       = 'DISK_MYOFFICE_HANDLER_22013';
	const ERROR_SHARED_EDIT_LINK          = 'DISK_MYOFFICE_HANDLER_22014';
	const ERROR_SHARED_EMBED_LINK         = 'DISK_MYOFFICE_HANDLER_22015';
	const ERROR_COULD_NOT_FIND_EMBED_LINK = 'DISK_MYOFFICE_HANDLER_22016';
	const ERROR_HTTP_LIST_FOLDER          = 'DISK_MYOFFICE_HANDLER_22017';

	/**
	 * @inheritdoc
	 */
	public static function getCode()
	{
		return 'myoffice';
	}

	/**
	 * @inheritdoc
	 */
	public static function getName()
	{
		return Loc::getMessage('DISK_MYOFFICE_HANDLER_NAME');
	}

	/**
	 * Public name storage of documents. May show in user interface.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getStorageName()
	{
		return Loc::getMessage('DISK_MYOFFICE_HANDLER_NAME_STORAGE');
	}

	/**
	 * Execute this method for check potential possibility get access token.
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function checkAccessibleTokenService()
	{
		return MyOfficeHandler::isEnabled();
	}


	/**
	 * Return link for authorize user in external service.
	 * @param string $mode
	 * @return string
	 */
	public function getUrlForAuthorizeInTokenService($mode = 'modal')
	{
		return '';
	}

	/**
	 * Only for test.
	 * @internal
	 * @return bool
	 */
	public static function isEnabled()
	{
		return Option::get('disk', 'demo_myoffice', false) == true;
	}

	/**
	 * Only for test.
	 * @param int $userId User id.
	 * @internal
	 * @return null
	 */
	public static function getPredefinedUser($userId)
	{
		$users = Option::get('disk', 'demo_myoffice_users', false);
		if(!$users)
		{
			return null;
		}

		$predefinedUsers = unserialize($users);
		if (empty($predefinedUsers[$userId]))
		{
			return null;
		}

		return $predefinedUsers[$userId];
	}

	/**
	 * Request and store access token (self::accessToken) for self::userId
	 * @return $this
	 */
	public function queryAccessToken()
	{
		list($login, $password) = self::getPredefinedUser($this->userId);

		if (!$login)
		{
			return $this;
		}

		$http = new HttpClient(array(
			'redirect' => false,
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));

		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');

		$postFields = Json::encode(array(
			'login' => $login,
			'password' => $password,
		));

		if ($http->post('https://auth.myoffice.ru/login', $postFields) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection[] = new Error(
				$errorString, self::ERROR_CODE_INVALID_CREDENTIALS
			);

			return $this;
		}

		if(!$this->checkHttpResponse($http))
		{
			return $this;
		}

		$token = Json::decode($http->getResult());
		if($token === null)
		{
			$this->errorCollection[] = new Error(
				'Could not decode response as json', self::ERROR_BAD_JSON
			);
			return $this;
		}

		$this->accessToken = $token['token'];

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
		return $this->createFile($fileData);
	}

	/**
	 * @param FileData $fileData
	 * @return FileData
	 */
	public function createFile(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'name', 'src',
		)))
		{
			return null;
		}

		$fileName = $fileData->getName();
		$fileName = $this->convertToUtf8($fileName);
		$file = new IO\File(IO\Path::convertPhysicalToLogical($fileData->getSrc()));

		$http = new HttpClient(array(
			'redirect' => false,
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('X-co-auth-token', $this->getAccessToken());
		$http->setHeader('Content-type', $fileData->getMimeType());


		if(
			$http->post(
				$this->getApiUrlRoot() . "/files/upload",
				array(
					'file' => array(
						'filename' => $fileName,
						'resource' => $file->open('r'),
						'contentType' => TypeFile::getMimeTypeByFilename($fileName),
					),
					'conflictStrategy' => 'keep_both'
				),
				true
			) === false
		)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_FILE_INTERNAL)
			));
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		$finalOutput = Json::decode($http->getResult());
		if($finalOutput === null)
		{
			$this->errorCollection->add(array(
				new Error('Could not decode response as json', self::ERROR_BAD_JSON)
			));
			return null;
		}
		$fileData
			->setId($finalOutput['file']['id'])
			->setLinkInService($this->getEditLink($finalOutput['links']))
			->setMetaData($this->normalizeMetadata($finalOutput))
		;

		return $fileData;
	}

	private function getEditLink(array $links)
	{
		foreach ($links as $link)
		{
			if ($link['rel'] === 'edit')
			{
				return $link['href'];
			}
		}

		return null;
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

		@set_time_limit(0);
		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));

		$http->setHeader('X-co-auth-token', $this->getAccessToken());

		if($startRange !== null && $chunkSize !== null)
		{
			$endRange = $startRange + $chunkSize - 1;
			$http->setHeader('Range', "bytes={$startRange}-{$endRange}");
		}

		if($http->download($this->getApiUrlRoot() . "/files/{$fileData->getId()}/content", $fileData->getSrc()) === false)
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
		$http->setHeader('X-co-auth-token', $this->getAccessToken());

		if($http->get($this->getApiUrlRoot() . "/files/{$fileData->getId()}") === false)
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
				new Error('Could not get meta-data', self::ERROR_HTTP_GET_METADATA)
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

		$http->setHeader('X-co-auth-token', $this->getAccessToken());

		if($http->post($this->getApiUrlRoot() . "/files/{$fileData->getId()}/trash") === false)
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
		$this->errorCollection[] = new Error('Could not use preview with MyOffice', self::ERROR_COULD_NOT_VIEW_FILE);
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
			$folderId = '';
		}
		else
		{
			$folderId = $folderId . '/children';
		}

		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('X-co-auth-token', $this->getAccessToken());

		if($http->get($this->getApiUrlRoot() . "/files/{$folderId}") === false)
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

		$reformatItems = array();
		foreach($items as $item)
		{
			$item = $item['file'];
			$isFolder = $item['mediaType'] === 'application/vnd.ncloudtech.cloudoffice.folder';
			$dateTime = new \DateTime($item['modifiedDate']);
			$reformatItems[$item['id']] = array(
				'id' => $item['id'],
				'name' => $item['filename'],
				'type' => $isFolder? 'folder' : 'file',

				'size' => $isFolder? '' : \CFile::formatSize($item['fileSize']),
				'sizeInt' => $isFolder? '' : $item['fileSize'],
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

	/**
	 * Returns normalized metadata.
	 *
	 * @param array $metaData
	 * @return array
	 */
	protected function normalizeMetadata($metaData)
	{
		return array(
			'id' => $metaData['file']['id'],
			'name' => $metaData['file']['filename'],
			'size' => $metaData['file']['fileSize'],
			'mimeType' => $metaData['file']['mediaType'],
			'etag' => $metaData['file']['checksum'],
			'original' => $metaData,
		);
	}
}