<?php
namespace Bitrix\Crm\Widget\Data;
use Bitrix\Main;

class ActivityProviderStatus extends DataSource
{
	const TYPE_NAME = 'ACTIVITY_PROVIDER_STATUS';

	/**
	 * @return string
	 */
	public function getTypeName()
	{
		return self::TYPE_NAME;
	}

	/**
	 * @param array $params
	 * @return array
	 */
	public function getList(array $params)
	{
		/** @var array $select */
		$select = isset($params['select']) && is_array($params['select']) ? $params['select'] : array();
		$name = '';
		if(!empty($select))
		{
			$selectItem = $select[0];
			if(isset($selectItem['name']))
			{
				$name = $selectItem['name'];
			}
		}

		if($name === '')
		{
			return array();
		}

		$provider = \CCrmActivity::GetProviderById($name);
		if (!$provider)
		{
			return array();
		}

		$item = array();
		$anchor = $provider::getStatusAnchor();

		if (isset($anchor['HTML']) && strlen($anchor['HTML']) > 0)
		{
			$item = array(
				'html' => (string)$anchor['HTML']
			);
		}
		else
		{
			$item = array(
				'text' => isset($anchor['TEXT']) ? (string)$anchor['TEXT'] : '',
				'url' => isset($anchor['URL']) ? (string)$anchor['URL'] : '', 
			);
		}
		
		return array($item);
	}

	/**
	 * Prepare permission SQL.
	 * @return string|boolean
	 */
	protected function preparePermissionSql()
	{
		return '';
	}
}