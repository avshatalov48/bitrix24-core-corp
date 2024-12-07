<?php


namespace Bitrix\Crm\Service\Display\Field;


class CrmCompanyField extends CrmField
{
	public const TYPE = 'crm_company';

	protected function __construct(string $id)
	{
		parent::__construct($id);
		$this->displayParams = [\CCrmOwnerType::CompanyName => 'Y'];
	}
}
