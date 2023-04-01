<?php
namespace Bitrix\ImOpenLines\Crm;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImOpenLines\Crm;
use Bitrix\ImOpenLines\Error;
use Bitrix\ImOpenLines\Result;
use Bitrix\ImOpenLines\Session;

use Bitrix\ImConnector\Connector;

use Bitrix\Crm\Category\DealCategory;

use Bitrix\Im;

Crm::loadMessages();

class Common
{
	/**
	 * @param $userCode
	 * @param bool $noImol
	 * @return string
	 */
	public static function getCommunicationType($userCode, $noImol = false)
	{
		$parsedUserCode = Session\Common::parseUserCode($userCode);
		$messengerType = $parsedUserCode['CONNECTOR_ID'];

		if ($messengerType == 'telegrambot')
		{
			$communicationType = 'TELEGRAM';
		}
		elseif ($messengerType == 'facebook')
		{
			$communicationType = 'FACEBOOK';
		}
		elseif ($messengerType == 'vkgroup')
		{
			$communicationType = 'VK';
		}
		elseif ($messengerType == 'network')
		{
			$communicationType = 'BITRIX24';
		}
		elseif ($messengerType == 'livechat')
		{
			$communicationType = 'OPENLINE';
		}
		elseif ($messengerType == 'viber')
		{
			$communicationType = 'VIBER';
		}
		elseif ($messengerType == 'instagram')
		{
			$communicationType = 'INSTAGRAM';
		}
		elseif ($messengerType == 'fbinstagram')
		{
			$communicationType = 'INSTAGRAM';
		}
		else
		{
			if($noImol === true)
			{
				$communicationType = mb_strtoupper($messengerType);
			}
			else
			{
				$communicationType = 'IMOL';
			}
		}
		return $communicationType;
	}

	/**
	 * @param $id
	 * @return Result
	 */
	public static function getActivityBindings($id): Result
	{
		$result = new Result();

		if (!Loader::includeModule('crm'))
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NOT_LOAD_CRM'), Crm::ERROR_IMOL_NOT_LOAD_CRM, __METHOD__));
		}
		else
		{
			$id = intval($id);
			$bindings = [
				\CCrmOwnerType::LeadName => 0,
				\CCrmOwnerType::ContactName => 0,
				\CCrmOwnerType::CompanyName => 0,
				\CCrmOwnerType::DealName => 0
			];

			if ($id > 0)
			{
				$bindingsCRM = \CAllCrmActivity::GetBindings($id);

				foreach ($bindingsCRM as $item)
				{
					$type = \CCrmOwnerType::ResolveName($item['OWNER_TYPE_ID']);

					switch ($type)
					{
						case \CCrmOwnerType::LeadName:
						case \CCrmOwnerType::ContactName:
						case \CCrmOwnerType::CompanyName:
						case \CCrmOwnerType::DealName:
							if($bindings[$type] == 0 || $bindings[$type] > $item['OWNER_ID'])
							{
								$bindings[$type] = $item['OWNER_ID'];
							}
							break;

						default:

							break;
					}

				}

				$result->setData($bindings);
			}
			else
			{
				$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_ID_ACTIVITY'), Crm::ERROR_IMOL_CRM_NO_ID_ACTIVITY, __METHOD__, $id));
			}
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param $newBindings
	 * @return Result
	 */
	public static function addActivityBindings($id, $newBindings): Result
	{
		$result = new Result();

		if (!Loader::includeModule('crm'))
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NOT_LOAD_CRM'), Crm::ERROR_IMOL_NOT_LOAD_CRM, __METHOD__));
		}
		else
		{
			if($id > 0 && !empty($newBindings) && is_array($newBindings))
			{
				$bindings = \CAllCrmActivity::GetBindings($id);

				foreach($bindings as $binding)
				{
					if(!empty($newBindings[\CCrmOwnerType::ResolveName($binding['OWNER_TYPE_ID'])]) &&
						$binding['OWNER_ID'] == $newBindings[\CCrmOwnerType::ResolveName($binding['OWNER_TYPE_ID'])])
					{
						unset($newBindings[\CCrmOwnerType::ResolveName($binding['OWNER_TYPE_ID'])]);
					}
				}

				if(!empty($newBindings))
				{
					foreach ($newBindings as $ownerType => $ownerId)
					{
						$bindings[] = [
							'OWNER_ID' => $ownerId,
							'OWNER_TYPE_ID' => \CCrmOwnerType::ResolveID($ownerType)
						];
					}

					\CAllCrmActivity::SaveBindings($id, $bindings, false, false);
				}
			}
			else
			{
				$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_REQUIRED_PARAMETERS'), Crm::ERROR_IMOL_CRM_NO_REQUIRED_PARAMETERS, __METHOD__));
			}
		}

		return $result;
	}

