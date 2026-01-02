<?php

namespace Bitrix\Crm\Integration\Rest\Configuration\Entity;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\EO_Status;
use Bitrix\Crm\Integration\Rest\Configuration\Helper;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Status\FunnelStatusCollectionRevalidator;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Rest\Configuration\Manifest;
use CAllCrmInvoice;
use CCrmOwnerType;
use CCrmStatus;
use CCrmQuote;
use Exception;

class Status
{
	const ENTITY_CODE = 'CRM_STATUS';
	const OWNER_ENTITY_TYPE_CRM_DEAL_CATEGORY = 'CRM_DEAL_CATEGORY';

	private static $clearSort = 99999;
	private static $dealStageStart = 'DEAL_STAGE';
	private static $customDealStagePrefix = 'DEAL_STAGE_';
	private static $isEntityTypeFunnel = [
		'STATUS',
		'DEAL_STAGE',
		'QUOTE_STATUS',
		'CALL_LIST',
		'INVOICE_STATUS'
	];
	private static $statusSemantics = [
		'STATUS' => [
			'final' => 'CONVERTED',
		],
		'INVOICE_STATUS' => [
			'final' => 'P',
		],
		'QUOTE_STATUS' => [
			'final' => 'APPROVED',
		],
		'DEAL_STAGE' => [
			'final' => 'WON',
		],
	];
	private static $accessManifest = [
		'total',
		'crm',
		'automated_solution',
	];

	/**
	 * @param $type string
	 *
	 * @return array
	 * @throws LoaderException
	 */
	private static function checkRequiredParams($type)
	{
		$errorList = [];
		if (!CAllCrmInvoice::installExternalEntities())
		{
			$errorList[] = 'need install external entities crm invoice';
		}

		if (!CCrmQuote::LocalComponentCausedUpdater())
		{
			$errorList[] = 'error quote';
		}

		if (!Loader::IncludeModule('currency'))
		{
			$errorList[] = 'need install module: currency';
		}

		if (!Loader::IncludeModule('catalog'))
		{
			$errorList[] = 'need install module: catalog';
		}

		if (!Loader::IncludeModule('sale'))
		{
			$errorList[] = 'need install module: sale';
		}

		if(!empty($errorList))
		{
			$return = [
				'NEXT' => false,
				'ERROR_ACTION' => $errorList,
				'ERROR_MESSAGES' => Loc::getMessage(
					'CRM_ERROR_CONFIGURATION_'.$type.'_EXCEPTION',
					[
						'#CODE#' => static::ENTITY_CODE
					]
				)
			];
		}
		else
		{
			$return = true;
		}

		return $return;
	}

	private static function filterTypeListByOptions(array $types, array $options): array
	{
		$filteredTypes = [];

		$helper = new Helper();
		$automatedSolutionModeParams = $helper->getAutomatedSolutionModeParams($options);

		foreach ($types as $typeInfo)
		{
			$typeCategoryId = $typeInfo['ID'];
			if (static::isDynamicEntityStage($typeCategoryId))
			{
				$dynamicEntityTypeId = static::getDynamicEntityTypeIdByCategoryId($typeCategoryId);
				if (
					$helper->checkDynamicTypeExportConditions(
						array_merge(
							$automatedSolutionModeParams,
							$helper->getDynamicTypeCheckExportParamsByEntityTypeId($dynamicEntityTypeId)
						)
					)
				)
				{
					$filteredTypes[] = $typeInfo;
				}
			}
			elseif (!$automatedSolutionModeParams['isAutomatedSolutionMode'])
			{
				$filteredTypes[] = $typeInfo;
			}
		}

		return $filteredTypes;
	}

