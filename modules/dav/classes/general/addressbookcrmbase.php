<?php

/**
 * Class CDavAddressbookCrmResource
 */
abstract class CDavAddressbookCrmBase
	extends CDavAddressbookBaseLimited
{

	private $multiFieldEntityId;
	protected static $vCardValueTypeMap = array(
		'pref' => 'HOME',
		'work' => 'WORK',
		'VOICE' => 'OTHER',
		'PAGER' => 'PAGER',
		'FAX' => 'FAX',
		'MAIN' => 'MOBILE',
	);

	/**
	 * Handler for PUT request of DAV protocol
	 * @param $id
	 * @param CDavICalendarComponent $card
	 * @return bool
	 */
	public function Put($id, $card)
	{
		parent::Put($id, $card);
		$entityParams = $this->PrepareEntityParamsFromVCard($id, $card);
		$photoProperty = $card->GetProperties('PHOTO');
		if (!empty($photoProperty[0]))
		{
			$photo = $photoProperty[0];
		}
		if (intval($id) && $oldEntity = $this->LoadEntityById($id))
		{
			$multiFields = $this->LoadMultiFields($id);
			$oldEntityParams = $this->AttachMultiFieldsToEntityForImport($multiFields, $oldEntity);
			if (isset($photo))
				$oldEntityParams = $this->AttachToEntityImgId($oldEntityParams, $photo->Value(), $photo->Parameter('TYPE'));
			$mergedEntityParams = $this->Merge($entityParams, $oldEntityParams);
			return $this->UpdateEntity($id, $mergedEntityParams);
		}
		else
		{
			if (isset($photo))
				$entityParams = $this->AttachToEntityImgId($entityParams, $photo->Value(), $photo->Parameter('TYPE'));
			return $this->AddEntity($entityParams);
		}
	}

	/**
	 * @param $id
	 * @return array|bool|false|mixed|null
	 */
	abstract protected function LoadEntityById($id);

	/**
	 * @param $entityParams
	 * @param $fileContent
	 * @param $fileExtension
	 * @return mixed
	 * @internal param $card
	 */
	abstract protected function AttachToEntityImgId($entityParams, $fileContent, $fileExtension);

	/**
	 * @param $fields
	 * @return bool|int
	 */
	abstract protected function AddEntity($fields);

	/**
	 * @param $id
	 * @param $fields
	 * @return bool
	 */
	abstract protected function UpdateEntity($id, $fields);

	/**
	 * @param $entityParams
	 * @param $oldEntityParams
	 * @return array
	 */
	abstract protected function Merge($entityParams, $oldEntityParams);


	/**
	 * @param $content
	 * @param $type
	 * @return bool|int|string
	 */
	protected function SaveImg($content, $type)
	{
		$fileContent = base64_decode($content);
		if ($fileContent !== false && strlen($fileContent) > 0)
		{
			$fileName = CTempFile::GetFileName(substr(md5(mt_rand()), 0, 3) . '.' . $type);
			if (CheckDirPath($fileName))
			{
				file_put_contents($fileName, $fileContent);
				$photo = CFile::MakeFileArray($fileName);
				$photo['MODULE_ID'] = 'crm';
				$photo['del'] = 'Y';
				return CFile::SaveFile($photo, 'crm');
			}
		}
		return false;
	}

	/**
	 * @return mixed
	 */
	protected function GetMultiFieldEntityId()
	{
		return $this->multiFieldEntityId;
	}

	/**
	 * @param $id
	 */
	protected function SetMultiFieldEntityId($id)
	{
		$this->multiFieldEntityId = $id;
	}

	/**
	 * Catalog getctag property for DAV protocol
	 * @param $collectionId
	 * @param array $filter
	 * @return string getctag property
	 */
	public function GetCTag($collectionId, $filter = array())
	{
		//@TODO: change ctag if change crm permissions, now crm permissions have not time tag, and it is now not possible.
		return parent::GetCTag($collectionId, $filter);
	}

	/**
	 * Return entities array
	 * @param $collectionId
	 * @param $account
	 * @param array $filter
	 * @param $maxCount
	 * @return mixed
	 */
	protected function LoadLimitedEntitiesList($collectionId, $account, $filter = array(), $maxCount)
	{
		$result = array();
		$crmResourceEntitiesList = $this->LoadCrmResourceEntitiesList($collectionId, $account, $filter, $maxCount);
		while ($crmResourceEntity = $crmResourceEntitiesList->Fetch())
		{
			$crmResourceEntityMultiFields = $this->LoadMultiFields($crmResourceEntity['ID']);
			$result[] = $this->AttachMultiFieldsToEntityForExport($crmResourceEntityMultiFields, $crmResourceEntity);
		}
		return $result;
	}


	/**
	 * Add checking if user has access to CRM
	 * @param $principal
	 * @return bool
	 */
	protected function AdditionalPrivilegesCheck($principal)
	{
		return parent::AdditionalPrivilegesCheck($principal)
			&& CCrmPerms::IsAccessEnabled();
	}

	/**
	 * Map: key=>value vCard properties of entity
	 * @param $entity
	 * @return mixed
	 */
	protected function GetVCardDataMap($entity)
	{
		$map = array();

		if (!empty($entity["PHONE_MOBILE"]))
		{
			foreach ($entity["PHONE_MOBILE"] as $number)
				$map["TEL"][] = array(
					"VALUE" => $number,
					"PARAMETERS" => array("TYPE" => "CELL")
				);
		}

		if (!empty($entity["PHONE_WORK"]))
		{
			foreach ($entity["PHONE_WORK"] as $number)
				$map["TEL"][] = array(
					"VALUE" => $number,
					"PARAMETERS" => array("TYPE" => "WORK")
				);
		}

		if (!empty($entity["PHONE_FAX"]))
		{
			foreach ($entity["PHONE_FAX"] as $number)
				$map["TEL"][] = array(
					"VALUE" => $number,
					"PARAMETERS" => array("TYPE" => "FAX")
				);
		}

		if (!empty($entity["PHONE_HOME"]))
		{
			foreach ($entity["PHONE_HOME"] as $number)
				$map["TEL"][] = array(
					"VALUE" => $number,
					"PARAMETERS" => array("TYPE" => "HOME")
				);
		}

		if (!empty($entity["PHONE_PAGER"]))
		{
			foreach ($entity["PHONE_PAGER"] as $number)
				$map["TEL"][] = array(
					"VALUE" => $number,
					"PARAMETERS" => array("TYPE" => "PAGER")
				);
		}

		if (!empty($entity["PHONE_OTHER"]))
		{
			foreach ($entity["PHONE_OTHER"] as $number)
				$map["TEL"][] = array(
					"VALUE" => $number,
				);
		}


		if (!empty($entity["EMAIL_WORK"]))
		{
			foreach ($entity["EMAIL_WORK"] as $number)
				$map["EMAIL"][] = array(
					"VALUE" => $number,
					"PARAMETERS" => array("TYPE" => "WORK")
				);
		}

		if (!empty($entity["EMAIL_HOME"]))
		{
			foreach ($entity["EMAIL_HOME"] as $number)
				$map["EMAIL"][] = array(
					"VALUE" => $number,
					"PARAMETERS" => array("TYPE" => "HOME")
				);
		}

		if (!empty($entity["EMAIL_OTHER"]))
		{
			foreach ($entity["EMAIL_OTHER"] as $number)
				$map["EMAIL"][] = array(
					"VALUE" => $number,
					"PARAMETERS" => array("TYPE" => "INTERNET")
				);
		}

		return $map;
	}

	/**
	 * @param $id
	 * @param CDavICalendarComponent $card
	 * @return array
	 */
	protected function PrepareEntityParamsFromVCard($id, $card)
	{
		$fields = parent::PrepareEntityParamsFromVCard($id, $card);
		$idProperty = $card->GetProperties('UID');
		$fields['ID'] = $idProperty[0]->Value();

		$telNumbersObjects = $card->GetProperties('TEL');
		$i = 0;
		foreach ($telNumbersObjects as $numberObject)
		{
			$fields['FM']['PHONE']['n' . ++$i] = array(
				'VALUE_TYPE' => !empty(self::$vCardValueTypeMap[$numberObject->Parameter('type')]) ? self::$vCardValueTypeMap[$numberObject->Parameter('type')] : 'OTHER',
				'VALUE' => $numberObject->Value(),
			);
		}

		$emailObjects = $card->GetProperties('EMAIL');
		$i = 0;
		foreach ($emailObjects as $emailObject)
		{
			$fields['FM']['EMAIL']['n' . ++$i] = array(
				'VALUE_TYPE' => !empty(self::$vCardValueTypeMap[$emailObject->Parameter('type')]) ? self::$vCardValueTypeMap[$emailObject->Parameter('type')] : 'OTHER',
				'VALUE' => $emailObject->Value(),
			);
		}

		return $fields;
	}


	/**
	 * Date of last modification
	 * @param $entity
	 * @return string
	 */
	protected function EntityLastModifiedAt($entity)
	{
		return $entity['DATE_MODIFY'];
	}

	/**
	 * Date of last modification of catalog = last modification of entities in this catalog
	 * @param $collectionId
	 * @param array $filter
	 * @return string
	 */
	protected function CatalogLastModifiedAt($collectionId = '', $filter = array())
	{
		if (!empty($filter['XML_ID']))
			unset($filter['XML_ID']);
		$lastEditedEntity = $this->LoadCrmResourceEntitiesListByParams(array('DATE_MODIFY' => "DESC"), $filter, array('DATE_MODIFY'), 1);
		if ($entity = $lastEditedEntity->Fetch())
			return $entity["DATE_MODIFY"];
		return \Bitrix\Main\Type\Date::createFromTimestamp(0)->toString();
	}

	/**
	 * @param $collectionId
	 * @param $account
	 * @param array $filter
	 * @param $maxCount
	 * @return mixed
	 */
	abstract protected function LoadCrmResourceEntitiesList($collectionId, $account, $filter = array(), $maxCount);

	/**
	 * @param $order
	 * @param $filter
	 * @param $selectParams
	 * @param $maxCount
	 * @return CDBResult
	 */
	abstract protected function LoadCrmResourceEntitiesListByParams($order, $filter, $selectParams = array(), $maxCount);

	/**
	 *
	 * @param $entityId
	 * @return bool|CDBResult
	 */
	protected function LoadMultiFields($entityId)
	{
		return CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => $this->GetMultiFieldEntityId(), 'ELEMENT_ID' => $entityId));
	}

	/**
	 * Attach to entity multi fields  for export with CardDAV
	 * @param $multiFields CDBResult
	 * @param $entity
	 * @return array
	 */
	protected function AttachMultiFieldsToEntityForExport($multiFields, $entity)
	{
		while ($multiField = $multiFields->Fetch())
		{
			$entity[$multiField['COMPLEX_ID']][] = $multiField['VALUE'];
		}
		return $entity;
	}


	/**
	 * Attach to entity multi fields  for import from VCard
	 * @param $multiFields CDBResult
	 * @param $entity
	 * @return mixed
	 */
	protected function AttachMultiFieldsToEntityForImport($multiFields, $entity)
	{
		while ($multiField = $multiFields->Fetch())
		{
			$entity['FM'][$multiField['TYPE_ID']][] = array(
				'VALUE_TYPE' => $multiField['VALUE_TYPE'],
				'VALUE' => $multiField['VALUE'],
			);
		}
		return $entity;
	}
}