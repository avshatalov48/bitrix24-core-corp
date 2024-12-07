<?php

namespace Bitrix\Sign\Controllers\V1\B2e;

use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute;
use Bitrix\Sign\Engine\Controller;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sign\Service;

class Fields extends Controller
{
	#[Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_READ)]
	public function loadAction(array $options = []): array
	{
		if (!\CModule::IncludeModule('crm'))
		{
			$this->addError(new Main\Error('Module `crm` is not installed'));
			return [];
		}

		$crmFieldsData = (new Crm\Controller\Form\Fields\Selector())->getDataAction($options);
		$crmFieldsData['fields'] = array_merge(
			$crmFieldsData['fields'],
			Service\Container::instance()->getServiceProfileProvider()->getFieldsForSelector()
		);

		return $crmFieldsData;
	}
}
