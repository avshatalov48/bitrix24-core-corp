<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\RandomSequence;
use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\Scrum\Checklist\TypeChecklistFacade;
use Bitrix\Tasks\Util\Result;

class DefinitionOfDoneService implements Errorable
{
	const ERROR_COULD_NOT_MERGE_LIST = 'TASKS_DOD_01';
	const ERROR_COULD_NOT_GET_DATA = 'TASKS_DOD_02';
	const ERROR_COULD_NOT_IS_EMPTY = 'TASKS_DOD_03';
	const ERROR_COULD_NOT_ADD_DEFAULT_LIST = 'TASKS_DOD_04';
	const ERROR_COULD_NOT_REMOVE_LIST = 'TASKS_DOD_05';
	const ERROR_COULD_NOT_SAVE_SETTINGS = 'TASKS_DOD_06';

	private $executiveUserId;
	private $errorCollection;

	public function __construct(int $executiveUserId = 0)
	{
		$this->executiveUserId = $executiveUserId;

		$this->errorCollection = new ErrorCollection;
	}

	public function mergeList(string $facade, int $entityId, array $items): Result
	{
		$result = new Result();

		try
		{
			foreach ($items as $id => $item)
			{
				$item['ID'] = ((int) $item['ID'] === 0 ? null : (int) $item['ID']);
				$item['IS_COMPLETE'] = ($item['IS_COMPLETE'] === "true");
				$item['IS_IMPORTANT'] = ($item['IS_IMPORTANT'] === "true");

				$items[$item['NODE_ID']] = $item;
				unset($items[$id]);
			}

			$result = $facade::merge($entityId, $this->executiveUserId, $items, []);
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_MERGE_LIST));
		}

		return $result;
	}

	public function removeList(string $facade, int $entityId): void
	{
		try
		{
			$facade::$currentAccessAction = CheckListFacade::ACTION_REMOVE;
			$facade::deleteByEntityId($entityId, $this->executiveUserId);
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_REMOVE_LIST
				)
			);
		}
	}

	public function getComponent(int $entityId, string $entityType, array $items): Component
	{
		$randomGenerator = new RandomSequence(rand());

		return new Component(
			'bitrix:tasks.widget.checklist.new',
			'',
			[
				'ENTITY_ID' => $entityId,
				'ENTITY_TYPE' => $entityType,
				'DATA' => $items,
				'CONVERTED' => true,
				'CAN_ADD_ACCOMPLICE' => false,
				'SIGNATURE_SEED' => $randomGenerator->randString(6),
				'SHOW_COMPLETE_ALL_BUTTON' => $entityType === 'SCRUM_ITEM',
				'COLLAPSE_ON_COMPLETE_ALL' => false,
			]
		);
	}

	public function getTypeItems(int $entityId): array
	{
		$items = [];

		try
		{
			$items = TypeChecklistFacade::getItemsForEntity($entityId, $this->executiveUserId);
			foreach (array_keys($items) as $id)
			{
				$items[$id]['COPIED_ID'] = $id;
				unset($items[$id]['ID']);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_GET_DATA
				)
			);
		}

		return $items;
	}

	public function isTypeListEmpty(int $entityId): bool
	{
		try
		{
			return empty(TypeChecklistFacade::getItemsForEntity($entityId, $this->executiveUserId));
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_IS_EMPTY
				)
			);

			return false;
		}
	}

	public function createDefaultList(int $entityId): void
	{
		try
		{
			$result = TypeChecklistFacade::add($entityId, $this->executiveUserId, [
				'TITLE' => Loc::getMessage('TASKS_SCRUM_DEFINITION_OF_DONE_0'),
				'IS_COMPLETE' => 'N',
				'PARENT_ID' => 0
			]);
			$newItem = $result->getData()['ITEM'];
			$newItemId = $newItem->getFields()['ID'];
			for ($i = 1; $i <= 3; $i++)
			{
				TypeChecklistFacade::add($entityId, $this->executiveUserId, [
					'TITLE' => Loc::getMessage('TASKS_SCRUM_DEFINITION_OF_DONE_'.$i),
					'IS_COMPLETE' => 'N',
					'PARENT_ID' => $newItemId
				]);
			}
		}
		catch (\Exception $exception)
		{
			try
			{
				TypeChecklistFacade::deleteByEntityId($entityId, $this->executiveUserId);
			}
			catch (\Exception $exception)
			{
				$this->errorCollection->setError(
					new Error(
						$exception->getMessage(),
						self::ERROR_COULD_NOT_ADD_DEFAULT_LIST
					)
				);
			}

			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_ADD_DEFAULT_LIST
				)
			);
		}
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}