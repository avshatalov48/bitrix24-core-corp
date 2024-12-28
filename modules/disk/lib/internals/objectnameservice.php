<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internals;

use Bitrix\Disk\BaseObject;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

/**
 * Class ObjectNameService
 * Helps to prepare unique name for object in folder in concurrent environment.
 */
final class ObjectNameService
{
	public const ERROR_NON_UNIQUE_NAME = BaseObject::ERROR_NON_UNIQUE_NAME;
	public const ERROR_LOCK_UNIQUE_NAME = 'DISK_ONS_22001';

	public const TYPE_FILE = ObjectTable::TYPE_FILE;
	public const TYPE_FOLDER = ObjectTable::TYPE_FOLDER;

	protected const MAX_ATTEMPT = 10;
	protected const LOCK_DEFAULT_WAITING_TIMEOUT = 3;

	private string $desiredName;
	private bool $shouldGenerateUniqueName = false;
	private bool $shouldRequireOpponentId = false;
	private ?int $underObjectId;
	private int $excludeId;
	private int $objectType;

	public function __construct(string $desiredName, ?int $underObjectId, int $objectType = self::TYPE_FILE)
	{
		$this->desiredName = $desiredName;
		$this->underObjectId = $underObjectId;
		$this->objectType = $objectType;
	}

	public function excludeId(int $id): void
	{
		$this->excludeId = $id;
	}

	public function requireOpponentId(): void
	{
		$this->shouldRequireOpponentId = true;
	}

	public function requireUniqueName(): void
	{
		$this->shouldGenerateUniqueName = true;
	}

	private function createNonUniqueError(): Error
	{
		if ($this->isFile())
		{
			return new Error(Loc::getMessage('DISK_OBJECT_NAME_SERVICE_ERROR_FILE_NON_UNIQUE_NAME'), self::ERROR_NON_UNIQUE_NAME);
		}
		if ($this->isFolder())
		{
			return new Error(Loc::getMessage('DISK_OBJECT_NAME_SERVICE_ERROR_FOLDER_NON_UNIQUE_NAME'), self::ERROR_NON_UNIQUE_NAME);
		}

		throw new \LogicException('Unknown object type');
	}

	private function createNonUniqueLockError(): Error
	{
		if ($this->isFile())
		{
			return new Error(Loc::getMessage('DISK_OBJECT_NAME_SERVICE_ERROR_FILE_LOCK_NAME'), self::ERROR_LOCK_UNIQUE_NAME);
		}
		if ($this->isFolder())
		{
			return new Error(Loc::getMessage('DISK_OBJECT_NAME_SERVICE_ERROR_FOLDER_LOCK_NAME'), self::ERROR_LOCK_UNIQUE_NAME);
		}

		throw new \LogicException('Unknown object type');
	}

	public function prepareName(): Result
	{
		if ($this->shouldLockName() === false)
		{
			return $this->buildRenameResult($this->getDesiredName());
		}

		if ($this->shouldGenerateUniqueName === false)
		{
			$nonUniqueError = $this->createNonUniqueError();

			if ($this->lock() === false)
			{
				$result = new Result();
				$result->addErrors([
					$nonUniqueError,
					$this->createNonUniqueLockError(),
				]);
				
				return $result;
			}

			$uniqueResult = $this->isUniqueName(
				excludeId: $this->excludeId ?? null,
				returnOpponentId: $this->shouldRequireOpponentId
			);
			if ($uniqueResult->isSuccess() === false)
			{
				return (new Result())
					->addError($nonUniqueError)
					->setData($uniqueResult->getData())
				;
			}

			return $this->buildRenameResult($this->getDesiredName());
		}

		return $this->buildRenameResult($this->generateUniqueName());
	}

	private function buildRenameResult(string $name): Result
	{
		return new class($name) extends Result {
			public function __construct(private string $name)
			{
				parent::__construct();
			}

			public function getName(): string
			{
				return $this->name;
			}
		};
	}

	private function getDesiredName(): string
	{
		return $this->desiredName;
	}

	private function getUnderObjectId(): ?int
	{
		return $this->underObjectId;
	}

	private function lock(int $timeout = self::LOCK_DEFAULT_WAITING_TIMEOUT): bool
	{
		return Application::getConnection()->lock($this->getLockKey(), $timeout);
	}

	private function unlock(): void
	{
		$connection = Application::getConnection();

		$connection->unlock($this->getLockKey());
	}

	private function getLockKey(): string
	{
		$keyData = [
			$this->getDesiredName(),
			$this->getUnderObjectId() ?? 0,
		];

		return implode('|', array_values($keyData));
	}

	private function shouldLockName(): bool
	{
		return $this->getUnderObjectId() !== null;
	}

	public function reserveName(): bool
	{
		if (!$this->shouldLockName())
		{
			return true;
		}

		return $this->lock();
	}

