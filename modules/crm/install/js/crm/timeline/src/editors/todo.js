import Editor from "../editor.js";
import {Dom, Type} from "main.core";
import {TodoEditor} from "crm.activity.todo-editor";
import {BaseEvent} from "main.core.events";

/** @memberof BX.Crm.Timeline.Editors */
export default class ToDo extends Editor
{
	#toDoEditor = null;

	initialize(id, settings): void
	{
		super.initialize(id, settings);
		this.#createEditor();
	}

	#createEditor(): void
	{
		const editorContainer = Dom.create('div');
		Dom.prepend(editorContainer, this._container);
		this.#toDoEditor = new TodoEditor({
			container: editorContainer,
			defaultDescription: '',
			ownerTypeId: this._ownerTypeId,
			ownerId: this._ownerId,
			events: {
				onFocus: this._focusHandler,
				onChangeDescription: this.#onChangeDescription.bind(this),
			},
			enableCalendarSync: this._settings.enableTodoCalendarSync || false,
		});

		this.#toDoEditor.show();
	}

	save(): Promise
	{
		if (Dom.hasClass(this._saveButton, 'ui-btn-disabled'))
		{
			return false;
		}
		return this.#toDoEditor.save().then((response) => {
			if (Type.isArray(response.errors) && response.errors.length)
			{
				return false;
			}
			this.cancel();
			this._manager.processEditingCompletion(this);

			return true;
		});
	}

	cancel(): void
	{
		this.#toDoEditor.clearValue();
		Dom.removeClass(this._container, 'focus');
		this.release();
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
		this.#toDoEditor.setDeadLine(deadLine);
	}

	setFocused(): void
	{
		this.#toDoEditor.setFocused();
	}

	#onChangeDescription(event: BaseEvent): void
	{
		const {description} = event.getData();

		if (!description.length && !Dom.hasClass(this._saveButton, 'ui-btn-disabled'))
		{
			Dom.addClass(this._saveButton, 'ui-btn-disabled');
		}
		else if (description.length && Dom.hasClass(this._saveButton, 'ui-btn-disabled'))
		{
			Dom.removeClass(this._saveButton, 'ui-btn-disabled')
		}
	}

	static create(id, settings): ToDo
	{
		const self = new ToDo();
		self.initialize(id, settings);
		ToDo.items[self.getId()] = self;
		return self;
	}

	static items = {};
}
