<?php

namespace Bitrix\Crm\Service\EditorAdapter\Normalizer;

abstract class Base
{
	abstract public function normalize(mixed $value): mixed;
}
