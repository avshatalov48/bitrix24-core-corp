<?
IncludeModuleLangFile(__FILE__);

class CDavGroupdavClient
{
	private $scheme = "http";
	private $server = null;
	private $port = '80';
	private $userName = null;
	private $userPassword = null;

	private $proxyScheme = null;
	private $proxyServer = null;
	private $proxyPort = null;
	private $proxyUserName = null;
	private $proxyUserPassword = null;
	private $proxyUsed = false;

	private $fp = null;
	private $socketTimeout = 5;
	private $connected = false;
	private $userAgent = 'Bitrix CalDAV/CardDAV/GroupDAV client';

	private $path = '/';
	private $principalUrl = null;

	private $arError = array();
	private $debug = false;

	private $encoding = "windows-1251";

	private $googleAuth = null;
	private $googleOAuth = null;

	public function __construct($scheme, $server, $port, $userName, $userPassword)
	{
		$this->scheme = ((strtolower($scheme) == "https") ? "https" : "http");
		$this->server = $server;
		$this->port = $port;
		$this->userName = $userName;
		$this->userPassword = $userPassword;

		$this->connected = false;
		$this->debug = false;

		$this->proxyScheme = null;
		$this->proxyServer = null;
		$this->proxyPort = null;
		$this->proxyUserName = null;
		$this->proxyUserPassword = null;
		$this->proxyUsed = false;
	}

	public function Debug()
	{
		$this->debug = true;
	}

	public function SetCurrentEncoding($siteId = null)
	{
		$this->encoding = CDav::GetCharset($siteId);
		if (is_null($this->encoding) || empty($this->encoding))
			$this->encoding = "utf-8";
	}

	public function SetProxy($proxyScheme, $proxyServer, $proxyPort, $proxyUserName, $proxyUserPassword)
	{
		$this->proxyScheme = ((strtolower($proxyScheme) == "https") ? "https" : "http");
		$this->proxyServer = $proxyServer;
		$this->proxyPort = $proxyPort;
		$this->proxyUserName = $proxyUserName;
		$this->proxyUserPassword = $proxyUserPassword;

		$this->proxyUsed = (strlen($this->proxyServer) > 0 && strlen($this->proxyPort) > 0);
	}

	public function setGoogleOAuth($token)
	{
		$this->googleOAuth = $token;
	}

	public function GetPath()
	{
		return $this->path;
	}

	public function Connect()
	{
		if ($this->connected)
			return true;

		$requestScheme = $this->scheme;
		$requestServer = $this->server;
		$requestPort = $this->port;
		if ($this->proxyUsed)
		{
			$requestScheme = $this->proxyScheme;
			$requestServer = $this->proxyServer;
			$requestPort = $this->proxyPort;
		}

		switch ($requestScheme)
		{
			case 'https':
				if (!function_exists("openssl_verify"))
				{
					$this->arError[] = array("EC0", "OpenSSL PHP extention required");
					$this->connected = false;
					return false;
				}

				$requestScheme = 'ssl://';
				$requestPort = ($requestPort === null) ? 443 : $requestPort;
				break;

			case 'http':
				$requestScheme = '';
				$requestPort = ($requestPort === null) ? 80 : $requestPort;
				break;

			default:
				$this->arError[] = array("EC1", "Invalid protocol");
				$this->connected = false;
				return false;
		}

		$this->fp = @fsockopen($requestScheme.$requestServer, $requestPort, $errno, $errstr, $this->socketTimeout);

		if (!$this->fp)
		{
			$this->arError[] = array($errno, $errstr);
			$this->connected = false;
			return false;
		}
		else
		{
			socket_set_blocking($this->fp, 1);
			$this->connected = true;
			return true;
		}
	}

	public function Disconnect()
	{
		if (!$this->connected)
			return;

		fclose($this->fp);
		$this->connected = false;
	}

	public function GetErrors()
	{
		return $this->arError;
	}

	public function AddError($code, $message)
	{
		$this->arError[] = array($code, $message);
	}

	public function ClearErrors()
	{
		$this->arError = array();
	}

	public function CheckWebdavServer($path)
	{
		$response = $this->Options($path);

		if (is_null($response))
			return false;
		if ($dav = $response->GetHeader('DAV'))
		{
			$ar = explode(",", $dav);
			foreach ($ar as $v)
			{
				if (trim($v)."!" == "1!")
					return true;
			}
		}

		return false;
	}

	public function Options($path)
	{
		$path = $this->FormatUri($path);

		$request = $this->CreateBasicRequest('OPTIONS', $path);

		return $this->Send($request);
	}

