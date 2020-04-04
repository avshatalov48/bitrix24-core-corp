<?php

namespace Bitrix\Voximplant\Ivr;

use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);
class Helper
{
	/**
	 * Returns license popup header.
	 * @return string
	 */
	public static function getLicensePopupHeader()
	{
		return Loc::getMessage('IVR_LICENSE_POPUP_HEADER');
	}

	/**
	 * Returns license popup content.
	 * @return string
	 */
	public static function getLicensePopupContent()
	{
		$text = '<p>'.Loc::getMessage('IVR_LICENSE_POPUP_TEXT').'</p> 
			 <ul class="hide-features-list">
			 	<li class="hide-features-list-item">'.GetMessage("IVR_LICENSE_POPUP_ITEM_1").'</li>
			 	<li class="hide-features-list-item">'.GetMessage("IVR_LICENSE_POPUP_ITEM_2").'</li>
			 	<li class="hide-features-list-item">'.GetMessage("IVR_LICENSE_POPUP_ITEM_3").'</li>
			 	<li class="hide-features-list-item">'.GetMessage("IVR_LICENSE_POPUP_ITEM_4").'</li>
			 	<li class="hide-features-list-item">'.GetMessage("IVR_LICENSE_POPUP_ITEM_5").'</li>
			</ul>
				<a href="'.\CVoxImplantMain::GetProLink().'" target="_blank" class="hide-features-more">'.GetMessage('IVR_LICENSE_POPUP_MORE').'</a>
			<strong>'.GetMessage('IVR_LICENSE_POPUP_FOOTER').'</strong>';
		return $text;
	}
}