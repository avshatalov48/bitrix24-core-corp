<?php

class CSecurityXSSDetect
{
	const SCRIPT_MARK = '<!-- deleted by bitrix WAF -->';
	private $quotes = array();

	private $action = "filter";
	private $doLog = false;

	/** @var CSecurityXSSDetectVariables */
	private $variables = null;

	/** @var string[] */
	private $searches = null;
	/** @var string[] */
	private $quotedSearches = null;

	function __construct($options = array())
	{
		if(isset($options["action"]))
		{
			$this->setAction($options["action"]);
		}
		else
		{
			$this->setAction(COption::GetOptionString("security", "filter_action"));
		}

		if(isset($options["log"]))
		{
			$this->setLog($options["log"]);
		}
		else
		{
			$this->setLog(COption::GetOptionString("security", "filter_log"));
		}
	}

	/**
	 * @param $content
	 */
	public static function OnEndBufferContent(&$content)
	{
		if (CSecuritySystemInformation::isCliMode())
			return;

		if (CSecurityFilterMask::Check(SITE_ID, $_SERVER["REQUEST_URI"]))
			return;

		if (!preg_match('#</script#', $content)) // Probably does not include the scripts
			return;

		$filter = new CSecurityXSSDetect();
		$filter->process($content);
	}

	/**
	 * @param string $content
	 * @return void
	 */
	public function process(&$content)
	{
		$this->variables = new CSecurityXSSDetectVariables();
		$this->extractVariablesFromArray("\$_GET", $_GET);
		$this->extractVariablesFromArray("\$_POST", $_POST);
		$this->extractVariablesFromArray("\$_SERVER[REQUEST_URI]", explode("/",$_SERVER['REQUEST_URI']));

		if(!$this->variables->isEmpty())
		{
			$this->searches = $this->variables->getSearchValues();
			$this->quotedSearches = $this->variables->getQuoteSearchValues();
			$content = $this->filter($content);
		}

	}

	/**
	 * @return array
	 */
	public function getQuotes()
	{
		return $this->quotes;
	}

	/**
	 * @param string $string
	 * @param bool $isSaveQuotes
	 * @return mixed
	 */
	public function removeQuotedStrings($string, $isSaveQuotes = true)
	{
		// http://stackoverflow.com/questions/5695240/php-regex-to-ignore-escaped-quotes-within-quotes
		// ToDo: R&D, what about JS comments?
		static $regexp = '/(
				"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"                           # match double quoted string
				|
				\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'                       # match single quoted string
			)/xs';

		if ($isSaveQuotes)
		{
			$this->quotes = array();
			return preg_replace_callback($regexp, array($this, "pushQuote"), $string);
		}

