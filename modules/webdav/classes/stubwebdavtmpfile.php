<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CWebDavStubTmpFile extends CWebDavTmpFile
{
	public static function getList(array $order = array(), array $filter = array())
	{
		return false;
	}

	protected function deleteRow()
	{
		return true;
	}

	protected function deleteTmpFile()
	{
		return;
	}

	protected function existsFile()
	{
		return file_exists($this->getAbsolutePath()) && is_file($this->getAbsolutePath());
	}

	public function getAbsolutePath()
	{
		return $this->path;
	}

	public static function getOne($name)
	{
		return false;
	}

	/**
	 * @param $name
	 * @return bool|CWebDavTmpFile
	 */
	public static function buildByName($name)
	{
		return false;
	}

	public function save()
	{
		return false;
	}
}