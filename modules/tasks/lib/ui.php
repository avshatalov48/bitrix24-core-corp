<?
/**
 * Namespace contains functions\classes for UI render purposes
 */

namespace Bitrix\Tasks;

use \Bitrix\Main\Web\Json;

class UI
{
	public static function toLowerCaseFirst($str)
	{
		$str = (string) $str;

		if(!strlen($str))
		{
			return $str;
		}

		return ToLower(substr($str, 0, 1)).substr($str, 1);
	}

	public static function getPluralForm($n)
	{
		return ( (($n%10 === 1) && ($n%100 !== 11)) ? 0 : ((($n%10 >= 2) && ($n%10 <= 4) && (($n%100 < 10) || ($n%100 >= 20))) ? 1 : 2) );
	}

	/**
	 * Converts placeholders in #PLACEHOLDER# into {{PLACEHOLDER}} notation by using mapping
	 *
	 * @param $path
	 * @param array $map
	 * @return string
	 */
	public static function convertActionPathToBarNotation($path, array $map = array())
	{
		if($path == '')
		{
			return $path;
		}

		if(!empty($map))
		{
			foreach($map as $from => $to)
			{
				$path = str_replace(
					array('#'.$from.'#', '#'.ToLower($from).'#', '#'.ToUpper($from).'#'),
					'{{'.$to.'}}',
					$path);
			}
		}
		else
		{
			$path = preg_replace_callback('/#([^#]+)#/', function($matches){
				return '{{'.ToUpper($matches[1]).'}}';
			}, $path);
		}

		return $path;
	}

	public static function getAvatar($fileId, $width = 50, $height = 50)
	{
		$fileId = intval($fileId);
		if ($fileId < 1) {
			return "";
		}

		$file = \CFile::GetFileArray($fileId);
		if ($file !== false)
		{
			$fileInfo = \CFile::ResizeImageGet(
				$file,
				array("width" => $width, "height" => $height),
				BX_RESIZE_IMAGE_EXACT,
				false
			);

			return $fileInfo["src"];
		}

		return "";
	}

	public static function getAvatarFile($fileId, array $parameters = array('WIDTH' => 50, 'HEIGHT' => 50))
	{
		$fileId = intval($fileId);
		if(!$fileId)
		{
			return array();
		}

		$file = \CFile::GetFileArray($fileId);
		if ($file !== false)
		{
			$fileInfo = \CFile::ResizeImageGet(
				$file,
				array("width" => $parameters['WIDTH'], "height" => $parameters['HEIGHT']),
				BX_RESIZE_IMAGE_EXACT,
				false
			);

			return array(
				'ORIGIN' => $file,
				'RESIZED' => array(
					'SRC' => $fileInfo['src']
				),
			);
		}

		return array();
	}

	public static function formatTimezoneOffsetUTC($offset)
	{
		return 'UTC '.($offset > 0 ? '+' : '').static::formatTimeAmount($offset, 'HH:MI');
	}

	public static function formatTimeAmount($time, $format = 'HH:MI:SS')
	{
		$time = intval($time);

		// todo: could be other formats, i.e. with T placeholder...

		$printFFormat = '%02d:%02d:%02d';
		if($format == 'HH:MI')
		{
			$printFFormat = '%02d:%02d';
		}

		if(!is_numeric($time))
		{
			return '';
		}

		$sign = $time < 0 ? '-' : '';
		$time = abs($time);

		return $sign.sprintf(
			$printFFormat,
			floor($time / 3600),	// hours
			floor($time / 60) % 60,	// minutes
			$time % 60				// seconds
		);
	}

	/**
	 * Function parses time into an amount of seconds. I.e. '100:30' will be parsed to 361800
	 *
	 * @param $time
	 * @return int
	 *
	 * @access private
	 */
	public static function parseTimeAmount($time, $format = 'HH:MI:SS')
	{
		$time = trim((string) $time);

		if($time == '')
		{
			return 0;
		}

		// todo: stop ignoring $format here
		$found = array();
		if(!preg_match('#^(\d{1,2}):(\d{1,2})?$#', $time, $found))
		{
			return 0;
		}

		$h = intval($found[1]);
		$m = intval($found[2]);

		if(($h < 0 || $h > 23 || $m < 0 || $m > 59))
		{
			return 0;
		}

		return $h*3600 + $m*60;
	}

