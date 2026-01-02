<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\Web\Uri;

class ContentBlockFactory
{
	public static function createTextOrLink(string $text, ?Action $action): ContentBlock
	{
		return $action
			? (new Link())
				->setValue($text)
				->setAction($action)
			: (new Text())
				->setValue($text)
		;
	}

	/**
	 * Convert simple html string into set of Text and Link content blocks
	 * Only tags &lt;a&gt; and &lt;b&gt; are supported
	 * Does not support nested tags
	 *
	 * @param string $html
	 * @param string $idPrefix
	 *
	 * @return LineOfTextBlocks
	 */
	public static function createFromHtmlString(
		string $html,
		string $idPrefix = '',
		array $textProperties = [],
		?string $delimiter = null,
	): LineOfTextBlocks
	{
		$lineOfTextBlocks = new LineOfTextBlocks();
		if (isset($delimiter))
		{
			$lineOfTextBlocks->setDelimiter($delimiter);
		}

		preg_match_all('/<([^\s>]+)(.*?)>((.*?)<\/\1>)?|(?<=^|>)(.+?)(?=$|<)/i',$html,$result);
		// assign the result to the variable
		foreach ($result[0] as $index => $text)
		{
			$block = null;
			$isHtmlTag = (substr($text, 0, 1) === '<');
			if ($isHtmlTag)
			{
				$tag = mb_strtolower($result[1][$index]);
				if ($tag === 'a')
				{
					$block = (new Link())
						->setValue(strip_tags($text))
					;

					if (isset($textProperties['link']))
					{
						$linkProp = $textProperties['link'];
						isset($linkProp['color']) && $block->setColor($linkProp['color']);
						isset($linkProp['size']) && $block->setFontSize($linkProp['size']);
						isset($linkProp['decoration']) && $block->setDecoration($linkProp['decoration']);
					}

					preg_match('/href=["\']?([^"\'>]+)["\']?/', $text, $href);
					$url = isset($href[1]) ? new Uri($href[1]) : null;

					if ($url)
					{
						$block->setAction(
							new Action\Redirect($url)
						);
					}
				}
				else
				{
					$block = (new Text())
						->setValue(strip_tags($text))
						->setIsBold($tag === 'b')
					;

					if (isset($textProperties['text']))
					{
						$textProp = $textProperties['text'];
						isset($textProp['color']) && $block->setColor($textProp['color']);
						isset($textProp['size']) && $block->setFontSize($textProp['size']);
					}
				}
			}
			else
			{
				$block = (new Text())->setValue($text);

				if (isset($textProperties['text']))
				{
					$textProp = $textProperties['text'];
					isset($textProp['color']) && $block->setColor($textProp['color']);
					isset($textProp['size']) && $block->setFontSize($textProp['size']);
				}
			}

			$lineOfTextBlocks->addContentBlock($idPrefix . $index, $block);
		}

		return $lineOfTextBlocks;
	}

	public static function createTitle(string $titleText): Text
	{
		return (new Text())
			->setValue(sprintf('%s', $titleText))
			->setColor(Text::COLOR_BASE_70)
			->setFontSize(Text::FONT_SIZE_SM)
		;
	}

	public static function createLineOfTextFromTemplate(
		string $template,
		array $replacements,
		string $idPrefix = ''
	): LineOfTextBlocks
	{
		$result = new LineOfTextBlocks();

		$blocks = self::getBlocksFromTemplate($template, $replacements);
		foreach ($blocks as $index => $block)
		{
			$result->addContentBlock($idPrefix . (++$index), $block);
		}

		return $result;
	}

	/**
	 * @param string $template
	 * @param array $replacements
	 * @return ContentBlock[]
	 */
	public static function getBlocksFromTemplate(string $template, array $replacements): array
	{
		$result = [];

		$parts = preg_split(
			'/(#\w+#)/u',
			$template,
			-1,
			PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE
		);
		foreach ($parts as $singlePart)
		{
			$singlePart = trim($singlePart, ' ');
			if ($singlePart === '')
			{
				continue;
			}

			if (mb_strpos($singlePart, '#') === 0)
			{
				$block = $replacements[$singlePart] ?? null;
			}
			else
			{
				$block = (new Text())->setValue($singlePart);
			}

			if ($block)
			{
				$result[] = $block;
			}
		}

		return $result;
	}
}
