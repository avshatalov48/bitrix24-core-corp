<?php

namespace Bitrix\Crm\Integration\Rest\Configuration\Entity;

use Bitrix\Crm\Entity\EntityEditorConfigScope;
use Bitrix\Crm\Entity\EntityEditorConfig;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\CustomerType;
use Bitrix\Crm\Integration\Rest\Configuration\Helper;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\Result;
use Bitrix\Rest\Configuration\Manifest;
use CCrmOwnerType;
use CCrmSecurityHelper;
use Exception;

class DetailConfiguration
{

	const ENTITY_CODE = 'CRM_DETAIL_CONFIGURATION';

	private $entityTypeDetailConfiguration = [];
	private bool $isEntityTypeDetailConfigurationReady = false;

	private static $instance = null;

	private $accessManifest = [
		'total',
		'crm',
		'automated_solution',
	];

	/**
	 * DetailConfiguration constructor.
	 */
	private function __construct()
	{
	}

	/**
	 * @return DetailConfiguration|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function refreshEntityTypeDetailConfiguration(): void
	{
		$this->isEntityTypeDetailConfigurationReady = false;
		$this->entityTypeDetailConfiguration = [
			'LEAD'.EntityEditorConfigScope::COMMON => [
				'ID' => CCrmOwnerType::Lead,
				'SCOPE' => EntityEditorConfigScope::COMMON
			],
			'DEAL'.EntityEditorConfigScope::COMMON => [
				'ID' => CCrmOwnerType::Deal,
				'SCOPE' => EntityEditorConfigScope::COMMON
			],
			'CONTACT'.EntityEditorConfigScope::COMMON => [
				'ID' => CCrmOwnerType::Contact,
				'SCOPE' => EntityEditorConfigScope::COMMON
			],
			'COMPANY'.EntityEditorConfigScope::COMMON => [
				'ID' => CCrmOwnerType::Company,
				'SCOPE' => EntityEditorConfigScope::COMMON
			],
		];

		$dynamicTypesMap = Container::getInstance()->getDynamicTypesMap()->load(
			[
				'isLoadCategories' => true,
				'isLoadStages' => false,
			]
		);

		foreach ($dynamicTypesMap->getTypes() as $type)
		{
			$entityTypeId = $type->getEntityTypeId();
			$entityTypeName = CCrmOwnerType::ResolveName($type->getEntityTypeId());
			$scope = EntityEditorConfigScope::COMMON;

			foreach ($dynamicTypesMap->getCategories($entityTypeId) as $category)
			{
				$categoryId = $category->getId();
				$this->entityTypeDetailConfiguration["$entityTypeName{$scope}_$categoryId"] = [
					'ID' => $entityTypeId,
					'SCOPE' => $scope,
					'CATEGORY_ID' => $categoryId,
				];
			}
		}

		$this->isEntityTypeDetailConfigurationReady = true;
	}

	private function getEntityTypeDetailConfiguration(): array
	{
		if (!$this->isEntityTypeDetailConfigurationReady)
		{
			$this->refreshEntityTypeDetailConfiguration();
		}

		return $this->entityTypeDetailConfiguration;
	}

	private function filterDetailConfigurationKeysByOptions(array $configKeyList, array $options): array
	{
		$map = array_fill_keys($configKeyList, true);

		return array_keys($this->filterDetailConfigurationListByOptions($map, $options));
	}

	private function filterDetailConfigurationListByOptions(array $configList, array $options): array
	{
		$filteredConfigList = [];

		if (empty($configList))
		{
			return $configList;
		}

		$automatedSolutionModeParams = (new Helper())->getAutomatedSolutionModeParams($options);

		if (
			$automatedSolutionModeParams['isAutomatedSolutionMode']
			&& !Container::getInstance()->getUserPermissions()->automatedSolution()->canEdit()
		)
		{
			return $filteredConfigList;
		}

		$helper = new Helper();
		$dynamicTypesMap = Container::getInstance()->getDynamicTypesMap()->load(
			[
				'isLoadCategories' => true,
				'isLoadStages' => false,
			]
		);
		$dynamicConfigMap = [];
		foreach ($dynamicTypesMap->getTypes() as $type)
		{
			if (
				!$helper->checkDynamicTypeExportConditions(
					array_merge(
						$automatedSolutionModeParams,
						$helper->getDynamicTypeCheckExportParamsByEntityTypeId($type->getEntityTypeId() ?? 0)
					)
				)
			)
			{
				continue;
			}

			$scope = EntityEditorConfigScope::COMMON;
			$dynamicTypePrefix = CCrmOwnerType::DynamicTypePrefixName;
			foreach ($dynamicTypesMap->getCategories($type->getEntityTypeId()) as $category)
			{
				$dynamicEntityTypeId = $type->getEntityTypeId();
				$categoryId = $category->getId();
				$configKey = "$dynamicTypePrefix$dynamicEntityTypeId{$scope}_$categoryId";
				if (isset($configList[$configKey]))
				{
					$dynamicConfigMap[$configKey] = true;
				}
			}
		}

		foreach ($configList as $configKey => $config)
		{
			$isDynamicType = str_starts_with($configKey, 'DYNAMIC_');
			if (
				(!$isDynamicType && !$automatedSolutionModeParams['isAutomatedSolutionMode'])
				|| isset($dynamicConfigMap[$configKey])
			)
			{
				$filteredConfigList[$configKey] = $config;
			}
		}

		return $filteredConfigList;
	}

	/**
	 * @return array
	 * @throws ArgumentException
	 */
	private function exportDetailConfigurationList()
	{
		$return = $this->getEntityTypeDetailConfiguration();
		unset($return['LEAD'.EntityEditorConfigScope::COMMON]);
		$return = array_keys($return);

		$return[] = 'LEAD'.EntityEditorConfigScope::COMMON.'_'.CustomerType::GENERAL;
		$return[] = 'LEAD'.EntityEditorConfigScope::COMMON.'_'.CustomerType::RETURNING;

		if(DealCategory::isCustomized())
		{
			$category = DealCategory::getAll(false);
			foreach ($category as $item)
			{
				if(!$item['IS_DEFAULT'])
				{
					$return[] = 'DEAL'.EntityEditorConfigScope::COMMON.'_'.$item['ID'];
				}
			}
		}

		return $return;
	}

