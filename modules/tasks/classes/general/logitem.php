<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2015 Bitrix
 * 
 * Just a wrapper for CTaskLog with permission access control and rest support
 * @access private
 */

final class CTaskLogItem extends CTaskSubItemAbstract
{
	protected function fetchListFromDb($taskData, $arOrder = array('ID' => 'ASC'), $arFilter = array())
	{
		CTaskAssert::assertLaxIntegers($taskData['ID']);

		if(!isset($arOrder))
			$arOrder = array('ID' => 'ASC');

		if(!is_array($arFilter))
			$arFilter = array();

		$arFilter['TASK_ID'] = (int) $taskData['ID'];

		$arItemsData = array();
		/** @noinspection PhpDeprecationInspection */
		$rsData = CTaskLog::GetList(
			$arOrder,
			$arFilter
		);

		if ( ! is_object($rsData) )
			throw new Exception();

		$i = 1;
		while ($arData = $rsData->fetch())
		{
			$arData['ID'] = $i; // emulate ID field that is required by CTaskSubItemAbstract::constructWithPreloadedData()
			$arItemsData[] = $arData;

			$i++;
		}

		return (array($arItemsData, $rsData));
	}

	protected function fetchDataFromDb($taskId, $itemId)
	{
		throw new \Bitrix\Main\NotImplementedException();
	}

	public static function runRestMethod($executiveUserId, $methodName, $args,
		/** @noinspection PhpUnusedParameterInspection */ $navigation)
	{
		static $arManifest = null;
		static $arMethodsMetaInfo = null;

		if ($arManifest === null)
		{
			$arManifest = self::getManifest();
			$arMethodsMetaInfo = $arManifest['REST: available methods'];
		}

		// Check and parse params
		CTaskAssert::assert(isset($arMethodsMetaInfo[$methodName]));
		$arMethodMetaInfo = $arMethodsMetaInfo[$methodName];
		$argsParsed = CTaskRestService::_parseRestParams('ctasklogitem', $methodName, $args);

		$returnValue = null;
		if (isset($arMethodMetaInfo['staticMethod']) && $arMethodMetaInfo['staticMethod'])
		{
			if ($methodName === 'list')
			{
				$taskId = $argsParsed[0];
				$order = $argsParsed[1];
				$filter = $argsParsed[2];
				$oTaskItem = CTaskItem::getInstance($taskId, $executiveUserId);

				list($items, $rsData) = self::fetchList($oTaskItem, $order, $filter);

				$returnValue = array();
				foreach ($items as $item)
					$returnValue[] = $item->getData(false);
			}
			else
			{
				$returnValue = call_user_func_array(array('self', $methodName), $argsParsed);
			}
		}
		else
		{
			$taskId     = array_shift($argsParsed);
			$itemId     = array_shift($argsParsed);
			$oTaskItem  = CTaskItem::getInstance($taskId, $executiveUserId);
			$item  = new self($oTaskItem, $itemId);

			$returnValue = call_user_func_array(array($item, $methodName), $argsParsed);
		}

		return (array($returnValue, null));
	}


	/**
	 * This method is not part of public API.
	 * Its purpose is for internal use only.
	 * It can be changed without any notifications
	 * 
	 * @access private
	 */
	public static function getManifest()
	{
		$arWritableKeys = 		array();
		$arSortableKeys = 		array('USER_ID', 'TASK_ID', 'FIELD', 'CREATED_DATE');
		$arAggregatableKeys = 	array();
		$arDateKeys = 			array('CREATED_DATE');
		$arReadableKeys = array_merge(
			$arDateKeys,
			$arSortableKeys,
			$arWritableKeys,
			array('FROM_VALUE', 'TO_VALUE')
		);
		$arFiltrableKeys = array('TASK_ID', 'USER_ID', 'CREATED_DATE', 'FIELD');

		return(array(
			'Manifest version' => '1.0',
			'Warning' => 'don\'t rely on format of this manifest, it can be changed without any notification',
			'REST: shortname alias to class' => 'logitem',
			'REST: writable logitem data fields'   =>  $arWritableKeys,
			'REST: readable logitem data fields'   =>  $arReadableKeys,
			'REST: sortable logitem data fields'   =>  $arSortableKeys,
			'REST: filterable logitem data fields' =>  $arFiltrableKeys,
			'REST: date fields' =>  $arDateKeys,
			'REST: available methods' => array(
				'getmanifest' => array(
					'staticMethod' => true,
					'params'       => array()
				),
				'list' => array(
					'staticMethod'         =>  true,
					'mandatoryParamsCount' =>  1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'arOrder',
							'type'        => 'array',
							'allowedKeys' => $arSortableKeys
						),
						array(
							'description' => 'arFilter',
							'type'        => 'array',
							'allowedKeys' => $arFiltrableKeys,
							'allowedKeyPrefixes' => array(
								'!', '<=', '<', '>=', '>'
							)
						),
					),
					'allowedKeysInReturnValue' => $arReadableKeys,
					'collectionInReturnValue'  => true,
				)
			)
		));
	}
}
