<?php
namespace Bitrix\Crm\Settings;
use Bitrix\Main;
class BooleanSetting
{
	/** @var string */
	protected $name = '';
	/** @var bool  */
	protected $default = false;
	function __construct($name, $default = false)
	{
		$this->name = $name;
		$this->default = (bool)$default;
	}

	public function set($value)
	{
		$value = (bool)$value;
		if($value === $this->default)
		{
			Main\Config\Option::delete('crm', array('name' => $this->name));
		}
		else
		{
			Main\Config\Option::set('crm', $this->name, $value ? 'Y' : 'N', '');
		}
	}

	public function get()
	{
		return strtoupper(Main\Config\Option::get('crm', $this->name, $this->default ? 'Y' : 'N', '')) === 'Y';
	}
}
