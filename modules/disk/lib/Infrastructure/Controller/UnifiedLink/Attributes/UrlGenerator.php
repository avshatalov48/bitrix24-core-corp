<?php

declare(strict_types=1);

namespace Bitrix\Disk\Infrastructure\Controller\UnifiedLink\Attributes;

use Attribute;
use Bitrix\Disk\File;

#[Attribute]
class UrlGenerator
{
	/**
	 * @var callable(File): string
	 */
	public $generator;

	/**
	 * @param callable(File): string $generator
	 */
	public function __construct(callable $generator)
	{
		$this->generator = $generator;
	}
}
