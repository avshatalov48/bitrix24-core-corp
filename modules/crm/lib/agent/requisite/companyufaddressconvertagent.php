<?php
namespace Bitrix\Crm\Agent\Requisite;

use Bitrix\Crm\Requisite\Conversion\EntityUfAddressConverterFactory;

class CompanyUfAddressConvertAgent extends EntityUfAddressConvertAgent
{
	protected static $optionName = '~CRM_CONVERT_COMPANY_UF_ADDRESSES';

	/** @var CompanyUfAddressConvertAgent|null */
	private static $instance = null;

	/**
	 * @return CompanyUfAddressConvertAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new CompanyUfAddressConvertAgent();
		}
		return self::$instance;
	}

	public function getTotalCount()
	{
		return \CCrmCompany::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}
	public function prepareItemIds($offsetId, $limit)
	{
		$filter = array('CHECK_PERMISSIONS' => 'N');
		if($offsetId > 0)
		{
			$filter['>ID'] = $offsetId;
		}

		$res = \CCrmCompany::GetListEx(
			array('ID' => 'ASC'),
			$filter,
			false,
			array('nTopCount' => $limit),
			array('ID')
		);

		$result = [];

		if(is_object($res))
		{
			while($fields = $res->Fetch())
			{
				$result[] = (int)$fields['ID'];
			}
		}

		return $result;
	}
	protected function getConverterInstance()
	{
		return EntityUfAddressConverterFactory::create(
			\CCrmOwnerType::Company,
			$this->getSourceEntityTypeId(),
			$this->getSourceUserFieldName()
		);
	}
}