	public function Propfind($path, $arProperties = null, $arFilter = null, $depth = 1)
	{
		$path = $this->FormatUri($path);

		$request = $this->CreateBasicRequest('PROPFIND', $path);
		$request->AddHeader('Depth', intval($depth));
		$request->AddHeader('Content-type', 'text/xml');

		$request->CreatePropfindBody($arProperties, $arFilter);

		$response = $this->Send($request);

		if (!is_null($response))
		{
			if (($statusCode = $response->GetStatus('code')) && (strcmp($statusCode, '207') == 0))
			{
				if (($contentType = $response->GetHeader('Content-Type')) && (strpos($contentType, '/xml') !== false))
					return $response->GetBodyXml();
			}
		}

		return null;
	}

	public function Report($path, $arProperties = null, $arFilter = null, $arHref = null, $depth = 1)
	{
		$path = $this->FormatUri($path);

		$request = $this->CreateBasicRequest('REPORT', $path);
		$request->AddHeader('Depth', intval($depth));
		$request->AddHeader('Content-type', 'text/xml');

		$request->CreateReportBody($arProperties, $arFilter, $arHref);

		$response = $this->Send($request);

		if (!is_null($response))
		{
			if (($statusCode = $response->GetStatus('code')) && (strcmp($statusCode, '207') == 0))
			{
				if (($contentType = $response->GetHeader('Content-Type')) && (strpos($contentType, '/xml') !== false))
					return $response->GetBodyXml();
			}
		}

		return null;
	}

	public function Delete($path)
	{
		$path = $this->FormatUri($path);

		$request = $this->CreateBasicRequest('DELETE', $path);

		$response = $this->Send($request);

		if (!is_null($response))
		{
			$statusCode = $response->GetStatus('code');

			if (strcmp($statusCode, '207') == 0)
			{
				if (($contentType = $response->GetHeader('Content-Type')) && (strpos($contentType, '/xml') !== false))
					return $response->GetBodyXml();
			}
			elseif (strcmp($statusCode, '404') == 0)
			{
				$this->AddError("404", "Item is not found");
				return false;
			}
			elseif (strcmp($statusCode, '200') == 0 || strcmp($statusCode, '201') == 0 || strcmp($statusCode, '204') == 0)
			{
				return true;
			}
		}

		return null;
	}

	public function Put($path, $data)
	{
		$path = $this->FormatUri($path);

		$request = $this->CreateBasicRequest('PUT', $path);
		$request->AddHeader('Content-type', 'text/calendar; charset=UTF-8');

		$request->SetBody($data);

		$response = $this->Send($request);

		if (!is_null($response))
		{
			$code = $response->GetStatus('code');

			if ($code != 200 && $code != 201 && $code != 204)
				$this->AddError($code, GetMessage("DAV_GDC_SERVER_ERROR").$response->GetStatus('phrase'));

			return $response->GetStatus('code');
		}

		$this->AddError("NA", "Unknown error");
		return null;
	}

	public function Encode($text)
	{
		if (is_null($text) || empty($text))
			return $text;
		if ($this->encoding == "utf-8")
			return $text;

		global $APPLICATION;
		return $APPLICATION->ConvertCharset($text, "utf-8", $this->encoding);
	}

	public function Decode($text)
	{
		if (is_null($text) || empty($text))
			return $text;
		if ($this->encoding == "utf-8")
			return $text;

		global $APPLICATION;
		return $APPLICATION->ConvertCharset($text, $this->encoding, "utf-8");
	}

	private function FormatUri($path)
	{
		return $path;
		$path = html_entity_decode($path);

		$arParts = explode('/', $path);
		$arPartsNew = array();
		for ($i = 0, $cnt = count($arParts); $i < $cnt; $i++)
		{
			if (strlen($arParts[$i]) > 0)
				$arPartsNew[] = str_replace("%40", "@", rawurlencode($arParts[$i]));
		}
		return "/".implode('/', $arPartsNew);
	}

	private function CreateBasicRequest($method, $path)
	{
		$request = new CDavGroupdavClientRequest($this);

		$request->SetMethod($method);
		if ($this->proxyUsed)
			$request->SetPath($this->scheme."://".$this->server.((intval($this->port) > 0) ? ":".$this->port : "").$path);
		else
			$request->SetPath($path);

		$request->AddHeader('Host', $this->server);
		$request->AddHeader('User-Agent', $this->userAgent);
		$request->AddHeader("Connection", "Keep-Alive");
		if ($this->googleAuth != null)
			$request->AddHeader("Authorization", sprintf("GoogleLogin auth=%s", $this->googleAuth));
		if ($this->googleOAuth != null)
			$request->AddHeader('Authorization', sprintf('Bearer %s', $this->googleOAuth));

		return $request;
	}

