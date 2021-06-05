<?php

namespace Bitrix\Disk\Document\OnlyOffice;

use Bitrix\Disk\Document\Contract\FileCreatable;
use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\Document\FileData;
use Bitrix\Disk\Document\IViewer;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Result;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\JWT;

class OnlyOfficeHandler extends DocumentHandler implements FileCreatable, IViewer
{
	public static function getCode()
	{
		return 'onlyoffice';
	}

	public static function getName()
	{
		return 'Bitrix24.Docs';
	}

	public static function getSecretKey(): string
	{
		return Option::get(Driver::INTERNAL_MODULE_ID, 'disk_onlyoffice_secret_key', '');
	}

	public static function isEnabled()
	{
		return (bool)self::getSecretKey();
	}

	/**
	 * Execute this method for check potential possibility get access token.
	 * @return bool
	 */
	public function checkAccessibleTokenService()
	{
		return self::isEnabled();
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
	 * Requests and store access token (self::accessToken) for self::userId
	 * @return $this
	 */
	public function queryAccessToken()
	{
		return $this;
	}

	/**
	 * Creates new blank file in cloud service.
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
		return $fileData;
	}

	/**
	 * Returns url root for API.
	 *
	 * @return string
	 */
	protected static function getApiUrlRoot()
	{
		$server = rtrim(Option::get(Driver::INTERNAL_MODULE_ID, 'disk_onlyoffice_server', ''), '/');

		return $server;
	}

	/**
	 * Downloads file from cloud service by FileData::id, put contents in FileData::src
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	public function downloadFile(FileData $fileData)
	{
	}

	/**
	 * Gets a file's metadata by ID.
	 *
	 * @param FileData $fileData
	 * @return array|null Describes file (id, title, size)
	 */
	public function getFileMetadata(FileData $fileData)
	{
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
	}

	/**
	 * Deletes file from cloud service by FileData::id
	 * @param FileData $fileData
	 * @return bool
	 */
	public function deleteFile(FileData $fileData)
	{
	}

	/**
	 * Get data for showing preview file.
	 * Array must be contains keys: id, viewUrl, neededDelete, neededCheckView
	 * @param FileData $fileData
	 * @return array|null
	 */
	public function getDataForViewFile(FileData $fileData)
	{
		$this->errorCollection[] = new Error('Could not use preview');

		return null;
	}

	public static function isValidToken(string $token): Result
	{
		$result = new Result();

		$http = new HttpClient([
			'socketTimeout' => 5,
			'streamTimeout' => 5,
			'version' => HttpClient::HTTP_1_1,
		]);

		$url = self::getApiUrlRoot() . '/coauthoring/CommandService.ashx';

		$postBody = ['c' => 'version'];
		$http->setHeader('Content-Type', 'application/json');
		$postFields = Json::encode($postBody);

		if ($http->post($url, $postFields) === false)
		{
			return $result->addError(new Main\Error('Server is not available.'));
		}
		if ($http->getStatus() !== 200)
		{
			return $result->addError(new Main\Error('Server is not available. Status ' . $http->getStatus()));
		}

		$response = Json::decode($http->getResult());
		if (isset($response['version']))
		{
			return $result->addError(new Main\Error('JSON Web Token is disabled on server. Please turn on this feature. https://api.onlyoffice.com/editors/signature/'));
		}

		$http = new HttpClient([
			'socketTimeout' => 5,
			'streamTimeout' => 5,
			'version' => HttpClient::HTTP_1_1,
		]);
		$http->setHeader('Content-Type', 'application/json');
		$http->setHeader('Authorization', 'Bearer ' . JWT::encode($postBody, $token));

		$postFields = Json::encode($postBody);
		if ($http->post($url, $postFields) === false)
		{
			return $result->addError(new Main\Error('Server is not available.'));
		}
		if ($http->getStatus() !== 200)
		{
			return $result->addError(new Main\Error('Server is not available. Status ' . $http->getStatus()));
		}

		$response = Json::decode($http->getResult());
		if (isset($response['error']) && $response['error'] !== 0)
		{
			return $result->addError(new Main\Error('Secret key is invalid. Please fix it, follow https://api.onlyoffice.com/editors/signature/'));
		}

		$result->setData([
			'version' => $response['version'],
		]);

		return $result;
	}

	public static function saveForceDocument(string $documentKey): Result
	{
		$result = new Result();

		$http = new HttpClient([
			'socketTimeout' => 5,
			'streamTimeout' => 10,
			'version' => HttpClient::HTTP_1_1,
		]);

		$postBody = [
			'c' => 'forcesave',
			'key' => $documentKey,
		];
		$http->setHeader('Content-Type', 'application/json');
		$http->setHeader('Authorization', 'Bearer ' . JWT::encode($postBody, self::getSecretKey()));

		$url = self::getApiUrlRoot() . '/coauthoring/CommandService.ashx';
		$postFields = Json::encode($postBody);
		if ($http->post($url, $postFields) === false)
		{
			return $result->addError(new Main\Error('Server is not available.'));
		}
		if ($http->getStatus() !== 200)
		{
			return $result->addError(new Main\Error('Server is not available. Status ' . $http->getStatus()));
		}

		$response = Json::decode($http->getResult());
		if (isset($response['error']) && $response['error'] !== 0)
		{
			return $result->addError(new Main\Error("Server sent error code {{$response['error']}}"));
		}

		return $result;
	}
}