import {Text} from 'main.core';
import {Options as GridOptions} from '../options';
import Item from './item';
import Backend from "../backend";

export default class ItemRename extends Item
{
	buffExtension:string = '';

	constructor(trackedObjectId, itemData)
	{
		super(trackedObjectId, itemData);

		if (!this.data['onclick'])
		{
			this.data['onclick'] = this.rename.bind(this);
		}
	}

	cutExtension(name: string): string
	{
		this.buffExtension = '';
		if (name.lastIndexOf('.') > 0)
		{
			this.buffExtension = name.substr(name.lastIndexOf('.'));
			return name.substr(0, name.lastIndexOf('.'));
		}
		return name;
	}

	restoreExtension(name: string): string
	{
		name += this.buffExtension;
		this.buffExtension = '';
		return name;
	}

	rename()
	{
		const grid = BX.Main.gridManager
			.getInstanceById(GridOptions.getGridId());

		const row = grid
			.getRows()
			.getById(this.trackedObjectId);
		row.edit();

		const editorContainer = BX.Grid.Utils.getByClass(
			row.getNode(), 'main-grid-editor-container', true);
		const input = editorContainer.querySelector('input');

		if (input)
		{
			input.value = this.cutExtension(input.value);
			const onBlur = function(event) {
				onBeforeSend(event);
			}.bind(this);

			const onBeforeSend = (event) => {
				event.stopPropagation();
				event.preventDefault();
				const fullName = this.restoreExtension(input.value);
				Backend
					.renameAction(this.trackedObjectId, fullName)
					.then(({data: {object: {name}}}) => {
						if (fullName !== name)
						{
							row.getNode().querySelector('#disk_obj_' + this.trackedObjectId).innerHTML = Text.encode(name);
							row.editData['NAME'] = name;
						}
					});

				input.removeEventListener('blur', onBlur);
				row.getNode().querySelector('#disk_obj_' + this.trackedObjectId).innerHTML = Text.encode(fullName);
				row.editData['NAME'] = fullName;

				row.editCancel();
			};

			input.addEventListener('keydown', function(event) {
				if(event.key === 'Enter')
				{
					onBeforeSend(event);
				}
				else if(event.key === 'Escape')
				{
					input.removeEventListener('blur', onBlur);
					row.editCancel();
				}
			}.bind(this));
			input.addEventListener('blur', onBlur);
			BX.focus(input);
		}
	}

	static detect(itemData)
	{
		return itemData['id'] === 'rename';
	}
}

