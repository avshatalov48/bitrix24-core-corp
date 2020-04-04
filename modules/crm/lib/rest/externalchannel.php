<?php

namespace Bitrix\Crm\Rest;
use Bitrix\Crm;
use Bitrix\Crm\Activity\Provider;
use Bitrix\Disk\File;
use Bitrix\Faceid\FaceTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration\Channel\ExternalTracker;
use Bitrix\Crm\Integration\Channel\ChannelType;
use Bitrix\Main\Result;

Loc::loadMessages(__FILE__);

class CCrmExternalChannelImportActivity extends \CCrmExternalChannelRestProxy
{
	private static $ENTITY = null;
	protected $class = null;
	protected $ownerId = -1;
	public $import = null;
	protected $activityType = CCrmExternalChannelActivityType::Undefined;

	public static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new \CCrmActivityRestProxy();
		}

		return self::$ENTITY;
	}

	public function setOwnerEntity($class)
	{
		$this->class = $class;
	}

	/**
	 * @return \CCrmCompanyRestProxy|\CCrmContactRestProxy
	 */
	protected function getOwnerEntity()
	{
		return $this->class;
	}

	public function setOwnerEntityId($id)
	{
		$this->ownerId = (int)$id;
	}

	protected function getOwnerEntityId()
	{
		return $this->ownerId;
	}

	public function setTypeActivity($type)
	{
		$this->activityType = $type;
	}

	protected function getTypeActivity()
	{
		return $this->activityType;
	}

	/**
	 * @param $fields
	 * @return Result
	 */
	protected function checkFields(&$fields)
	{
		$result = new Result();

		if(!is_set($fields, 'SUBJECT') || !is_string($fields['SUBJECT']))
		{
			$result->addError(new Error("SUBJECT is not defined or is invalid", 7001));
		}

		if(!is_set($fields, 'DESCRIPTION') || !is_string($fields['DESCRIPTION']))
		{
			$result->addError(new Error("DESCRIPTION is not defined or is invalid",7002));
		}

		if(!is_set($fields, 'RESULT_VALUE') || !is_numeric($fields['RESULT_VALUE']))
		{
			$result->addError(new Error("RESULT_VALUE is not defined or is invalid",7003));
		}

		if(!is_set($fields, 'RESULT_SUM') || $fields['RESULT_SUM']=='')
		{
			$result->addError(new Error("RESULT_SUM is not defined",7004));
		}

		if(!is_set($fields, 'RESULT_CURRENCY_ID') || $fields['RESULT_CURRENCY_ID']=='' || !\CCrmCurrency::IsExists($fields['RESULT_CURRENCY_ID']))
		{
			$result->addError(new Error("RESULT_CURRENCY_ID not defined or is invalid",7005));
		}

		if(!is_set($fields, 'START_TIME') || !is_string(\CRestUtil::unConvertDateTime($fields['START_TIME'])))
		{
			$result->addError(new Error("START_TIME is not defined or is invalid",7006));
		}

		if(!is_set($fields, 'ORIGIN_ID') || $fields['ORIGIN_ID']=='')
		{
			$result->addError(new Error("ORIGIN_ID is not defined",7007));
		}

		return $result;
	}

	public function fillEmptyFields(&$fields, $params=array())
	{
		$ownerEntity = $this->getOwnerEntity();

		if($ownerEntity->getOwnerTypeID() ===  \CCrmOwnerType::Company)
		{
			$title = is_set($params[CCrmExternalChannelImport::FIELDS], 'TITLE')? $params[CCrmExternalChannelImport::FIELDS]['TITLE']:'';

			$fields['SUBJECT'] =  Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_IMPORT_COMPANY_ACTIVITY_SUBJECT')." ".$title;
		}
		elseif($ownerEntity->getOwnerTypeID() ===  \CCrmOwnerType::Contact)
		{
			$name[] = is_set($params[CCrmExternalChannelImport::FIELDS], 'LAST_NAME')? $params[CCrmExternalChannelImport::FIELDS]['LAST_NAME']:'';
			$name[] = is_set($params[CCrmExternalChannelImport::FIELDS], 'NAME')? $params[CCrmExternalChannelImport::FIELDS]['NAME']:'';
			$name[] = is_set($params[CCrmExternalChannelImport::FIELDS], 'SECOND_NAME')? $params[CCrmExternalChannelImport::FIELDS]['SECOND_NAME']:'';

			$fields['SUBJECT'] = Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_IMPORT_CONTACT_ACTIVITY_SUBJECT')." ".implode(' ', $name);
		}

		$fields['START_TIME'] = ConvertTimeStamp((time() + \CTimeZone::GetOffset()), 'FULL', SITE_ID);
	}

	/**
	 * @return array
	 */
	protected function prepareFieldsProviderParams()
	{
		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;
		$fields = $import->getRawData();

		if(isset($fields[CCrmExternalChannelImport::ACTIVITY][CCrmExternalChannelImport::EXTERNAL_FIELDS]) &&
			isset($fields[CCrmExternalChannelImport::ACTIVITY][CCrmExternalChannelImport::EXTERNAL_FIELDS]['FACE_SNAPSHOT']))
		{
			unset($fields[CCrmExternalChannelImport::ACTIVITY][CCrmExternalChannelImport::EXTERNAL_FIELDS]['FACE_SNAPSHOT']);
		}

		return $fields;
	}

	/**
	 * @return string
	 */
	protected function getShapshotName()
	{
		return Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_IMPORT_ACTIVITI_SNAPSHOT_NAME', array('#POSTFIX#'=>ConvertTimeStamp((time() + \CTimeZone::GetOffset()), 'FULL', SITE_ID))).'.jpg';
	}

	/**
	 * @return array
	 */
	protected function internalizeFileFaceSnapshot()
	{
		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;
		$fields = $import->getRawData();
		$files['FILES'] = array();

		if(isset($fields[CCrmExternalChannelImport::ACTIVITY][CCrmExternalChannelImport::EXTERNAL_FIELDS]) &&
			isset($fields[CCrmExternalChannelImport::ACTIVITY][CCrmExternalChannelImport::EXTERNAL_FIELDS]['FACE_SNAPSHOT']))
		{
			$binaryImageContent = $fields[CCrmExternalChannelImport::ACTIVITY][CCrmExternalChannelImport::EXTERNAL_FIELDS]['FACE_SNAPSHOT'];

			$files['FILES'] = array('fileData'=>array($this->getShapshotName(), $binaryImageContent));

			$this->tryInternalizeDiskFileField($files, 'FILES');
		}

		return count($files['FILES'])>0 ? $files['FILES']:array();
	}

	public function fillFields(&$fields, $params=array())
	{
		$ownerEntity = $this->getOwnerEntity();

		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		/** @var CCrmExternalChannelConnector $connector */
		$connector = $import->getConnector();

		$curUserId = \CCrmSecurityHelper::GetCurrentUserID();

		$fields['DIRECTION'] = \CCrmActivityDirection::Incoming;
		$fields['COMPLETED'] = 'Y';
		$fields['START_TIME'] = \CRestUtil::unConvertDateTime($fields['START_TIME']);
		$fields['RESPONSIBLE_ID'] = $curUserId;
		$fields['AUTHOR_ID'] = $curUserId;
		$fields['PROVIDER_ID'] = Provider\ExternalChannel::PROVIDER_ID;
		$fields['PROVIDER_TYPE_ID'] = $this->getTypeActivity();
		$fields['PROVIDER_GROUP_ID'] = $connector->getTypeId();
		$fields['OWNER_ID'] = $this->getOwnerEntityId();
		$fields['OWNER_TYPE_ID'] = $ownerEntity->getOwnerTypeID();
		$fields['PROVIDER_PARAMS'] = $this->prepareFieldsProviderParams();
		$fields['ORIGINATOR_ID'] = $connector->getOriginatorId();

		if($this->getTypeActivity() == CCrmExternalChannelActivityType::ActivityFaceCardName)
		{
			$activityType = CCrmExternalChannelActivityType::getAllDescriptions();

			$fields['SUBJECT'] = $activityType[CCrmExternalChannelActivityType::ActivityFaceCard];
			$fields['FILES'][] = $this->internalizeFileFaceSnapshot();
		}

		$this->fields = $fields;
	}

	protected function innerAdd($activity, &$resultList)
	{
		$error = array();
		$resultList = array(
			'id'=> -1,
			'process' => array(
				'add' => false,
				'error' => array()
			)
		);

		if(($fields = $activity[self::FIELDS]) && count($fields)>0)
		{
			$errors = array();
			$this->checkFields($fields, $errors);
			if(count($errors)>0)
				$error[] = implode('; ', $errors);

			if(count($error)<=0)
			{
				$errors = array();

				$this->getEntity()->internalizeFields($fields, $this->getEntity()->getFieldsInfo());

				$this->fillFields($fields, $activity);

				$id = $this->getEntity()->innerAdd($fields, $errors);
				if($this->isValidID($id))
				{
					$resultList['id'] = $id;
					$resultList['process']['add'] = true;
				}

				if(count($errors)>0)
					$error[] = implode('; ', $errors);
			}
		}
		else
			$error[] = "Activity fields is not defined.";

		if(count($error)>0)
			$resultList['process']['error'] = $error;
	}

	/**
	 * @param $entityFields
	 * @param array $errors
	 * @return bool
	 * @internal
	 */
	public function onAfterEntityModify($entityFields, &$errors=array())
	{
		$ownerEntity = $this->getOwnerEntity();

		if($ownerEntity->getOwnerTypeID() <> \CCrmOwnerType::Contact)
		{
			return false;
		}

		if(is_array($entityFields['FILES'][0]) && intval($entityFields['FILES'][0]['FILE_ID'])>0)
		{
			$file = File::getById($entityFields['FILES'][0]['FILE_ID']);
			$fileId = $file->getFileId();

			$dbRes = \CCrmContact::GetListEx(
				array(),
				array('=ID' => $this->getOwnerEntityId()),
				false,
				false,
				array(),
				array()
			);

			$result = $dbRes ? $dbRes->Fetch() : null;
			if(is_array($result))
			{
				if(empty($result['PHOTO']))
				{
					$fields = array();
					$agent = new CCrmExternalChannelImportAgent();
					$agent->internalizeFileFieldPhoto($fields, $fileId);

					$errors = array();
					if(count($fields)>0)
					{
						$ownerEntity->innerUpdate($this->getOwnerEntityId(), $fields, $errors);
					}
				}
			}
		}

		return true;
	}

	public function import($activity, &$resultList, &$fields)
	{
		$result = new Result();

		$resultList = array(
			'id'=> -1,
			'process' => array(
				'add' => false,
				'upd' => false,
				'error' => array()
			)
		);

		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		/** @var CCrmExternalChannelConnector $connector */
		$connector = $import->getConnector();

		if(($fields = $activity[CCrmExternalChannelImport::FIELDS]) && count($fields)>0)
		{
			$r = $this->checkFields($fields);

			if($r->getErrors())
				$result->addErrors($r->getErrors());
			else
			{
				$res = $this->getEntity()->innerGetList(
					array(),
					array(
						'ORIGIN_ID'=>$fields['ORIGIN_ID'],
						'ORIGINATOR_ID' => $connector->getOriginatorId()
					),
					array('*'),
					false,
					$errors
				);
				if(!$res)
				{
					if(count($errors)>0)
						$result->addError(new Error(implode('; ', $errors), 7008));
				}
				else
				{
					$errors = array();

					$this->getEntity()->internalizeFields($fields, $this->getEntity()->getFieldsInfo());

					$this->fillFields($fields, $activity);

					if($r = $res->Fetch())
					{
						$resultUpdate = $this->getEntity()->innerUpdate($r['ID'], $fields, $errors);
						if($resultUpdate !== false)
						{
							$resultList['id'] = (int)$r['ID'];
							$resultList['process']['upd'] = true;
						}

						if(count($errors)>0)
							$result->addError(new Error(implode('; ', $errors), 7009));
					}
					else
					{
						$id = $this->getEntity()->innerAdd($fields, $errors);
						if($this->isValidID($id))
						{
							$this->registerActivityInChannel($id, $connector);

							$resultList['id'] = $id;
							$resultList['process']['add'] = true;
						}

						if(count($errors)>0)
							$result->addError(new Error(implode('; ', $errors), 7010));
					}
				}
			}
		}
		else
			$result->addError(new Error("Activity fields is not defined.", 7011)) ;

		return $result;
	}

	/**
	 * @param $id
	 * @param CCrmExternalChannelConnector $connector
	 */
	public function registerActivityInChannel($id, CCrmExternalChannelConnector $connector)
	{
		$instanceExternalTracker = '';
		switch($connector->getTypeId())
		{
			case CCrmExternalChannelType::CustomName:
				$instanceExternalTracker = ExternalTracker::getInstance(ChannelType::EXTERNAL_CUSTOM);
				break;
			case CCrmExternalChannelType::BitrixName:
				$instanceExternalTracker = ExternalTracker::getInstance(ChannelType::EXTERNAL_BITRIX);
				break;
			case CCrmExternalChannelType::OneCName:
				$instanceExternalTracker = ExternalTracker::getInstance(ChannelType::EXTERNAL_ONE_C);
				break;
			case CCrmExternalChannelType::WordpressName:
				$instanceExternalTracker = ExternalTracker::getInstance(ChannelType::EXTERNAL_WORDPRESS);
				break;
			case CCrmExternalChannelType::DrupalName:
				$instanceExternalTracker = ExternalTracker::getInstance(ChannelType::EXTERNAL_DRUPAL);
				break;
			case CCrmExternalChannelType::JoomlaName:
				$instanceExternalTracker = ExternalTracker::getInstance(ChannelType::EXTERNAL_JOOMLA);
				break;
			case CCrmExternalChannelType::MagentoName:
				$instanceExternalTracker = ExternalTracker::getInstance(ChannelType::EXTERNAL_MAGENTO);
				break;
		}

		if($instanceExternalTracker instanceof Crm\Integration\Channel\ExternalTracker)
		{
			$typeId = $this->getTypeActivity();
			$originatorId = $connector->getOriginatorId();
			$instanceExternalTracker->registerActivity($id, array('ORIGIN_ID' => $originatorId, 'COMPONENT_ID' => $typeId));
		}
	}
}

