<?php
namespace Bitrix\Crm\Statistics;
use Bitrix\Main;

interface StatisticEntry
{
	/**
	* Get entity type ID
	* @return string
	*/
	public function getEntityTypeID();
	/**
	* Get entry type name
	* @return string
	*/
	public function getTypeName();
	/**
	* Check if entry is valid
	* @return boolean
	*/
	public function isValid();
	/**
	* Get count of busy slots
	* @return integer
	*/
	public function getBusySlotCount();
	/**
	* Get slots data
	* @return array
	*/
	public function getSlotInfos();
	/**
	* Get fields data
	* @param string $langID Language ID.
	* @return array
	*/
	public function getSlotFieldInfos($langID = '');
	/**
	* Get binding map
	* @return StatisticFieldBindingMap
	*/
	public function getSlotBindingMap();
	/**
	* Set binding map
	* @param StatisticFieldBindingMap $srcBindingMap Binding map.
	* @return void
	*/
	public function setSlotBindingMap(StatisticFieldBindingMap $srcBindingMap);
	/**
	* Prepare binging data
	* @return array
	*/
	public function prepareSlotBingingData();
	/**
	* Prepare builder data
	* @return array
	*/
	public function prepareBuilderData();
}