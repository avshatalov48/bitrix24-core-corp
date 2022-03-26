<?php

namespace Bitrix\Mobile\Dto\Transformer;

use Bitrix\Main\Engine\Response\Converter;

final class ToLower extends Transformer
{
	public function __invoke(array $fields): array
	{
		$converter = new Converter(
			Converter::KEYS
			| Converter::RECURSIVE
			| Converter::TO_LOWER
		);
		$result = $converter->process($fields);

		return is_array($result) ? $result : $fields;
	}
}
