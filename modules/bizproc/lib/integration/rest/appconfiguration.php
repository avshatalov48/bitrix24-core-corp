<?php

namespace Bitrix\Bizproc\Integration\Rest;

use Bitrix\Bizproc\Workflow\Template\Entity\WorkflowTemplateTable;
use Bitrix\Crm\Automation\Trigger\Entity\TriggerTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Loader;
use Bitrix\Main\Event;
use Bitrix\Rest\Configuration\Helper;
use CBPDocument;
use Exception;

Loc::loadMessages(__FILE__);

class AppConfiguration
{
	const ENTITY_BIZPROC_MAIN = 'BIZPROC_MAIN';
	const ENTITY_BIZPROC_CRM_TRIGGER = 'BIZPROC_CRM_TRIGGER';

	const OWNER_ENTITY_TYPE_BIZPROC = 'BIZPROC';
	const OWNER_ENTITY_TYPE_TRIGGER = 'TRIGGER';

	private static $entityList = [
		self::ENTITY_BIZPROC_MAIN => 500,
		self::ENTITY_BIZPROC_CRM_TRIGGER => 600,
	];
	private static $customDealMatch = '/^C([0-9]+):/';
	private static $accessModules = ['crm'];
	private static $context;
	private static $accessManifest = [
		'total',
		'bizproc_crm'
	];

