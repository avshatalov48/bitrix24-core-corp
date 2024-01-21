import Item from "../item";
import { Dom, Loc, Tag, Type } from "main.core";
import { BaseEvent } from "main.core.events";
import { TodoEditor } from "crm.activity.todo-editor";

/** @memberof BX.Crm.Timeline.MenuBar */
export default class ToDo extends Item
{
	#toDoEditor = null;
	#todoEditorContainer: HTMLElement = null;
	#saveButton: HTMLElement = null;

	createLayout(): HTMLElement
	{
		this.#todoEditorContainer = Tag.render`<div></div>`;

		this.#saveButton = Tag.render`<button onclick="${this.onSaveButtonClick.bind(this)}" class="ui-btn ui-btn-xs ui-btn-primary ui-btn-round ui-btn-disabled" >${Loc.getMessage('CRM_TIMELINE_SAVE_BUTTON')}</button>`;

		return Tag.render`<div class="crm-entity-stream-content-new-detail crm-entity-stream-content-new-detail-todo --hidden">
			${this.#todoEditorContainer}
			<div class="crm-entity-stream-content-new-comment-btn-container">
				${this.#saveButton}
				<span onclick="${this.onCancelButtonClick.bind(this)}"  class="ui-btn ui-btn-xs ui-btn-link">${Loc.getMessage('CRM_TIMELINE_CANCEL_BTN')}</span>
			</div>
		</div>`;
	}

	initializeLayout(): void
	{
		this.#createEditor();
	}

	onSaveButtonClick(e)
	{
		if (
			this.isLocked()
			|| Dom.hasClass(this.#saveButton, 'ui-btn-disabled')
		)
		{
			return;
		}
		this.setLocked(true);

		this.save().then(
			() => this.setLocked(false),
			() => this.setLocked(false),
		).catch(() => this.setLocked(false));
	}

	onCancelButtonClick()
	{
		this.cancel();
		this.emitFinishEditEvent();
	}
	#createEditor(): void
	{
		this.#toDoEditor = new TodoEditor({
			container: this.#todoEditorContainer,
			defaultDescription: '',
			ownerTypeId: this.getEntityTypeId(),
			ownerId: this.getEntityId(),
			currentUser: this.getSetting('currentUser'),
			pingSettings: this.getSetting('pingSettings'),
			events: {
				onFocus: this.setFocused.bind(this, true),
				onChangeDescription: this.#onChangeDescription.bind(this),
			},
			enableCalendarSync: this.getSetting('enableTodoCalendarSync', false),
		});

		this.#toDoEditor.show();
	}

	save(): Promise
	{
		if (Dom.hasClass(this.#saveButton, 'ui-btn-disabled'))
		{
			return false;
		}
		return this.#toDoEditor.save().then((response) => {
			if (Type.isArray(response.errors) && response.errors.length)
			{
				return false;
			}
			this.cancel();
			this.emitFinishEditEvent();

			return true;
		});
	}

	cancel(): void
	{
		this.#toDoEditor.clearValue();
		Dom.addClass(this.#saveButton, 'ui-btn-disabled');
		this.setFocused(false);
	}

	bindInputHandlers(): void
	{
		// do nothing
	}

	setParentActivityId(activityId: Number): void
	{
		this.#toDoEditor.setParentActivityId(activityId);
	}

	setDeadLine(deadLine: String): void
	{
		this.#toDoEditor.setDeadline(deadLine);
	}

	focus(): void
	{
		this.#toDoEditor.setFocused();
	}

	#onChangeDescription(event: BaseEvent): void
	{
		let {description} = event.getData();
		description = description.trim();

		if (!description.length && !Dom.hasClass(this.#saveButton, 'ui-btn-disabled'))
		{
			Dom.addClass(this.#saveButton, 'ui-btn-disabled');
		}
		else if (description.length && Dom.hasClass(this.#saveButton, 'ui-btn-disabled'))
		{
			Dom.removeClass(this.#saveButton, 'ui-btn-disabled')
		}
	}
}
