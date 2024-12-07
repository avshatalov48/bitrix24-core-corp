<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Activity;

use Bitrix\Crm\Activity\ToDo\ColorSettings\ColorSettingsProvider;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\EditableDate;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ItemSelector;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\PingSelector;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\ArgumentTypeException;

class DeadlineAndPingSelector extends ContentBlock
{
	public const BACKGROUND_ORANGE = 'orange';
	public const BACKGROUND_GREY = 'gray';

	private ?EditableDate $deadlineBlock = null;
	private Text|ItemSelector|PingSelector|null $pingSelectorBlock = null;
	private ?string $deadlineBlockTitle = null;
	private ?string $backgroundToken = null;
	private ?string $backgroundColor = null;
	private bool $isScheduled = true;

	public function getRendererName(): string
	{
		return 'DeadlineAndPingSelector';
	}

	public function setDeadlineBlock(EditableDate $deadlineBlock): self
	{
		$this->deadlineBlock = $deadlineBlock;

		return $this;
	}

	public function setDeadlineBlockTitle(string $title): self
	{
		$this->deadlineBlockTitle = $title;

		return $this;
	}

	public function setPingSelectorBlock(Text | ItemSelector | PingSelector $pingSelectorBlock): self
	{
		if (!$this->isAvailableContentBlock($pingSelectorBlock))
		{
			throw new ArgumentTypeException(
				'pingSelectorBlock',
				Text::class . '|' . ItemSelector::class
			);
		}

		$this->pingSelectorBlock = $pingSelectorBlock;

		return $this;
	}

	private function isAvailableContentBlock(ContentBlock $contentBlock): bool
	{
		return
			($contentBlock instanceof Text)
			|| ($contentBlock instanceof ItemSelector)
			|| ($contentBlock instanceof PingSelector)
		;
	}

	public function setBackgroundToken(?string $token): DeadlineAndPingSelector
	{
		$this->backgroundToken = $token;

		return $this;
	}

	public function setBackgroundColorById(?string $backgroundColorId): DeadlineAndPingSelector
	{
		$colorSettingsProvider = new ColorSettingsProvider();
		if ($colorSettingsProvider->isAvailableColorId($backgroundColorId))
		{
			$this->backgroundColor = $colorSettingsProvider->getByColorId($backgroundColorId)['logoBackground'];
		}

		return $this;
	}

	public function setIsScheduled(bool $isScheduled = true): DeadlineAndPingSelector
	{
		$this->isScheduled = $isScheduled;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'deadlineBlock' => $this->deadlineBlock,
			'deadlineBlockTitle' => $this->deadlineBlockTitle,
			'pingSelectorBlock' => $this->pingSelectorBlock,
			'backgroundToken' => $this->backgroundToken,
			'backgroundColor' => $this->backgroundColor,
			'isScheduled' => $this->isScheduled,
		];
	}
}

