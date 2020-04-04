<?
class CDavICalendar
{
	private $component;

	public function __construct($cal, $siteId = null)
	{
		if (!isset($cal) || !is_array($cal) && !is_string($cal))
			return;

		$this->component = new CDavICalendarComponent();

		if (is_string($cal))
		{
			$this->component->InitializeFromString($cal);
		}
		else
		{
			$this->component->SetType('VCALENDAR');

			$this->component->SetProperties(
				array(
					new CDavICalendarProperty('VERSION:2.0'),
					new CDavICalendarProperty('PRODID:-//davical.org//NONSGML AWL Calendar//EN'),
					//new CDavICalendarProperty('CALSCALE:GREGORIAN'),
					new CDavICalendarProperty('METHOD:PUBLISH')
				)
			);

			$arComps = array();

			$tz = CDavICalendarTimeZone::GetTimezone(CDavICalendarTimeZone::getTimeZoneId());
			if (!empty($tz))
			{
				$comptz = new CDavICalendarComponent();
				$comptz->InitializeFromString($tz);
				$arComps[] = $comptz;
			}

			$comp = new CDavICalendarComponent();
			$comp->InitializeFromArray($cal);
			$arComps[] = $comp;

			$this->component->SetComponents($arComps);
		}
	}

	public function Render($restrictProperties = null)
	{
		return trim($this->component->Render($restrictProperties));
	}

	public function GetComponents($type = null, $normalMatch = true)
	{
		return $this->component->GetComponents($type, $normalMatch);
	}

	public function GetComponentsByProperty($type, $property, $propertyValue = null)
	{
		$arComponents = $this->component->GetComponents($type, true);

		$arKeys = array_keys($arComponents);
		foreach ($arKeys as $key)
		{
			$val = $arComponents[$key]->GetPropertyValue($property);
			if ($val == null || $propertyValue != null && $val != $propertyValue)
				unset($arComponents[$key]);
		}

		$arComponents = array_values($arComponents);

		return $arComponents;
	}
}

class CDavICalendarComponent
{
	private $type;				// Type of component ('VEVENT', 'VTODO', 'VTIMEZONE', etc.)
	private $arProperties;		// Array of properties (CDavICalendarProperty)
	private $arComponents;		// Array of subcomponents (CDavICalendarComponent)

	public function __construct($content = null)
	{
		$this->type = "";
		$this->arProperties = array();
		$this->arComponents = array();

		if ($content != null)
		{
			if (is_array($content))
				$this->InitializeFromArray($content);
			else
				$this->InitializeFromString($content);
		}
	}

	public function InitializeFromArray($arContent)
	{
		$this->SetType(isset($arContent["TYPE"]) ? $arContent["TYPE"] : "VEVENT");
		unset($arContent["TYPE"]);

		$arProperties = array();
		$arComponents = array();
		foreach ($arContent as $key => $val)
		{
			if (is_array($val) && substr($key, 0, 1) == "@")
			{
				$arComponents[] = new CDavICalendarComponent($val);
			}
			elseif (is_array($val) && !array_key_exists("PARAMETERS", $val) && !array_key_exists("VALUE", $val))
			{
				foreach ($val as $val1)
					$arProperties[] = new CDavICalendarProperty($key, $val1);
			}
			else
			{
				$arProperties[] = new CDavICalendarProperty($key, $val);
			}
		}

		$this->SetProperties($arProperties);
		$this->SetComponents($arComponents);
	}

