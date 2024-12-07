<?php

namespace Bitrix\Tasks\Flow\Comment\Task;

interface FlowCommentInterface
{
	public function getPartName(): string;

	public function getMessageKey(): string;

	public function getReplaces(): array;
}