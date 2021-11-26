<?php

namespace Bitrix\Crm;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;

class PresetListController extends Controller
{
	public function changeCurrentCountryAction(bool $change): bool
	{
		$needDeleteOption = !$change;

		if ($change)
		{
			if (!Loader::includeModule('crm'))
			{
				throw new SystemException("Can\'t include module CRM!");
			}

			$preset = EntityPreset::getSingleInstance();
			$countryId = (int)Option::get('crm', '~crm_requisite_current_country_can_change', 0);
			$result = $preset->changeCurrentCountry($countryId);
			if ($result->isSuccess())
			{
				$needDeleteOption = true;
			}
			else
			{
				$errorMessages = $result->getErrorMessages();
				$errorMessage = 'Unknown error!';
				if (count($errorMessages) > 0)
				{
					$errorMessage = $errorMessages[0];
				}

				throw new SystemException($errorMessage);
			}
		}

		if ($needDeleteOption)
		{
			Option::delete('crm', ['name' => '~crm_requisite_current_country_can_change']);
		}

		return true;
	}
}