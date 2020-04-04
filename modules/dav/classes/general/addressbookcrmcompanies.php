<?php


use Bitrix\Main\Localization\Loc;

/**
 * Class CDavCrmCompanies
 */
class CDavCrmCompanies
	extends CDavAddressbookCrmBaseLimited
{

	const RESOURCE_SYNC_SETTINGS_NAME = 'COMPANY';

	/**
	 * CDavCrmCompanies constructor.
	 * @param CDavGroupDav $groupdav
	 */
	public function __construct($groupdav)
	{
		parent::__construct($groupdav);
		$this->SetName(Loc::getMessage('DAV_COMPANIES'));
		$this->SetNamespace(CDavGroupDav::CARDDAV);
		$this->SetUri('crmCompanies');
		$this->SetMinimumPrivileges(array('DAV::read', 'DAV::write'));
		$this->SetMultiFieldEntityId('COMPANY');
	}

	/**
	 * Add checking of some additional cases
	 * @param $principal
	 * @return bool
	 */
	protected function AdditionalPrivilegesCheck($principal)
	{
		return parent::AdditionalPrivilegesCheck($principal)
			&& CAllCrmCompany::CheckExportPermission();
	}

	/**
	 * Map: key=>value vCard properties of entity
	 * @param $company
	 * @return mixed
	 */
	protected function GetVCardDataMap($company)
	{
		$map = parent::GetVCardDataMap($company);
		$map["N"] = $company["TITLE"];
		$map["FN"] = $company["TITLE"];
		$map["UID"] = $company["ID"];
		$map["REV"] = date("Ymd\\THis\\Z", MakeTimeStamp($company["DATE_MODIFY"]));
		$map["URL"] = 'bitrix24://@%';
		$map['IMG'] = !empty($company['LOGO']) ? $company['LOGO'] : '';
		return $map;
	}

	/**
	 * Load companies list
	 * @param $order
	 * @param $filter
	 * @param $selectParams
	 * @param $maxCount
	 * @return bool|CDBResult
	 */
	protected function LoadCrmResourceEntitiesListByParams($order, $filter, $selectParams = array(), $maxCount)
	{
		$filter['PERMISSION'] = array('EXPORT');
		return CCrmCompany::GetListEx(
			$order,
			$filter,
			false,
			$maxCount ? array('nTopCount' => $maxCount) : array(),
			$selectParams ? $selectParams : array('ID', 'TITLE', 'DATE_MODIFY', 'LOGO', 'ORIGIN_ID')
		);
	}

	/**
	 * @param $id
	 * @param CDavICalendarComponent $card
	 * @return array
	 */
	protected function PrepareEntityParamsFromVCard($id, $card)
	{
		$fields = parent::PrepareEntityParamsFromVCard($id, $card);
		$nameProperty = $card->GetProperties('N');
		$unParsedName = !empty($nameProperty[0]) ? $nameProperty[0]->Value() : '';
		$title = implode(' ', array_filter(explode(';', $unParsedName)));
		$fields['TITLE'] = !empty($title) ? $title : '';

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
		if (!isset($entityParams['LOGO']) || !$entityParams['LOGO'])
		{
			$entityParams['LOGO'] = $this->SaveImg($fileContent, $fileExtension);
		}
		return $entityParams;
	}

	/**
	 * @param $id
	 * @return array|bool|false|mixed|null
	 */
	protected function LoadEntityById($id)
	{
		return CAllCrmCompany::GetByID($id);

	}

	/**
	 * @param $fields
	 * @return bool|int
	 */
	protected function AddEntity($fields)
	{
		$company = new CAllCrmCompany;
		return $company->Add($fields);
	}

	/**
	 * @param $entityParams
	 * @param $oldEntityParams
	 * @return array
	 */
	protected function Merge($entityParams, $oldEntityParams)
	{
		$accountId = $this->groupdav->GetRequest()->GetPrincipal()->Id();
		$companyMerger = new \Bitrix\Crm\Merger\CompanyMerger($accountId, true);
		$companyMerger->mergeFields($entityParams, $oldEntityParams);
		$oldEntityParams['TITLE'] = !empty($entityParams['TITLE']) ? $entityParams['TITLE'] : $oldEntityParams['TITLE'];
		return $oldEntityParams;
	}

	/**
	 * @param $id
	 * @param $fields
	 * @return bool
	 */
	protected function UpdateEntity($id, $fields)
	{
		$company = new CAllCrmCompany;
		return $company->Update($id, $fields);
	}
}