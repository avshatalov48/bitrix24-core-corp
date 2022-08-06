<?php

namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Errorable;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\ORM\Query;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Tasks\Scrum\Form\EpicForm;
use Bitrix\Tasks\Scrum\Internal\EpicTable;

class EpicService implements Errorable
{
	const ERROR_COULD_NOT_ADD_EPIC = 'TASKS_EPS_01';
	const ERROR_COULD_NOT_GET_EPIC = 'TASKS_EPS_02';
	const ERROR_COULD_NOT_UPDATE_EPIC = 'TASKS_EPS_03';
	const ERROR_COULD_NOT_GET_LIST = 'TASKS_EPS_04';
	const ERROR_COULD_NOT_REMOVE_EPIC = 'TASKS_EPS_05';
	const ERROR_COULD_NOT_GET_EPICS = 'TASKS_EPS_06';
	const ERROR_COULD_NOT_ADD_FILES = 'TASKS_EPS_07';
	const ERROR_COULD_NOT_GET_UF_FIELD = 'TASKS_EPS_08';
	const ERROR_COULD_NOT_REMOVE_FILES = 'TASKS_EPS_09';

	private $userId;
	private $errorCollection;

	private static $allEpics = [];

	public function __construct(int $userId = 0)
	{
		$this->userId = $userId;

		$this->errorCollection = new ErrorCollection;
	}

