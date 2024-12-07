<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Request;

use Bitrix\BIConnector\Integration\Superset\Integrator\Request\Middleware\Queue\AfterRequestQueue;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\Middleware\Queue\BeforeRequestQueue;
use Bitrix\BIConnector\Integration\Superset\Integrator\Sender;
use Bitrix\BIConnector\Integration\Superset\Integrator\Dto;
use Bitrix\Main\Result;

final class IntegratorRequest
{
	private BeforeRequestQueue $beforeRequestMiddlewareQueue;
	private AfterRequestQueue $afterRequestMiddlewareQueue;
	private null|Dto\User $user = null;
	private string $action;
	private bool $isMultipart = false;
	private array $requestParams = [];

	public function __construct(private readonly Sender $sender)
	{
		$this->beforeRequestMiddlewareQueue = new BeforeRequestQueue();
		$this->afterRequestMiddlewareQueue = new AfterRequestQueue();
	}

	public function getSender(): Sender
	{
		return $this->sender;
	}

	public function getParams(): array
	{
		return $this->requestParams;
	}

	public function perform(): IntegratorResponse
	{
		$beforeResult = $this->beforeRequestMiddlewareQueue->execute($this);
		if ($beforeResult)
		{
			if ($this->beforeRequestMiddlewareQueue->isSkipAfterMiddlewares())
			{
				return $beforeResult;
			}

			return $this->afterRequestMiddlewareQueue->execute($this, $beforeResult);
		}

		if ($this->isMultipart)
		{
			$result = $this->sender->performMultipartRequest(
				$this->action,
				$this->requestParams,
				$this->user
			);
		}
		else
		{
			$result = $this->sender->performRequest(
				$this->action,
				$this->requestParams,
				$this->user
			);
		}

		return $this->afterRequestMiddlewareQueue->execute($this, $this->unpackSenderResult($result));
	}

	private function unpackSenderResult(Result $requestResult): IntegratorResponse
	{
		$response = new IntegratorResponse();

		$response->setRequestResult($requestResult);

		if (!$requestResult->isSuccess())
		{
			$errors = $requestResult->getErrors();
			$response->addError(...$errors);
		}

		$responseData = $requestResult->getData();

		$response->setData($responseData['data'] ?? []);
		$response->setStatus($responseData['status'] ?? IntegratorResponse::STATUS_UNKNOWN);

		return $response;
	}

	public function addBefore(Middleware\Base $middleware): self
	{
		$this->beforeRequestMiddlewareQueue->add($middleware);

		return $this;
	}

	public function removeBefore(string $middlewareId): self
	{
		$this->beforeRequestMiddlewareQueue->remove($middlewareId);

		return $this;
	}

	public function removeAfter(string $middlewareId): self
	{
		$this->afterRequestMiddlewareQueue->remove($middlewareId);

		return $this;
	}

	public function addAfter(Middleware\Base $middleware): self
	{
		$this->afterRequestMiddlewareQueue->add($middleware);

		return $this;
	}

	public function getUser(): null|Dto\User
	{
		return $this->user;
	}

	public function setUser(null|Dto\User $user): self
	{
		$this->user = $user;

		return $this;
	}

	public function setAction(string $action): self
	{
		$this->action = $action;

		return $this;
	}

	public function getAction(): string
	{
		return $this->action;
	}

	public function setMultipart(bool $isMultipart): self
	{
		$this->isMultipart = $isMultipart;

		return $this;
	}

	public function setParams(array $params): self
	{
		$this->requestParams = $params;

		return $this;
	}
}