	/**
	 * @param $option
	 *
	 * @return mixed
	 * @throws LoaderException
	 * @throws ArgumentException
	 */
	public static function export($option)
	{
		if(!Manifest::isEntityAvailable('', $option, static::$accessManifest))
		{
			return null;
		}

		$resultCheck = static::checkRequiredParams('EXPORT');
		if (is_array($resultCheck))
		{
			return $resultCheck;
		}

		$step = false;
		if(array_key_exists('STEP', $option))
		{
			$step = $option['STEP'];
		}

		$return = [
			'FILE_NAME' => '',
			'CONTENT' => [],
			'NEXT' => $step
		];
		$typeList = array_values(CCrmStatus::GetEntityTypes());
		$typeList = static::filterTypeListByOptions($typeList, $option);
		if($typeList[$step])
		{
			if(mb_strpos($typeList[$step]['ID'], static::$dealStageStart) !== false)
			{
				$allDeal = DealCategory::getAll(true);
				$allDealName = array_column($allDeal, 'NAME', 'ID');

				if($typeList[$step]['ID'] == static::$dealStageStart)
				{
					$typeList[$step]['NAME'] = $allDealName[0];
				}
				else
				{
					$matches = [];
					if(preg_match('/^'.static::$customDealStagePrefix.'([0-9]+)/', $typeList[$step]['ID'], $matches))
					{
						$id = $matches[1];
						if(!empty($allDealName[$id]))
						{
							$typeList[$step]['NAME'] = $allDealName[$id];
						}
					}
				}
			}

			$return['FILE_NAME'] = $typeList[$step]['ID'];
			$return['CONTENT']['ENTITY'] = $typeList[$step];

			$list = StatusTable::getList([
				'order' => [
					'ID' => 'ASC',
				],
				'filter' => [
					'=ENTITY_ID' => $typeList[$step]['ID'],
				],
			]);
			while($status = $list->fetch())
			{
				$return['CONTENT']['ITEMS'][] = $status;
			}
		}
		else
		{
			$return['NEXT'] = false;
		}

		return $return;
	}

