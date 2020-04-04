<?
class CDavGroupdavClientRequest
{
	private $method = '';
	private $path = '';
	private $arHeaders = array();
	private $body = '';

	private $exchangeClient = null;

	public function __construct($exchangeClient)
	{
		$this->exchangeClient = $exchangeClient;
	}

	public function AddHeader($key, $value)
	{
		if (empty($key) || empty($value))
			return;

		if (array_key_exists($key, $this->arHeaders))
		{
			if (is_array($this->arHeaders[$key]))
			{
				$this->arHeaders[$key][] = $value;
			}
			else
			{
				$ar = array($this->arHeaders[$key], $value);
				$this->arHeaders[$key] = $ar;
			}
		}
		else
		{
			$this->arHeaders[$key] = $value;
		}
	}

	public function SetHeader($key, $value)
	{
		if (empty($key))
			return;

		if (array_key_exists($key, $this->arHeaders) && empty($value))
			unset($this->arHeaders[$key]);
		else
			$this->arHeaders[$key] = $value;
	}

	public function SetMethod($method)
	{
		$this->method = $method;
	}

	public function GetMethod()
	{
		return $this->method;
	}

	public function SetPath($path)
	{
		$this->path = $path;
	}

	public function GetPath()
	{
		return $this->path;
	}

	public function SetBody($body)
	{
		$this->body = $body;
	}

	private function CreateBodyProperties($arProperties, &$bodyProp, &$xmlns, &$arXmlnsMap)
	{
		if (!is_array($arProperties) || (count($arProperties) <= 0))
		{
			$bodyProp .= "\t<A:allprop/>\r\n";
		}
		else
		{
			$bodyProp .= "\t<A:prop>\r\n";
			foreach ($arProperties as $prop)
			{
				if (is_array($prop))
				{
					if (!array_key_exists($prop[1], $arXmlnsMap))
					{
						$n = "A".count($arXmlnsMap);
						$xmlns .= " xmlns:".$n."=\"".$prop[1]."\"";
						$arXmlnsMap[$prop[1]] = $n;
					}
					$bodyProp .= "\t\t<".$arXmlnsMap[$prop[1]].":".$prop[0]."/>\r\n";
				}
				else
				{
					$bodyProp .= "\t\t<A:".$prop."/>\r\n";
				}
			}
			$bodyProp .= "\t</A:prop>\r\n";
		}
	}

	private function CreateBodyFilter($arFilter, &$bodyFilter)
	{
		$bodyFilter1 = '';

		if (!is_null($arFilter) && is_array($arFilter))
		{
			foreach ($arFilter as $key => $value)
			{
				if ($key == "time-range")
				{
					$bodyFilter1 .= "\t\t\t\t<A0:time-range";
					if (isset($value["start"]))
						$bodyFilter1 .= " start=\"".$value["start"]."\"";
					if (isset($value["end"]))
						$bodyFilter1 .= " end=\"".$value["end"]."\"";
					$bodyFilter1 .= "/>\r\n";
				}
			}
		}

		if (!empty($bodyFilter1))
		{
			$bodyFilter .= "\t<A0:filter>\r\n";
			$bodyFilter .= "\t\t<A0:comp-filter name=\"VCALENDAR\">\r\n";
			$bodyFilter .= "\t\t\t<A0:comp-filter name=\"VEVENT\">\r\n";
			$bodyFilter .= $bodyFilter1;
			$bodyFilter .= "\t\t\t</A0:comp-filter>\r\n";
			$bodyFilter .= "\t\t</A0:comp-filter>\r\n";
			$bodyFilter .= "\t</A0:filter>\r\n";
		}
	}

	public function CreatePropfindBody($arProperties = null, $arFilter = null)
	{
		$xmlns = " xmlns:A0=\"urn:ietf:params:xml:ns:caldav\"";
		$arXmlnsMap = array("urn:ietf:params:xml:ns:caldav" => "A0");

		$bodyProp = "";
		$this->CreateBodyProperties($arProperties, $bodyProp, $xmlns, $arXmlnsMap);

		$bodyFilter = "";
		$this->CreateBodyFilter($arFilter, $bodyFilter);

		$this->body = "<"."?xml version=\"1.0\" encoding=\"UTF-8\"?".">\r\n";
		$this->body .= "<A:propfind xmlns:A=\"DAV:\"".$xmlns.">\r\n";
		$this->body .= $bodyProp;
		$this->body .= $bodyFilter;
		$this->body .= "</A:propfind>";
	}

	public function CreateReportBody($arProperties = null, $arFilter = null, $arHref = null)
	{
		$xmlns = " xmlns:A0=\"urn:ietf:params:xml:ns:caldav\"";
		$arXmlnsMap = array("urn:ietf:params:xml:ns:caldav" => "A0");

		$bodyProp = "";
		$this->CreateBodyProperties($arProperties, $bodyProp, $xmlns, $arXmlnsMap);

		$bodyFilter = "";
		$this->CreateBodyFilter($arFilter, $bodyFilter);

		if (!is_array($arHref))
			$arHref = array();

		$bodyHref = "";
		foreach ($arHref as $href)
		{
			if (!empty($href))
				$bodyHref .= "\t<A:href>".self::UrlEncode($href)."</A:href>\r\n";
		}

		$this->body = "<"."?xml version=\"1.0\" encoding=\"UTF-8\"?".">\r\n";
		$this->body .= "<A0:calendar-multiget xmlns:A=\"DAV:\"".$xmlns.">\r\n";
		$this->body .= $bodyProp;
		$this->body .= $bodyFilter;
		$this->body .= $bodyHref;
		$this->body .= "</A0:calendar-multiget>";
	}

	public function ToString()
	{
		$buffer = sprintf("%s %s HTTP/1.1\r\n", $this->method, $this->path);
		foreach ($this->arHeaders as $key => $value)
		{
			if (!is_array($value))
				$value = array($value);

			foreach ($value as $value1)
				$buffer .= sprintf("%s: %s\r\n", $key, $value1);
		}
		$buffer .= sprintf("Content-length: %s\r\n", (function_exists('mb_strlen') ? mb_strlen($this->body, 'latin1') : strlen($this->body)));
		$buffer .= "\r\n";
		$buffer .= $this->body;
		return $buffer;
	}

	public static function UrlEncode($url)
	{
		return strtr($url, array(
			' ' => '%20',
			'&'	=> '%26',
			'<'	=> '%3C',
			'>'	=> '%3E',
			'+'	=> '%2B',
			'@' => '%40',
		));
	}
}
?>