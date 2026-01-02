<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\FooterNote;

use Bitrix\Main\Localization\Loc;
use CTimeZone;

final class DateUpdatedFooterNote extends AbstractFooterNote
{
	private FooterNote $footerNote;

	public function __construct(
		private readonly string $datetime,
	)
	{
		$this->footerNote = new FooterNote($this->format());
	}

	public function format(): string
	{
		return Loc::getMessage(
			'CRM_ITEM_MINI_CARD_FOOTER_NOTE_DATE_UPDATED_CONTENT',
			[
				'#DATE_UPDATED#' => FormatDate('x', MakeTimeStamp($this->datetime), (time() + CTimeZone::GetOffset())),
			],
		);
	}

	public function getName(): string
	{
		return $this->footerNote->getName();
	}

	public function getProps(): array
	{
		return $this->footerNote->getProps();
	}
}
