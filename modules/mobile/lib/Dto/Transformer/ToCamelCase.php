<?php

namespace Bitrix\Mobile\Dto\Transformer;

use Bitrix\Main\Engine\Response\Converter;

final class ToCamelCase extends Transformer
{
	public function __invoke(array $fields): array
	{
		$converter = new Converter(
			Converter::KEYS
			| Converter::RECURSIVE
			| Converter::TO_CAMEL
			| Converter::LC_FIRST
		);
		$result = $converter->process($fields);

		return is_array($result) ? $result : $fields;
	}
}
