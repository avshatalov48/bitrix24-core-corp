<?
class CDavXmlDocument
{
	private $root;	// holds the root document for the tree

	/**
	* Constructor
	*/
	public function __construct()
	{
	}

	private static function ExtractArrayFromXMLString($data)
	{
		$xmlParser = xml_parser_create_ns('UTF-8');

		xml_parser_set_option($xmlParser, XML_OPTION_SKIP_WHITE, 1);
		xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, 0);

		$xmlTags = array();
		$rc = xml_parse_into_struct($xmlParser, $data, $xmlTags);
		if ($rc == false)
			throw new CDavXMLParsingException(xml_error_string(xml_get_error_code($xmlParser)), xml_get_current_line_number($xmlParser), xml_get_current_column_number($xmlParser));

		xml_parser_free($xmlParser);
		if (count($xmlTags) == 0)
			$xmlTags = null;

		return $xmlTags;
	}

	public static function LoadFromString($data)
	{
		$result = new CDavXmlDocument();

		if (strlen($data) > 0)
		{
			$startFrom = 0;
			$arData = self::ExtractArrayFromXMLString($data);
			$arContent = self::LoadFromStringRecursive($result, $arData, $startFrom);

			$result->root = $arContent;
		}

		return $result;
	}

	private static function LoadFromStringRecursive(&$xmlDocument, $arData, &$startFrom)
	{
		$result = array();

		while (isset($arData[$startFrom]))
		{
			$tagdata = $arData[$startFrom++];
			if (!isset($tagdata) || !isset($tagdata['tag']) || !isset($tagdata['type']))
				break;
			if ($tagdata['type'] == "close")
				break;
			$attributes = (isset($tagdata['attributes']) ? $tagdata['attributes'] : false);
			if ($tagdata['type'] == "open")
			{
				$subtree = self::LoadFromStringRecursive($xmlDocument, $arData, $startFrom);
				$result[] = $xmlDocument->CreateNewNode($tagdata['tag'], $subtree, $attributes);
			}
			elseif ($tagdata['type'] == "complete")
			{
				$value = (isset($tagdata['value']) ? $tagdata['value'] : false);
				$result[] = $xmlDocument->CreateNewNode($tagdata['tag'], $value, $attributes);
			}
		}

		if (count($result) == 1 )
			return $result[0];

		return $result;
	}

	/**
	* @param string $tagname The tag name of the new element, possibly namespaced
	* @param mixed $content Either a string of content, or an array of sub-elements
	* @param array $attributes An array of attribute name/value pairs
	* @param array $xmlns An XML namespace specifier
	*/
	public function CreateNewNode($tagname, $content = false, $attributes = false, $xmlns = null)
	{
		if ($xmlns == null && preg_match('/^(.*):([^:]+)$/', $tagname, $matches))
		{
			$xmlns = $matches[1];
			$tagname = $matches[2];
		}
		else
		{
			$tagname = $tagname;
		}

		return new CDavXmlNode($tagname, $content, $attributes, $xmlns);
	}

	/**
	* Return an array of elements matching the specified path
	*
	* @return array The CDavXmlNode within the tree which match this tag
	*/
	public function GetPath($path)
	{
		if (!$this->root)
			return array();

		return $this->root->GetPath($path);
	}

	public function GetRoot()
	{
		return $this->root;
	}
}

class CDavXMLParsingException
	extends Exception
{
	private $xmlMessage = "";
	private $xmlLine = 0;
	private $xmlColumn = 0;

	public function __construct($xmlMessage = "", $xmlLine = 0, $xmlColumn = 0, $messageTemplate = "")
	{
		$this->xmlMessage = $xmlMessage;
		$this->xmlLine = $xmlLine;
		$this->xmlColumn = $xmlColumn;

		if (strlen($messageTemplate) <= 0)
			$messageTemplate = 'XML parsing error: #MESSAGE# at line #LINE#, column #COLUMN#';

		$message = str_replace(array("#MESSAGE#", "#LINE#", "#COLUMN#"), array(htmlspecialcharsbx($this->xmlMessage), $this->xmlLine, $this->xmlColumn), $messageTemplate);

		parent::__construct($message, 10010);
	}
}

?>