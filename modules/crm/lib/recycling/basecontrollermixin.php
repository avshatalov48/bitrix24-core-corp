<?php
namespace Bitrix\Crm\Recycling;

trait BaseControllerMixin
{
	/**
	 * Get Entity Type ID
	 * @return int
	 */
	abstract public function getEntityTypeID();

	/**
	 * Get Entity Type Name
	 * @return string
	 */
	abstract public function getEntityTypeName();

	/**
	 * Get Suspended Entity Type ID
	 * @return int
	 */
	abstract public function getSuspendedEntityTypeID();

	/**
	 * Get Suspended Entity Type Name
	 * @return string
	 */
	abstract public function getSuspendedEntityTypeName();
}