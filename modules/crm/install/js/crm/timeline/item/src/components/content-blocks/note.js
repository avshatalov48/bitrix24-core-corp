import {User} from '../layout/header/user';
import {Action} from '../../action';
import {Button} from '../layout/button';
import {ButtonState} from '../enums/button-state';
import {ButtonType} from '../enums/button-type';
import {Browser, Runtime, Event} from 'main.core';
import {MessageBox, MessageBoxButtons} from "ui.dialogs.messagebox";
import {TextCrop} from 'ui.textcrop';

export const Note = {
	components: {
		User,
		Button,
	},
	props: {
		id: {
			type: Number,
			required: false,
		},
		text: {
			type: String,
			required: false,
			default: '',
		},
		deleteConfirmationText: {
			type: String,
			required: false,
			default: '',
		},
		saveNoteAction: {
			type: Object,
		},
		deleteNoteAction: {
			type: Object,
		},
		updatedBy: {
			type: Object,
			required: false,
		},
	},
	data()
	{
		return {
			note: this.text,
			oldNote: this.text,
			isEdit: false,
			isExist: !!this.id,
			isSaving: false,
			isDeleting: false,
			isCollapsed: true,
			shortNoteLength: 113,
		}
	},
	inject: ['isReadOnly', 'currentUser'],
	computed: {
		noteText() {
			if (this.isCollapsed)
			{
				return this.shortNote;
			}

			return this.note;
		},

		shortNote() {
			if (this.note.length > this.shortNoteLength)
			{
				return `${this.note.slice(0, this.shortNoteLength)}...`;
			}
			else if (this.getNoteLineBreaksCount() > 2) {
				let currentLineBreakerCount = 0;
				for (let letterIndex = 0; letterIndex < this.note.length; letterIndex++)
				{
					const letter = this.note[letterIndex];
					if (letter !== '\n')
					{
						continue;
					}

					currentLineBreakerCount++;

					if (currentLineBreakerCount === this.maxLineBreakerCount)
					{
						return `${this.note.slice(0, letterIndex)}...`;
					}
				}
			}

			return this.note;
		},

		maxLineBreakerCount()
		{
			return 3;
		},

		expandNoteBtnText() {
			if (this.isCollapsed)
			{
				return this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_NOTE_SHOW');
			}
			else
			{
				return this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_NOTE_HIDE');
			}
		},
		ButtonType()
		{
			return ButtonType;
		},

		isDeleteButtonVisible()
		{
			return !(this.isReadOnly);
		},

		isEditButtonVisible()
		{
			return !(this.isReadOnly || this.isEdit);
		},

		saveButtonState()
		{
			if (this.isSaving)
			{
				return ButtonState.DISABLED;
			}

			if (this.note.trim().length > 0)
			{
				return ButtonState.DEFAULT;
			}

			return ButtonState.DISABLED;
		},

		cancelButtonState()
		{
			if (this.isSaving)
			{
				return ButtonState.DISABLED;
			}

			return ButtonState.DEFAULT;
		},

		isNoteVisible()
		{
			return this.isExist || this.isEdit;
		},

		user() {
			if (this.updatedBy)
			{
				return this.updatedBy;
			}
			if (this.currentUser)
			{
				return this.currentUser;
			}
			return {
				title: '',
				detailUrl: '',
				imageUrl: '',
			};
		},

		isShowExpandBtn() {
			return !this.isEdit && (this.note.length >  this.shortNoteLength || this.getNoteLineBreaksCount() > 2);
		}
	},
	methods: {
		toggleNoteLength() {
			this.isCollapsed = !this.isCollapsed;
		},

		startEditing()
		{
			this.isEdit = true;
			this.$nextTick(() => {
				this.isCollapsed = false;
				const textarea = this.$refs.noteText;
				this.adjustHeight(textarea);
				textarea.focus();
			});

			this.executeAction({type: 'jsEvent', value: 'Note:StartEdit'});
		},

		adjustHeight(elem) {
			elem.style.height = 0;
			elem.style.height = (elem.scrollHeight)+"px";
		},

		setEditMode(editMode: boolean)
		{
			const isEdit = editMode ? !this.isReadOnly : false;
			if (isEdit !== this.isEdit)
			{
				if(isEdit)
				{
					this.startEditing();
				}
				else
				{
					this.isEdit = false;
					this.executeAction({type: 'jsEvent', value: 'Note:FinishEdit'})
				}
			}
		},

		onEnterHandle(event)
		{
			if (
				event.ctrlKey === true ||
				( Browser.isMac() && (event.metaKey === true || event.altKey === true) )
			)
			{
				this.saveNote();
			}
		},

		cancelEditing()
		{
			this.note = this.oldNote;
			this.isEdit = false;
			this.executeAction({type: 'jsEvent', value: 'Note:FinishEdit'});
		},

		deleteNote()
		{
			if (this.isSaving)
			{
				return;
			}

			if (!this.isExist)
			{
				this.cancelEditing();
				return;
			}

			if (this.deleteConfirmationText && this.deleteConfirmationText.length)
			{
				MessageBox.show({
					message: this.deleteConfirmationText,
					modal: true,
					buttons: MessageBoxButtons.YES_NO,
					onYes: (messageBox) => {
						messageBox.close();
						this.executeDeleteAction();
					},
					onNo: (messageBox) => {
						messageBox.close();
					},
				});
			}
			else
			{
				this.executeDeleteAction();
			}
		},

		saveNote()
		{
			if (this.saveButtonState === ButtonState.DISABLED || this.isSaving || this.isDeleting)
			{
				return;
			}
			if (this.note === this.text)
			{
				this.cancelEditing();
				return;
			}

			this.isSaving = true;
			const action = Runtime.clone(this.saveNoteAction);
			action.actionParams.text = this.note;

			this.executeAction(action).then(({status}) =>
			{
				if (status === 'success')
				{
					this.oldNote = this.$refs.noteText.value.trim();
					this.isExist = true;
					this.cancelEditing();
				}
			}).finally(() =>
			{
				this.isSaving = false;
			});
		},

		executeDeleteAction()
		{
			if (this.isSaving)
			{
				return;
			}

			this.isDeleting = true;

			this.executeAction(this.deleteNoteAction).then(({status}) =>
			{
				if (status === 'success')
				{
					this.oldNote = '';
					this.isExist = false;
					this.cancelEditing();
				}
			}).finally(() =>
			{
				this.isDeleting = false;
			});
		},

		executeAction(actionObject): Promise
		{
			if (!actionObject)
			{
				console.error('No action object to execute');
				return;
			}

			const action = new Action(actionObject);
			return action.execute(this);
		},

		handleWindowResize() {
			const windowWidth = window.innerWidth;

			if (windowWidth > 1400)
			{
				this.shortNoteLength = 250;
			}
			else
			{
				this.shortNoteLength = 113;
			}
		},

		getNoteLineBreaksCount() {
			return this.note.split('').reduce((counter, elem) => {
				return counter + (elem === '\n' ? 1 : 0);
			}, 0);
		}
	},
	watch: {
		id(id)
		{
			this.isExist = !!id;
		},

		text(text)
		{
			this.note = text;
			this.oldNote = text;
		},

		note() {
			if (!this.isEdit)
			{
				return;
			}

			this.$nextTick(() => {
				this.adjustHeight(this.$refs.noteText);
			});
		},

		isEdit(value)
		{
			if (value)
			{
				this.$nextTick(() => this.$refs.noteText.focus());
			}
		}
	},

	created() {
		this.handleWindowResize();
		Event.bind(window, 'resize', this.handleWindowResize);
	},

	destroyed() {
		Event.unbind(window, 'resize', this.handleWindowResize);
	},

	template: `
		<div
			v-show="isNoteVisible"
			class="crm-timeline__card-note"
		>
			<div class="crm-timeline__card-note_user">
				<User v-bind="user"></User>
			</div>
			<div class="crm-timeline__card-note_area">
				<div class="crm-timeline__card-note_value">
						<textarea
							v-if="isEdit"
							v-model="note"
							@keydown.esc.stop="cancelEditing"
							@keydown.enter="onEnterHandle"
							:disabled="!isEdit || isSaving"
							:placeholder="$Bitrix.Loc.getMessage('CRM_TIMELINE_USER_NOTE_PLACEHOLDER')"
							ref="noteText"
							class="crm-timeline__card-note_text"
						></textarea>
						<span
							v-else
							ref="noteText"
							class="crm-timeline__card-note_text"
						>
							{{noteText}}
						</span>
	
					<span
						v-if="isEditButtonVisible"
						class="crm-timeline__card-note_edit"
						@click.prevent.stop="startEditing"
					>
							<i></i>
						</span>
				</div>
				<div v-if="isEdit" class="crm-timeline__card-note__controls">
					<div class="crm-timeline__card-note__control --save">
						<Button
							@click="saveNote"
							:state="saveButtonState" :type="ButtonType.PRIMARY"
							:title="$Bitrix.Loc.getMessage('CRM_TIMELINE_USER_NOTE_SAVE')"
						/>
					</div>
					<div class="crm-timeline__card-note__control --cancel">
						<Button @click="cancelEditing"
								:type="ButtonType.LIGHT"
								:state="cancelButtonState"
								:title="$Bitrix.Loc.getMessage('CRM_TIMELINE_USER_NOTE_CANCEL')"
						/>
					</div>
				</div>
			</div>
			<div v-if="isDeleteButtonVisible" class="crm-timeline__card-note_cross" @click="deleteNote">
				<i></i>
			</div>
			<div v-if="isDeleting" class="crm-timeline__card-note_dimmer"></div>
			<div
				v-show="isShowExpandBtn"
				@click="toggleNoteLength"
				class="crm-timeline__card-note_expand-note-btn"
			>
				{{ expandNoteBtnText }}
			</div>
		</div>
	`
};
