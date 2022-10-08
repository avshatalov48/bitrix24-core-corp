import {Loc, Tag, Text, Type, Event, Dom, Runtime} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Loader} from 'main.loader';

import {MessageBox, MessageBoxButtons} from 'ui.dialogs.messagebox';
import {Button, CancelButton} from 'ui.buttons';
import {Layout} from 'ui.sidepanel.layout';

import {RequestSender} from './request.sender';
import {TypeStorage} from './type.storage';
import {ItemType, ItemTypeParams} from './item.type';

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

		this.sidePanelManager = BX.SidePanel.Instance;

		this.requestSender = params.requestSender;

		this.groupId = parseInt(params.groupId, 10);
		this.taskId = parseInt(params.taskId, 10);

		this.skipNotifications = Type.isBoolean(params.skipNotifications) ? params.skipNotifications : false;

		this.typeStorage = new TypeStorage();

		this.empty = true;

		this.node = null;
	}

	show()
	{
		this.sidePanelManager.open(
			'tasks-scrum-dod-list-side-panel',
			{
				cacheable: false,
				width: 800,
				contentCallback: () => {
					return Layout.createContent({
						extensions: ['tasks.scrum.dod', 'tasks'],
						title: Loc.getMessage('TASKS_SCRUM_DOD_TITLE'),
						content: this.renderContent.bind(this),
						design: {
							section: false
						},
						toolbar: ({Button}) => {
							return [
								new Button({
									color: Button.Color.LIGHT_BORDER,
									text: Loc.getMessage('TASKS_SCRUM_DOD_TOOLBAR_SETTINGS'),
									onclick: () => this.emit('showSettings', false),
								}),
							];
						},
						buttons: ({cancelButton, SaveButton}) => {
							return [
								new SaveButton({
									text: this.getListButtonText(),
									onclick: this.onSaveList.bind(this)
								}),
								new CancelButton({
									onclick: () => {
										this.emit('reject')
										this.sidePanelManager.close(false);
									}
								}),
							];
						}
					});
				},
				events: {
					onLoad: this.onLoadList.bind(this)
				}
			}
		);
	}

	onLoadList()
	{
		if (this.isEmpty())
		{
			return;
		}

		this.renderList();
	}

	onSaveList()
	{
		if (this.isEmpty())
		{
			return;
		}

		this.save()
			.then((decision: string) => {
				if (decision === 'resolve')
				{
					this.emit('resolve');
					this.sidePanelManager.close(false);
				}
				else if (decision === 'reject')
				{
					this.emit('reject');
					this.sidePanelManager.close(false);
				}
			})
		;
	}

	getListButtonText(): string
	{
		if (this.isSkipNotifications())
		{
			return Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_SAVE_BUTTON_TEXT')
		}
		else
		{
			return Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_COMPLETE_BUTTON_TEXT')
		}
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

				this.typeStorage.setActiveType(itemTypes.get(activeTypeId));

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
				Runtime.html(listNode, response.data.html);
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

		Event.bind(node.querySelector('span'), 'click', () => this.emit('showSettings', true));

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
			this.typeStorage.setActiveType(this.typeStorage.getType(typeId));
			this.renderList();
		});

		return this.node;
	}

	save(): Promise
	{
		const activeType = this.getActiveType();

		return this.requestSender.saveList({
			typeId: activeType.getId(),
			taskId: this.taskId,
			groupId: this.groupId,
			items: this.getListItems()
		})
			.then(() => {
				if (this.isSkipNotifications())
				{
					return this.solve();
				}
				else
				{
					if (this.isListRequired(this.getActiveType()))
					{
						if (this.isAllToggled())
						{
							return 'resolve';
						}
						else
						{
							this.showInfoPopup();

							return 'wait';
						}
					}
					else
					{
						if (this.isAllToggled())
						{
							return 'resolve';
						}
						else
						{
							this.showConfirmPopup();

							return 'wait';
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
		return this.typeStorage.getActiveType();
	}

	isListRequired(type: ItemType): boolean
	{
		return type.isDodRequired();
	}

	solve(): string
	{
		if (this.isListRequired(this.getActiveType()))
		{
			if (this.isAllToggled())
			{
				return 'resolve';
			}
			else
			{
				return 'reject';
			}
		}
		else
		{
			return 'resolve';
		}
	}

	getListItems(): Array
	{
		/* eslint-disable */
		if (typeof BX.Tasks.CheckListInstance === 'undefined')
		{
			return [];
		}

		const treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();

		return treeStructure.getRequestData();
		/* eslint-enable */
	}

	isAllToggled(): boolean
	{
		/* eslint-disable */
		if (typeof BX.Tasks.CheckListInstance === 'undefined')
		{
			return false;
		}

		let isAllToggled = true;

		const treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();

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
		const popupOptions = {};
		const currentSlider = this.sidePanelManager.getTopSlider();
		if (currentSlider)
		{
			popupOptions.targetContainer = currentSlider.getContainer();
		}

		(new MessageBox({
			message: Loc.getMessage('TASKS_SCRUM_DOD_INFO_TEXT'),
			popupOptions: popupOptions,
			buttons: MessageBoxButtons.OK
		})).show();
	}

	showConfirmPopup()
	{
		const popupOptions = {};
		const currentSlider = this.sidePanelManager.getTopSlider();
		if (currentSlider)
		{
			popupOptions.targetContainer = currentSlider.getContainer();
		}

		const messageBox = new MessageBox({
			popupOptions: popupOptions,
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