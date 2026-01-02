<?php

namespace Bitrix\Crm\Integration\Rest\Configuration\Entity;

use Bitrix\Crm\AutomatedSolution\AutomatedSolutionManager;
use Bitrix\Crm\AutomatedSolution\Entity\AutomatedSolutionTable;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Integration\Rest\Configuration\Helper;
use Bitrix\Crm\Service\Container;
use Bitrix\Intranet\CustomSection\Entity\CustomSectionTable;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Rest\Configuration\Manifest;

/**
 * Class DynamicTypes
 * @package Bitrix\Crm\Integration\Rest\Configuration\Entity
 */
class AutomatedSolution
{
	const ENTITY_CODE = 'AUTOMATED_SOLUTION';

	private static $instance = null;

	private array $accessManifest = [
		'automated_solution',
	];

	/**
	 * Get instance.
	 *
	 * @return static
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new static;
		}

		return static::$instance;
	}

	/**
	 * Export.
	 *
	 * @param array $params Export params.
	 * @return array|null
	 */
	public function export(array $params)
	{
		if (
			!Manifest::isEntityAvailable(static::ENTITY_CODE, $params, $this->accessManifest)
			|| !Container::getInstance()->getUserPermissions()->automatedSolution()->canEdit()
		)
		{
			return null;
		}

		$helper = new Helper();
		if (!$helper->checkAutomatedSolutionModeExportParams($params))
		{
			return null;
		}

		$isSingleMode = (($params['MANIFEST']['CODE'] ?? '') === 'automated_solution_one');
		$automatedSolutionCode = '';
		if (
			$isSingleMode
			&& isset($params['ADDITIONAL_OPTION']['automatedSolutionCode'])
			&& is_string($params['ADDITIONAL_OPTION']['automatedSolutionCode'])
		)
		{
			$automatedSolutionCode = $params['ADDITIONAL_OPTION']['automatedSolutionCode'];
		}

		$manager = new AutomatedSolutionManager();
		$customSections = [];
		foreach ($manager->getExistingIntranetCustomSections() as $customSection)
		{
			if ($isSingleMode)
			{
				if ($customSection->getCode() === $automatedSolutionCode)
				{
					$customSections[] = $customSection->toArray();
					break;
				}
			}
			else
			{
				$customSections[] = $customSection->toArray();
			}
		}

		$automatedSolutions = [];
		foreach ($manager->getExistingAutomatedSolutions() as $automatedSolution)
		{
			if ($isSingleMode)
			{
				if ($automatedSolutionCode === ($automatedSolution['CODE'] ?? ''))
				{
					$automatedSolutions[] = $automatedSolution;
					break;
				}
			}
			else
			{
				$automatedSolutions[] = $automatedSolution;
			}
		}

		return [
			'FILE_NAME' => 'list',
			'CONTENT' => [
				'customSections' => $customSections,
				'automatedSolutions' => $automatedSolutions,
			],
		];
	}

	private function getIntranetCustomSectionDataByAutomatedSolutionFields(
		array $automatedSolutionFields,
		array $params
	): array
	{
		$result = [];

		$customSectionId = $automatedSolutionFields['INTRANET_CUSTOM_SECTION_ID'] ?? 0;
		if (
			$customSectionId > 0
			&& isset($params['CONTENT']['DATA']['customSections'])
			&& is_array($params['CONTENT']['DATA']['customSections'])
			&& !empty($params['CONTENT']['DATA']['customSections'])
		)
		{
			foreach ($params['CONTENT']['DATA']['customSections'] as $customSectionData)
			{
				if (
					is_array($customSectionData)
					&& isset($customSectionData['ID'])
					&& $customSectionId === (int)$customSectionData['ID']
				)
				{
					$result = $customSectionData;
					break;
				}
			}
		}

		return $result;
	}

	private function importAutomatedSolution(array $fields, array $params): Result
	{
		$result = new Result();

		if (!IntranetManager::isCustomSectionsAvailable())
		{
			return $result;
		}

		$intranetSectionData = $this->getIntranetCustomSectionDataByAutomatedSolutionFields($fields, $params);
		if (empty($intranetSectionData))
		{
			return $result->addError(new Error('No data found to import section of automated solution.'));
		}

		$intranetSection = CustomSectionTable::createObject([
			'TITLE' => $intranetSectionData['TITLE'] ?? null,
			'MODULE_ID' => AutomatedSolutionManager::MODULE_ID,
		]);
		$intranetSectionResult = $intranetSection->save();
		if (!$intranetSectionResult->isSuccess())
		{
			return $result->addErrors($intranetSectionResult->getErrors());
		}

		$intranetSectionId = $intranetSection->getId();
		$automatedSolution = AutomatedSolutionTable::createObject([
			'TITLE' => $intranetSection->getTitle(),
			'CODE' => $intranetSection->getCode(),
			'INTRANET_CUSTOM_SECTION_ID' => $intranetSectionId,
		]);
		$automatedSolutionResult = $automatedSolution->save();
		if (!$automatedSolutionResult->isSuccess())
		{
			CustomSectionTable::delete($intranetSectionId);

			return $result->addErrors($automatedSolutionResult->getErrors());
		}

		$result->setData(
			[
				'oldCustomSectionId' => (int)($intranetSectionData['ID'] ?? 0),
				'oldAutomatedSolutionId' => (int)($fields['ID'] ?? 0),
				'newCustomSectionId' => $intranetSectionId,
				'newAutomatedSolutionId' => $automatedSolution->getId(),
			]
		);

		return $result;
	}

	/**
	 * Import.
	 *
	 * @param array $params Import params.
	 * @return array|null
	 */
	public function import(array $params)
	{
		if (!Manifest::isEntityAvailable(static::ENTITY_CODE, $params, $this->accessManifest))
		{
			return null;
		}

		$helper = new Helper();
		if (!$helper->checkAutomatedSolutionModeImportParams($params))
		{
			return null;
		}

		if(empty($params['CONTENT']['DATA']))
		{
			return null;
		}

		$data = $params['CONTENT']['DATA'];
		if (empty($data['automatedSolutions']) || empty($data['customSections']))
		{
			return null;
		}

		$result = [];

		foreach ($data['automatedSolutions'] as $automatedSolutionFields)
		{
			$importResult = $this->importAutomatedSolution($automatedSolutionFields, $params);
			if (!$importResult->isSuccess())
			{
				$result['NEXT'] = false;
				$result['ERROR_MESSAGES'] = $importResult->getErrorMessages();

				return $result;
			}

			$oldAutomatedSolutionId = $importResult->getData()['oldAutomatedSolutionId'] ?? 0;
			$oldCustomSectionId = $importResult->getData()['oldCustomSectionId'] ?? 0;
			$newAutomatedSolutionId = $importResult->getData()['newAutomatedSolutionId'] ?? 0;
			$newCustomSectionId = $importResult->getData()['newCustomSectionId'] ?? 0;

			if (
				$oldAutomatedSolutionId > 0 && $newAutomatedSolutionId > 0
				&& $oldCustomSectionId > 0 && $newCustomSectionId
			)
			{
				$result['RATIO']["AS$oldAutomatedSolutionId"] = $newAutomatedSolutionId;
				$result['RATIO']["CS$oldCustomSectionId"] = $newCustomSectionId;
			}
		}

		return $result;
	}
}