	public function createEpic(EpicForm $epic, PushService $pushService = null): EpicForm
	{
		try
		{
			$result = EpicTable::add($epic->getFieldsToCreate());

			if ($result->isSuccess())
			{
				$epic->setId($result->getId());

				if ($pushService)
				{
					$pushService->sendAddEpicEvent($epic);
				}
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_ADD_EPIC);
			}

			return $epic;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_ADD_EPIC
				)
			);
		}

		return $epic;
	}

	/**
	 * Gets an epic by identifier.
	 *
	 * @param int $epicId
	 * @return EpicForm
	 */
	public function getEpic(int $epicId): EpicForm
	{
		$epic = new EpicForm();

		try
		{
			$queryObject = EpicTable::getList([
				'filter' => ['ID' => $epicId],
				'order' => ['ID']
			]);
			if ($epicData = $queryObject->fetch())
			{
				$epic->fillFromDatabase($epicData);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_GET_EPIC
				)
			);
		}

		return $epic;
	}

	/**
	 * Updates an epic.
	 *
	 * @param int $epicId
	 * @param EpicForm $epic
	 * @param PushService|null $pushService
	 * @return bool
	 */
	public function updateEpic(int $epicId, EpicForm $epic, PushService $pushService = null): bool
	{
		try
		{
			$result = EpicTable::update($epicId, $epic->getFieldsToUpdate());

			if ($result->isSuccess())
			{
				if ($pushService)
				{
					$pushService->sendUpdateEpicEvent($epic);
				}

				(new CacheService($epicId, CacheService::EPICS))->clean();

				return true;
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_UPDATE_EPIC);

				return false;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_UPDATE_EPIC
				)
			);

			return false;
		}
	}

	/**
	 *
	 *
	 * @param array $select
	 * @param array $filter
	 * @param array $order
	 * @param PageNavigation|null $nav
	 * @return Query\Result|null
	 */
	public function getList(
		array $select = [],
		array $filter = [],
		array $order = [],
		PageNavigation $nav = null
	): ?Query\Result
	{
		try
		{
			if (!Loader::includeModule('socialnetwork'))
			{
				$this->errorCollection->setError(
					new Error(
						'Unable to load socialnetwork.',
						self::ERROR_COULD_NOT_GET_LIST
					)
				);

				return null;
			}

			$query = new Query\Query(EpicTable::getEntity());

			if (empty($select))
			{
				$select = ['*'];
			}
			$query->setSelect($select);
			$query->setFilter($filter);
			$query->setOrder($order);

			if ($nav)
			{
				$query->setOffset($nav->getOffset());
				$query->setLimit($nav->getLimit() + 1);
			}

			$query->registerRuntimeField(
				'UG',
				new ReferenceField(
					'UG',
					UserToGroupTable::getEntity(),
					Join::on('this.GROUP_ID', 'ref.GROUP_ID')->where('ref.USER_ID', $this->userId),
					['join_type' => 'inner']
				)
			);

			$queryResult = $query->exec();

			return $queryResult;
		}
		catch (\Exception $e)
		{
			$this->errorCollection->setError(
				new Error(
					$e->getMessage(),
					self::ERROR_COULD_NOT_GET_LIST
				)
			);

			return null;
		}
	}

	public function removeEpic(EpicForm $epic, PushService $pushService = null): bool
	{
		try
		{
			$result = EpicTable::delete($epic->getId());

			if ($result->isSuccess())
			{
				if ($pushService)
				{
					$pushService->sendRemoveEpicEvent($epic);
				}

				(new CacheService($epic->getId(), CacheService::EPICS))->clean();

				return true;
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_REMOVE_EPIC);

				return false;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_REMOVE_EPIC
				)
			);

			return false;
		}
	}

	/**
	 * The method returns a list of all epics for an entity.
	 *
	 * @param int $groupId Group id.
	 * @return array
	 */
	public function getEpics(int $groupId): array
	{
		try
		{
			if (isset(self::$allEpics[$groupId]))
			{
				return self::$allEpics[$groupId];
			}

			self::$allEpics[$groupId] = [];

			$queryObject = EpicTable::getList([
				'filter' => ['GROUP_ID'=> $groupId],
				'order' => ['ID' => 'DESC'],
			]);
			foreach ($queryObject->fetchAll() as $data)
			{
				$epic = new EpicForm();

				$epic->fillFromDatabase($data);

				self::$allEpics[$groupId][$epic->getId()] = $epic->toArray();
			}

			return self::$allEpics[$groupId];
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_GET_EPICS
				)
			);

			return [];
		}
	}

	/**
	 * Attaches disk files to an epic.
	 *
	 * @param \CUserTypeManager $manager
	 * @param int $epicId Epic id.
	 * @param array $files
	 * @return array
	 */
	public function attachFiles(\CUserTypeManager $manager, int $epicId, array $files): array
	{
		try
		{
			$ufValues = $manager->getUserFieldValue('TASKS_SCRUM_EPIC', 'UF_SCRUM_EPIC_FILES', $epicId);

			if (is_array($ufValues))
			{
				$ufValues = array_merge($ufValues, $files);
			}
			else
			{
				$ufValues = $files;
			}

			if (empty($files))
			{
				$ufValues = [];
			}

			$userFields = ['UF_SCRUM_EPIC_FILES' => $ufValues];

			if ($manager->checkFields('TASKS_SCRUM_EPIC', $epicId, $userFields, $this->userId))
			{
				$manager->update('TASKS_SCRUM_EPIC', $epicId, $userFields);
			}

			return $ufValues;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_ADD_FILES
				)
			);

			return [];
		}
	}

	/**
	 * Removes disk files from epic.
	 *
	 * @param \CUserTypeManager $manager
	 * @param int $epicId Epic id.
	 * @return bool
	 */
	public function deleteFiles(\CUserTypeManager $manager, int $epicId): bool
	{
		try
		{
			$ufValues = $manager->getUserFieldValue('TASKS_SCRUM_EPIC', 'UF_SCRUM_EPIC_FILES', $epicId);

			$userFields = ['UF_SCRUM_EPIC_FILES' => $ufValues];

			if (!$manager->checkFields('TASKS_SCRUM_EPIC', $epicId, $userFields, $this->userId))
			{
				$this->errorCollection->setError(new Error('Access denied', self::ERROR_COULD_NOT_REMOVE_FILES));

				return false;
			}

			$manager->delete('TASKS_SCRUM_EPIC', $epicId);

			return true;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_REMOVE_FILES
				)
			);

			return false;
		}
	}

	public function getFilesUserField(\CUserTypeManager $manager, int $valueId = 0): array
	{
		try
		{
			$fields = $manager->getUserFields('TASKS_SCRUM_EPIC', $valueId);
			$filesFieldName = 'UF_SCRUM_EPIC_FILES';

			if (isset($fields[$filesFieldName]))
			{
				$fields[$filesFieldName]['EDIT_FORM_LABEL'] = $filesFieldName;
				$fields[$filesFieldName]['TAG'] = 'DOCUMENT ID';

				if (is_array($fields[$filesFieldName]['VALUE']))
				{
					$fields[$filesFieldName]['VALUE'] = array_unique($fields[$filesFieldName]['VALUE']);
				}
				else
				{
					$fields[$filesFieldName]['VALUE'] = [];
				}
			}

			return $fields;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_GET_UF_FIELD
				)
			);
		}

		return [];
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	private function setErrors(Result $result, string $code): void
	{
		$this->errorCollection->setError(
			new Error(
				implode('; ', $result->getErrorMessages()),
				$code
			)
		);
	}
}