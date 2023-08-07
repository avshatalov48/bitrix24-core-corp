<?php

declare(strict_types = 1);

namespace Bitrix\Mobile\UI\DetailCard;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Contract\FallbackActionInterface;
use Bitrix\Main\Engine\JsonController;
use Bitrix\Main\Error;

abstract class Controller extends JsonController implements FallbackActionInterface
{
	protected const ADD_INTERNAL_ACTION = 'addInternal';
	protected const UPDATE_INTERNAL_ACTION = 'updateInternal';
	protected const LOAD_ACTION = 'load';
	protected const LOAD_TAB_CONFIG_ACTION = 'loadTabConfig';

	protected const NON_CRITICAL_ERROR_DATA_KEY = 'NON_CRITICAL';

	protected function init()
	{
		parent::init();

		define('BX_MOBILE', true);
	}

	protected function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\Csrf(),
			new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
			new ActionFilter\ContentType([ActionFilter\ContentType::JSON]),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
		];
	}

	public function configureActions(): array
	{
		return [
			self::LOAD_TAB_CONFIG_ACTION => [
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
		];
	}

	final public function fallbackAction($actionName)
	{
		if ($actionName === self::LOAD_ACTION)
		{
			$actionName = $this->prepareTabLoadName();
		}

		return $this->forward($this, $actionName);
	}

	public static function getTabActionName(string $tabId): string
	{
		return mb_strtolower(self::LOAD_ACTION . $tabId);
	}

	protected function findInSourceParametersList(string $key)
	{
		foreach ($this->getSourceParametersList() as $list)
		{
			if (is_array($list) && array_key_exists($key, $list))
			{
				return $list[$key];
			}
		}

		return null;
	}

	private function prepareTabLoadName(): string
	{
		$tabId = $this->findInSourceParametersList('tabId');
		if (!$tabId)
		{
			throw new \DomainException("Empty tab id for load action.");
		}

		$actionName = self::getTabActionName($tabId);

		if (!in_array($actionName, $this->listNameActions(), true))
		{
			throw new \DomainException("Action {{$actionName}} not found.");
		}

		return $actionName;
	}

	/**
	 * @return array|null
	 */
	public function updateAction(): ?array
	{
		$entityId = $this->forward($this, self::UPDATE_INTERNAL_ACTION);
		if ($this->getCriticalErrors())
		{
			return null;
		}

		return [
			'entityId' => $entityId,
			'load' => $this->createLoadResponse(),
			'title' => $this->getEntityTitle(),
			'header' => $this->getEntityHeader(),
		];
	}

	/**
	 * @return array|null
	 */
	public function addAction(): ?array
	{
		$entityId = $this->forward($this, self::ADD_INTERNAL_ACTION);
		if ($this->getCriticalErrors())
		{
			return null;
		}

		$this->setSourceParametersList(array_merge(
			[
				[
					'entityId' => $entityId,
					'copy' => false,
				],
			],
			$this->getSourceParametersList(),
		));

		return [
			'entityId' => $entityId,
			'load' => $this->createLoadResponse(),
			'title' => $this->getEntityTitle(),
			'header' => $this->getEntityHeader(),
		];
	}

	/**
	 * @return string[]
	 */
	abstract public function getTabIds(): array;

	/**
	 * @return array
	 */
	protected function createLoadResponse(): array
	{
		$result = [];

		$closeOnSave = $this->findInSourceParametersList('closeOnSave');
		if ($closeOnSave)
		{
			return $result;
		}

		$loadedTabs = $this->findInSourceParametersList('loadedTabs');

		foreach ($this->getTabIds() as $tabId)
		{
			if ($loadedTabs !== null && !in_array($tabId, $loadedTabs, true))
			{
				continue;
			}

			$result[] = [
				'id' => $tabId,
				'result' => $this->forward($this, self::LOAD_ACTION . $tabId),
			];
		}

		return $result;
	}

	abstract protected function getEntityTitle(): string;

	protected function getEntityHeader(): ?array
	{
		return null;
	}

	/**
	 * @return Error[]
	 */
	protected function getCriticalErrors(): array
	{
		return array_filter(
			$this->getErrors(),
			static function ($error) {
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
