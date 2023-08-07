<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Store\Document;

use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Product;
use Bitrix\Main\Localization\Loc;

class Element extends Product
{
	public function getFields()
	{
		if (!isset($this->fields))
		{
			$this->fields = parent::getFields();
			$this->fields['COMMENT'] = [
				'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_STORE_DOCUMENT_ELEMENT_COMMENT_TITLE'),
			];
		}

		return $this->fields;
	}
}
