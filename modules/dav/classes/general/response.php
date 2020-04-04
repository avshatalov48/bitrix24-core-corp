<?
define("DAV_XML_OUTPUT", 0);
define("DAV_BINARY_OUTPUT", 1);
define("DAV_HTML_OUTPUT", 2);

class CDavResponse
{
	private $requestUri = "";

	private $httpStatus = "";
	private $arHeaders = array();
	private $body = "";

	private $encoding = "utf-8";
	private $outputType = 0;
	private $multipartSeparator = "";

	public function __construct($requestUri, $encoding)
	{
		$this->requestUri = $requestUri;
		$this->encoding = strtolower($encoding);
		$this->outputType = DAV_XML_OUTPUT;
	}

	public function SetEncoding($siteId)
	{
		$encoding = CDav::GetCharset($siteId);
		if (is_null($encoding) || empty($encoding))
			$encoding = "utf-8";
		$this->encoding = strtolower($encoding);
	}

	public function TurnOnBinaryOutput()
	{
		$this->outputType = DAV_BINARY_OUTPUT;
	}

	public function TurnOnHtmlOutput()
	{
		$this->outputType = DAV_HTML_OUTPUT;
	}

	public function SetHttpStatus($status)
	{
		$this->httpStatus = $status;
	}

	public function AddHeader($header)
	{
		$this->arHeaders[] = $header;
	}

	public function AddLine()
	{
		$args = func_get_args();
		if ($this->outputType == DAV_BINARY_OUTPUT)
			$this->body .= array_shift($args);
		else
			$this->body .= vsprintf(array_shift($args), array_values($args))."\n";
	}

	/**
	* Generate separator headers for multipart response
	*
	* First and last call happen without parameters to generate the initial header and closing sequence, all calls inbetween
	* require content mimetype, start and end byte position and optionaly the total byte length of the requested resource
	*
	* @param  string  mimetype
	* @param  int     start byte position
	* @param  int     end   byte position
	* @param  int     total resource byte size
	*/
	public function MultipartByteRangeHeader($mimetype = false, $from = false, $to = false, $total = false)
	{
		if ($mimetype === false)
		{
			if (strlen($this->multipartSeparator) <= 0)
			{
				$this->multipartSeparator = "bx_dav_".md5(microtime());
				$this->AddHeader("Content-type: multipart/byteranges; boundary=".$this->multipartSeparator);
			}
			else
			{
				$this->AddLine("\n--{".$this->multipartSeparator."}--");
			}
		}
		else
		{
			$this->AddLine("\n--{".$this->multipartSeparator."}\n");
			$this->AddLine("Content-type: ".$mimetype."\n");
			$this->AddLine("Content-range: ".$from."-".$to."/".($total === false ? "*" : $total));
			$this->AddLine("\n\n");
		}
	}

	public function GenerateError($error, $message = "")
	{
		$this->SetHttpStatus($error);
		$this->AddHeader('Content-Type: text/html');
		$this->AddLine("<html><head><title>Error %s</title></head>", $error);
		$this->AddLine("<body><h1>%s</h1>", $error);
		$this->AddLine("The requested could not be handled by this server.");
		$this->AddLine("(URI %s)<br>\n<br>", $this->requestUri);
		if (strlen($message) > 0)
			$this->AddLine("%s<br>\n<br>", $message);
		$this->AddLine("</body></html>");
	}

	public function Render()
	{
		$status = $this->httpStatus;
		if (strlen($status) <= 0)
			$status = "200 OK";

		static::sendStatus($status);

		foreach ($this->arHeaders as $header)
			static::sendHeader($header);

		static::sendStatus($status);

		if ($this->outputType == DAV_XML_OUTPUT && !empty($this->body))
			echo "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";

		echo $this->body;

		CDav::Report("<<<<<<<<<<<<<< RESPONSE >>>>>>>>>>>>>>>>", "\nheaders:\n".print_r(headers_list(), true)."\noutput:\n".$this->body."\n", "UNDEFINED", true);
	}

	public static function sendStatus($status)
	{
		CHTTP::SetStatus($status);
		static::sendHeader('X-WebDAV-Status: ' . $status, true);
	}

	private static function sendHeader($str, $force = true) // safe from response splitting
	{
		header(str_replace(array("\r", "\n"), "", $str), $force);
	}

	public function Encode($text)
	{
		if ($this->encoding == "utf-8")
			return $text;

		global $APPLICATION;
		return $APPLICATION->ConvertCharset($text, $this->encoding, "utf-8");
	}
}
?>