<?php
namespace Bitrix\Tasks\Rest;

class RestManager extends \IRestService
{
	public static function onRestGetModule()
	{
		return ['MODULE_ID'=>'tasks'];
	}
}