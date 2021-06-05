<?php
namespace Bitrix\Crm\Integration;
class StorageType
{
	const Undefined = 0;
	const File = 1;
	const WebDav = 2;
	const Disk = 3;

	const FileName = 'file';
	const WebDavName = 'webdav';
	const DiskName = 'disk';

	private static $defaultTypeID = null;

	public static function isDefined($typeID)
	{
		$typeID = (int)$typeID;
		return $typeID > self::Undefined && $typeID <= self::Disk;
	}
	public static function getDefaultTypeID()
	{
		if(self::$defaultTypeID === null)
		{
			if(IsModuleInstalled('disk') && \COption::GetOptionString('disk', 'successfully_converted', 'N') === 'Y')
			{
				self::$defaultTypeID = self::Disk;
			}
			elseif(IsModuleInstalled('webdav'))
			{
				self::$defaultTypeID = self::WebDav;
			}
			else
			{
				self::$defaultTypeID = self::File;
			}
		}
		return self::$defaultTypeID;
	}

	public static function getAllTypes(): array
	{
		return [
			self::File,
			self::WebDav,
			self::Disk,
		];
	}
	public static function resolveName($typeID)
	{
		$typeID = (int)$typeID;
		switch($typeID)
		{
			case self::File:
				return self::FileName;
			case self::WebDav:
				return self::WebDavName;
			case self::Disk:
				return self::DiskName;
		}
		return '';
	}
	public static function resolveID($typeName)
	{
		switch($typeName)
		{
			case self::FileName:
				return self::File;
			case self::WebDavName:
				return self::WebDav;
			case self::DiskName:
				return self::Disk;
		}
		return self::Undefined;
	}
}