<?
class CXMPPParser
{
	var $arTagList = array();
	var $arTagValue = array();
	var $array;
	var $string;
	var $raw;

	function ToArray($text)
	{
		$parser = new CXMPPParser($text);
		if (!$parser->Parse())
			return false;
		return $parser->array;
	}

	function ToXml($ar)
	{
		$parser = new CXMPPParser();
		$text = $parser->toString($ar);
		return $text;
	}

	function CXMPPParser($raw=false)
	{
		$this->raw = $this->ConvertCharsetToSite(trim($raw));
	}

	function ReadTags($pos = 0)
	{
		$str = $this->raw;

		$start = strpos($str,'<',$pos);
		$end = strpos($str,'>',$start)+1;

		if ($start===false || $end===false)
			return;

		$tag = trim(substr($str,$start,$end-$start));
		if ($tag)
		{
			$this->arTagList[] = array($tag,$start,$end);

			if ($end < strlen($str))
				return $end;
			else
				return true;
		}
		return;
	}

	function Parse()
	{
		$r = 0;
		while(is_numeric($r = $this->ReadTags($r)));
		if ($r !== true)
			return;

		$arTmpTags = array();
		$child = array();
		$level_items = array();
		$level = 0;
		$bLastOpenTag = false;
		foreach($this->arTagList as $arTag) // парсим теги
		{
			$name = $this->GetName($arTag[0]);
//			if ($name == 'stream:stream')
//				continue;

			if (substr($arTag[0],-2) == '/>') // сам себя закрывает
			{
				$val = array(
					'.' => $this->GetAttr($arTag[0]),
					'#' => ''
				);

				if (!$level_items[$level][$name])
					$level_items[$level][$name] = $val;
				elseif (!$level_items[$level][$name][0])
					$level_items[$level][$name] = array($level_items[$level][$name], $val);
				else
					$level_items[$level][$name][] = $val;

				$child = $level_items[$level];
				$bLastOpenTag = 0;
			}
			elseif (substr($arTag[0],0,2) != '</') // открывающий
			{
				$level++;
				$arTmpTags[] = $arTag;
				$bLastOpenTag = 1;
			}
			else // закрывающий
			{
				unset($level_items[$level]);
				$level--;
				$arOpenTag = array_pop($arTmpTags);
				$open_name = $this->GetName($arOpenTag[0]);

				if ($open_name == $name)
				{
					if ($bLastOpenTag) // string
					{
						$start = $arOpenTag[2];
						$end = $arTag[1];
						$val = array('#' => substr($this->raw,$start,$end-$start));
					}
					else
						$val = $child;

					$val = array_merge(array('.' => $this->GetAttr($arOpenTag[0])), $val);

					if (!$level_items[$level][$name])
						$level_items[$level][$name] = $val;
					elseif (!$level_items[$level][$name][0])
						$level_items[$level][$name] = array($level_items[$level][$name], $val);
					else
						$level_items[$level][$name][] = $val;

					$child = $level_items[$level];
				}
				else
					return; // закрывается не текущий тег

				$bLastOpenTag = 0;
			}
		}
		if ($level != 0) // остались открытые или есть незакрытые
			return;

		$this->array = $child;
		return true;
	}

	function __toStringInternal($ar = false)
	{
		if ($ar === false)
			$ar = $this->array;

		$str = '';
		foreach($ar as $name => $child)
		{
			$attr = array();
			$content = null;

			if (array_key_exists('#', $child))
			{
				$content = $child['#'];
				if (is_array($child['.']))
					foreach($child['.'] as $k => $v)
						$attr[] = $k.'="'.$v.'"'; // кавычки в атрибутах не ждём
			}
			else
			{
				if ($name == '.')
					continue;
				else
				{
					if ($child[0])
					{
						if (is_array($child))
							foreach($child as $item)
								$str .=  $this->__toStringInternal(array($name => $item));
						continue;
					}
					else
					{
						if (is_array($child['.']))
							foreach($child['.'] as $k => $v)
								$attr[] = $k.'="'.$v.'"'; // кавычки в атрибутах не ждём
						$content = $this->__toStringInternal($child);
					}
				}
			}
			$str .= '<'.$name.(count($attr)?' '.implode(' ',$attr):'').(isset($content)?'>'.$content.'</'.$name.'>':'/>');#."\n";
		}


		$this->string = $str;
		return $this->string;
	}

	function toString($ar = false)
	{
		$r = $this->__toStringInternal($ar);
		$r = $this->ConvertCharsetFromSite($r);
		return $r;
	}

	function ConvertCharsetToSite($text)
	{
		if (!defined('BX_UTF'))
			$text = $GLOBALS["APPLICATION"]->ConvertCharset($text, "UTF-8", SITE_CHARSET);

		return $text;
	}

	function ConvertCharsetFromSite($text)
	{
		if (!defined('BX_UTF'))
			$text = $GLOBALS["APPLICATION"]->ConvertCharset($text, SITE_CHARSET, "UTF-8");

		return $text;
	}

	function GetName($tag)
	{
		$pos = strpos($tag,' ');
		if (!$pos)
			$pos = strpos($tag,'>');
		if (!$pos) // 0 тоже не катит
			return;

		return strtolower(trim(substr($tag,1,$pos - 1),'/'));
	}

	function GetAttr($tag)
	{
		if (($pos = strpos($tag,' '))===false)
			return array();

		$tag = substr($tag,$pos,-1);
		$l = strlen($tag);

		$arAttr = array();
		$bParam = true;
		$param = "";

		for ($i=0;$i<$l;$i++)
		{
			$chr = $tag[$i];
			if ($bParam)
			{
				if ($chr == '=')
				{
					$bParam = false;
					continue;
				}
				else
					$param .= $chr;
			}
			else
			{
				if ($chr == '"' || $chr = "'")
				{
					$open = $chr;
					$pos = strpos($tag,$open,$i+1);
					if ($pos === false)
						return;

					$val = substr($tag,$i+1,$pos-$i-1);
					$arAttr[trim($param)] = $val;
					$i = $pos;
					$param = '';
					$val = '';
					$bParam = true;
				}
			}
		}

		return $arAttr;
	}
}
?>
