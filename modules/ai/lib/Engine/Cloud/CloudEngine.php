<?php
declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Engine;
use Bitrix\AI\Cache\EngineResultCache;
use Bitrix\AI\Cloud;
use Bitrix\AI\Engine\IEngine;
use Bitrix\AI\Payload\Prompt;
use Bitrix\AI\Payload\Text;
use Bitrix\AI\QueueJob;
use Bitrix\AI\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use function call_user_func;
use function is_callable;

abstract class CloudEngine extends Engine\Engine implements IEngine
{

	public const CLOUD_REGISTRATION_NOT_FOUND = 'CLOUD_REGISTRATION_DATA_NOT_FOUND';

	protected const AGREEMENT_CODE = 'AI_BOX_AGREEMENT';

	public function checkLimits(): bool
	{
		return false;
	}

	final protected function exportPromtData(): array
	{
		$data = [
			'moduleId' => $this->getContext()->getModuleId(),
			'contextId' => $this->getContext()->getContextId(),
			'userId' => $this->getContext()->getUserId(),
		];
		try
		{
			$payload = $this->getPayload();
			if ($payload instanceof Text)
			{
				$role = $payload->getRole();
				if ($role)
				{
					$data['role'] = $role->getCode();
				}
			}
			if ($payload instanceof Prompt)
			{
				$data['promptCode'] = $payload->getPromptCode();
				$data['promptCategory'] = $payload->getPromptCategory();
			}
		}
		catch (SystemException)
		{
		}

		return $data;
	}

	final public function completionsInQueue(): void
	{
		if ($this->payload->shouldUseCache())
		{
			$this->setCache(true);
		}
		$this->queueJob = QueueJob::createWithinFromEngine($this)->register();

		$cacheManager = new EngineResultCache($this->queueJob->getCacheHash());

		if ($this->isCache() && ($response = $cacheManager->getExists()))
		{
			$this->onResponseSuccess($response, $cacheManager, true);

			return;
		}

		$cloudConfiguration = new Cloud\Configuration();
		$registrationDto = $cloudConfiguration->getCloudRegistrationData();
		if (!$registrationDto)
		{
			$this->queueJob->cancel();

			call_user_func(
				$this->onErrorCallback,
				new Error(
					Loc::getMessage('AI_CLOUD_ENGINE_ERROR_CLOUD_REGISTRATION'),
					self::CLOUD_REGISTRATION_NOT_FOUND
				)
			);

			return;
		}

		$sendQuery = new Cloud\SendQuery($registrationDto->serverHost);
		$responseResult = $sendQuery->queue([
			'provider' => $this->getCode(),
			'moduleId' => $this->getContext()->getModuleId(),
			'promptData' => $this->exportPromtData(),

			'callbackUrl' => $this->queueJob->getCallbackUrl(),
			'errorCallbackUrl' => $this->queueJob->getErrorCallbackUrl(),
			'url' => $this->getCompletionsUrl(),
			'params' => $this->makeRequestParams(),
		]);
		if ($responseResult->isSuccess())
		{
			if (is_callable($this->onSuccessCallback))
			{
				call_user_func(
					$this->onSuccessCallback,
					new Result(true, ''),
					$this->queueJob->getHash(),
				);
			}

			return;
		}
		if (!is_callable($this->onErrorCallback))
		{
			return;
		}

		$errorCollection = $responseResult->getErrorCollection();
		/** @see \Bitrix\AiProxy\Controller\Query::ERROR_CODE_EXCEEDED_LIMIT */
		if ($errorCollection->getErrorByCode('exceeded_limit'))
		{
			$this->queueJob->cancel();

			$error = new Error(
				Loc::getMessage('AI_ENGINE_ERROR_LIMIT_IS_EXCEEDED'),
				'LIMIT_IS_EXCEEDED_MONTHLY',
				[
					'sliderCode' => 'limit_copilot_requests_box',
				]
			);

			call_user_func(
				$this->onErrorCallback,
				$error
			);
		}
		else
		{
			$error = $errorCollection[0];
			$this->onResponseError($error->getMessage(), (string)$error->getCode());
		}
	}

	final public function completions(): void
	{
		//todo Liskov substitution principle violation
		throw new SystemException('Direct completions are not supported for cloud engines');
	}

	/**
	 * Returns name of the engine.
	 * Will be used in the UI.
	 * @return string
	 */
	public function getName(): string
	{
		return $this->getModel();
	}

	protected function getModel(): string
	{
		$name = new Engine\Cloud\EngineProperty\Model(
			static::class,
			$this->getContext()->getModuleId(),
		);

		return $name->getValue() ?? $this->getDefaultModel();
	}

	abstract protected function getDefaultModel(): string;
}