class CCrmExternalChannelImportAgent extends \CCrmExternalChannelRestProxy
{
	const UPDATE_MODE_NONE = 0;
	const UPDATE_MODE_MERGE = 1;
	const CUSTOM_FIELDS = 'CUSTOM';

	public $import = null;
	protected $entityId = -1;
	protected $updateEntityMode = self::UPDATE_MODE_NONE;
	private static $ENTITY = null;

	/**
	 * @return int
	 */
	public function getUpdateEntityMode()
	{
		return $this->updateEntityMode;
	}

	/**
	 * @param $mode
	 * @throws ArgumentException
	 */
	public function setUpdateEntityMode($mode)
	{
		if (!in_array($mode, array(self::UPDATE_MODE_NONE, self::UPDATE_MODE_MERGE)))
		{
			throw new ArgumentException("Mode {$mode} not implemented.");
		}

		$this->updateEntityMode = $mode;
	}

	protected static function getEntity()
	{
		return self::$ENTITY;
	}

	public static function setEntity($class)
	{
		self::$ENTITY = $class;
	}

	private function getCustomFieldsFieldName()
	{
		return self::CUSTOM_FIELDS;
	}

	protected function sanitizeFields(&$fields)
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $entity */
		$entity = $this->getEntity();

		$originFields = $fields;
		$fieldsInfo = $entity->getFieldsInfo();
		if(is_array($fieldsInfo) && count($fieldsInfo)>0)
		{
			$sanitize = array();
			foreach($fieldsInfo as $fieldName => $fieldEntity)
			{
				$sanitize[$fieldName] = is_set($fields, $fieldName) ? $fields[$fieldName]:'';
			}

			$custom =  array_diff_assoc($originFields, $sanitize);
			if(!empty($custom))
			{
				$sanitize[$this->getCustomFieldsFieldName()] = $custom;
			}

			$fields[$this->getCustomFieldsFieldName()] = $sanitize[$this->getCustomFieldsFieldName()];
		}
	}

	protected static function getNameUserFieldExternalUrl()
	{
		return 'UF_CRM_EXTERNAL_URL';
	}

	/**
	 * @param $agent
	 * @return Result
	 */
	protected function convertExternalFieldsToFields(&$agent)
	{
		$result = new Result();

		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $entity */
		$entity = $this->getEntity();

		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		/** @var CCrmExternalChannelConnector $connector */
		$connector = $import->getConnector();

		$company = new \CCrmCompanyRestProxy();

		if(is_set($agent, CCrmExternalChannelImport::EXTERNAL_FIELDS))
		{
			$externalFields = $agent[CCrmExternalChannelImport::EXTERNAL_FIELDS];

			if($entity->getOwnerTypeID() ===  \CCrmOwnerType::Contact && is_set($externalFields, 'COMPANY_ORIGIN_ID'))
			{
				if($this->isValidOriginId($externalFields['COMPANY_ORIGIN_ID']))
				{
					$res = $company->innerGetList(
						array(),
						array(
							'ORIGIN_ID' => $externalFields['COMPANY_ORIGIN_ID'],
							'ORIGINATOR_ID' => $connector->getOriginatorId()
						),
						array('ID'),
						false,
						$error
					);

					if(!$res)
					{
						$result->addError(new Error(implode("\n", $error), 34004));
					}
					else
					{
						if ($r = $res->Fetch())
							$agent[CCrmExternalChannelImport::FIELDS]['COMPANY_ID'] = $r['ID'];
						else
							$result->addError(new Error("Company not found. Field COMPANY_ORIGIN_ID - '".$externalFields['COMPANY_ORIGIN_ID']."' is invalid", 34005));
					}
				}
				else
					$result->addError(new Error("Field COMPANY_ORIGIN_ID is empty", 34006));
			}
			if(is_set($externalFields, 'EXTERNAL_URL') && strlen($externalFields['EXTERNAL_URL'])>0)
			{
				$r = $this->prepareUserField(self::getNameUserFieldExternalUrl());
				$resUF = $r->getData();
				$userFieldsFieldName = $resUF['RESULT'];
				if($r->getErrors())
					$result->addErrors($r->getErrors());
				else
				{
					$agent[CCrmExternalChannelImport::FIELDS][$userFieldsFieldName] = $externalFields['EXTERNAL_URL'];
				}
			}
		}

		return $result;
	}

	/**
	 * @param $ufName
	 * @return Result
	 */
	protected function prepareUserField($ufName)
	{
		$result = new Result();

		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $entity */
		$entity = $this->getEntity();

		$ownerTypeID = $entity->getOwnerTypeID();
		$ufProxy = new \CCrmUserFieldRestProxy($ownerTypeID);
		$res = $ufProxy->getList(array(), array('FIELD_NAME'=>$ufName));
		if($res['total']>0)
		{
			$ufFields = $res[0];
			$id = $ufFields['ID'];
		}
		else
		{
			$ufFields['USER_TYPE_ID'] = 'url';
			$ufFields['FIELD_NAME'] = $ufName;

			$langDbResult = \CLanguage::GetList($by = '', $order = '');
			while($lang = $langDbResult->Fetch())
			{
				$lid = $lang['LID'];
				$ufFields['EDIT_FORM_LABEL'][$lid] = $ufFields['LIST_COLUMN_LABEL'][$lid] = $ufFields['LIST_FILTER_LABEL'][$lid] = Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_IMPORT_UF_EXTERNAL_URL');
			}

			$id = $ufProxy->add($ufFields);
		}

		$fieldName = '';
		if($this->isValidID((int)$id))
		{
			$fieldName = $ufFields['FIELD_NAME'];
		}
		else
		{
			$result->addError(new Error($ufName.' not created ', 34007));
		}

		$result->setData(array('RESULT'=>$fieldName)) ;

		return $result;
	}

	protected function prepareMultiFields($entityId, &$fields, $option = array())
	{
		$fmDeleteListFieldId = array();

		$fmResult = $this->innerGetListFieldsMulti($entityId);

		while($fm = $fmResult->Fetch())
		{
			$fmTypeID = $fm['TYPE_ID'];

			$fmDeleteListFieldId[$fmTypeID][] = $fm['ID'];

			if(is_set($fields, $fmTypeID))
			{
				foreach($fields[$fmTypeID] as &$fieldsType)
				{
					$valueType = isset($fieldsType['VALUE_TYPE']) ? trim($fieldsType['VALUE_TYPE']) : '';
					if($valueType === '')
						$fieldsType['VALUE_TYPE'] = \CCrmFieldMulti::GetDefaultValueType($fmTypeID);

					if($fieldsType['VALUE_TYPE'] ==  $fm['VALUE_TYPE']
						&& $fieldsType['VALUE'] == $fm['VALUE']
						&& !is_set($fieldsType, 'ID')
					)
					{
						$fieldsType['ID'] = $fm['ID'];

						$key = array_search($fm['ID'], $fmDeleteListFieldId[$fmTypeID]);
						if($key!==false && $key!==null)
						{
							unset($fmDeleteListFieldId[$fmTypeID][$key]);
						}
					}
				}
				unset($fieldsType);
			}
		}

		if($this->getUpdateEntityMode() == self::UPDATE_MODE_NONE)
		{
			if(count($fmDeleteListFieldId)>0)
			{
				foreach($fmDeleteListFieldId as $typeId => $listId)
				{
					if(count($listId) > 0)
					{
						foreach($listId as $id)
							$fields[$typeId][] = array('ID'=>$id, 'DELETE'=>'Y');
					}
				}
			}
		}
	}

	protected function innerGetListFieldsMulti($entityId)
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $entity */

		$entity = $this->getEntity();

		return \CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => \CCrmOwnerType::ResolveName($entity->getOwnerTypeID()),
				'ELEMENT_ID' => $entityId
			)
		);
	}

	protected function isValidOriginId($OriginId)
	{
		return $OriginId !== '';
	}

	/**
	 * @param $fields
	 * @return \Bitrix\Main\Result
	 */
	public function checkFields(&$fields)
	{
		$result = new \Bitrix\Main\Result();

		if(!is_set($fields,'ORIGIN_ID') || $fields['ORIGIN_ID']=='')
		{
			$result->addError(new Error("ORIGIN_ID is not defined", 34001));
		}

		if(!is_set($fields,'ORIGIN_VERSION') || $fields['ORIGIN_VERSION']=='')
		{
			$result->addError(new Error("VERSION is not defined", 34002));
		}

		return $result;
	}

	public function checkExternalFields(&$fields, &$errors) {}

	/**
	 * @param $fields
	 * @param array $entityFields
	 */
	protected function fillFields(&$fields, $entityFields=array())
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $entity */
		$entity = $this->getEntity();

		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		/** @var CCrmExternalChannelConnector $connector */
		$connector = $import->getConnector();

		$fields['ORIGINATOR_ID'] = $connector->getOriginatorId();

		if($entity->getOwnerTypeID() == \CCrmOwnerType::Contact)
		{
			if($this->getUpdateEntityMode() == self::UPDATE_MODE_MERGE)
			{
				$entityObject = new \CCrmContact(false);

				if(intval($entityFields['ID'])>0)
				{
					$merger = new Crm\Merger\ContactMerger(0, false);

					$entityFieldsDb = $entityObject->getListEx(
						array(),
						array(
							'=ID' => $entityFields['ID'],
							'CHECK_PERMISSIONS' => 'N'
						),
						false,
						false,
						array('*', 'UF_*')
					);
					$originFields = $entityFieldsDb->fetch();
					if ($originFields)
					{
						$merger->mergeFields($originFields, $fields);
					}
				}

				unset($fields['ORIGINATOR_ID']);
				unset($fields['ORIGIN_ID']);
				unset($fields['ORIGIN_VERSION']);
			}
		}
	}

	/**
	 * @param $fields
	 * @return int|0
	 * @internal
	 */
	protected function getFileIdByFaceId($fields)
	{
		$id = 0;

		if(!\Bitrix\Main\Loader::includeModule("faceid"))
			return $id;

		if(isset($fields[CCrmExternalChannelImport::AGENT][CCrmExternalChannelImport::FIELDS]) &&
			isset($fields[CCrmExternalChannelImport::AGENT][CCrmExternalChannelImport::FIELDS]['FACE_ID']))
		{
			$faceId = $fields[CCrmExternalChannelImport::AGENT][CCrmExternalChannelImport::FIELDS]['FACE_ID'];

			if(intval($faceId)>0)
			{
				$r = FaceTable::getList(array('filter' => array('ID' => $faceId)));
				if($fieldsFace = $r->fetch())
				{
					$id = $fieldsFace['FILE_ID'];
				}
			}
		}

		return $id;
	}

	/**
	 * @param $photoId
	 * @internal
	 */
	public function internalizeFileFieldPhoto(&$fieldsAgent, $photoId)
	{
		if(intval($photoId) > 0)
		{
			$fieldsAgent['PHOTO'] = \CFile::MakeFileArray($photoId);
		}
	}

	/**
	 * @param $fieldsAgent
	 * @return \Bitrix\Main\Result
	 * @internal
	 */
	public function tryGetOwnerInfos($fieldsAgent)
	{
		$result = new \Bitrix\Main\Result();

		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $entity */
		$entity = $this->getEntity();

		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		/** @var CCrmExternalChannelConnector $connector */
		$connector = $import->getConnector();

		$res = array(
			'ID' => 0,
			'ORIGIN_VERSION' => ''
		);

		$entityList = $entity->innerGetList(
			array(),
			array(
				'ORIGIN_ID' => $fieldsAgent['ORIGIN_ID'],
				'ORIGINATOR_ID' => $connector->getOriginatorId()
			),
			array('*'),
			false,
			$errors
		);
		if(!$entityList)
		{
			if(count($errors)>0)
			{
				$result->addError(new Error(implode('; ', $errors), 34003));
			}

		}
		elseif($entityEntity = $entityList->Fetch())
		{
			$res = $entityEntity;
		}
		else
		{
			if($entity->getOwnerTypeID()== \CCrmOwnerType::Contact)
			{
				$personFields = $this->prepareFieldsPerson($fieldsAgent);

				$personId = $this->getActualPersonId($personFields);

				if(intval($personId)>0)
				{
					$this->setUpdateEntityMode(self::UPDATE_MODE_MERGE);

					$res['ID'] = $personId;
				}
			}
		}

		$result->setData(array('RESULT'=>$res));

		return $result;
	}


	/**
	 * @return int|null
	 */
	protected function getActualPersonId($fields)
	{
		$duplicateCriteria = \Bitrix\Crm\Integrity\ActualEntitySelector::createDuplicateCriteria($fields, array(
			Crm\Integrity\ActualEntitySelector::SEARCH_PARAM_PERSON));

		$list = array();
		foreach ($duplicateCriteria as $criterion)
		{
			/** @var Crm\Integrity\Duplicate $duplicate */
			$duplicate = $criterion->find();
			if($duplicate !== null)
			{
				$list = array_merge(
					$list,
					$duplicate->getEntityIDsByType(\CCrmOwnerType::Contact)
				);
			}
		}

		$list = array_unique($list);
		$ranking = new \Bitrix\Crm\Integrity\ActualRanking;
		$ranking->rank(\CCrmOwnerType::Contact, $list);

		return $ranking->getEntityId();
	}

	/**
	 * @param $fieldsAgent
	 * @return array
	 * @internal
	 */
	protected function prepareFieldsPerson($fieldsAgent)
	{
		$result = array();

		$fieldsInfo = array(
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
			'PHONE',
			'EMAIL'
		);

		foreach ($fieldsInfo as $value)
		{
			if(isset($fieldsAgent[$value]))
			{
				if($value == 'PHONE' || $value == 'EMAIL')
				{
					$result['FM'][$value] = $fieldsAgent[$value];
				}
				else
				{
					$result[$value] = $fieldsAgent[$value];
				}
			}
		}

		return $result;
	}

	/**
	 * @param array $entityField
	 * @param $agent
	 * @param $resultList
	 * @internal
	 */
	public function modify($entityFields, $agent, &$resultList)
	{
		$result = new \Bitrix\Main\Result();

		$entityId = $entityFields['ID'];
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $entity */
		$entity = $this->getEntity();
		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		$bContact = $entity->getOwnerTypeID()== \CCrmOwnerType::Contact ? true:false;

		$resultList = array(
			'id'=> -1,
			'process' => array(
				'add' => false,
				'upd' => false,
				'error' => array()
			)
		);

		$r = $this->convertExternalFieldsToFields($agent);

		$this->sanitizeFields($agent[CCrmExternalChannelImport::FIELDS]);

		if($r->getErrors())
			$result->addErrors($r->getErrors());
		else
		{
			$requisite = new CCrmExternalChannelImportRequisite();
			$requisite->setOwnerEntity($entity);
			$requisite->import = $this->import;

			$fieldsAgent = $agent[CCrmExternalChannelImport::FIELDS];

			$this->fillFields($fieldsAgent, $entityFields);

			$photoId = 0;
			if($bContact)
			{
				$photoId = $this->getFileIdByFaceId($import->getRawData());
			}

			$id = 0;
			if(intval($entityId)>0)
			{
				$this->prepareMultiFields($entityId, $fieldsAgent, $entityFields);

				$entity->internalizeFields($fieldsAgent, $entity->getFieldsInfo());

				if($bContact && empty($entityFields['PHOTO']))
				{
					$this->internalizeFileFieldPhoto($fieldsAgent, $photoId);
				}

				$res = $entity->innerUpdate($entityId, $fieldsAgent, $errors);
				if($res !== false)
				{
					$id = (int)$entityId;
					$resultList['process']['upd'] = true;
				}

				if(count($errors)>0)
					$result->addError(new Error(implode('; ', $errors), 34008));
			}
			else
			{
				$entity->internalizeFields($fieldsAgent, $entity->getFieldsInfo());

				if($bContact)
				{
					$this->internalizeFileFieldPhoto($fieldsAgent, $photoId);
				}

				$resultId = $entity->innerAdd($fieldsAgent, $errors);
				if($entity->isValidID($resultId))
				{
					$id = (int)$resultId;
					$resultList['process']['add'] = true;
				}

				if(count($errors)>0)
					$result->addError(new Error(implode('; ', $errors), 34009));
			}

			if($id>0)
			{
				$resultList['id'] = $id;
				$requisite->setOwnerEntityId($id);
				$r = $requisite->import($agent);
				if($r->getErrors())
				{
					$result->addError(new Error('Import error', 34010));
					$result->setData(array('requisites'=>$r->getData()));

				}
			}
		}

		return $result;
	}
}

