<?php
namespace Bitrix\ImConnector\Emoji;

/**
 * Client for Emojione.
 */
class Client implements ClientInterface
{
	// convert ascii smileys?
	public $ascii = true;
	// use the unicode char as the alt attribute (makes copy and pasting the resulting text better)
	public $unicodeAlt = false;
	public $cacheBustParam = '?v=2.2.5';
	public $imagePathSVG = '/bitrix/images/emoji/';
	public $ignoredRegexp = '<object[^>]*>.*?<\/object>|<span[^>]*>.*?<\/span>|<(?:object|embed|svg|img|div|span|p|a)[^>]*>';
	public $unicodeRegexp = '([*#0-9](?>\\xEF\\xB8\\x8F)?\\xE2\\x83\\xA3|\\xC2[\\xA9\\xAE]|\\xE2..(\\xF0\\x9F\\x8F[\\xBB-\\xBF])?(?>\\xEF\\xB8\\x8F)?|\\xE3(?>\\x80[\\xB0\\xBD]|\\x8A[\\x97\\x99])(?>\\xEF\\xB8\\x8F)?|\\xF0\\x9F(?>[\\x80-\\x86].(?>\\xEF\\xB8\\x8F)?|\\x87.\\xF0\\x9F\\x87.|..((\\xE2\\x80\\x8D\\xF0\\x9F\\x97\\xA8)|(\\xF0\\x9F\\x8F[\\xBB-\\xBF])|(\\xE2\\x80\\x8D\\xF0\\x9F\\x91[\\xA6-\\xA9]){2,3}|(\\xE2\\x80\\x8D\\xE2\\x9D\\xA4\\xEF\\xB8\\x8F\\xE2\\x80\\x8D\\xF0\\x9F..(\\xE2\\x80\\x8D\\xF0\\x9F\\x91[\\xA6-\\xA9])?))?))';

	public $shortcodeRegexp = ':([-+\\w]+):';

	protected $ruleset = null;

	public function __construct(RulesetInterface $ruleset = null)
	{
		if ( ! is_null($ruleset) )
		{
			$this->ruleset = $ruleset;
		}
	}

	// ##########################################
	// ######## core methods
	// ##########################################

	/**
	 * First pass changes unicode characters into emoji markup.
	 * Second pass changes any shortnames into emoji markup.
	 *
	 * @param   string  $string The input string.
	 * @return  string  String with appropriate html for rendering emoji.
	 */
	public function toImage($string)
	{
		$string = $this->unicodeToImage($string);
		$string = $this->shortnameToImage($string);
		return $string;
	}

	/**
	 * This will output image markup (for png or svg) from shortname input.
	 *
	 * @param   string  $string The input string.
	 * @return  string  String with appropriate html for rendering emoji.
	 */
	public function shortnameToImage($string)
	{
		$string = preg_replace_callback('/' . $this->ignoredRegexp . '|(' . $this->shortcodeRegexp . ')/Si', array($this, 'shortnameToImageCallback'), $string);

		if ($this->ascii)
		{
			$ruleset = $this->getRuleset();
			$asciiRegexp = $ruleset->getAsciiRegexp();

			$string = preg_replace_callback('/' . $this->ignoredRegexp . '|((\\s|^)'.$asciiRegexp . '(?=\\s|$|[!,.?]))/S', array($this, 'asciiToImageCallback'), $string);
		}

		return $string;
	}

	/**
	 * This will output image markup (for png or svg) from unicode input.
	 *
	 * @param   string  $string The input string.
	 * @return  string  String with appropriate html for rendering emoji.
	 */
	public function unicodeToImage($string)
	{
		return preg_replace_callback('/' . $this->ignoredRegexp . '|' . $this->unicodeRegexp . '/S', array($this, 'unicodeToImageCallback'), $string);
	}

	// ##########################################
	// ######## preg_replace callbacks
	// ##########################################
	/**
	 * @param   array   $m  Results of preg_replace_callback().
	 * @return  string  Image HTML replacement result.
	 */
	public function shortnameToImageCallback($m)
	{
		if ((!is_array($m)) || (!isset($m[1])) || (empty($m[1])))
		{
			return $m[0];
		}
		else
			{
			$ruleset = $this->getRuleset();
			$shortcodeReplace = $ruleset->getShortcodeReplace();

			$shortname = $m[1];

			if (!isset($shortcodeReplace[$shortname]))
			{
				return $m[0];
			}


			$unicode = $shortcodeReplace[$shortname];
			$filename = $unicode;

			if ($this->unicodeAlt)
			{
				$alt = $this->convert($unicode);
			}
			else
			{
				$alt = $shortname;
			}

			return '[ICON=' . $this->imagePathSVG . $filename . '.svg' . $this->cacheBustParam . ' title=' . $alt . ']';
		}
	}

