/**
 * Bitrix OpenLines widget
 * Form history component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import 'ui.icons';
import 'ui.forms';

import {Vue} from "ui.vue";
import {Vuex} from "ui.vue.vuex";
import {Utils} from "im.lib.utils";
import {FormType} from "../const";

Vue.component('bx-livechat-form-history',
{
	data()
	{
		return {
			fieldEmail: '',
		}
	},
	watch:
	{
		fieldEmail(value)
		{
			clearTimeout(this.fieldEmailTimeout);
			this.fieldEmailTimeout = setTimeout(this.checkEmailField, 300);
		},
	},
	computed:
	{
		localize()
		{
			return Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
		},
		...Vuex.mapState({
			widget: state => state.widget,
		})
	},
	created()
	{
		this.fieldEmail = '' + this.widget.user.email;
	},
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
			let email = this.checkEmailField()? this.fieldEmail: '';
			if (email)
			{
				this.$root.$bitrixApplication.sendForm(FormType.history, {email});
			}

			this.hideForm();
		},
		hideForm(event)
		{
			clearTimeout(this.fieldEmailTimeout);
			this.$parent.hideForm();
		},
		onFieldEnterPress(event)
		{
			this.sendForm();
			event.preventDefault();
		},
		checkEmailField()
		{
			if (this.fieldEmail.match(/^(.*)@(.*)\.[a-zA-Z]{2,}$/))
			{
				if (this.$refs.email)
				{
					this.$refs.email.classList.remove('ui-ctl-danger');
				}
				return true;
			}
			else
			{
				if (document.activeElement !== this.$refs.emailInput)
				{
					if (this.$refs.email)
					{
						this.$refs.email.classList.add('ui-ctl-danger');
					}
				}
				return false;
			}
		},
	},
	template: `
		<transition enter-active-class="bx-livechat-consent-window-show" leave-active-class="bx-livechat-form-close" @after-enter="formShowed">
			<div v-if="false" class="bx-livechat-alert-box bx-livechat-form-show" key="welcome">	
				<div class="bx-livechat-alert-close" @click="hideForm"></div>
				<div class="bx-livechat-alert-form-box">
					<h4 class="bx-livechat-alert-title bx-livechat-alert-title-sm">{{localize.BX_LIVECHAT_MAIL_TITLE_NEW}}</h4>
					<div class="bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg" ref="email">
					   <div class="ui-ctl-after ui-ctl-icon-mail bx-livechat-form-icon" :title="localize.BX_LIVECHAT_FIELD_MAIL_TOOLTIP"></div>
					   <input type="text" class="ui-ctl-element ui-ctl-textbox" :placeholder="localize.BX_LIVECHAT_FIELD_MAIL" v-model="fieldEmail" ref="emailInput" @blur="checkEmailField" @keydown.enter="onFieldEnterPress">
					</div>
					<div class="bx-livechat-btn-box">
						<button class="bx-livechat-btn bx-livechat-btn-success" @click="sendForm">{{localize.BX_LIVECHAT_MAIL_BUTTON_NEW}}</button>
					</div>
				</div>
			</div>	
		</transition>	
	`
});