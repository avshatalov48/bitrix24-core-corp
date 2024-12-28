import {Dialog} from "ui.entity-selector";
import {EventEmitter} from "main.core.events";

export class GroupSelector extends EventEmitter
{
	#userEntities = [
		{
			id: 'user',
			options: {
				emailUsers: true,
				inviteGuestLink: true,
			},
			filters: [
				{
					id: 'tasks.distributedUserDataFilter',
				},
			],
		},
		{
			id: 'department',
			options: {
				selectMode: 'usersOnly',
			},
		},
	];
	#projectEntities = [
		{
			id: 'project',
			filters: [
				{
					id: 'tasks.projectDataFilter',
				},
			],
		},
	];
	#entitiesByMode = {
		['user']: this.#userEntities,
		['group']: this.#projectEntities,
		['project']: this.#projectEntities,
		['all']: this.#userEntities.concat(this.#projectEntities),
	};

	#mode = 'user';
	#targetNodeId = null;
	#showAvatars = false;
	#enableSearch = false;
	#multiple = false;
	#context = 'TASKS';
	#dialog = null;

	constructor(data)
	{
		super(data);
		this.setEventNamespace('BX.Tasks.GroupSelector');

		this.#mode = data.mode;
		this.#targetNodeId = data.targetNodeId;
		this.#showAvatars = data.showAvatars;
		this.#enableSearch = data.enableSearch;
		this.#multiple = data.multiple;
		this.#context = data.context;
	}

	getDialog()
	{
		if (!this.#dialog)
		{
			this.#dialog = new Dialog({
				targetNode: document.getElementById(this.#targetNodeId),
				showAvatars: this.#showAvatars,
				enableSearch: this.#enableSearch,
				multiple: this.#multiple,
				context: this.#context,
				entities: this.#entitiesByMode[this.#mode],
				events: {
					'Item:onSelect': (event) => this.onItemSelect(event),
				},
			});
		}

		return this.#dialog;
	}

	show()
	{
		if(this.#dialog === null)
		{
			this.getDialog();
		}

		this.#dialog.show();
	}

	onItemSelect(event)
	{
		this.emit('itemSelected', event.getData());
	}
}