	public static function getEntityList()
	{
		return static::$entityList;
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
					case self::ENTITY_BIZPROC_MAIN:
						$result = static::exportBizproc($step);
						break;
					case self::ENTITY_BIZPROC_CRM_TRIGGER:
						$result = static::exportCrmTrigger($step);
						break;
				}
			}
		}
		catch (Exception $e)
		{
			$result['NEXT'] = false;
			$result['ERROR_ACTION'] = $e->getMessage();
			$result['ERROR_MESSAGES'] = Loc::getMessage(
				'BIZPROC_ERROR_CONFIGURATION_EXPORT_EXCEPTION',
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
		$option = $event->getParameters();
		if (!Helper::checkAccessManifest($option, static::$accessManifest))
		{
			return null;
		}

		$result = null;

		try
		{
			if(static::checkRequiredParams($code))
			{
				switch ($code)
				{
					case self::ENTITY_BIZPROC_MAIN:
						$result = static::clearBizproc($option);
						break;
					case self::ENTITY_BIZPROC_CRM_TRIGGER:
						$result = static::clearCrmTrigger($option);
						break;
				}
			}
		}
		catch (Exception $e)
		{
			$result['NEXT'] = false;
			$result['ERROR_ACTION'] = $e->getMessage();
			$result['ERROR_MESSAGES'] = Loc::getMessage(
				'BIZPROC_ERROR_CONFIGURATION_CLEAR_EXCEPTION',
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
		$data = $event->getParameters();
		if (!Helper::checkAccessManifest($data, static::$accessManifest))
		{
			return null;
		}

		$result = null;

		try
		{
			if(static::checkRequiredParams($code))
			{
				switch ($code)
				{
					case self::ENTITY_BIZPROC_MAIN:
						$result = static::importBizproc($data);
						break;
					case self::ENTITY_BIZPROC_CRM_TRIGGER:
						$result = static::importCrmTrigger($data);
						break;
				}
			}
		}
		catch (Exception $e)
		{
			$result['NEXT'] = false;
			$result['ERROR_ACTION'] = $e->getMessage();
			$result['ERROR_MESSAGES'] = Loc::getMessage(
				'BIZPROC_ERROR_CONFIGURATION_IMPORT_EXCEPTION',
				[
					'#CODE#' => $code
				]
			);
		}

		return $result;
	}

	/**
	 * @param $type string of event
	 * @throws SystemException
	 * @return boolean
	 */
	private static function checkRequiredParams($type)
	{
		$return = true;
		if($type == self::ENTITY_BIZPROC_MAIN)
		{
			if(
				!class_exists('\Bitrix\Bizproc\Workflow\Template\Packer\Bpt')
				|| !method_exists('Bitrix\Bizproc\Workflow\Template\Packer\Bpt', 'makePackageData')
			)
			{
				throw new SystemException('not available bizproc');
			}
		}
		elseif($type == self::ENTITY_BIZPROC_CRM_TRIGGER)
		{
			if(!Loader::IncludeModule('crm'))
			{
				throw new SystemException('need install module: crm');
			}
		}

		return $return;
	}

	//region bizproc
	private static function exportBizproc($step)
	{
		$result = [
			'FILE_NAME' => '',
			'CONTENT' => [],
			'NEXT' => false
		];
		$res = WorkflowTemplateTable::getList(
			[
				'order' => [
					'ID' => 'ASC'
				],
				'filter' => [
					'=MODULE_ID' => static::$accessModules
				],
				'limit' => 1,
				'offset' => $step
			]
		);
		if($tpl = $res->fetchObject())
		{
			$result['NEXT'] = $step;
			if(in_array($tpl->getModuleId(), static::$accessModules))
			{
				$result['FILE_NAME'] = $step;
				$packer = new \Bitrix\Bizproc\Workflow\Template\Packer\Bpt();
				$data =  $packer->makePackageData($tpl);
				$result['CONTENT'] = [
					'ID' => $tpl->getId(),
					'MODULE_ID' => $tpl->getModuleId(),
					'ENTITY' => $tpl->getEntity(),
					'DOCUMENT_TYPE' => $tpl->getDocumentType(),
					'DOCUMENT_STATUS' => $tpl->getDocumentStatus(),
					'NAME' => $tpl->getName(),
					'AUTO_EXECUTE' => $tpl->getAutoExecute(),
					'DESCRIPTION' => $tpl->getDescription(),
					'SYSTEM_CODE' => $tpl->getSystemCode(),
					'ORIGINATOR_ID' => $tpl->getOriginatorId(),
					'ORIGIN_ID' => $tpl->getOriginId(),
					'TEMPLATE_DATA' => $data
				];
			}
		}

		return $result;
	}

	private static function clearBizproc($option)
	{
		$result = [
			'NEXT' => false,
			'OWNER_DELETE' => []
		];
		$clearFull = $option['CLEAR_FULL'];
		$prefix = $option['PREFIX_NAME'];
		$pattern = '/^\('.$prefix.'\)/';

		$res = WorkflowTemplateTable::getList(
			[
				'order' => [
					'ID' => 'ASC'
				],
				'filter' => [
					'=MODULE_ID' => static::$accessModules,
					'>ID' => $option['NEXT']
				],
				'select' => ['*']
			]
		);
		$errorsTmp = [];
		while($item = $res->Fetch())
		{
			$result['NEXT'] = $item['ID'];

			if(!$clearFull && $item['DOCUMENT_TYPE'] == 'DEAL')
			{
				//dont off old custom deal robot
				$matches = [];
				preg_match(static::$customDealMatch, $item['DOCUMENT_STATUS'], $matches, PREG_OFFSET_CAPTURE);
				if(!empty($matches))
				{
					continue;
				}
			}

			if($clearFull || !empty($item['DOCUMENT_STATUS']))
			{
				CBPDocument::DeleteWorkflowTemplate(
					$item['ID'],
					[
						$item['MODULE_ID'],
						$item['ENTITY'],
						$item['DOCUMENT_TYPE']
					],
					$errorsTmp
				);
				if(!$errorsTmp)
				{
					$result['OWNER_DELETE'][] = [
						'ENTITY_TYPE' => self::OWNER_ENTITY_TYPE_BIZPROC,
						'ENTITY' => $item['ID']
					];
				}
			}
			else
			{
				$name = $item['NAME'];
				if($prefix != '' && preg_match($pattern, $name) === 0)
				{
					$name = "($prefix) ".$name;
				}
				CBPDocument::UpdateWorkflowTemplate(
					$item['ID'],
					[
						$item['MODULE_ID'],
						$item['ENTITY'],
						$item['DOCUMENT_TYPE']
					],
					[
						'ACTIVE' => 'N',
						'AUTO_EXECUTE' => \CBPDocumentEventType::None,
						'NAME' => $name
					],
					$errorsTmp
				);
			}
		}

		return $result;
	}

	private static function importBizproc($importData)
	{
		$result = [];

		if (!isset($importData['CONTENT']['DATA']))
		{
			return false;
		}
		$item = $importData['CONTENT']['DATA'];
		if(
			in_array($item['MODULE_ID'], static::$accessModules)
			&& Loader::includeModule($item['MODULE_ID'])
			&& class_exists($item['ENTITY'])
		)
		{
			if (is_subclass_of($item['ENTITY'], '\\IBPWorkflowDocument'))
			{

				if(isset($importData['RATIO']['CRM_STATUS']))
				{
					if(is_array($item['TEMPLATE_DATA']))
					{
						$item['TEMPLATE_DATA'] = static::changeDealCategory($item['TEMPLATE_DATA'], $importData['RATIO']['CRM_STATUS']);
					}
					if($item['DOCUMENT_TYPE'] == 'DEAL' && $item['DOCUMENT_STATUS'])
					{
						$item['DOCUMENT_STATUS'] = static::changeDealCategory($item['DOCUMENT_STATUS'], $importData['RATIO']['CRM_STATUS']);
					}
				}

				try
				{
					$code = static::$context.'_xml_'.intval($item['ID']);
					$id = \CBPWorkflowTemplateLoader::importTemplateFromArray(
						0,
						[
							$item['MODULE_ID'],
							$item['ENTITY'],
							$item['DOCUMENT_TYPE']
						],
						$item['AUTO_EXECUTE'],
						$item['NAME'],
						isset($item['DESCRIPTION']) ? (string) $item['DESCRIPTION'] : '',
						$item['TEMPLATE_DATA'],
						$code
					);

					if ($id > 0)
					{
						$result['OWNER'] = [
							'ENTITY_TYPE' => self::OWNER_ENTITY_TYPE_BIZPROC,
							'ENTITY' => $id
						];

						if ($item['DOCUMENT_STATUS'])
						{
							\CBPWorkflowTemplateLoader::update(
								$id,
								[
									'DOCUMENT_STATUS' => $item['DOCUMENT_STATUS'],
								]
							);
						}
					}
				}
				catch (\Exception $e)
				{
					$result['ERROR_ACTION'] = $e->getMessage();
					$result['ERROR_MESSAGES'] = Loc::getMessage(
						'BIZPROC_ERROR_CONFIGURATION_IMPORT_EXCEPTION_BP'
					);
				}
			}
		}

		return $result;
	}
	//end region bizproc

	//region trigger
	private static function exportCrmTrigger($step)
	{
		$result = [
			'FILE_NAME' => '',
			'CONTENT' => [],
			'NEXT' => false
		];

		$res = TriggerTable::getList(
			[
				'order' => [
					'ID' => 'ASC'
				],
				'filter' => [],
				'select' => ['*'],
				'limit' => 1,
				'offset' => $step
			]
		);
		if($item = $res->Fetch())
		{
			$result['FILE_NAME'] = $step;
			$result['CONTENT'] = $item;
			$result['NEXT'] = $step;
		}

		return $result;
	}

	private static function clearCrmTrigger($option)
	{
		$result = [
			'NEXT' => false
		];
		$clearFull = $option['CLEAR_FULL'];

		$res = TriggerTable::getList(
			[
				'order' => [
					'ID' => 'ASC'
				],
				'filter' => [
					'>ID' => $option['NEXT']
				],
				'limit' => 10
			]
		);
		while ($item = $res->Fetch())
		{
			$result['NEXT'] = $item['ID'];
			if (!$clearFull && $item['ENTITY_TYPE_ID'] == \CCrmOwnerType::Deal)
			{
				//dont off old custom deal trigger
				$matches = [];
				preg_match(static::$customDealMatch, $item['ENTITY_STATUS'], $matches, PREG_OFFSET_CAPTURE);
				if (!empty($matches))
				{
					continue;
				}
			}
			$delete = TriggerTable::delete($item['ID']);
			if($delete->isSuccess())
			{
				$result['OWNER_DELETE'][] = [
					'ENTITY_TYPE' => self::OWNER_ENTITY_TYPE_TRIGGER,
					'ENTITY' => $item['ID']
				];
			}
		}

		return $result;
	}

	private static function importCrmTrigger($importData)
	{
		$result = [];
		if (!isset($importData['CONTENT']['DATA']))
		{
			return false;
		}
		$item = $importData['CONTENT']['DATA'];

		if(
			isset($item['NAME'])
			&& isset($item['CODE'])
			&& isset($item['ENTITY_TYPE_ID'])
			&& isset($item['ENTITY_STATUS'])
		)
		{
			if(isset($importData['RATIO']['CRM_STATUS']))
			{
				if (is_array($item['APPLY_RULES']))
				{
					$item['APPLY_RULES'] = static::changeDealCategory(
						$item['APPLY_RULES'],
						$importData['RATIO']['CRM_STATUS']
					);
				}
				if ($item['ENTITY_TYPE_ID'] == \CCrmOwnerType::Deal)
				{
					$item['ENTITY_STATUS'] = static::changeDealCategory(
						$item['ENTITY_STATUS'],
						$importData['RATIO']['CRM_STATUS']
					);
				}
			}

			$saveData = [
				'NAME' => $item['NAME'],
				'CODE' => $item['CODE'],
				'ENTITY_TYPE_ID' => $item['ENTITY_TYPE_ID'],
				'ENTITY_STATUS' => $item['ENTITY_STATUS'],
				'APPLY_RULES' => is_array($item['APPLY_RULES']) ? $item['APPLY_RULES'] : null
			];

			$res = TriggerTable::add($saveData);
			if($res->isSuccess())
			{
				$result['OWNER'] = [
					'ENTITY_TYPE' => self::OWNER_ENTITY_TYPE_TRIGGER,
					'ENTITY' => $res->getId()
				];
			}
		}

		return $result;
	}
	//end region trigger

	private static function changeDealCategory($data, $ratio)
	{
		if(!empty($ratio))
		{
			$ratioRegEx = [];
			$ratioReplace = [];
			foreach ($ratio as $oldId => $newId)
			{
				$ratioRegEx[] = '/^C'.$oldId.':/';
				$ratioReplace[] = 'C'.$newId.':';
			}
			if(!empty($ratioRegEx))
			{
				$data = static::changeDealCategoryAction($data, $ratioRegEx, $ratioReplace, $ratio);
			}
		}

		return $data;
	}

	private static function changeDealCategoryAction($data, $ratioRegEx, $ratioReplace, $ratio)
	{
		if (is_string($data))
		{
			$data = preg_replace($ratioRegEx, $ratioReplace, $data);
		}
		elseif (is_array($data))
		{
			if (
				isset($data['field'])
				&& $data['field'] == 'CATEGORY_ID'
				&& $data['value'] > 0
				&& $ratio[$data['value']] > 0
			)
			{
				$data['value'] = $ratio[$data['value']];
			}

			foreach ($data as $key => $value)
			{
				$newKey = static::changeDealCategoryAction($key, $ratioRegEx, $ratioReplace, $ratio);
				if ($newKey != $key)
				{
					unset($data[$key]);
				}

				if ($newKey == 'CATEGORY_ID')
				{
					if (is_array($value))
					{
						if (isset($value['Options']) && is_array($value['Options']))
						{
							$data[$newKey]['Options'] = [];
							foreach ($value['Options'] as $dealId => $title)
							{
								if (isset($ratio[$dealId]))
								{
									$data[$newKey]['Options'][$ratio[$dealId]] = $title;
								}
							}
						}
						else
						{
							$data[$newKey] = static::changeDealCategoryAction(
								$value,
								$ratioRegEx,
								$ratioReplace,
								$ratio
							);
						}
					}
					elseif (is_string($value) && isset($ratio[$value]))
					{
						$data[$newKey] = $ratio[$value];
					}
					else
					{
						$data[$newKey] = static::changeDealCategoryAction(
							$value,
							$ratioRegEx,
							$ratioReplace,
							$ratio
						);
					}
				}
				elseif($newKey == 'CategoryId' && intVal($value) > 0 && !empty($ratio[$value]))
				{
					$data[$newKey] = $ratio[$value];
				}
				else
				{
					$data[$newKey] = static::changeDealCategoryAction(
						$value,
						$ratioRegEx,
						$ratioReplace,
						$ratio
					);
				}
			}
		}

		return $data;
	}
}