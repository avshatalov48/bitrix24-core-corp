/**
 * Bitrix OpenLines widget
 * Form offline component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import 'ui.icons';
import 'ui.forms';

import {Vue} from "ui.vue";
import {Utils} from "im.lib.utils";
import {FormType} from "../const";

Vue.cloneComponent('bx-livechat-form-offline', 'bx-livechat-form-welcome',
{
	methods:
	{
		formShowed()
		{
			if (!Utils.platform.isMobile())
			{
				this.$refs.emailInput.focus();
			}
		},
		sendForm()
		{
			let name = this.fieldName;
			let email = this.checkEmailField()? this.fieldEmail: '';
			let phone = this.checkPhoneField()? this.fieldPhone: '';

			if (name || email || phone)
			{
				this.$root.$bitrixApplication.sendForm(FormType.offline, {name, email, phone});
			}

			this.hideForm();
		},
		onFieldEnterPress(event)
		{
			if (event.target === this.$refs.emailInput)
			{
				this.showFullForm();
				this.$refs.phoneInput.focus();
			}
			else if (event.target === this.$refs.phoneInput)
			{
				this.$refs.nameInput.focus();
			}
			else
			{
				this.sendForm();
			}

			event.preventDefault();
		},
	},
	watch:
	{
		fieldName()
		{
			clearTimeout(this.fieldNameTimeout);
			this.fieldNameTimeout = setTimeout(this.checkNameField, 300);
		},
		fieldEmail()
		{
			clearTimeout(this.showFormTimeout);
			this.showFormTimeout = setTimeout(this.showFullForm, 1000);

			clearTimeout(this.fieldEmailTimeout);
			this.fieldEmailTimeout = setTimeout(this.checkEmailField, 300);
		},
	},
	template: `
		<transition enter-active-class="bx-livechat-consent-window-show" leave-active-class="bx-livechat-form-close" @after-enter="formShowed">
			<div class="bx-livechat-alert-box bx-livechat-form-show" key="welcome">	
				<div class="bx-livechat-alert-close" @click="hideForm"></div>
				<div class="bx-livechat-alert-form-box">
					<h4 class="bx-livechat-alert-title bx-livechat-alert-title-sm">{{localize.BX_LIVECHAT_OFFLINE_TITLE}}</h4>
					<div class="bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg" ref="email">
					   <div class="ui-ctl-after ui-ctl-icon-mail bx-livechat-form-icon" :title="localize.BX_LIVECHAT_FIELD_MAIL_TOOLTIP"></div>
					   <input type="text" class="ui-ctl-element ui-ctl-textbox" :placeholder="localize.BX_LIVECHAT_FIELD_MAIL" v-model="fieldEmail" ref="emailInput" @blur="checkEmailField" @keydown.enter="onFieldEnterPress">
					</div>
					<div :class="['bx-livechat-form-short', {
						'bx-livechat-form-full': isFullForm,
					}]">
						<div class="bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg" ref="phone">
						   <div class="ui-ctl-after ui-ctl-icon-phone bx-livechat-form-icon" :title="localize.BX_LIVECHAT_FIELD_PHONE_TOOLTIP"></div>
						   <input type="text" class="ui-ctl-element ui-ctl-textbox" :placeholder="localize.BX_LIVECHAT_FIELD_PHONE" v-model="fieldPhone" ref="phoneInput" @blur="checkPhoneField" @keydown.enter="onFieldEnterPress">
						</div>
						<div class="bx-livechat-form-item ui-ctl ui-ctl-w100 ui-ctl-lg" ref="name">
						   <input type="text" class="ui-ctl-element ui-ctl-textbox" :placeholder="localize.BX_LIVECHAT_FIELD_NAME" v-model="fieldName" ref="nameInput" @blur="checkNameField" @keydown.enter="onFieldEnterPress"  @keydown.tab="onFieldEnterPress">
						</div>
						<div class="bx-livechat-btn-box">
							<button class="bx-livechat-btn bx-livechat-btn-success" @click="sendForm">{{localize.BX_LIVECHAT_ABOUT_SEND}}</button>
						</div>
					</div>
				</div>
			</div>	
		</transition>	
	`
});