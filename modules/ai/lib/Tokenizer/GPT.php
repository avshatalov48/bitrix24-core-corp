<?php

namespace Bitrix\AI\Tokenizer;

use Bitrix\AI\Facade\File;
use Bitrix\Main\Text\Encoding;

class GPT
{
	private string $text;
	private string $error = '';

	public function __construct(string $text)
	{
		$this->text = $text;
	}

	public function getError(): string
	{
		return $this->error;
	}

	public function encode(): array
	{
		$text = $this->text;

		$bpe_tokens = [];
		if (empty($text))
		{
			return $bpe_tokens;
		}
		$raw_chars = $this->file_get_contents('characters.json');
		$byte_encoder = json_decode($raw_chars, true);
		if (empty($byte_encoder))
		{
			$this->error = 'Failed to load characters.json: '.$raw_chars;
			return $bpe_tokens;
		}
		$rencoder = $this->file_get_contents('encoder.json');
		$encoder = json_decode($rencoder, true);
		if (empty($encoder))
		{
			$this->error = 'Failed to load encoder.json: '.$rencoder;
			return $bpe_tokens;
		}

		$bpe_file = $this->file_get_contents('vocab.bpe');
		if (empty($bpe_file))
		{
			$this->error = 'Failed to load vocab.bpe';
			return $bpe_tokens;
		}

		preg_match_all(
			"#'s|'t|'re|'ve|'m|'ll|'d| ?\p{L}+| ?\p{N}+| ?[^\s\p{L}\p{N}]+|\s+(?!\S)|\s+#u",
			$text,
			$matches
		);
		if (!isset($matches[0]) || count($matches[0]) == 0)
		{
			$this->error = 'Failed to match string: '.$text;
			return $bpe_tokens;
		}
		$lines = preg_split('/\r\n|\r|\n/', $bpe_file);
		$bpe_merges = [];
		$bpe_merges_temp = array_slice($lines, 1, count($lines), true);
		foreach ($bpe_merges_temp as $bmt)
		{
			$split_bmt = preg_split('#(\s+)#', $bmt);
			$split_bmt = array_filter($split_bmt, [$this, 'gpt_my_filter']);
			if (count($split_bmt) > 0)
			{
				$bpe_merges[] = $split_bmt;
			}
		}
		$bpe_ranks = $this->gpt_dictZip($bpe_merges, range(0, count($bpe_merges) - 1));

		$cache = [];
		foreach ($matches[0] as $token)
		{
			$new_tokens = [];
			$chars = [];
			$token = $this->gpt_utf8_encode($token);
			if (function_exists('mb_strlen'))
			{
				$len = mb_strlen($token, 'UTF-8');
				for ($i = 0; $i < $len; $i++)
				{
					$chars[] = mb_substr($token, $i, 1, 'UTF-8');
				}
			}
			else
			{
				$chars = str_split($token);
			}
			$result_word = '';
			foreach ($chars as $char)
			{
				if (isset($byte_encoder[$this->gpt_unichr($char)]))
				{
					$result_word .= $byte_encoder[$this->gpt_unichr($char)];
				}
			}
			$new_tokens_bpe = $this->gpt_bpe($result_word, $bpe_ranks, $cache);
			$new_tokens_bpe = explode(' ', $new_tokens_bpe);
			foreach ($new_tokens_bpe as $x)
			{
				if (isset($encoder[$x]))
				{
					if (isset($new_tokens[$x]))
					{
						$new_tokens[rand().'---'.$x] = $encoder[$x];
					}
					else
					{
						$new_tokens[$x] = $encoder[$x];
					}
				}
				else
				{
					if (isset($new_tokens[$x]))
					{
						$new_tokens[rand().'---'.$x] = $x;
					}
					else
					{
						$new_tokens[$x] = $x;
					}
				}
			}
			foreach ($new_tokens as $ninx => $nval)
			{
				if (isset($bpe_tokens[$ninx]))
				{
					$bpe_tokens[rand().'---'.$ninx] = $nval;
				}
				else
				{
					$bpe_tokens[$ninx] = $nval;
				}
			}
		}

		return $bpe_tokens;
	}