	/**
	 * @param $type
	 * @param $id
	 * @param $updateFields
	 * @return bool
	 */
	public static function update($type, $id, $updateFields): bool
	{
		$result = false;
		$entity = null;
		$options = [];

		if(Loader::includeModule('crm'))
		{
			if ($type === Crm::ENTITY_LEAD)
			{
				$entity = new \CCrmLead(false);
			}
			elseif ($type === Crm::ENTITY_COMPANY)
			{
				$entity = new \CCrmCompany(false);

				unset(
					$updateFields['COMPANY_ID'],
					$updateFields['CONTACT_ID'],
					$updateFields['NAME'],
					$updateFields['LAST_NAME'],
					$updateFields['SECOND_NAME'],
					$updateFields['SOURCE_DESCRIPTION']
				);
			}
			elseif ($type === Crm::ENTITY_CONTACT)
			{
				$entity = new \CCrmContact(false);

				unset(
					$updateFields['COMPANY_ID'],
					$updateFields['CONTACT_ID']
				);
			}
			elseif ($type === Crm::ENTITY_DEAL)
			{
				$entity = new \CCrmDeal(false);

				unset(
					$updateFields['COMPANY_ID'],
					$updateFields['CONTACT_ID'],
					$updateFields['FM'],
					$updateFields['NAME'],
					$updateFields['LAST_NAME'],
					$updateFields['SECOND_NAME']
				);
			}

			if(!empty($updateFields['EDITOR_ID']))
			{
				$options['CURRENT_USER'] = $updateFields['EDITOR_ID'];

				unset($updateFields['EDITOR_ID']);
			}

			if(
				$entity !== null
				&& !empty($updateFields)
				&& (
					!isset($updateFields['FM'])
					|| !empty($updateFields['FM'])
				)
			)
			{
				$previousFields = $entity::GetByID($id, false) ?: [];
				if ($entity->Update($id, $updateFields, true, true, $options))
				{
					$errors = [];
					\CCrmBizProcHelper::AutoStartWorkflows(
						\CCrmOwnerType::ResolveID($type),
						$id,
						\CCrmBizProcEventType::Edit,
						$errors
					);

					//Region automation
					if (\Bitrix\Crm\Automation\Factory::isAutomationRunnable(\CCrmOwnerType::ResolveID($type)))
					{
						$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::ResolveID($type), $id);
						$starter->runOnUpdate($updateFields, $previousFields);
					}
					//End region
				}

				$result = true;
			}
		}

