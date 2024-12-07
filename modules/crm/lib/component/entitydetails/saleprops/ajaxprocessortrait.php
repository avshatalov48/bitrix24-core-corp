<?php

namespace Bitrix\Crm\Component\EntityDetails\SaleProps;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Trait AjaxProcessorTrait
 * @package Bitrix\Crm\Component\EntityDetails\SaleProps
 */
trait AjaxProcessorTrait
{
	protected function sortPropertiesAction()
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		$allowConfig = $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
		if (!$allowConfig)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
			return;
		}

		$propertyList = $this->request['PROPERTIES'];
		if (empty($propertyList) || !is_array($propertyList))
		{
			return;
		}

		$propertiesData = \Bitrix\Crm\Order\Property::getList(
			array(
				'filter' => array('ACTIVE' => 'Y'),
				'select' => array('ID', 'SORT'),
				'order' => array('SORT')
			)
		);
		$sortList = [];
		while ($property = $propertiesData->fetch())
		{
			if (in_array($property['ID'], $propertyList))
			{
				$sortList[] = $property['SORT'];
			}
		}

		foreach ($propertyList as $key => $id)
		{
			$id = (int)$id;
			if ($id > 0 && $sortList[$key] > 0)
			{
				\Bitrix\Sale\Internals\OrderPropsTable::update($id, array('SORT' => $sortList[$key]));
			}
		}
	}

	/**
	 * @return array
	 */
	protected function preparePropertyFiles()
	{
		$files = [];
		foreach ($_FILES as $id => $fileData)
		{
			$propertyId = mb_substr($id, 9);
			if ($propertyId > 0 && !isset($_FILES[$propertyId]['DELETE']))
			{
				foreach ($fileData as $key => $value)
				{
					$files['PROPERTIES'][$key][$propertyId] = $value;
				}
			}
		}

		return $files;
	}

	/**
	 * @param $formData
	 * @return array
	 */
	protected function getPropertiesField($formData)
	{
		$result = [];

		$props = array_filter(
			$formData,
			function($k){
				return mb_substr($k, 0, 9) == 'PROPERTY_';
			},
			ARRAY_FILTER_USE_KEY
		);

		if(!empty($props) && is_array($props))
		{
			foreach($props as $id => $value)
			{
				$propId = mb_substr($id, 9);

				if (isset($this->request[$id]))
				{
					$result[mb_substr($id, 9)] = $this->request[$id];
				}
				elseif ((int)$propId > 0 || (mb_substr($propId, 0, 1) == 'n'))
				{
					$result[mb_substr($id, 9)] = $value;
				}
			}
		}
		$files = $this->preparePropertyFiles();

		if (!empty($files) && is_array($files['PROPERTIES']['name']))
		{
			foreach ($files['PROPERTIES']['name'] as $key => $value)
			{
				if (!is_array($value))
				{
					$result[$key] = [
						'ID' => ''
					];
				}
			}
		}

		return $result;
	}

	protected function savePropertyConfigAction()
	{
		$allowConfig = $this->userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
		if (!$allowConfig)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
			return;
		}

		$id = isset($this->request['PROPERTY_ID']) ? max((int)$this->request['PROPERTY_ID'], 0) : 0;
		if ($id <= 0)
		{
			$this->addError('PROPERTY NOT FOUND');
			return;
		}

		$updateParams = [];
		$propertyData = \Bitrix\Sale\Internals\OrderPropsTable::getById($id);
		if ($property = $propertyData->fetch())
		{
			$updateParams['SETTINGS'] = $property['SETTINGS'];
		}
		else
		{
			$this->addError('PROPERTY NOT FOUND');
			return;
		}

		if (isset($this->request['CONFIG']['NAME']))
		{
			$updateParams['NAME'] = $this->request['CONFIG']['NAME'];
		}
		if (is_array($this->request['CONFIG']['SETTINGS']))
		{
			$updateParams['SETTINGS']['SHOW_ALWAYS'] = ($this->request['CONFIG']['SETTINGS']['SHOW_ALWAYS'] === 'Y') ? 'Y' : 'N';
			$updateParams['SETTINGS']['IS_HIDDEN'] = ($this->request['CONFIG']['SETTINGS']['IS_HIDDEN'] === 'Y') ? 'Y' : 'N';
		}

		if (!empty($updateParams))
		{
			$result = \Bitrix\Sale\Internals\OrderPropsTable::update($id, $updateParams);

			if ($result->isSuccess())
			{
				$this->addData(['PROPERTY_ID' => $id]);
			}
			else
			{
				$this->addError($result->getErrors());
			}
		}
	}
}
