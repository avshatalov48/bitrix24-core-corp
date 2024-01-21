<?php

namespace Bitrix\Tasks\Internals\Task\Search;

interface IndexBuilderInterface
{
	public function build(): string;
}