	/**
	 * @param $option
	 *
	 * @return mixed
	 * @throws ArgumentException
	 * @throws LoaderException
	 */
	public static function clear($option)
	{
		if(!Manifest::isEntityAvailable('', $option, static::$accessManifest))
		{
			return null;
		}

		$helper = new Helper();
		if (!$helper->checkAutomatedSolutionModeClearParams($option))
		{
			return null;
		}

		$resultCheck = static::checkRequiredParams('CLEAR');
		if (is_array($resultCheck))
		{
			return $resultCheck;
		}

		$result = [
			'NEXT' => false
		];
		$step = (int)$option['STEP'];
		$clearFull = $option['CLEAR_FULL'];

		$entityList = array_values(CCrmStatus::GetEntityTypes());
		$entityList = static::filterTypeListByOptions($entityList, $option);
		$staticEntityCount = 0;
		$dynamicEntityCount = 0;
		foreach ($entityList as $entityInfo)
		{
			static::isDynamicEntityStage($entityInfo['ID']) ? $dynamicEntityCount++ : $staticEntityCount++;
		}
		$entitiesCount = $staticEntityCount + $dynamicEntityCount;
		if ($step >= $staticEntityCount && $dynamicEntityCount > 0 && $step < $entitiesCount)
		{
			$index = $entitiesCount + $staticEntityCount - $step - 1;
		}
		else
		{
			$index = $step;
		}

		$entityId = $entityList[$index]['ID'] ?? '';
		$isDynamicType = static::isDynamicEntityStage($entityId);
		if(!empty($entityId))
		{
			$result['NEXT'] = $step;

			// skip dynamic types based on static entity
			if (
				isset($entityList[$index]['ENTITY_TYPE_ID'])
				&& CCrmOwnerType::isDynamicTypeBasedStaticEntity((int)$entityList[$index]['ENTITY_TYPE_ID'])
			)
			{
				return $result;
			}

			$entity = new CCrmStatus($entityId);

			if ($clearFull || $isDynamicType || in_array($entityId, static::$isEntityTypeFunnel))
			{
				$langStatus = Application::getDocumentRoot(). BX_ROOT.'/modules/crm/install/index.php';
				Loc::loadMessages($langStatus);

				$entity->DeleteAll();

				if ($isDynamicType)
				{
					if (!empty($entityId) && static::isDynamicEntityStage($entityId))
					{
						$factory = null;
						$entityTypeId = static::getDynamicEntityTypeIdByCategoryId($entityId);
						if ($entityTypeId > 0)
						{
							$factory = Container::getInstance()->getFactory($entityTypeId);
						}
						if ($factory)
						{
							$categoryId = static::getCategoryIdByDynamicEntityId($entityId);
							$category = $factory->getCategory($categoryId);
							if ($category)
							{
								if ($clearFull)
								{
									if (Container::getInstance()->getUserPermissions()->canDeleteCategory($category))
									{
										try
										{
											$category->delete();
											$result['OWNER_DELETE'][] = [
												'ENTITY_TYPE' => static::getDynamicOwnerEntityType($entityTypeId),
												'ENTITY' => $categoryId
											];
										}
										catch(Exception $e)
										{
										}
									}
								}
								else
								{
									if (Container::getInstance()->getUserPermissions()->canUpdateCategory($category))
									{
										try
										{
											$category->setSort(static::$clearSort + $category->getSort());
											$category->save();
										}
										catch(Exception $e)
										{
										}
									}
								}
							}
						}
					}
				}
				else
				{
					CCrmStatus::InstallDefault($entityId);

					$addList = [];
					if($entityId === 'INDUSTRY')
					{
						$addList = [
							[
								'NAME' => Loc::getMessage('CRM_INDUSTRY_IT'),
								'STATUS_ID' => 'IT',
								'SORT' => 10,
								'SYSTEM' => 'N'
							],
							[
								'NAME' => Loc::getMessage('CRM_INDUSTRY_TELECOM'),
								'STATUS_ID' => 'TELECOM',
								'SORT' => 20,
								'SYSTEM' => 'N'
							],
							[
								'NAME' => Loc::getMessage('CRM_INDUSTRY_MANUFACTURING'),
								'STATUS_ID' => 'MANUFACTURING',
								'SORT' => 30,
								'SYSTEM' => 'N'
							],
							[
								'NAME' => Loc::getMessage('CRM_INDUSTRY_BANKING'),
								'STATUS_ID' => 'BANKING',
								'SORT' => 40,
								'SYSTEM' => 'N'
							],
							[
								'NAME' => Loc::getMessage('CRM_INDUSTRY_CONSULTING'),
								'STATUS_ID' => 'CONSULTING',
								'SORT' => 50,
								'SYSTEM' => 'N'
							],
							[
								'NAME' => Loc::getMessage('CRM_INDUSTRY_FINANCE'),
								'STATUS_ID' => 'FINANCE',
								'SORT' => 60,
								'SYSTEM' => 'N'
							],
							[
								'NAME' => Loc::getMessage('CRM_INDUSTRY_GOVERNMENT'),
								'STATUS_ID' => 'GOVERNMENT',
								'SORT' => 70,
								'SYSTEM' => 'N'
							],
							[
								'NAME' => Loc::getMessage('CRM_INDUSTRY_DELIVERY'),
								'STATUS_ID' => 'DELIVERY',
								'SORT' => 80,
								'SYSTEM' => 'N'
							],
							[
								'NAME' => Loc::getMessage('CRM_INDUSTRY_ENTERTAINMENT'),
								'STATUS_ID' => 'ENTERTAINMENT',
								'SORT' => 90,
								'SYSTEM' => 'N'
							],
							[
								'NAME' => Loc::getMessage('CRM_INDUSTRY_NOTPROFIT'),
								'STATUS_ID' => 'NOTPROFIT',
								'SORT' => 100,
								'SYSTEM' => 'N'
							],
							[
								'NAME' => Loc::getMessage('CRM_INDUSTRY_OTHER'),
								'STATUS_ID' => 'OTHER',
								'SORT' => 110,
								'SYSTEM' => 'Y'
							]
						];
					}
					elseif($entityId === 'DEAL_TYPE')
					{
						$addList = [
							[
								'NAME' => Loc::getMessage('CRM_DEAL_TYPE_SALE'),
								'STATUS_ID' => 'SALE',
								'SORT' => 10,
								'SYSTEM' => 'Y'
							],
							[
								'NAME' => Loc::getMessage('CRM_DEAL_TYPE_COMPLEX'),
								'STATUS_ID' => 'COMPLEX',
								'SORT' => 20,
								'SYSTEM' => 'N'
							],
							[
								'NAME' => Loc::getMessage('CRM_DEAL_TYPE_GOODS'),
								'STATUS_ID' => 'GOODS',
								'SORT' => 30,
								'SYSTEM' => 'N'
							],
							[
								'NAME' => Loc::getMessage('CRM_DEAL_TYPE_SERVICES'),
								'STATUS_ID' => 'SERVICES',
								'SORT' => 40,
								'SYSTEM' => 'N'
							],
							[
								'NAME' => Loc::getMessage('CRM_DEAL_TYPE_SERVICE'),
								'STATUS_ID' => 'SERVICE',
								'SORT' => 50,
								'SYSTEM' => 'N'
							]
						];
					}
					elseif($entityId === 'DEAL_STATE')
					{
						$addList = [
							[
								'NAME' => Loc::getMessage('CRM_DEAL_STATE_PLANNED'),
								'STATUS_ID' => 'PLANNED',
								'SORT' => 10,
								'SYSTEM' => 'N'
							],
							[
								'NAME' => Loc::getMessage('CRM_DEAL_STATE_PROCESS'),
								'STATUS_ID' => 'PROCESS',
								'SORT' => 20,
								'SYSTEM' => 'Y'
							],
							[
								'NAME' => Loc::getMessage('CRM_DEAL_STATE_COMPLETE'),
								'STATUS_ID' => 'COMPLETE',
								'SORT' => 30,
								'SYSTEM' => 'Y'
							],
							[
								'NAME' => Loc::getMessage('CRM_DEAL_STATE_CANCELED'),
								'STATUS_ID' => 'CANCELED',
								'SORT' => 40,
								'SYSTEM' => 'Y'
							]
						];
					}
					elseif($entityId === 'EVENT_TYPE')
					{
						$addList = [
							[
								'NAME' => Loc::getMessage('CRM_EVENT_TYPE_INFO'),
								'STATUS_ID' => 'INFO',
								'SORT' => 10,
								'SYSTEM' => 'Y'
							],
							[
								'NAME' => Loc::getMessage('CRM_EVENT_TYPE_PHONE'),
								'STATUS_ID' => 'PHONE',
								'SORT' => 20,
								'SYSTEM' => 'Y'
							],
							[
								'NAME' => Loc::getMessage('CRM_EVENT_TYPE_MESSAGE'),
								'STATUS_ID' => 'MESSAGE',
								'SORT' => 30,
								'SYSTEM' => 'Y'
							]
						];
					}
					elseif($entityId === 'HONORIFIC')
					{
						\Bitrix\Crm\Honorific::installDefault();
					}
					foreach($addList as $item)
					{
						$entity->Add($item);
					}
				}
			}
			else
			{
				foreach ($entity->GetStatus($entityId) as $data)
				{
					$entity->Update($data['ID'], ['SORT' => $data['SORT'] + static::$clearSort]);
				}
			}
		}
		else
		{
			if (DealCategory::isCustomized())
			{
				$oldCategory = DealCategory::getAll(false);
				foreach ($oldCategory as $category)
				{
					if ($clearFull)
					{
						try
						{
							DealCategory::delete($category['ID']);

							$result['OWNER_DELETE'][] = [
								'ENTITY_TYPE' => self::OWNER_ENTITY_TYPE_CRM_DEAL_CATEGORY,
								'ENTITY' => $category['ID']
							];
						}
						catch(Exception $e)
						{
						}
					}
					else
					{
						try
						{
							DealCategory::update(
								$category['ID'],
								[
									'SORT' => static::$clearSort + $category['SORT']
								]
							);
						}
						catch(Exception $e)
						{
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param $import
	 *
	 * @return mixed
	 * @throws LoaderException
	 * @throws ArgumentOutOfRangeException
	 */
	public static function import($import)
	{
		if(!Manifest::isEntityAvailable('', $import, static::$accessManifest))
		{
			return null;
		}

		$helper = new Helper();
		if (!$helper->checkAutomatedSolutionModeImportParams($import))
		{
			return null;
		}

		$resultCheck = static::checkRequiredParams('IMPORT');
		if (is_array($resultCheck))
		{
			return $resultCheck;
		}

		$result = [];
		if(!isset($import['CONTENT']['DATA']))
		{
			return $result;
		}
		$itemList = $import['CONTENT']['DATA'];
		$entityTypes = CCrmStatus::GetEntityTypes();

		if(!empty($itemList['ENTITY']['ID']) && !empty($itemList['ITEMS']))
		{
			\Bitrix\Main\Type\Collection::sortByColumn($itemList['ITEMS'], 'SORT');
			$entityId = $itemList['ENTITY']['ID'];
			$isDynamicType = static::isDynamicEntityStage($entityId);

			$automatedSolutionModeParams = $helper->getAutomatedSolutionModeImportParams($import);
			if (!$isDynamicType && $automatedSolutionModeParams['isAutomatedSolutionMode'])
			{
				return $result;
			}

			if (!(mb_strpos($entityId, static::$customDealStagePrefix) === 0 || $isDynamicType))
			{
				$entityList = array_column($entityTypes,'ID');
				if(!in_array($entityId, $entityList))
				{
					$entityId = '';
				}
			}

			// skip dynamic types based on static entity
			if (
				isset($entityTypes[$entityId]['ENTITY_TYPE_ID'])
				&& CCrmOwnerType::isDynamicTypeBasedStaticEntity((int)$entityTypes[$entityId]['ENTITY_TYPE_ID'])
			)
			{
				$entityId = '';
			}

			// skip dynamic types if they have not been imported for backward compatibility
			if ($isDynamicType && !isset($import['RATIO']['CRM_DYNAMIC_TYPES']))
			{
				$entityId = '';
			}

			if($entityId != '')
			{
				$entity = new CCrmStatus($entityId);
				//region standard funnel
				if(in_array($entityId, static::$isEntityTypeFunnel))
				{
					if($entityId === static::$dealStageStart)
					{
						try
						{
							if (!empty($itemList['ENTITY']['NAME']))
							{
								DealCategory::setDefaultCategoryName($itemList['ENTITY']['NAME']);
							}
							if (intVal($itemList['ENTITY']['SORT']) > 0)
							{
								DealCategory::setDefaultCategorySort(intVal($itemList['ENTITY']['SORT']));
							}
						}
						catch (Exception $ex)
						{
						}
					}

					$existsStatuses = static::getExistsStatuses($entityId);
					$resultCollection = StatusTable::createCollection();

					foreach ($itemList['ITEMS'] as $item)
					{
						$existingCouple = $existsStatuses[$item['STATUS_ID']] ?? null;

						if(!$item['NAME'])
						{
							continue;
						}

						$color = $item['COLOR'] ?? $existingCouple?->getColor();
						if(
							empty($color)
							&& is_array($itemList['COLOR_SETTING'])
							&& isset($itemList['COLOR_SETTING'][$item['STATUS_ID']]['COLOR'])
						)
						{
							$color = $itemList['COLOR_SETTING'][$item['STATUS_ID']]['COLOR'];
						}

						$semantics = static::getSemanticsByStatus($item);

						if($existingCouple !== null)
						{
							$existingCouple
								->setSort(intVal($item['SORT']))
								->setName($item['NAME'])
								->setColor($color)
								->setSemantics($semantics)
							;

							$resultCollection->add($existingCouple);
							unset($existsStatuses[$item['STATUS_ID']]);

							continue;
						}

						$newStatus = (StatusTable::createObject(false))
							->setEntityId($entityId)
							->setStatusId($item['STATUS_ID'])
							->setName($item['NAME'])
							->setNameInit($item['NAME_INIT'])
							->setSort(intVal($item['SORT']))
							->setSystem(false)
							->setColor($color)
							->setSemantics($semantics)
						;

						$resultCollection->add($newStatus);
					}

					if(!empty($existsStatuses))
					{
						foreach ($existsStatuses as $existsStatus)
						{
							if ($existsStatus->getSystem())
							{
								$resultCollection->add($existsStatus);
							}
							else
							{
								$existsStatus->delete();
							}
						}
					}

					(new FunnelStatusCollectionRevalidator($resultCollection))->save();
				}
				//endregion standard funnel
				//region custom deal funnel
				elseif (str_contains($entityId, static::$customDealStagePrefix))
				{
					try
					{
						$categoryParams = [
							'NAME' => $itemList['ENTITY']['NAME'],
							'SORT' => (
								(int)$itemList['ENTITY']['SORT'] > 0
								? (int)$itemList['ENTITY']['SORT'] : 10
							)
						];

						if($import['APP_ID'] > 0)
						{
							$categoryParams['ORIGIN_ID'] = $import['APP_ID'];
							$categoryParams['ORIGINATOR_ID'] = DealCategory::MARKETPLACE_CRM_ORIGINATOR;
						}

						$categotyId = DealCategory::add($categoryParams);

						if($categotyId > 0)
						{
							$result['OWNER'] = [
								'ENTITY_TYPE' => self::OWNER_ENTITY_TYPE_CRM_DEAL_CATEGORY,
								'ENTITY' => $categotyId
							];

							$oldEntityId = $itemList['ENTITY']['ID'];
							$oldCategoryId = DealCategory::convertFromStatusEntityID($oldEntityId);
							$prefixStatusOld = DealCategory::prepareStageNamespaceID($oldCategoryId);
							$prefixStatus = DealCategory::prepareStageNamespaceID($categotyId);
							$newEntityId = static::$customDealStagePrefix . $categotyId;
							$existsStatuses = static::getExistsStatuses($newEntityId);
							$resultCollection = StatusTable::createCollection();
							static::$statusSemantics[$oldEntityId] = [
								'final' => $prefixStatusOld . ':WON',
							];
							foreach ($itemList['ITEMS'] as $item)
							{
								if(!$item['NAME'])
								{
									continue;
								}

								$newStatusId = str_replace($prefixStatusOld, $prefixStatus, $item['STATUS_ID']);
								$existingCouple = $existsStatuses[$newStatusId] ?? null;
								$color = $item['COLOR'] ?? $existingCouple?->getColor();
								if(
									empty($color)
									&& is_array($itemList['COLOR_SETTING'])
									&& isset($itemList['COLOR_SETTING'][$item['STATUS_ID']]['COLOR'])
								)
								{
									$color = $itemList['COLOR_SETTING'][$item['STATUS_ID']]['COLOR'];
								}
								$semantics = static::getSemanticsByStatus($item);
								if ($existingCouple !== null)
								{
									$existingCouple
										->setSort((int)($item['SORT']))
										->setName($item['NAME'])
										->setColor($color)
										->setSemantics($semantics)
										->setCategoryId($categotyId)
									;
									$resultCollection->add($existingCouple);
									unset($existsStatuses[$newStatusId]);
								}
								else
								{
									$newStatus = (StatusTable::createObject(false))
										->setEntityId($newEntityId)
										->setStatusId($newStatusId)
										->setName($item['NAME'])
										->setNameInit($item['NAME_INIT'])
										->setSort((int)($item['SORT']))
										->setSystem(false)
										->setColor($color)
										->setSemantics($semantics)
										->setCategoryId($categotyId)
									;
									$resultCollection->add($newStatus);
								}
							}
							if (!empty($existsStatuses))
							{
								foreach ($existsStatuses as $existsStatus)
								{
									if ($existsStatus->getSystem())
									{
										$resultCollection->add($existsStatus);
									}
									else
									{
										$existsStatus->delete();
									}
								}
							}
							(new FunnelStatusCollectionRevalidator($resultCollection))->save();
							$result['RATIO'][$oldCategoryId] = $categotyId;
						}
					}
					catch (Exception $e)
					{
						$result['ERROR_EXCEPTION'] = Loc::getMessage(
							'CRM_ERROR_CONFIGURATION_IMPORT_EXCEPTION_DEAL_STAGE_ADD',
							[
								'#NAME#' => $itemList['ENTITY']['NAME'],
							]
						);
					}
				}
				//endregion custom deal funnel
				//region dynamic entity funnel and stages
				elseif ($isDynamicType)
				{
					try
					{
						$isDefaultCategory = $itemList['ENTITY']['IS_DEFAULT_CATEGORY'] ?? false;
						$categoryParams = [
							'NAME' => $itemList['ENTITY']['CATEGORY_NAME'],
							'SORT' => (
							(int)$itemList['ENTITY']['CATEGORY_SORT'] > 0
								? (int)$itemList['ENTITY']['CATEGORY_SORT'] : 10
							)
						];

						$categoryId = 0;
						$entityTypeId = 0;
						$factory = null;
						$oldEntityTypeId = static::getDynamicEntityTypeIdByCategoryId($entityId);
						$ratioKey = "DYNAMIC_$oldEntityTypeId";
						if (isset($import['RATIO']['CRM_DYNAMIC_TYPES'][$ratioKey]))
						{
							$entityTypeId = (int)$import['RATIO']['CRM_DYNAMIC_TYPES'][$ratioKey];
						}
						if ($entityTypeId > 0 && $helper->checkDynamicTypeImportConditions($entityTypeId, $import))
						{
							$factory = Container::getInstance()->getFactory($entityTypeId);
						}
						unset($ratioKey);

						if ($factory)
						{
							if (!$factory->isCategoriesSupported())
							{
								throw new SystemException('Dynamic type categories is not supported.');
							}
							$category = null;
							if ($isDefaultCategory)
							{
								$category = $factory->getDefaultCategory();
							}
							if ($category)
							{
								$category->setName($categoryParams['NAME']);
								$category->setSort($categoryParams['SORT']);
							}
							else
							{
								$category = $factory->createCategory($categoryParams);
							}
							$categoryResult = $category->save();
							if ($categoryResult->isSuccess())
							{
								Container::getInstance()->getDynamicTypesMap()->reloadCategories();
								$categoryId = $category->getId();
							}
							else
							{
								throw new SystemException('Failed to import dynamic type category.');
							}
						}
						else
						{
							throw new SystemException('Failed to get dynamic type factory.');
						}

						if($categoryId > 0)
						{
							$result['OWNER'] = [
								'ENTITY_TYPE' => static::getDynamicOwnerEntityType($entityTypeId),
								'ENTITY' => $categoryId
							];

							$oldEntityId = $entityId;
							$oldCategoryId = static::getCategoryIdByDynamicEntityId($oldEntityId);
							$prefixStatusOld = static::getDynamicStagePrefix($oldEntityTypeId, $oldCategoryId);
							$prefixStatus = static::getDynamicStagePrefix($entityTypeId, $categoryId);
							$newEntityId = static::getDynamicEntityId($entityTypeId, $categoryId);
							$existsStatuses = static::getExistsStatuses($newEntityId);
							$resultCollection = StatusTable::createCollection();
							static::$statusSemantics[$oldEntityId] = [
								'final' => $prefixStatusOld . ':SUCCESS',
							];
							foreach ($itemList['ITEMS'] as $item)
							{
								if(!$item['NAME'])
								{
									continue;
								}

								$newStatusId = str_replace($prefixStatusOld, $prefixStatus, $item['STATUS_ID']);
								$existingCouple = $existsStatuses[$newStatusId] ?? null;
								$color = $item['COLOR'] ?? $existingCouple?->getColor();
								if(
									empty($color)
									&& is_array($itemList['COLOR_SETTING'])
									&& isset($itemList['COLOR_SETTING'][$item['STATUS_ID']]['COLOR'])
								)
								{
									$color = $itemList['COLOR_SETTING'][$item['STATUS_ID']]['COLOR'];
								}
								$semantics = static::getSemanticsByStatus($item);
								if ($existingCouple !== null)
								{
									$existingCouple
										->setSort((int)($item['SORT']))
										->setName($item['NAME'])
										->setColor($color)
										->setSemantics($semantics)
										->setCategoryId($categoryId)
									;
									$resultCollection->add($existingCouple);
									unset($existsStatuses[$newStatusId]);
								}
								else
								{
									$newStatus = (StatusTable::createObject(false))
										->setEntityId($newEntityId)
										->setStatusId($newStatusId)
										->setName($item['NAME'])
										->setNameInit($item['NAME_INIT'])
										->setSort((int)($item['SORT']))
										->setSystem(false)
										->setColor($color)
										->setSemantics($semantics)
										->setCategoryId($categoryId)
									;
									$resultCollection->add($newStatus);
								}
							}
							if (!empty($existsStatuses))
							{
								foreach ($existsStatuses as $existsStatus)
								{
									if ($existsStatus->getSystem())
									{
										$resultCollection->add($existsStatus);
									}
									else
									{
										$existsStatus->delete();
									}
								}
							}
							(new FunnelStatusCollectionRevalidator($resultCollection))->save();

							$oldCategoryCode = static::getDynamicCategoryCode($oldEntityTypeId, $oldCategoryId);
							$result['RATIO'][$oldCategoryCode] = $categoryId;
						}
					}
					catch (Exception $e)
					{
						$result['ERROR_EXCEPTION'] = Loc::getMessage(
							'CRM_ERROR_CONFIGURATION_IMPORT_EXCEPTION_DYMANIC_ENTITY_CATEGORY_ADD',
							[
								'#NAME#' => $itemList['ENTITY']['NAME'],
							]
						);
					}
				}
				//endregion dynamic entity funnel and stages
				//region dictionary
				else
				{
					$oldList = array_values($entity->GetStatus($entityId));
					$oldStatusList = array_column($entity->GetStatus($entityId), 'STATUS_ID');
					foreach ($itemList['ITEMS'] as $item)
					{
						$key = array_search($item['STATUS_ID'], $oldStatusList);
						if($key !== false)
						{
							$entity->update(
								$oldList[$key]['ID'],
								[
									'NAME' => $item['NAME'],
									'NAME_INIT' => $item['NAME'],
									'SORT' => intVal($item['SORT'])
								]
							);
							unset($oldList[$key]);
						}
						else
						{
							$entity->add(
								[
									'ENTITY_ID' => $entityId,
									'STATUS_ID' => $item['STATUS_ID'],
									'NAME' => $item['NAME'],
									'NAME_INIT' => $item['NAME'],
									'SORT' => intVal($item['SORT']),
									'SYSTEM' => 'N'
								]
							);
						}
					}

					if(!empty($oldList))
					{
						foreach ($oldList as $item)
						{
							if ($item['SYSTEM'] == 'N')
							{
								$entity->delete($item['ID']);
							}
						}
					}
				}
				//endregion dictionary
			}
		}

		return $result;
	}

	/**
	 * @param string $entityId
	 * @return Array<string, EO_Status>
	 */
	private static function getExistsStatuses(string $entityId): array
	{
		$statusCollection = StatusTable::query()
			->where('ENTITY_ID', $entityId)
			->fetchCollection()
		;

		$result = [];
		foreach ($statusCollection as $status)
		{
			$result[$status->getStatusId()] = $status;
		}

		return $result;
	}

	private static function getSemanticsByStatus(array $status): ?string
	{
		if(!empty($status['SEMANTICS']))
		{
			return $status['SEMANTICS'];
		}

		if(isset(static::$statusSemantics[$status['ENTITY_ID']]['isSuccessPassed']))
		{
			return PhaseSemantics::FAILURE;
		}
		if($status['STATUS_ID'] === static::$statusSemantics[$status['ENTITY_ID']]['final'])
		{
			static::$statusSemantics[$status['ENTITY_ID']]['isSuccessPassed'] = true;
			return PhaseSemantics::SUCCESS;
		}

		return null;
	}

	protected static function getDynamicEntityIdPattern(): string
	{
		return '/^' . preg_quote(CCrmOwnerType::DynamicTypePrefixName) . '(\\d+)_STAGE_(\\d+)$/u';
	}

	protected static function isDynamicEntityStage(string $entityId): bool
	{
		return preg_match(static::getDynamicEntityIdPattern(), $entityId);
	}

	protected static function getDynamicEntityTypeIdByCategoryId(string $entityId): int
	{
		$matches = [];

		return preg_match(static::getDynamicEntityIdPattern(), $entityId, $matches) ? (int)$matches[1] : 0;
	}

	protected static function getCategoryIdByDynamicEntityId(string $entityId): int
	{
		$matches = [];

		return preg_match(static::getDynamicEntityIdPattern(), $entityId, $matches) ? (int)$matches[2] : 0;
	}

	protected static function getDynamicEntityId(int $entityTypeId, int $categoryId): string
	{
		return CCrmOwnerType::DynamicTypePrefixName . "{$entityTypeId}_STAGE_$categoryId";
	}

	protected static function getDynamicCategoryCode(int $entityTypeId, int $categoryId): string
	{
		return "DT{$entityTypeId}_$categoryId";
	}

	protected static function getDynamicStagePrefix(int $entityTypeId, int $categoryId): string
	{
		return static::getDynamicCategoryCode($entityTypeId, $categoryId) . ':';
	}

	protected static function getDynamicOwnerEntityType(int $entityTypeId)
	{
		return "DYNAMIC_{$entityTypeId}_CATEGORY";
	}
}