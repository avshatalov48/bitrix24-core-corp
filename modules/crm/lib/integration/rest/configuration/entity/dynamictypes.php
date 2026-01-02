<?php

namespace Bitrix\Crm\Integration\Rest\Configuration\Entity;

use Bitrix\Crm\Integration\Rest\Configuration\Helper;
use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Rest\Configuration\Manifest;
use CCrmOwnerType;
use CCrmSecurityHelper;

/**
 * Class DynamicTypes
 * @package Bitrix\Crm\Integration\Rest\Configuration\Entity
 */
class DynamicTypes
{
	const ENTITY_CODE = 'CRM_DYNAMIC_TYPES';

	private static $instance = null;

	private $accessManifest = [
		'total',
		'crm',
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
		if (!Manifest::isEntityAvailable(static::ENTITY_CODE, $params, $this->accessManifest))
		{
			return null;
		}

		$helper = new Helper();

		if (!($helper->checkAutomatedSolutionModeExportParams($params)))
		{
			return null;
		}

		$dynamicTypesMap = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadCategories' => false,
			'isLoadStages' => false,
		]);

		$automatedSolutionModeParams = $helper->getAutomatedSolutionModeParams($params);

		$list = [];
		foreach ($dynamicTypesMap->getTypesCollection()->collectValues() as $typeFields)
		{
			$entityTypeId = (int)$typeFields['ENTITY_TYPE_ID'];
			if (
				!$helper->checkDynamicTypeExportConditions(
					array_merge(
						$automatedSolutionModeParams,
						$helper->getDynamicTypeCheckExportParamsByEntityTypeId($entityTypeId)
					)
				)
			)
			{
				continue;
			}

			foreach (['CREATED_BY', 'CREATED_TIME', 'UPDATED_TIME', 'UPDATED_BY'] as $fieldName)
			{
				unset($typeFields[$fieldName]);
			}

			if (
				isset($typeFields['ENTITY_TYPE_ID'])
				&& CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
			)
			{
				$list[] = $typeFields;
			}
		}

		return [
			'FILE_NAME' => 'types',
			'CONTENT' => ['list' => $list],
		];
	}

	/**
	 * Clear.
	 *
	 * @param array $options Options.
	 * @return array|null
	 */
	public function clear(array $options)
	{
		if(!Manifest::isEntityAvailable(static::ENTITY_CODE, $options, $this->accessManifest))
		{
			return null;
		}

		$helper = new Helper();
		if (!$helper->checkAutomatedSolutionModeClearParams($options))
		{
			return null;
		}

		$result = [
			'NEXT' => false
		];

		$clearFull = $options['CLEAR_FULL'];
		if($clearFull)
		{
			try
			{
				$dynamicTypeId = 0;
				$nextDynamicTypeId = 0;
				$factory = null;
				if (isset($options['NEXT']) && is_string($options['NEXT']) && $options['NEXT'] !== '')
				{
					$nextParts = explode('_', $options['NEXT'], 3);
					if (count($nextParts) > 1)
					{
						$dynamicTypeId = (int)$nextParts[0];

						if (!CCrmOwnerType::isPossibleDynamicTypeId($dynamicTypeId))
						{
							throw new SystemException(
								Loc::getMessage('CRM_ERROR_CONFIGURATION_CLEAR_EXCEPTION_DYMANIC_ENTITY_TYPE_ID')
							);
						}

						$factory = Container::getInstance()->getFactory($dynamicTypeId);
						if (!$factory)
						{
							throw new SystemException(
								Loc::getMessage(
									'CRM_ERROR_CONFIGURATION_CLEAR_EXCEPTION_DYMANIC_INSTANCE',
									['#DYNAMIC_TYPE_ID#' => $dynamicTypeId]
								)
							);
						}
					}
				}

				$entity = TypeTable::getEntity();
				$entity->addField(
					(
					new ExpressionField('ENTYPRAD', 'ENTITY_TYPE_ID %% 2')
					)->configureValueType(IntegerField::class),
					'ETRADBTWO'
				);
				$query = new Query($entity);
				$res = $query
					->setSelect(['ENTITY_TYPE_ID'])
					->setOrder(['ENTITY_TYPE_ID' => 'asc'])
					->setFilter(
						[
							['>=ENTITY_TYPE_ID' => $dynamicTypeId],
							[
								'LOGIC' => 'OR',
								[
									'>=ENTITY_TYPE_ID' => CCrmOwnerType::DynamicTypeStart,
									'<ENTITY_TYPE_ID' => CCrmOwnerType::DynamicTypeEnd,
								],
								[
									'>=ENTITY_TYPE_ID' => CCrmOwnerType::UnlimitedTypeStart,
									'==ETRADBTWO' => 0,
								]
							]
						]
					)
					->setLimit(2)
					->fetchAll()
				;
				unset($entity, $query);

				if (is_array($res) && count($res) > 0)
				{
					if (!$factory)
					{
						$dynamicTypeId = (int)$res[0]['ENTITY_TYPE_ID'];
						$factory = Container::getInstance()->getFactory($dynamicTypeId);
					}
					$nextDynamicTypeId = (int)($res[1]['ENTITY_TYPE_ID'] ?? 0);
				}

				if ($factory)
				{
					$automatedSolutionModeParams = $helper->getAutomatedSolutionModeImportParams($options);
					if (
						$helper->checkDynamicTypeExportConditions(
							array_merge(
								$automatedSolutionModeParams,
								$helper->getDynamicTypeCheckExportParamsByEntityTypeId($dynamicTypeId)
							)
						)
					)
					{
						$rows = $factory->getDataClass()::getList(['select' => ['ID'], 'limit' => 10])->fetchAll();
						if (count($rows) <= 0)
						{
							// Delete dynamic type
							$type = Container::getInstance()->getTypeByEntityTypeId($dynamicTypeId);
							if ($type)
							{
								$userPermissions = Container::getInstance()->getUserPermissions();
								if (
									!(
										$userPermissions->isAdminForEntity($dynamicTypeId)
										|| !$userPermissions->isCrmAdmin()
									)
								)
								{
									throw new SystemException(
										Loc::getMessage(
											'CRM_ERROR_CONFIGURATION_CLEAR_EXCEPTION_DYMANIC_TYPE_DEL_DENIED',
											['#DYNAMIC_TYPE_ID#' => $dynamicTypeId]
										)
									);
								}
								$res = $type->delete();
								if (!$res->isSuccess())
								{
									throw new SystemException(
										Loc::getMessage(
											'CRM_ERROR_CONFIGURATION_CLEAR_EXCEPTION_DYMANIC_TYPE_DEL',
											['#DYNAMIC_TYPE_ID#' => $dynamicTypeId]
										)
									);
								}
							}

							if ($nextDynamicTypeId > 0)
							{
								$result['NEXT'] = "{$nextDynamicTypeId}_0";
							}
						}
						foreach ($rows as $row)
						{
							$itemId = (int)$row['ID'];
							$item = $factory->getItem($itemId);
							$operation = $factory->getDeleteOperation($item);
							$res = $operation->launch();
							if (!$res->isSuccess())
							{
								throw new SystemException(
									Loc::getMessage(
										'CRM_ERROR_CONFIGURATION_CLEAR_EXCEPTION_DYMANIC_ITEM_DEL',
										[
											'#DYNAMIC_TYPE_ID#' => $dynamicTypeId,
											'#ITEM_ID#' => $itemId,
										]
									)
								);
							}
							else
							{
								$result['NEXT'] = "{$dynamicTypeId}_$itemId";
							}
						}
					}
					elseif ($nextDynamicTypeId > 0)
					{
						$result['NEXT'] = "{$nextDynamicTypeId}_0";
					}
				}
			}
			catch (\Exception $e)
			{
				$result['NEXT'] = false;
				$result['ERROR_EXCEPTION'] = $e->getMessage();
				$result['ERROR_ACTION'] = 'DELETE_ERROR_DYNAMIC_TYPE_ITEMS';
			}
		}

		return $result;
	}

	private function setCustomSection(
		Type $type,
		array $fields,
		bool $isTypeTitleChangedBeforeSave,
		array $importParams
	): Result
	{
		// to avoid inconsistent with data from \Bitrix\Crm\Service\Container::getTypeByEntityTypeId
		/** @var Type $typeFromContainer */
		$typeFromContainer =
			Container::getInstance()
				->getDynamicTypesMap()
				->getTypesCollection()
				->getByPrimary($type->getId())
		;
		
		$customSectionId = 0;
		if (isset($fields['CUSTOM_SECTION_ID']) && $fields['CUSTOM_SECTION_ID'] > 0)
		{
			$oldCustomSectionId = (int)$fields['CUSTOM_SECTION_ID'];
			if (isset($importParams['RATIO']['AUTOMATED_SOLUTION']["CS$oldCustomSectionId"]))
			{
				$customSectionId = (int)$importParams['RATIO']['AUTOMATED_SOLUTION']["CS$oldCustomSectionId"];
			}
		}

		if ($customSectionId <= 0)
		{
			return (new Result())->addError(
				new Error(
					Loc::getMessage('CRM_ERROR_CONFIGURATION_IMPORT_DYNAMIC_TYPE_INVALID_AS_RELATION')
				)
			);
		}

		$customSectionsResult =
			Container::getInstance()
				->getAutomatedSolutionManager()
				->setAutomatedSolutions(
					$typeFromContainer,
					[
						'CUSTOM_SECTION_ID' => $customSectionId,
						'IS_TYPE_TITLE_CHANGED' => $isTypeTitleChangedBeforeSave,
					]
				)
		;

		if (!$customSectionsResult->isSuccess())
		{
			return $customSectionsResult;
		}

		$type->setCustomSectionId($typeFromContainer->getCustomSectionId());

		// update cached object
		Container::getInstance()->getTypeByEntityTypeId(
			$type->getEntityTypeId())?->setCustomSectionId($typeFromContainer->getCustomSectionId()
		);

		return $customSectionsResult;
	}

	/**
	 * Import.
	 *
	 * @param array $params Import params.
	 * @return array|null
	 */
	public function import(array $params)
	{
		if(!Manifest::isEntityAvailable(static::ENTITY_CODE, $params, $this->accessManifest))
		{
			return null;
		}

		$helper = new Helper();
		if (!$helper->checkAutomatedSolutionModeImportParams($params))
		{
			return null;
		}

		$result = [];
		if(empty($params['CONTENT']['DATA']))
		{
			return $result;
		}

		$data = $params['CONTENT']['DATA'];
		if(empty($data['list']))
		{
			return $result;
		}

		$automatedSolutionModeParams = $helper->getAutomatedSolutionModeImportParams($params);

		foreach ($data['list'] as $typeFields)
		{
			$oldDynamicTypeId = (int)($typeFields['ID'] ?? 0);
			$oldDynamicEntityTypeId = (int)($typeFields['ENTITY_TYPE_ID'] ?? 0);

			if (!$helper->checkDynamicTypeRelationWithAutomatedSolutionForImport($typeFields, $params))
			{
				$result['NEXT'] = false;
				$result['ERROR_MESSAGES'] = [
					Loc::getMessage('CRM_ERROR_CONFIGURATION_IMPORT_DYNAMIC_TYPE_INVALID_AS_RELATION'),
				];

				return $result;
			}

			if (
				$oldDynamicTypeId <= 0
				|| $oldDynamicEntityTypeId <= 0
				|| !$helper->checkDynamicTypeImportConditionsByParams(
					array_merge(
						$automatedSolutionModeParams,
						$helper->getDynamicTypeCheckImportParamsByTypeFieldsForImport($typeFields, $params)
					)
				)
			)
			{
				continue;
			}

			$restriction = RestrictionManager::getDynamicTypesLimitRestriction();
			if ($restriction->isCreateTypeRestricted())
			{
				$result['NEXT'] = false;
				$result['ERROR_MESSAGES'] = [
					Loc::getMessage('CRM_ERROR_CONFIGURATION_IMPORT_DYNAMIC_TYPES_BY_LIMIT'),
					Loc::getMessage('CRM_ERROR_CONFIGURATION_IMPORT_DYNAMIC_TYPES'),
				];

				return $result;
			}

			foreach (['ID', 'CREATED_BY', 'CREATED_TIME', 'UPDATED_TIME', 'UPDATED_BY'] as $fieldName)
			{
				unset($typeFields[$fieldName]);
			}

			if (isset($typeFields['TITLE']))
			{
				$typeFields['TITLE'] = trim($typeFields['TITLE']);
			}

			Container::getInstance()->getLocalization()->loadMessages();

			$type =
				TypeTable::createObject()
					->setName($typeFields['NAME'])
					->setTitle($typeFields['TITLE'])
					->setCode($typeFields['CODE'])
					->setCreatedBy(CCrmSecurityHelper::GetCurrentUser()->getId())
					->setIsCategoriesEnabled($typeFields['IS_CATEGORIES_ENABLED'])
					->setIsStagesEnabled($typeFields['IS_STAGES_ENABLED'])
					->setIsBeginCloseDatesEnabled($typeFields['IS_BEGIN_CLOSE_DATES_ENABLED'])
					->setIsClientEnabled($typeFields['IS_CLIENT_ENABLED'])
					->setIsUseInUserfieldEnabled($typeFields['IS_USE_IN_USERFIELD_ENABLED'])
					->setIsLinkWithProductsEnabled($typeFields['IS_LINK_WITH_PRODUCTS_ENABLED'])
					->setIsCrmTrackingEnabled($typeFields['IS_CRM_TRACKING_ENABLED'])
					->setIsMycompanyEnabled($typeFields['IS_MYCOMPANY_ENABLED'])
					->setIsDocumentsEnabled($typeFields['IS_DOCUMENTS_ENABLED'])
					->setIsSourceEnabled($typeFields['IS_SOURCE_ENABLED'])
					->setIsObserversEnabled($typeFields['IS_OBSERVERS_ENABLED'])
					->setIsRecurringEnabled($typeFields['IS_RECURRING_ENABLED'])
					->setIsRecyclebinEnabled($typeFields['IS_RECYCLEBIN_ENABLED'])
					->setIsAutomationEnabled($typeFields['IS_AUTOMATION_ENABLED'])
					->setIsBizProcEnabled($typeFields['IS_BIZ_PROC_ENABLED'])
					->setIsSetOpenPermissions($typeFields['IS_SET_OPEN_PERMISSIONS'])
					->setIsPaymentsEnabled($typeFields['IS_PAYMENTS_ENABLED'])
					->setIsCountersEnabled($typeFields['IS_COUNTERS_ENABLED'])
			;

			$isTitleChanged = false;
			if ($automatedSolutionModeParams['isAutomatedSolutionMode'])
			{
				$type->setName(TypeTable::generateName($typeFields['TITLE']));
				$isTitleChanged = $type->isChanged('TITLE');
			}

			/** @var AddResult $result */
			$newTypeResult = $type->save();
			if (!$newTypeResult->isSuccess())
			{
				$result['NEXT'] = false;
				$result['ERROR_MESSAGES'] = $newTypeResult->getErrorMessages();

				return $result;
			}

			if ($automatedSolutionModeParams['isAutomatedSolutionMode'])
			{
				$customSectionResult = $this->setCustomSection($type, $typeFields, $isTitleChanged, $params);
				if (!$customSectionResult->isSuccess())
				{
					$result['NEXT'] = false;
					$result['ERROR_MESSAGES'] = $customSectionResult->getErrorMessages();

					$type->delete();

					return $result;
				}
			}

			$result['RATIO']["DT$oldDynamicTypeId"] = $type->getId();
			$result['RATIO']["DYNAMIC_$oldDynamicEntityTypeId"] = $type->getEntityTypeId();
		}

		return $result;
	}
}