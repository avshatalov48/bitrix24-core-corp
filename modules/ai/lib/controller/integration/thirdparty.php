<?php
declare(strict_types=1);

namespace Bitrix\AI\Controller\Integration;

use Bitrix\AI\QueueJob;
use Bitrix\AI\ThirdParty\Manager;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Error;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\JsonPayload;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

/**
 * Class Thirdparty
 */
class Thirdparty extends Controller
{
	public function configureActions(): array
	{
		return [
			'callbackSuccess' => [
				'prefilters' => [],
			],
			'callbackError' => [
				'prefilters' => [],
			],
		];
	}

	/**
	 * Allows or denies access to action.
	 * @param Action $action Action.
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected function processBeforeAction(Action $action): bool
	{
		if (Manager::hasEngines() === false)
		{
			$this->addError(new Error('Thirdparty is not available. You should register at least one engine.'));

			return false;
		}

		return parent::processBeforeAction($action);
	}


	/**
	 * Returns default prefilters for Thirdparty controller.
	 * @return array
	 */
	protected function getDefaultPreFilters(): array
	{
		return [];
	}

	/**
	 * Accepts external result for Queue Job.
	 *
	 * @param string $hash Job hash.
	 * @param JsonPayload $result Queue result.
	 * @return bool
	 */
	public function callbackSuccessAction(string $hash, JsonPayload $result): bool
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