	public static function getDateTimeFormat()
	{
		if(defined('FORMAT_DATETIME'))
		{
			$format = FORMAT_DATETIME;
		}
		else
		{
			$format = \CSite::GetDateFormat("FULL");
		}

		return $GLOBALS['DB']->DateFormatToPHP($format); // have to make php format from site format
	}

	public static function getDateTimeFormatShort()
	{
		if(defined('FORMAT_DATE'))
		{
			$format = FORMAT_DATE;
		}
		else
		{
			$format = \CSite::GetDateFormat("SHORT");
		}

		return $GLOBALS['DB']->dateFormatToPHP($format); // have to make php format from site format
	}

	/**
	 * Converts timestamp into the time string in site format without any additional decorations
	 *
	 * todo: make this function even smarter: detect if $stamp is a unix timestamp or a date string
	 * todo: in the last case, provide $fromFormat argument
	 *
	 * @param $stamp
	 * @param bool|string $format in php notation
	 * @return string
	 */
	public static function formatDateTime($stamp, $format = false)
	{
		$simple = false;

		// accept also FORMAT_DATE and FORMAT_DATETIME as ones of the legal formats
		if((defined('FORMAT_DATE') && $format == FORMAT_DATE) || (defined('FORMAT_DATETIME') && $format == FORMAT_DATETIME))
		{
			$format = $GLOBALS['DB']->dateFormatToPHP($format);
			$simple = true;
		}

		$default = static::getDateTimeFormat();
		if($format === false)
		{
			$format = $default;
			$simple = true;
		}

		if($simple)
		{
			// its a simple format, we can use a simpler function
			return date($format, $stamp);
		}
		else
		{
			return \FormatDate($format, $stamp);
		}
	}

	public static function formatDateTimeSiteL2S($time)
	{
		return static::formatDateTime(static::parseDateTime($time), static::getDateTimeFormatShort());
	}

	/**
	 * Converts time string from database format into site format:
	 * 2016-11-15 15:40:00 => 15.11.2016 15:40:00
	 *
	 * @param $value
	 * @return bool|string
	 */
	public static function formatDateTimeFromDB($value)
	{
		return \CDatabase::FormatDate($value, "YYYY-MM-DD HH:MI:SS", \CLang::GetDateFormat("FULL"));
	}

	/**
	 * Parses date time string. Note: this function wont work with date time formatted with format with additions.
	 *
	 * @param $dayTime
	 * @return bool|int
	 */
	public static function parseDateTime($dayTime)
	{
		return \MakeTimeStamp($dayTime);
	}

	/**
	 * Checks if a argument is a legal time string
	 *
	 * @param $dayTime
	 * @return bool|int
	 */
	public static function checkDateTime($dayTime)
	{
		$dayTime = \Bitrix\Tasks\Util::trim($dayTime);
		if($dayTime == '' || !\CheckDateTime($dayTime))
		{
			return '';
		}

		return $dayTime;
	}

	public static function getHintState()
	{
		$result = array();

		$options = \Bitrix\Tasks\Util\User::getOption('task_hints');
		if(is_array($options))
		{
			foreach($options as $name => $value)
			{
				$result[$name] = $value == 'Y';
			}
		}

		return $result;
	}

	public static function sanitizeString($string, array $allowedTags = array())
	{
		$Sanitizer = new \CBXSanitizer;
		$Sanitizer->AddTags($allowedTags);
		return $Sanitizer->SanitizeHtml($string);
	}

