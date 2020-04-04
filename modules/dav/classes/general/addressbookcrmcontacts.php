<?php

use Bitrix\Main\Localization\Loc;

/**
 * Class CDavCrmContacts
 */
class CDavCrmContacts
	extends CDavAddressbookCrmBaseLimited
{

	const RESOURCE_SYNC_SETTINGS_NAME = 'CONTACT';

	/**
	 * CDavCrmContacts constructor.
	 * @param CDavGroupDav $groupdav
	 */
	public function __construct(CDavGroupDav $groupdav)
	{
		parent::__construct($groupdav);
		$this->SetName(Loc::getMessage('DAV_CONTACTS'));
		$this->SetNamespace(CDavGroupDav::CARDDAV);
		$this->SetUri('crmContacts');
		$this->SetMinimumPrivileges(array('DAV::read', 'DAV::write'));
		$this->SetMultiFieldEntityId('CONTACT');
	}

	/**
	 * Add checking of some additional cases
	 * @param $principal
	 * @return bool
	 */
	protected function AdditionalPrivilegesCheck($principal)
	{
		return parent::AdditionalPrivilegesCheck($principal)
			&& CAllCrmContact::CheckExportPermission();
	}

	/**
	 * Map: key=>value vCard properties of contact
	 * @param $contact
	 * @return mixed
	 */
	protected function GetVCardDataMap($contact)
	{
		$map = parent::GetVCardDataMap($contact);
		$map["N"] = $contact["LAST_NAME"] . ";" . $contact["NAME"] . ";" . $contact["SECOND_NAME"] . ";;";
		$map["FN"] = $contact["NAME"] . ($contact["SECOND_NAME"] ? " " . $contact["SECOND_NAME"] : "") . " " . $contact["LAST_NAME"];
		$map["REV"] = date("Ymd\\THis\\Z", MakeTimeStamp($contact["DATE_MODIFY"]));
		$map["UID"] = $contact["ID"];
		if (strlen($contact["POST"]) > 0)
			$map["TITLE"] = $contact["POST"];
		if (!empty($contact['COMPANY_TITLE']))
			$map['ORG'] = $contact['COMPANY_TITLE'];
		$map["URL"] = 'bitrix24://@%';
		$map['IMG'] = !empty($contact['PHOTO']) ? $contact['PHOTO'] : '';
		return $map;
	}

	/**
	 * Load contacts list
	 * @param $order
	 * @param $filter
	 * @param $selectParams
	 * @param $maxCount
	 * @return CDBResult
	 */
	protected function LoadCrmResourceEntitiesListByParams($order, $filter, $selectParams = array(), $maxCount)
	{
		$filter['PERMISSION'] = array('EXPORT');
		return CCrmContact::GetListEx(
			$order,
			$filter,
			false,
			$maxCount ? array('nTopCount' => $maxCount) : array(),
			$selectParams ? $selectParams : array('ID', 'LAST_NAME', 'SECOND_NAME', 'NAME', 'POST', 'COMPANY_TITLE', 'DATE_MODIFY', 'PHOTO', 'ORIGIN_ID')
		);
	}

	/**
	 * Handler for PUT request of DAV protocol,
	 * don't forgot add DAV::write to minimal privileges
	 * @param $id
	 * @param CDavICalendarComponent $card
	 * @return array
	 */
	public function PrepareEntityParamsFromVCard($id, $card)
	{
		$fields = parent::PrepareEntityParamsFromVCard($id, $card);
		$nameProperty = $card->GetProperties('N');
		$unParsedName = !empty($nameProperty[0]) ? $nameProperty[0]->Value() : '';
		$nameArray = array_filter(explode(';', $unParsedName));
		$fields['LAST_NAME'] = !empty($nameArray[0]) ? $nameArray[0] : '';
		$fields['NAME'] = !empty($nameArray[1]) ? $nameArray[1] : '';
		$fields['SECOND_NAME'] = !empty($nameArray[2]) ? $nameArray[2] : '';

		return $fields;
	}


	/**
	 * @param $entityParams
	 * @param $fileContent
	 * @param $fileExtension
	 * @return mixed
	 * @internal param $card
	 */
	protected function AttachToEntityImgId($entityParams, $fileContent, $fileExtension)
	{
		if (!isset($entityParams['PHOTO']) || !$entityParams['PHOTO'])
		{
			$entityParams['PHOTO'] = $this->SaveImg($fileContent, $fileExtension);
		}
		return $entityParams;
	}

	/**
	 * @param $id
	 * @return array|bool|false|mixed|null
	 */
	protected function LoadEntityById($id)
	{
		return CAllCrmContact::GetByID($id);

	}

	/**
	 * @param $fields
	 * @return bool|int
	 */
	protected function AddEntity($fields)
	{
		$contact = new CAllCrmContact;
		return $contact->Add($fields);
	}

	/**
	 * @param $entityParams
	 * @param $oldEntityParams
	 * @return array
	 */
	protected function Merge($entityParams, $oldEntityParams)
	{
		$accountId = $this->groupdav->GetRequest()->GetPrincipal()->Id();
		$contactMerger = new \Bitrix\Crm\Merger\ContactMerger($accountId, true);
		$contactMerger->mergeFields($entityParams, $oldEntityParams);
		$oldEntityParams['LAST_NAME'] = !empty($entityParams['LAST_NAME']) ? $entityParams['LAST_NAME'] : $oldEntityParams['LAST_NAME'];
		$oldEntityParams['NAME'] = !empty($entityParams['NAME']) ? $entityParams['NAME'] : $oldEntityParams['NAME'];
		$oldEntityParams['SECOND_NAME'] = !empty($entityParams['SECOND_NAME']) ? $entityParams['SECOND_NAME'] : $oldEntityParams['SECOND_NAME'];
		return $oldEntityParams;
	}

	/**
	 * @param $id
	 * @param $fields
	 * @return bool
	 */
	protected function UpdateEntity($id, $fields)
	{
		$updateContact = new CAllCrmContact;
		return $updateContact->Update($id, $fields);
	}
}