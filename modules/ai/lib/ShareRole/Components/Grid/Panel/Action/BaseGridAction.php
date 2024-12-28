<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Panel\Action;

use Bitrix\AI\ShareRole\Events\BaseAnalyticEvent;
use Bitrix\AI\Exception\ErrorCollectionException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Action\Action;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\AI\ShareRole\Events\Enums\Status;

abstract class BaseGridAction implements Action
{
	protected BaseAnalyticEvent $event;

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		try
		{
			$this->fillEvent();
			$result = $this->processRequestAction($request, $isSelectedAllRows, $filter);

			if ($result)
			{
				$this->sendEvent($result->isSuccess());
			}

			return $result;
		}
		catch (ErrorCollectionException $collectionException)
		{
			$this->logErrorFromCollection($collectionException);
			$this->sendEvent(false);

			return (new Result())
				->addError(
					new Error(Loc::getMessage('AI_COMPONENT_GRID_SHARE_ROLE_ERROR_IN_ACTION'))
				)
			;
		}
	}

	public function processRequestAction(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		return null;
	}

	protected function fillEvent(): void
	{
	}

	protected function sendEvent(bool $isSuccess): void
	{
		if (empty($this->event))
		{
			return;
		}

		if ($isSuccess)
		{
			$this->event->send(Status::Success);

			return;
		}

		$this->event->send(Status::Error);
	}

	protected function logErrorFromCollection(ErrorCollectionException $collectionException): void
	{
		$errors = [];
		foreach ($collectionException->getCollection() as $error)
		{
			if ($error instanceof Error)
			{
				$errors[] = $error->getMessage();
			}
		}

		$this->log(implode('; ', $errors));
	}

	protected function getCurrentUser(): CurrentUser
	{
		return CurrentUser::get();
	}

	protected function log(string $message): void
	{
		AddMessage2Log($message);
	}

	public function getControl(): ?array
	{
		return null;
	}
}
