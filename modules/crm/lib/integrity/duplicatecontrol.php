<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
class DuplicateControl
{
	private static $currentSettings = null;
	protected $settings = array();

	protected function __construct(array $settings = null)
	{
		if($settings !== null)
		{
			$this->settings = $settings;
		}
	}
	public static function isControlEnabledFor($entityTypeID)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\NotSupportedException("Entity type ID: '{$entityTypeID}' is not supported in current context");
		}
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);

		//By default control is enabled
		$settings = self::loadCurrentSettings();
		return !isset($settings['enableFor'][$entityTypeName]) || $settings['enableFor'][$entityTypeName] === 'Y';
	}
	public function isEnabledFor($entityTypeID)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\NotSupportedException("Entity ID: '{$entityTypeID}' is not supported in current context");
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		//By default control is enabled
		return !isset($this->settings['enableFor'][$entityTypeName]) || $this->settings['enableFor'][$entityTypeName] === 'Y';
	}
	public function enabledFor($entityTypeID, $enable)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\NotSupportedException("Entity ID: '{$entityTypeID}' is not supported in current context");
		}

		if(!is_bool($enable))
		{
			if(is_numeric($enable))
			{
				$enable = $enable > 0;
			}
			elseif(is_string($enable))
			{
				$enable = strtoupper($enable) === 'Y';
			}
			else
			{
				$enable = false;
			}
		}
		$this->settings['enableFor'][\CCrmOwnerType::ResolveName($entityTypeID)] = $enable ? 'Y' : 'N';
	}
	public static function getCurrent()
	{
		return new DuplicateControl(self::loadCurrentSettings());
	}
	public function save()
	{
		self::$currentSettings = $this->settings;
		\Bitrix\Main\Config\Option::delete('crm', array('name'=>'dup_ctrl'));
		\Bitrix\Main\Config\Option::set('crm', 'dup_ctrl', serialize(self::$currentSettings));
	}
	private static function loadCurrentSettings()
	{
		if(self::$currentSettings === null)
		{
			$s = \Bitrix\Main\Config\Option::get('crm', 'dup_ctrl');
			if(is_string($s) && $s !== '')
			{
				$ary = unserialize($s);
				if(is_array($ary))
				{
					self::$currentSettings = &$ary;
					unset($ary);
				}
			}
			if(!is_array(self::$currentSettings))
			{
				self::$currentSettings = array();
			}
			if(!isset(self::$currentSettings['enableFor']))
			{
				self::$currentSettings['enableFor'] = array();
			}
		}
		return self::$currentSettings;
	}
}