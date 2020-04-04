<?
namespace Bitrix\Tasks\Util\Type;

use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Type\StructureChecker;

class ArrayOption
{
	protected $optionName = '';
	protected $rules = array();
	protected $type = null;

	protected $checker = null;

	const TYPE_USER = 1;
	const TYPE_GLOBAL = 2;

	public function __construct($optionName = '', $rules = array(), $type = null)
	{
		$this->optionName = trim((string) $optionName);
		if(is_array($rules))
		{
			$this->rules = $rules;
		}

		if($type !== null)
		{
			$this->type = $type;
		}
		else
		{
			$this->type = static::TYPE_USER;
		}
	}

	/**
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 *
	 * @deprecated This is the default option name. Better to use from $this->optionName
	 */
	protected static function getFilterOptionName()
	{
		throw new \Bitrix\Main\NotImplementedException();
	}

	protected function getRules()
	{
		return $this->rules;
	}

	public function setOptionName($name)
	{
		$this->optionName = $name;
	}

	public function set(array $value)
	{
		$name = $this->getOptionName();
		$value = \Bitrix\Tasks\Util\Type::serializeArray($this->check($value));

		if($this->type == static::TYPE_USER)
		{
			User::setOption($name, $value);
		}
		else
		{
			Util::setOption($name, $value);
		}
	}

	public function get()
	{
		$value = $this->check(
			\Bitrix\Tasks\Util\Type::unSerializeArray($this->fetchOptionValue()),
			!$this->checkOptionValueExists()
		);

		return $value;
	}

	public function remove()
	{
		$name = $this->getOptionName();

		if($this->type == static::TYPE_USER)
		{
			User::unSetOption($name);
		}
		else
		{
			Util::unSetOption($name);
		}
	}

	public function check($value, $initial = false)
	{
		// todo: use object pool here for StructureChecker
		if(!$this->checker)
		{
			$this->checker = new StructureChecker($this->getRules());
		}

		return $this->checker->check($value, $initial);
	}

	public function removeForAllUsers()
	{
		$name = $this->getOptionName();

		if($this->type == static::TYPE_USER)
		{
			User::unSetOptionForAll($name);
		}
		else
		{
			Util::unSetOption($name);
		}
	}

	protected function fetchOptionValue()
	{
		$name = $this->getOptionName();

		if($this->type == static::TYPE_USER)
		{
			return User::getOption($name);
		}
		else
		{
			return Util::getOption($name);
		}
	}

	protected function checkOptionValueExists()
	{
		return $this->fetchOptionValue() != '';
	}

	protected function getOptionName()
	{
		if($this->optionName != '')
		{
			return $this->optionName;
		}

		return static::getFilterOptionName();
	}
}