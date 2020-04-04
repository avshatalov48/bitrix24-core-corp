<?php
namespace Bitrix\Crm\Entry;
use Bitrix\Main;
class AddException extends EntryException
{
	public function __construct($entityTypeID, array $errorMessages, $code = 0, $file = '', $line = 0, \Exception $previous = null)
	{
		parent::__construct($entityTypeID, 0, $errorMessages, $code, $file, $line, $previous);
	}
}