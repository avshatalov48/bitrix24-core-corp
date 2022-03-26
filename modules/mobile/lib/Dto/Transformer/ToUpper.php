<?php

namespace Bitrix\Mobile\Dto\Transformer;

use Bitrix\Main\Engine\Response\Converter;

final class ToUpper extends Transformer
{
	public function __invoke(array $fields): array
	{
		$converter = new Converter(
			Converter::KEYS
			| Converter::RECURSIVE
			| Converter::TO_UPPER
		);
		$result = $converter->process($fields);

		return is_array($result) ? $result : $fields;
	}
}
