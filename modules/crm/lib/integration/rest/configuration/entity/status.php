<?php

namespace Bitrix\Crm\Integration\Rest\Configuration\Entity;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Rest\Configuration\Helper;
use CAllCrmInvoice;
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
		'crm'
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

	/**
	 * @param $option
	 *
	 * @return mixed
	 * @throws LoaderException
	 * @throws ArgumentException
	 */
	public static function export($option)
	{
		if(!Helper::checkAccessManifest($option, static::$accessManifest))
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
		if(!Helper::checkAccessManifest($option, static::$accessManifest))
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
		$step = $option['STEP'];
		$clearFull = $option['CLEAR_FULL'];

		$entityList = array_values(CCrmStatus::GetEntityTypes());

		if(!empty($entityList[$step]['ID']))
		{
			$result['NEXT'] = $step;
			$entityID = $entityList[$step]['ID'];

			// skip dynamic type based statuses
			if (
				isset($entityList[$step]['ENTITY_TYPE_ID'])
				&& \CCrmOwnerType::isUseDynamicTypeBasedApproach((int)$entityList[$step]['ENTITY_TYPE_ID'])
			)
			{
				return $result;
			}

			$entity = new CCrmStatus($entityList[$step]['ID']);

			if($clearFull || in_array($entityID, static::$isEntityTypeFunnel))
			{
				$langStatus = Application::getDocumentRoot(). BX_ROOT.'/modules/crm/install/index.php';
				Loc::loadMessages($langStatus);

				$entity->DeleteAll();
				CCrmStatus::InstallDefault($entityID);

				$addList = [];
				if($entityList[$step]['ID'] === 'INDUSTRY')
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
				elseif($entityList[$step]['ID'] === 'DEAL_TYPE')
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
				elseif($entityList[$step]['ID'] === 'DEAL_STATE')
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
				elseif($entityList[$step]['ID'] === 'EVENT_TYPE')
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
				elseif($entityList[$step]['ID'] === 'HONORIFIC')
				{
					\Bitrix\Crm\Honorific::installDefault();
				}
				foreach($addList as $item)
					$entity->Add($item);
			}
			else
			{
				$oldData = $entity->GetStatus($entityID);
				foreach ($oldData as $data)
				{
					$entity->Update(
						$data['ID'],
						[
							'SORT' => $data['SORT'] + static::$clearSort
						]
					);
				}
			}
		}
		elseif(DealCategory::isCustomized())
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
		if(!Helper::checkAccessManifest($import, static::$accessManifest))
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
			$entityID = $itemList['ENTITY']['ID'];
			if(mb_strpos($entityID, static::$customDealStagePrefix) === false)
			{
				$entityList = array_column($entityTypes,'ID');
				if(!in_array($entityID,$entityList))
				{
					$entityID = '';
				}
			}

			// skip dynamic type based statuses
			if (
				isset($entityTypes[$entityID]['ENTITY_TYPE_ID'])
				&& \CCrmOwnerType::isUseDynamicTypeBasedApproach((int)$entityTypes[$entityID]['ENTITY_TYPE_ID'])
			)
			{
				$entityID = '';
			}

			if($entityID != '')
			{
				$entity = new CCrmStatus($entityID);
				//region standard funnel
				if(in_array($entityID, static::$isEntityTypeFunnel))
				{
					if($entityID == static::$dealStageStart)
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
					$oldData = array_column($entity->GetStatus($entityID), null, 'STATUS_ID');
					foreach ($itemList['ITEMS'] as $item)
					{
						if(!$item['NAME'])
						{
							continue;
						}
						$color = $item['COLOR'] ?? $oldData['COLOR'] ?? null;
						if(
							empty($color)
							&& is_array($itemList['COLOR_SETTING'])
							&& isset($itemList['COLOR_SETTING'][$item['STATUS_ID']]['COLOR'])
						)
						{
							$color = $itemList['COLOR_SETTING'][$item['STATUS_ID']]['COLOR'];
						}
						$semantics = static::getSemanticsByStatus($item);
						if(!empty($oldData[$item['STATUS_ID']]))
						{
							$saveData = [
								'SORT' => intVal($item['SORT']),
								'NAME' => $item['NAME'],
								'COLOR' => $color,
								'SEMANTICS' => $semantics,
							];
							if(!empty($saveData))
							{
								$entity->update(
									$oldData[$item['STATUS_ID']]['ID'],
									$saveData
								);
							}
							unset($oldData[$item['STATUS_ID']]);
						}
						else
						{
							$entity->add(
								[
									'ENTITY_ID' => $entityID,
									'STATUS_ID' => $item['STATUS_ID'],
									'NAME' => $item['NAME'],
									'NAME_INIT' => $item['NAME_INIT'],
									'SORT' => intVal($item['SORT']),
									'SYSTEM' => 'N',
									'COLOR' => $color,
									'SEMANTICS' => $semantics,
								]
							);
						}
					}

					if(!empty($oldData))
					{
						foreach ($oldData as $item)
						{
							if ($item['SYSTEM'] === 'N')
							{
								$entity->delete($item['ID']);
							}
						}
					}
				}
				//end region standard funnel
				//region custom deal funnel
				elseif(mb_strpos($entityID, static::$customDealStagePrefix) !== false)
				{
					try
					{
						$dealCategory = [
							'NAME' => $itemList['ENTITY']['NAME'],
							'SORT' => (
								(int)$itemList['ENTITY']['SORT'] > 0
								? (int)$itemList['ENTITY']['SORT'] : 10
							)
						];

						if($import['APP_ID'] > 0)
						{
							$dealCategory['ORIGIN_ID'] = $import['APP_ID'];
							$dealCategory['ORIGINATOR_ID'] = DealCategory::MARKETPLACE_CRM_ORIGINATOR;
						}

						$ID = DealCategory::add($dealCategory);

						if($ID > 0)
						{
							$result['OWNER'] = [
								'ENTITY_TYPE' => self::OWNER_ENTITY_TYPE_CRM_DEAL_CATEGORY,
								'ENTITY' => $ID
							];

							$oldID = DealCategory::convertFromStatusEntityID($itemList['ENTITY']['ID']);
							$prefixStatusOld = DealCategory::prepareStageNamespaceID($oldID);
							$prefixStatus = DealCategory::prepareStageNamespaceID($ID);
							$oldEntityId = static::$customDealStagePrefix . $oldID;
							$entityID = static::$customDealStagePrefix . $ID;
							$entity = new CCrmStatus($entityID);

							$defaultStatus = array_column($entity->GetStatus($entityID), null, 'STATUS_ID');
							static::$statusSemantics[$oldEntityId] = [
								'final' => $prefixStatusOld . ':WON',
							];
							foreach ($itemList['ITEMS'] as $item)
							{
								$color = $item['COLOR'] ?? $defaultStatus['COLOR'] ?? null;
								if(
									empty($color)
									&& is_array($itemList['COLOR_SETTING'])
									&& isset($itemList['COLOR_SETTING'][$item['STATUS_ID']]['COLOR'])
								)
								{
									$color = $itemList['COLOR_SETTING'][$item['STATUS_ID']]['COLOR'];
								}
								$semantics = static::getSemanticsByStatus($item);
								$statusID = str_replace($prefixStatusOld, $prefixStatus, $item['STATUS_ID']);
								if (!empty($defaultStatus[$statusID]))
								{
									$entity->update(
										$defaultStatus[$statusID]['ID'],
										[
											'NAME' => $item['NAME'],
											'SORT' => intVal($item['SORT']),
											'SEMANTICS' => $semantics,
											'COLOR' => $color,
											'CATEGORY_ID' => $ID,
										]
									);
									unset($defaultStatus[$statusID]);
								}
								else
								{
									$entity->add(
										[
											'ENTITY_ID' => $entityID,
											'STATUS_ID' => str_replace(
												$prefixStatusOld,
												$prefixStatus,
												$item['STATUS_ID']
											),
											'NAME' => $item['NAME'],
											'NAME_INIT' => $item['NAME_INIT'],
											'SORT' => intVal($item['SORT']),
											'SYSTEM' => 'N',
											'SEMANTICS' => $semantics,
											'COLOR' => $color,
											'CATEGORY_ID' => $ID,
										]
									);
								}
							}
							foreach ($defaultStatus as $status)
							{
								if ($status['SYSTEM'] == 'N')
								{
									$entity->delete($status['ID']);
								}
							}
							$result['RATIO'][$oldID] = $ID;
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
				//end region custom deal funnel
				//region dictionary
				else
				{
					$oldList = array_values($entity->GetStatus($entityID));
					$oldStatusList = array_column($entity->GetStatus($entityID), 'STATUS_ID');
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
									'ENTITY_ID' => $entityID,
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
				//end region dictionary
			}
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
}