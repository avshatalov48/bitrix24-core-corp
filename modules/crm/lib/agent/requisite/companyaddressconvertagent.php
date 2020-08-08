<?php
namespace Bitrix\Crm\Agent\Requisite;

use Bitrix\Crm\Requisite\Conversion\EntityAddressConverterFactory;
use CCrmOwnerType;

class CompanyAddressConvertAgent extends EntityAddressConvertAgent
{
	protected static $optionName = '~CRM_CONVERT_COMPANY_ADDRESSES';

	/** @var CompanyAddressConvertAgent|null */
	private static $instance = null;

	/**
	 * @return CompanyAddressConvertAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new CompanyAddressConvertAgent();
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
		return EntityAddressConverterFactory::create(CCrmOwnerType::Company);
	}
}
