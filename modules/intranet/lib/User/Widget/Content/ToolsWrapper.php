<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content;

use Bitrix\Intranet\User\Widget\BaseContent;
use Bitrix\Intranet;

class ToolsWrapper extends BaseContent
{
	public function __construct(
		protected Intranet\User $user,
		protected readonly string $name,
		protected readonly ToolCollection $tools,
	) {
		parent::__construct($user);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getConfiguration(): array
	{
		return [
			'tools' => $this->tools,
		];
	}
}