	public function InitializeFromString($content)
	{
		$content = $this->UnwrapComponent($content);

		$type = false;
		$subtype = false;
		$finish = null;
		$subfinish = null;

		$length = strlen($content);
		$linefrom = 0;
		while ($linefrom < $length)
		{
			$lineto = strpos($content, "\n", $linefrom);
			if ($lineto === false)
				$lineto = strpos($content, "\r", $linefrom);

			if ($lineto > 0)
			{
				$line = substr($content, $linefrom, $lineto - $linefrom);
				$linefrom = $lineto + 1;
			}
			else
			{
				$line = substr($content, $linefrom);
				$linefrom = $length;
			}
			if (preg_match('/^\s*$/', $line))
				continue;
			$line = rtrim($line, "\r\n");

			if ($type === false)
			{
				if (preg_match('/^BEGIN:(.+)$/', $line, $matches))
				{
					$type = $matches[1];
					$finish = "END:$type";
					$this->type = $type;
				}
			}
			elseif ($type == null)
			{
			}
			elseif ($line == $finish)
			{
				$type = null;
			}
			else
			{
				if ($subtype === false && preg_match('/^BEGIN:(.+)$/', $line, $matches))
				{
					$subtype = $matches[1];
					$subfinish = "END:$subtype";
					$subcomponent = $line."\r\n";
				}
				elseif ($subtype)
				{
					$subcomponent .= $this->WrapComponent($line);
					if ($line == $subfinish)
					{
						$this->arComponents[] = new CDavICalendarComponent($subcomponent);
						$subtype = false;
					}
				}
				else
				{
					$this->arProperties[] = new CDavICalendarProperty($line);
				}
			}
		}
	}

	/**
	* This unescapes the (CRLF + linear space) wrapping specified in RFC2445. According
	* to RFC2445 we should always end with CRLF but the CalDAV spec says that normalising
	* XML parsers often muck with it and may remove the CR.  We accept either case.
	*/
	private function UnwrapComponent($content)
	{
		return preg_replace('/\r?\n[ \t]/', '', $content);
	}

	/**
	* This imposes the (CRLF + linear space) wrapping specified in RFC2445. According
	* to RFC2445 we should always end with CRLF but the CalDAV spec says that normalising
	* XML parsers often muck with it and may remove the CR.  We output RFC2445 compliance.
	*
	* In order to preserve pre-existing wrapping in the component, we split the incoming
	* string on line breaks before running wordwrap over each component of that.
	*/
	private function WrapComponent($content)
	{
		$strs = preg_split("/\r?\n/", $content);

		$wrapped = "";
		foreach ($strs as $str)
			$wrapped .= preg_replace('/(.{72})/'.BX_UTF_PCRE_MODIFIER, "\\1\r\n ", $str)."\r\n";

		return $wrapped;
	}

	public function GetType()
	{
		return $this->type;
	}

	public function SetType($type)
	{
		$this->type = $type;
	}

	/**
	 * @param null $type
	 * @return CDavICalendarProperty[]
	 */
	public function GetProperties($type = null)
	{
		$arProps = array();
		foreach ($this->arProperties as $val)
		{
			if ($type == null || $val->Name() == $type)
				$arProps[] = $val;
		}
		return $arProps;
	}

	public function SetProperties($arProperties, $type = null)
	{
		if (!is_array($arProperties))
			$arProperties = array($arProperties);

		$this->ClearProperties($type);

		foreach ($arProperties as $val)
			$this->arProperties[] = $val;
	}

	private function ClearProperties($type = null)
	{
		if ($type != null)
		{
			$keys = array_keys($this->arProperties);
			foreach ($keys as $key)
			{
				if ($this->arProperties[$key]->Name() == $type)
					unset($this->arProperties[$key]);
			}
			$this->arProperties = array_values($this->arProperties);
		}
		else
		{
			$this->arProperties = array();
		}
	}

	/**
	* Get the value of the first property matching the name. Obviously this isn't
	* so useful for properties which may occur multiply, but most don't.
	*
	* @param string $type The type of property we are after.
	* @return string The value of the property, or null if there was no such property.
	*/
	public function GetPropertyValue($type)
	{
		foreach ($this->arProperties as $key => $val)
		{
			if ($val->Name() == $type)
				return $val->Value();
		}
		return null;
	}

