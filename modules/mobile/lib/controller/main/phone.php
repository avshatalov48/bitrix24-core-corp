<?php

namespace Bitrix\Mobile\Controller\Main;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Main\PhoneNumber\Parser;

class Phone extends Controller
{
	public function getDefaultCountryAction()
	{
		return Parser::getDefaultCountry();
	}
}
