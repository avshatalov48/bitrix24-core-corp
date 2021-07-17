/**
 * Bitrix Messenger
 * Form Vue component
 *
 * @package bitrix
 * @subpackage im
 * @copyright 2001-2019 Bitrix
 */

import {Vue} from "ui.vue";
import {Vuex} from "ui.vue.vuex";
import 'im.view.message';

BX.Vue.cloneComponent('bx-test-form', 'bx-im-view-message',
	{
		data()
		{
			return {
				formValue: '',
			}
		},
		created()
		{
		},
		computed:
		{
			wasFilled()
			{
				return !!this.message.params.CRM_FORM_VALUE;
			},
			...Vuex.mapState({
				widget: state => state.widget,
			}),
		},
		methods:
		{
			onFillForm()
			{
				this.$Bitrix.RestClient.get().callMethod('imopenlines.widget.form.fill', {
					'CRM_FORM_VALUE' : this.formValue,
					'MESSAGE_ID' : this.message.id,
				}).then(response => {
					console.log(response);
				}).catch(error => {
					console.log(error);
				});
			}
		},
		template: `
		<div class="bx-im-message bx-im-message-without-menu bx-im-message-without-avatar">
			<div v-if="!wasFilled" class="bx-im-message-content">
				<div style="margin-bottom: 10px;">Form component with id {{message.params.CRM_FORM_ID}}</div>
				<div style="margin-bottom: 10px; display: flex;">
					<input type="text" v-model="formValue" style="margin-right: 15px;" />
					<button @click="onFillForm" class="bx-im-textarea-send-button bx-im-textarea-send-button-bright-arrow" style="background-color: rgb(23, 163, 234);"></button>	
				</div>
				<!--#PARENT_TEMPLATE#-->
			</div>
			<div v-else class="bx-im-message-content">
				Form was already filled with the value - "{{message.params.CRM_FORM_VALUE[0]}}"!
			</div>
		</div>
	`
	});