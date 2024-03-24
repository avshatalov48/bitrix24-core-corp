<?php

namespace Bitrix\Tasks\Internals\Notification\UseCase;

use Bitrix\Tasks\Internals\Notification\BufferInterface;
use Bitrix\Tasks\Internals\Notification\Dictionary;
use Bitrix\Tasks\Internals\Notification\ProviderCollection;
use Bitrix\Tasks\Internals\Notification\Strategy\RecipientStrategyFactory;
use Bitrix\Tasks\Internals\Notification\Strategy\RecipientStrategyInterface;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

abstract class AbstractCase
{
	protected TaskObject $task;
	protected BufferInterface $buffer;
	protected UserRepositoryInterface $userRepository;
	protected ProviderCollection $providers;
	protected RecipientStrategyInterface $strategy;
	protected Dictionary $dictionary;

	public function __construct(
		TaskObject $task,
		BufferInterface $buffer,
		UserRepositoryInterface $userRepository,
		ProviderCollection $providers
	)
	{
		$this->task = $task;
		$this->buffer = $buffer;
		$this->userRepository = $userRepository;
		$this->providers = $providers;
		$this->init();
	}

	public function getTask(): TaskObject
	{
		return $this->task;
	}

	public function getUserRepository(): UserRepositoryInterface
	{
		return $this->userRepository;
	}

	public function getCurrentSender(): ?User
	{
		return $this->getCurrentStrategy()->getSender();
	}

	public function getCurrentRecipients(): array
	{
		return $this->getCurrentStrategy()->getRecipients();
	}

	protected function createDictionary(array $options): void
	{
		$this->dictionary->setValues($options);
	}

	private function getCurrentStrategy(): RecipientStrategyInterface
	{
		return RecipientStrategyFactory::getStrategy($this, $this->providers->current(), $this->dictionary);
	}

	private function init(): void
	{
		$this->dictionary = new Dictionary();
	}
}