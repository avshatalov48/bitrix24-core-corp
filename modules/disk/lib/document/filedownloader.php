<?php

namespace Bitrix\Disk\Document;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Web\Http\Method;
use Bitrix\Main\Web\HttpClient;

final class FileDownloader
{
	private const MAX_BACKOFF_COUNTER = 4;

	public readonly string $downloadUrl;
	private HttpClient $httpClient;
	private $exponentialBackoffCounter = 0;
	private $wait = 1000;

	/**
	 * @param string $downloadUrl
	 */
	public function __construct(string $downloadUrl, HttpClient $httpClient = null, )
	{
		$this->downloadUrl = $downloadUrl;
		$this->httpClient = $httpClient ?? new HttpClient();
	}

	public function download(string $method = Method::GET, $entityBody = null): Result
	{
		$tmpFile = $this->getTempPath();

		$result = new Result();
		do
		{
			if ($this->exponentialBackoffCounter > 0)
			{
				$rand = random_int(0, (int)$this->wait/2);
				usleep($rand + (2 ** $this->exponentialBackoffCounter) * $this->wait);
			}

			if (!$this->httpClient->download($this->downloadUrl, $tmpFile, $method, $entityBody))
			{
				$this->exponentialBackoffCounter++;

				continue;
			}

			$status = $this->httpClient->getStatus();
			if ($status === 200)
			{
				$result->setData([
					'file' => $tmpFile,
				]);

				return $result;
			}

			if ($status > 501 && $status < 505)
			{
				$this->exponentialBackoffCounter++;

				continue;
			}

			$result->addError(new Error("Could not download file. Getting {$status}."));

			return $result;
		}
		while ($this->exponentialBackoffCounter <= self::MAX_BACKOFF_COUNTER);

		$result->addError(new Error("Could not download file."));

		return $result;
	}

	private function getTempPath(): string
	{
		$tmpFile = \CTempFile::getFileName(Random::getString(16));
		checkDirPath($tmpFile);

		return $tmpFile;
	}
}