	private function Send($request)
	{
		$i = 0;
		while (true)
		{
			$i++;
			if ($i > 3)
				break;

			if ($this->debug)
			{
				$f = fopen($_SERVER["DOCUMENT_ROOT"]."/++++++++.+++", "a");
				fwrite($f, "\n>>>>>>>>>>>>>>>>>> REQUEST ".$i." >>>>>>>>>>>>>>>>\n");
				fwrite($f, $request ? $request->ToString() : "???");
				fwrite($f, "\n<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n\n");
				fclose($f);
			}

			$this->SendRequest($request);
			$response = $this->GetResponse();

			if ($this->debug)
			{
				$f = fopen($_SERVER["DOCUMENT_ROOT"]."/++++++++.+++", "a");
				fwrite($f, "\n>>>>>>>>>>>>>>>>>> RESPONCE ".$i." >>>>>>>>>>>>>>>>\n");
				fwrite($f, $response ? $response->Dump() : "???");
				fwrite($f, "\n<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n\n");
				fclose($f);
			}

			if (!is_null($response))
			{
				if (($location = $response->GetHeader('Location')) && !is_null($location) && $response->GetStatus('code') != 201)
				{
					if ($this->proxyUsed)
						$request->SetPath($this->scheme."://".$this->server.((intval($this->port) > 0) ? ":".$this->port : "").$location);
					else
						$request->SetPath($location);

					continue;
				}
				elseif (($statusCode = $response->GetStatus('code')) && (intval($statusCode) == 401))
				{
					$request = $this->Authenticate($request, $response);
					if (is_null($request))
					{
						return null;
					}
					continue;
				}
			}

			break;
		}

		if (!is_null($response) && ($statusCode = $response->GetStatus('code')) && (intval($statusCode) == 401))
			$this->AddError("401", GetMessage("DAV_GDC_NOT_AUTH"));

		if ($this->debug)
		{
			$f = fopen($_SERVER["DOCUMENT_ROOT"]."/++++++++.+++", "a");
			fwrite($f, "\n>>>>>>>>>>>>>>>>>> RESPONSE >>>>>>>>>>>>>>>>\n");
			if (is_null($response))
				fwrite($f, "NULL");
			else
				fwrite($f, $response->Dump());
			fwrite($f, "\n<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n\n");
			fclose($f);
		}

		return $response;
	}

	private function Authenticate($request, $response)
	{
		$authenticate = $response->GetHeader('WWW-Authenticate');
		$authenticateProxy = $response->GetHeader('Proxy-Authenticate');

		if (is_null($authenticate) && is_null($authenticateProxy))
			return null;

		if (!is_null($authenticate) && !is_array($authenticate))
			$authenticate = array($authenticate);
		if (!is_null($authenticateProxy) && !is_array($authenticateProxy))
			$authenticateProxy = array($authenticateProxy);

		if (!is_null($authenticate))
		{
			$arAuth = array();
			foreach ($authenticate as $auth)
			{
				$auth = trim($auth);
				$p = strpos($auth, " ");
				if ($p !== false)
					$arAuth[strtolower(substr($auth, 0, $p))] = trim(substr($auth, $p));
				else
					$arAuth[strtolower($auth)] = "";
			}

			if (array_key_exists("digest", $arAuth))
			{
				$request = $this->AuthenticateDigest(CDavExchangeClientResponce::ExtractArray($arAuth["digest"]), $request, $response, "Authorization");
			}
			elseif (array_key_exists("basic", $arAuth))
			{
				$request = $this->AuthenticateBasic(CDavExchangeClientResponce::ExtractArray($arAuth["basic"]), $request, $response, "Authorization");
			}
			elseif (array_key_exists("googlelogin", $arAuth))
			{
				$request = $this->AuthenticateGoogleLogin(CDavExchangeClientResponce::ExtractArray($arAuth["basic"]), $request, $response, "Authorization");
				if ($request === null)
					return null;
			}
			else
				return null;
		}

		if (!is_null($authenticateProxy))
		{
			$arAuthProxy = array();
			foreach ($authenticateProxy as $auth)
			{
				$auth = trim($auth);
				$p = strpos($auth, " ");
				if ($p !== false)
					$arAuthProxy[strtolower(substr($auth, 0, $p))] = trim(substr($auth, $p));
				else
					$arAuthProxy[strtolower($auth)] = "";
			}

			if (array_key_exists("digest", $arAuthProxy))
				$request = $this->AuthenticateDigest(CDavExchangeClientResponce::ExtractArray($arAuthProxy["digest"]), $request, $response, "Proxy-Authorization");
			elseif (array_key_exists("basic", $arAuthProxy))
				$request = $this->AuthenticateBasic(CDavExchangeClientResponce::ExtractArray($arAuthProxy["basic"]), $request, $response, "Proxy-Authorization");
			else
				return null;
		}

		return $request;
	}

