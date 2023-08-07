import { Extension, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { getFilesFromDataTransfer, isFilePasted } from 'ui.uploader.core';

import { EventType, LocalStorageKey, SoundType } from 'im.v2.const';
import { Logger } from 'im.v2.lib.logger';
import { DraftManager } from 'im.v2.lib.draft';
import { Utils } from 'im.v2.lib.utils';
import { Parser } from 'im.v2.lib.parser';
import { LocalStorageManager } from 'im.v2.lib.local-storage';
import { MessageService, SendingService } from 'im.v2.provider.service';
import { SoundNotificationManager } from 'im.v2.lib.sound-notification';

import { ResizeManager } from './classes/resize-manager';
import { TypingService } from './classes/typing-service';
import { Text } from './classes/text';
import { SmileSelector } from './components/smile-selector/smile-selector';
import { EditPanel } from './components/edit-panel';
import { UploadMenu } from './components/upload-menu/upload-menu';
import { CreateEntityMenu } from './components/create-entity-menu/create-entity-menu';
import { SendButton } from './components/send-button';
import { MarketAppsPanel } from './components/market-apps-panel/market-apps-panel';
import { UploadPreviewPopup } from './components/upload-preview/upload-preview-popup';

import './css/textarea.css';

import type { ImModelDialog, ImModelMessage } from 'im.v2.model';
import type { InsertTextEvent, InsertMentionEvent, EditMessageEvent } from 'im.v2.const';

// @vue/component
export const ChatTextarea = {
	components: {
		EditPanel,
		UploadMenu,
		CreateEntityMenu,
		SmileSelector,
		SendButton,
		UploadPreviewPopup,
		MarketAppsPanel,
	},
	props: {
		dialogId: {
			type: String,
			default: '',
		},
	},
	data(): { [key: string]: any}
	{
		return {
			text: '',
			mentions: {},
			textareaHeight: ResizeManager.minHeight,
			editMessageId: 0,
			showMarketApps: false,

			showUploadPreviewPopup: false,
			previewPopupUploaderId: '',
		};
	},
	computed:
	{
		dialog(): ImModelDialog
		{
			return this.$store.getters['dialogues/get'](this.dialogId, true);
		},
		dialogInited(): boolean
		{
			return this.dialog.inited;
		},
		editMode(): boolean
		{
			return Boolean(this.editMessageId);
		},
		isDisabled(): boolean
		{
			return this.text.trim() === '' && !this.editMode;
		},
		textareaStyle(): Object
		{
			let height = `${this.textareaHeight}px`;
			if (this.textareaHeight === 'auto')
			{
				height = 'auto';
			}

			return {
				height,
				maxHeight: height,
			};
		},
		textareaMaxLength(): number
		{
			const settings = Extension.getSettings('im.v2.component.textarea');

			return settings.get('maxLength');
		},
		hasMentions(): boolean
		{
			return Object.keys(this.mentions).length > 0;
		},
	},
	watch:
	{
		text(newValue)
		{
			this.adjustTextareaHeight();
			if (!this.editMode)
			{
				DraftManager.getInstance().setDraft(this.dialogId, newValue);
			}

			if (Type.isStringFilled(newValue))
			{
				this.getTypingService().startTyping();
			}
		},
	},
	created()
	{
		this.initResizeManager();
		this.restoreTextareaHeight();
		this.restoreMarketPanelOpenState();
		this.restoreDraftText();

		EventEmitter.subscribe(EventType.textarea.insertMention, this.onInsertMention);
		EventEmitter.subscribe(EventType.textarea.insertText, this.onInsertText);
		EventEmitter.subscribe(EventType.textarea.editMessage, this.onEditMessage);
	},
	mounted()
	{
		this.$refs.textarea.focus();
	},
	beforeUnmount()
	{
		this.resizeManager.destroy();
		EventEmitter.unsubscribe(EventType.textarea.insertMention, this.onInsertMention);
		EventEmitter.unsubscribe(EventType.textarea.insertText, this.onInsertText);
		EventEmitter.unsubscribe(EventType.textarea.editMessage, this.onEditMessage);
	},
	methods:
	{
		sendMessage()
		{
			this.text = this.text.trim();
			if (this.isDisabled || !this.dialogInited)
			{
				return;
			}

			if (this.editMode)
			{
				if (this.text === '')
				{
					this.getMessageService().deleteMessage(this.editMessageId);
				}
				else
				{
					this.getMessageService().editMessageText(this.editMessageId, this.text);
				}
				this.closeEditPanel();
				this.clear();

				return;
			}

			const text = this.hasMentions ? this.replaceMentions(this.text) : this.text;

			this.getSendingService().sendMessage({ text, dialogId: this.dialogId });
			this.getTypingService().stopTyping();
			this.clear();
			DraftManager.getInstance().clearDraftInRecentList(this.dialogId);
			SoundNotificationManager.getInstance().playOnce(SoundType.send);
		},
		replaceMentions(text: string): string
		{
			if (!this.hasMentions)
			{
				return '';
			}

			let textWithMentions = text;
			Object.entries(this.mentions).forEach((mention) => {
				const [mentionText, mentionReplacement] = mention;
				textWithMentions = textWithMentions.replace(mentionText, mentionReplacement);
			});

			return textWithMentions;
		},
		clear()
		{
			this.text = '';
			this.mentions = {};
		},
		openEditPanel(messageId: number)
		{
			this.showMarketApps = false;
			const message: ImModelMessage = this.$store.getters['messages/getById'](messageId);

			if (message.isDeleted)
			{
				return;
			}

			this.editMessageId = messageId;
			this.text = Parser.prepareEdit(message);

			this.$refs.textarea.focus();
		},
		closeEditPanel()
		{
			this.editMessageId = 0;
			this.restoreMarketPanelOpenState();
		},
		async adjustTextareaHeight()
		{
			this.textareaHeight = 'auto';

			await this.$nextTick();
			const newMaxPoint = Math.min(ResizeManager.maxHeight, this.$refs.textarea.scrollHeight);
			if (this.resizedTextareaHeight)
			{
				this.textareaHeight = Math.max(newMaxPoint, this.resizedTextareaHeight);

				return;
			}

			this.textareaHeight = Math.max(newMaxPoint, ResizeManager.minHeight);
		},
		saveTextareaHeight()
		{
			const WRITE_TO_STORAGE_TIMEOUT = 200;
			clearTimeout(this.saveTextareaTimeout);
			this.saveTextareaTimeout = setTimeout(() => {
				LocalStorageManager.getInstance().set(LocalStorageKey.textareaHeight, this.resizedTextareaHeight);
			}, WRITE_TO_STORAGE_TIMEOUT);
		},
		restoreTextareaHeight()
		{
			const rawSavedHeight = LocalStorageManager.getInstance().get(LocalStorageKey.textareaHeight);
			const savedHeight = Number.parseInt(rawSavedHeight, 10);
			if (!savedHeight)
			{
				return;
			}

			this.resizedTextareaHeight = savedHeight;
			this.adjustTextareaHeight();
		},
		restoreDraftText()
		{
			this.text = DraftManager.getInstance().getDraft(this.dialogId);
		},
		onKeyDown(event: KeyboardEvent)
		{
			const exitEditCombination = Utils.key.isCombination(event, 'Escape');
			if (this.editMode && exitEditCombination)
			{
				this.onEditPanelClose();

				return;
			}

			const sendMessageCombination = Utils.key.isCombination(event, ['Enter', 'NumpadEnter']);
			const newLineCombination = Utils.key.isCombination(event, 'Shift+Enter');
			if (sendMessageCombination && !newLineCombination)
			{
				event.preventDefault();
				this.sendMessage();
			}

			const tabCombination = Utils.key.isCombination(event, 'Tab');
			if (tabCombination)
			{
				event.preventDefault();
				const { textarea } = this.$refs;
				if (event.shiftKey)
				{
					this.text = Text.removeTab(textarea);

					return;
				}
				this.text = Text.addTab(textarea);

				return;
			}

			const decorationCombination = Utils.key.isCombination(event, ['Ctrl+b', 'Ctrl+i', 'Ctrl+u', 'Ctrl+s']);
			if (decorationCombination)
			{
				event.preventDefault();
				const { textarea } = this.$refs;
				this.text = Text.handleDecorationTag(textarea, event.code);

				return;
			}

			if (this.text === '' && Utils.key.isCombination(event, 'ArrowUp'))
			{
				event.preventDefault();
				const lastOwnMessageId = this.$store.getters['messages/getLastOwnMessageId'](this.dialog.chatId);
				if (lastOwnMessageId)
				{
					this.openEditPanel(lastOwnMessageId);
				}
			}
		},
		onResizeStart(event)
		{
			this.resizeManager.onResizeStart(event, this.textareaHeight);
		},
		onFileSelect(fileEvent: InputEvent)
		{
			const files = Object.values(fileEvent.target.files);

			this.getSendingService().sendFilesFromInput(files, this.dialogId);
		},
		onDiskFileSelect({ files })
		{
			this.getSendingService().sendFilesFromDisk(files, this.dialogId);
		},
		onInsertMention(event: BaseEvent<InsertMentionEvent>)
		{
			const { mentionText, mentionReplacement } = event.getData();
			this.mentions[mentionText] = mentionReplacement;
			this.text += `${mentionText} `;
			this.$refs.textarea.focus();
		},
		onInsertText(event: BaseEvent<InsertTextEvent>)
		{
			// TODO sync with im/install/js/im/component/textarea/src/textarea.js:164
			const textarea = this.$refs.textarea;
			const { text, withNewLine, replace } = event.getData();

			if (replace)
			{
				this.text = '';
				textarea.value = '';
				textarea.selectionStart = 0;
				textarea.selectionEnd = 0;
			}

			if (this.text.length === 0)
			{
				this.text = text;
			}
			else
			{
				this.text = withNewLine ? `${this.text}\n${text}` : `${this.text} ${text}`;
			}

			textarea.focus();
		},
		onEditMessage(event: BaseEvent<EditMessageEvent>)
		{
			const { messageId } = event.getData();
			this.openEditPanel(messageId);
		},
		onEditPanelClose()
		{
			this.closeEditPanel();
			this.clear();
		},
		onPaste(clipboardEvent: ClipboardEvent)
		{
			const { clipboardData } = clipboardEvent;
			if (!clipboardData || !isFilePasted(clipboardData))
			{
				return;
			}

			clipboardEvent.preventDefault();

			getFilesFromDataTransfer(clipboardData).then((files: File[]) => {
				const imagesOnly = files.filter((file) => Utils.file.isImage(file.name));
				if (imagesOnly.length === 0)
				{
					return [];
				}

				return this.getSendingService().sendFilesFromClipboard(imagesOnly, this.dialogId);
			}).then(({ files, uploaderId }) => {
				if (files.length === 0)
				{
					return;
				}

				this.showUploadPreviewPopup = true;
				this.previewPopupUploaderId = uploaderId;
			}).catch((error) => {
				Logger.error('Textarea: onPaste error', error);
			});
		},
		onMarketIconClick()
		{
			this.showMarketApps = !this.showMarketApps;
			this.saveMarketPanelOpenState(this.showMarketApps);
			if (this.showMarketApps && this.editMode)
			{
				this.onEditPanelClose();
			}
		},
		initResizeManager()
		{
			this.resizeManager = new ResizeManager();
			this.resizeManager.subscribe(ResizeManager.events.onHeightChange, ({ data: { newHeight } }) => {
				Logger.warn('Textarea: Resize height change', newHeight);
				this.textareaHeight = newHeight;
			});
			this.resizeManager.subscribe(ResizeManager.events.onResizeStop, () => {
				Logger.warn('Textarea: Resize stop');
				this.resizedTextareaHeight = this.textareaHeight;
				this.saveTextareaHeight();
			});
		},
		getSendingService(): SendingService
		{
			if (!this.sendingService)
			{
				this.sendingService = SendingService.getInstance();
			}

			return this.sendingService;
		},
		getTypingService(): TypingService
		{
			if (!this.typingService)
			{
				this.typingService = new TypingService(this.dialogId);
			}

			return this.typingService;
		},
		getMessageService(): MessageService
		{
			if (!this.messageService)
			{
				this.messageService = new MessageService({ chatId: this.dialog.chatId });
			}

			return this.messageService;
		},
		loc(phraseCode: string): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode);
		},
		restoreMarketPanelOpenState()
		{
			const showMarketApps = LocalStorageManager.getInstance().get(LocalStorageKey.textareaMarketOpened);
			this.showMarketApps = Boolean(showMarketApps);
		},
		saveMarketPanelOpenState(showMarketApps: boolean)
		{
			const WRITE_TO_STORAGE_TIMEOUT = 200;
			clearTimeout(this.saveMarketOpenedStateTimeout);
			this.saveMarketOpenedStateTimeout = setTimeout(() => {
				LocalStorageManager.getInstance().set(LocalStorageKey.textareaMarketOpened, showMarketApps);
			}, WRITE_TO_STORAGE_TIMEOUT);
		},
		onSendFilesFromPreviewPopup(event)
		{
			this.text = '';
			const { groupFiles, text, uploaderId, sendAsFile } = event;
			this.getSendingService().sendMessagesWithFiles({
				groupFiles,
				text,
				uploaderId,
				dialogId: this.dialogId,
				sendAsFile,
			});
		},
	},
	template: `
		<div class="bx-im-send-panel__scope bx-im-send-panel__container">
			<div class="bx-im-textarea__container">
				<div @mousedown="onResizeStart" class="bx-im-textarea__drag-handle"></div>
				<EditPanel v-if="editMode" :messageId="editMessageId" @close="onEditPanelClose" />
				<MarketAppsPanel v-if="showMarketApps" :dialogId="dialogId"/>
				<div class="bx-im-textarea__content">
					<div class="bx-im-textarea__left">
						<div class="bx-im-textarea__upload_container">
							<UploadMenu @fileSelect="onFileSelect" @diskFileSelect="onDiskFileSelect" />
						</div>
						<textarea
							v-model="text"
							:style="textareaStyle"
							:placeholder="loc('IM_TEXTAREA_PLACEHOLDER')"
							:maxlength="textareaMaxLength"
							@keydown="onKeyDown"
							@paste="onPaste"
							class="bx-im-textarea__element"
							ref="textarea"
							rows="1"
						></textarea>
					</div>
					<div class="bx-im-textarea__right">
						<div class="bx-im-textarea__action-panel">
							<CreateEntityMenu :dialogId="dialogId" :textareaValue="text" />
							<div 
								:title="loc('IM_TEXTAREA_ICON_APPLICATION')"
								@click="onMarketIconClick"
								class="bx-im-textarea__icon --market"
								:class="{'--active': showMarketApps}"
							></div>
							<SmileSelector :dialogId="dialogId" />
						</div>
					</div>
				</div>
			</div>
			<SendButton :editMode="editMode" :isDisabled="isDisabled" @click="sendMessage" />
			<UploadPreviewPopup
				v-if="showUploadPreviewPopup"
				:dialogId="dialogId"
				:uploaderId="previewPopupUploaderId"
				:textareaValue="text"
				@close="showUploadPreviewPopup = false"
				@sendFiles="onSendFilesFromPreviewPopup"
			/>
		</div>
	`,
};
