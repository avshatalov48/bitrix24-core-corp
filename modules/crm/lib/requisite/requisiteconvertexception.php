<?php
namespace Bitrix\Crm\Requisite;
use Bitrix\Main;
abstract class RequisiteConvertException extends Main\SystemException
{
	/**
	 * Get localized error message
	 * @return string
	 */
	public abstract function getLocalizedMessage();
}