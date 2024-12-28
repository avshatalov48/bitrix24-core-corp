<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Row\Action;

use Bitrix\AI\ShareRole\Events\Enums\Status;
use Bitrix\AI\ShareRole\Events\BaseAnalyticEvent;
use Bitrix\AI\Exception\ErrorCollectionException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Error;

abstract class BaseGridAction extends BaseAction
{
	protected BaseAnalyticEvent $event;

	public function processRequest(HttpRequest $request): ?Result
	{
		try
		{
			$result = $this->processRequestAction($request);
			if ($result)
			{
				$this->sendEvent($result->isSuccess());
			}

			return null;
		}
		catch (ErrorCollectionException $collectionException)
		{
			$this->logErrorFromCollection($collectionException);
			$this->sendEvent(false);

			return (new Result())
				->addError(
					new Error(Loc::getMessage('AI_COMPONENT_GRID_SHARE_ROLE_ERROR_IN_ACTION'))
				);
		}
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

	public function processRequestAction(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getCurrentUser(): CurrentUser
	{
		return CurrentUser::get();
	}

	protected function log(string $message): void
	{
		AddMessage2Log($message);
	}
}
