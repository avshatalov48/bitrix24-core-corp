// noinspection JSUnresolvedReference

import { CopilotDraftManager } from 'im.v2.lib.draft';
import { BitrixVue } from 'ui.vue3';

import { ChatTextarea } from 'im.v2.component.textarea';

import '../css/textarea.css';

// @vue/component
export const CopilotTextarea = BitrixVue.cloneComponent(ChatTextarea, {
	name: 'CopilotTextarea',
	methods:
	{
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
	},
	template: `
		<div class="bx-im-send-panel__scope bx-im-send-panel__container bx-im-copilot-send-panel__container">
			<div class="bx-im-textarea__container">
				<div @mousedown="onResizeStart" class="bx-im-textarea__drag-handle"></div>
				<EditPanel v-if="editMode" :messageId="editMessageId" @close="onEditPanelClose" />
				<ReplyPanel v-if="replyMode" :messageId="replyMessageId" @close="closeReplyPanel" />
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
					</div>
				</div>
			</div>
			<SendButton :editMode="editMode" :isDisabled="isDisabled" @click="sendMessage" />
		</div>
	`,
});
