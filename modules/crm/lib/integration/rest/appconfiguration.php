<?php

namespace Bitrix\Crm\Integration\Rest;

use Bitrix\Crm\Entity\EntityEditorConfigScope;
use Bitrix\Crm\Entity\EntityEditorConfig;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\CustomerType;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Event;
use CAllCrmInvoice;
use CUserFieldEnum;
use CCrmOwnerType;
use CCrmStatus;
use CCrmFields;
use CCrmQuote;
use Exception;
use CLanguage;

Loc::loadMessages(__FILE__);

class AppConfiguration
{
	private static $entityList = [
		'CRM_ITEMS_LEAD' => 200,
		'CRM_ITEMS_DEAL' => 200,
		'CRM_STATUS' => 300,
		'CRM_FIELDS' => 400,
		'CRM_DETAIL_CONFIGURATION' => 500,
	];
	private static $clearSort = 99999;
	private static $dealStageStart = 'DEAL_STAGE';
	private static $customDealStagePrefix = 'DEAL_STAGE_';
	private static $isEntityTypeFunnel = [
		'STATUS', 'DEAL_STAGE', 'QUOTE_STATUS', 'CALL_LIST', 'INVOICE_STATUS'
	];
	private static $entityTypeDetailConfiguration = [];
	private static $context;
	private static $accessManifest = [
		'total',
		'crm'
	];

	public static function getEntityList()
	{
		return static::$entityList;
	}

	public static function getManifestList(Event $event)
	{
		$manifestList = [];
		$manifestList[] = [
			'CODE' => 'vertical_crm',
			'VERSION' => 1,
			'ACTIVE' => 'Y',
			'PLACEMENT' => [
				'crm',
				'crm_lead',
				'crm_deal',
				'crm_contact',
				'crm_company',
				'crm_settings'
			],
			'USES' => [
				'app',
				'crm',
				'bizproc_crm',
				'landing',
			],
			'TITLE' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_TITLE_VERTICAL_CRM"),
			'DESCRIPTION' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_DESCRIPTION_VERTICAL_CRM"),
			'COLOR' => '#ff799c',
			'ICON' => '/bitrix/images/crm/configuration/vertical-crm-icon.svg',
			'EXPORT_TITLE_PAGE' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_PAGE_TITLE_VERTICAL_CRM"),
			'EXPORT_TITLE_BLOCK' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_BLOCK_TITLE_VERTICAL_CRM"),
			'EXPORT_ACTION_DESCRIPTION' => Loc::getMessage("CRM_CONFIGURATION_MANIFEST_ACTION_DESCRIPTION_VERTICAL_CRM"),
		];

		return $manifestList;
	}

	public static function onEventExportController(Event $event)
	{
		$result = null;
		$code = $event->getParameter('CODE');
		if(!static::$entityList[$code])
		{
			return $result;
		}

		$manifest = $event->getParameter('MANIFEST');
		$access = array_intersect($manifest['USES'], static::$accessManifest);
		if(!$access)
		{
			return $result;
		}

		try
		{
			if(static::checkRequiredParams($code))
			{
				$step = $event->getParameter('STEP');
				switch ($code)
				{
					case 'CRM_STATUS':
						$result = static::exportStatus($step);
						break;
					case 'CRM_FIELDS':
						$result = static::exportFields($step);
						break;
					case 'CRM_DETAIL_CONFIGURATION':
						$result = static::exportDetailConfiguration($step);
						break;
				}
			}
		}
		catch (Exception $e)
		{
			$result['NEXT'] = false;
			$result['ERROR_ACTION'] = $e->getMessage();
			$result['ERROR_MESSAGES'] = Loc::getMessage(
				'CRM_ERROR_CONFIGURATION_EXPORT_EXCEPTION',
				[
					'#CODE#' => $code
				]
			);
		}

		return $result;
	}

	public static function onEventClearController(Event $event)
	{
		$code = $event->getParameter('CODE');
		if(!static::$entityList[$code])
		{
			return null;
		}
		$result = null;

		try
		{
			if(static::checkRequiredParams($code))
			{
				$option = $event->getParameters();
				switch ($code)
				{
					case 'CRM_STATUS':
						$result = static::clearStatus($option);
						break;
					case 'CRM_FIELDS':
						$result = static::clearFields($option);
						break;
					case 'CRM_DETAIL_CONFIGURATION':
						$result = static::clearDetailConfiguration($option);
						break;
					case 'CRM_ITEMS_LEAD':
						$result = static::clearLead($option);
						break;
					case 'CRM_ITEMS_DEAL':
						$result = static::clearDeal($option);
						break;
				}
			}
		}
		catch (Exception $e)
		{
			$result['NEXT'] = false;
			$result['ERROR_ACTION'] = $e->getMessage();
			$result['ERROR_MESSAGES'] = Loc::getMessage(
				'CRM_ERROR_CONFIGURATION_CLEAR_EXCEPTION',
				[
					'#CODE#' => $code
				]
			);
		}

		return $result;
	}

