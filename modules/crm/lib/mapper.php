<?php
namespace Bitrix\Crm;
class Mapper
{
	protected static $stub = null;
	protected $map = null;

	public function __construct(array $map = null)
	{
		if($map !== null)
		{
			$this->map = $map;
		}
	}

	public function getMapping($name)
	{
		return ($this->map !== null && isset($this->map[$name])) ? $this->map[$name] : $name;
	}
	public static function stub()
	{
		if(self::$stub === null)
		{
			self::$stub = new Mapper();
		}
		return self::$stub;
	}
}