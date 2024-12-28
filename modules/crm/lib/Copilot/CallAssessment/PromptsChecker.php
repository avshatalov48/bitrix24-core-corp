<?php

namespace Bitrix\Crm\Copilot\CallAssessment;

use CTextParser;

final class PromptsChecker
{
	public static function isChanged(string $prevPrompt, string $newPrompt): bool
	{
		$cleaner = static function(string $input) {
			$parser = new CTextParser();

			$input = trim(preg_replace('/\s+/', ' ', $input));
			$input = $parser->convertText($input);

			return $parser::clearAllTags($input);
		};

		return (strcasecmp($cleaner($prevPrompt), $cleaner($newPrompt)) !== 0);
	}
}
