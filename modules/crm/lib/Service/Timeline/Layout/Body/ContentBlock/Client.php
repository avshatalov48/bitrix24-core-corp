<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\PhoneNumber\Parser;
use Bitrix\Main\Web\Uri;

final class Client extends ContentBlock
{
	public const BLOCK_WITH_FORMATTED_VALUE = 1;
	public const BLOCK_WITH_FIXED_TITLE = 2;

	private bool $isBlockWithFormattedValue;
	private bool $isBlockWithFixedTitle;

	private array $data;
	private ?string $title = null;

	public function __construct(array $data, int $options = 0)
	{
		// first, setup options
		$this->isBlockWithFormattedValue = $options & self::BLOCK_WITH_FORMATTED_VALUE;
		$this->isBlockWithFixedTitle = $options & self::BLOCK_WITH_FIXED_TITLE;

		$this->data = $data;
	}

	public function getRendererName(): string
	{
		return 'ClientBlock';
	}

	public function getName(): string
	{
		return trim(
			sprintf(
				'%s %s',
				$this->fetchName(),
				$this->fetchFormattedValue()
			)
		);
	}

	public function setTitle(?string $title): self
	{
		$this->title = $title;

		return $this;
	}

	public function build(): ?ContentBlock
	{
		$name = $this->fetchName();
		if (empty($name))
		{
			return null;
		}

		$url = isset($this->data['SHOW_URL']) ? new Uri($this->data['SHOW_URL']) : null;
		$action = $url ? new Redirect($url) : null;
		$formattedValue = $this->fetchFormattedValue();
		if (empty($formattedValue))
		{
			$textOrLink = ContentBlockFactory::createTextOrLink($name, $action);
			$textOrLink->setTitle($name)->setIsBold(isset($url))->setColor(Text::COLOR_BASE_90);
		}
		else
		{
			$clientNameBlock = ContentBlockFactory::createTextOrLink($name, $action);
			$clientNameBlock->setTitle($name)->setIsBold(isset($url))->setColor(Text::COLOR_BASE_90);

			$clientContactBlock = ContentBlockFactory::createTextOrLink($formattedValue, $action);
			$clientNameBlock->setTitle($name)->setColor(Text::COLOR_BASE_90);

			$textOrLink = (new LineOfTextBlocks())
				->addContentBlock('clientTitle', $clientNameBlock)
				->addContentBlock('clientContact', $clientContactBlock)
			;
		}

		return (new ContentBlockWithTitle())
			->setTitle($this->title)
			->setContentBlock($textOrLink)
			->setFixedWidth($this->isBlockWithFixedTitle)
			->setInline()
		;
	}

	protected function getProperties(): array
	{
		return [];
	}

	private function fetchName(): string
	{
		return $this->data['TITLE'] ?? '';
	}

	private function fetchFormattedValue(): string
	{
		if ($this->isBlockWithFormattedValue)
		{
			$source = empty($this->data['SOURCE'])
				? ''
				: Parser::getInstance()->parse($this->data['SOURCE'])->format();

			return empty($this->data['FORMATTED_VALUE'])
				? $source
				: $this->data['FORMATTED_VALUE'];
		}

		return '';
	}
}
