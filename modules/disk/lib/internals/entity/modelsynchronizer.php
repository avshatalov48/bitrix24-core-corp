<?php

namespace Bitrix\Disk\Internals\Entity;

use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\FileLink;
use Bitrix\Disk\Folder;
use Bitrix\Disk\FolderLink;
use Bitrix\Main\EventManager;
use Bitrix\Main;

final class ModelSynchronizer
{
	private static $instance;
	/** @var EventManager */
	private $eventManager;
	/** @var \SplObjectStorage */
	private $modelToHandlers;

	private function __construct()
	{
		$this->eventManager = EventManager::getInstance();
		$this->modelToHandlers = new \SplObjectStorage();
	}

	private function __clone()
	{
	}

	/**
	 * Returns singleton.
	 *
	 * @return static
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			self::$instance = new static();
		}

		return self::$instance;
	}

	private function generateEventName(BaseObject $link)
	{
		if($link instanceof File)
		{
			$realClass = File::className();
		}
		else
		{
			$realClass = Folder::className();
		}

		return $realClass . '|' . $link->getRealObjectId();
	}

	/**
	 * Subscribes link on its real object to synchronize attributes.
	 *
	 * @param BaseObject $link File or folder link.
	 * @return bool
	 */
	public function subscribeOnRealObject(BaseObject $link)
	{
		if (!$link instanceof FolderLink && !$link instanceof FileLink)
		{
			return false;
		}

		/** @var FileLink|FolderLink $link */

		if ($this->modelToHandlers->contains($link))
		{
			return true;
		}

		$handlerId = $this->eventManager->addEventHandler(
			Driver::INTERNAL_MODULE_ID,
			$this->generateEventName($link),
			function(Main\Event $event) use ($link)
			{
				$link->onModelSynchronize($event->getParameter('attributes'));
			}
		);

		$this->modelToHandlers->attach($link, $handlerId);

		return true;
	}

	/**
	 * Unsubscribes link.
	 *
	 * @param BaseObject $link File or folder link.
	 * @return void
	 */
	public function unsubscribe(BaseObject $link)
	{
		if (!$this->modelToHandlers->contains($link))
		{
			return;
		}

		$handlerId = $this->modelToHandlers[$link];
		$this->eventManager->removeEventHandler(
			Driver::INTERNAL_MODULE_ID,
			$this->generateEventName($link),
			$handlerId
		);

		$this->modelToHandlers->detach($link);
	}

	/**
	 * Triggers event to send data to subscribers.
	 *
	 * @param BaseObject $baseObject File or Folder.
	 * @param array $attributes Attributes.
	 * @return void
	 */
	public function trigger(BaseObject $baseObject, array $attributes)
	{
		$event = new Main\Event(
			Driver::INTERNAL_MODULE_ID,
			$this->generateEventName($baseObject),
			array(
				'id' => $baseObject->getId(),
				'attributes' => $attributes,
			)
		);

		$event->send($this);
	}

	/**
	 * Returns the fully qualified name of this class.
	 * @return string
	 */
	public static function className()
	{
		return get_called_class();
	}
}