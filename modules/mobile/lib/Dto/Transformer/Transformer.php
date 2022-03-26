<?php

namespace Bitrix\Mobile\Dto\Transformer;

abstract class Transformer
{
	public abstract function __invoke(array $fields): array;
}
