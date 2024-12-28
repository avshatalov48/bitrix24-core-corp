import { CallPopupContainer } from 'call.component.elements';
import { Loc, Dom } from 'main.core';
import { Util, CallAI } from 'call.core';

import type { PopupOptions } from 'main.popup';
import type { JsonObject } from 'main.core';

// @vue/component
export const CallButtonPromo = {
	name: 'CallButtonPromo',
	components: { CallPopupContainer },
	emits: ['close'],
	props:
	{
		bindElement: {
			type: Object,
			required: true,
		},
	},
	data(): JsonObject
	{
		return {
			isAIConcertAccepted: CallAI.agreementAccepted,
		};
	},
	computed:
	{
		getId()
		{
			return 'call-ai-user-list-popup';
		},
		config(): PopupOptions
		{
			return {
				width: 380,
				padding: 0,
				overlay: false,
				autoHide: false,
				closeByEsc: false,
				angle: {
					offset: Dom.getPosition(this.bindElement).width / 2,
					position: 'top',
				},
				closeIcon: true,
				bindElement: this.bindElement,
				offsetTop: 28,
			};
		},
		callButtonPromoText(): string
		{
			return this.isAIConcertAccepted
				? this.loc('CALL_CHAT_HEADER_BUTTON_PROMO_TEXT')
				: this.loc('CALL_CHAT_HEADER_BUTTON_PROMO_TEXT_WITHOUT_TOS')
			;
		},
	},
	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return Loc.getMessage(phraseCode, replacements);
		},
	},
	template: `
		<CallPopupContainer
			:config="config"
			@close="$emit('close')"
			:id="getId"
		>
			<div class='bx-call-chat-header-call-button__promo-container'>
                <div class='bx-call-chat-header-call-button__promo-title'>
                    {{ loc('CALL_CHAT_HEADER_BUTTON_PROMO_TITLE') }}
                </div>
                <div class='bx-call-chat-header-call-button__promo-text'>
                    {{ callButtonPromoText }}
                </div>
			</div>
		</CallPopupContainer>
	`,
};
