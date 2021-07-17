/**
 * Bitrix OpenLines widget
 * Form welcome component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import 'ui.icons';
import 'ui.forms';

import {BitrixVue} from "ui.vue";
import {Vuex} from "ui.vue.vuex";
import {Utils} from "im.lib.utils";
import {FormType} from "../const";

BitrixVue.component('bx-livechat-form-welcome',
{
	data()
	{
		return {
			fieldName: '',
			fieldEmail: '',
			fieldPhone: '',
			isFullForm: Utils.platform.isMobile()
		}
	},
	watch:
	{
		fieldName()
		{
			clearTimeout(this.showFormTimeout);
			this.showFormTimeout = setTimeout(this.showFullForm, 1000);

			clearTimeout(this.fieldNameTimeout);
			this.fieldNameTimeout = setTimeout(this.checkNameField, 300);
		},
		fieldEmail(value)
		{
			clearTimeout(this.fieldEmailTimeout);
			this.fieldEmailTimeout = setTimeout(this.checkEmailField, 300);
		},
		fieldPhone(value)
		{
			clearTimeout(this.fieldPhoneTimeout);
			this.fieldPhoneTimeout = setTimeout(this.checkPhoneField, 300);
		}
	},
	computed:
	{
		localize()
		{
			return BitrixVue.getFilteredPhrases('BX_LIVECHAT_', this);
		},
		...Vuex.mapState({
			widget: state => state.widget,
		})
	},
	created()
	{
		this.fieldName = '' + this.widget.user.name;
		this.fieldEmail = '' + this.widget.user.email;
		this.fieldPhone = '' + this.widget.user.phone;
	},
	methods:
	{
		formShowed()
		{
			if (!Utils.platform.isMobile())
			{
				this.$refs.nameInput.focus();
			}
		},
		showFullForm()
		{
			clearTimeout(this.showFormTimeout);
			this.isFullForm = true;
		},
		sendForm()
		{
			let name = this.fieldName;
			let email = this.checkEmailField()? this.fieldEmail: '';
			let phone = this.checkPhoneField()? this.fieldPhone: '';

			if (name || email || phone)
			{
				this.$Bitrix.Application.get().sendForm(FormType.welcome, {name, email, phone});
			}

			this.hideForm();
		},
		hideForm(event)
		{
			clearTimeout(this.showFormTimeout);
			clearTimeout(this.fieldNameTimeout);
			clearTimeout(this.fieldEmailTimeout);
			clearTimeout(this.fieldPhoneTimeout);

			this.$parent.hideForm();
		},
		onFieldEnterPress(event)
		{
			if (event.target === this.$refs.nameInput)
			{
				this.showFullForm();
				this.$refs.emailInput.focus();
			}
			else if (event.target === this.$refs.emailInput)
			{
				this.$refs.phoneInput.focus();
			}
			else
			{
				this.sendForm();
			}

			event.preventDefault();
		},
		checkNameField()
		{
			if (this.fieldName.length > 0)
			{
				if (this.$refs.name)
				{
					this.$refs.name.classList.remove('ui-ctl-danger');
				}
				return true;
			}
			else
			{
				if (document.activeElement !== this.$refs.nameInput)
				{
					if (this.$refs.name)
					{
						this.$refs.name.classList.add('ui-ctl-danger');
					}
				}
				return false;
			}
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
		checkPhoneField()
		{
			if (this.fieldPhone.match(/^(\s*)?(\+)?([- _():=+]?\d[- _():=+]?){10,14}(\s*)?$/))
			{
				if (this.$refs.phone)
				{
					this.$refs.phone.classList.remove('ui-ctl-danger');
				}
				return true;
			}
			else
			{
				if (document.activeElement !== this.$refs.phoneInput)
				{
					if (this.$refs.phone)
					{
						this.$refs.phone.classList.add('ui-ctl-danger');
					}
				}
				return false;
			}
		}
	},
	template: `
		<transition enter-active-class="bx-livechat-consent-window-show" leave-active-class="bx-livechat-form-close" @after-enter="formShowed">
			<div class="bx-livechat-alert-box bx-livechat-form-show" key="welcome">	
				<div class="bx-livechat-alert-close" @click="hideForm"></div>
				<div class="bx-livechat-alert-form-box">
					<h4 class="bx-livechat-alert-title bx-livechat-alert-title-sm">{{localize.BX_LIVECHAT_ABOUT_TITLE}}</h4>
					<div class="bx-livechat-form-item ui-ctl ui-ctl-w100 ui-ctl-lg" ref="name">
					   <input type="text" class="ui-ctl-element ui-ctl-textbox" :placeholder="localize.BX_LIVECHAT_FIELD_NAME" v-model="fieldName" ref="nameInput" @blur="checkNameField" @keydown.enter="onFieldEnterPress"  @keydown.tab="onFieldEnterPress">
					</div>
					<div :class="['bx-livechat-form-short', {
						'bx-livechat-form-full': isFullForm,
					}]">
						<div class="bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg" ref="email">
						   <div class="ui-ctl-after ui-ctl-icon-mail bx-livechat-form-icon" :title="localize.BX_LIVECHAT_FIELD_MAIL_TOOLTIP"></div>
						   <input type="text" class="ui-ctl-element ui-ctl-textbox" :placeholder="localize.BX_LIVECHAT_FIELD_MAIL" v-model="fieldEmail" ref="emailInput" @blur="checkEmailField" @keydown.enter="onFieldEnterPress">
						</div>
						<div class="bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg" ref="phone">
						   <div class="ui-ctl-after ui-ctl-icon-phone bx-livechat-form-icon" :title="localize.BX_LIVECHAT_FIELD_PHONE_TOOLTIP"></div>
						   <input type="text" class="ui-ctl-element ui-ctl-textbox" :placeholder="localize.BX_LIVECHAT_FIELD_PHONE" v-model="fieldPhone" ref="phoneInput" @blur="checkPhoneField" @keydown.enter="onFieldEnterPress">
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