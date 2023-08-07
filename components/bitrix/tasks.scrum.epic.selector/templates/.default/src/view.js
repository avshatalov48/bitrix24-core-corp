import {Loc, Text, Tag, Dom, Type, Event, ajax} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {Dialog} from 'ui.entity-selector';

type Params = {
	groupId: number,
	taskId: number,
	epic: ?Epic,
	canEdit: boolean
}

type Epic = {
	id: number,
	groupId: number,
	name: string,
	description: string,
	createdBy: number,
	modifiedBy: number,
	color: string
}

type ErrorResponse = {
	data: string,
	errors: Array,
	status: string
}

export class ViewSelector
{
	constructor(params: Params)
	{
		this.groupId = params.groupId;
		this.taskId = params.taskId;
		this.epic = params.epic;
		this.canEdit = params.canEdit;

		this.dialog = null;

		this.node = null;
		this.nameNode = null;
		this.selectorNode = null;

		EventEmitter.subscribe('onChangeProjectLink', this.onChangeTaskProject.bind(this));
	}

	renderTo(container: HTMLElement)
	{
		Dom.append(this.render(), container);
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div>
				${this.renderName()}
				${this.renderSelector()}
			</div>
		`;

		return this.node;
	}

	onChangeTaskProject(baseEvent: BaseEvent): void
	{
		const [groupId, taskId] = baseEvent.getCompatData();

		this.groupId = parseInt(groupId, 10);
		this.taskId = parseInt(taskId, 10);
		this.epic = null;

		this.dialog = null;

		this.updateSelector(null);
	}

	renderName(): ?HTMLElement
	{
		if (Type.isNull(this.epic))
		{
			return '';
		}

		const colorBorder = this.convertHexToRGBA(this.epic.color, 0.7);
		const colorBackground = this.convertHexToRGBA(this.epic.color, 0.3);

		this.nameNode = Tag.render`
			<div
				class="tasks-scrum__epic-selector--epic"
				style="background: ${colorBackground}; border-color: ${colorBorder};"
			>
				${Text.encode(this.epic.name)}
			</div>
		`;

		return this.nameNode;
	}

	renderSelector(): HTMLElement
	{
		this.selectorNode = Tag.render`
			<div>
				<div class="ui-btn-link tasks-scrum__epic-selector--link">
					${this.getButtonText()}
				</div>
			</div>
		`;

		const buttonNode = this.selectorNode.firstElementChild;

		Event.bind(buttonNode, 'click', this.onClick.bind(this, buttonNode));

		return this.selectorNode;
	}

	onClick(buttonNode: HTMLElement)
	{
		if (this.dialog)
		{
			if (this.dialog.isOpen())
			{
				this.dialog.hide();
			}
			else
			{
				this.dialog.show();
			}

			return;
		}

		const preselectedItems = [];
		if (!Type.isNull(this.epic))
		{
			preselectedItems.push(['epic-selector' , this.epic.id]);
		}

		this.dialog = new Dialog({
			id: Text.getRandom(),
			targetNode: this.selectorNode,
			width: 350,
			height: 300,
			multiple: false,
			dropdownMode: true,
			enableSearch: true,
			compactView: true,
			hideOnDeselect: true,
			context: 'epic-selector-' + this.groupId,
			preselectedItems: preselectedItems,
			entities: [
				{
					id: 'epic-selector',
					options: {
						groupId: this.groupId
					},
					dynamicLoad: true,
					dynamicSearch: true
				}
			],
			searchOptions: {
				allowCreateItem: true,
				footerOptions: {
					label: Loc.getMessage('TSE_SELECTOR_SEARCHER_EPIC_ADD')
				}
			},
			events: {
				'Search:onItemCreateAsync': (event) => {
					return new Promise((resolve) => {
						const { searchQuery } = event.getData();
						this.createEpic(searchQuery.getQuery())
							.then((epic: Epic) => {
								const epicDialogItem = this.getEpicDialogItem(epic);
								epicDialogItem.selected = true;
								epicDialogItem.sort = 1;
								this.dialog.addItem(epicDialogItem);
								this.dialog.hide();
								resolve();
							})
						;
					});
				}
			},
			tagSelectorOptions: {
				textBoxWidth: 300
			}
		});

		this.dialog.subscribe('onHide', () => {
			const selectedItems = this.dialog.getSelectedItems();
			const epicId = selectedItems.length ? selectedItems[0].getId() : 0;
			this.changeTaskEpic(epicId).then((epic: ?Epic) => this.updateSelector(epic));
		});

		this.dialog.show();
	}

	updateSelector(epic: ?Epic)
	{
		this.epic = epic;

		this.selectorNode.firstElementChild.textContent = this.getButtonText();

		if (Type.isNull(epic))
		{
			Dom.remove(this.nameNode);

			this.nameNode = null;
		}
		else
		{
			if (Type.isNull(this.nameNode))
			{
				Dom.insertBefore(this.renderName(), this.selectorNode);
			}
			else
			{
				Dom.replace(this.nameNode, this.renderName());
			}
		}
	}

	getEpicDialogItem(epic: Epic): Object
	{
		return {
			id: epic.id,
			entityId: 'epic-selector',
			title: epic.name,
			tabs: 'recents',
			avatarOptions: {
				bgColor: epic.color,
				bgImage: 'none'
			}
		};
	}

	changeTaskEpic(epicId: number): Promise
	{
		return ajax.runComponentAction(
			'bitrix:tasks.scrum.epic.selector',
			'changeTaskEpic',
			{
				mode: 'class',
				data: {
					taskId: this.taskId,
					epicId: epicId
				}
			}
		)
			.then((response) => {
				return response.data;
			})
			.catch((response) => this.showErrorAlert(response))
		;
	}

	createEpic(epicName: string): Promise
	{
		return ajax.runAction(
			'bitrix:tasks.scrum.epic.createEpic',
			{
				data: {
					groupId: this.groupId,
					name: epicName
				}
			}
		)
			.then((response) => {
				return response.data;
			})
			.catch((response) => this.showErrorAlert(response))
		;
	}

	getButtonText(): string
	{
		if (Type.isNull(this.epic))
		{
			return Loc.getMessage('TSE_SELECTOR_ADD');
		}
		else
		{
			return Loc.getMessage('TSE_SELECTOR_EDIT');
		}
	}

	convertHexToRGBA(hexCode, opacity): string
	{
		let hex = hexCode.replace('#', '');

		if (hex.length === 3)
		{
			hex = `${hex[0]}${hex[0]}${hex[1]}${hex[1]}${hex[2]}${hex[2]}`;
		}

		const r = parseInt(hex.substring(0, 2), 16);
		const g = parseInt(hex.substring(2, 4), 16);
		const b = parseInt(hex.substring(4, 6), 16);

		return `rgba(${r},${g},${b},${opacity})`;
	}

	showErrorAlert(response: ErrorResponse, alertTitle?: string)
	{
		if (Type.isUndefined(response.errors))
		{
			return;
		}

		if (response.errors.length)
		{
			const firstError = response.errors.shift();
			if (firstError)
			{
				const errorCode = (firstError.code ? firstError.code : '');

				const message = firstError.message + ' ' + errorCode;
				const title = (alertTitle ? alertTitle : Loc.getMessage('TSE_SELECTOR_ERROR_POPUP_TITLE'));

				top.BX.UI.Dialogs.MessageBox.alert(message, title);
			}
		}
	}
}