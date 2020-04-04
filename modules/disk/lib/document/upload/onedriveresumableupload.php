<?php

namespace Bitrix\Disk\Document\Upload;

use Bitrix\Disk\Document\FileData;
use Bitrix\Disk\Document\OneDriveHandler;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

/**
 * @property OneDriveHandler $documentHandler
 */
class OneDriveResumableUpload extends ResumableUpload
{
	const SUFFIX_TO_CREATE_UPLOAD_SESSION = OneDriveHandler::SUFFIX_TO_CREATE_UPLOAD_SESSION;

	/** @var string */
	protected $uploadPath;

	public function __construct(OneDriveHandler $documentHandler, FileData $fileData)
	{
		parent::__construct($documentHandler, $fileData);
	}

	public function setUploadPath($uploadPath)
	{
		$this->uploadPath = $uploadPath;

		return $this;
	}

	protected function getPostFieldsForUpload(FileData $fileData)
	{
		return array(
			'item' => array('@name.conflictBehavior' => 'rename')
		);
	}

	protected function getLocationForResumableUpload()
	{
		if(!$this->checkRequiredInputParams($this->fileData->toArray(), array(
			'name', 'size',
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
		$this->setBearer($http);
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');

		$postFields = json_encode($this->getPostFieldsForUpload($this->fileData));
		if($http->post($this->uploadPath . static::SUFFIX_TO_CREATE_UPLOAD_SESSION, $postFields) === false)
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

		$responseData = Json::decode($http->getResult());
		if($responseData === null)
		{
			$this->errorCollection->add(array(
				new Error('Could not decode response as json', self::ERROR_BAD_JSON)
			));
			return null;
		}


		return $responseData['uploadUrl'];
	}

	protected function getNextStartRange(HttpClient $httpClient = null)
	{
		if (!$httpClient)
		{
			return 0;
		}

		if ($httpClient->getStatus() != 202)
		{
			return 0;
		}

		$result = Json::decode($httpClient->getResult());
		if (!$result['nextExpectedRanges'])
		{
			return 0;
		}

		$ranges = explode('-', reset($result['nextExpectedRanges']));

		return (int)$ranges[0];
	}

	protected function setBearer(HttpClient $httpClient)
	{
		$httpClient->setHeader('Authorization', "bearer {$this->documentHandler->getAccessToken()}");
	}

	protected function fillFileDataByResponse(FileData $fileData, $response)
	{
		$fileData
			->setId($response['id'])
			->setLinkInService($response['webUrl'])
			->setMetaData($this->documentHandler->normalizeMetadata($response))
		;

		return $fileData;
	}
}