	/**
	 * @param $option
	 *
	 * @return mixed
	 * @throws ArgumentException
	 * @throws InvalidOperationException
	 * @throws NotSupportedException
	 */
	public function export($option)
	{
		if (!Manifest::isEntityAvailable('', $option, $this->accessManifest))
		{
			return null;
		}

		if (!(new Helper())->checkAutomatedSolutionModeExportParams($option))
		{
			return null;
		}

		$step = false;
		if(array_key_exists('STEP', $option))
		{
			$step = $option['STEP'];
		}

		$keys = $this->exportDetailConfigurationList();
		$keys = $this->filterDetailConfigurationKeysByOptions($keys, $option);
		$typeEntity = $keys[$step]?:'';
		$return = [
			'FILE_NAME' => $typeEntity,
			'CONTENT' => [],
			'NEXT' => count($keys) > $step+1 ? $step : false
		];

		$entityTypeDetailConfiguration = $this->getEntityTypeDetailConfiguration();
		$entityTypeDetailConfiguration = $this->filterDetailConfigurationListByOptions(
			$entityTypeDetailConfiguration,
			$option
		);
		if(!empty($entityTypeDetailConfiguration[$typeEntity]))
		{
			if (isset($entityTypeDetailConfiguration[$typeEntity]['CATEGORY_ID']))
			{
				$extras = ['CATEGORY_ID' => $entityTypeDetailConfiguration[$typeEntity]['CATEGORY_ID']];
			}
			else
			{
				$extras = [];
			}
			$config = new EntityEditorConfig(
				$entityTypeDetailConfiguration[$typeEntity]['ID'],
				CCrmSecurityHelper::GetCurrentUser()->GetID(),
				$entityTypeDetailConfiguration[$typeEntity]['SCOPE'],
				$extras
			);
			try
			{
				$return['CONTENT'] = [
					'ENTITY' => $typeEntity,
					'DATA' => $config->get()
				];
			}
			catch (Exception $e)
			{
			}
		}
		elseif(mb_strpos($typeEntity,'DEAL') !== false || mb_strpos($typeEntity,'LEAD') !== false)
		{
			[$entity, $id] = explode('_', $typeEntity,2);
			if($entityTypeDetailConfiguration[$entity])
			{
				$id = intVal($id);
				if(mb_strpos($typeEntity,'DEAL') !== false)
				{
					$extras = [
						'DEAL_CATEGORY_ID' => $id
					];
				}
				else
				{
					$extras = [
						'LEAD_CUSTOMER_TYPE' => $id
					];
				}
				$config = new EntityEditorConfig(
					$entityTypeDetailConfiguration[$entity]['ID'],
					CCrmSecurityHelper::GetCurrentUser()->GetID(),
					$entityTypeDetailConfiguration[$entity]['SCOPE'],
					$extras
				);
				if($id > 0)
				{
					if($extras['DEAL_CATEGORY_ID'])
					{
						$category = array_column(DealCategory::getAll(false), null, 'ID');
						if(!empty($category[$id]))
						{
							$return['CONTENT'] = [
								'ENTITY' => $typeEntity,
								'DATA' => $config->get()
							];
						}
					}
					else
					{
						$return['CONTENT'] = [
							'ENTITY' => $typeEntity,
							'DATA' => $config->get()
						];
					}
				}
			}
		}

		return $return;
	}