	public function GetPropertyValueParsed($type)
	{
		$v = $this->GetPropertyValue($type);
		if (is_null($v))
			return $v;

		$arResult = array();

		$arV = explode(';', $v);
		foreach ($arV as $v1)
		{
			$pos = strpos($v1, '=');
			$name = substr($v1, 0, $pos);
			$value = substr($v1, $pos + 1);
			$arResult[$name] = $value;
		}

		return $arResult;
	}

	public function GetPropertyParameter($type, $name)
	{
		foreach ($this->arProperties as $key => $val)
		{
			if ($val->Name() == $type)
				return $val->Parameter($name);
		}
		return null;
	}

	/**
	* Get all sub-components, or at least get those matching a type, or failling to match,
	* should the second parameter be set to false.
	*
	* @param string $type The type to match (default: All)
	* @param boolean $normal_match Set to false to invert the match (default: true)
	* @return array an array of the sub-components
	*/
	public function GetComponents($type = null, $normalMatch = true)
	{
		$arComponents = $this->arComponents;
		if ($type != null)
		{
			foreach ($arComponents as $key => $val)
			{
				if (($val->GetType() != $type) === $normalMatch)
					unset($arComponents[$key]);
			}
			$arComponents = array_values($arComponents);
		}
		return $arComponents;
	}

	public function SetComponents($arComponents, $type = null)
	{
		if (!is_array($arComponents))
			$arComponents = array($arComponents);

		$this->ClearComponents($type);

		foreach ($arComponents as $val)
			$this->arComponents[] = $val;
	}

	private function ClearComponents($type = null)
	{
		if ($type != null)
		{
			$keys = array_keys($this->arComponents);
			foreach ($keys as $key)
			{
				if ($this->arComponents[$key]->GetType() == $type)
					unset($this->arComponents[$key]);
				else
					$this->arComponents[$key]->ClearComponents($type);
			}
		}
		else
		{
			$this->arComponents = array();
		}
	}

	public function Render($restrictedProperties = null)
	{
		$bUnrestricted = (!isset($restrictedProperties) || count($restrictedProperties) == 0);
		$result = "BEGIN:".$this->type."\n";
		foreach ($this->arProperties as $prop)
		{
			if (method_exists($prop, 'Render'))
			{
				if ($bUnrestricted || isset($restrictedProperties[$prop]))
					$result .= $prop->Render()."\n";
			}
		}
		foreach ($this->arComponents as $comp)
			$result .= $comp->Render();
		$result .= "END:".$this->type."\n";

		return $result;
	}

	public function __Render($restrictedProperties = null)
	{
		$bUnrestricted = (!isset($restrictedProperties) || count($restrictedProperties) == 0);

		$result = "BEGIN:".$this->type."\r\n";
		foreach ($this->arProperties as $prop)
		{
			if (method_exists($prop, 'Render'))
			{
				if ($bUnrestricted || isset($restrictedProperties[$prop]))
					$result .= $prop->Render()."\r\n";
			}
		}
		foreach ($this->arComponents as $comp)
			$result .= $comp->Render();
		$result .= "END:".$this->type."\r\n";

		return $result;
	}
}

class CDavICalendarProperty
{
	private $name;			// Property name
	private $arParameters;	// Property parameters (key/value pairs)
	private $content;		// Property value

	public function __construct($name = null, $value = null)
	{
		$this->name = "";
		$this->content = "";
		$this->arParameters = array();

		if ($name != null)
		{
			if ($value == null)
				$this->InitializeFromString($name);
			else
				$this->InitializeFromArray($name, $value);
		}
	}

	public function InitializeFromArray($name, $value)
	{
		$this->name = $name;
		if (is_array($value))
		{
			$this->content = (array_key_exists("VALUE", $value)) ? $value["VALUE"] : "";
			$this->arParameters = (array_key_exists("PARAMETERS", $value)) ? $value["PARAMETERS"] : array();
		}
		else
		{
			$this->content = $value;
			$this->arParameters = array();
		}
	}

