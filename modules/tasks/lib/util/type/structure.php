<?
namespace Bitrix\Tasks\Util\Type;

class Structure
{
	protected $data = null;
	protected $rules = array();

	public function __construct($data = array(), $rules = array())
	{
		$this->setRules($rules);

		if(is_array($data) && !empty($data))
		{
			$this->data = $this->check($data);
		}
		else
		{
			$this->data = $this->check(array(), true);
		}
	}

	public function set($data, $point = '')
	{
		$current = $this->data;

		$ref =& static::deReference($point, $current);
		$ref = $data;

		$this->data = $this->check($current);
	}

	public function get($point = '')
	{
		return static::deReference($point, $this->data);
	}

	public function toArray()
	{
		return $this->get();
	}

	public function exportFlat($point = '', $delimiter = '.')
	{
		$dictionary = array();
		$this->makeDictionary($this->get($point), $dictionary, $delimiter);

		return $dictionary;
	}

	private function makeDictionary($data, &$dict, $delimiter = '.', $namePath = array(), $depth = 0)
	{
		if($depth > 10) // smth went wrong
		{
			return;
		}

		if(is_array($data))
		{
			foreach($data as $k => $v)
			{
				array_push($namePath, $k);
				if(is_array($v))
				{
					$this->makeDictionary($v, $dict, $delimiter, $namePath, $depth + 1);
				}
				else
				{
					$dict[implode($delimiter, $namePath)] = $v;
				}
				array_pop($namePath);
			}
		}
	}

	protected function check($value, $initial = false)
	{
		static $checker;

		if(!$checker)
		{
			$checker = new StructureChecker($this->getRules());
		}

		return $checker->check($value, $initial);
	}

	public function getRules()
	{
		return $this->rules;
	}

	public function setRules($rules)
	{
		$this->rules = (array) $rules;
	}

    private static function &deReference($name, &$ctx)
	{
		$name = (string) $name;
		if(!is_array($ctx))
		{
			return null;
		}

		if($name == '')
		{
			return $ctx;
		}

		$name = explode('.', $name);
		$len = count($name);
		$top =& $ctx;

		for($k = 0; $k < $len; $k++)
		{
			if($top === null)
			{
				return null;
			}

			$name[$k] = trim($name[$k]);
			if($name[$k] == '')
			{
				return null;
			}

			if($top[$name[$k]] === null)
			{
				return null;
			}

			$top =& $top[$name[$k]];
		}

		return $top;
	}

	public static function isA($object)
	{
		return is_a($object, static::getClass());
	}

	protected static function getClass()
	{
		return get_called_class();
	}
}