	/**
	 * @param $option
	 *
	 * @return mixed
	 * @throws ArgumentException
	 */
	public function clear($option)
	{
		if(!Manifest::isEntityAvailable('', $option, $this->accessManifest))
		{
			return null;
		}

		$helper = new Helper();
		if (!$helper->checkAutomatedSolutionModeClearParams($option))
		{
			return null;
		}

		$result = [
			'NEXT' => false
		];
		$clearFull = $option['CLEAR_FULL'];
		if($clearFull)
		{
			$configurationEntity = $this->exportDetailConfigurationList();
			$configurationEntity = $this->filterDetailConfigurationKeysByOptions($configurationEntity, $option);

			foreach ($configurationEntity as $entity)
			{
				$extras = [];
				if (mb_strpos($entity, 'LEAD') !== false)
				{
					[$entity, $id] = explode('_', $entity, 2);
					$extras = [
						'LEAD_CUSTOMER_TYPE' => $id
					];
				}

				$entityTypeDetailConfiguration = $this->getEntityTypeDetailConfiguration();
				$entityTypeDetailConfiguration = $this->filterDetailConfigurationListByOptions(
					$entityTypeDetailConfiguration,
					$option
				);

				if($entityTypeDetailConfiguration[$entity])
				{
					$id = $entityTypeDetailConfiguration[$entity]['ID'];
					$scope = $entityTypeDetailConfiguration[$entity]['SCOPE'];
				}
				else
				{
					continue;
				}

				if (isset($entityTypeDetailConfiguration[$entity]['CATEGORY_ID']))
				{
					$extras['CATEGORY_ID'] = $entityTypeDetailConfiguration[$entity]['CATEGORY_ID'];
				}

				$config = new EntityEditorConfig(
					$id,
					CCrmSecurityHelper::GetCurrentUser()->GetID(),
					$scope,
					$extras
				);
				try
				{
					$config->reset();
					$config->forceCommonScopeForAll();
				}
				catch (\Exception $e)
				{
				}
			}
		}

		return $result;
	}

	protected function setEntityTypeDetailConfig(
		array $configData,
		int $entityTypeId,
		int $userId,
		string $scope,
		array $extras
	): Result
	{
		$result = new Result();

		$config = new EntityEditorConfig($entityTypeId, $userId, $scope, $extras);
		$errors = [];
		$data = $config->normalize($configData, ['remove_if_empty_name' => true]);
		if(!$config->check($data, $errors))
		{
			foreach ($errors as $error)
			{
				$result->addError(new Error($error));
			}
		}
		else
		{
			$data = $config->sanitize($data);
			if(!empty($data))
			{
				try
				{
					$config->set($data);
					$config->forceCommonScopeForAll();
				}
				catch (\Exception $e)
				{
				}
			}
		}

		return $result;
	}

