<?php
declare(strict_types=1);

namespace Bitrix\AI\Cloud\HttpClient;

use Bitrix\Main\Web\Http\ResponseBuilderInterface;
use Bitrix\Main\Web\Http\Response;
use Bitrix\Main\Web\HttpHeaders;

/**
 * Class ResponseBuilder
 * Response builder for HTTP client with custom body stream.
 */
final class ResponseBuilder implements ResponseBuilderInterface
{
	/**
	 * @var callable
	 */
	private $readPortionCallback;

	public function setReadPortionCallback(callable $callback): void
	{
		$this->readPortionCallback = $callback;
	}

	public function createFromString(string $response): Response
	{
		$headers = HttpHeaders::createFromString($response);
		$body = $this->createBody();

		return new Response(
			$headers->getStatus(),
			$headers->getHeaders(),
			$body,
			$headers->getVersion(),
			$headers->getReasonPhrase()
		);
	}

	private function createBody(): Stream
	{
		$stream = new Stream('php://temp', 'r+');
		if ($this->readPortionCallback)
		{
			$stream->setReadPortionCallback($this->readPortionCallback);
		}

		return $stream;
	}
}
