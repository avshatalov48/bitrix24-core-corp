<?
class CDavResource
{
	private $path = "";
	private $arProperties = array();

	public function __construct($path = "", $arProperties = array())
	{
		$this->path = $path;
		$this->arProperties = $arProperties;
	}

	public function GetPath()
	{
		return $this->path;
	}

	public function AddProperty()
	{
		$n = func_num_args();
		if ($n < 1)
			return;

		$args = func_get_args();
		if (is_array($args[0]))
		{
			$this->arProperties[] = $args[0];
		}
		else
		{
			if ($n > 1 && is_array($args[1]))
			{
				if (count($args[1]) > 0 && is_string($args[1][0]))
					$args[1] = array($args[1]);

				$content = $args[1];
				$args[1] = array();
				foreach ($content as $val)
					$args[1][] = self::MakeProp($val);
			}
			$this->arProperties[] = self::MakeProp($args);
		}
	}

	public function GetProperties()
	{
		return $this->arProperties;
	}

	public function ExtractFromLock($path, $arLock)
	{
		$this->path = CDav::CheckIfRightSlashAdded($path);
		$this->arProperties = array(
			self::MakeProp("displayname", $path),
			self::MakeProp("creationdate", $arLock['CREATED']),
			self::MakeProp("getlastmodified", $arLock['MODIFIED']),
			self::MakeProp("resourcetype", ""),
			self::MakeProp("getcontenttype", ""),
			self::MakeProp("getcontentlength", 0)
		);
	}

	public static function MakeProp()
	{
		$n = func_num_args();
		if ($n < 1)
			return null;

		$args = func_get_args();
		if (is_array($args[0]))
		{
			$n = count($args[0]);
			$args = $args[0];
		}

		return array(
			'xmlns' => ($n > 2) ? $args[2] : "DAV:",
			'tagname' => ($n > 0) ? $args[0] : "",
			'content' => ($n > 1) ? $args[1] : "",
			'raw' => ($n > 3) ? $args[3] : false,
			'status' => ($n > 4) ? $args[4] : null,
		);
	}

	public static function RenderProperty($arProp, &$xmlnsHash, &$response, &$request)
	{
		if (!is_array($arProp))
			return;
		if (!isset($arProp["tagname"]))
			return;

		if (!isset($arProp["content"]) || $arProp["content"] === "" || $arProp["content"] === false)
		{
			if ($arProp["xmlns"] == "DAV:")
				$response->AddLine("     <D:%s/>", $arProp["tagname"]);
			elseif (!empty($arProp["xmlns"]))
				$response->AddLine("     <%s:%s/>", $xmlnsHash[$arProp["xmlns"]], $arProp["tagname"]);
			else
				$response->AddLine("     <%s xmlns=\"\"/>", $arProp["tagname"]);
		}
		elseif ($arProp["xmlns"] == "DAV:")
		{
			switch ($arProp["tagname"])
			{
				case "creationdate":
					$response->AddLine("     <D:creationdate ns0:dt=\"dateTime.tz\">%s</D:creationdate>", gmdate("Y-m-d\\TH:i:s\\Z", $arProp["content"]));
					break;
				case "getlastmodified":
					$response->AddLine("     <D:getlastmodified ns0:dt=\"dateTime.rfc1123\">%sGMT</D:getlastmodified>", gmdate("D, d M Y H:i:s ", $arProp["content"]));
					break;
				case "supportedlock":
					$response->AddLine("     <D:supportedlock>%s</D:supportedlock>", $arProp["content"]);
					break;
				case "lockdiscovery":
					$response->AddLine("     <D:lockdiscovery>");
					$response->AddLine($arProp["content"]);
					$response->AddLine("     </D:lockdiscovery>");
					break;
				default:
					$xmlnsDefs = '';
					if (is_array($arProp["content"]))
					{
						$xmlnsHashTmp = $xmlnsHash;
						$val = self::EncodeHierarchicalProp($arProp["content"], 'DAV:', $xmlnsDefs, $xmlnsHashTmp, $response, $request);
					}
					elseif ($arProp['raw'])
					{
						$val = $response->Encode(sprintf('<![CDATA[%s]]>', $arProp["content"]));
					}
					else
					{
						$val = $response->Encode(htmlspecialcharsbx($arProp["content"]));
					}
					$response->AddLine("     <D:%s%s>%s</D:%s>", $arProp["tagname"], $xmlnsDefs, $val, $arProp["tagname"]);
					break;
			}
		}
		else
		{
			if (is_array($arProp["content"]))
			{
				$vals = '';
				$extraXmlns = '';
				foreach ($arProp["content"] as $arSubProp)
				{
					if ($arSubProp['xmlns'] && $arSubProp['xmlns'] != 'DAV:')
					{
						if (!isset($xmlnsHash[$arSubProp['xmlns']]))
						{
							$ns = "ns".(count($xmlnsHash) + 1);
							$xmlnsHash[$arSubProp['xmlns']] = $ns;
						}
						else
						{
							$ns = $xmlnsHash[$arSubProp['xmlns']];
						}
						if (strpos($extraXmlns, $extra = ' xmlns:'.$ns.'="'.$arSubProp['xmlns'].'"') === false)
							$extraXmlns .= $extra;
						$ns .= ':';
					}
					elseif ($arSubProp['xmlns'] == 'DAV:')
					{
						$ns = 'D:';
					}
					else
					{
						$ns = '';
					}
					$vals .= sprintf("<%s%s", $ns, $arSubProp["tagname"]);
					if (is_array($arSubProp['content']))
					{
						foreach ($arSubProp['content'] as $attr => $val)
							$vals .= ' '.$attr.'="'.$response->Encode(htmlspecialcharsbx($val)).'"';
						$vals .= '/>';
					}
					else
					{
						$vals .= '>';
						if ($arSubProp['raw'])
							$vals .= $response->Encode(sprintf('<![CDATA[%s]]>', $arSubProp['content']));
						else
							$vals .= $response->Encode(htmlspecialcharsbx($arSubProp['content']));
						$vals .= sprintf("</%s%s>", $ns, $arSubProp["tagname"]);
					}
				}
				$response->AddLine("     <%s:%s%s>%s</%s:%s>", $xmlnsHash[$arProp['xmlns']], $arProp['tagname'], $extraXmlns, $vals, $xmlnsHash[$arProp['xmlns']], $arProp['tagname']);
			}
			else
			{
				if ($arProp['raw'])
					$val = sprintf('<![CDATA[%s]]>', $arProp['content']);
				else
					$val = htmlspecialcharsbx($arProp['content']);
				$val = $response->Encode($val);
				if ($arProp['xmlns'])
					$response->AddLine("     <%s:%s>%s</%s:%s>", $xmlnsHash[$arProp['xmlns']], $arProp['tagname'], $val, $xmlnsHash[$arProp['xmlns']], $arProp['tagname']);
				else
					$response->AddLine("     <%s xmlns=\"\">%s</%s>", $arProp['tagname'], $val, $arProp['tagname']);
			}
		}
	}