	/**
	 * @param $import
	 *
	 * @return mixed
	 * @throws ArgumentException
	 * @throws InvalidOperationException
	 * @throws NotSupportedException
	 */
	public function import($import)
	{
		if(!Manifest::isEntityAvailable('', $import, $this->accessManifest))
		{
			return null;
		}

		$helper = new Helper();
		if (!$helper->checkAutomatedSolutionModeImportParams($import))
		{
			return null;
		}

		$result = [];

		if(!isset($import['CONTENT']['DATA']))
		{
			return $result;
		}
		$item = $import['CONTENT']['DATA'];
		if(!$item['ENTITY'] || !$item['DATA'])
		{
			return $result;
		}

		$entityTypeDetailConfiguration = $this->getEntityTypeDetailConfiguration();

		$isDynamicType = (is_string($item['ENTITY']) && mb_substr($item['ENTITY'], 0, 8) === 'DYNAMIC_');

		$automatedSolutionModeParams = $helper->getAutomatedSolutionModeImportParams($import);
		if (!$isDynamicType && $automatedSolutionModeParams['isAutomatedSolutionMode'])
		{
			return $result;
		}

		if ($isDynamicType)
		{
			$oldDynamicEntityTypeId = $this->getDynamicEntityTypeIdByEntityId($item['ENTITY']);
			$isSetRatio = (isset($import['RATIO']) && is_array($import['RATIO']));
			$newDynamicEntityTypeId = $helper->getDynamicEntityTypeIdByOldEntityTypeId(
				$oldDynamicEntityTypeId,
				$isSetRatio ? $import['RATIO'] : []
			);
			if (!$helper->checkDynamicTypeImportConditions($newDynamicEntityTypeId, $import))
			{
				return $result;
			}
		}

		$entityCode = $isDynamicType ? $this->getNewEntityId($import, $item['ENTITY']) : $item['ENTITY'];
		if ($entityTypeDetailConfiguration[$entityCode])
		{
			if (isset($entityTypeDetailConfiguration[$entityCode]['CATEGORY_ID']))
			{
				$extras = ['CATEGORY_ID' => $entityTypeDetailConfiguration[$entityCode]['CATEGORY_ID']];
			}
			else
			{
				$extras = [];
			}

			if (
				$isDynamicType
				&& isset($import['RATIO']['CRM_DYNAMIC_TYPES'])
				&& is_array($import['RATIO']['CRM_DYNAMIC_TYPES'])
				&& !empty($import['RATIO']['CRM_DYNAMIC_TYPES'])
			)
			{
				$configData = $this->replaceUserFieldNamesWithNewDynamicTypeId(
					$import['RATIO']['CRM_DYNAMIC_TYPES'],
					$item['DATA']
				);
			}
			else
			{
				$configData = $item['DATA'];
			}

			$setConfigResult = $this->setEntityTypeDetailConfig(
				$configData,
				$entityTypeDetailConfiguration[$entityCode]['ID'],
				CCrmSecurityHelper::GetCurrentUser()->GetID(),
				$entityTypeDetailConfiguration[$entityCode]['SCOPE'],
				$extras
			);

			if (!$setConfigResult->isSuccess())
			{
				$result['ERROR_MESSAGES'][] = $setConfigResult->getErrorMessages();
			}
		}
		elseif(mb_strpos($entityCode,'DEAL') !== false || mb_strpos($entityCode,'LEAD') !== false)
		{
			[$entity, $id] = explode('_', $entityCode,2);
			if ($entityTypeDetailConfiguration[$entity])
			{
				$id = intVal($id);
				if(mb_strpos($entityCode,'DEAL') !== false)
				{
					if(!empty($import['RATIO'][Status::ENTITY_CODE][$id]))
					{
						$id = $import['RATIO'][Status::ENTITY_CODE][$id];
					}

					$extras = [
						'DEAL_CATEGORY_ID' => $id
					];
				}
				else
				{
					$extras = [
						'LEAD_CUSTOMER_TYPE' => $id
					];
				}

				$setConfigResult = $this->setEntityTypeDetailConfig(
					$item['DATA'],
					$entityTypeDetailConfiguration[$entity]['ID'],
					CCrmSecurityHelper::GetCurrentUser()->GetID(),
					$entityTypeDetailConfiguration[$entity]['SCOPE'],
					$extras
				);

				if (!$setConfigResult->isSuccess())
				{
					$result['ERROR_MESSAGES'][] = $setConfigResult->getErrorMessages();
				}
			}
		}

		return $result;
	}

