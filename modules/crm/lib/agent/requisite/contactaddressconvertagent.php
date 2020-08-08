<?php
namespace Bitrix\Crm\Agent\Requisite;

use Bitrix\Crm\Requisite\Conversion\EntityAddressConverterFactory;
use CCrmOwnerType;

class ContactAddressConvertAgent extends EntityAddressConvertAgent
{
	protected static $optionName = '~CRM_CONVERT_CONTACT_ADDRESSES';

	/** @var ContactAddressConvertAgent|null */
	private static $instance = null;

	/**
	 * @return ContactAddressConvertAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new ContactAddressConvertAgent();
		}
		return self::$instance;
	}

	public function getTotalCount()
	{
		return \CCrmContact::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}
	public function prepareItemIds($offsetId, $limit)
	{
		$filter = array('CHECK_PERMISSIONS' => 'N');
		if($offsetId > 0)
		{
			$filter['>ID'] = $offsetId;
		}

		$res = \CCrmContact::GetListEx(
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
		return EntityAddressConverterFactory::create(CCrmOwnerType::Contact);
	}
}
