<?php
namespace Bitrix\Crm\Filter;

abstract class EntityDataProvider extends DataProvider
{
	/**
	 * Get specified entity field caption.
	 * @param string $fieldID Field ID.
	 * @return string
	 */
	protected abstract function getFieldName($fieldID);

	/**
	 * Create filter field.
	 * @param string $fieldID Field ID.
	 * @param array|null $params Field parameters (optional).
	 * @return Field
	 */
	protected function createField($fieldID, array $params = null)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		if(!isset($params['name']))
		{
			$params['name'] = $this->getFieldName($fieldID);
		}

		return new Field($this, $fieldID, $params);
	}
}