	protected function replaceUserFieldNamesWithNewDynamicTypeId(array $dynamicTypeImportInfo, array $configData): array
	{
		foreach ($configData as $key => $value)
		{
			$matches = [];
			if (is_array($value))
			{
				$configData[$key] = $this->replaceUserFieldNamesWithNewDynamicTypeId($dynamicTypeImportInfo, $value);
			}
			elseif (
				$key === 'name'
				&& is_string($value)
				&& preg_match('/^UF_CRM_(\\d+)_/u', $value, $matches)
			)
			{
				$oldDynamicTypeId = (int)$matches[1];
				$infoKey = "DT$oldDynamicTypeId";
				if (
					$oldDynamicTypeId > 0
					&& isset($dynamicTypeImportInfo[$infoKey])
					&& $dynamicTypeImportInfo[$infoKey] > 0
				)
				{
					$newDynamicTypeId = (int)$dynamicTypeImportInfo[$infoKey];
					$configData[$key] = "UF_CRM_{$newDynamicTypeId}_" . mb_substr($value, mb_strlen($matches[0]));
				}
			}
		}

		return $configData;
	}

	private function getIdsByEntityId(string $entityId): array
	{
		static $data = [];

		if (!isset($data[$entityId]))
		{
			$scope = EntityEditorConfigScope::COMMON;
			$dynamicTypePrefix = CCrmOwnerType::DynamicTypePrefixName;
			$matches = [];

			if ($entityId !== '' && preg_match("/^$dynamicTypePrefix(\\d+){$scope}_(\\d+)$/u", $entityId, $matches))
			{
				$data[$entityId] = [(int)$matches[1], (int)$matches[2]];
			}
			else
			{
				$data[$entityId] = [0, 0];
			}
		}

		return $data[$entityId];
	}

	protected function getDynamicEntityTypeIdByEntityId(string $entityId): int
	{
		$ids = $this->getIdsByEntityId($entityId);

		return $ids[0];
	}

	protected function getDynamicCategoryIdByEntityId(string $entityId): int
	{
		$ids = $this->getIdsByEntityId($entityId);

		return $ids[1];
	}

	protected function getNewEntityId(array $importData, string $entityId): string
	{
		$result = $entityId;

		if ($entityId !== '')
		{
			$oldDynamicEntityTypeId = $this->getDynamicEntityTypeIdByEntityId($entityId);
			$oldCategoryId = $this->getDynamicCategoryIdByEntityId($entityId);
			if ($oldDynamicEntityTypeId > 0 && $oldCategoryId > 0)
			{
				$helper = new Helper();
				$isSetRatio = (isset($importData['RATIO']) && is_array($importData['RATIO']));
				$newDynamicEntityTypeId = $helper->getDynamicEntityTypeIdByOldEntityTypeId(
					$oldDynamicEntityTypeId,
					$isSetRatio ? $importData['RATIO'] : []
				);
				$categoryRatioKey = "DT{$oldDynamicEntityTypeId}_$oldCategoryId";
				if ($newDynamicEntityTypeId > 0 && isset($importData['RATIO']['CRM_STATUS'][$categoryRatioKey]))
				{
					$newCategoryId = (int)$importData['RATIO']['CRM_STATUS'][$categoryRatioKey];
					if ($newCategoryId > 0)
					{
						$scope = EntityEditorConfigScope::COMMON;
						$dynamicTypePrefix = CCrmOwnerType::DynamicTypePrefixName;
						$result = "$dynamicTypePrefix$newDynamicEntityTypeId{$scope}_$newCategoryId";
					}
				}
			}
		}

		return $result;
	}
}