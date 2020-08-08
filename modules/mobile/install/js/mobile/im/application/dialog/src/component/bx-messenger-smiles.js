/**
 * Bitrix OpenLines widget
 * Smiles component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {Vue} from "ui.vue";
import "ui.vue.components.smiles";

Vue.cloneComponent('bx-messenger-smiles', 'bx-smiles',
	{
		methods:
			{
				hideSmiles(event)
				{
					this.$emit('hideSmiles');
				},
			},
		template: `
		<transition enter-active-class="bx-livechat-consent-window-show" leave-active-class="bx-livechat-form-close">
			<div class="bx-messenger-alert-box bx-livechat-alert-box-zero-padding bx-livechat-form-show" key="vote">
				<div class="bx-livechat-alert-close" @click="hideSmiles"></div>
				<div class="bx-messenger-smiles-box">
					#PARENT_TEMPLATE#
				</div>
			</div>
		</transition>
	`,
	});