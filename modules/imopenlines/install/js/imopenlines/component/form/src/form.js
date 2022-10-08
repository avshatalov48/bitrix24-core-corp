/**
 * Bitrix Messenger
 * Form Vue component
 *
 * @package bitrix
 * @subpackage im
 * @copyright 2001-2019 Bitrix
 */

import { BitrixVue } from "ui.vue";
import { Vuex } from "ui.vue.vuex";
import './form.css';
import 'ui.fonts.opensans';

const EVENT_POSTFIX = 'Openlines';
const LIVECHAT_PREFIX = 'livechat';

BitrixVue.component('bx-imopenlines-form',
{
	props: {
		message: {
			type: Object,
			required: false
		}
	},
	data: function() {
		return {
			formSuccess: false,
			formError: false
		}
	},
	mounted()
	{
		if (this.filledFormFlag)
		{
			this.formSuccess = true;
		}
	},
	computed:
	{
		chatId()
		{
			return this.application.dialog.chatId;
		},
		formId()
		{
			if (this.message)
			{
				return String(this.message.params.CRM_FORM_ID);
			}

			if (this.widget.common.crmFormsSettings.welcomeFormId)
			{
				return this.widget.common.crmFormsSettings.welcomeFormId;
			}

			return '';
		},
		formSec()
		{
			if (this.message)
			{
				return this.message.params.CRM_FORM_SEC;
			}

			if (this.widget.common.crmFormsSettings.welcomeFormSec)
			{
				return this.widget.common.crmFormsSettings.welcomeFormSec;
			}

			return '';
		},
		showForm()
		{
			return this.formId && this.formSec && !this.formSuccess && !this.formError;
		},
		filledFormFlag()
		{
			if (this.message)
			{
				return this.message.params.CRM_FORM_FILLED === 'Y';
			}

			return false;
		},
		messageCount()
		{
			return this.dialog.messageCount;
		},
		...Vuex.mapState({
			widget: state => state.widget,
			application: state => state.application,
			dialog: state => state.dialogues.collection[state.application.dialog.dialogId]
		}),
	},
	watch:
	{
		filledFormFlag(newValue)
		{
			if (newValue === true && !this.formSuccess)
			{
				this.formSuccess = true;
			}
		},
		chatId(newValue)
		{
			// chatId > 0 means chat and user were initialized
			if (newValue !== 0 && this.widgetInitPromiseResolve)
			{
				this.widgetInitPromiseResolve();
			}
		}
	},
	methods:
	{
		getCrmBindings()
		{
			return new Promise((resolve, reject) => {
				this.$Bitrix.RestClient.get().callMethod('imopenlines.widget.crm.bindings.get', {
					'OPENLINES_CODE':  this.buildOpenlinesCode()
				}).then(resolve).catch(reject);
			});
		},
		onBeforeFormSubmit(eventData)
		{
			if (this.signedEntities && this.signedEntities !== '')
			{
				eventData.sign = this.signedEntities;
			}
		},
		onFormSubmit(eventData)
		{
			this.eventData = eventData;
			// redefine form promise so we can send form manually later
			this.eventData.promise = this.eventData.promise.then(() => new Promise(resolve => {
				if (this.chatId === 0)
				{
					// promise we resolve after user and chat are inited, resolve method is saved to use in chatId watcher
					new Promise((widgetResolve, widgetReject) => {
						this.widgetInitPromiseResolve = widgetResolve;
					}).then(() => {
						this.setFormProperties();
						return resolve();
					});

					this.getApplication().requestData();
				}
				// we have user and chat so we can just resolve form promise instantly
				else
				{
					// request current crm bindings and attach them to form
					if (this.widget.common.crmFormsSettings.welcomeFormDelay)
					{
						this.getCrmBindings().then((result) => {
							this.signedEntities = result.data();

							this.setFormProperties();
							return resolve();
						}).catch((error) => {
							console.error('Error getting CRM bindings', error);
						});
					}
					else
					{
						this.setFormProperties();
						return resolve();
					}
				}
			}));
		},
		setFormProperties()
		{
			if (!this.eventData)
			{
				return false;
			}

			this.eventData.form.setProperty('eventNamePostfix', EVENT_POSTFIX);
			this.eventData.form.setProperty('openlinesCode', this.buildOpenlinesCode());
			if (this.message)
			{
				this.eventData.form.setProperty('messageId', this.message.id);
				this.eventData.form.setProperty('isWelcomeForm', this.message.params.IS_WELCOME_FORM ?? 'N');
			}
			else
			{
				this.eventData.form.setProperty('isWelcomeForm', 'Y');
			}
		},
		buildOpenlinesCode()
		{
			let configId = 0;
			if (this.dialog.entityId !== '')
			{
				configId = this.dialog.entityId.split('|')[0];
			}
			const chatId = this.dialog.chatId || 0;
			const userId = this.application.common.userId || 0;

			return `${LIVECHAT_PREFIX}|${configId}|${chatId}|${userId}`;
		},
		onFormSendSuccess()
		{
			if (!this.message)
			{
				this.$store.commit('widget/common', {dialogStart: true});
			}

			this.$emit('formSendSuccess');
			this.formSuccess = true;
		},
		onFormSendError(error)
		{
			this.formError = true;
			this.$emit('formSendError', {error});
		},
		getSuccessText()
		{
			return this.widget.common.crmFormsSettings.successText;
		},
		getErrorText()
		{
			return this.widget.common.crmFormsSettings.errorText;
		},
		getApplication()
		{
			return this.$Bitrix.Application.get();
		}
	},
	template: `
		<div class="bx-im-message bx-im-message-without-menu bx-im-message-without-avatar bx-imopenlines-form-wrapper">
			<div v-show="showForm" class="bx-imopenlines-form-content">
				<bx-crm-form
					:id="formId"
					:sec="formSec"
					:address="widget.common.host"
					:lang="application.common.languageId"
					@form:submit:post:before="onBeforeFormSubmit"
					@form:submit="onFormSubmit"
					@form:send:success="onFormSendSuccess"
					@form:send:error="onFormSendError"
				/>
			</div>
			<div v-show="formSuccess" class="bx-imopenlines-form-result-container bx-imopenlines-form-success">
				<div class="bx-imopenlines-form-result-icon"></div>
				<div class="bx-imopenlines-form-result-title">
					{{ getSuccessText() }}
				</div>
			</div>
			<div v-show="formError" class="bx-imopenlines-form-result-container bx-imopenlines-form-error">
				<div class="bx-imopenlines-form-result-icon"></div>
				<div class="bx-imopenlines-form-result-title">
					{{ getErrorText() }}
				</div>
			</div>
		</div>
	`
});
