/**
 * Bitrix OpenLines widget
 * Body loading component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {BitrixVue} from "ui.vue";

BitrixVue.component('bx-livechat-body-loading',
{
	template: `
		<div class="bx-livechat-loading-window">
			<svg class="bx-livechat-loading-circular" viewBox="25 25 50 50">
				<circle class="bx-livechat-loading-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
				<circle class="bx-livechat-loading-inner-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
			</svg>
			<h3 class="bx-livechat-help-title bx-livechat-help-title-md bx-livechat-loading-msg">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_LOADING')}}</h3>
		</div>
	`
});