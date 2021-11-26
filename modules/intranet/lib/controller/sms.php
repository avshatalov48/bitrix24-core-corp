<?
namespace Bitrix\Intranet\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class SMS extends \Bitrix\Main\Engine\Controller
{
	public function sendSmsForAppAction($phone)
	{
		if (empty($phone))
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_SMS_NO_PHONE'), 'INTRANET_CONTROLLER_SMS_NO_PHONE'));
			return null;
		}

		if (Loader::includeModule('socialservices'))
		{
			\Bitrix\Socialservices\Network::sendMobileApplicationLink($phone, LANGUAGE_ID);
		}
	}
}
