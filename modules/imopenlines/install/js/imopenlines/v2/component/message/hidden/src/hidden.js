import { hint } from 'ui.vue3.directives.hint';

import { BaseMessage } from 'im.v2.component.message.base';
import { DateCode, DateFormatter } from 'im.v2.lib.date-formatter';
import { MessageAttach, ReactionList } from 'im.v2.component.message.elements';
import { Parser } from 'im.v2.lib.parser';

import './css/hidden.css';

import type { ImModelMessage } from 'im.v2.model';

// @vue/component
export const HiddenMessage = {
	name: 'HiddenMessage',
	components: { BaseMessage, MessageAttach, ReactionList },
	directives: {
		hint,
	},
	props:
	{
		item: {
			type: Object,
			required: true,
		},
		dialogId: {
			type: String,
			required: true,
		},
	},
	computed:
	{
		message(): ImModelMessage
		{
			return this.item;
		},
		formattedText(): string
		{
			return Parser.decodeMessage(this.item);
		},
		formattedDate(): string
		{
			return DateFormatter.formatByCode(this.message.date, DateCode.shortTimeFormat);
		},
		hintAvailable(): { text: string, popupOptions: Object<string, any> }
		{
			return {
				text: this.loc('IMOL_MESSAGE_HIDDEN_TOOLTIP_TEXT'),
				popupOptions: {
					angle: true,
					targetContainer: document.body,
					offsetTop: -10,
					offsetLeft: 5,
					bindOptions: {
						position: 'top',
					},
				},
			};
		},
	},
	methods:
	{
		loc(phraseCode: string): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode);
		},
	},
	template: `
		<BaseMessage
			:dialogId="dialogId"
			:item="item"
			:withTitle="false"
			:withBackground="false"
			:withContextMenu="false"
			:withReactions="false"
			class="bx-imol-message-hidden__container"
		>
			<div class="bx-imol-message-hidden__content">
				<div class="bx-imol-message-hidden-content__text" v-html="formattedText"></div>
				<div v-if="message.attach.length > 0" class="bx-imol-message-hidden-content__attach">
					<MessageAttach :item="message" :dialogId="dialogId" />
				</div>
				<div class="bx-imol-message-hidden-content__bottom-panel">
					<div class="bx-imol-message-hidden-content__container-date">
						<div class="bx-imol-message-hidden-content__date">
							{{ formattedDate }}
						</div>
						<div
							v-hint="hintAvailable"
							class="bx-imol-message-hidden-content__lock"
						>
							<i class="fa-solid fa-lock"></i>
						</div>
					</div>
				</div>
			</div>
		</BaseMessage>
	`,
};
