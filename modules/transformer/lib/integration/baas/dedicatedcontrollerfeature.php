<?php

namespace Bitrix\Transformer\Integration\Baas;

use Bitrix\Transformer\Command;
use Bitrix\Transformer\DocumentTransformer;

/**
 * @internal
 */
final class DedicatedControllerFeature implements Feature
{
	public function __construct(
		private readonly bool $isAvailable,
		private readonly bool $isEnabled,
		private readonly bool $isActive,
	)
	{
	}

	public function isAvailable(): bool
	{
		return $this->isAvailable;
	}

	public function isEnabled(): bool
	{
		return $this->isEnabled;
	}

	public function isActive(): bool
	{
		return $this->isActive;
	}

	public function isApplicableToCommand(Command $command): bool
	{
		return $this->isApplicable([
			'commandName' => $command->getCommandName(),
			'queue' => $command->getQueue(),
		]);
	}

	public function isApplicable(array $params): bool
	{
		$commandName = $params['commandName'] ?? null;
		$queue = $params['queue'] ?? null;

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$isDocumentGeneration = (
			$commandName === DocumentTransformer::getTransformerCommandName()
			&& $queue === 'documentgenerator_create'
		);

		return $isDocumentGeneration;
	}
}
