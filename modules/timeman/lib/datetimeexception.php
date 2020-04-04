<?php
namespace Bitrix\Timeman;

use Bitrix\Main\Loader;
use Bitrix\Rest\RestException;

Loader::includeModule('rest');

class DateTimeException extends RestException
{
	const ERROR_WRONG_DATETIME_FORMAT = 'WRONG_DATETIME_FORMAT';
	const ERROR_WRONG_DATETIME = 'WRONG_DATETIME';
}