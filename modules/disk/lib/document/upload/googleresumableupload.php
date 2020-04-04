<?php

namespace Bitrix\Disk\Document\Upload;

use Bitrix\Disk\Document\FileData;
use Bitrix\Disk\Document\GoogleHandler;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Web\HttpClient;
/**
 * @property GoogleHandler $documentHandler
 */
class GoogleResumableUpload extends ResumableUpload
{
	public function __construct(GoogleHandler $documentHandler, FileData $fileData)
	{
		parent::__construct($documentHandler, $fileData);
	}

	protected function getLocationForResumableUpload()
	{
		if(!$this->checkRequiredInputParams($this->fileData->toArray(), array(
			'name', 'mimeType', 'size',
		)))
		{
			return null;
		}

		$fileName = $this->fileData->getName();
		$fileName = $this->convertToUtf8($fileName);

		$http = new HttpClient(array(
			'redirect' => false,
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$this->setBearer($http);
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('X-Upload-Content-Type', $this->fileData->getMimeType());
		$http->setHeader('X-Upload-Content-Length', $this->fileData->getSize());

		$postFields = "{\"name\":\"{$fileName}\"}";
		if($this->fileData->isNeededToConvert())
		{
			$googleMimeType = GoogleHandler::getInternalMimeTypeByExtension(getFileExtension($this->fileData->getName()));
			$postFields = "{\"name\":\"{$fileName}\", \"mimeType\": \"{$googleMimeType}\"}";
		}
		if($http->post(GoogleHandler::API_URL_UPLOAD_V3 . '/files?uploadType=resumable&fields=id,webViewLink,version,createdTime,modifiedTime', $postFields) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection[] = new Error(
				$errorString, self::ERROR_HTTP_GET_LOCATION_FOR_UPLOAD
			);
			return null;
		}

		if(!$this->documentHandler->checkHttpResponse($http))
		{
			return null;
		}

		return $http->getHeaders()->get('Location');
	}

	protected function getNextStartRange(HttpClient $httpClient = null)
	{
		if (!$httpClient)
		{
			return 0;
		}

		if ($httpClient->getStatus() != 308)
		{
			return 0;
		}

		$range = $httpClient->getHeaders()->get('Range');
		if (!$range)
		{
			return 0;
		}

		$ranges = explode('-', $range);

		return (int)$ranges[1] + 1;
	}

	protected function setBearer(HttpClient $httpClient)
	{
		$httpClient->setHeader('Authorization', "Bearer {$this->documentHandler->getAccessToken()}");
	}

	protected function fillFileDataByResponse(FileData $fileData, $response)
	{
		$fileData
			->setMetaData($this->documentHandler->normalizeMetadata($response))
			->setLinkInService($response['webViewLink'])
			->setId($response['id'])
		;

		return $fileData;
	}
}