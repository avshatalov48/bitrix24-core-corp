<?php
namespace Bitrix\Tasks\Internals\Project\UserOption;

use Bitrix\Tasks\Integration\Pull\PushCommand;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Task\ProjectUserOptionTable;
use Bitrix\Tasks\Util\Result;

/**
 * Class UserOptionController
 *
 * @package Bitrix\Tasks\Internals\Project\UserOption
 */
class UserOptionController
{
	private static $instances = [];

	private $userId;
	private $projectId;

	private function __construct(int $userId, int $projectId)
	{
		$this->userId = $userId;
		$this->projectId = $projectId;
	}

	public static function getInstance(int $userId, int $projectId): UserOptionController
	{
		if (
			!array_key_exists($userId, static::$instances)
			|| !is_array(static::$instances[$userId])
			|| !array_key_exists($projectId, static::$instances[$userId])
		)
		{
			static::$instances[$userId][$projectId] = new self($userId, $projectId);
		}

		return static::$instances[$userId][$projectId];
	}

	public function add(int $option): Result
	{
		$addResult = new Result();

		if (!$this->isOption($option))
		{
			$addResult->addError(0, 'Wrong option.');
			return $addResult;
		}

		$data = [
			'PROJECT_ID' => $this->projectId,
			'USER_ID' => $this->userId,
			'OPTION_CODE' => $option,
		];

		$item = ProjectUserOptionTable::getList([
			'select' => ['ID'],
			'filter' => $data,
		])->fetch();

		if (!$item)
		{
			$tableAddResult = ProjectUserOptionTable::add($data);
			if (!$tableAddResult->isSuccess())
			{
				$addResult->addError(2, 'Adding to table failed.');
				return $addResult;
			}

			$this->onAfterOptionAdded($option);

			return $addResult;
		}

		$addResult->addError(1, 'Option is already exist.');

		return $addResult;
	}

	public function delete(int $option): Result
	{
		$deleteResult = new Result();

		if (!$this->isOption($option))
		{
			$deleteResult->addError(0, 'Wrong option.');
			return $deleteResult;
		}

		$item = ProjectUserOptionTable::getList([
			'select' => ['ID'],
			'filter' => [
				'PROJECT_ID' => $this->projectId,
				'USER_ID' => $this->userId,
				'OPTION_CODE' => $option,
			],
		])->fetch();

		if ($item)
		{
			$tableDeleteResult = ProjectUserOptionTable::delete($item);
			if (!$tableDeleteResult->isSuccess())
			{
				$deleteResult->addError(1, 'Deleting from table failed.');
				return $deleteResult;
			}

			$this->onAfterOptionDeleted($option);
		}

		return $deleteResult;
	}

	private function onAfterOptionAdded(int $option): void
	{
		$this->onAfterOptionChanged($option, true);
	}

	private function onAfterOptionDeleted(int $option): void
	{
		$this->onAfterOptionChanged($option, false);
	}

	private function onAfterOptionChanged(int $option, bool $added): void
	{
		if (!$this->isOption($option))
		{
			return;
		}

		PushService::addEvent(
			$this->userId,
			[
				'module_id' => 'tasks',
				'command' => PushCommand::PROJECT_USER_OPTION_UPDATED,
				'params' => [
					'PROJECT_ID' => $this->projectId,
					'USER_ID' => $this->userId,
					'OPTION' => $option,
					'ADDED' => $added,
				],
			]
		);
	}

	public function isOptionSet(int $option): bool
	{
		return $this->isOption($option) && in_array($option, $this->getOptions(), true);
	}

	private function isOption(int $option): bool
	{
		return in_array($option, $this->getAllowedOptions(), true);
	}

	private function getAllowedOptions(): array
	{
		$allowedOptions = [];

		$reflect = new \ReflectionClass(UserOptionTypeDictionary::class);
		foreach ($reflect->getConstants() as $option)
		{
			$allowedOptions[] = $option;
		}

		return $allowedOptions;
	}

	private function getOptions(): array
	{
		$optionsResult = ProjectUserOptionTable::getList([
			'select' => ['OPTION_CODE'],
			'filter' => [
				'PROJECT_ID' => $this->projectId,
				'USER_ID' => $this->userId,
			],
		]);

		$options = [];
		while ($option = $optionsResult->fetch())
		{
			$options[] = (int)$option['OPTION_CODE'];
		}

		return $options;
	}
}