	private static function gmailUsernameFix($name, $link)
	{
		if (strpos($name, '@gmail.com') !== false)
		{
			$arN = explode('@', $name);
			$name = str_replace('.', '', $arN[0]).'@'.$arN[1];
		}
		return $name;
	}

	private function AuthenticateDigest($arDigestRequest, $request, $response, $verb = "Authorization")
	{
		// qop="auth",algorithm=MD5-sess,nonce="+Upgraded+v1fdcb1e18d2cc7a72322c81c0d8d2a3c332f7908ef0dfcb01aa9fb63930eadf5722dc8f6ce7b82912353531b18360cd62382a6c2433939d3f",charset=utf-8,realm="Digest"

/*
TODO:
If the "qop" value is "auth" or "auth-int":

      request-digest  = <"> < KD ( H(A1),     unq(nonce-value)
                                          ":" nc-value
                                          ":" unq(cnonce-value)
                                          ":" unq(qop-value)
                                          ":" H(A2)
                                  ) <">

   If the "qop" directive is not present (this construction is for
   compatibility with RFC 2069):

      request-digest  =
                 <"> < KD ( H(A1), unq(nonce-value) ":" H(A2) ) > <">

If the "algorithm" directive's value is "MD5" or is unspecified, then
   A1 is:

      A1       = unq(username-value) ":" unq(realm-value) ":" passwd

   where

      passwd   = < user's password >

   If the "algorithm" directive's value is "MD5-sess", then A1 is
   calculated only once - on the first request by the client following
   receipt of a WWW-Authenticate challenge from the server.  It uses the
   server nonce from that challenge, and the first client nonce value to
   construct A1 as follows:

      A1       = H( unq(username-value) ":" unq(realm-value)
                     ":" passwd )
                     ":" unq(nonce-value) ":" unq(cnonce-value)

 If the "qop" directive's value is "auth" or is unspecified, then A2
   is:

      A2       = Method ":" digest-uri-value

   If the "qop" value is "auth-int", then A2 is:

      A2       = Method ":" digest-uri-value ":" H(entity-body)

*/

		$cn = md5(uniqid());

		$userName = static::gmailUsernameFix($this->userName, $this->server);

		$a1 = md5($userName.':'.$arDigestRequest["realm"].':'.$this->userPassword).":".$arDigestRequest["nonce"].":".$cn;
		$a2 = $request->GetMethod().":".$request->GetPath();
		$hash = md5(md5($a1).":".$arDigestRequest["nonce"].":00000001:".$cn.":".$arDigestRequest["qop"].":".md5($a2));

		$request->SetHeader(
			$verb,
			sprintf(
				"Digest username=\"%s\",realm=\"%s\",nonce=\"%s\",uri=\"%s\",cnonce=\"%s\",nc=00000001,algorithm=%s,response=\"%s\",qop=\"%s\",charset=utf-8",
				$userName,
				$arDigestRequest["realm"],
				$arDigestRequest["nonce"],
				$request->GetPath(),
				$cn,
				$arDigestRequest["algorithm"],
				$hash,
				$arDigestRequest["qop"]
			)
		);

		return $request;
	}

	private function AuthenticateBasic($arBasicRequest, $request, $response, $verb = "Authorization")
	{
		// realm="test-exch2007"
		$userName = static::gmailUsernameFix($this->userName, $this->server);

		$request->SetHeader(
			$verb,
			sprintf(
				"Basic %s",
				base64_encode($userName.":".$this->userPassword)
			)
		);

		return $request;
	}

