<?php
namespace Bitrix\Crm\Agent;

class AgentBase
{
	public static function run()
	{
		return static::doRun() ? get_called_class().'::run();' : '';
	}
	public static function doRun()
	{
		return false;
	}
}