	/**
	 * Use when you need to display bbcode-d text as (safe) html
	 *
	 * @param $value
	 * @param array $parameters
	 * @return string
	 *
	 */
	public static function convertBBCodeToHtml($value, array $parameters = array())
	{
		$value = (string) $value;
		if($value == '')
		{
			return '';
		}

		$preset = $parameters['PRESET'] == 'BASIC' ? 'BASIC' : 'FULL';

		if($preset == 'FULL')
		{
			$parser = \Bitrix\Tasks\Util::getParser($parameters);

			if(!empty($parameters["USER_FIELDS"]))
			{
				$parser->arUserfields = $parameters["USER_FIELDS"];
			}

			$rules = array(
				"HTML" => "N",
				"ALIGN" => "Y",
				"ANCHOR" => "Y", "BIU" => "Y",
				"IMG" => "Y", "QUOTE" => "Y",
				"CODE" => "Y", "FONT" => "Y",
				"LIST" => "Y", "SMILES" => "Y",
				"NL2BR" => "Y", "MULTIPLE_BR" => "N",
				"VIDEO" => "Y", "LOG_VIDEO" => "N",
				"SHORT_ANCHOR" => "Y",
				"USERFIELDS" => $parameters["USER_FIELDS"]
			);

			return $parser->convert(
				$value,
				$rules,
				"html",
				array()
			);
		}
		else
		{
			$parser = new \CTextParser();
			$rules = array('ANCHOR' => 'Y', 'BIU' => 'Y', 'HTML' => 'N');
			$parser->allow = $rules;

			return $parser->convertText($value);
		}
	}

	public static function convertBBCodeToHtmlSimple($value)
	{
		return static::convertBBCodeToHtml($value, array('PRESET' => 'BASIC'));
	}

	public static function convertHtmlToBBCode($value)
	{
		$id = AddEventHandler("main", "TextParserBeforeAnchorTags", Array("\\Bitrix\\Tasks\\UI", "convertHtmlToBBCodeHack"));

		$TextParser = \Bitrix\Tasks\Util::getParser();
		$TextParser->allow = array(
			"HTML" => "N",
			"ANCHOR" => "Y",
			"BIU" => "Y",
			"IMG" => "Y",
			"QUOTE" => "Y",
			"CODE" => "Y",
			"FONT" => "N",
			"LIST" => "Y",
			"SMILES" => "Y",
			"NL2BR" => "Y",
			"VIDEO" => "Y",
			"TABLE" => "Y",
			"CUT_ANCHOR" => "N",
			"ALIGN" => "Y",
		);

		$value = $TextParser->convertText($value);

		$value = htmlspecialcharsback($value);
		// Replace BR
		$value = preg_replace("/\<br\s*\/*\>/is".BX_UTF_PCRE_MODIFIER,"\n", $value);
		// Kill &nbsp;
		$value = preg_replace("/&nbsp;/is".BX_UTF_PCRE_MODIFIER,"", $value);
		// Kill tags
		$value = preg_replace("/\<([^>]*?)>/is".BX_UTF_PCRE_MODIFIER,"", $value);
		$value = htmlspecialcharsbx($value);

		RemoveEventHandler("main", "TextParserBeforeAnchorTags", $id);

		return $value;
	}

	/**
	 * @param $text
	 * @param $TextParser
	 * @return bool
	 *
	 * @internal
	 * @access private
	 */
	public static function convertHtmlToBBCodeHack(&$text, &$TextParser)
	{
		$text = preg_replace(array("/\&lt;/is".BX_UTF_PCRE_MODIFIER, "/\&gt;/is".BX_UTF_PCRE_MODIFIER),array('<', '>'),$text);

		$text = preg_replace("/\<br\s*\/*\>/is".BX_UTF_PCRE_MODIFIER,"", $text);
		$text = preg_replace("/\<(\w+)[^>]*\>(.+?)\<\/\\1[^>]*\>/is".BX_UTF_PCRE_MODIFIER,"\\2",$text);
		$text = preg_replace("/\<*\/li\>/is".BX_UTF_PCRE_MODIFIER,"", $text);

		$text = str_replace(array("<", ">"),array("&lt;", "&gt;"),$text);

		$TextParser->allow = array();
		return true;
	}