class CCrmExternalChannelImportRequisite extends CCrmExternalChannelImportAgent
{
	private static $ENTITY = null;

	protected $ownerId = -1;
	protected $class = null;

	public function setOwnerEntity($class)
	{
		$this->class = $class;
	}

	protected function getOwnerEntity()
	{
		return $this->class;
	}

	public function setOwnerEntityId($id)
	{
		$this->ownerId = (int)$id;
	}

	protected function getOwnerEntityId()
	{
		return $this->ownerId;
	}

	public function getOwnerTypeID()
	{
		return \CCrmOwnerType::Requisite;
	}

	protected static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new \Bitrix\Crm\EntityRequisite();
		}

		return self::$ENTITY;
	}

	protected function innerList($filter=array())
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $ownerEntity */
		$ownerEntity = $this->getOwnerEntity();

		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		/** @var CCrmExternalChannelConnector $connector */
		$connector = $import->getConnector();


		return $this->getEntity()->getList(
			array(
				'order' => array('ID'),
				'filter' => array_merge(
					$filter,
					array(
						'ENTITY_TYPE_ID' => $ownerEntity->getOwnerTypeID(),
						'ORIGINATOR_ID' => $connector->getOriginatorId()
					)
				),
				'select' => array('*')
			)
		);
	}

	/**
	 * @param $id
	 * @param $fields
	 * @return Result
	 */
	protected function innerUpdate($id, $fields)
	{
		$result = new Result();
		$entity = $this->getEntity();

		if(!$this->isValidID((int)$id))
		{
			$result->addError(new Error("ID is not defined or invalid", 8007));
		}

		if($result->isSuccess())
		{
			$r = $entity->update($id, $fields);
			if(!$r->isSuccess())
			{
				$error = '';
				foreach($r->getErrorMessages() as $message)
					$error .= $message."\n";

				$result->addError(new Error($error, 8004));
			}
		}

		return $result;
	}

	/**
	 * @param $fields
	 * @return Result
	 */
	protected function innerAdd($fields)
	{
		$result = new Result();
		$entity = $this->getEntity();

		$r = $entity->add($fields);
		if($r->isSuccess())
		{
			$result->setData(array('RESULT'=>$r->getId()));
		}
		else
		{
			$error = "";
			foreach($r->getErrorMessages() as $message)
				$error .= $message."\n";

			$result->addError(new Error($error, 8005));
		}

		return $result;
	}

	/**
	 * @param $id
	 * @return Result
	 */
	protected function innerDelete($id)
	{
		$result = new Result();
		$entity = $this->getEntity();

		$r = $entity->delete($id);
		if(!$r->isSuccess())
		{
			$error = "";
			foreach($r->getErrorMessages() as $message)
				$error .= $id.":".$message."\n";

			$result->addError(new Error($error, 8006));
		}

		return $result;
	}

	protected function getFieldsInfo()
	{
		$result = array();

		$fieldsInfo = array_flip(
			array_merge(
				array('NAME', 'XML_ID'),
				\Bitrix\Crm\EntityRequisite::getRqFields()
			)
		);

		foreach($fieldsInfo as $name=>$key)
		{
			$result[$name] = array('TYPE' => '', 'ATTRIBUTES'=>'');
		}

		return $result;
	}

	protected function fillFields(&$requisite, $entityFields=array())
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $ownerEntity */
		$ownerEntity = $this->getOwnerEntity();

		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		/** @var CCrmExternalChannelConnector $connector */
		$connector = $import->getConnector();

		$curDateTime = new \Bitrix\Main\Type\DateTime();
		$curUserId = \CCrmSecurityHelper::GetCurrentUserID();

		$requisite['DATE_CREATE'] = $curDateTime;
		$requisite['DATE_MODIFY'] = $curDateTime;
		$requisite['CREATED_BY_ID'] = $curUserId;
		$requisite['MODIFY_BY_ID'] = $curUserId;
		$requisite['NAME'] = !is_set($requisite, 'NAME') || $requisite['NAME']=='' ? Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_IMPORT_REQUISITE_NAME'):$requisite['NAME'];
		$requisite['PRESET_ID'] = Crm\EntityRequisite::getDefaultPresetId($ownerEntity->getOwnerTypeID());
		$requisite['ENTITY_TYPE_ID'] = $ownerEntity->getOwnerTypeID();
		$requisite['ENTITY_ID'] = $this->getOwnerEntityId();
		$requisite['ACTIVE'] = 'Y';
		$requisite['ORIGINATOR_ID'] = $connector->getOriginatorId();

	}

	/**
	 * @param $requisite
	 * @return Result
	 */
	protected function sanitizeFields(&$requisite)
	{
		$result = new Result();

		$addres = new CCrmExternalChannelImportAddress();

		if(is_array($requisite) && count($requisite)>0)
		{
			$fieldsInfo = $addres->getFieldsInfo();

			if(is_set($requisite, CCrmExternalChannelImport::FIELDS_ADDRESS))
			{
				foreach($requisite[CCrmExternalChannelImport::FIELDS_ADDRESS] as $addresTypeId=>$addresFields)
				{
					unset($requisite[CCrmExternalChannelImport::FIELDS_ADDRESS][$addresTypeId]);

					if(!is_numeric($addresTypeId))
						$addresTypeId = \Bitrix\Crm\EntityAddressType::resolveID($addresTypeId);

					if(is_set($fieldsInfo, $addresTypeId))
						$requisite[CCrmExternalChannelImport::FIELDS_ADDRESS][$addresTypeId] = $addresFields;
				}
			}
		}

		return $result;
	}

	protected function prepareFieldsAddress(&$requisite)
	{
		$addres = new CCrmExternalChannelImportAddress();

		if($this->getUpdateEntityMode() == self::UPDATE_MODE_NONE)
		{
			foreach($addres->getFieldsInfo() as $typeId=>$typeInfo)
			{
				if(!is_set($requisite, CCrmExternalChannelImport::FIELDS_ADDRESS) ||
					!is_set($requisite[CCrmExternalChannelImport::FIELDS_ADDRESS], $typeId)
				)
					$requisite[CCrmExternalChannelImport::FIELDS_ADDRESS][$typeId]['DELETED'] = 'Y';
			}
		}
	}

	/**
	 * @param $fields
	 * @return Result
	 */
	public function checkFields(&$fields)
	{
		$result = new Result();

		$bank = new CCrmExternalChannelImportBank();

		if(is_set($fields, CCrmExternalChannelImport::FIELDS_REQUISITE) && count($fields[CCrmExternalChannelImport::FIELDS_REQUISITE])>0)
		{
			foreach($fields[CCrmExternalChannelImport::FIELDS_REQUISITE] as $requisiteKey=>$requisite)
			{
				if(is_array($requisite) && count($requisite)>0)
				{
					if(!is_set($requisite, 'XML_ID') || $requisite['XML_ID'] == '')
					{
						$result->addError(new Error(" requisite.$requisiteKey. xml_id is not defined", 8002));
					}

					if(is_set($requisite, CCrmExternalChannelImport::FIELDS_BANK))
					{
						$r = $bank->checkFields($requisite);
						if($r->getErrors())
							$result->addErrors($r->getErrors());
					}
				}
				else
				{
					$result->addError(new Error(" field requisite is invalid", 8003));
					unset($fields[CCrmExternalChannelImport::FIELDS_REQUISITE][$requisiteKey]);
					break;
				}
			}
		}
		return $result;
	}

	/**
	 * @param $fields
	 * @return Result
	 */
	public function import(&$fields)
	{
		$result = new Result();
		$import = new Result();
		$batch = array();

		if(!$this->isValidID($this->getOwnerEntityId()))
		{
			$import->addError(new Error("EntityId is not defined or invalid", 8001));
		}
		else
		{
			$proccessList = array();

			$r = $this->checkFields($fields);

			if($r->getErrors())
			{
				$import->addErrors($r->getErrors());
			}
			else
			{
				if(is_set($fields, CCrmExternalChannelImport::FIELDS_REQUISITE) && count($fields[CCrmExternalChannelImport::FIELDS_REQUISITE])>0)
				{
					$bank = new CCrmExternalChannelImportBank();
					$bank->setOwnerEntity($this);
					$bank->import = $this->import;

					foreach($fields[CCrmExternalChannelImport::FIELDS_REQUISITE] as $row=>$requisite)
					{
						$fields = $requisite;
						$this->sanitizeFields($requisite);
						$this->internalizeFields($requisite, $this->getFieldsInfo());
						$this->fillFields($requisite);

						$res = $this->innerList(array(
							'=ENTITY_ID' => $this->getOwnerEntityId(),
							'=XML_ID' => $requisite['XML_ID'],
						));

						if($r = $res->fetch())
						{
							$this->prepareFieldsAddress($requisite);
							$id = $r['ID'];
							$r = $this->innerUpdate($id, $requisite);
						}
						else
						{
							$r = $this->innerAdd($requisite);
							$data = $r->getData();
							$id = $data['RESULT'];
						}

						if($r->getErrors())
						{
							$batch[$row]['errors'] = $r->getErrors();
						}
						elseif($this->isValidID((int)$id))
						{
							$proccessList[] = $id;

							$bank->setOwnerEntityId($id);
							$r = $bank->import($fields);
							if($r->getErrors())
							{
								$batch[$row]['banks'] = $r->getData();
							}
						}
					}

					if(count($batch)<=0)
					{
						$r = $this->deleteEntities($proccessList);
						if($r->getErrors())
							$import->addErrors($r->getErrors());
					}
				}
				else
				{
					$r = $this->deleteEntities(array());
					if($r->getErrors())
						$import->addErrors($r->getErrors());
				}
			}
		}

		if($import->getErrors() || count($batch)>0)
		{
			$result->addError(new Error('','IMPORT_ERROR'));
			$result->setData(array(
				'IMPORT_ERROR'=>$import->getErrors(),
				'BATCH_ERROR'=>$batch
			));
		}

		return $result;
	}

	/**
	 * @param array $proccessList
	 * @return Result
	 */
	protected function deleteEntities($proccessList=array())
	{
		$result = new Result();

		if($this->getUpdateEntityMode() == self::UPDATE_MODE_NONE)
		{
			$resultList = $this->innerList(array('=ENTITY_ID' => $this->getOwnerEntityId()));
			while($list = $resultList->fetch())
			{
				if(!in_array($list['ID'], $proccessList))
				{
					$r = $this->innerDelete($list['ID']);
					if($r->getErrors())
						$result->addErrors($r->getErrors());
				}
			}
		}
		return $result;
	}
}

