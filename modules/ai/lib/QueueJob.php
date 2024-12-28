<?php

namespace Bitrix\AI;

use Bitrix\AI\Cache\EngineResultCache;
use Bitrix\AI\Engine\IEngine;
use Bitrix\AI\Engine\IQueue;
use Bitrix\AI\Engine\ThirdParty;
use Bitrix\AI\Facade\Analytics;
use Bitrix\AI\Facade\User;
use Bitrix\AI\History\Manager;
use Bitrix\AI\Limiter\LimitControlService;
use Bitrix\AI\Model\QueueTable;
use Bitrix\AI\Payload\IPayload;
use Bitrix\AI\Role\RoleManager;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use ReflectionClass;

final class QueueJob
{
	private const EVENT_SUCCESS = 'onQueueJobExecute';
	private const EVENT_FAIL = 'onQueueJobFail';

	private const TTL_SECONDS = 840;
	private const CALLBACK_PATH = '/bitrix/services/main/ajax.php?action=ai.api.queue.callbackBody&hash={hash}';
	private const CLOUD_CALLBACK_PATH = '/bitrix/services/main/ajax.php?action=ai.controller.integration.b24cloudai.callbackSuccess&hash={hash}';
	private const THIRDPARTY_CALLBACK_PATH = '/bitrix/services/main/ajax.php?action=ai.controller.integration.thirdparty.callbackSuccess&hash={hash}';
	private const CALLBACK_ERROR_PATH = '/bitrix/services/main/ajax.php?action=ai.api.queue.callbackError&hash={hash}';
	private const CLOUD_CALLBACK_ERROR_PATH = '/bitrix/services/main/ajax.php?action=ai.controller.integration.b24cloudai.callbackError&hash={hash}';
	private const THIRDPARTY_CALLBACK_ERROR_PATH = '/bitrix/services/main/ajax.php?action=ai.controller.integration.thirdparty.callbackError&hash={hash}';

	private ?int $id = null;
	private ?string $hash = null;
	private ?string $cacheHash = null;
	private ?Error $error = null;
	private bool $apiRequestCompleted = true;
	private Context $context;
	private IEngine $engine;
	protected LimitControlService $limitControlService;

	private function __construct() {}

	/**
	 * Agent. Clears all expired jobs, returns agent's full name.
	 *
	 * @return string
	 */
	public static function clearOldAgent(): string
	{
		$date = new DateTime();
		$date->add('-' . self::TTL_SECONDS . ' seconds');
		$limit = 100;

		$rows = QueueTable::query()
			->setSelect(['ID', 'HASH', 'DATE_CREATE'])
			->where('DATE_CREATE', '<', $date)
			->setOrder('ID')
			->setLimit($limit)
			->fetchAll()
		;

		$limiterControlService = new LimitControlService();
		foreach ($rows as $row)
		{
			$queueJob = self::createFromHash($row['HASH']);
			if ($queueJob)
			{
				$result = new Result(null, null);

				$queueJob->error = new Error('Hash expired', 'HASH_EXPIRED');
				$queueJob->sendBackendEvent($result, self::EVENT_FAIL);
				$queueJob->sendFrontendEvent($result, self::EVENT_FAIL);

				$limiterControlService->rollbackConsumption(
					new Limiter\Usage($queueJob->engine->getContext()),
					$queueJob->engine->getPayload()->getCost(),
					$queueJob->engine->getConsumptionId()
				);

				$queueJob->delete();
			}
			else
			{
				QueueTable::delete($row['ID'])->isSuccess();
			}
		}

		return __CLASS__ . '::' . __FUNCTION__ . '();';
	}

	/**
	 * Creates Queue Job instance within from Engine.
	 *
	 * @param IEngine $engine Engine instance.
	 * @return self
	 */
	public static function createWithinFromEngine(IEngine $engine): self
	{
		$self = new self();
		$self->engine = $engine;

		return $self;
	}

	/**
	 * Creates Queue Job object by hash, and return it (if exists).
	 *
	 * @param string $hash Queue job hash.
	 * @return static|null
	 */
	public static function createFromHash(string $hash): ?self
	{
		$row = QueueTable::query()
			->setSelect(['*'])
			->setFilter(['=HASH' => $hash])
			->setLimit(1)
			->fetch()
		;
		if ($row)
		{
			/** @var IPayload $payloadClass */
			/** @var IEngine&IQueue $engineClass */

			$payloadClass = $row['PAYLOAD_CLASS'];
			$payload = $payloadClass::unpack($row['PAYLOAD']);
			$context = Context::unpack($row['CONTEXT']);

			$engine = Engine::getByCode($row['ENGINE_CODE'], $context);
			if ($engine === null || $payload === null)
			{
				return null;
			}

			$engineCustomSettings = $row['ENGINE_CUSTOM_SETTINGS'] ?? [];
			if (isset($engineCustomSettings['JSON_RESPONSE_MODE']))
			{
				$engine->setResponseJsonMode((bool)$engineCustomSettings['JSON_RESPONSE_MODE']);
			}
			if (isset($engineCustomSettings['ANALYTIC_DATA']))
			{
				$engine->setAnalyticData($engineCustomSettings['ANALYTIC_DATA']);
			}
			if (isset($engineCustomSettings['SHOULD_SKIP_AGREEMENT']) && $engineCustomSettings['SHOULD_SKIP_AGREEMENT'])
			{
				$engine->skipAgreement();
			}
			if (!empty($engineCustomSettings['HIDDEN_TOKENS']) && \is_array($engineCustomSettings['HIDDEN_TOKENS']))
			{
				$payload->setProcessedReplacements($engineCustomSettings['HIDDEN_TOKENS']);
			}

			$engine->setPayload($payload);
			$engine->setParameters($row['PARAMETERS']);
			$engine->setHistoryState($row['HISTORY_WRITE'] === 'Y');
			$engine->setHistoryGroupId($row['HISTORY_GROUP_ID']);

			$queueJob = new self();

			$queueJob->id = $row['ID'];
			$queueJob->hash = $row['HASH'];
			$queueJob->cacheHash = $row['CACHE_HASH'];
			$queueJob->context = $context;
			$queueJob->engine = $engine->getIEngine();

			return $queueJob;
		}

		return null;
	}

