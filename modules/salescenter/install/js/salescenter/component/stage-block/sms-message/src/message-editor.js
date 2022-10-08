import {Popup} from 'main.popup';
import {Loc} from 'main.core';
import {MessageEdit} from "./message-edit";
import {MessageView} from "./message-view";
import {MessageControl} from "./message-control";

const MODE_VIEW = 'view';
const MODE_EDIT = 'edit';

const MessageEditor = {
	props: {
		editor: {
			type: Object,
			required: true
		},
		isReadOnly: {
			type: Boolean,
			default: false,
		},
		selectedMode: {
			type: String,
			required: true,
		},
	},
	data()
	{
		return {
			mode: MODE_VIEW,
			text: this.editor.template,
			hasError: false,
			orderPublicUrl: this.editor.url,
			smsEditMessageMode: false
		}
	},
	components:
	{
		'sms-message-edit-block'	:	MessageEdit,
		'sms-message-view-block'	:	MessageView,
		'sms-message-control-block'	:	MessageControl,
	},
	computed:
	{
		getMode()
		{
			return this.mode;
		},

		setMode(value)
		{
			this.mode = value;
		},
	},
	methods:
	{
		isEditable()
		{
			return this.mode === MODE_EDIT && !this.isReadOnly;
		},
		resetError()
		{
			this.hasError = false;
		},

		//region edit
		updateTemplate(text)
		{
			this.editor.template = text;
			this.$root.$app.sendingMethodDesc.text = text;
			this.$root.$app.sendingMethodDesc.text_modes[this.selectedMode] = text;
		},
		showPopupHint(target, message, timer)
		{
			if(this.popup)
			{
				this.popup.destroy();
				this.popup = null;
			}

			if(!target && !message)
			{
				return;
			}

			this.popup = new Popup(null, target, {
				events: {
					onPopupClose: () => {
						this.popup.destroy();
						this.popup = null;
					}
				},
				darkMode: true,
				content: message,
				offsetLeft: target.offsetWidth,
			});

			if(timer)
			{
				setTimeout(() => {
					this.popup.destroy();
					this.popup = null;
				}, timer);
			}

			this.popup.show();
		},
		afterPressKey(e)
		{
			this.afterSavePressKey(e);
		},
		beforeBlur()
		{
			this.hasError = false;
		},
		showHasLinkErrorHint(e)
		{
			this.hasError = true;
		},
		afterSavePressKey(e)
		{
			this.reverseMode();

			if(this.hasError)
			{
				this.showPopupHint(
					e.target,
					Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_TEMPLATE_ERROR'),
					2000
				);
			}

			this.resetError();
		},
		//endregion

		//region view
		showSmsMessagePopupHint(e)
		{
			this.showPopupHint(e.target, Loc.getMessage('SALESCENTER_SMS_MESSAGE_HINT'))
		},

		hidePopupHint()
		{
			if(this.popup)
			{
				this.popup.destroy();
			}
		},
		//endregion

		//region control
		reverseMode()
		{
			this.mode === MODE_EDIT ?
				this.mode = MODE_VIEW :
				this.mode = MODE_EDIT ;
		},

		afterSaveControl(e)
		{
			if(!this.hasError)
			{
				this.reverseMode();
			}
			else
			{
				this.showPopupHint(
					e.target,
					Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_TEMPLATE_ERROR'),
					2000
				);
			}
		},
		//endregion
	},
	template: `
		<div class="salescenter-app-payment-by-sms-item-container-sms-content">
			<div class="salescenter-app-payment-by-sms-item-container-sms-content-message">	
				<template v-if="isEditable()">
					<sms-message-edit-block				
						:text="editor.template"
						:selectedMode="selectedMode"
						v-on:edit-on-before-blur="beforeBlur"
						v-on:edit-on-after-press-key="afterPressKey"
						v-on:edit-on-update-template="updateTemplate"
						v-on:edit-on-has-link-error="showHasLinkErrorHint"
					/>
				</template> 
				<template v-else>
					<sms-message-view-block
						:text="editor.template"
						:orderPublicUrl="orderPublicUrl"
						v-on:view-on-mouseenter="showSmsMessagePopupHint"
						v-on:view-on-mouseleave="hidePopupHint"
					/>
				</template>
				<sms-message-control-block v-if="!isReadOnly"
					:editable="isEditable()"
					v-on:control-on-save="afterSaveControl"
				/>
			</div>
		</div>
	`
};

export {
	MessageEditor
}