	public function InitializeFromString($prop)
	{
		$pn = '[a-z0-9-]+';
		$pv = '(?:[^";:,]*|"[^"]*")';

		if (preg_match(sprintf('/^(%1$s(?:;%1$s=%2$s(?:,%2$s)*)*):(.+)$/i', $pn, $pv), $prop, $matches))
		{
			$propStart = $matches[1];
			$propEnd   = $matches[2];
		}
		else
		{
			$pos = strpos($prop, ':');

			$propStart = substr($prop, 0, $pos);
			$propEnd   = substr($prop, $pos + 1);
		}

		$propEnd = str_replace(array('\\N', '\\n'), "\n", $propEnd);
		$this->content = preg_replace('/\\\\([,;\\\\])/', '$1', $propEnd);

		$arParams = explode(';', $propStart);
		$this->name = array_shift($arParams);
		$this->arParameters = array();
		foreach ($arParams as $val)
		{
			$pos = strpos($val, '=');
			$name = substr($val, 0, $pos);
			$value = substr($val, $pos + 1);
			$this->arParameters[$name] = $value;
		}
	}

	public function Name($newname = null)
	{
		if ($newname != null)
			$this->name = $newname;

		return $this->name;
	}

	public function Value($newvalue = null)
	{
		if ($newvalue != null)
			$this->content = $newvalue;

		return $this->content;
	}

	public function Parameter($name, $newparamvalue = null)
	{
		if (empty($name))
			return null;

		if ($newparamvalue != null)
			$this->arParameters[$name] = $newparamvalue;

		if (isset($this->arParameters[$name]))
			return $this->arParameters[$name];

		return null;
	}

	public function Render()
	{
		static $arRender1 = array('ATTACH', 'GEO', 'PERCENT-COMPLETE', 'PRIORITY', 'DURATION', 'FREEBUSY', 'TZOFFSETFROM', 'TZOFFSETTO', 'TZURL', 'ATTENDEE', 'ORGANIZER', 'RECURRENCE-ID', 'URL', 'EXRULE', 'SEQUENCE', 'CREATED', 'RRULE', 'REPEAT', 'TRIGGER', 'N', 'ADR');
		static $arRender2 = array('COMPLETED', 'DTEND', 'DUE', 'DTSTART', 'DTSTAMP', 'LAST-MODIFIED', 'CREATED', 'EXDATE', 'RDATE');

		$name = preg_replace('/[;].*$/', '', $this->name);
		$str = $this->content;
		$str = strip_tags($str);

		if (in_array($name, $arRender2))
		{
			if (isset($this->arParameters['VALUE']) && $this->arParameters['VALUE'] == 'DATE' && !strpos($str, ','))
				$str = substr($str, 0, 8);
		}
		elseif (isset($this->arParameters['ENCODING']) && $this->arParameters['ENCODING'] == 'BASE64')
		{
		}
		elseif (!in_array($name, $arRender1))
		{
			$str = preg_replace('/([,;\\\\])/', '\\\\$1', $str);
			$str = preg_replace('/\r?\n/', '\\\\n', $str);
		}

		$name = sprintf("%s%s:", $this->name, $this->RenderParameters());
		if ((strlen($name) + strlen($str)) <= 72)
			$result = $name.$str;
		elseif ((strlen($name) + strlen($str)) > 72 && (strlen($name) < 72) && (strlen($str) < 72))
			$result = $name."\r\n ".$str;
		else
			$result = preg_replace('/(.{72})/'.BX_UTF_PCRE_MODIFIER, '$1'."\r\n ", $name.$str);

		return $result;
	}

	private function RenderParameters()
	{
		$result = "";
		foreach ($this->arParameters as $key => $val)
		{
			$str = str_replace('"', '', $val);
			$result .= sprintf(
				preg_match('/[,;:]/', $str) ? ';%s="%s"' : ';%s=%s',
				$key, $str
			);
		}
		return $result;
	}

}
?>