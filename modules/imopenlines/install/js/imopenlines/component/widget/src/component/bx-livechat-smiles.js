/**
 * Bitrix OpenLines widget
 * Smiles component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {Vue} from "ui.vue";

Vue.cloneComponent('bx-livechat-smiles', 'bx-smiles',
{
	methods:
	{
		hideForm(event)
		{
			this.$parent.hideForm();
		},
	},
	template: `
		<transition enter-active-class="bx-livechat-consent-window-show" leave-active-class="bx-livechat-form-close">
			<div class="bx-livechat-alert-box bx-livechat-alert-box-zero-padding bx-livechat-form-show" key="vote">
				<div class="bx-livechat-alert-close" @click="hideForm"></div>
				<div class="bx-livechat-alert-smiles-box">
					#PARENT_TEMPLATE#
				</div>
			</div>
		</transition>
	`
});