	private function AuthenticateGoogleLogin($arBasicRequest, $request, $response, $verb = "Authorization")
	{
		$request1 = $this->CreateBasicRequest("POST", "/accounts/ClientLogin");
		$request1->SetHeader("Content-Type", "application/x-www-form-urlencoded");
		$request1->SetBody('accountType='.urlencode('HOSTED_OR_GOOGLE').'&Email='.urlencode($this->userName).'&Passwd='.urlencode($this->userPassword).'&service='.urlencode('cl').'&source='.urlencode("none-none-1"));

		if ($this->debug)
		{
			$f = fopen($_SERVER["DOCUMENT_ROOT"]."/++++++++.+++", "a");			
			fwrite($f, "\n>>>>>>>>>>>>>>>>>> GOOGLEREQUEST >>>>>>>>>>>>>>>>\n");
			fwrite($f, $request1 ? $request1->ToString() : "???");
			fwrite($f, "\n<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n\n");
			fclose($f);
		}

		$this->SendRequest($request1);
		$response1 = $this->GetResponse();

		if ($this->debug)
		{
			$f = fopen($_SERVER["DOCUMENT_ROOT"]."/++++++++.+++", "a");
			fwrite($f, "\n>>>>>>>>>>>>>>>>>> GOOGLERESPONCE >>>>>>>>>>>>>>>>\n");
			fwrite($f, $response1 ? $response1->Dump() : "???");
			fwrite($f, "\n<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n\n");
			fclose($f);
		}

		if (($statusCode = $response1->GetStatus('code')) && (intval($statusCode) == 200))
		{
			preg_match('/Auth=(.*)/', $response1->GetBody(), $matches);
			if (isset($matches[1]))
			{
				$this->googleAuth = $matches[1];
				$request->SetHeader($verb, sprintf("GoogleLogin auth=%s", $this->googleAuth));
			}
			else
			{
				return null;
			}
		}
		else
		{
			return null;
		}

		return $request;
	}

	private function SendRequest($request)
	{
		if (!$this->connected)
			$this->Connect();

		if (!$this->connected)
			return null;

		fputs($this->fp, $request->ToString());
	}

	private function GetResponse()
	{
		if (!$this->connected)
			return null;

		$arHeaders = array();
		$body = "";

		while ($line = fgets($this->fp, 4096))
		{
			if ($line == "\r\n")
				break;

			$arHeaders[] = trim($line);
		}

		if (count($arHeaders) <= 0)
			return null;

		$bChunked = $bConnectionClosed = false;
		$contentLength = null;
		foreach ($arHeaders as $value)
		{
			if (!$bChunked && preg_match("#Transfer-Encoding:\s*chunked#i", $value))
				$bChunked = true;
			if (!$bConnectionClosed && preg_match('#Connection:\s*close#i', $value))
				$bConnectionClosed = true;
			if (is_null($contentLength))
			{
				if (preg_match('#Content-Length:\s*([0-9]*)#i', $value, $arMatches))
					$contentLength = intval($arMatches[1]);
				if (preg_match('#HTTP/1\.1\s+204#i', $value))
					$contentLength = 0;
			}
		}

		if ($bChunked)
		{
			do
			{
				$line = fgets($this->fp, 4096);
				$line = strtolower($line);

				$chunkSize = "";
				$i = 0;
				while ($i < strlen($line))
				{
					$c = substr($line, $i, 1);
					if (in_array($c, array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f")))
						$chunkSize .= $c;
					else
						break;
					$i++;
				}

				$chunkSize = hexdec($chunkSize);

				if ($chunkSize > 0)
				{
					$lb = $chunkSize;
					$body1 = '';
					$crutchCnt = 0;
					while ($lb > 0)
					{
						$d = fread($this->fp, $lb);
						if ($d === false)
							break;

						if ($d === '')
						{
							$crutchCnt++;
							if ($crutchCnt > 10)
								break;
						}
						else
						{
							$crutchCnt = 0;

							$body1 .= $d;
							$lb = $chunkSize - ((function_exists('mb_strlen') ? mb_strlen($body1, 'latin1') : strlen($body1)));
						}
					}
					$body .= $body1;
				}

				fgets($this->fp, 4096);
			}
			while ($chunkSize);
		}
		elseif ($contentLength === 0)
		{
		}
		elseif ($contentLength > 0)
		{
			$lb = $contentLength;
			while ($lb > 0)
			{
				$d = fread($this->fp, $lb);
				if ($d === false)
					break;

				$body .= $d;
				$lb = $contentLength - (function_exists('mb_strlen') ? mb_strlen($body, 'latin1') : strlen($body));
			}
		}
		else
		{
			socket_set_timeout($this->fp, 0);

			while (!feof($this->fp))
			{
				$d = fread($this->fp, 4096);
				if ($d === false)
					break;

				$body .= $d;
				if (substr($body, -9) == "\r\n\r\n0\r\n\r\n")
				{
					$body = substr($body, 0, -9);
					break;
				}
			}

			socket_set_timeout($this->fp, $this->socketTimeout);
		}

		if ($bConnectionClosed)
			$this->Disconnect();

		$responce = new CDavGroupdavClientResponce($arHeaders, $body);

		$httpVersion = $responce->GetStatus('version');
		if (is_null($httpVersion) || ($httpVersion != 'HTTP/1.1' && $httpVersion != 'HTTP/1.0'))
			return null;

		return $responce;
	}
}
?>