	public function decode($tokens): ?string
	{
		$rencoder = $this->file_get_contents('encoder.json');
		$encoder = json_decode($rencoder, true);
		if (empty($encoder))
		{
			$this->error = 'Failed to load encoder.json: '.$rencoder;
			return null;
		}
		$decoder = [];
		foreach ($encoder as $index => $val)
		{
			$decoder[$val] = $index;
		}
		$raw_chars = $this->file_get_contents('characters.json');
		$byte_encoder = json_decode($raw_chars, true);
		if (empty($byte_encoder))
		{
			$this->error = 'Failed to load characters.json: '.$raw_chars;
			return null;
		}
		$byte_decoder = [];
		foreach ($byte_encoder as $index => $val)
		{
			$byte_decoder[$val] = $index;
		}
		$mych_arr = [];
		foreach ($tokens as $myt)
		{
			if (isset($decoder[$myt]))
			{
				$mych_arr[] = $decoder[$myt];
			}
			else
			{
				$this->error = 'Character not found in decoder: '.$myt;
			}
		}
		$text = implode('', $mych_arr);
		$text_arr = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
		$final_arr = [];
		foreach ($text_arr as $txa)
		{
			if (isset($byte_decoder[$txa]))
			{
				$final_arr[] = $byte_decoder[$txa];
			}
			else
			{
				$this->error = 'Character not found in byte_decoder: '.$txa;
			}
		}
		$output = '';
		for ($i = 0, $j = count($final_arr); $i < $j; ++$i)
		{
			$output .= chr($final_arr[$i]);
		}

		return $output;
	}

	public function count(): int
	{
		return count($this->encode());
	}

	private function file_get_contents(string $localFileName): string
	{
		return File::getContents(dirname(__FILE__) . "/data/$localFileName");
	}

	private function gpt_my_filter($var): bool
	{
		return ($var !== null && $var !== false && $var !== '');
	}

	private function gpt_utf8_encode(string $str): string
	{
		$str .= $str;
		$len = mb_strlen($str);
		for ($i = $len >> 1, $j = 0; $i < $len; ++$i, ++$j)
		{
			switch (true)
			{
				case $str[$i] < "\x80":
					$str[$j] = $str[$i];
					break;
				case $str[$i] < "\xC0":
					$str[$j] = "\xC2";
					$str[++$j] = $str[$i];
					break;
				default:
					$str[$j] = "\xC3";
					$str[++$j] = chr(ord($str[$i]) - 64);
					break;
			}
		}

		return substr($str, 0, $j);
	}

	private function gpt_unichr($c): int
	{
		if (ord($c[0]) >= 0 && ord($c[0]) <= 127)
		{
			return ord($c[0]);
		}
		if (ord($c[0]) >= 192 && ord($c[0]) <= 223)
		{
			return (ord($c[0]) - 192) * 64 + (ord($c[1]) - 128);
		}
		if (ord($c[0]) >= 224 && ord($c[0]) <= 239)
		{
			return (ord($c[0]) - 224) * 4096 + (ord($c[1]) - 128) * 64 + (ord($c[2]) - 128);
		}
		if (ord($c[0]) >= 240 && ord($c[0]) <= 247)
		{
			return (ord($c[0]) - 240) * 262144 +
			       (ord($c[1]) - 128) * 4096 +
			       (ord($c[2]) - 128) * 64 +
			       (ord($c[3]) - 128);
		}
		if (ord($c[0]) >= 248 && ord($c[0]) <= 251)
		{
			return (ord($c[0]) - 248) * 16777216 +
			       (ord($c[1]) - 128) * 262144 +
			       (ord($c[2]) - 128) * 4096 +
			       (ord($c[3]) - 128) * 64 +
			       (ord($c[4]) - 128);
		}
		if (ord($c[0]) >= 252 && ord($c[0]) <= 253)
		{
			return (ord($c[0]) - 252) * 1073741824 +
			       (ord($c[1]) - 128) * 16777216 +
			       (ord($c[2]) - 128) * 262144 +
			       (ord($c[3]) - 128) * 4096 +
			       (ord($c[4]) - 128) * 64 +
			       (ord($c[5]) - 128);
		}
		if (ord($c[0]) >= 254 && ord($c[0]) <= 255)
		{
			return 0;
		}

		return 0;
	}

