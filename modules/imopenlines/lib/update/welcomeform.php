<?php

namespace Bitrix\Imopenlines\Update;

use Bitrix\Crm\WebForm\Form;
use Bitrix\Crm\WebForm\Preset;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;

class WelcomeForm
{
	/**
	 * Set default form ID for all existing OL configs with empty WELCOME_FORM_ID
	 */
	public static function setFormIdForExistingConfigs(): string
	{
		if (!Loader::includeModule('crm'))
		{
			$sql = "UPDATE b_imopenlines_config SET USE_WELCOME_FORM = 'N'";
			HttpApplication::getConnection()->query($sql);

			return "";
		}

		$defaultWelcomeFormId = (new Preset)->getInstalledId('imol_reg');
		if ($defaultWelcomeFormId && (new Form($defaultWelcomeFormId))->isActive())
		{
			$sql = "UPDATE b_imopenlines_config SET WELCOME_FORM_ID = '" . $defaultWelcomeFormId . "' WHERE WELCOME_FORM_ID IS NULL";
			HttpApplication::getConnection()->query($sql);
		}

		return "";
	}
}