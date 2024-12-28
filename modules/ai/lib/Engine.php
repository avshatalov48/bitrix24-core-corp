<?php

namespace Bitrix\AI;

use Bitrix\AI\Engine\Cloud;
use Bitrix\AI\Engine\Enum\Category;
use Bitrix\AI\Engine\IEngine;
use Bitrix\AI\Engine\IQueue;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Facade\User;
use Bitrix\AI\Guard\ShowCopilotGuard;
use Bitrix\AI\Limiter\Enums\ErrorLimit;
use Bitrix\AI\Limiter\LimitControlService;
use Bitrix\AI\Limiter\ReserveRequest;
use Bitrix\AI\Limiter\Usage;
use Bitrix\AI\Payload\IPayload;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Engine
{
	private const EVENT_NAME_ENGINE_ADDED = 'onEngineAddedInternal';

	public const CATEGORIES = [
		'text' => 'text',
		'image' => 'image',
		'audio' => 'audio',
		'call' => 'call',
	];

	private const CONFIG_PREFIX = 'engine_';
	private const CONFIG_QUALITY_SUFFIX = '_quality_';

	private static array $engines = [];

	private array $analyticData = [
		'category' => '',
		'type' => '',
		'c_element' => '',
		'c_sub_section' => '',
	];

	private const ERRORS = [
		'MUST_AGREE_WITH_AGREEMENT' => 'MUST_AGREE_WITH_AGREEMENT',
		'SERVICE_IS_NOT_AVAILABLE_BY_TARIFF' => 'SERVICE_IS_NOT_AVAILABLE_BY_TARIFF',
		'CURRENT_PROVIDER_IS_EXPIRED' => 'CURRENT_PROVIDER_IS_EXPIRED',
		'LIMIT_IS_EXCEEDED' => 'LIMIT_IS_EXCEEDED',
	];

	/** @var callable $errorCallback */
	private $errorCallback;
	private bool $needRollbackConsumption = false;
	protected LimitControlService $limitControlService;

	private function __construct(
		private IEngine $engine,
	) {}

	/**
	 * Returns true if Engine by code exists.
	 *
	 * @param string $category Category's code.
	 * @param string $code Engine's code.
	 * @return bool
	 */
	public static function isExistByCode(string $category, string $code): bool
	{
		self::loadThirdParty();

		$code = mb_strtolower($code);
		foreach (self::$engines[$category] ?? [] as $engine)
		{
			$exists = $code === mb_strtolower((new $engine['engine'](Context::getFake(), $engine['data']))->getCode());
			if ($exists)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Adds Engine to the list of available Engines.
	 * @param Category $category Engine's category.
	 * @param string $class Engine's class name.
	 * @return void
	 * @internal
	 */
	public static function addEngine(Category $category, string $class): void
	{
		self::$engines[$category->value][] = [
			'engine' => $class,
			'data' => null,
		];
	}

	/**
	 * Returns Engine's available categories.
	 *
	 * @return array
	 */
	public static function getCategories(): array
	{
		return array_values(self::CATEGORIES);
	}

	/**
	 * Loads Third Party Engines.
	 *
	 * @return void
	 */
	public static function loadThirdParty(): void
	{
		static $loaded = false;

		if ($loaded)
		{
			return;
		}

		foreach (ThirdParty\Manager::getCollection() as $item)
		{
			self::$engines[$item->getCategory()][] = [
				'engine' => Engine\ThirdParty::class,
				'data' => $item,
			];
		}

		$loaded = true;
	}

	/**
	 * Returns Engine's objects list by category.
	 *
	 * @param string $category Engine's category.
	 * @param Context $context Context instance.
	 * @return IEngine[]
	 */
	public static function getList(string $category, Context $context): array
	{
		self::loadThirdParty();

		$engines = [];
		foreach (self::$engines[$category] ?? [] as $item)
		{
			$engines[] = new $item['engine']($context, $item['data']);
		}

		return $engines;
	}

	/**
	 * Get list of only available engines with selected quality (if passed)
	 * @param string $category Engine's category.
	 * @param Quality|null $quality If specified, check that Engine has Quality.
	 *
	 * @return IEngine[]
	 */
	public static function getListAvailable(string $category, ?Quality $quality = null): array
	{
		$available = [];
		$all = self::getList($category, Context::getFake());

		foreach ($all as $engine)
		{
			if (!$engine->isAvailable())
			{
				continue;
			}

			if ($quality !== null && !$engine->hasQuality($quality))
			{
				continue;
			}

			$available[] = $engine;
		}

		return $available;
	}

	/**
	 * Returns Engine's list by category.
	 *
	 * @param string $category Engine's category.
	 * @param Context|null $context Context instance.
	 * @return array
	 */
	public static function getData(string $category, ?Context $context = null): array
	{
		if (!$context)
		{
			$context = Context::getFake();
		}

		$engines = [];
		$lastEngineCode = User::getLastUsedEngineCode($category, $context->getModuleId());
		if ($lastEngineCode !== '')
		{
			$lastEngine = Engine::getByCode($lastEngineCode, $context, $category);
		}

		if ($lastEngineCode === '' || $lastEngine === null)
		{
			$lastEngineCode =  Config::getValue(self::getConfigCode($category));
		}

		$hasSelectedEngine = false;
		foreach (Engine::getList($category, $context) as $engine)
		{
			if (!$engine->isAvailable())
			{
				continue;
			}

			if (!$lastEngineCode)
			{
				$lastEngineCode = $engine->getCode();
			}

			if ($engine->getCode() === $lastEngineCode)
			{
				$hasSelectedEngine = true;
			}
			$agreement = $engine->getAgreement();
			$engines[] = [
				'code' => $engine->getCode(),
				'title' => $engine->getName(),
				'partner' => $engine->isThirdParty(),
				'inTariff' => $engine->inTariff(),
				'expired' => $engine->isExpired(),
				'queue' => $engine instanceof IQueue,
				'selected' => $engine->getCode() === $lastEngineCode,
				'agreement' => $agreement
					? [
						'title' => $agreement->getTitle(),
						'text' => $agreement->getText(),
						'accepted' => $agreement->isAcceptedByContext($engine->getContext()),
					]
					: [],
			];
		}
		if (!$hasSelectedEngine && !empty($engines))
		{
			$engines[0]['selected'] = true;
		}

		return $engines;
	}

	/**
	 * Returns Engine by code and category.
	 *
	 * @param string $code Engine's code.
	 * @param Context $context Context instance.
	 * @param string|null $category Engine's category.
	 * @return self|null
	 */
	public static function getByCode(string $code, Context $context, ?string $category = null): ?self
	{
		self::loadThirdParty();

		foreach (self::getCategories() as $cCode)
		{
			if (!empty($category) && $category !== $cCode)
			{
				continue;
			}

			foreach (self::$engines[$cCode] ?? [] as $item)
			{
				/** @var IEngine $engine */
				$engine = new $item['engine']($context, $item['data']);

				if ($engine->getCode() !== $code)
				{
					continue;
				}
				if (!$engine->isAvailable())
				{
					return null;
				}

				return new self($engine);
			}
		}

		return null;
	}

	/**
	 * Returns only one available Engine by category.
	 *
	 * @param string $category Engine's category.
	 * @param Context $context Context instance.
	 * @param Quality|null $quality If specified, check that Engine has Quality.
	 * @return self|null
	 */
	public static function getByCategory(string $category, Context $context, ?Quality $quality = null): ?self
	{
		/** @var ShowCopilotGuard $showCopilotGuard */
		$showCopilotGuard = Container::init()->getItem(ShowCopilotGuard::class);
		if (!$showCopilotGuard->hasAccess(CurrentUser::get()->getId()))
		{
			return null;
		}

		self::loadThirdParty();

		$selectedEngine = Config::getValue(self::getConfigCode($category, $quality));

		// check that selected engine exists
		if (!empty($selectedEngine))
		{
			$selectedExists = false;
			foreach ((self::$engines[$category] ?? []) as $item)
			{
				/** @var IEngine $engine */
				$engine = new $item['engine']($context, $item['data']);
				if ($engine->isAvailable() && $engine->getCode() === $selectedEngine)
				{
					$selectedExists = true;
				}
			}
			if (!$selectedExists)
			{
				$selectedEngine = null;
			}
		}

		foreach ((self::$engines[$category] ?? []) as $item)
		{
			/** @var IEngine $engine */
			$engine = new $item['engine']($context, $item['data']);
			// check available
			if (!$engine->isAvailable())
			{
				continue;
			}
			// check by quality if specified
			if (!is_null($quality) && !$engine->hasQuality($quality))
			{
				continue;
			}
			// check if not current selected
			if (!empty($selectedEngine) && $engine->getCode() !== $selectedEngine)
			{
				continue;
			}

			return new self($engine);
		}

		return null;
	}

	public static function getConfigCode(string $category, ?Quality $quality = null): string
	{
		if (!$quality)
		{
			return self::CONFIG_PREFIX . $category;
		}

		$qualities = implode('_', $quality->getRequired());

		return self::CONFIG_PREFIX . $category . self::CONFIG_QUALITY_SUFFIX . $qualities;
	}

	/**
	 * Returns local portal parameters for certain engine.
	 *
	 * @param string $engineName Engine name (class name in general).
	 * @return array
	 */
	public static function getConfigParameters(string $engineName): array
	{
		$config = [];
		$engineName = mb_strtolower($engineName);
		$engineNameLength = mb_strlen($engineName);
		$configAll = Option::getForModule('ai');

		if ($configAll)
		{
			foreach ($configAll as $key => $value)
			{
				if (str_starts_with($key, "{$engineName}.param."))
				{
					$config[substr($key, $engineNameLength + 7)] = is_numeric($value) ? floatval($value) : $value;
				}
			}
		}

		return $config;
	}

	/**
	 * Returns Engine instance for system purpose.
	 * Friend method for QueueJob.
	 *
	 * @return IEngine
	 */
	public function getIEngine(): IEngine
	{
		return $this->engine;
	}

	/**
	 * Returns Engine's code.
	 *
	 * @return string
	 */
	public function getCode(): string
	{
		return $this->engine->getCode();
	}

	/**
	 * Returns Engine's category.
	 *
	 * @return string
	 */
	public function getCategory(): string
	{
		return $this->engine->getCategory();
	}

	/**
	 * Returns true, if current Engine has no agreement or current user accepted it.
	 *
	 * @return bool
	 */
	public function isAvailableByAgreement(): bool
	{
		if (Bitrix24::shouldUseB24())
		{
			return Bitrix24::isFeatureEnabled('ai_available_by_version');
		}

		$agreement = $this->engine->getAgreement();
		if ($agreement && !$agreement->isAcceptedByContext($this->engine->getContext()))
		{
			return false;
		}

		return true;
	}

	/**
	 * Checks that current Engine is available in the current tariff.
	 *
	 * @return bool
	 */
	public function isAvailableByTariff(): bool
	{
		return $this->engine->inTariff();
	}

	/**
	 * Returns true if Engine is expired (in REST Application case for example).
	 *
	 * @return bool
	 */
	public function isExpired(): bool
	{
		return $this->engine->isExpired();
	}

	public function useMemoryContextService(): self
	{
//		$this->engine->useMemoryContextService();

		return $this;
	}

	/**
	 * Stores payload for future request.
	 *
	 * @param IPayload $payload Payload.
	 * @return self
	 */
	public function setPayload(IPayload $payload): self
	{
		$payload->setEngine($this->engine);
		$this->engine->setPayload($payload);
		return $this;
	}

	/**
	 * Returns stored payload.
	 *
	 * @return IPayload
	 */
	public function getPayload(): IPayload
	{
		return $this->engine->getPayload();
	}

	/**
	 * Sets additional parameters for future request to Engine.
	 * Method replace old parameters, if they were set.
	 *
	 * @param array $params Array of parameters.
	 * @return self
	 */
	public function setParameters(array $params): self
	{
		$this->engine->setParameters($params);
		return $this;
	}

	/**
	 * Extension of setParameters(), forbids setting by user important engine params,
	 * such as allowed query cost (for example).
	 *
	 * @see setParameters()
	 * @param array $params
	 * @return self
	 */
	public function setUserParameters(array $params): self
	{
		$this->engine->setUserParameters($params);
		return $this;
	}

	/**
	 * Set response json mode for engine with json_response_mode quality support.
	 *
	 * @param bool $enable
	 *
	 * @return $this
	 */
	public function setResponseJsonMode(bool $enable): self
	{
		$this->engine->setResponseJsonMode($enable);

		return $this;
	}

	/**
	 * Extension of setAnalyticData(), set data for analytic,
	 *
	 * @see setAnalyticData()
	 * @param string[] $analyticData
	 * @return self
	 */
	public function setAnalyticData(array $analyticData): self
	{
		$this->engine->setAnalyticData($analyticData);
		return $this;
	}

	/**
	 * Returns true, if history will be written in current request.
	 *
	 * @return bool
	 */
	public function shouldWriteHistory(): bool
	{
		return $this->engine->shouldWriteHistory();
	}

	/**
	 * Write or not history, in depend on $state.
	 *
	 * @param bool $state
	 * @return $this
	 */
	public function setHistoryState(bool $state): self
	{
		$this->engine->setHistoryState($state);
		return $this;
	}

	/**
	 * Set group ID for save history.
	 * -1 - no grouped, 0 - first item of group
	 * @param int $groupId
	 * @return $this
	 */
	public function setHistoryGroupId(int $groupId): self
	{
		$this->engine->setHistoryGroupId($groupId);
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isCache(): bool
	{
		return $this->engine->isCache();
	}

	/**
	 * Set cache for Engine result.
	 *
	 * @param bool $cache
	 *
	 * @return $this
	 */
	public function setCache(bool $cache): self
	{
		$this->engine->setCache($cache);
		return $this;
	}

	/**
	 * Sets callback, that will be called on successful request.
	 *
	 * @param callable $callback Function will be called.
	 * @return self
	 */
	public function onSuccess(callable $callback): self
	{
		$this->engine->onSuccess($callback);
		return $this;
	}

	/**
	 * Sets callback, that will be called on any error occurred.
	 *
	 * @param callable $callback Function will be called.
	 * @return self
	 */
	public function onError(callable $callback): self
	{
		$this->errorCallback = $callback;
		$this->engine->onError([$this, 'internalErrorCallback']);
		return $this;
	}

	/**
	 * Wraps user error callback for internal purposes.
	 *
	 * @param Error $error Error instance.
	 * @return void
	 */
	public function internalErrorCallback(Error $error): void
	{
		if ($this->needRollbackConsumption)
		{
			$this->getLimitControlService()->rollbackConsumption(
				new Limiter\Usage($this->engine->getContext()),
				$this->engine->getPayload()->getCost(),
				$this->engine->getConsumptionId()
			);
		}

		if ($this->errorCallback)
		{
			call_user_func($this->errorCallback, $error);
		}
	}

	/**
	 * Returns Agreement if current Engine has it, null otherwise.
	 *
	 * @return Agreement|null
	 */
	public function getAgreement(): ?Agreement
	{
		return $this->engine->getAgreement();
	}

	/**
	 * Skips User Agreement (in fact just accept agreement by current Context's user).
	 *
	 * @return self
	 */
	public function skipAgreement(): self
	{
		$this->engine->skipAgreement();

		return $this;
	}

	/**
	 * Checks that current Engine is available in the current tariff.
	 *
	 * @return bool
	 */
	public function inTariff(): bool
	{
		return $this->engine->inTariff();
	}

	/**
	 * Checks that current Engine is in limits.
	 *
	 * @param string|null $limitCode Will be returned code of limit.
	 * @return bool
	 */
	public function isInLimit(?string &$limitCode = null): bool
	{
		if ($this->engine->checkLimits())
		{
			return (new Limiter\Usage($this->engine->getContext()))->isInLimit($limitCode);
		}

		return true;
	}

	/**
	 * Returns true, if Engine has required Quality.
	 *
	 * @param Quality $quality
	 * @return bool
	 */
	public function hasQuality(Quality $quality): bool
	{
		return $this->engine->hasQuality($quality);
	}

	/**
	 * Make request to AI Engine. The model will return one or more predicted completions.
	 *
	 * @param bool $queue Try to send request throw the queue.
	 * @return void
	 */
	public function completions(bool $queue = false): void
	{
		if (Config::getValue('force_queue') === 'Y' && $this->isNotTranslatePicturePrompt())
		{
			$queue = true;
		}

		$event = new Event('ai', 'onBeforeCompletions', [
			'engine' => $this->engine,
		]);
		$event->send();

		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() === EventResult::ERROR)
			{
				// return;
			}
		}

		// throw error in some cases

		if (!$this->isAvailableByAgreement())
		{
			$this->throwError(self::ERRORS['MUST_AGREE_WITH_AGREEMENT']);
			return;
		}

		if (!$this->isAvailableByTariff())
		{
			$this->throwError(self::ERRORS['SERVICE_IS_NOT_AVAILABLE_BY_TARIFF']);
			return;
		}

		if ($this->isExpired())
		{
			$this->throwError(self::ERRORS['CURRENT_PROVIDER_IS_EXPIRED']);
			return;
		}


		if ($this->engine->checkLimits())
		{
			$limitControlService = $this->getLimitControlService();
			$reservedRequest = $limitControlService->reserveRequest(
				new Usage($this->engine->getContext()),
				$this->engine->getPayload()->getCost()
			);

			if (!$reservedRequest->isSuccess())
			{
				$this->throwErrorLimit($reservedRequest);

				return;
			}

			$consumptionId = $limitControlService->commitRequest($reservedRequest);

			$this->engine->setConsumptionId($consumptionId);
		}

		if ($this->engine instanceof Cloud\CloudEngine)
		{
			$this->engine->completionsInQueue();
		}
		elseif ($queue && ($this->engine instanceof IQueueOptional))
		{
			$this->engine->completionsInQueue();
		}
		else
		{
			$this->engine->completions();
		}

		Facade\User::setLastUsedEngineCode($this);
		Facade\User::setLastUsedRoleCode($this);
		$this->logRestUsage();

		$event = new Event('ai', 'onAfterCompletions', [
			'engine' => $this->engine,
			'idIncrementLimit' => $this->engine->getConsumptionId()
		]);

		$event->send();
	}

	private function isNotTranslatePicturePrompt(): bool
	{
		$payload = $this->engine->getPayload();
		if (!$payload instanceof Payload\Prompt)
		{
			return true;
		}

		return ($payload->getPromptCode() !== 'translate_picture_request');
	}

	public function throwErrorLimit(ReserveRequest $reservedRequest): void
	{
		if ($reservedRequest->getErrorLimit() === ErrorLimit::BAAS_LIMIT)
		{
			$this->throwError(self::ERRORS['LIMIT_IS_EXCEEDED'], '_BAAS');

			return;
		}

		$suffixErrorCode = null;
		if (!empty($reservedRequest->getPromoLimitCode()))
		{
			$suffixErrorCode = '_' . strtoupper($reservedRequest->getPromoLimitCode());
		}

		$this->throwError(
			self::ERRORS['LIMIT_IS_EXCEEDED'],
			$suffixErrorCode
		);
	}

	/**
	 * Make request to AI Engine throw the queue. The model will return one or more predicted completions.
	 *
	 * @return void
	 */
	public function completionsInQueue(): void
	{
		$this->completions(true);
	}

	/**
	 * Registers usage or REST Apps (Engines or Prompts).
	 *
	 * @return void
	 */
	private function logRestUsage(): void
	{
		if ($this->engine instanceof Engine\ThirdParty)
		{
			$tpEngine = $this->engine->getRestItem();
			Facade\RestLog::logUsage($tpEngine->getAppCode(), "AI_PROVIDER;{$tpEngine->getCode()}");
		}

		$payloadProvider = mb_strtolower((new \ReflectionClass($this->getPayload()))->getShortName());
		if ($payloadProvider === 'prompt')
		{
			$prompt = Prompt\Manager::getByCode($this->getPayload()->getRawData());
			if ($prompt)
			{
				Facade\RestLog::logUsage($prompt->getAppCode(), "AI_PROMPT;{$prompt->getCode()}");
			}
		}
	}

	/**
	 * If Error callback was set call it with error code.
	 *
	 * @param string $errorCode Error code.
	 * @param string|null $suffixErrorCode Suffix error code.
	 * @return void
	 */
	private function throwError(string $errorCode, ?string $suffixErrorCode = null): void
	{
		call_user_func(
			[$this, 'internalErrorCallback'],
			new Error(Loc::getMessage("AI_ENGINE_ERROR_$errorCode"), $errorCode . $suffixErrorCode),
		);
	}

	private function getLimitControlService(): LimitControlService
	{
		if (empty($this->limitControlService))
		{
			$this->limitControlService = new LimitControlService();
		}

		return $this->limitControlService;
	}

	/**
	 * Triggers event when new engine finally added in ai/include.php.
	 * @internal
	 * @return void
	 */
	public static function triggerEngineAddedEvent(): void
	{
		$event = new Event('ai', self::EVENT_NAME_ENGINE_ADDED);
		$event->send();
	}
}
