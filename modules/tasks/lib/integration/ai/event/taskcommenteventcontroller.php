<?php

namespace Bitrix\Tasks\Integration\AI\Event;

use Bitrix\Forum\MessageTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Integration\AI\Event\Message\Message;
use Bitrix\Tasks\Integration\AI\Event\Message\MessageCollection;
use Bitrix\Tasks\Integration\AI\User\Author;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\TaskObject;
use CTextParser;

class TaskCommentEventController implements EventControllerInterface
{
	private const LIMIT = 1500;

	private int $taskId;
	private string $xmlId;

	private ?TaskObject $task;
	private CTextParser $parser;

	public function __construct(int $taskId, string $xmlId)
	{
		$this->taskId = $taskId;
		$this->xmlId = $xmlId;

		$this->init();
	}

	public function getOriginalMessage(): Message
	{
		$description = $this->task->getDescription();
		$author = new Author($this->task->getId());

		return new Message(
			$this->parser::clearAllTags($description),
			true,
			$author->toMeta(),
		);
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getAdditionalMessages(): MessageCollection
	{
		$collection = new MessageCollection();
		if (!Loader::includeModule('forum'))
		{
			return $collection;
		}

		$query = MessageTable::query();
		$query
			->setSelect(['ID', 'POST_MESSAGE'])
			->where('XML_ID', $this->xmlId)
			->whereNull('SERVICE_TYPE')
			->whereNull('PARAM1')
			->setOrder(['POST_DATE' => 'desc'])
			->setLimit(static::LIMIT);

		$postMessages = $query->exec()->fetchCollection();
		foreach ($postMessages as $postMessage)
		{
			$message = $this->parser::clearAllTags($postMessage->getPostMessage());
			$collection->add(new Message($message));
		}

		return $collection;
	}

	private function init(): void
	{
		$this->task = TaskRegistry::getInstance()->getObject($this->taskId);
		$this->parser = new CTextParser();
	}
}
