<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Repository;

use Bitrix\AI\Chatbot\Model\ChatTable;
use Bitrix\AI\Chatbot\Model\EO_Scenario;
use Bitrix\AI\Chatbot\Model\MessageTable;
use Bitrix\AI\Chatbot\Model\MessageUnreadTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DeleteResult;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\AI\Chatbot\Model\ScenarioTable;

class ScenarioRepository
{
	public function __construct()
	{
	}

	/**
	 * @param $scenarioCode
	 *
	 * @return ?EO_Scenario
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function findScenarioByCode($scenarioCode): ?EO_Scenario
	{
		return ScenarioTable::query()->setSelect(['*'])->where('CODE', $scenarioCode)->fetchObject();
	}

	/**
	 * @param $scenarioId
	 *
	 * @return ?EO_Scenario
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function findScenarioById($scenarioId): ?EO_Scenario
	{
		return ScenarioTable::query()->setSelect(['*'])->where('ID', $scenarioId)->fetchObject();
	}

	/**
	 * @param string $moduleId
	 * @param string $code
	 * @param string $class
	 *
	 * @return int|null
	 */
	public function addScenario(string $moduleId, string $code, string $class): ?int
	{
		$newScenario = new EO_Scenario();
		$newScenario->setModuleId($moduleId)
			->setCode($code)
			->setClass($class)
		;

		$result = $newScenario->save();

		return $result->isSuccess() ? $result->getId() : null;
	}

	/**
	 * @param $id
	 *
	 * @return DeleteResult
	 * @throws \Exception
	 */
	public function deleteScenario($id): DeleteResult
	{
		// delete all scenario data
		$chatIds = ChatTable::query()->setSelect(['ID'])->where('SCENARIO_ID', $id)->fetchCollection()->getIdList();
		ChatTable::deleteByFilter(['=SCENARIO_ID' => $id]);
		MessageTable::deleteByFilter(['=CHAT_ID' => $chatIds]);
		MessageUnreadTable::deleteByFilter(['=CHAT_ID' => $chatIds]);

		return ScenarioTable::delete($id);
	}
}