	/**
	 * Registers new Queue Job.
	 *
	 * @return self
	 */
	public function register(): self
	{
		if ($this->hash)
		{
			return $this;
		}

		$hash = QueueTable::generateHash();
		$data = [
			'ENGINE_CLASS' => (new ReflectionClass($this->engine))->getName(),
			'ENGINE_CODE' => $this->engine->getCode(),
			'PAYLOAD_CLASS' => (new ReflectionClass($this->engine->getPayload()))->getName(),
			'PAYLOAD' => $this->engine->getPayload()->pack(),
			'CONTEXT' => $this->engine->getContext()->pack(),
			'PARAMETERS' => $this->engine->getParameters(),
			'HISTORY_WRITE' => $this->engine->shouldWriteHistory() ? 'Y' : 'N',
			'HISTORY_GROUP_ID' => $this->engine->getHistoryGroupId(),
			'ENGINE_CUSTOM_SETTINGS' => [
				'JSON_RESPONSE_MODE' => $this->engine->getResponseJsonMode(),
				'ANALYTIC_DATA' => $this->engine->getAnalyticData(),
				'SHOULD_SKIP_AGREEMENT' => $this->engine->shouldSkipAgreement(),
				'HIDDEN_TOKENS' => $this->engine->getPayload()->getTokenProcessor()->getReplacements(),
			],
		];
		$cacheHash = md5(serialize($data));
		$data['HASH'] = $hash;
		$data['CACHE_HASH'] = $cacheHash;

		$result = QueueTable::add($data);

		if ($result->isSuccess())
		{
			$this->id = $result->getId();
			$this->hash = $hash;
			$this->context = $this->engine->getContext();
			$this->cacheHash = $cacheHash;
		}
		else
		{
			throw new SystemException(implode(' ', $result->getErrorMessages()));
		}

		return $this;
	}

	/**
	 * Returns hash, if job was registered.
	 *
	 * @return string|null
	 */
	public function getHash(): ?string
	{
		return $this->hash;
	}

	/**
	 * Returns cache hash, if job was registered.
	 *
	 * @return string|null
	 */
	public function getCacheHash(): ?string
	{
		return $this->cacheHash;
	}

	/**
	 * Returns callback url for job. When job will complete, this url must receive result data.
	 *
	 * @return string
	 */
	public function getCallbackUrl(): string
	{
		if ($this->engine instanceof ThirdParty)
		{
			/** @see \Bitrix\AI\Controller\Integration\Thirdparty::callbackSuccessAction */
			return $this->getCallbackUrlTemplate(self::THIRDPARTY_CALLBACK_PATH);
		}

		if (!Loader::includeModule('bitrix24'))
		{
			return $this->getCallbackUrlTemplate(self::CLOUD_CALLBACK_PATH);
		}

		return $this->getCallbackUrlTemplate(self::CALLBACK_PATH);
	}

	/**
	 * Returns error callback url for job. If job will fail with error, this url must receive result data.
	 *
	 * @return string
	 */
	public function getErrorCallbackUrl(): string
	{
		if ($this->engine instanceof ThirdParty)
		{
			/** @see \Bitrix\AI\Controller\Integration\Thirdparty::callbackErrorAction */
			return $this->getCallbackUrlTemplate(self::THIRDPARTY_CALLBACK_ERROR_PATH);
		}

		if (!Loader::includeModule('bitrix24'))
		{
			return $this->getCallbackUrlTemplate(self::CLOUD_CALLBACK_ERROR_PATH);
		}

		return $this->getCallbackUrlTemplate(self::CALLBACK_ERROR_PATH);
	}

	/**
	 * Returns template url for callbacks.
	 *
	 * @param string $path Relative path.
	 * @return string
	 */
	private function getCallbackUrlTemplate(string $path): string
	{
		$url = Config::getValue('public_url');
		if ($url)
		{
			$url = rtrim(trim($url), '/');
		}
		else
		{
			$url = UrlManager::getInstance()->getHostUrl();
		}

		return str_replace('{hash}', $this->hash, $url . $path);
	}