		return $result;
	}

	/**
	 * @param $type
	 * @param $id
	 * @param bool $withMultiFields
	 * @return array|bool|false|mixed|null
	 */
	public static function get($type, $id, $withMultiFields = false)
	{
		$data = false;
		$entity = null;

		if (Loader::includeModule('crm'))
		{
			if ($type === Crm::ENTITY_LEAD)
			{
				$entity = new \CCrmLead(false);
			}
			elseif ($type === Crm::ENTITY_COMPANY)
			{
				$entity = new \CCrmCompany(false);
			}
			elseif ($type === Crm::ENTITY_CONTACT)
			{
				$entity = new \CCrmContact(false);
			}
			elseif ($type === Crm::ENTITY_DEAL)
			{
				$entity = new \CCrmDeal(false);
			}

			if (!empty($entity))
			{
				$data = $entity->GetByID($id, false);

				if ($withMultiFields && $type != Crm::ENTITY_DEAL)
				{
					$multiFields = new \CCrmFieldMulti();
					$res = $multiFields->GetList([], [
						'ENTITY_ID' => $type,
						'ELEMENT_ID' => $id
					]);
					while ($row = $res->Fetch())
					{
						$data['FM'][$row['TYPE_ID']][$row['VALUE_TYPE']][] = $row['VALUE'];
					}


				}

				$assignedId = (int)$data['ASSIGNED_BY_ID'];

				if (
					Loader::includeModule('im')
					&& (
						!Im\User::getInstance($assignedId)->isActive()
						|| Im\User::getInstance($assignedId)->isAbsent()
					)
				)
				{
					$data['ASSIGNED_BY_ID'] = 0;
				}
			}
		}

		return $data;
	}

	/**
	 * @param $type
	 * @param $id
	 * @return bool
	 */
	public static function delete($type, $id)
	{
		$result = false;

		if (Loader::includeModule('crm'))
		{
			if ($type === Crm::ENTITY_LEAD)
			{
				$entity = new \CCrmLead(false);
			}
			elseif ($type === Crm::ENTITY_COMPANY)
			{
				$entity = new \CCrmCompany(false);
			}
			elseif ($type === Crm::ENTITY_CONTACT)
			{
				$entity = new \CCrmContact(false);
			}
			elseif ($type === Crm::ENTITY_DEAL)
			{
				$entity = new \CCrmDeal(false);
			}

			if(!empty($entity))
			{
				$entity->Delete($id);

				$result = true;
			}
		}

		return $result;
	}

	/**
	 * @param $type
	 * @param $id
	 * @param $fieldType
	 * @param $fieldValue
	 * @return bool
	 */
	public static function deleteMultiField($type, $id, $fieldType, $fieldValue)
	{
		$result = false;

		if (Loader::includeModule('crm'))
		{
			$crmFieldMulti = new \CCrmFieldMulti();
			$ar = \CCrmFieldMulti::GetList([], [
				'TYPE_ID' => $fieldType,
				'RAW_VALUE' => $fieldValue,
				'ENTITY_ID' => $type,
				'ELEMENT_ID' => $id,
			]);
			if ($row = $ar->Fetch())
			{
				$crmFieldMulti->Delete($row['ID']);

				$result = true;
			}
		}

		return $result;
	}

	/**
	 * @param $fields
	 * @return array
	 */
	public static function formatMultifieldFields($fields)
	{
		$destinationData = [];
		if (is_array($fields))
		{
			foreach ($fields as $typeID => $typeData)
			{
				$counter = 0;
				$results = [];
				foreach ($typeData as $valueType => $values)
				{
					for ($i = 0, $length = count($values); $i < $length; $i++)
					{
						$results["n{$counter}"] = ['VALUE_TYPE' => $valueType, 'VALUE' => $values[$i]];
						$counter++;
					}
				}

				$destinationData[$typeID] = $results;
			}
		}

		return $destinationData;
	}

	/**
	 * @param $userCode
	 * @param string $lineTitle
	 * @return string
	 */
	public static function getSourceName($userCode, $lineTitle = '')
	{
		$parsedUserCode = Session\Common::parseUserCode($userCode);
		$messengerType = $parsedUserCode['CONNECTOR_ID'];

		$lineName = Loc::getMessage('IMOL_CRM_LINE_TYPE_'.mb_strtoupper($messengerType));

		if (!$lineName && Loader::includeModule("imconnector"))
		{
			$lineName = Connector::getNameConnector($messengerType);
		}

		return ($lineName ? $lineName : $messengerType) . ($lineTitle ? ' - ' . $lineTitle : '');
	}

	/**
	 * @param $entityType
	 * @param $entityId
	 * @return bool
	 */
	public static function hasAccessToEntity($entityType, $entityId)
	{
		if (!Loader::includeModule("crm") || !$entityType || !$entityId || $entityType == 'NONE')
		{
			$return = true;
		}
		else
		{
			$return = \CCrmAuthorizationHelper::CheckReadPermission($entityType, $entityId);
		}

		return $return;
	}

	/**
	 * @param $activityId
	 * @return Result
	 */
	public static function hasAccessToEntitiesBindingActivity($activityId)
	{
		$result = new Result();
		$result->setResult(false);

		if(Loader::includeModule('crm'))
		{
			if(self::hasAccessToEntity(\CCrmOwnerType::ActivityName,$activityId))
			{
				$result->setResult(true);
			}

			if($result->getResult() == false)
			{
				$bindings = self::getActivityBindings($activityId);

				foreach ($bindings as $typeEntity => $idEntity)
				{
					if($result->getResult() == false && self::hasAccessToEntity($typeEntity, $idEntity))
					{
						$result->setResult(true);
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param $type
	 * @param null $id
	 * @return bool|mixed|string
	 */
	public static function getLink($type, $id = null)
	{
		$result = false;

		if (Loader::includeModule('crm'))
		{
			$defaultValue = false;
			if (is_null($id))
			{
				$defaultValue = true;
				//hack
				$id = 0;
			}

			$result = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::ResolveID($type), $id, false);

			if ($defaultValue)
			{
				$result = str_replace($id, '#ID#', $result);
			}
		}

		return $result;
	}

	/**
	 * @param $type
	 * @param $id
	 * @return mixed|string
	 */
	public static function getEntityCaption($type, $id)
	{
		$result = '';

		if (Loader::includeModule('crm'))
		{
			$result = \CCrmOwnerType::GetCaption(\CCrmOwnerType::ResolveID($type), $id, false);
		}

		return $result;
	}

	/**
	 * @param $activityId
	 * @return array
	 */
	public static function generateSearchContent($activityId)
	{
		$result = [];

		if ((int)$activityId > 0)
		{
			$bindings = self::getActivityBindings($activityId);

			foreach ($bindings as $typeEntity => $idEntity)
			{
				$entityCaption = self::getEntityCaption($typeEntity, $idEntity);

				if (!empty($entityCaption))
				{
					$result[] = $entityCaption;
				}
			}
		}

		return $result;
	}

	/**
	 * @param $crmEntityType
	 *
	 * @return bool|mixed
	 */
	public static function getCrmEntityIdByTypeCode($crmEntityType)
	{
		$result = false;
		$crmEntityType = mb_strtoupper($crmEntityType);

		if (Loader::includeModule('crm'))
		{
			$crmEntityList = [
				\CCrmOwnerType::LeadName => \CCrmOwnerType::Lead,
				\CCrmOwnerType::DealName => \CCrmOwnerType::Deal,
				\CCrmOwnerType::ContactName => \CCrmOwnerType::Contact,
				\CCrmOwnerType::CompanyName => \CCrmOwnerType::Company,
			];

			$result = !empty($crmEntityList[$crmEntityType]) ? $crmEntityList[$crmEntityType] : false;
		}

		return $result;
	}

	/**
	 * @param $id
	 *
	 * @return array
	 */
	public static function getActivityBindingsFormatted($id)
	{
		$result = [];
		$bindings = self::getActivityBindings($id)->getData();

		foreach ($bindings as $key => $binding)
		{
			if ($binding > 0)
			{
				$ownerTypeId = self::getCrmEntityIdByTypeCode($key);
				if ($ownerTypeId)
				{
					$result[] = [
						'OWNER_TYPE_ID' => $ownerTypeId,
						'OWNER_ID' => $binding
					];
				}
			}
		}

		return $result;
	}


	/**
	 * @param $crmEntityType
	 * @param $crmEntityId
	 * @return int
	 */
	public static function getLastChatIdByCrmEntity($crmEntityType, $crmEntityId): int
	{
		$result = 0;

		if (Loader::includeModule('im') && Loader::includeModule('crm'))
		{
			$crmEntityIdByTypeCode = self::getCrmEntityIdByTypeCode($crmEntityType);
			$crmEntityId = (int)$crmEntityId;

			if($crmEntityIdByTypeCode && $crmEntityId > 0)
			{
				$filter = [
					'PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\OpenLine::ACTIVITY_PROVIDER_ID,
					'BINDINGS' => [
						0 => [
							'OWNER_TYPE_ID' => $crmEntityIdByTypeCode,
							'OWNER_ID' => $crmEntityId,
						],
					],
				];
				$activity = \CCrmActivity::GetList(
					['LAST_UPDATED' => 'DESC'],
					$filter,
					false,
					false,
					[
						'ID', 'OWNER_ID', 'OWNER_TYPE_ID',
						'TYPE_ID', 'PROVIDER_ID', 'PROVIDER_TYPE_ID', 'ASSOCIATED_ENTITY_ID', 'DIRECTION',
						'SUBJECT', 'STATUS', 'DESCRIPTION', 'DESCRIPTION_TYPE',
						'DEADLINE', 'RESPONSIBLE_ID'
					],
					['QUERY_OPTIONS' => ['LIMIT' => 1, 'OFFSET' => 0]]
				)->fetch();
			}

			if (!empty($activity))
			{
				$activity = \Bitrix\Crm\Timeline\ActivityController::prepareScheduleDataModel($activity);

				if (
					!empty($activity['ASSOCIATED_ENTITY']['COMMUNICATION']['VALUE'])
					&& mb_strpos($activity['ASSOCIATED_ENTITY']['COMMUNICATION']['VALUE'], 'imol|') === 0
				)
				{
					$entityId = str_replace('imol|', '',  $activity['ASSOCIATED_ENTITY']['COMMUNICATION']['VALUE']);
					$filter = [
						'=ENTITY_TYPE' => 'LINES',
						'=ENTITY_ID' => $entityId
					];

					$chatData = \Bitrix\Im\Model\ChatTable::getList(['select' => ['ID'], 'filter' => $filter])->fetch();
					$chatData['ID'] = (int)$chatData['ID'];

					$result = $chatData['ID'] > 0 ? $chatData['ID'] : 0;
				}
			}
		}

		return $result;
	}

	/**
	 * Return a list of funnels for sales transactions.
	 *
	 * @return Result
	 */
	public static function getDealCategories(): Result
	{
		$result = new Result();

		if (!Loader::includeModule('crm'))
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NOT_LOAD_CRM'), Crm::ERROR_IMOL_NOT_LOAD_CRM, __METHOD__));
		}
		else
		{
			$categories = DealCategory::getSelectListItems();

			foreach ($categories as $id => $category)
			{
				$categories[$id] = [
					'ID' => $id,
					'NAME' => $category,
				];
			}

			$result->setData($categories);
		}

		return $result;
	}
}