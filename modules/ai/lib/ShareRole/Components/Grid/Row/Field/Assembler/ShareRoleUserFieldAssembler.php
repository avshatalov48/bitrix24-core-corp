<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Row\Field\Assembler;

use Bitrix\Main\Grid\Row\FieldAssembler;

class ShareRoleUserFieldAssembler extends FieldAssembler
{

	protected function prepareColumn($value): ?string
	{
		if (is_array($value) === false)
		{
			return null;
		}

		$userName = htmlspecialcharsbx($value['name']);
		$userPhoto = $value['photo']
			? htmlspecialcharsbx($value['photo'])
			: '/bitrix/js/socialnetwork/entity-selector/src/images/default-user.svg'
			;

		return '
			<div class="ai__role-library-grid_user-field">
				<img class="ai__role-library-grid_author-photo" src="' . $userPhoto . '" alt="' . $userName . '">
				<span>'. $userName . '</span>
			</div>
		';
	}
}