<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Row\Field\Assembler;

use Bitrix\AI\SharePrompt\Service\GridPrompt\Dto\ShareDto;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Text\Encoding;

class SharePromptShareFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		$shares = array_values($value['shares']);
		$sharePromptCode = $value['promptCode'];
		$totalCount = $value['totalCount'] ?? 0;

		$firstValues = array_slice($shares, 0, 5);

		$result = '<div class="ai__prompt-library-grid_shares-cell">';

		foreach ($firstValues as $value)
		{
			$result .= $this->getAvatar($value);
		}

		if ($totalCount > count($shares))
		{
			$otherSharesCount = $totalCount - 5;
			$onClick = "BX.AI.SharePrompt.Library.Controller.handleClickOnSharesCell('{$sharePromptCode}', event)";
			$result .= '
				<div class="ai__prompt-library-grid_shares-etc-items-count" title="Показать все" onclick="'
					. $onClick
					. '">+'
					. $otherSharesCount
					. '</div>';
		}

		$result .= '</div>';

		return $result;
	}

	/**
	 * @param ShareDto $shareDto
	 *
	 * @return string
	 */
	protected function getAvatar(ShareDto $shareDto): string
	{
		if ($shareDto->getImg())
		{
			return $this->getAvatarWithPhoto($shareDto);
		}

		return $this->getAvatarWithInitials($shareDto);
	}

	protected function getAvatarWithPhoto(ShareDto $shareDto): string
	{
		$photo = $shareDto->getImg();
		$name = $shareDto->getName();

		return "
			<div data-hint='{$name}' data-hint-no-icon class='ai__prompt-library-grid_shares-item'>
				<img src='{$photo}' alt='{$name}'>
			</div>
		";
	}

	protected function getAvatarWithInitials(ShareDto $shareDto): string
	{
		$fullName = $shareDto->getName();
		$initials = $this->getInitials($fullName);

		return
			"<div data-hint='{$fullName}' data-hint-no-icon class='ai__prompt-library-grid_shares-item'>
				<span class='ai__prompt-library-grid_share-initials'>
					{$initials}
				</span>
			</div>"
		;
	}

	protected function getInitials(string $string): string
	{
		$string = Encoding::convertEncodingToCurrent($string);

		$words = mb_split('\s+', $string);

		if (count($words) < 2)
		{
			return mb_strtoupper(mb_substr($words[0], 0, 1));
		}

		$firstLetter = mb_strtoupper(mb_substr($words[0], 0, 1));
		$secondLetter = mb_strtoupper(mb_substr($words[1], 0, 1));

		return $firstLetter . $secondLetter;
	}
}
