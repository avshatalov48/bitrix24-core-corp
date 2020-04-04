<?php
namespace Bitrix\Crm\Synchronization;

use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class UserFieldSynchronizationException extends Main\SystemException
{
	const GENERAL = 10;
	const CREATE_FAILED = 20;

	/** @var array $field */
	protected $field = null;
	/** @var \CApplicationException $error */
	protected $error = null;

	public function __construct(array $field, \CApplicationException $error = null, $code = 0, $file = '', $line = 0, \Exception $previous = null)
	{
		$this->field = $field;
		$this->error = $error;

		$message = $error !== null ? $error->GetString() : '';
		if($code === self::CREATE_FAILED)
		{
			$name = isset($field['FIELD_NAME']) ? $field['FIELD_NAME'] : '';
			$typeName = isset($field['USER_TYPE_ID']) ? $field['USER_TYPE_ID'] : '';
			$entityType = isset($field['ENTITY_ID']) ? $field['ENTITY_ID'] : '';
			if($message === '')
			{
				$message = Main\Localization\Loc::getMessage(
					'CRM_UF_SYNC_EXCEPTION_CREATE_FAILED',
					array('#NAME#' => $name, '#TYPE_NAME#' => $typeName, '#ENTITY_TYPE#' => $entityType)
				);
			}
		}
		elseif($message === '')
		{
			$message = Main\Localization\Loc::getMessage('CRM_UF_SYNC_EXCEPTION_GENERAL');
		}

		parent::__construct($message, $code, $file, $line, $previous);
	}

	public function getField()
	{
		return $this->field;
	}

	public function getError()
	{
		return $this->error;
	}
}