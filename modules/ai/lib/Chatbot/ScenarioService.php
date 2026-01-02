<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot;

use Bitrix\AI\Chatbot\Model\EO_Scenario;
use Bitrix\AI\Chatbot\Repository\ScenarioRepository;
use Bitrix\Main\ORM\Data\DeleteResult;

class ScenarioService
{
	private ScenarioRepository $scenarioRepository;

	public function __construct(ScenarioRepository $scenarioRepository)
	{
		$this->scenarioRepository = $scenarioRepository;
	}

	/**
	 * Get scenario by code
	 */
	public function getScenarioByCode($scenarioCode): ?EO_Scenario
	{
		$scenario = $this->scenarioRepository->findScenarioByCode($scenarioCode);
		if (!$scenario)
		{
			return null;
		}

		return $scenario;
	}

	public function getScenarioById($scenarioId): mixed
	{
		$scenario = $this->scenarioRepository->findScenarioById($scenarioId);
		if (!$scenario)
		{
			return null;
		}

		return $scenario;
	}

	/**
	 * Create a new scenario
	 */
	public function createScenario(string $moduleId, string $code, string $class): ?int
	{
		return $this->scenarioRepository->addScenario($moduleId, $code, $class);
	}

	/**
	 * @param $id
	 *
	 * @return DeleteResult
	 * @throws \Exception
	 */
	public function deleteScenario($id): DeleteResult
	{
		return $this->scenarioRepository->deleteScenario($id);
	}
}
