<?
IncludeModuleLangFile(__FILE__);

class CListFieldType
{
	const IS_READONLY = true;
	const NOT_READONLY = false;

	const IS_FIELD = true;
	const NOT_FIELD = false;

	private $id;
	private $name;
	private $is_field;
	private $is_readonly;

	function __construct($id, $name, $is_field, $is_readonly)
	{
		$this->id = (string)$id;
		$this->name = (string)$name;
		$this->is_field = $is_field == CListFieldType::IS_FIELD;
		$this->is_readonly = $is_readonly == CListFieldType::IS_READONLY;
	}

	function IsField()
	{
		return $this->is_field;
	}

	function IsReadonly()
	{
		return $this->is_readonly;
	}

	function GetName()
	{
		return $this->name;
	}

	function GetID()
	{
		return $this->id;
	}
}
?>