	public function isUniqueName(?int $excludeId = null, bool $returnOpponentId = false): Result
	{
		$result = new Result();
		if ($this->getUnderObjectId() === null)
		{
			return $result;
		}

		$parameters = [
			'select' => ['NAME'],
			'filter' => [
				'PARENT_ID' => $this->getUnderObjectId(),
				'=NAME' => $this->getDesiredName(),
			],
			'limit' => 1,
		];

		if ($excludeId !== null)
		{
			$parameters['filter']['!ID'] = $excludeId;
		}

		if ($returnOpponentId)
		{
			$parameters['select'][] = 'ID';
		}

		$opponent = ObjectTable::getList($parameters)->fetch();
		if (!$opponent)
		{
			return $result;
		}

		if ($returnOpponentId)
		{
			$result->setData(['opponentId' => $opponent['ID']]);
		}
		$result->addError(new Error('Name is not unique'));

		return $result;
	}

	/**
	 * Appends (1), (2), etc. if name is non unique in target directory.
	 * @return string
	 */
	private function generateUniqueName(): string
	{
		if ($this->isUniqueName($this->excludeId ?? null)->isSuccess())
		{
			return $this->desiredName;
		}

		$count = 0;
		$underObjectId = $this->getUnderObjectId();
		[$mainPartName, $suffix] = $this->extractSuffixAndMainPart($this->desiredName);

		while (true)
		{
			$count++;
			[$this->desiredName] = $this->getNextGeneratedName(
				$mainPartName,
				$suffix,
				$underObjectId,
				withoutLike: $count > 2,
				fastWayById: $count === 1,
			);

			if ($this->lock(1) && $this->isUniqueName($this->excludeId ?? null)->isSuccess())
			{
				break;
			}

			if ($count > self::MAX_ATTEMPT)
			{
				$this->desiredName = $this->generateFallbackName($mainPartName, $suffix);
				break;
			}
		}

		return $this->desiredName;
	}

	private function extractSuffixAndMainPart(string $name): array
	{
		$mainParts = explode('.', $name);
		$partsCount = \count($mainParts);
		//name without extension
		if ($partsCount <= 1)
		{
			return [$name, null];
		}

		$suffix = array_pop($mainParts);

		//name with a few dots like "example.tar.gz"
		if ($partsCount > 2 && preg_match('/^[a-zA-Z][a-zA-Z0-9]{1,2}$/', $mainParts[$partsCount - 2]))
		{
			$suffix = array_pop($mainParts) . '.' . $suffix;
		}

		$mainPart = implode('.', $mainParts);

		return [$mainPart, $suffix];
	}

	private function getNextGeneratedName(
		string $mainPartName,
		?string $suffix,
		int $underObjectId,
		bool $withoutLike = false,
		bool $fastWayById = false,
	): array
	{
		$left = $mainPartName . ' (';
		$right = ')';

		if ($suffix)
		{
			$right = ').' . $suffix;
		}
		$lengthR = mb_strlen($right);
		$lengthL = mb_strlen($left);

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$filterByLike = "NAME LIKE '" . $sqlHelper->forSql($left) . "%' AND";
		if ($withoutLike)
		{
			$filterByLike = '';
		}

		$regexpCondition = $sqlHelper->getRegexpOperator(
			"SUBSTR(NAME, {$lengthL} + 1, CHAR_LENGTH(NAME) - {$lengthL} - {$lengthR})",
			"'^[1-9][[:digit:]]*$'"
		);

		$order = 'CHAR_LENGTH(NAME) DESC, NAME DESC';
		if ($fastWayById)
		{
			$order = 'ID DESC';
		}

		$sql = "
			SELECT NAME FROM b_disk_object 
			WHERE 
				PARENT_ID = {$underObjectId} AND 
				{$filterByLike}
				LEFT(NAME, {$lengthL}) = '" . $sqlHelper->forSql($left) . "' AND
				{$regexpCondition} AND
				RIGHT(NAME, {$lengthR}) = '" . $sqlHelper->forSql($right) . "'
			ORDER BY {$order}
			LIMIT 1;
		";

		$row = $connection->query($sql)->fetch();
		if (!$row)
		{
			$newName = $mainPartName . ' (1)';
			if ($suffix)
			{
				$newName .= '.' . $suffix;
			}

			return [
				$newName,
				null,
				1
			];
		}

		$counter = mb_substr($row['NAME'], $lengthL, mb_strlen($row['NAME']) - $lengthL - $lengthR);
		$counter = (int)$counter + 1;

		return [
			$left . $counter . $right,
			$row['NAME'],
			$counter
		];
	}

	private function generateFallbackName(string $mainPartName, string $suffix): string
	{
		return implode('.', array_filter([
			$mainPartName . ' ' . time(),
			$suffix
		]));
	}

	private function isFolder(): bool
	{
		return $this->objectType === self::TYPE_FOLDER;
	}

	private function isFile(): bool
	{
		return $this->objectType === self::TYPE_FILE;
	}
}