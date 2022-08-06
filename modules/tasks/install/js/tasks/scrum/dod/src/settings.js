import {Dom, Tag, Type, Loc, Event, Runtime} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {Loader} from 'main.loader';

import {TagSelector} from 'ui.entity-selector';

import {ItemType, ItemTypeParams} from './item.type';
import {TypeStorage} from './type.storage';
import {Tabs} from './tabs';

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

		this.typeStorage = new TypeStorage();
		this.tabs = new Tabs();

		this.tabs.subscribe('switchType', this.onSwitchType.bind(this));
		this.tabs.subscribe('createType', this.onCreateType.bind(this));
		this.tabs.subscribe('changeTypeName', this.onChangeTypeName.bind(this));
		this.tabs.subscribe('removeType', this.onRemoveType.bind(this));

		this.changed = false;
		EventEmitter.subscribe(
			'BX.Tasks.CheckListItem:CheckListChanged',
			() => {
				this.setChanged();
			}
		);
	}

	isEmpty(): boolean
	{
		return this.tabs.isEmpty();
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

				this.tabs.setTypeStorage(this.typeStorage);
				this.tabs.setActiveType(this.typeStorage.getNextType());

				this.addEmptyCreationType();

				return this.render();
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	render(): HTMLElement
	{
		const currentType: ItemType = this.typeStorage.getNextType();

		this.node = Tag.render`
			<div class="tasks-scrum-dod-settings">
				<div class="tasks-scrum-dod-settings-container">
					<div class="tasks-scrum-dod-settings-container-wrap">
						<div class="tasks-scrum-dod-settings-container-shell">
							${this.tabs.render()}
							<div class="tasks-scrum-dod-settings-container-sidebar-wrapper">
								${this.renderContainer(currentType)}
							</div>
						</div>
					</div>
				</div>
			</div>
		`;

		return this.node;
	}

	renderContainer(type: ItemType): HTMLElement
	{
		if (this.tabs.isEmpty())
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
				preselectedItems: this.tabs.getActiveType().getParticipants(),
				entities: [
					{
						id: 'user',
						options: {
							inviteEmployeeLink: false
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

	onSwitchType(baseEvent: BaseEvent)
	{
		const type: ItemType = baseEvent.getData();
		const previousType: ?ItemType = this.tabs.getPreviousType();

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

	onCreateType(baseEvent: BaseEvent)
	{
		const container = this.node.querySelector('.tasks-scrum-dod-settings-container-sidebar-wrapper');

		const loader = this.showLoader(container);

		const tmpType = baseEvent.getData();

		this.requestSender.createType({
			groupId: this.groupId,
			name: tmpType.getName(),
			sort: tmpType.getSort()
		})
		.then((response) => {
			this.setChanged();
			loader.hide();
			const createdType = new ItemType(response.data);
			this.typeStorage.addType(createdType);
			this.tabs.addType(createdType, tmpType);
		})
		.catch((response) => {
			loader.hide();
			this.requestSender.showErrorAlert(response);
		});
	}

	onChangeTypeName(baseEvent: BaseEvent)
	{
		const type = baseEvent.getData();

		this.requestSender.changeTypeName({
			groupId: this.groupId,
			id: type.getId(),
			name: type.getName()
		})
			.then(() => {
				this.setChanged();
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	onRemoveType(baseEvent: BaseEvent)
	{
		const type = baseEvent.getData();

		this.requestSender.removeType({
			groupId: this.groupId,
			id: type.getId()
		})
		.then(() => {
			this.setChanged();
			this.typeStorage.removeType(type);
			if (this.tabs.isEmpty())
			{
				this.buildEmptyForm();
			}
			else
			{
				const nextType = [...this.typeStorage.getTypes().values()]
					.find((type: ItemType) => !this.tabs.isEmptyType(type))
				;
				this.tabs.switchToType(nextType);
			}
		})
		.catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	saveSettings(inputType?: ItemType): Promise
	{
		if (this.tabs.isEmpty())
		{
			return Promise.resolve();
		}

		const type = inputType ? inputType : this.tabs.getActiveType();

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

	getActiveType(): ?ItemType
	{
		return this.tabs.getActiveType();
	}

	updateActiveType()
	{
		const type = this.tabs.getActiveType();

		type.setDodRequired(this.getRequiredOptionValue());
	}

	cleanTypeForm(): HTMLElement
	{
		const container = this.node.querySelector('.tasks-scrum-dod-settings-container-sidebar-wrapper');

		Dom.clean(container);

		return container;
	}

	addEmptyCreationType()
	{
		const itemType = new ItemType();

		this.tabs.setEmptyType(itemType);

		this.typeStorage.addType(itemType);
	}
}