<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1;

use Bitrix\Booking\Rest\V1\Controller\Filter\BookingDisabled;
use Bitrix\Booking\Rest\V1\View\ViewManager;
use Bitrix\Main;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\SystemException;
use Bitrix\Rest\Integration\Externalizer;
use Bitrix\Rest\Integration\Internalizer;

class Controller extends Main\Engine\Controller
{
	private Converter $converterToRestFields;
	protected ViewManager $viewManager;

	public function __construct()
	{
		$this->converterToRestFields = new Converter(
			Converter::KEYS
			| Converter::TO_UPPER
			| Converter::TO_SNAKE
			| Converter::RECURSIVE
		);

		parent::__construct();
	}
	protected function getDefaultPreFilters(): array
	{
		return [
			...parent::getDefaultPreFilters(),
			new Main\Engine\ActionFilter\Scope(Main\Engine\ActionFilter\Scope::REST),
			new BookingDisabled(),
		];
	}

	/**
	 * @throws SystemException
	 */
	protected function create($actionName): Main\Engine\InlineAction|Main\Engine\FallbackAction|Action|null
	{
		$action = parent::create($actionName);
		if (!$action)
		{
			return null;
		}

		$this->viewManager = $this->createViewManager($action);

		return $action;
	}

	protected function createViewManager(Action $action): ViewManager
	{
		return new ViewManager($action);
	}

	/**
	 * @throws SystemException
	 */
	protected function processBeforeAction(Action $action): ?bool
	{
		$internalizer = new Internalizer($this->viewManager);

		$internalizerResult = $internalizer->process();
		if (!$internalizerResult->isSuccess())
		{
			$this->addErrors($internalizerResult->getErrors());

			return null;
		}

		$action->setArguments($internalizerResult->getData()['data']);

		return parent::processBeforeAction($action);
	}

	protected function processAfterAction(Action $action, $result)
	{
		if (!$this->errorCollection->isEmpty())
		{
			return parent::processAfterAction($action, $result);
		}

		if(
			!($result instanceof Page)
			&& !is_array($result)
		)
		{
			return parent::processAfterAction($action, $result);
		}

		$externalizer = new Externalizer(
			$this->getViewManager(),
			$result instanceof Page ? $result->toArray() : $result
		);

		if ($result instanceof Page)
		{
			return $externalizer->getPage($result);
		}

		return $externalizer;
	}

	public function getViewManager(): ViewManager
	{
		return $this->viewManager;
	}

	protected function getUserId(): int
	{
		return (int)$this->getCurrentUser()?->getId();
	}

	/**
	 * @param Error $error
	 * @return null
	 */
	protected function responseWithError(Main\Error $error)
	{
		$this->addError($error);

		return null;
	}

	/**
	 * @param array<Main\Error> $errors
	 * @return null
	 */
	protected function responseWithErrors(array $errors)
	{
		$this->addErrors($errors);

		return null;
	}

	protected function convertToRestFields(Main\Type\Contract\Arrayable $object): array
	{
		return $this->converterToRestFields->process($object->toArray());
	}
}
