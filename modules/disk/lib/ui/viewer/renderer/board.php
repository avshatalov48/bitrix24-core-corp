<?php

declare(strict_types=1);

namespace Bitrix\Disk\UI\Viewer\Renderer;

use Bitrix\Main\UI\Viewer\Renderer\Renderer;

class Board extends Renderer
{
	const JS_TYPE_BOARD = 'board';

	public static function getJsType(): string
	{
		return self::JS_TYPE_BOARD;
	}

	public static function getAllowedContentTypes(): array
	{
		return [
			'application/flp',
			'application/flip',
			'application/flip-board',
			'application/board',
		];
	}

	public function render(): ?string
	{
		return null;
	}

	public function getData(): array
	{
		return [
			'src' => $this->sourceUri,
		];
	}
}