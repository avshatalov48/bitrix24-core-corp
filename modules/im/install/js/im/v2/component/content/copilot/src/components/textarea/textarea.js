// noinspection JSUnresolvedReference

import { BitrixVue } from 'ui.vue3';
import { CopilotDraftManager } from 'im.v2.lib.draft';

import { ChatTextarea } from 'im.v2.component.textarea';

import { AudioInput } from './audio-input';

import './css/textarea.css';

import type { JsonObject } from 'main.core';

// @vue/component
export const CopilotTextarea = BitrixVue.cloneComponent(ChatTextarea, {
	name: 'CopilotTextarea',
	components: { AudioInput },
	data(): JsonObject
	{
		return {
			...this.parentData(),
			audioMode: false,
		};
	},
	computed:
	{
		isEmptyText(): boolean
		{
			return this.text === '';
		},
	},
	methods:
	{
		onAudioInputStart()
		{
			if (this.isEmptyText)
			{
				return;
			}

			this.text += ' ';
		},
		onAudioInputResult(inputText: string)
		{
			if (!this.audioMode)
			{
				return;
			}
			this.text += inputText;
		},
		openEditPanel()
		{},
		getDraftManager(): CopilotDraftManager
		{
			if (!this.draftManager)
			{
				this.draftManager = CopilotDraftManager.getInstance();
			}

			return this.draftManager;
		},
		sendMessage(): void
		{
			this.parentSendMessage();
			this.audioMode = false;
		},
	},
	template: `
		<div class="bx-im-send-panel__scope bx-im-send-panel__container bx-im-copilot-send-panel__container">
			<div class="bx-im-textarea__container">
				<div @mousedown="onResizeStart" class="bx-im-textarea__drag-handle"></div>
				<div class="bx-im-textarea__content">
					<div class="bx-im-textarea__left">
						<textarea
							v-model="text"
							:style="textareaStyle"
							:placeholder="loc('IM_CONTENT_COPILOT_TEXTAREA_PLACEHOLDER')"
							:maxlength="textareaMaxLength"
							@keydown="onKeyDown"
							@paste="onPaste"
							class="bx-im-textarea__element"
							ref="textarea"
							rows="1"
						></textarea>
						<AudioInput
							:audioMode="audioMode"
							@start="audioMode = true"
							@stop="audioMode = false"
							@inputStart="onAudioInputStart"
							@inputResult="onAudioInputResult"
						/>
					</div>
				</div>
			</div>
			<SendButton :editMode="editMode" :isDisabled="isDisabled" @click="sendMessage" />
		</div>
	`,
});
