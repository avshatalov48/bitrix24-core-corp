<?php

declare(strict_types = 1);

namespace Bitrix\Mobile\UI\DetailCard;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\JsonController;
use Bitrix\Main\Engine\JsonPayload;
use Bitrix\Main\Error;

abstract class Controller extends JsonController
{
	private const NON_CRITICAL_ERROR_DATA_KEY = 'NON_CRITICAL';

	protected function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\Csrf(),
			new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
			new ActionFilter\ContentType([ActionFilter\ContentType::JSON]),
		];
	}

	public function configureActions(): array
	{
		return [
			'load' => [
				'+prefilters' => [
					new ActionFilter\CloseSession(),
					new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
				],
			],
		];
	}

	/**
	 * @return string[]
	 */
	abstract public function getLoadActionsList(): array;

	public static function getTabActionName(string $tabId): string
	{
		return mb_strtolower("load{$tabId}");
	}

	public function loadAction(JsonPayload $payload): array
	{
		$data = $payload->getData() ?? [];
		$tabId = (string)($data['tabId'] ?? '');
		$action = static::getTabActionName($tabId);

		if ($tabId === '' || !in_array($action, $this->listNameActions(), true))
		{
			throw new \DomainException("Action {{$action}} not found.");
		}

		$parameters = [
			'params' => (array)($data['parameters'] ?? [])
		];

		return $this->forward($this, $action, $parameters);
	}

	/**
	 * @param JsonPayload $payload
	 * @return array|null
	 */
	public function updateAction(JsonPayload $payload): ?array
	{
		[$parameters, $data] = $this->extractSavePayload($payload);

		$entityId = $this->update($parameters, $data);
		if ($this->getCriticalErrors())
		{
			return null;
		}

		return [
			'id' => $entityId,
			'load' => $this->createLoadResponse($parameters),
			'title' => $this->getEntityTitle($entityId),
		];
	}

	/**
	 * @param array $parameters
	 * @param array $data
	 * @return int|null
	 */
	abstract protected function update(array $parameters, array $data): ?int;

	/**
	 * @param JsonPayload $payload
	 * @return array|null
	 */
	public function addAction(JsonPayload $payload): ?array
	{
		[$parameters, $data] = $this->extractSavePayload($payload);

		$entityId = $this->add($parameters, $data);
		if ($this->getCriticalErrors())
		{
			return null;
		}

		$parameters = ['id' => $entityId];

		return [
			'id' => $entityId,
			'params' => $parameters,
			'load' => $this->createLoadResponse($parameters),
			'title' => $this->getEntityTitle($entityId),
		];
	}

	/**
	 * @param array $parameters
	 * @param array $data
	 * @return int|null
	 */
	abstract protected function add(array $parameters, array $data): ?int;

	/**
	 * @param JsonPayload $payload
	 * @return array
	 */
	protected function extractSavePayload(JsonPayload $payload): array
	{
		$data = $payload->getData() ?? [];

		$parameters = $data['parameters'] ?? [];
		$data = $data['data'] ?? [];

		return [$parameters, $data];
	}

	/**
	 * @param array $parameters
	 * @return array
	 */
	protected function createLoadResponse(array $parameters): array
	{
		$result = [];

		$loadActionsList = $this->getLoadActionsList();
		foreach ($loadActionsList as $loadAction)
		{
			$result[] = [
				'id' => $loadAction,
				'result' => $this->forward(
					static::class,
					'load' . ucfirst($loadAction),
					[
						'params' => $parameters,
					]
				),
			];
		}

		return $result;
	}

	/**
	 * @param int $entityId
	 * @return string
	 */
	abstract protected function getEntityTitle(int $entityId): string;

	/**
	 * @return Error[]
	 */
	protected function getCriticalErrors(): array
	{
		return array_filter(
			$this->getErrors(),
			function ($error)
			{
				$customData = $error->getCustomData();

				return !(
					isset($customData[self::NON_CRITICAL_ERROR_DATA_KEY])
					&& $customData[self::NON_CRITICAL_ERROR_DATA_KEY] === true
				);
			}
		);
	}

	/**
	 * @param Error $error
	 */
	protected function addNonCriticalError(Error $error): void
	{
		$this->addError(new Error(
			$error->getMessage(),
			$error->getCode(),
			array_merge(
				[
					self::NON_CRITICAL_ERROR_DATA_KEY => true,
				],
				($error->getCustomData() ?? [])
			)
		));
	}

	/**
	 * @param array $errors
	 */
	protected function addNonCriticalErrors(array $errors): void
	{
		foreach ($errors as $error)
		{
			$this->addNonCriticalError($error);
		}
	}
}
