<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field;

use Bitrix\Main\Type\Contract\Arrayable;

interface FieldInterface extends Arrayable
{
	public function getUid(): string;
	public function getType(): string;
	public function getTitle(): string;
	public function isRequired(): bool;
	public function getValue(): mixed;
	public function getJavascript(): string;
	public function getValidators(): array;
	public function getDependencies(): array;
}