	// Encode a hierarchical properties
	public function EncodeHierarchicalProp(array $arProps, $parentXmlns, &$xmlnsDefs, array &$xmlnsHash, &$response, &$request)
	{
		$result = '';

		if (isset($arProps['tagname']))
			$arProps = array($arProps);

		$isRNDRequired = $request->IsRedundantNamespaceDeclarationsRequired();
		foreach ($arProps as $arProp)
		{
			if (!isset($xmlnsHash[$arProp['xmlns']]))
			{
				$n = 'ns'.(count($xmlnsHash) + 1);
				$xmlnsHash[$arProp['xmlns']] = $n;
				$xmlnsDefs .= ' xmlns:'.$n.'="'.$arProp['xmlns'].'"';
			}

			$ns = ($arProp['xmlns'] == $parentXmlns ? ($isRNDRequired ? $xmlnsHash[$parentXmlns].':' : '') : $xmlnsHash[$arProp['xmlns']].':');

			if (is_array($arProp['content']))
			{
				$arSubProp = $arProp['content'];
				if (isset($arSubProp['xmlns']) || isset($arSubProp[0]['xmlns']))
				{
					$result .= sprintf('<%s%s', $ns, $arProp['tagname']);
					if (empty($arProp['content']))
					{
						$result .= '/>';
					}
					else
					{
						$result .= '>';
						$result .= self::EncodeHierarchicalProp($arProp['content'], $arProp['xmlns'], $xmlnsDefs, $xmlnsHash, $response, $request);
						$result .= sprintf('</%s%s>', $ns, $arProp['tagname']);
					}
				}
				else
				{
					$vals = '';
					foreach ($arSubProp as $attr => $val)
						$vals .= ' '.$attr.'="'.htmlspecialcharsbx($val).'"';

					$result .= sprintf('<%s%s%s/>', $ns, $arProp['tagname'], $vals);
				}
			}
			else
			{
				if (empty($arProp['content']))
				{
					$val = '';
				}
				else
				{
					if ($arProp['raw'])
						$val = $response->Encode(sprintf('<![CDATA[%s]]>', $arProp['content']));
					else
						$val = $response->Encode(htmlspecialcharsbx($arProp['content']));
				}

				$result .= sprintf('<%s%s', $ns, $arProp['tagname']);
				if (empty($arProp['content']))
					$result .= '/>';
				else
					$result .= '>'.$val.sprintf('</%s%s>', $ns, $arProp['tagname']);
			}
		}

		return $result;
	}
}
?>