	public static function onEventImportController(Event $event)
	{
		$code = $event->getParameter('CODE');
		if(!static::$entityList[$code])
		{
			return null;
		}
		$result = null;

		try
		{
			if(static::checkRequiredParams($code))
			{
				$data = $event->getParameters();
				switch ($code)
				{
					case 'CRM_STATUS':
						$result = static::importStatus($data);
						break;
					case 'CRM_FIELDS':
						$result = static::importFields($data);
						break;
					case 'CRM_DETAIL_CONFIGURATION':
						$result = static::importDetailConfiguration($data);
						break;
				}
			}
		}
		catch (Exception $e)
		{
			$result['NEXT'] = false;
			$result['ERROR_ACTION'] = $e->getMessage();
			$result['ERROR_MESSAGES'] = Loc::getMessage(
				'CRM_ERROR_CONFIGURATION_IMPORT_EXCEPTION',
				[
					'#CODE#' => $code
				]
			);
		}

		return $result;
	}

	/**
	 *
	 * @param $type string of event
	 * @throws SystemException
	 * @return boolean
	 */
	private static function checkRequiredParams($type)
	{
		$return = true;
		if($type == 'CRM_STATUS')
		{
			if(!CAllCrmInvoice::installExternalEntities())
			{
				throw new SystemException('need install external entities crm invoice');
			}

			if(!CCrmQuote::LocalComponentCausedUpdater())
			{
				throw new SystemException('error quote');
			}

			if(!Loader::IncludeModule('currency'))
			{
				throw new SystemException('need install module: currency');
			}

			if(!Loader::IncludeModule('catalog'))
			{
				throw new SystemException('need install module: catalog');
			}

			if(!Loader::IncludeModule('sale'))
			{
				throw new SystemException('need install module: sale');
			}
		}
		elseif($type == 'CRM_DETAIL_CONFIGURATION')
		{
			static::$entityTypeDetailConfiguration = [
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
		}

		return $return;
	}

	//lead region
	private static function clearLead($option)
	{
		$result = [
			'NEXT' => false
		];
		$clearFull = $option['CLEAR_FULL'];
		if($clearFull)
		{
			$entity = new \CCrmLead(true);
			$res = $entity->getList([], [], [], 10);
			while($lead = $res->fetch())
			{
				if(!$entity->delete($lead['ID']))
				{
					$result['NEXT'] = false;
					$result['ERROR_ACTION'] = 'DELETE_ERROR_LEAD';
					break;
				}
				else
				{
					$result['NEXT'] = $lead['ID'];
				}
			}
		}
		return $result;
	}
	//end region lead

	//deal region
	private static function clearDeal($option)
	{
		$result = [
			'NEXT' => false
		];
		$clearFull = $option['CLEAR_FULL'];
		if($clearFull)
		{
			$entity = new \CCrmDeal(true);
			$res = $entity->getList([], [], [], 10);
			while($deal = $res->fetch())
			{
				if(!$entity->delete($deal['ID']))
				{
					$result['NEXT'] = false;
					$result['ERROR_ACTION'] = 'DELETE_ERROR_DEAL';
					break;
				}
				else
				{
					$result['NEXT'] = $deal['ID'];
				}
			}
		}
		return $result;
	}
	//end region deal

	//region status
	private static function exportStatus($step)
	{
		$return = [
			'FILE_NAME' => '',
			'CONTENT' => [],
			'NEXT' => $step
		];
		$typeList = array_values(CCrmStatus::GetEntityTypes());
		if($typeList[$step])
		{
			if(strpos($typeList[$step]['ID'], static::$dealStageStart) !== false)
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

			$res = CCrmStatus::GetList(
				[
					'ID' => 'ASC'
				],
				[
					'ENTITY_ID' => $typeList[$step]['ID']
				]
			);
			while($element = $res->Fetch())
			{
				$return['CONTENT']['ITEMS'][] = $element;
			}

			try
			{
				$data = Option::get('crm', 'CONFIG_STATUS_' . $typeList[$step]['ID']);
				if($data)
				{
					$return['CONTENT']['COLOR_SETTING'] = unserialize($data);
				}
			}
			catch (Exception $e)
			{
			}
		}
		else
		{
			$return['NEXT'] = false;
		}

		return $return;
	}

	private static function clearStatus($option)
	{
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

	private static function importStatus($import)
	{
		$result = [];
		if(!isset($import['CONTENT']['DATA']))
		{
			return $result;
		}
		$itemList = $import['CONTENT']['DATA'];

		if(!empty($itemList['ENTITY']['ID']) && !empty($itemList['ITEMS']))
		{
			$entityID = $itemList['ENTITY']['ID'];
			if(strpos($entityID, static::$customDealStagePrefix) === false)
			{
				$entityList = array_column(CCrmStatus::GetEntityTypes(),'ID');
				if(!in_array($entityID,$entityList))
				{
					$entityID = '';
				}
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
							continue;
						if(!empty($oldData[$item['STATUS_ID']]))
						{
							$saveData = [
								'SORT' => intVal($item['SORT']),
								'NAME' => $item['NAME']
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
									'SYSTEM' => 'N'
								]
							);
						}
					}

					if (is_array($itemList['COLOR_SETTING']))
					{
						Option::set(
							'crm',
							'CONFIG_STATUS_' . $itemList['ENTITY']['ID'],
							serialize($itemList['COLOR_SETTING'])
						);
					}

					if(!empty($oldData))
					{
						foreach ($oldData as $item)
						{
							if ($item['SYSTEM'] == 'N')
							{
								$entity->delete($item['ID']);
							}
						}
					}
				}
				//end region standard funnel
				//region custom deal funnel
				elseif(strpos($entityID, static::$customDealStagePrefix) !== false)
				{
					try
					{
						$ID = DealCategory::add(
							[
								'NAME' => $itemList['ENTITY']['NAME'],
								'SORT' => (intVal($itemList['ENTITY']['SORT']) > 0)?
									intVal($itemList['ENTITY']['SORT']):10,
							]
						);
						if($ID > 0)
						{
							$oldID = DealCategory::convertFromStatusEntityID($itemList['ENTITY']['ID']);
							$prefixStatusOld = DealCategory::prepareStageNamespaceID($oldID);
							$prefixStatus = DealCategory::prepareStageNamespaceID($ID);
							$entityID = static::$customDealStagePrefix . $ID;
							$entity = new CCrmStatus($entityID);

							$defaultStatus = array_column($entity->GetStatus($entityID), null, 'STATUS_ID');
							foreach ($itemList['ITEMS'] as $item)
							{
								$statusID = str_replace($prefixStatusOld, $prefixStatus, $item['STATUS_ID']);
								if (!empty($defaultStatus[$statusID]))
								{
									$entity->update(
										$defaultStatus[$statusID]['ID'],
										[
											'NAME' => $item['NAME'],
											'SORT' => intVal($item['SORT']),
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
											'SYSTEM' => 'N'
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

							if (is_array($itemList['COLOR_SETTING']))
							{
								$colorList = [];
								foreach ($itemList['COLOR_SETTING'] as $key=>$color)
								{
									$key = str_replace(
										$prefixStatusOld,
										$prefixStatus,
										$key
									);
									$colorList[$key] = $color;
								}

								Option::set(
									'crm',
									'CONFIG_STATUS_' . $entityID,
									serialize($colorList)
								);
							}
						}
					}
					catch (Exception $e)
					{
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
				}
				//end region dictionary
			}
		}

		return $result;
	}
	//end region status

	//region fields
	private static function exportFields($step)
	{
		$return = [
			'FILE_NAME' => '',
			'CONTENT' => [],
			'NEXT' => $step
		];
		global $USER_FIELD_MANAGER;
		$entityList = array_column(CCrmFields::GetEntityTypes(), 'ID');
		if($entityList[$step])
		{
			$return['FILE_NAME'] = $entityList[$step];
			$return['CONTENT'] = [
				'TYPE' => $entityList[$step],
				'ITEMS' => (new CCrmFields($USER_FIELD_MANAGER, $entityList[$step]))->GetFields(),
			];

			foreach ($return['CONTENT']['ITEMS'] as $key => $field)
			{
				if($field['USER_TYPE_ID'] == 'enumeration')
				{
					$return['CONTENT']['ITEMS'][$key]['LIST'] = [];
					$res = CUserFieldEnum::GetList([], ['USER_FIELD_ID' =>$field['ID']]);
					$i = 0;
					while($value = $res->fetch())
					{
						$i++;
						$return['CONTENT']['ITEMS'][$key]['LIST']['n'.$i] = $value;
					}
				}
			}
		}
		else
		{
			$return['NEXT'] = false;
		}

		return $return;
	}

	private static function clearFields($option)
	{
		$result = [
			'NEXT' => false
		];
		$step = $option['STEP'];
		$clearFull = $option['CLEAR_FULL'];
		$prefix = $option['PREFIX_NAME'];
		$pattern = '/^\('.$prefix.'\)/';

		$entityTypeList = array_column(CCrmFields::GetEntityTypes(), 'ID');
		if(isset($entityTypeList[$step]))
		{
			$result['NEXT'] = $step;
			global $USER_FIELD_MANAGER;
			$entity = new CCrmFields($USER_FIELD_MANAGER, $entityTypeList[$step]);
			$fieldList = $entity->GetFields();

			foreach ($fieldList as $field)
			{
				if($clearFull)
				{
					$entity->DeleteField($field['ID']);
				}
				else
				{
					$saveData = [
						'MANDATORY' => 'N',
						'SORT' => static::$clearSort + $field['SORT']
					];
					if ($prefix != '')
					{
						if($field['EDIT_FORM_LABEL'] != '' && preg_match($pattern, $field['EDIT_FORM_LABEL']) === 0)
						{
							$saveData['EDIT_FORM_LABEL'] = "($prefix) ".$field['EDIT_FORM_LABEL'];
						}
						if($field['LIST_COLUMN_LABEL'] != '' && preg_match($pattern, $field['LIST_COLUMN_LABEL']) === 0)
						{
							$saveData['LIST_COLUMN_LABEL'] = "($prefix) ".$field['LIST_COLUMN_LABEL'];
						}
						if($field['LIST_FILTER_LABEL'] != '' && preg_match($pattern, $field['LIST_FILTER_LABEL']) === 0)
						{
							$saveData['LIST_FILTER_LABEL'] = "($prefix) ".$field['LIST_FILTER_LABEL'];
						}
					}
					$entity->UpdateField(
						$field['ID'],
						$saveData
					);
				}
			}

			if($clearFull)
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->ClearByTag('crm_fields_list_'.$entityTypeList[$step]);
			}
		}

		return $result;
	}

	private static function importFields($import)
	{
		$result = [];
		if(!isset($import['CONTENT']['DATA']))
		{
			return $result;
		}
		$fieldList = $import['CONTENT']['DATA'];
		if(!empty($fieldList['ITEMS']))
		{
			$entityList = array_column(CCrmFields::GetEntityTypes(), 'ID');
			if(in_array($fieldList['TYPE'], $entityList))
			{
				global $USER_FIELD_MANAGER;
				$entity = new CCrmFields($USER_FIELD_MANAGER, $fieldList['TYPE']);
				$langList = array();
				$resLang = CLanguage::GetList($by = '', $order = '');
				while($lang = $resLang->Fetch())
				{
					$langList[] = $lang['LID'];
				}

				$oldFields = $entity->GetFields();
				foreach ($fieldList['ITEMS'] as $field)
				{
					$saveData = [
						'ENTITY_ID' => $fieldList['TYPE'],
						'XML_ID' => static::$context.'_'.$field['FIELD_NAME'],
						'FIELD_NAME' => $field['FIELD_NAME'],
						'SORT' => intVal($field['SORT']),
						'MULTIPLE' => $field['MULTIPLE'],
						'MANDATORY' => $field['MANDATORY'],
						'SHOW_FILTER' => $field['SHOW_FILTER'],
						'SHOW_IN_LIST' => $field['SHOW_IN_LIST'],
						'EDIT_IN_LIST' => $field['EDIT_IN_LIST'],
						'IS_SEARCHABLE' => $field['IS_SEARCHABLE'],
						'SETTINGS' => $field['SETTINGS'],
						'USER_TYPE_ID' => $field['USER_TYPE']["USER_TYPE_ID"],
						'LIST' => is_array($field['LIST'])? $field['LIST'] : []
					];

					$arLabels = ["EDIT_FORM_LABEL", "LIST_COLUMN_LABEL", "LIST_FILTER_LABEL", "ERROR_MESSAGE", "HELP_MESSAGE"];
					foreach($arLabels as $label)
					{
						foreach ($langList as $lang)
						{
							$saveData[$label][$lang] = $field[$label];
						}
					}

					if(!empty($oldFields[$saveData['FIELD_NAME']]))
					{
						if(
							$oldFields[$saveData['FIELD_NAME']]['XML_ID'] == $saveData['XML_ID']
							&&
							$oldFields[$saveData['FIELD_NAME']]['USER_TYPE']['USER_TYPE_ID'] == $saveData['USER_TYPE_ID']
						)
						{
							$entity->UpdateField($oldFields[$saveData['FIELD_NAME']]['ID'], $saveData);
						}
						else
						{
							$result['ERROR_MESSAGES'] = Loc::getMessage(
								"CRM_ERROR_CONFIGURATION_IMPORT_CONFLICT_FIELDS",
									[
										'#CODE#' => $saveData['FIELD_NAME']
									]
							);
						}
					}
					else
					{
						$entity->AddField($saveData);
					}
				}
			}
		}

		return $result;
	}
	//end region fields

	//region detail configuration
	private static function exportDetailConfigurationList()
	{
		$return = static::$entityTypeDetailConfiguration;
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

	private static function exportDetailConfiguration($step)
	{
		$keys = static::exportDetailConfigurationList();
		$typeEntity = $keys[$step]?:'';
		$return = [
			'FILE_NAME' => $typeEntity,
			'CONTENT' => [],
			'NEXT' => count($keys) > $step+1 ? $step : false
		];

		if(!empty(static::$entityTypeDetailConfiguration[$typeEntity]))
		{
			global $USER;
			$extras = [];
			$config = new EntityEditorConfig(
				static::$entityTypeDetailConfiguration[$typeEntity]['ID'],
				$USER->GetID(),
				static::$entityTypeDetailConfiguration[$typeEntity]['SCOPE'],
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
		elseif(strpos($typeEntity,'DEAL') !== false || strpos($typeEntity,'LEAD') !== false)
		{
			list($entity, $id) = explode('_', $typeEntity,2);
			if(static::$entityTypeDetailConfiguration[$entity])
			{
				global $USER;
				$id = intVal($id);
				if(strpos($typeEntity,'DEAL') !== false)
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
					static::$entityTypeDetailConfiguration[$entity]['ID'],
					$USER->GetID(),
					static::$entityTypeDetailConfiguration[$entity]['SCOPE'],
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

	private static function clearDetailConfiguration($option)
	{
		$result = [
			'NEXT' => false
		];
		$clearFull = $option['CLEAR_FULL'];
		if($clearFull)
		{
			global $USER;

			$configurationEntity = static::exportDetailConfigurationList();
			foreach ($configurationEntity as $entity)
			{
				$extras = [];
				if (strpos($entity, 'LEAD') !== false)
				{
					list($entity, $id) = explode('_', $entity, 2);
					$extras = [
						'LEAD_CUSTOMER_TYPE' => $id
					];
				}

				if(static::$entityTypeDetailConfiguration[$entity])
				{
					$id = static::$entityTypeDetailConfiguration[$entity]['ID'];
					$scope = static::$entityTypeDetailConfiguration[$entity]['SCOPE'];
				}
				else
				{
					continue;
				}

				$config = new EntityEditorConfig(
					$id,
					$USER->GetID(),
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

	private static function importDetailConfiguration($import)
	{
		$return = [];
		if(!isset($import['CONTENT']['DATA']))
		{
			return $return;
		}
		$item = $import['CONTENT']['DATA'];
		if(!$item['ENTITY'] || !$item['DATA'])
		{
			return $return;
		}
		if(static::$entityTypeDetailConfiguration[$item['ENTITY']])
		{
			global $USER;
			$extras = [];
			$config = new EntityEditorConfig(
				static::$entityTypeDetailConfiguration[$item['ENTITY']]['ID'],
				$USER->GetID(),
				static::$entityTypeDetailConfiguration[$item['ENTITY']]['SCOPE'],
				$extras
			);
			$data = $config->sanitize($item['DATA']);
			if(!empty($data))
			{
				$config->set($data);
				$config->forceCommonScopeForAll();
			}
		}
		elseif(strpos($item['ENTITY'],'DEAL') !== false || strpos($item['ENTITY'],'LEAD') !== false)
		{
			list($entity, $id) = explode('_', $item['ENTITY'],2);
			if(static::$entityTypeDetailConfiguration[$entity])
			{
				global $USER;
				$id = intVal($id);
				if(strpos($item['ENTITY'],'DEAL') !== false)
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
					static::$entityTypeDetailConfiguration[$entity]['ID'],
					$USER->GetID(),
					static::$entityTypeDetailConfiguration[$entity]['SCOPE'],
					$extras
				);
				$errors = [];
				if(!$config->check($item['DATA'], $errors))
				{
					$return['ERROR_MESSAGES'][] = $errors;
				}
				else
				{
					$data = $config->sanitize($item['DATA']);
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
			}
		}

		return $return;
	}
	//end region detail configuration
}