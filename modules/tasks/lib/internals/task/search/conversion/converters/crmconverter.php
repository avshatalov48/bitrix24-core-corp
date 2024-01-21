<?php

namespace Bitrix\Tasks\Internals\Task\Search\Conversion\Converters;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\CRM\Fields\Crm;
use Bitrix\Tasks\Integration\CRM\Fields\Mapper;
use Bitrix\Tasks\Internals\Task\Search\Conversion\AbstractConverter;

class CrmConverter extends AbstractConverter
{
	public function convert(): string
	{
		if (!Loader::includeModule('crm'))
		{
			return '';
		}
		$crmFields = $this->getFieldValue();
		if (!is_array($crmFields))
		{
			return '';
		}

		$items = (new Mapper())->map($crmFields);
		$titles = [];
		foreach ($items as $crm)
		{
			/** @var Crm $crm */
			$titles[] = $crm->getCaption();
		}

		return implode(' ', $titles);
	}

	public static function getFieldName(): string
	{
		return 'UF_CRM_TASK';
	}
}