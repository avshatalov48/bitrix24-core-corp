<?php
declare(strict_types=1);

namespace Bitrix\Disk\Controller\Types\Folder;

use Bitrix\Disk\Folder;

/**
 * Lazy resolver for context type.
 * It is used to resolve context type only when it is needed (for example, in json serialization).
 */
final class LazyContextType implements \JsonSerializable
{

	public function __construct(
		private readonly Folder $folder,
		private readonly ContextTypeResolver $resolver
	)
	{
	}

	public function resolveType(): ContextType
	{
		return $this->resolver->resolveContextType($this->folder);
	}

	public function jsonSerialize(): string
	{
		return $this->resolveType()->value;
	}
}