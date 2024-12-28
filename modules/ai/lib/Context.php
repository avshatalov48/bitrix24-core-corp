<?php

namespace Bitrix\AI;

use Bitrix\AI\Context\Language;
use Bitrix\AI\Facade\User;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

/**
 * AI request context
 */
class Context
{
	private mixed $contextParams = null;
	private ?Language $contextLanguage = null;
	private array $eventGetMessagesNextStep = [];

	public function __construct(
		private string $moduleId,
		private string $contextId,
		private ?int $userId = null,
	) {}

	public static function getFake(): self
	{
		return new self('fake', 'fake');
	}

	public function isFake(): bool
	{
		return $this->moduleId === 'fake' && $this->contextId === 'fake';
	}

	public function getModuleId(): string
	{
		return $this->moduleId;
	}

	public function getContextId(): string
	{
		return $this->contextId;
	}

	public function getUserId(): int
	{
		// todo: pay attention. User should be set manually
		return $this->userId ?? User::getCurrentUserId();
	}

	public function setParameters(mixed $params): void
	{
		$this->contextParams = $params;
	}

	public function getParameters(): mixed
	{
		return $this->contextParams;
	}

	/**
	 * If context must have special language - set them.
	 * @param Language|string $language
	 * @return void
	 */
	public function setLanguage(Language|string $language): void
	{
		if (is_string($language))
		{
			$this->contextLanguage = new Language($language);
		}
		else
		{
			$this->contextLanguage = $language;
		}
	}

	/**
	 * Get context language, if set
	 * @return Language|null
	 */
	public function getLanguage(): ?Language
	{
		return $this->contextLanguage;
	}

	/**
	 * Returns context messages for current Context.
	 * For example, you can send another post's comments in current request.
	 * In this case, external Engine will understand the context and be able to answer more accurately.
	 *
	 * @return Context\Message[]
	 */
	public function getMessages(): array
	{
		$messages = [];
		$eventId = $this->getModuleId() . $this->getContextId();

		if (
			array_key_exists($eventId, $this->eventGetMessagesNextStep)
			&& $this->eventGetMessagesNextStep[$eventId] === null
		)
		{
			return [];
		}

		$event = new Event('ai', 'onContextGetMessages', [
			'module' => $this->getModuleId(),
			'id' => $this->getContextId(),
			'params' => $this->getParameters(),
			'next_step' => $this->eventGetMessagesNextStep[$eventId] ?? null,
		]);
		$event->send();

		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() !== EventResult::ERROR)
			{
				$parameters = $eventResult->getParameters();
				if (empty($parameters['messages']) || !is_array($parameters['messages']))
				{
					continue;
				}

				$messages = array_merge($messages, Context\Message::retrieveFromArrays($parameters['messages']));

				$this->eventGetMessagesNextStep[$eventId] = $parameters['next_step'] ?? null;
			}
		}

		return $messages;
	}

	/**
	 * Packs the Context's data in string and returns it.
	 *
	 * @return string
	 */
	public function pack(): string
	{
		return json_encode([
			'moduleId' => $this->getModuleId(),
			'contextId' => $this->getContextId(),
			'userId' => $this->getUserId(),
			'parameters' => $this->getParameters(),
			'languageCode'=> $this->getLanguage()?->getCode()
		]);
	}

	/**
	 * Unpacks data and creates Context instance from it.
	 *
	 * @param string $packedData Packed data by using method self::pack().
	 * @return self
	 */
	public static function unpack(string $packedData): self
	{
		[
			'moduleId' => $moduleId,
			'contextId' => $contextId,
			'userId' => $userId,
			'parameters' => $parameters,
			'languageCode' => $languageCode,
		] = json_decode($packedData, true);

		$context = new self(
			$moduleId,
			$contextId,
			$userId,
		);
		$context->setParameters($parameters);
		if ($languageCode !== null)
		{
			$context->setLanguage($languageCode);
		}

		return $context;
	}

	/**
	 * Removes all about Context.
	 *
	 * @param array<string>|string $context Specified context.
	 * @return void
	 */
	public static function clearContext(array|string $context): void
	{
		History\Manager::clearHistoryByContext($context);
	}
}
