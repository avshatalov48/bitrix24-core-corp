<?php
namespace Bitrix\Crm\Format;

use CCrmContentType;
use CTextParser;

class TextHelper
{
	public const SANITIZE_BB_CODE_WHITE_LIST =  [
		'b', //bold
		'i', //italic
		'u', //underlined
		's', //strike
		'list', //ul and ol,
		'\*', //* - list item
		'user', //mention
		'img',
		'disk file id',
		'url',
	];

	public static function convertHtmlToBbCode($html)
	{
		$html = strval($html);
		if($html === '')
		{
			return '';
		}

		$allow = [
			'ANCHOR' => 'Y',
			'BIU' => 'Y',
			'IMG' => 'Y',
			'QUOTE' => 'Y',
			'CODE' => 'N',
			'FONT' => 'Y',
			'LIST' => 'Y',
			'SMILES' => 'Y',
			'NL2BR' => 'Y',
			'VIDEO' => 'Y',
			'TABLE' => 'Y',
			'ALIGN' => 'Y',
			'P' => 'Y',
			'CUT_ANCHOR' => 'Y',
		];

		$result = (new \CTextParser())->convertHTMLToBB($html, $allow);

		// \CTextParser leaves some html tags, escaping them instead of removing/transforming them
		// remove those remaining tags, and escape the result for backwards compatibility
		return htmlspecialcharsbx(strip_tags(htmlspecialcharsback($result)));
	}

	public static function convertBbCodeToHtml($bb, $useTypography = false): string
	{
		$parser = new \CTextParser();

		$parser->allow = [
			'ANCHOR' => 'Y',
			'BIU' => 'Y',
			'IMG' => 'Y',
			'QUOTE' => 'Y',
			'CODE' => 'Y',
			'FONT' => 'Y',
			'LIST' => 'Y',
			'SMILES' => 'Y',
			'NL2BR' => 'Y',
			'VIDEO' => 'Y',
			'TABLE' => 'Y',
			'ALIGN' => 'Y',
			'P' => 'Y',
			'HTML' => 'Y',
		];

		if ($useTypography && property_exists($parser, 'useTypography'))
		{
			$parser->useTypography = true;
		}

		$result =  $parser->convertText((string)$bb);

		return (string)preg_replace(
			[
				"#<br />[\\t\\s]*(<li[^>]*>)#isu",
				"#<br />[\\t\\s]*(</ol[^>]*>)#isu",
				"#<br />[\\t\\s]*(</ul[^>]*>)#isu",
			],
			"\\1",
			$result
		);
	}

	/**
	 * @deprecated
	 */
	public static function onTextParserBeforeTags(&$text, &$textParser)
	{
		return true;
	}

	public static function sanitizeHtml($html)
	{
		$html = strval($html);
		if($html === '' || mb_strpos($html, '<') === false)
		{
			return $html;
		}

		$sanitizer = new \CBXSanitizer();
		$sanitizer->ApplyDoubleEncode(false);

		//region Method #1 (Disable when CBXSanitizer::DeleteAttributes will be released)
		$tags = [
			'a'		=> ['href', 'title','name','style','class','shape','coords','alt','target'],
			'b'		=> ['style','class'],
			'br'		=> ['style','class'],
			'big'		=> ['style','class'],
			'blockquote'	=> ['title','style','class'],
			'caption'	=> ['style','class'],
			'code'		=> ['style','class'],
			'del'		=> ['title','style','class'],
			'div'		=> ['title','style','class','align'],
			'dt'		=> ['style','class'],
			'dd'		=> ['style','class'],
			'font'		=> ['color','size','face','style','class'],
			'h1'		=> ['style','class','align'],
			'h2'		=> ['style','class','align'],
			'h3'		=> ['style','class','align'],
			'h4'		=> ['style','class','align'],
			'h5'		=> ['style','class','align'],
			'h6'		=> ['style','class','align'],
			'hr'		=> ['style','class'],
			'i'		=> ['style','class'],
			'img'		=> ['style','class','src','alt','height','width','title'],
			'ins'		=> ['title','style','class'],
			'li'		=> ['style','class'],
			'map'		=> ['shape','coords','href','alt','title','style','class','name'],
			'ol'		=> ['style','class'],
			'p'		=> ['style','class','align'],
			'pre'		=> ['style','class'],
			's'		=> ['style','class'],
			'small'		=> ['style','class'],
			'strong'	=> ['style','class'],
			'span'		=> ['title','style','class','align'],
			'sub'		=> ['style','class'],
			'sup'		=> ['style','class'],
			'table'		=> ['border','width','style','class','cellspacing','cellpadding'],
			'tbody'		=> ['align','valign','style','class'],
			'td'		=> ['width','height','style','class','align','valign','colspan','rowspan'],
			'tfoot'		=> ['align','valign','style','class','align','valign'],
			'th'		=> ['width','height','style','class','colspan','rowspan'],
			'thead'		=> ['align','valign','style','class'],
			'tr'		=> ['align','valign','style','class'],
			'u'		=> ['style','class'],
			'ul'		=> ['style','class']
		];

		$sanitizer->DelAllTags();
		$sanitizer->AddTags($tags);
		//endregion
		//region Method #2 (Enable when CBXSanitizer::DeleteAttributes will be released)
		//$sanitizer->SetLevel(\CBXSanitizer::SECURE_LEVEL_LOW);
		//$sanitizer->DeleteAttributes(array('id'));
		//endregion

		return $sanitizer->SanitizeHtml($html);
	}

