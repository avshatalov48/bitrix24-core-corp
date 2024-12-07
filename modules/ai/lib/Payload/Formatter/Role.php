<?php

namespace Bitrix\AI\Payload\Formatter;

use Bitrix\AI\Prompt;

class Role extends Formatter implements IFormatter
{
	private const MARKER = '{role}';
	private const PATTERN = '/(?P<block>{role}(?P<instructions>.*?){\/role})/is';

	/**
	 * @inheritDoc
	 */
	public function format(array $additionalMarkers = []): string
	{
		if (!str_contains($this->text, self::MARKER))
		{
			return $this->text;
		}

		$this->parseConditions();

		return $this->text;
	}

	/**
	 * Parses {role}, creates runtime Role, removes {role} blocks.
	 * Uses only first appeared block.
	 *
	 * @return void
	 */
	private function parseConditions(): void
	{
		$firstInstruction = null;

		if (preg_match_all(self::PATTERN, $this->text, $matches))
		{
			foreach ($matches['instructions'] as $i => $instruction)
			{
				$replaceFrom = $matches['block'][$i] ?? '';
/*				$replaceTo = '';

				if (is_null($firstInstruction))
				{
					$firstInstruction = $instruction;
				}*/

				$this->text = str_replace($replaceFrom, $instruction, $this->text);
			}
		}

		if (!is_null($firstInstruction))
		{
			$role = Prompt\Role::createRuntime($firstInstruction);
			$this->engine->getPayload()->setRole($role, true);
		}
	}
}
