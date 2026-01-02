import { CallPopupContainer } from '../popup/popup';
import { Loc, Type } from 'main.core';

import './action-popup.css';

import type { PopupOptions } from 'main.popup';
import type { JsonObject } from 'main.core';

// @vue/component
export const CallActionPopup = {
	name: 'CallActionPopup',
	components: { CallPopupContainer },
	props:
	{
		titleText: {
			type: String,
			required: true,
		},
		descriptionText: {
			type: String,
			required: true,
		},
		okText: {
			type: String,
			required: true,
		},
		cancelText: {
			type: String,
			required: false,
			default: Loc.getMessage('CALL_ACTION_POPUP_CANCEL_BUTTON'),
		},
		okAction: {
			type: Function,
			required: true,
		},
	},
	emits: ['close'],
	data(): JsonObject
	{
		return {
			popupInstance: null,
		};
	},
	computed:
	{
		getId()
		{
			return 'call-action-popup';
		},
		config(): PopupOptions
		{
			return {
				width: 470,
				padding: 0,
				overlay: false,
				autoHide: false,
				closeByEsc: false,
				angle: false,
				closeIcon: true,
				contentBorderRadius: '18px',
			};
		},
	},
	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return Loc.getMessage(phraseCode, replacements);
		},
		onOkButtonClick()
		{
			if (!this.okAction || !Type.isFunction(this.okAction))
			{
				return;
			}

			this.okAction();
			this.popupInstance.destroy();
			this.$emit('close');
		},
		onCancelBtnClick()
		{
			this.popupInstance.destroy();
			this.$emit('close');
		},
		popupCreated(popupInstance)
		{
			this.popupInstance = popupInstance;
		},
	},
	template: `
		<CallPopupContainer
			:config="config"
			@close="$emit('close')"
			:id="getId"
			@popup-instance-created="popupCreated($event)"
		>
			<div class='bx-call-action-popup__container bx-call-action-popup-scope'>
				<div class='bx-call-action-popup__title'>
					{{ titleText }}
				</div>
				<div class='bx-call-action-popup__body'>
					<div class='bx-call-action-popup__description'>{{ descriptionText }}</div>
					<div class='bx-call-action-popup__buttons-wrapper'>
						<div class='bx-call-action-popup__button --cancel' @click="onCancelBtnClick">{{ cancelText }}</div>
						<div class='bx-call-action-popup__button --ok' @click="onOkButtonClick">{{ okText }}</div>
					</div>
				</div>
			</div>
		</CallPopupContainer>
	`,
};
