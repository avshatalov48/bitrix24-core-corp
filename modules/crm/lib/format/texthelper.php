<?php
namespace Bitrix\Crm\Format;
class TextHelper
{
	public static function convertHtmlToBbCode($html)
	{
		$html = strval($html);
		if($html === '')
		{
			return '';
		}

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventKey = $eventManager->addEventHandlerCompatible("main", "TextParserBeforeTags", array("\Bitrix\Crm\Format\TextHelper", "onTextParserBeforeTags"));

		$textParser = new \CTextParser();
		$textParser->allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "TABLE" => "Y", "CUT_ANCHOR" => "Y", "ALIGN" => "Y");
		$result = $textParser->convertText($html);
		$result = preg_replace("/\<br\s*\/*\>/is".BX_UTF_PCRE_MODIFIER,"\n", $result);

		$eventManager->removeEventHandler("main", "TextParserBeforeTags", $eventKey);
		return $result;
	}
	public static function onTextParserBeforeTags(&$text, &$textParser)
	{
		$text = preg_replace(array("/\&lt;/is".BX_UTF_PCRE_MODIFIER, "/\&gt;/is".BX_UTF_PCRE_MODIFIER),array('<', '>'),$text);
		$text = preg_replace("/\<br\s*\/*\>/is".BX_UTF_PCRE_MODIFIER,"\n", $text);
		$text = preg_replace("/&nbsp;/is".BX_UTF_PCRE_MODIFIER,"", $text);
		$text = preg_replace("/\<(\w+)[^>]*\>(.+?)\<\/\\1[^>]*\>/is".BX_UTF_PCRE_MODIFIER,"\\2",$text);
		$text = preg_replace("/\<*\/li\>/is".BX_UTF_PCRE_MODIFIER,"", $text);
		$text = str_replace(array("<", ">"),array("&lt;", "&gt;"),$text);
		$textParser->allow = array();
		return true;
	}

	public static function sanitizeHtml($html)
	{
		$html = strval($html);
		if($html === '' || strpos($html, '<') === false)
		{
			return $html;
		}

		$sanitizer = new \CBXSanitizer();
		$sanitizer->ApplyDoubleEncode(false);

		//region Method #1 (Disable when CBXSanitizer::DeleteAttributes will be released)
		$tags = array(
			'a'		=> array('href', 'title','name','style','class','shape','coords','alt','target'),
			'b'		=> array('style','class'),
			'br'		=> array('style','class'),
			'big'		=> array('style','class'),
			'blockquote'	=> array('title','style','class'),
			'caption'	=> array('style','class'),
			'code'		=> array('style','class'),
			'del'		=> array('title','style','class'),
			'div'		=> array('title','style','class','align'),
			'dt'		=> array('style','class'),
			'dd'		=> array('style','class'),
			'font'		=> array('color','size','face','style','class'),
			'h1'		=> array('style','class','align'),
			'h2'		=> array('style','class','align'),
			'h3'		=> array('style','class','align'),
			'h4'		=> array('style','class','align'),
			'h5'		=> array('style','class','align'),
			'h6'		=> array('style','class','align'),
			'hr'		=> array('style','class'),
			'i'		=> array('style','class'),
			'img'		=> array('style','class','src','alt','height','width','title'),
			'ins'		=> array('title','style','class'),
			'li'		=> array('style','class'),
			'map'		=> array('shape','coords','href','alt','title','style','class','name'),
			'ol'		=> array('style','class'),
			'p'		=> array('style','class','align'),
			'pre'		=> array('style','class'),
			's'		=> array('style','class'),
			'small'		=> array('style','class'),
			'strong'	=> array('style','class'),
			'span'		=> array('title','style','class','align'),
			'sub'		=> array('style','class'),
			'sup'		=> array('style','class'),
			'table'		=> array('border','width','style','class','cellspacing','cellpadding'),
			'tbody'		=> array('align','valign','style','class'),
			'td'		=> array('width','height','style','class','align','valign','colspan','rowspan'),
			'tfoot'		=> array('align','valign','style','class','align','valign'),
			'th'		=> array('width','height','style','class','colspan','rowspan'),
			'thead'		=> array('align','valign','style','class'),
			'tr'		=> array('align','valign','style','class'),
			'u'		=> array('style','class'),
			'ul'		=> array('style','class')
		);

		$sanitizer->DelAllTags();
		$sanitizer->AddTags($tags);
		//endregion
		//region Method #2 (Enable when CBXSanitizer::DeleteAttributes will be released)
		//$sanitizer->SetLevel(\CBXSanitizer::SECURE_LEVEL_LOW);
		//$sanitizer->DeleteAttributes(array('id'));
		//endregion

		return $sanitizer->SanitizeHtml($html);
	}
}