class CCrmExternalChannelImportBank extends CCrmExternalChannelImportRequisite
{
	private static $ENTITY = null;

	protected static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new \Bitrix\Crm\EntityBankDetail();
		}

		return self::$ENTITY;
	}

	protected function getFieldsInfo()
	{
		$result = array();

		$fieldsInfo = array_flip(
			array_merge(
				array('NAME', 'XML_ID'),
				\Bitrix\Crm\EntityBankDetail::getRqFields(),
				array('COMMENTS')
			)
		);

		foreach($fieldsInfo as $name=>$key)
		{
			$result[$name] = array('TYPE' => '', 'ATTRIBUTES'=>'');
		}

		return $result;
	}

	/**
	 * @param $fields
	 * @return Result
	 */
	public function checkFields(&$fields)
	{
		$result = new Result();

		if(is_set($fields, CCrmExternalChannelImport::FIELDS_BANK) && count($fields[CCrmExternalChannelImport::FIELDS_BANK])>0)
		{
			foreach($fields[CCrmExternalChannelImport::FIELDS_BANK] as $bankKey=>$bank)
			{
				if(is_array($bank) && count($bank)>0)
				{
					if(!is_set($bank, 'XML_ID') || $bank['XML_ID']=='')
					{
						$result->addError(new Error(" bank:$bankKey xml_id is not defined", 9001));
					}
				}
				else
				{
					$result->addError(new Error(" bank: is invalid", 9002));
					unset($fields[CCrmExternalChannelImport::FIELDS_BANK][$bankKey]);
					break;
				}
			}
		}
		return $result;
	}

	protected function fillFields(&$requisite, $entityFields=array())
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $ownerEntity */
		$ownerEntity = $this->getOwnerEntity();

		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		/** @var CCrmExternalChannelConnector $connector */
		$connector = $import->getConnector();

		if(isset($requisite['COMMENTS']))
		{
			$requisite['COMMENTS'] = $this->sanitizeHtml($requisite['COMMENTS']);
		}

		$curUserId = \CCrmSecurityHelper::GetCurrentUserID();

		$requisite['ENTITY_ID'] = $this->getOwnerEntityId();
		$requisite['ENTITY_TYPE_ID'] = $ownerEntity->getOwnerTypeID();
		$requisite['NAME'] = !is_set($requisite, 'NAME') || $requisite['NAME']=='' ? Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_IMPORT_BANK_REQUISITE_NAME'):$requisite['NAME'];
		$requisite['CREATED_BY_ID'] = $curUserId;
		$requisite['MODIFY_BY_ID'] = $curUserId;
		$requisite['COUNTRY_ID'] = \Bitrix\Crm\EntityPreset::getCurrentCountryId();
		$requisite['ACTIVE'] = 'Y';
		$requisite['ORIGINATOR_ID'] = $connector->getOriginatorId();
	}

	/**
	 * @param $requisite
	 * @return Result
	 */
	public function import(&$requisite)
	{
		$result = new Result();
		$import = new Result();
		$batch = array();

		$proccessList = array();

		if(is_array($requisite[CCrmExternalChannelImport::FIELDS_BANK]) && !empty($requisite[CCrmExternalChannelImport::FIELDS_BANK]))
		{
			foreach($requisite[CCrmExternalChannelImport::FIELDS_BANK] as $row=>&$bank)
			{
				$this->internalizeFields($bank, $this->getFieldsInfo());
				$this->fillFields($bank);

				$res = $this->innerList(array(
					'=ENTITY_ID' => $this->getOwnerEntityId(),
					'=XML_ID' => $bank['XML_ID'],
				));

				if($r = $res->fetch())
				{
					$id = $r['ID'];
					$r = $this->innerUpdate($id, $bank);
				}
				else
				{
					$r = $this->innerAdd($bank);
					$data = $r->getData();
					$id = $data['RESULT'];
				}

				if($r->getErrors())
				{
					$batch[$row]['errors'] = $r->getErrors();
				}
				elseif($this->isValidID((int)$id))
				{
					$proccessList[] = $id;
				}
			}

			if(count($batch)<=0)
			{
				$r = $this->deleteEntities($proccessList);
				if($r->getErrors())
					$import->addErrors($r->getErrors());
			}
		}
		else
		{
			$r = $this->deleteEntities(array());
			if($r->getErrors())
				$import->addErrors($r->getErrors());
		}

		if($import->getErrors() || count($batch)>0)
		{
			$result->addError(new Error('','IMPORT_ERROR'));
			$result->setData(array(
				'IMPORT_ERROR'=>$import->getErrors(),
				'BATCH_ERROR'=>$batch
			));
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param $fields
	 * @return Result
	 */
	protected function innerUpdate($id, $fields)
	{
		$result = new Result();
		$entity = $this->getEntity();

		if(!$this->isValidID((int)$id))
		{
			$result->addError(new Error("ID is not defined or invalid", 9007));
		}

		if($result->isSuccess())
		{
			$r = $entity->update($id, $fields);
			if(!$r->isSuccess())
			{
				$error = '';
				foreach($r->getErrorMessages() as $message)
					$error .= $message."\n";

				$result->addError(new Error($error, 9004));
			}
		}

		return $result;
	}

	/**
	 * @param $fields
	 * @return Result
	 */
	protected function innerAdd($fields)
	{
		$result = new Result();
		$entity = $this->getEntity();

		$r = $entity->add($fields);
		if($r->isSuccess())
		{
			$result->setData(array('RESULT'=>$r->getId()));
		}
		else
		{
			$error = "";
			foreach($r->getErrorMessages() as $message)
				$error .= $message."\n";

			$result->addError(new Error($error, 9005));
		}

		return $result;
	}

	/**
	 * @param $id
	 * @return Result
	 */
	protected function innerDelete($id)
	{
		$result = new Result();
		$entity = $this->getEntity();

		$r = $entity->delete($id);
		if(!$r->isSuccess())
		{
			$error = "";
			foreach($r->getErrorMessages() as $message)
				$error .= $id.":".$message."\n";

			$result->addError(new Error($error, 9006));
		}

		return $result;
	}
}

class CCrmExternalChannelImportAddress extends CCrmExternalChannelImportRequisite
{
	protected function getFieldsInfo()
	{
		$result = array();

		foreach(\Bitrix\Crm\RequisiteAddress::getClientTypeInfos() as $typeInfo)
		{
			$result[$typeInfo['id']] = $typeInfo;
		}

		return $result;
	}
}