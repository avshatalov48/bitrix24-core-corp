<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\AvatarsStackSteps;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\AvatarsStackSteps\Enum\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\AvatarsStackSteps\Enum\IconColor;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\AvatarsStackSteps\Enum\StackStatus;

final class Step extends \Bitrix\Crm\Service\Timeline\Layout\Base
{
	private string $id;
	private ?string $headerTitle = null;
	private ?array $images = null;
	private ?array $status = null;
	private ?string $footerTitle = null;
	private ?array $footer = null;
	private array $avatars = [];
	private ?string $progressBoxTitle = null;
	private ?array $styles = null;

	public function __construct(string $id)
	{
		$this->id = $id;
	}

	public function setHeaderTitle(string $title): self
	{
		$this->headerTitle = $title;

		return $this;
	}

	public function getHeader(): array
	{
		if (!$this->headerTitle)
		{
			return ['type' => 'stub'];
		}

		return ['type' => 'text', 'data' => ['text' => $this->headerTitle]];
	}

	public function setFooter(array $footer): self
	{
		$this->footer = $footer;

		return $this;
	}

	public function setDurationFooter(int $duration, bool $realtime = false): self
	{
		$this->footer = [
			'type' => 'duration', 'data' => ['duration' => $duration, 'realtime' => $realtime],
		];

		return $this;
	}

	public function setFooterTitle(string $title): self
	{
		$this->footerTitle = $title;

		return $this;
	}

	public function getFooter(): array
	{
		if ($this->footer)
		{
			return $this->footer;
		}

		if (!$this->footerTitle)
		{
			return ['type' => 'stub'];
		}

		return ['type' => 'text', 'data' => ['text' => $this->footerTitle]];
	}

	public function setImages(array $images): self
	{
		$this->images = $images;

		return $this;
	}

	public function setAvatars(array $avatars): self
	{
		$this->avatars = $avatars;

		return $this;
	}

	public function setIcon(Icon $icon, IconColor $color): self
	{
		$this->images = [
			['type' => 'icon', 'data' => ['icon' => $icon->value, 'color' => $color->value]],
		];

		return $this;
	}

	public function getImages(): array
	{
		if ($this->images)
		{
			return $this->images;
		}

		if (!$this->avatars)
		{
			return [['type' => 'user-stub']];
		}

		$images = [];
		foreach ($this->avatars as $avatar)
		{
			$images[] = ['type' => 'user', 'data' => ['src' => $avatar['src'], 'userId' => (int)$avatar['id']]];
		}

		return $images;
	}

	public function setStatus(StackStatus $status, array $customData = null): self
	{
		$this->status = ['type' => $status->value];
		if ($customData)
		{
			$this->status['data'] = $customData;
		}

		return $this;
	}

	public function getStatus(): ?array
	{
		return $this->status;
	}

	public function getStack(): array
	{
		$stack = ['images' => $this->getImages()];

		$status = $this->getStatus();
		if ($status)
		{
			$stack['status'] = $status;
		}

		return $stack;
	}

	public function setProgressBoxTitle(string $title): self
	{
		$this->progressBoxTitle = $title;

		return $this;
	}

	public function getProgressBox(): ?array
	{
		if ($this->progressBoxTitle)
		{
			return ['title' => $this->progressBoxTitle];
		}

		return null;
	}

	public function setStyles(array $styles): self
	{
		$this->styles = $styles;

		return $this;
	}

	public function getStyles(): ?array
	{
		return $this->styles;
	}

	public function toArray(): array
	{
		$data = [
			'id' => $this->id,
			'header' => $this->getHeader(),
			'stack' => $this->getStack(),
			'footer' => $this->getFooter(),
		];

		$progressBox = $this->getProgressBox();
		if ($progressBox)
		{
			$data['progressBox'] = $progressBox;
		}

		$styles = $this->styles;
		if ($styles)
		{
			$data['styles'] = $styles;
		}

		return $data;
	}
}
