<?php

namespace Bitrix\AI\Controller;

use Bitrix\AI\QueueJob;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\JsonPayload;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use JetBrains\PhpStorm\ArrayShape;

class Queue extends Controller
{
	protected function processBeforeAction(Action $action): bool
	{
		if (!Loader::includeModule('bitrix24'))
		{
			$this->addError(new Error('Module bitrix24 is required.'));

			return false;
		}

		return parent::processBeforeAction($action);
	}

	public function getDefaultPreFilters(): array
	{
		return [];
	}

	/**
	 * Set filter for JSON type.
	 *
	 * @return array[]
	 */
	public function configureActions(): array
	{
		return [
			'callbackBody' => [
				'+prefilters' => [
					new ActionFilter\ContentType([ActionFilter\ContentType::JSON]),
				]
			],
		];
	}

	/**
	 * Accepts external result for Queue Job.
	 *
	 * @param string $hash Job hash.
	 * @param JsonPayload $result Queue result.
	 * @return bool
	 */
	public function callbackBodyAction(string $hash, JsonPayload $result): bool
	{
		$queueJob = QueueJob::createFromHash($hash);
		if ($queueJob)
		{
			$queueJob->execute($result->getData());

			return true;
		}

		return false;
	}

	/**
	 * Accepts external error for Queue Job.
	 *
	 * @param string $hash Job hash.
	 * @param JsonPayload $result Queue error.
	 * @return bool
	 */
	public function callbackErrorAction(string $hash, JsonPayload $result): bool
	{
		$queueJob = QueueJob::createFromHash($hash);
		if ($queueJob)
		{
			$queueJob->fail($result->getData());

			return true;
		}

		return false;
	}
}
