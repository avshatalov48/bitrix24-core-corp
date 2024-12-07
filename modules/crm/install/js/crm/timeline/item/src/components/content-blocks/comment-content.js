import { Runtime, Type } from 'main.core';
import { Loader } from 'main.loader';
import { BitrixVue } from 'ui.vue3';
import { CommentEditor } from 'crm.timeline.editors.comment-editor';

import { Action } from '../../action';
import { EditableDescription } from './editable-description';
import { ButtonState } from '../enums/button-state';

const TYPE_LOAD_FILES_BLOCK = 1;
const TYPE_LOAD_TEXT_CONTENT = 2;

/**
 * @extends EditableDescription
 */
export default BitrixVue.cloneComponent(EditableDescription, {
	props: {
		filesCount: {
			type: Number,
			required: false,
			default: 0,
		},
		hasInlineFiles: {
			type: Boolean,
			required: false,
			default: false,
		},
		loadAction: {
			type: Object,
			required: false,
			default: () => ({}),
		},
	},

	data(): Object {
		return {
			...this.parentData(),
			value: this.text,
			oldValue: this.text,
			isTextLoaded: false,
			isTextChanged: false,
			isMoving: false,
			isFilesBlockDisplayed: this.filesCount > 0,
			filesHtmlBlock: null,
			loader: Object.freeze(null),
			editor: Object.freeze(null),
		};
	},

	computed: {
		textWrapperClassName(): Array
		{
			return [
				'crm-timeline__editable-text_content',
				{
					'--is-editor-loaded': this.isEdit,
				},
			];
		},
	},

	methods: {
		startEditing(): void
		{
			this.isEdit = true;
			this.isCollapsed = true;

			this.$nextTick(() => {
				this.editor.show(this.$refs.editor);
			});

			this.emitEvent('Comment:StartEdit');
		},

		cancelEditing(): void
		{
			if (!this.isEdit || this.isSaving)
			{
				return;
			}

			this.value = this.oldValue;
			this.isEdit = false;

			if (this.filesHtmlBlock)
			{
				void Runtime.html(this.$refs.files, this.filesHtmlBlock).then(() => {
					this.registerImages(this.$refs.files);
					BX.LazyLoad.showImages();

					this.emitEvent('Comment:FinishEdit');
				});
			}
			else
			{
				this.emitEvent('Comment:FinishEdit');
			}
		},

		toggleIsCollapsed(): void
		{
			this.parentToggleIsCollapsed();

			if (!this.isTextLoaded)
			{
				this.executeLoadAction(TYPE_LOAD_TEXT_CONTENT, this.$refs.text);
			}
		},

		checkIsLongText(): boolean
		{
			const textBlock = this.$refs.text;
			if (!textBlock)
			{
				return false;
			}

			const textBlockMaxHeightStyle = window.getComputedStyle(textBlock)
				.getPropertyValue('--crm-timeline__editable-text_max-height')
			;
			const textBlockMaxHeight = parseFloat(textBlockMaxHeightStyle.slice(0, -2));

			const root = this.filesCount > 0
				? this.$refs.rootElement
				: this.$refs.rootWrapperElement;

			const parentComputedStyles = window.getComputedStyle(root);
			const parentHeight = root.offsetHeight
				- parseFloat(parentComputedStyles.paddingTop)
				- parseFloat(parentComputedStyles.paddingBottom)
			;

			const isLongText = parentHeight > textBlockMaxHeight;

			return isLongText || this.hasInlineFiles;
		},

		saveContent(): void
		{
			const isSaveDisabled = this.saveTextButtonState === ButtonState.LOADING
				|| !this.isEdit
				|| !this.saveAction
			;

			if (isSaveDisabled)
			{
				return;
			}

			const content = this.editor.getContent();
			if (!Type.isStringFilled(content))
			{
				return;
			}

			const htmlContent = this.editor.getHtmlContent();
			const attachmentList = this.editor.getAttachments();
			const attachmentAllowEditOptions = this.editor.getAttachmentsAllowEditOptions(attachmentList);
			this.isSaving = true;

			void this.executeSaveAction(content, attachmentList, attachmentAllowEditOptions).then(() => {
				this.isEdit = false;

				if (!this.isTextChanged)
				{
					this.oldValue = htmlContent;
					this.value = htmlContent;
				}

				this.$nextTick((): void => {
					this.isLongText = this.checkIsLongText();
					this.executeLoadAction(TYPE_LOAD_FILES_BLOCK, this.$refs.files);
				});

				this.emitEvent('Comment:FinishEdit');
			}).finally(() => {
				this.isSaving = false;
			});
		},

		executeSaveAction(content: String, attachmentList: Array, attachmentAllowEditOptions: Object): Promise
		{
			// to avoid unintended props mutation
			const actionDescription = Runtime.clone(this.saveAction);

			actionDescription.actionParams ??= {};
			actionDescription.actionParams.id = actionDescription.actionParams.commentId;
			actionDescription.actionParams.fields = {
				COMMENT: content,
				ATTACHMENTS: attachmentList,
			};

			if (Object.keys(attachmentAllowEditOptions).length > 0)
			{
				actionDescription.actionParams.CRM_TIMELINE_DISK_ATTACHED_OBJECT_ALLOW_EDIT = attachmentAllowEditOptions;
			}

			const action = new Action(actionDescription);

			return action.execute(this);
		},

		executeLoadAction(type: Number, node: HTMLElement): void
		{
			if (this.filesCount === 0)
			{
				this.filesHtmlBlock = null;

				return;
			}

			if (!Type.isDomNode(node) || !this.loadAction)
			{
				return;
			}

			const actionDescription = Runtime.clone(this.loadAction);
			actionDescription.actionParams ??= {};
			actionDescription.actionParams.options = type;

			const action = new Action(actionDescription);

			this.showLoader(true);

			action.execute(this).then((response) => {
				if (type === TYPE_LOAD_FILES_BLOCK)
				{
					this.filesHtmlBlock = response.data.html;
				}
				else if (type === TYPE_LOAD_TEXT_CONTENT)
				{
					this.isTextLoaded = true;
				}

				void Runtime.html(node, response.data.html).then(() => {
					this.registerImages(node);
					BX.LazyLoad.showImages();

					this.showLoader(false);
				});
			}).catch(() => {
				if (type === TYPE_LOAD_FILES_BLOCK)
				{
					this.filesHtmlBlock = null;
				}
				else if (type === TYPE_LOAD_TEXT_CONTENT)
				{
					this.isTextLoaded = false;
				}

				this.showLoader(false);
			});
		},

		registerImages(node: HTMLElement): void
		{
			if (!Type.isDomNode(node))
			{
				return;
			}

			const idsList = [];
			const commentImages = node.querySelectorAll('[data-viewer-type="image"]');
			const commentImagesLength = commentImages.length;
			if (commentImagesLength > 0)
			{
				for (let i = 0; i < commentImagesLength; ++i)
				{
					if (Type.isDomNode(commentImages[i]))
					{
						commentImages[i].id += BX.util.getRandomString(4);
						idsList.push(commentImages[i].id);
					}
				}

				if (idsList.length > 0)
				{
					BX.LazyLoad.registerImages(idsList, null, { dataSrcName: 'thumbSrc' });
				}
			}

			BX.LazyLoad.registerImages(idsList, null, { dataSrcName: 'thumbSrc' });
		},

		showLoader(showLoader: boolean): void
		{
			if (showLoader)
			{
				if (!this.loader)
				{
					this.loader = new Loader({
						size: 20,
						mode: 'inline',
					});
				}

				this.loader.show(this.$refs.files);
			}
			else if (this.loader)
			{
				this.loader.hide();
			}
		},

		createEditor(): void
		{
			this.editor = new CommentEditor(this.loadAction.actionParams.commentId);
		},

		setIsMoving(flag: boolean = true): void
		{
			this.isMoving = flag;
		},

		setIsFilesBlockDisplayed(flag: boolean = true): void
		{
			this.isFilesBlockDisplayed = flag;

			if (this.filesHtmlBlock)
			{
				void Runtime.html(this.$refs.files, this.filesHtmlBlock).then(() => {
					this.registerImages(this.$refs.files);
					BX.LazyLoad.showImages();
				});
			}
		},
	},

	watch: {
		text(newValue: String): void
		{
			this.value = newValue;
			this.oldValue = newValue;
			this.isTextChanged = true;

			this.$nextTick((): void => {
				this.isLongText = this.checkIsLongText();
				this.executeLoadAction(TYPE_LOAD_FILES_BLOCK, this.$refs.files);
			});
		},

		value(newValue: String): void
		{
			if (!this.isEdit)
			{
				return;
			}

			this.value = newValue;
			this.oldValue = newValue;
		},

		filesCount(newValue: Number): void
		{
			if (this.isMoving)
			{
				return;
			}

			this.isFilesBlockDisplayed = newValue > 0;

			this.$nextTick((): void => {
				this.executeLoadAction(TYPE_LOAD_FILES_BLOCK, this.$refs.files);
			});
		},
	},

	mounted() {
		this.createEditor();

		this.$nextTick((): void => {
			this.isLongText = this.checkIsLongText();
			this.executeLoadAction(TYPE_LOAD_FILES_BLOCK, this.$refs.files);
		});
	},

	updated() {
		this.createEditor();
	},

	template: `
		<div ref="rootWrapperElement" class="crm-timeline__editable-text_wrapper --comment">
			<div ref="rootElement" :class="className">
				<button
					v-if="isLongText && !isEdit && isEditable && isEditButtonVisible"
					:disabled="isSaving"
					@click="startEditing"
					class="crm-timeline__editable-text_edit-btn"
				>
					<i class="crm-timeline__editable-text_edit-icon"></i>
				</button>
				<div class="crm-timeline__editable-text_inner">
					<div :class="textWrapperClassName">
						<div
							v-if="isEdit"
							ref="editor"
							:disabled="!isEdit || isSaving"
							class="crm-timeline__editable-text_editor"
						></div>
						<span 
							v-else
							ref="text"
							class="crm-timeline__editable-text_text"
							v-html="value"
						>
						</span>
						<span
							v-if="!isEdit && !isLongText && isEditable && isEditButtonVisible"
							@click="startEditing"
							class="crm-timeline__editable-text_text-edit-icon"
						>
							<span class="crm-timeline__editable-text_edit-icon"></span>
						</span>
					</div>
					<div
						v-if="isEdit"
						class="crm-timeline__editable-text_actions"
					>
						<div class="crm-timeline__editable-text_action">
							<Button
								v-bind="saveTextButtonProps"
								@click="saveContent"
							/>
						</div>
						<div class="crm-timeline__editable-text_action">
							<Button
								v-bind="cancelEditingButtonProps"
								@click="cancelEditing"
							/>
						</div>
					</div>
				</div>
				<button
					v-if="isLongText && !isEdit"
					@click="toggleIsCollapsed"
					class="crm-timeline__editable-text_collapse-btn"
				>
					{{ expandButtonText }}
				</button>
			</div>
			<div
				v-if="!isEdit && isFilesBlockDisplayed"
				ref="files"
				class="crm-timeline__comment_files_wrapper"
				:class="{'--long-comment': isLongText}"
				v-html="filesHtmlBlock"
			>
			</div>
		</div>
	`,
});
