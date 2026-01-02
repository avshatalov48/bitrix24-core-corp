<?php

namespace Bitrix\Crm\RepeatSale\DataCollector\Activity;

use Bitrix\Crm\Format\TextHelper;
use CCrmContentType;
use CTextParser;

class TextNormalizer
{
	public function __construct(private readonly TextLengthConfig $lengthConfig) {}

	public function normalize(string $text, ActivityType $type, int $contentType = CCrmContentType::BBCode): ?string
	{
		$text = trim($text);
		if (empty($text))
		{
			return null;
		}

		$config = $this->lengthConfig->getConfigForType($type);
		$text = $this->cleanText($text, $contentType);

		return $this->applyLengthLimits($text, $config);
	}

	private function cleanText(string $text, int $contentType): string
	{
		$text = TextHelper::cleanTextByType($text, $contentType);
		$text = preg_replace('/\s+/', ' ', $text);

		return CTextParser::cleanTag($text);
	}

	private function applyLengthLimits(string $text, array $config): ?string
	{
		$length = mb_strlen($text, 'UTF-8');
		$minLength = $config['min_length'] ?? null;
		$maxLength = $config['max_length'] ?? null;
		if (!$minLength || !$maxLength)
		{
			throw new \InvalidArgumentException('Minimal or maximum length is not specified');
		}

		if ($length < $minLength)
		{
			return null;
		}

		if ($length > $maxLength)
		{
			$text = mb_substr($text, 0, $maxLength, 'UTF-8');
		}

		return $text;
	}
}