	/**
	 * @param   array   $m  Results of preg_replace_callback().
	 * @return  string  Unicode replacement result.
	 */
	public function asciiToUnicodeCallback($m)
	{
		if ((!is_array($m)) || (!isset($m[3])) || (empty($m[3])))
		{
			return $m[0];
		}
		else
		{
			$ruleset = $this->getRuleset();
			$asciiReplace = $ruleset->getAsciiReplace();

			$shortname = $m[3];
			$unicode = $asciiReplace[$shortname];
			return $m[2] . $this->convert($unicode);
		}
	}

	/**
	 * @param   array   $m  Results of preg_replace_callback().
	 * @return  string  Image HTML replacement result.
	 */
	public function asciiToImageCallback($m)
	{
		if ((!is_array($m)) || (!isset($m[3])) || (empty($m[3])))
		{
			return $m[0];
		}
		else
		{
			$ruleset = $this->getRuleset();
			$asciiReplace = $ruleset->getAsciiReplace();

			$shortname = html_entity_decode($m[3]);
			$unicode = $asciiReplace[$shortname];

			// unicode char or shortname for the alt tag? (unicode is better for copying and pasting the resulting text)
			if ($this->unicodeAlt)
			{
				$alt = $this->convert($unicode);
			}
			else
			{
				$alt = htmlspecialcharsbx($shortname);
			}

			return $m[2] . '[ICON=' . $this->imagePathSVG . $unicode . '.svg'.$this->cacheBustParam . ' title=' . $alt . ']';
		}
	}

	/**
	 * @param   array   $m  Results of preg_replace_callback().
	 * @return  string  Image HTML replacement result.
	 */
	public function unicodeToImageCallback($m)
	{
		if ((!is_array($m)) || (!isset($m[1])) || (empty($m[1])))
		{
			return $m[0];
		}
		else
		{
			$ruleset = $this->getRuleset();
			$shortcodeReplace = $ruleset->getShortcodeReplace();
			$unicodeReplace = $ruleset->getUnicodeReplace();

			$unicode = $m[1];

			if (!in_array($unicode, $unicodeReplace))
			{
				$unicode .= "\xEF\xB8\x8F";

				if (!in_array($unicode, $unicodeReplace))
				{
					$unicode = substr($m[1], 0, 4);

					if (!in_array($unicode, $unicodeReplace))
					{
						if ("\xE2\x83\xA3" === substr($m[1], 1, 3))
						{
							$unicode = substr($m[1], 0, 1) . "\xEF\xB8\x8F\xE2\x83\xA3";

							if (!in_array($unicode, $unicodeReplace))
							{
								return $m[0];
							}
						}
						else
						{
							return $m[0];
						}
					}
				}
			}

			$shortname = array_search($unicode, $unicodeReplace);
			$filename = $shortcodeReplace[$shortname];

			if ($this->unicodeAlt)
			{
				$alt = $unicode;
			}
			else
			{
				$alt = $shortname;
			}


			return '[ICON=' . $this->imagePathSVG . $filename . '.svg' . $this->cacheBustParam . ' title=' . $alt . ']';
		}
	}

	// ##########################################
	// ######## helper methods
	// ##########################################

	/**
	 * Converts from unicode to hexadecimal NCR.
	 *
	 * @param   string  $unicode unicode character/s.
	 * @return  string  hexadecimal NCR.
	 * */
	public function convert($unicode)
	{
		if (stristr($unicode,'-'))
		{
			$pairs = explode('-',$unicode);
			return '&#x' . implode(';&#x',$pairs).';';
		}
		else
		{
			return '&#x' . $unicode.';';
		}
	}

	/**
	 * Get the Ruleset
	 *
	 * @return RulesetInterface The Ruleset
	 */
	public function getRuleset()
	{
		if ( $this->ruleset === null )
		{
			$this->ruleset = new Ruleset;
		}

		return $this->ruleset;
	}
}
