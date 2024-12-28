<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Row\Field\Assembler;

use Bitrix\AI\ShareRole\Service\GridRole\Dto\GridRoleDto;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use CUtil;

class ShareRoleNameFieldAssembler extends FieldAssembler
{
	public function prepareColumn($value): string
	{
		/** @var GridRoleDto $value */

		$name = $value->getTitle();
		$encodedName = htmlspecialcharsbx($name);

		$roleCode = CUtil::JSEscape($value->getCode());

		$button = $this->getButton($value->isFavorite(), $value->isActive() , $roleCode, $name);

		$title = Loc::getMessage('ROLE_LIBRARY_GRID_ACTION_EDIT_ROLE');

		$onTitleClick = HtmlFilter::encode(
			"BX.AI.ShareRole.Library.Controller.handleClickOnRoleName(event, '{$roleCode}')"
		);

		return "
			<div class='ai__role-library-grid_role-title-cell'>
				<div 
					title='{$title}' 
					onclick=\"{$onTitleClick}\" 
					class='ai__role-library-grid_role-title'
					>{$encodedName}</div>
				{$button}
			</div>
		";
	}

	private function getButton(bool $isFavourite, bool $isActive, string $roleCode, string $name): string
	{
		$jsControllerName = "BX.AI.ShareRole.Library.Controller";
		$isFavouriteClassname = $isFavourite ? '--is-active' : '';
		$name = CUtil::JSEscape($name);


		$disabledFavouriteClassname = $isActive ? '' : '--disabled';
		$disabledRoleAddToFavoriteHint  = Loc::getMessage('ROLE_LIBRARY_GRID_ADD_TO_FAVORITE_INACTIVE_ERROR');
		$hintAttributes = '';

		if ($isFavourite)
		{
			$isFavouriteButtonTitle = Loc::getMessage('ROLE_LIBRARY_GRID_ACTION_REMOVE_FROM_FAVOURITE');
			$onFavouriteLabelClick = HtmlFilter::encode(
				"$jsControllerName.handleClickOnRoleIsFavouriteLabel(event, '{$roleCode}','false', '{$name}')"
			);
		}
		else
		{
			$isFavouriteButtonTitle = Loc::getMessage('ROLE_LIBRARY_GRID_ACTION_ADD_TO_FAVOURITE');
			$onFavouriteLabelClick = HtmlFilter::encode(
				"$jsControllerName.handleClickOnRoleIsFavouriteLabel(event, '{$roleCode}','true', '{$name}')"
			);
		}

		if (!$isActive)
		{
			$isFavouriteButtonTitle = '';
			$isFavouriteClassname = '';
			$onFavouriteLabelClick = '';
			$hintAttributes = "data-hint='{$disabledRoleAddToFavoriteHint}'
					data-hint-no-icon
					data-hint-html";
		}

		return "
			<button
					{$hintAttributes}
					type='button'
					title='{$isFavouriteButtonTitle}'
					onclick=\"{$onFavouriteLabelClick}\"
					class='ai__role-library-grid_role-favourite-label {$isFavouriteClassname} {$disabledFavouriteClassname}'
				>
					<div class='ui-icon-set --bookmark-1'></div>
				</button>
		";
	}
}
