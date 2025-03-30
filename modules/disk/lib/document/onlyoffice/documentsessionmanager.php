<?php

namespace Bitrix\Disk\Document\OnlyOffice;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Document\Models\DocumentService;
use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Disk\Document\Models\DocumentSessionContext;
use Bitrix\Disk\File;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Disk\Version;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;

final class DocumentSessionManager implements IErrorable
{
	protected const LOCK_LIMIT = 15;

	/** @var Version */
	protected $version;
	/** @var File */
	protected $file;
	/** @var AttachedObject */
	protected $attachedObject;
	/** @var int */
	protected $userId;
	/** @var int */
	protected $sessionType;
	/** @var DocumentSessionContext */
	protected $sessionContext;
	protected DocumentService $service = DocumentService::OnlyOffice;
	/** @var  ErrorCollection */
	protected $errorCollection;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection();
	}

	public function lock(): bool
	{
		$connection = Application::getConnection();

		return $connection->lock($this->getLockKey(), self::LOCK_LIMIT);
	}

	public function unlock(): void
	{
		$connection = Application::getConnection();

		$connection->unlock($this->getLockKey());
	}

	protected function getLockKey(): string
	{
		$filter = $this->buildFilter();
		$keyData = [
			'TYPE' => $filter['TYPE'],
			'VERSION_ID' => $filter['VERSION_ID'] ?? 0,
			'OBJECT_ID' => $filter['OBJECT_ID'] ?? 0,
		];

		return implode('|', array_values($keyData));
	}

	public function setSessionType(int $sessionType): self
	{
		$this->sessionType = $sessionType;

		return $this;
	}

	public function setSessionContext(DocumentSessionContext $sessionContext): self
	{
		$this->sessionContext = $sessionContext;

		return $this;
	}

	public function setUserId(int $userId): self
	{
		$this->userId = $userId;

		return $this;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function setVersion(?Version $version): self
	{
		$this->version = $version;

		return $this;
	}

	public function setFile(?File $file): self
	{
		$this->file = $file;

		return $this;
	}

	public function setAttachedObject(?AttachedObject $attachedObject): self
	{
		$this->attachedObject = $attachedObject;

		return $this;
	}

	public function setService(DocumentService $service): self
	{
		$this->service = $service;

		return $this;
	}

	public function findOrCreateSession(): ?DocumentSession
	{
		$session = $this->findSession() ?: $this->addSession();
		if (!$session)
		{
			return null;
		}

		if ($session->isView() && $session->isOutdatedByFileContent())
		{
			$session = $this->addSession();
			if (!$session)
			{
				return null;
			}
		}

		if (!$session->belongsToUser($this->getUserId()))
		{
			$fork = $session->forkForUser($this->getUserId(), $this->sessionContext);
			if (!$fork)
			{
				$this->errorCollection->add($session->getErrors());
			}

			return $fork;
		}

		if ($session->isNonActive())
		{
			return $session->cloneWithNewHash($this->getUserId(), $this->sessionContext);
		}

		return $session;
	}

	public function findSession(): ?DocumentSession
	{
		$filter = $this->buildFilter();

		$models = DocumentSession::getModelList([
			'select' => ['*'],
			'filter' => $filter,
			'limit' => 1,
			'order' => ['ID' => 'DESC'],
		]);

		$session = array_shift($models);
		if ($session)
		{
			return $session;
		}

		unset($filter['USER_ID']);
		$models = DocumentSession::getModelList([
			'select' => ['*'],
			'filter' => $filter,
			'limit' => 1,
			'order' => ['ID' => 'DESC'],
		]);

		return array_shift($models);
	}

	protected function buildFilter(): array
	{
		$filter = [
			'USER_ID' => $this->userId,
			'TYPE' => $this->sessionType,
			'IS_EXCLUSIVE' => false,
			'STATUS' => DocumentSession::STATUS_ACTIVE,
			'VERSION_ID' => null,
			'SERVICE' => $this->service->value,
		];

		if ($this->version)
		{
			$filter['VERSION_ID'] = $this->version->getId();
			$filter['OBJECT_ID'] = $this->version->getObjectId();
		}
		elseif ($this->file)
		{
			$filter['OBJECT_ID'] = $this->file->getRealObjectId();
		}
		elseif ($this->attachedObject)
		{
			$filter['OBJECT_ID'] = $this->attachedObject->getObjectId();
			if ($this->attachedObject->isSpecificVersion())
			{
				$filter['VERSION_ID'] = $this->attachedObject->getVersionId();
			}
		}
		else
		{
			throw new ArgumentException('Neither file nor version nor attached object were installed.');
		}

		return $filter;
	}

	public function addSession(): ?DocumentSession
	{
		$fields = $this->buildFilter();
		$fields['OWNER_ID'] = $this->userId;
		$fields['CONTEXT'] = $this->sessionContext->toJson();
		$fields['SERVICE'] = $this->service->value;

		return DocumentSession::add($fields, $this->errorCollection);
	}

	/**
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * @param string $code
	 * @return Error[]
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * @param string $code
	 * @return Error|null
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}