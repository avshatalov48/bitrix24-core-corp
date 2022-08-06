<?php

namespace Bitrix\Crm\Item;

use Bitrix\Crm\Item;
use Bitrix\Crm\LeadTable;
use Bitrix\Main\Localization\Loc;

/**
 * @method string|null getCompanyTitle()
 * @method Item setCompanyTitle(string $companyTitle)
 */
class Lead extends Item
{
	public const FIELD_NAME_COMPANY_TITLE = 'COMPANY_TITLE';
	public const FIELD_NAME_DATE_CLOSED = 'DATE_CLOSED';

	//lead entity field names for common fields
	public const FIELD_NAME_STATUS_ID = 'STATUS_ID';
	public const FIELD_NAME_STATUS_DESCRIPTION = 'STATUS_DESCRIPTION';
	public const FIELD_NAME_CREATED_BY_ID = 'CREATED_BY_ID';
	public const FIELD_NAME_MODIFY_BY_ID = 'MODIFY_BY_ID';
	public const FIELD_NAME_DATE_CREATE = 'DATE_CREATE';
	public const FIELD_NAME_DATE_MODIFY = 'DATE_MODIFY';

	protected function getItemReferenceFieldNameInProduct(): string
	{
		return 'LEAD_OWNER';
	}

	public function getCategoryId(): ?int
	{
		return null;
	}

	protected function disableCheckUserFields(): void
	{
		LeadTable::disableUserFieldsCheck();
	}

	public function getTitlePlaceholder(): ?string
	{
		$number = ($this->getId() > 0) ? $this->getId() : '';

		return Loc::getMessage('CRM_LEAD_DEFAULT_TITLE_TEMPLATE', ['%NUMBER%' => $number]);
	}

	protected function getExternalizableFieldNames(): array
	{
		return array_merge(
			parent::getExternalizableFieldNames(),
			[
				$this->getEntityFieldNameByMap(Item::FIELD_NAME_OBSERVERS),
			],
		);
	}
}
