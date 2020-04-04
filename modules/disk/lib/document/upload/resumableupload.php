<?php

namespace Bitrix\Disk\Document\Upload;

use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\Document\FileData;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

abstract class ResumableUpload implements IErrorable
{
	const CHUNK_SIZE = 10485760; //10Mb

	const ERROR_HTTP_RESUMABLE_UPLOAD        = 'RESUMABLE_UPLOAD_22000';
	const ERROR_HTTP_GET_LOCATION_FOR_UPLOAD = 'RESUMABLE_UPLOAD_22001';
	const ERROR_BAD_JSON                     = 'RESUMABLE_UPLOAD_22002';

	/** @var  ErrorCollection */
	protected $errorCollection;
	/** @var DocumentHandler */
	protected $documentHandler;
	/** @var FileData */
	protected $fileData;
	protected $lastStatus;
	protected $lastResponse;

	public function __construct(DocumentHandler $documentHandler, FileData $fileData)
	{
		$this->errorCollection = new ErrorCollection;
		$this->documentHandler = $documentHandler;
		$this->fileData = $fileData;
	}

	/**
	 * @return bool
	 */
	public function upload()
	{
		if(!$this->checkRequiredInputParams($this->fileData->toArray(), array(
			'src', 'mimeType',
		)))
		{
			return false;
		}

		if(!$this->fileData->getSize())
		{
			$this->fileData->setSize(filesize($this->fileData->getSrc()));
		}

		$chunkSize = self::CHUNK_SIZE;
		$locationForUpload = $this->getLocationForResumableUpload();
		if(!$locationForUpload)
		{
			return false;
		}

		/** @var HttpClient $http */
		$http = null;
		$lastResponseCode = false;
		$fileMetadata = null;
		$lastRange = false;
		$transactionCounter = 0;
		$doExponentialBackoff = false;
		$exponentialBackoffCounter = 0;
		$response = array();

		while ($lastResponseCode === false || $lastResponseCode == 308 || $lastResponseCode == 202)
		{
			$transactionCounter++;

			if ($doExponentialBackoff)
			{
				$sleepFor = pow(2, $exponentialBackoffCounter);
				sleep($sleepFor);
				usleep(rand(0, 1000));
				$exponentialBackoffCounter++;
				if ($exponentialBackoffCounter > 5)
				{
					$this->lastStatus = $http? $http->getStatus() : 0;
					$this->errorCollection[] = new Error(
						"Could not upload part (Exponential back off) ({$this->lastStatus})",
						self::ERROR_HTTP_RESUMABLE_UPLOAD
					);

					return false;
				}
			}

			// determining what range is next
			$rangeStart = $this->getNextStartRange($http);
			$rangeEnd = min($chunkSize, $this->fileData->getSize() - 1);

			if ($rangeStart > 0)
			{
				$rangeEnd = min($rangeStart + $chunkSize, $this->fileData->getSize() - 1);
			}

			$http = new HttpClient(array(
				'socketTimeout' => 10,
				'streamTimeout' => 30,
				'version' => HttpClient::HTTP_1_1,
			));
			$this->setBearer($http);
			$http->setHeader('Content-Length', (string)($rangeEnd - $rangeStart + 1));
			$http->setHeader('Content-Type', $this->fileData->getMimeType());
			$http->setHeader('Content-Range', "bytes {$rangeStart}-{$rangeEnd}/{$this->fileData->getSize()}");

			$toSendContent = file_get_contents($this->fileData->getSrc(), false, null, $rangeStart, ($rangeEnd - $rangeStart + 1));
			if($http->query('PUT', $locationForUpload, $toSendContent))
			{
				$response['headers']['range'] = $http->getHeaders()->get('Range');
			}

			$doExponentialBackoff = false;
			if ($http->getStatus())
			{
				// checking for expired credentials
				if ($http->getStatus() == 401)
				{
					$this->documentHandler->queryAccessToken();
					$lastResponseCode = false;
				}
				else if ($http->getStatus() == 308 || $http->getStatus() == 202)
				{
					// todo: verify x-range-md5 header to be sure
					$lastResponseCode = $http->getStatus();
					$exponentialBackoffCounter = 0;
				}
				else if ($http->getStatus() == 503)
				{ // Google's letting us know we should retry
					$doExponentialBackoff = true;
					$lastResponseCode = false;
				}
				else
				{
					if (in_array($http->getStatus(), array(200, 201)))
					{ // we are done!
						$lastResponseCode = $http->getStatus();
					}
					else
					{
						$this->lastStatus = $http->getStatus();
						$this->errorCollection[] = new Error(
							"Could not upload part ({$this->lastStatus})",
							self::ERROR_HTTP_RESUMABLE_UPLOAD
						);

						return false;
					}
				}
			}
			else
			{
				$doExponentialBackoff = true;
				$lastResponseCode = false;
			}
		}

		if ($lastResponseCode != 200 && $lastResponseCode != 201)
		{
			$this->lastStatus = $http->getStatus();
			$this->errorCollection[] = new Error(
				"Could not upload final part ({$this->lastStatus})",
				self::ERROR_HTTP_RESUMABLE_UPLOAD
			);

			return false;
		}

		$this->lastResponse = null;
		if(isset($http))
		{
			$this->lastResponse = Json::decode($http->getResult());
		}
		if($this->lastResponse === null)
		{
			$this->errorCollection[] = new Error(
				'Could not decode response as json', self::ERROR_BAD_JSON
			);
			return false;
		}

		$this->fillFileDataByResponse($this->fileData, $this->lastResponse);

		return true;
	}

	abstract protected function getNextStartRange(HttpClient $httpClient = null);

	abstract protected function fillFileDataByResponse(FileData $fileData, $response);

	abstract protected function getLocationForResumableUpload();

	abstract protected function setBearer(HttpClient $httpClient);

	/**
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * @inheritdoc
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * @inheritdoc
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * @return int
	 */
	public function getLastStatus()
	{
		return (int)$this->lastStatus;
	}

	/**
	 * @return array
	 */
	public function getLastResponse()
	{
		return $this->lastResponse;
	}

	/**
	 * @return FileData
	 */
	public function getFileData()
	{
		return $this->fileData;
	}

	/**
	 * @param array $inputParams
	 * @param array $required
	 * @return bool
	 */
	protected function checkRequiredInputParams(array $inputParams, array $required)
	{
		foreach ($required as $item)
		{
			if(
				!isset($inputParams[$item]) ||
				(
					!$inputParams[$item] &&
					!(is_string($inputParams[$item]) && $inputParams[$item] !== '') &&
					!($inputParams[$item] === 0)
				)
			)
			{
				$this->errorCollection[] = new Error(
					Loc::getMessage('DISK_DOC_HANDLER_ERROR_REQUIRED_PARAMETER', array('#PARAM#' => $item)),
					DocumentHandler::ERROR_REQUIRED_PARAMETER
				);

				return false;
			}
		}

		return true;
	}

	protected function convertToUtf8($data)
	{
		if (Application::getInstance()->isUtfMode())
		{
			return $data;
		}
		return Encoding::convertEncodingArray($data, SITE_CHARSET, 'UTF-8');
	}
}