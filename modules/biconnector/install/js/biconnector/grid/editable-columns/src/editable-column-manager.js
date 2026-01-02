import { ajax as Ajax, Dom, Type, Event, Tag, Text } from 'main.core';
import './css/editable-columns.css';

type Field = {
	name: string,
	saveEndpoint: string,
	onValueCheck: (newValue: string) => boolean,
	onSave: (rowId: any, newValue: string) => boolean,
}

export class EditableColumnManager
{
	static instances = new Map();

	constructor(gridId, field: Field)
	{
		this.gridId = gridId;
		this.grid = BX.Main.gridManager.getById(gridId)?.instance;

		this.columnId = field.name;
		this.saveEndpoint = field.saveEndpoint;
		this.onSave = field.onSave;
		this.onValueCheck = field.onValueCheck;

		if (!EditableColumnManager.instances.has(field.name))
		{
			EditableColumnManager.instances.set(field.name, this);
		}
	}

	static init(gridId: string, fields: Field[]): void
	{
		fields.forEach((field) => {
			new EditableColumnManager(gridId, field);
		});
	}

	static editCell(columnId: string, rowId: string)
	{
		const instance = EditableColumnManager.instances.get(columnId);
		if (!instance)
		{
			return;
		}
		instance.#editCell(rowId);
	}

	#editCell(rowId: string)
	{
		const row = this.grid.getRows().getById(rowId);
		if (!row)
		{
			return;
		}

		Dom.removeClass(row.getNode(), 'editable-column-edited');
		const cell = row.getCellById(this.columnId);
		let currentValue = row.getEditData()[this.columnId];
		if (!currentValue)
		{
			currentValue = '';
		}
		const wrapper = cell.querySelector('.editable-column-wrapper');
		if (!wrapper)
		{
			return;
		}

		const editor = this.#buildEditor(rowId, currentValue);

		const preview = wrapper.querySelector('.editable-column-preview');
		if (preview)
		{
			Dom.style(preview, {
				display: 'none',
			});
		}
		Dom.append(editor, wrapper);

		const actionsClickHandler = () => {
			Event.unbind(row.getActionsButton(), 'click', actionsClickHandler);

			this.#cancelEdit(rowId);
		};

		Event.bind(row.getActionsButton(), 'click', actionsClickHandler);
	}

	#buildEditor(rowId, value): HTMLElement
	{
		const input = Tag.render`<input class="main-grid-editor main-grid-editor-text" type="text">`;
		input.value = value;

		const applyButton = Tag.render`
			<a>
				<i class="ui-icon-set --check"></i>
			</a>
		`;

		const cancelButton = Tag.render`
			<a>
				<i class="ui-icon-set --cross-60"></i>
			</a>
		`;

		const onSave = (newValue) => {
			Dom.removeClass(input, 'editable-column-input-danger');

			if (!this.onValueCheck(newValue))
			{
				Dom.addClass(input, 'editable-column-input-danger');

				return;
			}

			this.#saveValue(rowId, newValue);
		};

		const onCancel = () => {
			this.#cancelEdit(rowId);
		};

		Event.bind(applyButton, 'click', (event) => {
			event.preventDefault();
			onSave(input.value);
		});
		Event.bind(cancelButton, 'click', (event) => {
			event.preventDefault();
			onCancel();
		});

		Event.bind(input, 'keydown', (event) => {
			if (event.keyCode === 13)
			{
				onSave(input.value);
				event.preventDefault();
			}
			else if (event.keyCode === 27)
			{
				onCancel();
				event.preventDefault();
			}
		});

		return Tag.render`
			<div class="editable-column-wrapper__item editable-column-edit">
				${input}
				<div class="editable-column-wrapper__buttons-wrapper">
					<div class="editable-column-wrapper__buttons">
						${applyButton}
						${cancelButton}
					</div>
				</div>
			</div>
		`;
	}

	#cancelEdit(rowId: Object)
	{
		const row = this.grid.getRows().getById(rowId);
		const cell = row.getCellById(this.columnId);
		const wrapper = cell.querySelector('.editable-column-wrapper');
		const editor = wrapper.querySelector('.editable-column-edit');
		const preview = wrapper.querySelector('.editable-column-preview');

		if (editor)
		{
			editor.remove();
		}

		if (preview)
		{
			Dom.style(preview, {
				display: 'flex',
			});
		}
	}

	#saveValue(rowId: string, newValue)
	{
		Ajax.runAction(this.saveEndpoint, {
			data: {
				id: rowId,
				columnId: this.columnId,
				value: newValue,
			},
		}).then(() => {
			this.onSave(rowId, newValue);

			const row = this.grid.getRows().getById(rowId);
			const cell = row.getCellById(this.columnId);
			const wrapper = cell.querySelector('.editable-column-wrapper');
			const content = wrapper.querySelector('.editable-column-content');

			Dom.addClass(row.getNode(), 'editable-column-edited');

			const rowEditData = row.getEditData();
			rowEditData[this.columnId] = newValue;
			const editableData = this.grid.getParam('EDITABLE_DATA');
			if (Type.isPlainObject(editableData))
			{
				editableData[row.getId()] = rowEditData;
			}

			if (content)
			{
				content.innerText = newValue;
				Dom.style(content, {
					display: 'flex',
				});
			}
			this.#cancelEdit(rowId);
		}).catch((response) => {
			if (response.errors)
			{
				this.#notifyErrors(response.errors);
			}
		});
	}

	#notifyErrors(errors: Array): void
	{
		if (errors[0] && errors[0].message)
		{
			BX.UI.Notification.Center.notify({
				content: Text.encode(errors[0].message),
			});
		}
	}
}
