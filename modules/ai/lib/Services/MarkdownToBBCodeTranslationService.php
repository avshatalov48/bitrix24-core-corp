<?php

declare(strict_types=1);

namespace Bitrix\Ai\Services;

class MarkdownToBBCodeTranslationService
{
	/**
	 * @var array<string, array{0:string,1:string}>
	 */
	private array $codeBlocks = [];

	private string $text = '';

	public function convert(string $text): string
	{
		$this->text = $text;
		$this->extractCodeBlocks();
		$this->processImages();
		$this->processHeadings();
		$this->processHorizontalRule();
		$this->processMarkdownEmphasis();
		$this->processStrikethrough();
		$this->processLinks();
		$this->processLists();
		$this->processBlockQuotes();
		$this->restoreCodeBlocks();

		return $this->text;
	}

	private function extractCodeBlocks(): void
	{
		$this->codeBlocks = [];

		$this->text = preg_replace_callback(
			'/```(?:[a-zA-Z]+)?\s*\n?(.*?)```/s',
			function (array $matches): string {
				$code = trim($matches[1]);
				$hash = md5($code);
				$this->codeBlocks[$hash] = ['code_block', $code];

				return "@@CODEBLOCK{$hash}@@";
			},
			$this->text,
		);

		$this->text = preg_replace_callback(
			'/`([^`\n]+?)`/',
			function (array $matches): string {
				$code = trim($matches[1]);
				$hash = md5($code);
				$this->codeBlocks[$hash] = ['inline_code', $code];

				return "@@INLINE{$hash}@@";
			},
			$this->text,
		);
	}

	private function restoreCodeBlocks(): void
	{
		foreach ($this->codeBlocks as $hash => [$kind, $code])
		{
			$tag = $kind === 'code_block' ? 'CODEBLOCK' : 'INLINE';
			$ph = "@@{$tag}{$hash}@@";
			$bb = "[code]{$code}[/code]";
			$this->text = str_replace($ph, $bb, $this->text);
		}
	}

	private function processLists(): void
	{
		$lines = explode("\n", $this->text);
		$out = [];

		foreach ($lines as $line)
		{
			if (preg_match('/^([ \t]*)[-*] (.+)/', $line, $m))
			{
				$out[] = $m[1] . '- ' . $m[2];
			}
			elseif (preg_match('/^([ \t]*)(\d+)[.)] (.+)/', $line, $m))
			{
				$out[] = $m[1] . $m[2] . '. ' . $m[3];
			}
			else
			{
				$out[] = $line;
			}
		}

		$this->text = implode("\n", $out);
	}

	private function processImages(): void
	{
		$this->text = preg_replace_callback(
			'/!\[(.*?)]\((.*?)\)/',
			function (array $m): string {
				$alt = trim($m[1]);
				$url = trim($m[2]);
				$url = $this->sanitizeUrl($url);

				if ($url === '')
				{
					return ''; // If URL is invalid, don't return anything
				}
				// TODO: At some point we will be able to use tag [img] for images

				return $url . "\n" . $alt;
			},
			$this->text,
		);
	}

	protected function processHeadings(): void
	{
		for ($i = 6; $i >= 1; $i--)
		{
			$hashes = str_repeat('#', $i);
			$size = 25 - ($i - 1) * 2;
			$pattern = '/^' . preg_quote($hashes, '/') . ' (.*)$/m';
			$this->text = preg_replace($pattern, "[size={$size}]$1[/size]", $this->text);
		}
	}

	private function processMarkdownEmphasis(): void
	{
		// Step 1: Handle italic with bold inside (\*italic \*\*bold\*\*\*)
		$this->text = preg_replace_callback('/\*([^*]*)\*\*([^*]*?)\*\*\*/', function ($matches) {
			return '[i]' . $matches[1] . '[b]' . $matches[2] . '[/b][/i]';
		}, $this->text);

		// Step 2: Handle bold with italic inside (\*\*bold \*italic\*\*\*)
		$this->text = preg_replace_callback('/\*\*([^*]*)\*([^*]*?)\*\*\*/', function ($matches) {
			return '[b]' . $matches[1] . '[i]' . $matches[2] . '[/i][/b]';
		}, $this->text);

		// Step 3: Handle bold+italic (\*\*\*text\*\*\*)
		$this->text = preg_replace('/\*\*\*([^*]+?)\*\*\*/', '[b][i]$1[/i][/b]', $this->text);

		// Step 4: Handle remaining bold (\*\*text\*\*)
		$this->text = preg_replace('/\*\*([^*]+?)\*\*/', '[b]$1[/b]', $this->text);

		// Step 5: Handle remaining italic (\*text\*)
		$this->text = preg_replace('/\*([^*]+?)\*/', '[i]$1[/i]', $this->text);

		// Step 6: Handle underlined italic with bold inside (_italic __bold___)
		$this->text = preg_replace_callback('/_([^_]*)__([^_]*?)___/', function ($matches) {
			return '[i]' . $matches[1] . '[b]' . $matches[2] . '[/b][/i]';
		}, $this->text);

		// Step 7: Handle underlined bold with italic inside (__bold _italic___)
		$this->text = preg_replace_callback('/__([^_]*)_([^_]*?)___/', function ($matches) {
			return '[b]' . $matches[1] . '[i]' . $matches[2] . '[/i][/b]';
		}, $this->text);

		// Step 8: Handle underlined bold+italic (___text___)
		$this->text = preg_replace('/___([^_]+?)___/', '[b][i]$1[/i][/b]', $this->text);

		// Step 9: Handle underlined remaining bold (__text__)
		$this->text = preg_replace('/__([^_]+?)__/', '[b]$1[/b]', $this->text);

		// Step 10: Handle underlined remaining italic (_text_)
		$this->text = preg_replace('/_([^_]+?)_/', '[i]$1[/i]', $this->text);
	}

	protected function processStrikethrough(): void
	{
		$this->text = preg_replace('/~~(.+?)~~/', '[s]$1[/s]', $this->text);
	}

	protected function processLinks(): void
	{
		$this->text = preg_replace_callback(
			'/\[(.*?)]\((.*?)\)/',
			function (array $m): string {
				$text = trim($m[1]);
				$url = trim($m[2]);
				$url = $this->sanitizeUrl($url);

				if ($url === '')
				{
					return $text; // If URL is invalid, return just the text
				}

				return "[url={$url}]{$text}[/url]";
			},
			$this->text,
		);
	}

	private function processHorizontalRule(): void
	{
		$this->text = preg_replace('/^\s*---\s*$/m', "───────────", $this->text);
	}

	private function processBlockQuotes(): void
	{
		$lines = explode("\n", $this->text);
		$result = [];
		$inQuote = false;
		$quoteBuffer = [];
		$separator = "------------------------------------------------------";

		foreach ($lines as $line) {
			if (preg_match('/^>\s*(.+)$/', $line, $matches)) {
				if (!$inQuote) {
					$inQuote = true;
					$result[] = $separator;
				}
				$quoteBuffer[] = $matches[1];
			} else {
				if ($inQuote) {
					$result = array_merge($result, $quoteBuffer);
					$result[] = $separator;
					$quoteBuffer = [];
					$inQuote = false;
				}
				$result[] = $line;
			}
		}

		if ($inQuote) {
			$result = array_merge($result, $quoteBuffer);
			$result[] = $separator;
		}

		$this->text = implode("\n", $result);
	}

	private function sanitizeUrl(string $url): string
	{
		// Ensure this is a valid URL
		if (filter_var($url, FILTER_VALIDATE_URL) !== false)
		{
			return $url;
		}

		return '';
	}
}
