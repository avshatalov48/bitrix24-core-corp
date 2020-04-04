<?php


namespace Bitrix\Disk\Rest\Entity;


abstract class Base
{
	/**
	 * Gets all fields (DataManager fields).
	 * @return array
	 */
	abstract public function getDataManagerFields();

	/**
	 * Gets fields which entity can show in response.
	 * @return array
	 */
	abstract public function getFieldsForShow();

	/**
	 * Gets fields which entity can filter in getList().
	 * @return array
	 */
	abstract public function getFieldsForFilter();

	/**
	 * Gets fields which Externalizer or Internalizer should modify.
	 * @return array
	 */
	public function getFieldsForMap()
	{
		return array();
	}

	/**
	 * Returns field descriptions for rendering in REST method fields.
	 * @return array
	 */
	public function getFields()
	{
		$dataManagerFields = $this->getDataManagerFields();
		$fieldsForFilter = $this->getFieldsForFilter();
		$fieldsForShow = $this->getFieldsForShow();

		$dataManagerFields = array_merge(
			array_intersect_key($dataManagerFields, $fieldsForShow),
			array_intersect_key($dataManagerFields, $fieldsForFilter)
		);

		foreach($dataManagerFields as $fieldName => $fieldData)
		{
			$dataManagerFields[$fieldName] = array(
				'TYPE' => $fieldData['data_type'],
				'USE_IN_FILTER' => !empty($fieldsForFilter[$fieldName]),
				'USE_IN_SHOW' => !empty($fieldsForShow[$fieldName]),
			);
		}
		unset($fieldName, $fieldData);

		return $dataManagerFields;
	}
}