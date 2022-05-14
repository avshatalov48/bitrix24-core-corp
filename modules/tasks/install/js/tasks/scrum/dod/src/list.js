import {Loc, Tag, Text, Type, Event, Dom} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Loader} from 'main.loader';

import {MessageBox} from 'ui.dialogs.messagebox';

import {Button} from 'ui.buttons';

import {RequestSender} from './request.sender';
import {TypeStorage} from './type.storage';
import {Tabs} from './tabs';
import {ItemType} from './item.type';

import type {ItemTypeParams} from './item.type';

type Params = {
	requestSender: RequestSender,
	groupId: number,
	taskId?: number,
	skipNotifications?: boolean
}

type SettingsResponse = {
	data: {
		types: Array<ItemTypeParams>,
		activeTypeId: number
	}
}

export class List extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Dod.List');

		this.requestSender = params.requestSender;

		this.groupId = parseInt(params.groupId, 10);
		this.taskId = parseInt(params.taskId, 10);

		this.skipNotifications = Type.isBoolean(params.skipNotifications) ? params.skipNotifications : false;

		this.typeStorage = new TypeStorage();
		this.tabs = new Tabs();

		this.empty = true;
		this.activeTypeData = null;

		this.node = null;
	}

	renderContent(): Promise
	{
		return this.requestSender.getSettings({
			groupId: this.groupId,
			taskId: this.taskId,
			saveRequest: this.isSkipNotifications() ? 'Y' : 'N'
		})
			.then((response: SettingsResponse) => {
				const types = Type.isArray(response.data.types) ? response.data.types : [];
				const activeTypeId = (
					Type.isInteger(response.data.activeTypeId)
						? parseInt(response.data.activeTypeId, 10)
						: 0
				);

				this.empty = (types.length === 0);

				const itemTypes = new Map();

				types.forEach((typeData: ItemTypeParams) => {
					const itemType = new ItemType(typeData);
					itemTypes.set(itemType.getId(), itemType);
				});

				this.typeStorage.setTypes(itemTypes);

				this.tabs.setTypeStorage(this.typeStorage);

				const activeType = itemTypes.get(activeTypeId);
				if (Type.isUndefined(activeType))
				{
					this.tabs.setActiveType(this.typeStorage.getNextType());
				}
				else
				{
					this.tabs.setActiveType(activeType);
				}

				if (this.isEmpty())
				{
					if (!this.isSkipNotifications())
					{
						this.emit('resolve');
					}

					return this.renderEmpty();
				}

				return this.render();
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	renderList()
	{
		const listNode = this.node.querySelector('.tasks-scrum-dod-checklist');

		Dom.clean(listNode);

		const loader = this.showLoader(listNode);

		this.requestSender.getList({
			groupId: this.groupId,
			taskId: this.taskId,
			typeId: this.getActiveType().getId()
		})
			.then((response) => {
				loader.hide();
				top.BX.Runtime.html(listNode, response.data.html);
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	renderEmpty(): HTMLElement
	{
		const node = Tag.render`
				<div class="ui-form ui-form-line tasks-scrum-dod-form">
					<div class="ui-form-row">
						<div class="ui-form-label">
							<div class="ui-ctl-label-text">
								${Loc.getMessage('TASKS_SCRUM_DOD_LABEL_EMPTY')}
							</div>
						</div>
					</div>
				</div>
			`;

		Event.bind(node.querySelector('span'), 'click', () => this.emit('showSettings'));

		return node;
	}

	render(): HTMLElement
	{
		const activeType = this.getActiveType();

		const renderOption = (typeData: ItemTypeParams) => {
			const selected = activeType.getId() === typeData.id ? 'selected' : '';

			return `<option value="${parseInt(typeData.id, 10)}" ${selected}>${Text.encode(typeData.name)}</option>`;
		};

		this.node = Tag.render`
			<div class="ui-form ui-form-line tasks-scrum-dod-form">
				<div class="ui-form-row">
					<div class="ui-form-label">
						<div class="ui-ctl-label-text">
							${Loc.getMessage('TASKS_SCRUM_DOD_LABEL_TYPES')}
						</div>
					</div>
					<div class="ui-form-content">
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
							<div class="ui-ctl-after ui-ctl-icon-angle"></div>
							<select class="ui-ctl-element tasks-scrum-dod-types">
								${
									[...this.typeStorage.getTypes().values()]
										.map((typeData: ItemTypeParams) => renderOption(typeData)).join('')
								}
							</select>
						</div>
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-content tasks-scrum-dod-checklist"></div>
				</div>
			</div>
		`;

		const typeSelector = this.node.querySelector('.tasks-scrum-dod-types');

		Event.bind(typeSelector, 'change', (event) => {
			const typeId = parseInt(event.target.value, 10);
			this.tabs.setActiveType(this.typeStorage.getType(typeId));
			this.renderList();
		});

		return this.node;
	}

	save()
	{
		const activeType = this.getActiveType();

		this.requestSender.saveList({
			typeId: activeType.getId(),
			taskId: this.taskId,
			groupId: this.groupId,
			items: this.getListItems()
		})
			.then(() => {
				if (this.isSkipNotifications())
				{
					this.solve();
				}
				else
				{
					if (this.isListRequired(this.getActiveType()))
					{
						if (this.isAllToggled())
						{
							this.emit('resolve');
						}
						else
						{
							this.emit('reject');

							this.showInfoPopup();
						}
					}
					else
					{
						if (this.isAllToggled())
						{
							this.emit('resolve');
						}
						else
						{
							this.showConfirmPopup();
						}
					}
				}
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	isSkipNotifications(): boolean
	{
		return this.skipNotifications;
	}

	isEmpty(): boolean
	{
		return this.empty;
	}

	getActiveType(): ?ItemType
	{
		return this.tabs.getActiveType();
	}

	isListRequired(type: ItemType): boolean
	{
		return type.isDodRequired();
	}

	solve()
	{
		if (this.isListRequired(this.getActiveType()))
		{
			if (this.isAllToggled())
			{
				this.emit('resolve');
			}
			else
			{
				this.emit('reject');
			}
		}
		else
		{
			this.emit('resolve');
		}
	}

	getListItems(): Array
	{
		/* eslint-disable */
		if (typeof top.BX.Tasks.CheckListInstance === 'undefined')
		{
			return [];
		}

		const treeStructure = top.BX.Tasks.CheckListInstance.getTreeStructure();

		return treeStructure.getRequestData();
		/* eslint-enable */
	}

	isAllToggled(): boolean
	{
		/* eslint-disable */
		if (typeof top.BX.Tasks.CheckListInstance === 'undefined')
		{
			return false;
		}

		let isAllToggled = true;

		const treeStructure = top.BX.Tasks.CheckListInstance.getTreeStructure();

		treeStructure.getDescendants()
			.forEach((checkList) => {
				if (checkList.countTotalCount() > 0 && !checkList.checkIsComplete())
				{
					isAllToggled = false;
				}
			})
		;

		return isAllToggled;
		/* eslint-enable */
	}

	showInfoPopup()
	{
		MessageBox.alert(Loc.getMessage('TASKS_SCRUM_DOD_INFO_TEXT'));
	}

	showConfirmPopup()
	{
		const messageBox = new MessageBox({
			message: Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_TEXT_COMPLETE'),
			modal: true,
			buttons: [
				new Button({
					text: Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_COMPLETE_BUTTON_TEXT'),
					color: Button.Color.SUCCESS,
					events: {
						click: () => {
							this.emit('resolve');
							messageBox.close();
						},
					},
				}),
				new Button({
					text: Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_SAVE_BUTTON_TEXT'),
					color: Button.Color.LINK,
					events: {
						click: () => {
							this.emit('reject');
							messageBox.close();
						},
					},
				}),
			],
		});

		messageBox.show();
	}

	showLoader(container: HTMLElement): Loader
	{
		const listPosition = Dom.getPosition(container);

		const loader = new Loader({
			target: container,
			size: 60,
			mode: 'inline',
			color: 'rgba(82, 92, 105, 0.9)',
			offset: {
				left: `${(listPosition.width / 2 - 30)}px`
			}
		});

		loader.show();

		return loader;
	}
}