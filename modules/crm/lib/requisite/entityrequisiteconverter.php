<?php
namespace Bitrix\Crm\Requisite;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

abstract class EntityRequisiteConverter
{
	/** @var int */
	protected $entityTypeID = 0;

	/**
	 * @param int $entityTypeID Entity type ID.
	 */
	protected function __construct($entityTypeID)
	{
		$this->entityTypeID = $entityTypeID;
	}

	/**
	 * Get entity type ID
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}

	/**
	 * Check converter settings
	 * @return void
	 * @throws RequisiteConvertException
	 */
	abstract public function validate();

	/**
	 * Process entity. Convert invoice requisites to entity requisites
	 * @param int $entityID Entity ID.
	 * @throws InvoiceRequisiteConvertException
	 * @return bool
	 */
	abstract public function processEntity($entityID);
	/**
	 * Complete convertion process
	 * @return void
	 */
	abstract public function complete();

	public static function getIntroMessage(array $params)
	{
		$replace = array();
		if(isset($params['EXEC_ID']))
		{
			$replace['#EXEC_ID#'] = $params['EXEC_ID'];
		}

		if(isset($params['EXEC_URL']))
		{
			$replace['#EXEC_URL#'] = $params['EXEC_URL'];
		}

		if(isset($params['SKIP_ID']))
		{
			$replace['#SKIP_ID#'] = $params['SKIP_ID'];
		}

		if(isset($params['SKIP_URL']))
		{
			$replace['#SKIP_URL#'] = $params['SKIP_URL'];
		}

		Loc::loadMessages(__FILE__);
		return GetMessage('CRM_ENTITY_RQ_CONV_INTRO', $replace);
	}
}