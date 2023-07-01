import {Loc, Text, Tag, Dom, Type, ajax} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {TagSelector} from 'ui.entity-selector';

type Params = {
	groupId: number,
	taskId: number,
	epic: ?Epic,
	inputName: string
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

export class EditSelector
{
	constructor(params: Params)
	{
		this.groupId = params.groupId;
		this.taskId = params.taskId;
		this.savedEpic = params.epic;
		this.inputName = params.inputName;

		this.selector = null;

		this.node = null;
		this.inputNode = null;

		EventEmitter.subscribe('BX.Tasks.MemberSelector:projectSelected', this.onProjectSelected.bind(this));
		EventEmitter.subscribe('BX.Tasks.Component.Task:projectPreselected', this.onProjectPreselected.bind(this));
	}

	renderTo(container: HTMLElement)
	{
		this.node = container;

		Dom.addClass(this.node, 'tasks-scrum-epic-edit-selector');

		if (this.inputName)
		{
			Dom.append(this.renderInput(), this.node);
		}

		this.buildSelector().renderTo(this.node);
	}

	renderInput(): HTMLElement
	{
		const value = this.savedEpic ? parseInt(this.savedEpic.id, 10) : 0;

		this.inputNode = Tag.render`
			<input type="hidden" name="${Text.encode(this.inputName)}" value="${value}">
		`;

		return this.inputNode;
	}

	buildSelector(): TagSelector
	{
		const preselectedItems = [];
		if (this.savedEpic)
		{
			preselectedItems.push(['epic-selector' , this.savedEpic.id]);

			this.updateInputValue(this.savedEpic.id);
		}

		this.selector = new TagSelector({
			multiple: false,
			textBoxWidth: 200,
			dialogOptions: {
				width: 350,
				height: 240,
				dropdownMode: true,
				compactView: true,
				multiple: false,
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
				items: [],
				events: {
					'Search:onItemCreateAsync': (event) => {
						return new Promise((resolve) => {
							const { searchQuery } = event.getData();
							const dialog = event.getTarget();
							this.createEpic(searchQuery.getQuery())
								.then((epic: Epic) => {
									const epicDialogItem = this.getEpicDialogItem(epic);
									epicDialogItem.selected = true;
									epicDialogItem.sort = 1;
									dialog.addItem(epicDialogItem);
									this.updateInputValue(epicDialogItem.id);
									resolve();
								})
							;
						});
					},
					'Item:onSelect': (event) => {
						const selectedItem = event.getData().item;
						this.updateInputValue(selectedItem.getId());
					},
					'Item:onDeselect': (event) => {
						const dialog = event.getTarget();
						setTimeout(() => {
							if (dialog.getSelectedItems().length === 0)
							{
								this.updateInputValue(0);
							}
						}, 50);
					},
				}
			}
		});

		this.selector
			.subscribe('onMetaEnter', (baseEvent: BaseEvent) => {
				const tagSelector: TagSelector = baseEvent.getTarget();
				if (tagSelector.getDialog().isOpen())
				{
					const { event: keyboardEvent } = baseEvent.getData();

					keyboardEvent.stopPropagation();
				}
			})
		;

		return this.selector;
	}

	onProjectSelected(baseEvent: BaseEvent)
	{
		const data = baseEvent.getData();

		this.groupId = parseInt(data.ID, 10);

		this.updateInputValue(0);

		this.savedEpic = null;

		Dom.clean(this.node);

		this.renderTo(this.node);
	}

	onProjectPreselected(baseEvent: BaseEvent)
	{
		const data = baseEvent.getData();

		this.groupId = parseInt(data.groupId, 10);

		Dom.clean(this.node);

		this.renderTo(this.node);
	}

	updateInputValue(epicId: number)
	{
		this.inputNode.value = parseInt(epicId, 10);
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
				bgImage: 'none',
				borderRadius: '12px'
			}
		};
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