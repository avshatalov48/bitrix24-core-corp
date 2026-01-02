<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Document\Models\DocumentService;
use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Disk\Document\Models\DocumentSessionContext;
use Bitrix\Disk\File;
use Bitrix\Disk\Internals\Error\Error as InternalDiskError;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Disk\Version;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\NotImplementedException;

abstract class SessionManager implements IErrorable
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
	protected DocumentService|null $service;
	/** @var  ErrorCollection */
	protected $errorCollection;
	private string|null $externalHash;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection();
	}

	public function lock(): bool
	{
		return Application::getConnection()->lock($this->getLockKey(), self::LOCK_LIMIT);
	}

	public function unlock(): void
	{
		Application::getConnection()->unlock($this->getLockKey());
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

	public function setExternalHash(string $hash): self
	{
		$this->externalHash = $hash;

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

	public function findOrCreateSession($exactUser = false): ?DocumentSession
	{
		$session = $this->findSession($exactUser) ?: $this->addSession();
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

	public function findSession($exactUser = false): ?DocumentSession
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

		if ($exactUser)
		{
			return null;
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

	/**
	 * @throws ArgumentException
	 */
	protected function buildFilter(): array
	{
		$filter = [
			'USER_ID' => $this->userId,
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
		elseif ($this->externalHash)
		{
			$filter['EXTERNAL_HASH'] = $this->externalHash;
		}
		else
		{
			throw new ArgumentException('Neither file nor version nor attached object were installed.');
		}

		return $filter;
	}

	/**
	 * @return DocumentSession|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 */
	public function addSession(): ?DocumentSession
	{
		$fields = $this->buildFilter();
		$fields['OWNER_ID'] = $this->userId;
		$fields['CONTEXT'] = $this->sessionContext->toJson();
		$fields['SERVICE'] = $this->service->value;

		return DocumentSession::add($fields, $this->errorCollection);
	}

	/**
	 * @return InternalDiskError[]
	 */
	public function getErrors(): array
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * @param string $code
	 * @return InternalDiskError[]
	 */
	public function getErrorsByCode($code): array
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * @param string $code
	 * @return InternalDiskError|Error|null
	 */
	public function getErrorByCode($code): InternalDiskError|Error|null
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}