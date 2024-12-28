<?php

namespace Bitrix\ImOpenLines\V2\Transfer;

use Bitrix\Main\Loader;

Loader::requireModule('im');

interface Transferable
{
	public static function getInstance(mixed $id): ?self;
	public function getId(): ?int;
	public function getTransferId(): int|string;
}