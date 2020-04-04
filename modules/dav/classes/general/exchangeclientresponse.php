<?
// http://msdn.microsoft.com/en-us/library/aa580675(v=EXCHG.140).aspx

class CDavExchangeClientResponce
{
	private $arDraftHeaders = array();
	private $draftBody = '';

	private $arStatus = array();
	private $arHeaders = array();
	private $xmlBody = null;

	public function __construct($arHeaders, $body)
	{
		$this->arDraftHeaders = $arHeaders;
		$this->draftBody = $body;
	}

	public function Dump()
	{
		if (count($this->arStatus) <= 0)
			$this->Parse();

		return "<hr><pre>arStatus:\n".print_r($this->arStatus, true)."\narHeaders:\n".print_r($this->arHeaders, true)."\nbody:\n".$this->draftBody."</pre><hr>";
	}

	public function GetHeader($name)
	{
		if (count($this->arStatus) <= 0)
			$this->Parse();

		$name = strtolower($name);
		if (array_key_exists($name, $this->arHeaders))
			return $this->arHeaders[$name];

		return null;
	}

	public function GetStatus($name = 'code')
	{
		if (count($this->arStatus) <= 0)
			$this->Parse();

		$name = strtolower($name);
		if (array_key_exists($name, $this->arStatus))
			return $this->arStatus[$name];

		return null;
	}

	public function GetBody()
	{
		return $this->draftBody;
	}

	public function GetBodyXml()
	{
		if (is_null($this->xmlBody))
			$this->xmlBody = CDavXmlDocument::LoadFromString($this->draftBody);

		return $this->xmlBody;
	}

	public static function ExtractArray($str)
	{
		$arResult = array();

		$ar = explode(",", $str);
		foreach ($ar as $v)
		{
			list($x1, $x2) = explode("=", $v);
			$arResult[trim($x1)] = trim(trim($x2), '"\'');
		}

		return $arResult;
	}

	private function Parse()
	{
		if (count($this->arDraftHeaders) <= 0)
			return;

		// First line should be a HTTP status line (see http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html#sec6)
		// Format is: HTTP-Version SP Status-Code SP Reason-Phrase CRLF
		list($httpVersion, $statusCode, $reasonPhrase) = explode(' ', $this->arDraftHeaders[0], 3);
		$this->arStatus = array(
			'version' => $httpVersion,
			'code' => $statusCode,
			'phrase' => $reasonPhrase
		);

		// get the response header fields
		// See http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html#sec6
		for ($i = 1, $cnt = count($this->arDraftHeaders); $i < $cnt; $i++)
		{
			list($name, $value) = explode(':', $this->arDraftHeaders[$i]);

			$name = strtolower($name);
			if (!array_key_exists($name, $this->arHeaders))
			{
				$this->arHeaders[$name] = trim($value);
			}
			elseif (is_array($this->arHeaders[$name]))
			{
				$this->arHeaders[$name][] = trim($value);
			}
			else
			{
				$ar = array($this->arHeaders[$name], trim($value));
				$this->arHeaders[$name] = $ar;
			}
		}
	}
}
?>