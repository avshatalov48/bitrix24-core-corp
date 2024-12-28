<?php
namespace Bitrix\AI\Controller;

use Bitrix\AI\Config;
use Bitrix\AI\Container;
use Bitrix\AI\Context\Message;
use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IQueue;
use Bitrix\AI\History;
use Bitrix\AI\Result;
use Bitrix\AI\Prompt\Role;
use Bitrix\AI\Role\RoleManager;
use Bitrix\AI\Services\PromptService;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;

class Text extends Controller
{
	protected ?string $category = Engine::CATEGORIES['text'];

	public function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
		];
	}

	/**
	 * Makes request to AI Engine. The model will return one or more predicted completions.
	 *
	 * @param string|array $prompt Prompt to completion.
	 * @param string | null $engineCode Engine's code (by default is taken first Engine in category).
	 * @param array $markers Marker for replacing in prompt ({key} => value).
	 * @param array $parameters Additional params for tuning query.
	 * @return array
	 */
	public function completionsAction($prompt, string $engineCode = null, array $markers = [], string $roleCode = null, array $parameters = []): array
	{
		if (!empty($this->getErrors()))
		{
			return [];
		}

		Config::setPersonalValue('first_launch', 'N');

		$engine = $engineCode === null
			? Engine::getByCategory($this->category, $this->context)
			: Engine::getByCode($engineCode, $this->context, $this->category);

		if (!$engine)
		{
			$this->addError(new Error('Engine not found'));

			return [];
		}

		if (!$this->checkAgreementAccepted($engine))
		{
			return [];
		}

		/** @var PromptService $promptService */
		$promptService = Container::init()->getItem(PromptService::class);
		$payload = $promptService->getPayloadForTextPrompt(
			$prompt,
			(int)$this->getCurrentUser()->getId(),
			$markers,
			$parameters
		);

		if (empty($payload))
		{
			$this->addError(new Error('Prompt not found'));

			return [];
		}

		$resultData = null;
		$queueHash = null;
		$roleCode = $roleCode ?: RoleManager::getUniversalRoleCode();
		$role = Role::get($roleCode) ?: Role::getUniversalRole();

		$payload->setRole($role);

		$engine
			->setPayload($payload)
			->setUserParameters($parameters)
			->setAnalyticData($parameters['bx_analytic'] ?? [])
			->setHistoryState($this->isTrue($parameters['bx_history'] ?? false))
			->onSuccess(function(Result $result, ?string $hash = null) use(&$resultData, &$queueHash) {
				$resultData = $result->getPrettifiedData();
				$queueHash = $hash;
			})
			->onError(function(Error $error) {
				$this->addError($error);
			})
		;

		if ($this->shouldUseQueueMode())
		{
			$engine->completionsInQueue();
		}
		else
		{
			$engine->completions();
		}

		return [
			'result' => $resultData,
			'last' => $engine->shouldWriteHistory()
				? History\Manager::getLastItem($this->context)
				: History\Manager::getFakeItem($resultData, $engine)->toArray()
			,
			'queue' => $queueHash,
		];
	}

	private function shouldUseQueueMode(): bool
	{
		return Config::getValue('queue_mode') === 'Y';
	}

	/**
	 * Gets data for feedback form.
	 *
	 * @param array $parameters
	 * @return array
	 */
	public function getFeedbackDataAction(array $parameters): array
	{
		$engine = Engine::getByCategory($this->category, $this->context);

		if (!$engine)
		{
			$this->addError(new Error('Engine not found'));

			return [];
		}

		$engine->setUserParameters($parameters);

		/** @var Message[] $messages */
		$messages = $engine->getIEngine()->getMessages();

		$originalMessage = $this->getOriginalMessageTextFromContextMessages($messages);
		$preparedMessages = array_map(static fn($message) => $message->toArray(), $messages);

		return [
			'context_messages' => $preparedMessages,
			'original_message' => $originalMessage,
		];
	}

	/**
	 * @param array $contextMessages
	 *
	 * @return string|null
	 */
	private function getOriginalMessageTextFromContextMessages(array $contextMessages): ?string
	{
		/** @var Message|null $firstMessage */
		$firstMessage = $contextMessages[0] ?? null;
		$originalMessage = null;

		if ($firstMessage && $firstMessage->getMeta()['is_original_message'])
		{
			$originalMessage = $firstMessage->getContent();
		}

		return $originalMessage;
	}
}