	/**
	 * Executes Queue Job and removes Job.
	 *
	 * @param mixed $rawResult External raw result.
	 * @return void
	 */
	public function execute(mixed $rawResult): void
	{
		if ($this->engine->getPayload()->shouldUseCache())
		{
			$this->engine->setCache(true);
		}
		$cacheManager = new EngineResultCache($this->getCacheHash());
		if($this->engine->isCache() && !$cacheManager->getExists()){
			$cacheManager->store($rawResult);
		}

		Analytics::engineGenerateResultEvent(
			'generate',
			$this->engine,
			$this->engine->getAnalyticData()
		);

		$result = $this->engine->getResultFromRaw($rawResult);

		$this->engine->writeHistory($result);

		if ($this->engine->getPayload()->getRole() !== null)
		{
			$langCode = $this->context->getLanguage()?->getCode() ?? User::getUserLanguage();
			$roleManager = new RoleManager($this->context->getUserId(), $langCode);
			$roleManager->addRecentRole($this->engine->getPayload()->getRole());
		}

		$this->sendBackendEvent($result, self::EVENT_SUCCESS);
		$this->sendFrontendEvent($result, self::EVENT_SUCCESS);
		$this->delete();
	}

	/**
	 * Fails Queue Job and removes Job.
	 *
	 * @param mixed $rawError External raw error.
	 * @return void
	 */
	public function fail(mixed $rawError): void
	{
		$this->error = new Error($rawError['message'] ?? 'Unknown Error', $rawError['code'] ?? '');
		$this->engine->writeErrorInHistory($this->error);

		if (isset($rawError['api_request_completed']))
		{
			$this->apiRequestCompleted = (bool)$rawError['api_request_completed'];
		}

		$errorCode = isset($rawError['code']) ? (int)$rawError['code'] : 0;

		Loc::loadLanguageFile(__DIR__ . '/Engine.php');

		if ($errorCode === 100 || $errorCode >= 500)
		{
			$this->error = new Error(Loc::getMessage('AI_ENGINE_ERROR_PROVIDER'), 'AI_ENGINE_ERROR_PROVIDER');
		}
		else
		{
			$this->error = new Error(Loc::getMessage('AI_ENGINE_ERROR_OTHER'),'AI_ENGINE_ERROR_OTHER');
		}


		$this->sendBackendEvent(new Result(null, null), self::EVENT_FAIL);
		$this->sendFrontendEvent(new Result(null, null), self::EVENT_FAIL);

		if (!$this->apiRequestCompleted)
		{
			$this->getLimitControlService()->rollbackConsumption(
				new Limiter\Usage($this->engine->getContext()),
				$this->engine->getPayload()->getCost(),
				$this->engine->getConsumptionId()
			);
		}

		$this->delete();
	}

	/**
	 * Returns TTL in seconds for each Queue Job.
	 *
	 * @return int
	 */
	public function getTTL(): int
	{
		return self::TTL_SECONDS;
	}

	/**
	 * Sends event about Job result to backend listeners.
	 *
	 * @param Result $result Result instance.
	 * @param string $eventName Event name.
	 * @return void
	 */
	private function sendBackendEvent(Result $result, string $eventName): void
	{
		$event = new Event('ai', $eventName, [
			'queue' => $this->hash,
			'engine' => $this->engine,
			'result' => $result,
			'error' => $this->error,
		]);
		$event->send();
	}

	/**
	 * Sends event about Job result to frontend listeners.
	 *
	 * @param Result $result Result instance.
	 * @param string $eventName Event name.
	 * @return void
	 */
	private function sendFrontendEvent(Result $result, string $eventName): void
	{
		if (Loader::includeModule('pull'))
		{
			\Bitrix\Pull\Event::add($this->context->getUserId(), [
				'module_id' => 'ai',
				'command' => $eventName,
				'params' => [
					'hash' => $this->hash,
					'error' => $this->error ? [
						'code' => $this->error->getCode(),
						'message' => $this->error->getMessage(),
					] : null,
					'data' => [
						'result' => $this->engine->getResponseJsonMode() ? $result->getJsonData() : $result->getPrettifiedData(),
						'last' => $this->engine->shouldWriteHistory()
							? Manager::getLastItem($this->context)
							: Manager::getFakeItem($result->getPrettifiedData(), $this->engine)
						,
						'queue' => $this->hash,
					]
				],
			]);
		}
	}

	/**
	 * Cancels current Queue Job.
	 * Removes Job from Queue and does not callbacks.
	 * @return void
	 */
	public function cancel(): void
	{
		$this->delete();
	}

	/**
	 * Removes current Queue Job.
	 *
	 * @return void
	 */
	private function delete(): void
	{
		if ($this->id)
		{
			QueueTable::delete($this->id)->isSuccess();
		}
	}

	private function getLimitControlService(): LimitControlService
	{
		if (empty($this->limitControlService))
		{
			$this->limitControlService = new LimitControlService();
		}

		return $this->limitControlService;
	}
}