	/**
	 * Use when you need to make your html a little safer
	 *
	 * @param $value
	 * @return string
	 *
	 */
	public static function convertHtmlToSafeHtml($value)
	{
		$value = (string) $value;
		if($value == '')
		{
			return '';
		}

		static $sanitizer;

		if($sanitizer === null)
		{
			$sanitizer = new \CBXSanitizer();
			$sanitizer->setLevel(\CBXSanitizer::SECURE_LEVEL_LOW);
			$sanitizer->addTags(
				array(
					'blockquote' => array('style', 'class', 'id'),
					'colgroup'   => array('style', 'class', 'id'),
					'col'        => array('style', 'class', 'id', 'width', 'height', 'span', 'style')
				)
			);
			$sanitizer->applyHtmlSpecChars(true);

			// if we don't disable this, than text such as "df 1 < 2 dasfa and 5 > 4 will be partially lost"
			$sanitizer->deleteSanitizedTags(false);
		}

		return $sanitizer->sanitizeHtml($value);
	}

	/**
	 * Formats php structure into JSON format (application/json)
	 *
	 * @param $data
	 * @return mixed
	 */
	public static function toJSON($data)
	{
		$data = static::processObjects($data);

		return Json::encode($data);
	}

	/**
	 * Formats php structure into Javascript object format (application/x-javascript)
	 *
	 * Note: CUtil::PhpToJSObject() translates php nulls to js empty strings, which is not correct
	 *
	 * @param $data
	 * @return string
	 */
	public static function toJSObject($data)
	{
		$data = static::processObjects($data);

		return \CUtil::PhpToJSObject($data, false, false, true);
	}

	/**
	 * Translate php-objects met in $data into suitable js format. For example, an instance of DateTime object
	 * will be translated into user local time string.
	 *
	 * @param $data
	 * @return array|mixed
	 */
	private static function processObjects($data)
	{
		// todo: actually, there could be options.
		// todo: if object implements Iterator and\or ArrayAccess, then better solution would be to iterate over it instead of casting to a string
		// todo: also, there could be "special" objects, that must be translated to js "in a special manner"

		if(is_object($data) && is_subclass_of($data, '\Bitrix\Main\Type\DateTime'))
		{
			// as JSON does not support native js Date type, have to convert to a string representation of local user type
			return \CUtil::JSEscape((string) $data);
		}
		elseif(is_array($data))
		{
			foreach($data as $k => $v)
			{
				$data[$k] = static::processObjects($v);
			}
		}

		return $data;
	}

	public static function translateCalendarSettings(array $settings)
	{
		$h = $settings['HOURS'];

		$hours = str_pad($h['START']['H'], 2, '0', STR_PAD_LEFT).':'.str_pad($h['START']['M'], 2, '0', STR_PAD_LEFT).'-'.str_pad($h['END']['H'], 2, '0', STR_PAD_LEFT).':'.str_pad($h['END']['M'], 2, '0', STR_PAD_LEFT);

		$holidays = array();
		if(is_array($settings['HOLIDAYS']))
		{
			foreach($settings['HOLIDAYS'] as $day)
			{
				$holidays[] = array(
					'month' => intval($day['M']) - 1,
					'day' => $day['D']
				);
			}
		}

		$dayMap = array(
			'MO' => 1,
			'TU' => 2,
			'WE' => 3,
			'TH' => 4,
			'FR' => 5,
			'SA' => 6,
			'SU' => 0,
		);

		$weekEnds = array();
		if(is_array($settings['WEEKEND']))
		{
			foreach($settings['WEEKEND'] as $i)
			{
				$weekEnds[] = $dayMap[$i];
			}
		}

		$weekStart = $dayMap[$settings['WEEK_START']];

		return array(
			'HOURS' => $hours,
			'HOLIDAYS' => $holidays,
			'WEEK_END' => $weekEnds,
			'WEEK_START' => $weekStart
		);
	}
}