	/**
	 * Removes bb tags that are not allowed. The main criteria for whitelisting specific tags it's whether they are
	 * supported by mobile app, since it's usually a bottleneck.
	 *
	 * @param $bb - string with bb content
	 * @param array $excludeFromWhitelist - tags that are additionally removed from the input string
	 * @param array $extraWhiteList
	 * @return string
	 */
	final public static function sanitizeBbCode($bb, array $excludeFromWhitelist = [], array $extraWhiteList = []): string
	{
		$bb = (string)$bb;
		if (empty($bb))
		{
			return '';
		}

		$tags = array_diff(self::SANITIZE_BB_CODE_WHITE_LIST, $excludeFromWhitelist);
		$tags = array_merge($tags, $extraWhiteList);

		$tagsPattern = '';
		if (!empty($tags))
		{
			$tagsPattern = '(?!\b' . implode('\b|\b', $tags) . '\b)';
		}

		$pattern = "#\[(\/?){$tagsPattern}\w+\b[^\]]*\]#iu";
		$result = (string)preg_replace($pattern, '', $bb);

		if (in_array('\*',$excludeFromWhitelist, true))
		{
			$listItemPattern = '#\[\*]#u';
			$result = (string)preg_replace($listItemPattern, '', $result);
		}

		return $result;
	}

	final public static function removeParagraphs(string $bbcode): string
	{
		$text = $bbcode;
		$replaced  = 0;
		$doubleLF = false;
		do
		{
			$text = preg_replace_callback(
				"/\\[p](.*?)\\[\\/p](([ \r\t]*)\n?)/isu",
				function($matches) use (&$doubleLF) {
					$result = preg_replace("/^\r?\n|\r?\n$/", '', $matches[1]);
					if ($doubleLF)
					{
						$result = "\n\n" . $result;
					}

					$doubleLF = true;

					return $result;
				},
				$text,
				-1,
				$replaced
			);
		}
		while ($replaced > 0);

		return $text;
	}

	final public static function convertHtmlToText(string $html, bool $saveBreaks = false): string
	{
		$text = preg_replace(['/<p[^>]*>/isu', '/<\\/p[^>]*>/isu'], ['', '<br><br>'], $html);
		if (str_ends_with($text, '<br><br>'))
		{
			$text = mb_substr($text, 0, -8);
		}

		if (!$saveBreaks)
		{
			$text = preg_replace('/(<br[^>]*>)+/isu', "\n", $text);
		}

		return strip_tags($text, $saveBreaks ? '<br>' : null);
	}

	final public static function cleanTextByType(?string $text, int $contentType = CCrmContentType::PlainText): string
	{
		$cleanedText = '';
		if (!$text)
		{
			return '';
		}

		if (!CCrmContentType::IsDefined($contentType))
		{
			return $text;
		}

		if ($contentType === CCrmContentType::Html)
		{
			$cleanedText = strip_tags($text);
		}
		else if ($contentType === CCrmContentType::BBCode)
		{
			$cleanedText = strip_tags((new CTextParser())->convertText($text));
		}
		else if ($contentType === CCrmContentType::PlainText)
		{
			$cleanedText = $text;
		}

		return $cleanedText;
	}
}