		return preg_replace($regexp, '', $string);
	}

	/**
	 * @param string $action
	 */
	protected function setAction($action)
	{
		$this->action = $action;
	}

	/**
	 * @param string $log - only Y/N
	 */
	protected function setLog($log)
	{
		if(is_string($log) && $log == "Y")
		{
			$this->doLog = true;
		}
		else
		{
			$this->doLog = false;
		}
	}

	/**
	 * @param $name
	 * @param $value
	 * @param $sourceScript
	 * @return mixed
	 */
	protected function logVariable($name, $value, $sourceScript)
	{
		if(defined("ANTIVIRUS_CREATE_TRACE"))
			$this->CreateTrace($name, $value, $sourceScript);

		return CSecurityEvent::getInstance()->doLog("SECURITY", "SECURITY_FILTER_XSS2", $name, $value);
	}

	/**
	 * @param $var_name
	 * @param $str
	 * @param $script
	 */
	protected function CreateTrace($var_name, $str, $script)
	{
		$cache_id = md5($var_name.'|'.$str);
		$fn = $_SERVER["DOCUMENT_ROOT"]."/bitrix/cache/virus.db/".$cache_id.".flt";
		if(!file_exists($fn))
		{
			CheckDirPath($fn);
			$f = fopen($fn, "wb");

			fwrite($f, $var_name.": ".$str);
			fwrite($f, "\n------------\n".$script);
			fwrite($f, "\n------------------------------\n\$_SERVER:\n");
			foreach($_SERVER as $k=>$v)
				fwrite($f, $k." = ".$v."\n");

			fclose($f);
			@chmod($fn, BX_FILE_PERMISSIONS);
		}
	}

	/**
	 * @param string $quote
	 * @return string
	 */
	protected function pushQuote($quote)
	{
		$this->quotes[] = $quote[0];
		return "";
	}

	/**
	 * @param string $string
	 * @param array $searches
	 * @return null|string
	 */
	protected function findInArray($string, $searches)
	{
		foreach($searches as $i => $search)
		{
			$pos = static::fastStrpos($string, $search["value"]);
			if ($pos !== false)
			{
				$prevChar = static::fastSubstr($string, $pos - 1, 1);
				$isFound = ($prevChar !== '\\');
				if ($isFound && preg_match("/^[a-zA-Z_]/", $search["value"]))
				{
					$isFound = preg_match("/^[a-zA-Z_]/", $prevChar) <= 0;
				}
			}

			if ($isFound)
				return $i;
		}
		return null;
	}

	/**
	 * @param string $body
	 * @return array|false
	 */
	protected function isDangerBody($body)
	{
		$search = $this->findInArray($body, $this->quotedSearches);
		if ($search !== null)
		{
			return $this->quotedSearches[$search];
		}
		else if (!empty($this->searches))
		{
			$bodyWithoutQuotes = $this->removeQuotedStrings($body, false);
			$search = $this->findInArray($bodyWithoutQuotes, $this->searches);
			if ($search !== null)
			{
				return $this->searches[$search];
			}
		}

		return false;
	}

	/**
	 * @param string $body
	 * @return string
	 */
	protected function getFilteredScriptBody($body)
	{
		if($var = $this->isDangerBody($body))
		{
			if($this->doLog)
			{
				$this->logVariable($var["name"], $var["value"], $str);
			}

			if($this->action !== "none")
			{
				$body = self::SCRIPT_MARK;
			}
		}

		return $body;
	}

	/**
	 * @param array $strs
	 * @return string
	 */
	protected function getFilteredScript($strs)
	{
		if(trim($strs[2]) === "")
			return $strs[0];
		else
			return $strs[1].$this->getFilteredScriptBody($strs[2]).$strs[3];
	}

	/**
	 * @param string $string
	 * @return string
	 */
	protected function filter($string)
	{
		$stringLen = CUtil::BinStrlen($string) * 2;
		CUtil::AdjustPcreBacktrackLimit($stringLen);

		return preg_replace_callback("/(<script[^>]*>)(.*?)(<\\/script[^>]*>)/is", array($this, "getFilteredScript"), $string);
	}

	/**
	 * @param string $name
	 * @param string $value
	 */
	protected function addVariable($name, $value)
	{
		if(!is_string($value))
			return;
		if(mb_strlen($value) <= 2)
			return; //too short
		if(preg_match("/^(?P<quot>[\"']?)[^`,;+\-*\/\{\}\[\]\(\)&\\|=\\\\]*(?P=quot)\$/D", $value))
			return; //there is no potantially dangerous code
		if(preg_match("/^[,0-9_-]*\$/D", $value))
			return; //there is no potantially dangerous code
		if(preg_match("/^[0-9 \n\r\t\\[\\]]*\$/D", $value))
			return; //there is no potantially dangerous code

		$this->variables->addVariable($name, $value);
	}

	/**
	 * @param string $name
	 * @param array $array
	 */
	protected function extractVariablesFromArray($name, $array)
	{
		if(!is_array($array))
			return;

		foreach($array as $key => $value)
		{
			$variableName = sprintf('%s[%s]', $name, $key);
			if (is_array($value))
				$this->extractVariablesFromArray($variableName, $value);
			else
				$this->addVariable($variableName, $value);
		}
	}

	protected static function fastStrpos($haystack, $needle)
	{
		if (function_exists("mb_orig_strpos"))
		{
			return mb_orig_strpos($haystack, $needle);
		}

		return mb_strpos($haystack, $needle);
	}

	protected static function fastSubstr($string, $start, $length = null)
	{
		if (function_exists("mb_orig_substr"))
		{
			return mb_orig_substr($string, $start, $length);
		}

		return mb_substr($string, $start, $length);
	}

}
