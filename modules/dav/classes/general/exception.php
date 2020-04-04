<?
IncludeModuleLangFile(__FILE__);

class CDavArgumentException
	extends Exception
{
    private $argumentName = '';
    private $argumentTitle = '';

	public function __construct($message, $argumentName = '', $argumentTitle = '')
	{
		parent::__construct($message, 10001);
		$this->argumentName = $argumentName;
		$this->argumentTitle = $argumentTitle;
	}

	public function GetArgumentName()
	{
		return $this->argumentName;
	}

	public function GetArgumentTitle()
	{
		return $this->argumentTitle;
	}
}

class CDavArgumentNullException
	extends CDavArgumentException
{
	public function __construct($argumentName, $argumentTitle = '')
	{
		if (empty($argumentTitle))
			$argumentTitle = $argumentName;
		$message = str_replace("#PARAM#", htmlspecialcharsbx($argumentTitle), GetMessage("DAVCGERR_NULL_ARG"));
		parent::__construct($message, $argumentName, $argumentTitle);
		$this->code = "10002";
	}
}

class CDavArgumentOutOfRangeException
	extends CDavArgumentException
{
	private $possibleValues = null;

	public function __construct($argumentName, $argumentTitle = '', $possibleValues = array())
	{
		if (empty($argumentTitle))
			$argumentTitle = $argumentName;

		if (!is_array($possibleValues))
			$possibleValues = array($possibleValues);

		$str = '';
		foreach ($possibleValues as $v)
		{
			if (!empty($str))
				$str .= ', ';
			if (!is_null($v))
				$str .= '"'.htmlspecialcharsbx($v).'"';
			else
				$str .= 'null';
		}

		if (is_null($actualValue))
			$message = str_replace("#PARAM#", htmlspecialcharsbx($argumentTitle), GetMessage("DAVCGERR_INVALID_ARG"));
		else
			$message = str_replace(array("#PARAM#", "#VALUE#"), array(htmlspecialcharsbx($argumentTitle), $str), GetMessage("DAVCGERR_INVALID_ARG1"));

		parent::__construct($message, $argumentName, $argumentTitle);

		$this->code = "10003";
		$this->possibleValues = $possibleValues;
	}

	public function GetPossibleValues()
	{
		return $this->possibleValues;
	}
}

class CDavArgumentTypeException
	extends CDavArgumentException
{
	private $correctType = null;

	public function __construct($argumentName, $argumentTitle = '', $correctType = null)
	{
		if (empty($argumentTitle))
			$argumentTitle = $argumentName;

		if ($correctType === null)
			$message = str_replace("#PARAM#", htmlspecialcharsbx($argumentTitle), GetMessage("DAVCGERR_INVALID_TYPE"));
		else
			$message = str_replace(array("#PARAM#", "#VALUE#"), array(htmlspecialcharsbx($argumentTitle), htmlspecialcharsbx($correctType)), GetMessage("DAVCGERR_INVALID_TYPE1"));

		parent::__construct($message, $argumentName, $argumentTitle);

		$this->code = "10005";
		$this->correctType = $correctType;
	}

	public function GetCorrectType()
	{
		return $this->correctType;
	}
}

class CDavInvalidOperationException
	extends Exception
{
	public function __construct($message = "")
	{
		parent::__construct($message, 10006);
	}
}
?>