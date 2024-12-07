<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Row\Field\Assembler;

use Bitrix\AI\SharePrompt\Service\GridPrompt\Dto\GridPromptDto;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use CUtil;

class SharePromptNameFieldAssembler extends FieldAssembler
{
	public function prepareColumn($value): string
	{
		/** @var GridPromptDto $value */

		$name = $value->getTitle();
		$encodedName = htmlspecialcharsbx($name);
		$promptCode = CUtil::JSEscape($value->getCode());
		$title = Loc::getMessage('PROMPT_LIBRARY_GRID_ACTION_EDIT_PROMPT');

		$onTitleClick = HtmlFilter::encode(
			"BX.AI.SharePrompt.Library.Controller.handleClickOnPromptName(event, '{$promptCode}')"
		);

		$button = '';
		if ($value->isActive())
		{
			$button = $this->getButton($value->isFavorite(), $promptCode, $name);
		}

		return "
			<div class='ai__prompt-library-grid_prompt-title-cell'>
				<div 
					title='{$title}' 
					onclick=\"{$onTitleClick}\" 
					class='ai__prompt-library-grid_prompt-title'
					>{$encodedName}</div>
				{$button}
			</div>
		";
	}


	private function getButton(bool $isFavourite, string $promptCode, string $name): string
	{
		$jsControllerName = "BX.AI.SharePrompt.Library.Controller";
		$isFavouriteClassname = $isFavourite ? '--is-active' : '';
		$name = CUtil::JSEscape($name);

		if ($isFavourite)
		{
			$isFavouriteButtonTitle = Loc::getMessage('PROMPT_LIBRARY_GRID_ACTION_REMOVE_FROM_FAVOURITE');
			$onFavouriteLabelClick = HtmlFilter::encode(
				"$jsControllerName.handleClickOnPromptIsFavouriteLabel(event, '{$promptCode}','false', '{$name}')"
			);
		}
		else
		{
			$isFavouriteButtonTitle = Loc::getMessage('PROMPT_LIBRARY_GRID_ACTION_ADD_TO_FAVOURITE');
			$onFavouriteLabelClick = HtmlFilter::encode(
				"$jsControllerName.handleClickOnPromptIsFavouriteLabel(event, '{$promptCode}','true', '{$name}')"
			);
		}

		return "
			<button
					type='button'
					title='{$isFavouriteButtonTitle}'
					onclick=\"{$onFavouriteLabelClick}\"
					class='ai__prompt-library-grid_prompt-favourite-label {$isFavouriteClassname}'
				>
					<div class='ui-icon-set --bookmark-1'></div>
				</button>
		";
	}
}
