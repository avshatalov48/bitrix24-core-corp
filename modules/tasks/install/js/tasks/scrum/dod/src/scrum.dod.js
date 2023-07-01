import {Event, Loc, Runtime, Tag, Text, Dom} from 'main.core';
import {BaseEvent} from 'main.core.events';
import {Popup} from 'main.popup';
import {Loader} from 'main.loader';
import {MessageBox, MessageBoxButtons} from 'ui.dialogs.messagebox';
import {Button} from 'ui.buttons';

import {SidePanel} from './side.panel';
import {RequestSender} from './request.sender';

import './css/base.css';

import 'ui.layout-form';
import 'ui.forms';

type Params = {
	groupId: number
}

type TypeData = {
	id: number,
	name: string,
	sort: number,
	dodRequired: 'Y' | 'N'
}

type Response = {
	data: {
		types: Array<TypeData>,
		activeTypeId: number
	}
}

export class ScrumDod
{
	constructor(params: Params)
	{
		this.groupId = parseInt(params.groupId, 10);

		this.sidePanel = new SidePanel();
		this.requestSender = new RequestSender();

		this.emptyDod = true;
		this.skipNotifications = false;
	}

	showList(taskId: number): Promise
	{
		this.taskId = parseInt(taskId, 10);

		return this.requestSender.getSettings({
			groupId: this.groupId,
			taskId: this.taskId
		}).then((response: Response) => {
			const settings = response.data;

			const types = settings.types;

			this.emptyDod = (types.length === 0);

			const activeTypeId = settings.activeTypeId;

			if (this.isEmptyDod())
			{
				if (!this.skipNotifications)
				{
					return Promise.resolve();
				}
			}

			this.setActiveTypeData(activeTypeId, types);

			const popup = this.createPopup(types);

			popup.subscribe('onAfterShow', (baseEvent: BaseEvent) => {

				if (this.isEmptyDod())
				{
					return;
				}

				const contentContainer = popup.getContentContainer();
				const typesNode = contentContainer.querySelector('.tasks-scrum-dod-types');
				const listNode = contentContainer.querySelector('.tasks-scrum-dod-checklist');

				Event.bind(typesNode, 'change', () => {
					const typeId = parseInt(typesNode.value, 10);
					this.setActiveTypeData(typeId, types);
					this.renderListTo(listNode, typeId)
						.then(() => {
							popup.adjustPosition();
						})
					;
				});

				this.renderListTo(listNode, typesNode.value)
					.then(() => {
						popup.adjustPosition();
					})
				;
			});

			popup.subscribe('onClose', () => this.onClose());

			popup.show();

			return new Promise((resolve, reject) => {
				this.resolver = resolve;
				this.rejecter = reject;
			});
		});
	}

	skipNotificationPopups()
	{
		this.skipNotifications = true;
	}

	onClose()
	{
		if (this.isEmptyDod())
		{
			return;
		}

		const activeTypeData = this.getActiveTypeData();

		this.requestSender.saveList({
			typeId: activeTypeData.id,
			taskId: this.taskId,
			items: this.getListItems()
		})
		.then(() => {
			if (this.skipNotifications)
			{
				this.solve();
			}
			else
			{
				if (this.isListRequired(this.getActiveTypeData()))
				{
					if (this.isAllToggled())
					{
						this.resolver();
					}
					else
					{
						this.rejecter();

						this.showInfoPopup();
					}
				}
				else
				{
					if (this.isAllToggled())
					{
						this.resolver();
					}
					else
					{
						this.showConfirmPopup();
					}
				}
			}
		});
	}

	showInfoPopup()
	{
		MessageBox.show({
			message: Loc.getMessage('TASKS_SCRUM_DOD_INFO_TEXT'),
			modal: true,
			buttons: MessageBoxButtons.OK,
		});
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
							this.resolver();
							messageBox.close();
						},
					},
				}),
				new Button({
					text: Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_SAVE_BUTTON_TEXT'),
					color: Button.Color.LINK,
					events: {
						click: () => {
							this.rejecter();
							messageBox.close();
						},
					},
				}),
			],
		});

		messageBox.show();
	}

	solve()
	{
		if (this.isListRequired(this.getActiveTypeData()))
		{
			if (this.isAllToggled())
			{
				this.resolver();
			}
			else
			{
				this.rejecter();
			}
		}
		else
		{
			this.resolver();
		}
	}

	createPopup(types: Array<TypeData>): Popup
	{
		const buttons = [];

		if (this.isEmptyDod())
		{
			buttons.push(
				new Button({
					text : Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_CLOSE_BUTTON_TEXT'),
					color: Button.Color.LINK,
					events : {
						click: () => popup.close()
					}
				})
			);
		}
		else
		{
			buttons.push(
				new Button({
					text : this.getPopupButtonText(),
					color: Button.Color.SUCCESS,
					events : {
						click: () => popup.close()
					}
				})
			);
		}

		const popup = new Popup(
			Text.getRandom(),
			null,
			{
				titleBar: Loc.getMessage('TASKS_SCRUM_DOD_HEADER'),
				content: this.renderContent(types),
				contentPadding: 10,
				contentBackground: '#f8f9fa',
				autoHide: true,
				closeByEsc: true,
				closeIcon: true,
				overlay: true,
				buttons: buttons
			}
		);

		return popup;
	}

	renderContent(types: Array<TypeData>): HTMLElement
	{
		if (this.isEmptyDod())
		{
			return Tag.render`
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
		}

		const activeTypeData = this.getActiveTypeData();

		const renderOption = (typeData: TypeData) =>
		{
			const selected = activeTypeData.id === typeData.id ? 'selected' : '';

			return `<option value="${parseInt(typeData.id, 10)}" ${selected}>${Text.encode(typeData.name)}</option>`;
		};

		return Tag.render`
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
								${types.map((typeData: TypeData) => renderOption(typeData)).join('')}
							</select>
						</div>
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-content tasks-scrum-dod-checklist"></div>
				</div>
			</div>
		`;
	}

	renderListTo(container: HTMLElement, typeId: number): Promise
	{
		Dom.clean(container);

		const loader = this.showLoader(container);

		return this.requestSender.getList({
			groupId: this.groupId,
			taskId: this.taskId,
			typeId: typeId
		}).then((response) => {
			loader.hide();
			return Runtime.html(container, response.data.html);
		});
	}

	setActiveTypeData(activeTypeId: number, types: Array<TypeData>)
	{
		const activeTypeData = types.find((typeData: TypeData) => typeData.id === activeTypeId);

		if (activeTypeData)
		{
			this.activeTypeData = activeTypeData;
		}
		else
		{
			this.activeTypeData = types[0];
		}
	}

	getActiveTypeData(): TypeData
	{
		return this.activeTypeData;
	}

	isEmptyDod(): boolean
	{
		return this.emptyDod;
	}

	isListRequired(typeData: TypeData): boolean
	{
		return typeData.dodRequired === 'Y';
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

		treeStructure.getDescendants().forEach((checkList) => {
			if (!checkList.checkIsComplete())
			{
				isAllToggled = false;
			}
		});

		return isAllToggled;
		/* eslint-enable */
	}

	getPopupButtonText(): string
	{
		if (this.skipNotifications)
		{
			return Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_SAVE_BUTTON_TEXT')
		}
		else
		{
			return Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_COMPLETE_BUTTON_TEXT')
		}
	}
}