	private function gpt_dictZip($x, $y): array
	{
		$result = [];
		$cnt = 0;
		foreach ($x as $i)
		{
			if (isset($i[1]) && isset($i[0]))
			{
				$result[$i[0].','.$i[1]] = $cnt;
				$cnt++;
			}
		}

		return $result;
	}

	private function gpt_get_pairs($word): array
	{
		$pairs = [];
		$prev_char = $word[0];
		for ($i = 1; $i < count($word); $i++)
		{
			$char = $word[$i];
			$pairs[] = [$prev_char, $char];
			$prev_char = $char;
		}

		return $pairs;
	}

	private function gpt_split($str, $len = 1): array
	{
		$arr = [];
		if (function_exists('mb_strlen'))
		{
			$length = mb_strlen($str, 'UTF-8');
		}
		else
		{
			$length = strlen($str);
		}

		for ($i = 0; $i < $length; $i += $len)
		{
			if (function_exists('mb_substr'))
			{
				$arr[] = mb_substr($str, $i, $len, 'UTF-8');
			}
			else
			{
				$arr[] = substr($str, $i, $len);
			}
		}

		return $arr;

	}

	private function gpt_bpe($token, $bpe_ranks, &$cache): string
	{
		if (array_key_exists($token, $cache))
		{
			return $cache[$token];
		}
		$word = $this->gpt_split($token);
		$init_len = count($word);
		$pairs = $this->gpt_get_pairs($word);
		if (!$pairs)
		{
			return $token;
		}
		while (true)
		{
			$minPairs = [];
			foreach ($pairs as $pair)
			{
				if (array_key_exists($pair[0].','.$pair[1], $bpe_ranks))
				{
					$rank = $bpe_ranks[$pair[0].','.$pair[1]];
					$minPairs[$rank] = $pair;
				}
				else
				{
					$minPairs[10e10] = $pair;
				}
			}
			ksort($minPairs);
			$min_key = array_key_first($minPairs);
			foreach ($minPairs as $mpi => $mp)
			{
				if ($mpi < $min_key)
				{
					$min_key = $mpi;
				}
			}
			$bigram = $minPairs[$min_key];
			if (!array_key_exists($bigram[0].','.$bigram[1], $bpe_ranks))
			{
				break;
			}
			$first = $bigram[0];
			$second = $bigram[1];
			$new_word = [];
			$i = 0;
			while ($i < count($word))
			{
				$j = $this->gpt_indexOf($word, $first, $i);
				if ($j === -1)
				{
					$new_word = array_merge($new_word, array_slice($word, $i, null, true));
					break;
				}
				if ($i > $j)
				{
					$slicer = [];
				}
				elseif ($j == 0)
				{
					$slicer = [];
				}
				else
				{
					$slicer = array_slice($word, $i, $j - $i, true);
				}
				$new_word = array_merge($new_word, $slicer);
				if (count($new_word) > $init_len)
				{
					break;
				}
				$i = $j;
				if ($word[$i] === $first && $i < count($word) - 1 && $word[$i + 1] === $second)
				{
					array_push($new_word, $first.$second);
					$i = $i + 2;
				}
				else
				{
					array_push($new_word, $word[$i]);
					$i = $i + 1;
				}
			}
			if ($word == $new_word)
			{
				break;
			}
			$word = $new_word;
			if (count($word) === 1)
			{
				break;
			}
			else
			{
				$pairs = $this->gpt_get_pairs($word);
			}
		}
		$word = implode(' ', $word);
		$cache[$token] = $word;

		return $word;
	}

	private function gpt_indexOf($arrax, $searchElement, $fromIndex): int
	{
		$index = 0;
		foreach ($arrax as $index => $value)
		{
			if ($index < $fromIndex)
			{
				$index++;
				continue;
			}
			if ($value == $searchElement)
			{
				return $index;
			}
			$index++;
		}

		return -1;
	}
}
