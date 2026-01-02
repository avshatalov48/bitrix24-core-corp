<?php

namespace Bitrix\Tasks\Internals\Notification\Strategy;

use Bitrix\Main\Application;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Internals\Notification\Dictionary;
use Bitrix\Tasks\Internals\Notification\ProviderInterface;
use Bitrix\Tasks\Internals\Notification\UseCase\AbstractCase;
use Bitrix\Tasks\Internals\Notification\Strategy\Default\TaskDeletedStrategy;
use Bitrix\Tasks\Internals\Notification\Strategy\Default\CommentCreatedStrategy;
use Bitrix\Tasks\Internals\Notification\Strategy\Default\NotificationReplyStrategy;
use Bitrix\Tasks\Internals\Notification\Strategy\Default\RegularTaskReplicatedStrategy;
use Bitrix\Tasks\Internals\Notification\Strategy\Default\RegularTaskStartedStrategy;
use Bitrix\Tasks\Internals\Notification\Strategy\Default\TaskCreatedStrategy;
use Bitrix\Tasks\Internals\Notification\Strategy\Default\TaskExpiresSoonStrategy;
use Bitrix\Tasks\Internals\Notification\Strategy\Default\TaskExpiredStrategy;
use Bitrix\Tasks\Internals\Notification\Strategy\Default\TaskPingSentStrategy;
use Bitrix\Tasks\Internals\Notification\Strategy\Default\TaskUpdatedStrategy;
use ReflectionClass;

class RecipientStrategyFactory
{
	private const STRATEGY_SUFFIX = 'strategy';
	private const STRATEGY_DIRECTORY = 'default';

	private AbstractCase $case;
	private ProviderInterface $provider;
	private Dictionary $dictionary;

	public static function getStrategy(
		AbstractCase $case,
		ProviderInterface $provider,
		Dictionary $dictionary
	): RecipientStrategyInterface
	{
		$factory = new static($case, $provider, $dictionary);
		/** @var RecipientStrategyInterface $class */
		$class = $factory->find();

		return new $class($case->getUserRepository(), $case->getTask(), $dictionary);
	}

	private function __construct(AbstractCase $case, ProviderInterface $provider, Dictionary $dictionary)
	{
		$this->case = $case;
		$this->provider = $provider;
		$this->dictionary = $dictionary;
	}

	private function find(): string
	{
		return $this->getDefaultStrategyClass();
	}

	/**
	 * Find default strategy in default (@see RecipientStrategyFactory::STRATEGY_DIRECTORY) directory.
	 * If the default strategy is not found, returns fake (@see FakeStrategy::class) strategy.
	 *
	 * @uses CommentCreatedStrategy;
	 * @uses NotificationReplyStrategy;
	 * @uses RegularTaskReplicatedStrategy;
	 * @uses RegularTaskStartedStrategy;
	 * @uses TaskCreatedStrategy;
	 * @uses TaskDeletedStrategy;
	 * @uses TaskExpiresSoonStrategy;
	 * @uses TaskExpiredStrategy;
	 * @uses TaskPingSentStrategy;
	 * @uses TaskUpdatedStrategy;
	 */
	private function getDefaultStrategyClass(): string
	{
		$caseReflection = new ReflectionClass($this->case);
		$factoryReflection = new ReflectionClass($this);
		$strategyClass = $this->case->getStrategyAlias() ?? $caseReflection->getShortName() . static::STRATEGY_SUFFIX;

		$defaultStrategy =
			$factoryReflection->getNamespaceName()
			. '\\'
			. static::STRATEGY_DIRECTORY
			. '\\'
			. $strategyClass;

		$defaultStrategy = mb_strtolower($defaultStrategy);

		if (class_exists($defaultStrategy))
		{
			return $defaultStrategy;
		}

		$this->logFakeStrategyCall();

		return FakeStrategy::class;
	}

	private function logFakeStrategyCall(): void
	{
		$caseData = var_export($this->case::class, true);
		$providerData = var_export($this->provider::class, true);
		$dictionaryData = var_export($this->dictionary->toArray(), true);

		$message = "
			Fake Strategy call with:
			Case: {$caseData},
			Provider: {$providerData},
			Dictionary: {$dictionaryData}.
		";

		Logger::handle($message);
	}
}