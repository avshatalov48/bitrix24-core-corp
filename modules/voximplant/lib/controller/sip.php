<?php


namespace Bitrix\Voximplant\Controller;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\PhoneNumber\Format;
use Bitrix\Main\PhoneNumber\Parser;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Voximplant\Model\CallerIdTable;

class Sip extends Engine\Controller
{
	public function deleteAction($id)
	{
		$viSip = new \CVoxImplantSip();
		$result = $viSip->delete($id);

		if(!$result)
		{
			$this->errorCollection[] = new Error($viSip->GetError()->msg);
			return null;
		}

		return true;
	}
}