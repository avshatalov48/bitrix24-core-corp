import {Dom, Tag, Type, Loc, Runtime, Event} from 'main.core';
import {BaseEvent} from 'main.core.events';
import {Loader} from 'main.loader';

import {ItemType} from './item.type';
import {TypeStorage} from './type.storage';
import {Tabs} from './tabs';

import {RequestSender} from '../utility/request.sender';

type Params = {
	requestSender: RequestSender,
	entityId: number,
	types: Map<number, ItemType>
}

import '../css/dod.css';

export class Settings
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;
		this.entityId = params.entityId;
		this.types = params.types;

		this.typeStorage = new TypeStorage({
			types: this.types
		});

		this.tabs = new Tabs({
			typeStorage: this.typeStorage
		});
		this.tabs.subscribe('switchType', this.onSwitchType.bind(this));
		this.tabs.subscribe('createType', this.onCreateType.bind(this));
		this.tabs.subscribe('changeTypeName', this.onChangeTypeName.bind(this));
		this.tabs.subscribe('removeType', this.onRemoveType.bind(this));

		this.addEmptyCreationType();
	}

	renderTo(container: HTMLElement)
	{
		if (!Type.isDomNode(container))
		{
			throw new Error('DoD Settings: HTMLElement for dod settings not found');
		}

		Dom.append(this.render(), container);

		if (this.tabs.isEmpty())
		{
			this.buildEmptyForm();
		}
		else
		{
			this.buildEditingForm(this.typeStorage.getNextType());
		}
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum-dod-settings">
				<div class="tasks-scrum-dod-settings-container">
					<div class="tasks-scrum-dod-settings-container-wrap">
						<div class="tasks-scrum-dod-settings-container-shell">
							${this.tabs.render()}
							<div class="tasks-scrum-dod-settings-container-sidebar-wrapper"></div>
						</div>
					</div>
				</div>
			</div>
		`;

		return this.node;
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
			this.updateActiveType();
		});

		return node;
	}

	buildEditingForm(type: ItemType)
	{
		const container =this.cleanTypeForm();

		Dom.append(this.renderEditingForm(type), container);

		const listContainer = this.node.querySelector('.ui-form-content-dod-list');

		const loader = this.showLoader(listContainer);

		this.requestSender.getDodChecklist({
			entityId: type.getId()
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
		const type = baseEvent.getData();
		const previousType = this.tabs.getPreviousType();

		if (previousType)
		{
			this.saveSettings(previousType)
				.then(() => {
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
			entityId: this.entityId,
			name: tmpType.getName(),
			sort: tmpType.getSort()
		})
		.then((response) => {
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
			id: type.getId(),
			name: type.getName()
		})
		.catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	onRemoveType(baseEvent: BaseEvent)
	{
		const type = baseEvent.getData();

		this.requestSender.removeType({
			id: type.getId()
		})
		.then(() => {
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

		const type = inputType? inputType : this.tabs.getActiveType();

		if (!(type instanceof ItemType))
		{
			return Promise.resolve();
		}

		return this.requestSender.saveDodSettings({
			entityId: type.getId(),
			requiredOption: this.getRequiredOptionValue(),
			items: this.getChecklistItems()
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