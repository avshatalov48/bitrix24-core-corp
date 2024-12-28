import {Dom, Tag, Type, Loc, Event, Runtime, Text} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {Loader} from 'main.loader';

import {TagSelector} from 'ui.entity-selector';
import {Layout} from 'ui.sidepanel.layout';
import {Item} from 'ui.sidepanel.menu';
import {UI} from 'ui.notification';
import {MessageBox, MessageBoxButtons} from 'ui.dialogs.messagebox';

import {ItemType, ItemTypeParams} from './item.type';
import {TypeStorage} from './type.storage';

import {RequestSender} from './request.sender';

type Params = {
	requestSender: RequestSender,
	groupId: number,
	taskId?: number
}

export class Settings
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;

		this.groupId = parseInt(params.groupId, 10);
		this.taskId = parseInt(params.taskId, 10);

		this.sidePanelManager = BX.SidePanel.Instance;

		this.typeStorage = new TypeStorage();

		this.layoutMenu = null;

		this.nameInput = null;

		this.changed = false;

		EventEmitter.subscribe(
			'BX.Tasks.CheckListItem:CheckListChanged',
			() => {
				this.setChanged();
			}
		);
	}

	show()
	{
		this.sidePanelManager.open(
			'tasks-scrum-dod-settings-side-panel',
			{
				cacheable: false,
				width: 1000,
				contentCallback: () => {
					return Layout.createLayout({
						extensions: ['tasks.scrum.dod', 'ui.entity-selector', 'tasks'],
						title: Loc.getMessage('TASKS_SCRUM_DOD_TITLE'),
						content: this.renderContent.bind(this),
						design: {
							section: false
						},
						menu: {},
						toolbar: ({Button}) => {
							return [
								new Button({
									color: Button.Color.LIGHT_BORDER,
									text: Loc.getMessage('TASKS_SCRUM_DOD_BTN_CREATE_TYPE'),
									onclick: () => {
										this.showTypeForm();
									},
								}),
							];
						},
						buttons: []
					})
						.then((layout: Layout) => {
							this.layoutMenu = layout.getMenu();

							this.layoutMenu.subscribe('click', this.onMenuItemClick.bind(this));

							return layout.render();
						})
					;
				},
				events: {
					onLoad: this.onLoadSettings.bind(this),
					onClose: this.onCloseSettings.bind(this),
					onCloseComplete: this.onCloseSettingsComplete.bind(this)
				}
			}
		);
	}

	onLoadSettings()
	{
		this.layoutMenu.setItems(this.getMenuItems());

		if (!this.isEmpty())
		{
			this.buildEditingForm(this.typeStorage.getActiveType());
		}
	}

	onCloseSettings()
	{
		if (this.isChanged())
		{
			this.saveSettings()
				.then(() => {
					UI.Notification.Center.notify({
						autoHideDelay: 1000,
						content: Loc.getMessage('TASKS_SCRUM_DOD_SAVE_SETTINGS_NOTIFY')
					});
				})
				.catch(() => {})
			;
		}
	}

	onCloseSettingsComplete()
	{
		const currentSlider = this.sidePanelManager.getTopSlider();
		if (currentSlider)
		{
			if (
				currentSlider.getUrl() === 'tasks-scrum-dod-list-side-panel'
				&& this.isChanged()
			)
			{
				currentSlider.reload();
			}
		}
	}

	isEmpty(): boolean
	{
		return this.typeStorage.isEmpty();
	}

	isChanged(): boolean
	{
		return this.changed;
	}

	setChanged(): void
	{
		this.changed = true;
	}

	renderContent(): Promise
	{
		return this.requestSender.getSettings({
			groupId: this.groupId
		})
			.then((response) => {

				const types = Type.isArray(response.data.types) ? response.data.types : [];

				const itemTypes = new Map();

				types.forEach((typeData: ItemTypeParams) => {
					const itemType = new ItemType(typeData);
					itemTypes.set(itemType.getId(), itemType);
				});

				this.typeStorage.setTypes(itemTypes);
				this.typeStorage.setActiveType();

				return this.render(this.typeStorage.getActiveType());
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	render(type: ItemType): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum-dod-settings">
				<div class="tasks-scrum-dod-settings-container">
					<div class="tasks-scrum-dod-settings-container-wrap">
						<div class="tasks-scrum-dod-settings-container-sidebar-wrapper">
							${this.renderContainer(type)}
						</div>
					</div>
				</div>
			</div>
		`;

		return this.node;
	}

	renderContainer(type: ItemType): HTMLElement
	{
		if (this.typeStorage.isEmpty())
		{
			return this.renderEmptyForm();
		}
		else
		{
			return this.renderEditingForm(type);
		}
	}

	renderEditingForm(type: ItemType): HTMLElement
	{
		return Tag.render`
			<div class="ui-form">
				<div class="ui-form-row">
					<div class="ui-form-content">
						${this.renderRequiredOption(type)}
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-content">
						${this.renderParticipantsSelector()}
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-content ui-form-content-dod-list"></div>
				</div>
			</div>
		`;
	}

	renderEmptyForm(): HTMLElement
	{
		return Tag.render`
			<div class="ui-form">
				<div class="ui-form-row">
					<div class="ui-form-content">
						<div class="ui-form-row">
							<div class="ui-ctl-label-text">
								${Loc.getMessage('TASKS_SCRUM_CREATE_TYPE_PROMPT')}
							</div>
						</div>
					</div>
				</div>
			</div>
		`;
	}

	renderRequiredOption(type: ItemType): HTMLElement
	{
		const node = Tag.render`
			<div class="ui-form-row">
				<label class="ui-ctl ui-ctl-checkbox">
					<input type="checkbox" class="ui-ctl-element ui-form-content-required-option">
					<div class="ui-ctl-label-text">
						${Loc.getMessage('TASKS_SCRUM_DOD_OPTIONS_REQUIRED_LABEL')}
					</div>
				</label>
			</div>
		`;

		const checkbox = node.querySelector('.ui-form-content-required-option');
		checkbox.checked = type.isDodRequired();

		Event.bind(checkbox, 'click', () => {
			this.setChanged();
			this.updateActiveType();
		});

		return node;
	}

	renderParticipantsSelector(): ?HTMLElement
	{
		return ''; //todo tmp

		return Tag.render`
			<div class="ui-form-row">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${Loc.getMessage('TASKS_SCRUM_DOD_LABEL_USER_SELECTOR')}
					</div>
				</div>
				<div class="ui-form-content">
					<div class="tasks-scrum-dod-settings-user-selector"></div>
				</div>
			</div>
		`;
	}

	renderTypeForm(type?: ItemType): HTMLElement
	{
		const name = type ? type.getName() : '';

		this.typeFormNode = Tag.render`
			<div class="tasks-scrum-dod-settings-type-form">
				<div class="ui-alert ui-alert-danger --hidden">
					<span class="ui-alert-message"></span>
				</div>
				<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
					<input
						type="text"
						class="ui-ctl-element"
						placeholder="${Loc.getMessage('TASKS_SCRUM_DOD_POPUP_INPUT_PLACEHOLDER')}"
						value="${Text.encode(name)}"
					>
				</div>
			</div>
		`;

		this.nameInput = this.typeFormNode.querySelector('input');

		Event.bind(
			this.nameInput,
			'keydown',
			(event: KeyboardEvent) => {
				if (event.key === 'Enter')
				{
					this.onOkTypeForm(type);
				}

				this.hideTypeFormError();
			}
		);

		return this.typeFormNode;
	}

	initParticipantsSelector(type: ItemType)
	{
		const participantsSelectorContainer = this.node.querySelector('.tasks-scrum-dod-settings-user-selector');

		if (Type.isNil(participantsSelectorContainer))
		{
			return;
		}

		const selectorId = 'tasks-scrum-dod-settings-participants-selector-' + type.getId();

		// todo change to scrum-user provider
		this.participantsSelector = new TagSelector({
			id: selectorId,
			dialogOptions: {
				id: selectorId,
				context: 'TASKS',
				preselectedItems: this.typeStorage.getActiveType().getParticipants(),
				entities: [
					{
						id: 'user',
						options: {
							inviteEmployeeLink: false,
							analyticsSource: 'tasks',
						}
					},
					{
						id: 'project-roles',
						options: {
							projectId: this.groupId
						},
						dynamicLoad: true
					}
				],
			}
		});

		this.participantsSelector.renderTo(participantsSelectorContainer);
	}

	buildEditingForm(type: ItemType)
	{
		const container = this.cleanTypeForm();

		Dom.append(this.renderEditingForm(type), container);

		this.initParticipantsSelector(type);

		const listContainer = this.node.querySelector('.ui-form-content-dod-list');

		const loader = this.showLoader(listContainer);

		this.requestSender.getChecklist({
			groupId: this.groupId,
			typeId: type.getId()
		})
		.then((response) => {
			loader.hide();
			Runtime.html(listContainer, response.data.html);
		})
		.catch((response) => {
			loader.hide();
			this.requestSender.showErrorAlert(response);
		});
	}

	buildEmptyForm()
	{
		const container = this.cleanTypeForm();

		Dom.append(this.renderEmptyForm(), container);
	}

	showTypeForm(type?: ItemType)
	{
		this.typeForm = new MessageBox({
			popupOptions: this.getDefaultPopupOptions(),
			title: Type.isUndefined(type)
				? Loc.getMessage('TASKS_SCRUM_DOD_POPUP_TITLE_CREATE')
				: Loc.getMessage('TASKS_SCRUM_DOD_POPUP_TITLE_EDIT')
			,
			message: this.renderTypeForm(type),
			buttons: MessageBoxButtons.OK_CANCEL,
			okCaption: Type.isUndefined(type)
				? Loc.getMessage('TASKS_SCRUM_DOD_BTN_CREATE_TYPE')
				: Loc.getMessage('TASKS_SCRUM_DOD_BTN_SAVE')
			,
			onOk: () => this.onOkTypeForm(type)
		});

		const popup = this.typeForm.getPopupWindow();
		popup.subscribe('onAfterShow', () => {
			const length = this.nameInput.value.length;
			this.nameInput.focus();
			this.nameInput.setSelectionRange(length, length);
		});

		this.typeForm.show();
	}

	onOkTypeForm(type?: ItemType)
	{
		if (!this.nameInput.value.trim())
		{
			this.showTypeFormError(Loc.getMessage('TASKS_SCRUM_DOD_POPUP_EMPTY_NAME'));

			this.typeForm.getOkButton().setDisabled(false);

			return;
		}

		this.typeForm.close();

		if (Type.isUndefined(type))
		{
			const skipPrevious = this.typeStorage.isEmpty();
			this.createType(this.nameInput.value)
				.then((createdType: ?ItemType) => {
					if (createdType)
					{
						this.addMenuItem(createdType);
						this.switchType(createdType, skipPrevious);
					}
				})
			;
		}
		else
		{
			type.setName(this.nameInput.value);

			this.changeType(type)
				.then((changedType: ?ItemType) => {
					if (changedType)
					{
						this.changeMenuItem(changedType)
						this.switchType(changedType);
					}
				})
			;
		}
	}

	showTypeFormError(message: string)
	{
		const alertNode = this.typeFormNode.querySelector('.ui-alert');

		alertNode.querySelector('.ui-alert-message').textContent = message;

		Dom.removeClass(alertNode, '--hidden');
	}

	hideTypeFormError()
	{
		const alertNode = this.typeFormNode.querySelector('.ui-alert');

		if (!Dom.hasClass(alertNode, '--hidden'))
		{
			Dom.addClass(this.typeFormNode.querySelector('.ui-alert'), '--hidden');
		}
	}

	createType(name: string): Promise
	{
		const container = this.node.querySelector('.tasks-scrum-dod-settings-container-sidebar-wrapper');

		const loader = this.showLoader(container);

		return this.requestSender.createType({
			groupId: this.groupId,
			name: name,
			sort: this.typeStorage.getTypes().size + 1
		})
		.then((response) => {
			this.setChanged();
			loader.hide();

			const createdType = new ItemType(response.data);
			this.typeStorage.addType(createdType);

			return createdType;
		})
		.catch((response) => {
			loader.hide();

			this.requestSender.showErrorAlert(response);
		});
	}

	switchType(type: ItemType, skipPrevious: boolean = false)
	{
		const menuItem: Item = this.getMenuItem(type);

		let previousType: ?ItemType = null;
		if (!skipPrevious)
		{
			previousType = this.typeStorage.getActiveType();

			if (menuItem.getId() === previousType.getId())
			{
				return;
			}
		}

		this.typeStorage.setActiveType(type);
		this.setActiveMenuItem(type);

		if (previousType)
		{
			this.saveSettings(previousType)
				.then((response) => {
					const updatedType: ItemTypeParams = response.data.type;

					previousType.setDodRequired(updatedType.dodRequired);
					previousType.setParticipants(updatedType.participants);

					this.buildEditingForm(type);
				})
			;
		}
		else
		{
			this.buildEditingForm(type);
		}
	}

	changeType(type: ItemType): Promise
	{
		return this.requestSender.changeTypeName({
			groupId: this.groupId,
			id: type.getId(),
			name: type.getName()
		})
			.then(() => {
				this.setChanged();

				return type;
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	removeType(type: ItemType): Promise
	{
		return this.requestSender.removeType({
			groupId: this.groupId,
			id: type.getId()
		})
		.then(() => {
			this.setChanged();
			this.typeStorage.removeType(type);
			if (this.typeStorage.isEmpty())
			{
				this.buildEmptyForm();

				return null;
			}
			else
			{
				return this.typeStorage.getActiveType()
			}
		})
		.catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	saveSettings(inputType?: ItemType): Promise
	{
		if (this.typeStorage.isEmpty())
		{
			return Promise.resolve();
		}

		const type = inputType ? inputType : this.typeStorage.getActiveType();

		if (!(type instanceof ItemType))
		{
			return Promise.resolve();
		}

		return this.requestSender.saveSettings({
			groupId: this.groupId,
			typeId: type.getId(),
			requiredOption: this.getRequiredOptionValue(),
			items: this.getChecklistItems(),
			participants: this.getSelectedParticipants()
		})
		.catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	getChecklistItems(): Array
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

	getSelectedParticipants(): Array
	{
		if (Type.isNil(this.participantsSelector))
		{
			return [];
		}

		const selectedParticipants = [];

		this.participantsSelector.getTags()
			.forEach((tag) => {
				selectedParticipants.push({
					id: tag.getId(),
					entityId: tag.getEntityId()
				});
			})
		;

		return selectedParticipants;
	}

	getRequiredOptionValue(): string
	{
		const requiredOption = this.node.querySelector('.ui-form-content-required-option');

		return (requiredOption.checked === true ? 'Y' : 'N');
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

	updateActiveType()
	{
		const type = this.typeStorage.getActiveType();

		type.setDodRequired(this.getRequiredOptionValue());
	}

	cleanTypeForm(): HTMLElement
	{
		const container = this.node.querySelector('.tasks-scrum-dod-settings-container-sidebar-wrapper');

		Dom.clean(container);

		return container;
	}

	getMenuItems(): Array
	{
		if (this.typeStorage.isEmpty())
		{
			return [];
		}

		const items = [];

		this.typeStorage.getTypes()
			.forEach((type: ItemType) => {
				items.push(this.getMenuItemOptions(type, items.length === 0))
			})
		;

		return items;
	}

	addMenuItem(type: ItemType): Item
	{
		return this.layoutMenu.add(this.getMenuItemOptions(type));
	}

	changeMenuItem(type: ItemType): ?Item
	{
		return this.layoutMenu.change(
			type.getId(),
			{
				label: type.getName()
			}
		);
	}

	removeMenuItem(type: ItemType)
	{
		this.layoutMenu.remove(type.getId());
	}

	getMenuItem(type: ItemType): ?Item
	{
		return this.layoutMenu.get(type.getId());
	}

	setActiveMenuItem(type: ItemType)
	{
		this.getMenuItem(type).setActive();
	}

	getMenuItemOptions(type: ItemType, active: boolean = false): Object
	{
		return {
			id: type.getId(),
			label: type.getName(),
			active: active,
			actions: [
				{
					label: Loc.getMessage('TASKS_SCRUM_DOD_BTN_EDIT_TYPE'),
					onclick: () => {
						this.showTypeForm(type);
					}
				},
				{
					label: Loc.getMessage('TASKS_SCRUM_DOD_BTN_REMOVE_TYPE'),
					onclick: (item: Item) => {
						(new MessageBox({
							title: Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_TYPE_TITLE'),
							message: Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_TYPE_NEW')
								.replace('#name#', Text.encode(type.getName()))
							,
							popupOptions: this.getDefaultPopupOptions(),
							okCaption: Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_REMOVE'),
							buttons: MessageBoxButtons.OK_CANCEL,
							minHeight: 100,
							onOk: (messageBox) => {
								this.removeType(type)
									.then((nextType: ?ItemType) => {
										this.removeMenuItem(type);

										if (!Type.isNull(nextType))
										{
											this.switchType(nextType, true);
										}

										messageBox.close();
									})
								;
							}
						})).show();

					}
				},
			]
		};
	}

	onMenuItemClick(baseEvent: BaseEvent)
	{
		const { item: menuItem } = baseEvent.getData();

		this.switchType(this.typeStorage.getType(menuItem.getId()));
	}

	getDefaultPopupOptions(): Object
	{
		const popupOptions = {};

		const currentSlider = this.sidePanelManager.getTopSlider();
		if (currentSlider)
		{
			popupOptions.targetContainer = currentSlider.getContainer();
		}

		return popupOptions;
	}
}