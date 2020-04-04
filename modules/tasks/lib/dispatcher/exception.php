<?
namespace Bitrix\Tasks\Dispatcher;

class Exception	extends \Bitrix\Tasks\Exception
{
	public function getDefaultMessage()
	{
		return 'Dispatcher failure';
	}
};

class BadQueryException extends Exception
{
	public function getDefaultMessage()
	{
		return 'Dispatcher failure: bad query';
	}
};