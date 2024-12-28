<?php

declare(strict_types=1);

namespace Bitrix\AI\Payload\Tokens;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use SplObjectStorage;

final class TokenProcessor
{
	/** @var SplObjectStorage<HiddenToken, string> */
	private SplObjectStorage $replacements;
	/** @var HiddenToken[]  */
	private array $tokens;

	/** @var array<string, HiddenToken> */
	private array $replacementToTokenMap = [];

	/** @var array<string> */
	private array $replacementSet = [];

	/**
	 * @param HiddenToken[] $tokens
	 */
	public function __construct(HiddenToken ...$tokens)
	{
		$this->replacements = new SplObjectStorage();
		$this->attachTokens($tokens);
	}

	/**
	 * Attaches tokens to the processor.
	 *
	 * @param HiddenToken[] $tokens
	 */
	private function attachTokens(array $tokens): void
	{
		foreach ($tokens as $token)
		{
			$this->replacementSet[$token->getValue()] = true;
			$token->attachToProcessor($this);
		}

		$this->tokens = $tokens;
	}

	/**
	 * Retrieves the replacement for a given token, generating it if it doesn't exist.
	 *
	 * @param HiddenToken $token
	 * @return string
	 * @throws \Exception If unable to generate a unique replacement after multiple attempts.
	 */
	public function getReplacement(HiddenToken $token): string
	{
		if (!$this->replacements->contains($token))
		{
			$replacement = $this->generateReplacement($token);

			$this->replacements->attach($token, $replacement);
			$this->replacementSet[$replacement] = true;
			$this->replacementToTokenMap[$replacement] = $token;
		}

		return $this->replacements[$token];
	}

	/**
	 * Generates a unique replacement based on the token type.
	 *
	 * @param HiddenToken $token
	 * @return string
	 * @throws ArgumentException If unable to generate a unique replacement after multiple attempts.
	 * @throws SystemException
	 */
	private function generateReplacement(HiddenToken $token): string
	{
		$maxAttempts = 5;
		$attempt = 0;

		do
		{
			$replacement = match ($token->getType())
			{
				TokenType::INTEGER_ID => Random::getInt(min: 10**4, max: 10**8),
				TokenType::RANDOM_STRING => Random::getString(6),
				default => throw new ArgumentException('Unsupported token type'),
			};

			if ($token->getPrefix())
			{
				$replacement = $token->getPrefix() . $replacement;
			}

			$isUnique = $this->isUniqueReplacement($replacement);
			$attempt++;
		}
		while (!$isUnique && $attempt < $maxAttempts);

		if (!$isUnique)
		{
			throw new SystemException("Unable to generate a unique replacement after {$maxAttempts} attempts.");
		}

		return $replacement;
	}

	private function isUniqueReplacement(string $potentialReplace): bool
	{
		if (isset($this->replacementSet[$potentialReplace]))
		{
			return false;
		}

		foreach ($this->replacements as $replacement => $value)
		{
			if (!\is_string($replacement))
			{
				continue;
			}

			if (str_contains($replacement, $potentialReplace) || str_contains($potentialReplace, $replacement))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Hides all tokens in the provided text by replacing them with their unique replacements.
	 *
	 * @param string $text
	 * @return string
	 */
	public function hideTokens(string $text): string
	{
		return strtr($text, $this->getReplacements());
	}

	/**
	 * Retrieves all replacements mapped to their original tokens.
	 *
	 * @return array<string, string> Associative array of original token values to replacements.
	 */
	public function getReplacements(): array
	{
		$replacements = [];
		foreach ($this->tokens as $token)
		{
			$replacements[$token->getValue()] = $token->getReplacement();
		}

		return $replacements;
	}
}