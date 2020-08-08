<?php
namespace Bitrix\Rpa;

abstract class Field
{
	abstract public function getName(): string;

	abstract public function getNameInCamelCase(): string;

	abstract public function getTitle(): string;

	abstract public function isVisible(): bool;

	abstract public function isEditable(): bool;

	abstract public function isMandatory(): bool;
}