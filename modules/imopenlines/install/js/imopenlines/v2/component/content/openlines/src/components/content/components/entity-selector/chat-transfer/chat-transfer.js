import { PopupOptions } from 'main.popup';
import { MessengerPopup } from 'im.v2.component.elements';

import { ChatTransferContent } from './chat-transfer-content';

import './css/chat-transfer.css';

const POPUP_ID = 'imol-chat-transfer-popup';

// @vue/component
export const ChatTransfer = {
	name: 'ChatTransfer',
	components: { MessengerPopup, ChatTransferContent },
	props:
	{
		showPopup: {
			type: Boolean,
			required: true,
		},
		bindElement: {
			type: Object,
			required: true,
		},
		dialogId: {
			type: String,
			required: true,
		},
		popupConfig: {
			type: Object,
			required: true,
		},
	},
	emits: ['close'],
	computed:
	{
		POPUP_ID: () => POPUP_ID,
		config(): PopupOptions
		{
			return {
				titleBar: this.$Bitrix.Loc.getMessage('IMOL_CONTENT_BUTTON_TRANSFER'),
				closeIcon: true,
				bindElement: this.bindElement,
				offsetTop: this.popupConfig.offsetTop,
				offsetLeft: this.popupConfig.offsetLeft,
				padding: 0,
				contentPadding: 0,
				contentBackground: '#fff',
				className: 'bx-imol-entity-selector-chat-transfer__container',
			};
		},
	},
	template: `
		<MessengerPopup
			v-if="showPopup"
			:config="config"
			@close="$emit('close')"
			:id="POPUP_ID"
		>
			<ChatTransferContent :dialogId="dialogId" @close="$emit('close')"/>
		</MessengerPopup>
	`,
};
