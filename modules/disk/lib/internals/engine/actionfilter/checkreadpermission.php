<?php

namespace Bitrix\Disk\Internals\Engine\ActionFilter;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Document\TrackedObject;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Storage;
use Bitrix\Disk\Type;
use Bitrix\Disk\Version;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

class CheckReadPermission extends ActionFilter\Base
{
	const ERROR_COULD_NOT_READ_OBJECT = 'read_right';

	protected $currentUser;

	public function __construct()
	{
		parent::__construct();
		$this->currentUser = CurrentUser::get();
	}

	public function onBeforeAction(Event $event)
	{
		foreach ($this->action->getArguments() as $argument)
		{
			if ($argument instanceof BaseObject)
			{
				if (!$this->checkObject($argument))
				{
					return new EventResult(EventResult::ERROR, null, null, $this);
				}
			}
			elseif ($argument instanceof Storage)
			{
				if (!$argument->canRead($argument->getSecurityContext($this->currentUser->getId())))
				{
					$this->addReadError();

					return new EventResult(EventResult::ERROR, null, null, $this);
				}
			}
			elseif ($argument instanceof AttachedObject)
			{
				if (!$argument->canRead($this->currentUser->getId()))
				{
					$this->addReadError();

					return new EventResult(EventResult::ERROR, null, null, $this);
				}
			}
			elseif ($argument instanceof TrackedObject)
			{
				if (!$this->checkTrackedObject($argument))
				{
					$this->addReadError();

					return new EventResult(EventResult::ERROR, null, null, $this);
				}
			}
			elseif ($argument instanceof Version)
			{
				$file = $argument->getObject();
				if (!$file)
				{
					continue;
				}

				$securityContext = $file->getStorage()->getSecurityContext($this->currentUser->getId());
				if (!$file->canRead($securityContext))
				{
					$this->addReadError();

					return new EventResult(EventResult::ERROR, null, null, $this);
				}
			}
			elseif ($argument instanceof Type\ObjectCollection)
			{
				foreach ($argument as $item)
				{
					if (!$this->checkObject($item))
					{
						return new EventResult(EventResult::ERROR, null, null, $this);
					}
				}
			}
			elseif ($argument instanceof Type\TrackedObjectCollection)
			{
				foreach ($argument as $item)
				{
					if (!$this->checkTrackedObject($item))
					{
						return new EventResult(EventResult::ERROR, null, null, $this);
					}
				}
			}
		}

		return null;
	}

	protected function checkTrackedObject(TrackedObject $trackedObject): bool
	{
		if (!$this->currentUser->getId() || !$trackedObject->canRead($this->currentUser->getId()))
		{
			$this->addReadError();

			return false;
		}

		return true;
	}

	protected function checkObject(BaseObject $object): bool
	{
		$securityContext = $object->getStorage()?->getSecurityContext($this->currentUser->getId());
		if (!$securityContext)
		{
			return false;
		}

		if (!$object->canRead($securityContext))
		{
			$this->addReadError();

			return false;
		}

		return true;
	}

	protected function addReadError(): void
	{
		$this->errorCollection[] = new Error(
			Loc::getMessage('DISK_CHECK_READ_PERMISSION_ERROR_MESSAGE'), self::ERROR_COULD_NOT_READ_OBJECT
		);
	}

}