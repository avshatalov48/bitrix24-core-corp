<?php


namespace Bitrix\Crm\Merger\ConflictResolver;


class HtmlField extends TextField
{
	protected function getNewLine(): string
	{
		return '<br>';
	}
}