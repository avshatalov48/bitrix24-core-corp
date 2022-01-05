import {ajax, Dom, Event, Loc, Runtime, Tag, Text, Type} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {Layout} from 'ui.sidepanel.layout';
import {Label} from 'ui.label';

import {RequestSender} from './request.sender';

import '../css/base.css';

type Params = {
	view: 'list' | 'add' | 'view' | 'edit',
	groupId: number,
	epicId?: number
}

type ResponseAfter = {
	data: EpicType
}

type EpicType = {
	id: number,
	groupId: number,
	name: string,
	description: string,
	createdBy: number,
	modifiedBy: number,
	color: string
}

export class Epic extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Epic');

		this.view = params.view ? params.view : '';

		this.groupId = parseInt(params.groupId, 10);
		this.epicId = parseInt(params.epicId, 10);

		this.requestSender = new RequestSender();

		/* eslint-disable */
		this.sidePanelManager = BX.SidePanel.Instance;
		/* eslint-enable */
		this.sidePanel = null;

		this.id = Text.getRandom();

		this.form = null;
		this.formData = null;

		this.listData = null;

		this.editorHandler = null;

		this.defaultColor = '#69dafc';
		this.selectedColor = '';
	}

	show()
	{
		switch(this.view)
		{
			case 'add':
				this.showAddForm();
				break;
			case 'list':
				this.showList();
				break;
			case 'view':
				this.showViewForm();
				break;
			case 'edit':
				this.showEditForm();
				break;
		}
	}

	static showView(groupId: number, epicId: number)
	{
		const epic = new Epic({
			view: 'view',
			groupId: groupId,
			epicId: epicId
		});

		epic.show();
	}

	static showEdit(groupId: number, epicId: number)
	{
		const epic = new Epic({
			view: 'edit',
			groupId: groupId,
			epicId: epicId
		});

		epic.show();
	}

	static removeEpic(groupId: number, epicId: number)
	{
		const epic = new Epic({
			view: 'edit',
			groupId: groupId,
			epicId: epicId
		});

		epic.removeEpic()
			.then(() => {
				epic.reloadSidePanel();
			})
		;
	}

	showAddForm()
	{
		this.sidePanelManager.open(
			'tasks-scrum-epic-add-form-side-panel',
			{
				cacheable: false,
				width: 800,
				contentCallback: () => {
					return Layout.createContent({
						extensions: ['tasks.scrum.epic'],
						title: Loc.getMessage('TASKS_SCRUM_ADD_EPIC_FORM_TITLE'),
						content: this.createAddContent.bind(this),
						design: {
							section: false
						},
						buttons: ({cancelButton, SaveButton}) => {
							return [
								new SaveButton({
									onclick: this.onSaveAddForm.bind(this)
								}),
								cancelButton
							];
						}
					});
				},
				events: {
					onLoad: this.onLoadAddForm.bind(this)
				}
			}
		);
	}

	showList()
	{
		this.gridId = 'EntityEpicsGrid_' + this.groupId;

		const sidePanelId = 'tasks-scrum-epic-list-side-panel';

		this.subscribeListToEvents(sidePanelId);

		this.sidePanelManager.open(
			sidePanelId,
			{
				cacheable: false,
				contentCallback: () => {
					return Layout.createContent({
						extensions: ['tasks.scrum.epic'],
						title: Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_EPIC_LIST_TITLE'),
						toolbar: ({Button}) => {
							return [
								new Button({
									text: Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_EPIC_LIST_TOOLBAR_BUTTON'),
									color: Button.Color.PRIMARY,
									onclick: () => {
										this.showAddForm();
									}
								}),
							];
						},
						content: this.createListContent.bind(this),
						design: {
							section: false
						},
						buttons: []
					});
				},
				events: {
					onLoad: this.onLoadList.bind(this)
				}
			}
		);
	}

	showViewForm()
	{
		this.subscribeViewToEvents();

		this.sidePanelManager.open(
			'tasks-scrum-epic-view-form-side-panel',
			{
				cacheable: false,
				width: 800,
				contentCallback: () => {
					return new Promise((resolve, reject) => {
						this.getEpic().then((response) => {
							const epic: EpicType = response.data;
							resolve(Layout.createContent({
								extensions: ['tasks.scrum.epic'],
								title: Loc.getMessage('TASKS_SCRUM_VIEW_EPIC_FORM_TITLE'),
								content: this.createViewContent.bind(this, epic),
								design: {
									section: false
								},
								buttons: ({cancelButton, SaveButton}) => {
									return [
										new SaveButton({
											text: Loc.getMessage('TASKS_SCRUM_EPIC_EDIT_BUTTON'),
											onclick: () => {
												this.sidePanel.close(false, () => {
													EventEmitter.emit(
														this.getEventNamespace() + ':' + 'openEdit',
														epic.id
													);
												});
											}
										}),
										cancelButton
									];
								}
							}));
						});
					});
				},
				events: {
					onLoad: this.onLoadViewForm.bind(this)
				}
			}
		);
	}

	showEditForm()
	{
		this.sidePanelManager.open(
			'tasks-scrum-epic-edit-form-side-panel',
			{
				cacheable: false,
				width: 800,
				contentCallback: () => {
					return Layout.createContent({
						extensions: ['tasks.scrum.epic'],
						title: Loc.getMessage('TASKS_SCRUM_EDIT_EPIC_FORM_TITLE'),
						content: this.createEditContent.bind(this),
						design: {
							section: false
						},
						buttons: ({cancelButton, SaveButton}) => {
							return [
								new SaveButton({
									onclick: this.onSaveEditForm.bind(this)
								}),
								cancelButton
							];
						}
					});
				},
				events: {
					onLoad: this.onLoadEditForm.bind(this)
				}
			}
		);
	}

	removeEpic(): Promise
	{
		return ajax.runAction(
			'bitrix:tasks.scrum.epic.removeEpic',
			{
				data: {
					groupId: this.groupId,
					epicId: this.epicId
				}
			}
		)
			.then((response) => {
				EventEmitter.emit(this.getEventNamespace() + ':' + 'afterRemove', response.data);

				return true;
			})
		;
	}

	subscribeListToEvents(sidePanelId: string)
	{
		EventEmitter.subscribe(
			this.getEventNamespace() + ':' + 'afterAdd',
			() => {
				this.reloadSidePanel(sidePanelId);
			})
		;
		top.BX.Event.EventEmitter.subscribe(
			this.getEventNamespace() + ':' + 'afterEdit',
			() => {
				this.reloadSidePanel(sidePanelId);
			})
		;
		EventEmitter.subscribe(
			this.getEventNamespace() + ':' + 'afterRemove',
			() => {
				this.reloadSidePanel(sidePanelId);
			})
		;
	}

	subscribeViewToEvents()
	{
		EventEmitter.subscribe(
			this.getEventNamespace() + ':' + 'openEdit',
			(baseEvent: BaseEvent) => {
				Epic.showEdit(this.groupId, baseEvent.getData());
			})
		;
	}

	reloadSidePanel(sidePanelId?: string)
	{
		if (Type.isUndefined(sidePanelId))
		{
			this.sidePanelManager.reload();
		}
		else
		{
			const openSliders = this.sidePanelManager.getOpenSliders();
			if (openSliders.length > 0)
			{
				openSliders.forEach((slider) => {
					if (slider.getUrl() === sidePanelId)
					{
						slider.reload();
					}
				});
			}
		}
	}

	createAddContent(): Promise
	{
		return new Promise((resolve, reject) => {
			top.BX.ajax.runAction(
				'bitrix:tasks.scrum.epic.getDescriptionEditor',
				{
					data: {
						groupId: this.groupId,
						editorId: this.id
					}
				}
			)
				.then((response) => {
					this.formData = response.data;
					resolve(this.renderAddForm());
				})
			;
		});
	}

	createListContent(): Promise
	{
		return new Promise((resolve, reject) => {
			top.BX.ajax.runAction(
				'bitrix:tasks.scrum.epic.getList',
				{
					data: {
						groupId: this.groupId,
						gridId: this.gridId
					}
				}
			)
				.then((response) => {
					this.listData = response.data;
					resolve(this.renderList());
				})
			;
		});
	}

	createViewContent(epic: EpicType)
	{
		return new Promise((resolve, reject) => {
			top.BX.ajax.runAction(
				'bitrix:tasks.scrum.epic.getEpicFiles',
				{
					data: {
						groupId: this.groupId,
						epicId: epic.id
					}
				}
			)
				.then((response) => {
					this.epicFiles = Type.isUndefined(response.data.html) ? '' : response.data.html;
					resolve(this.renderViewForm(epic));
				})
			;
		});
	}

	createEditContent()
	{
		return new Promise((resolve, reject) => {
			this.getEpic().then((response) => {
				const epic: EpicType = response.data;
				top.BX.ajax.runAction(
					'bitrix:tasks.scrum.epic.getDescriptionEditor',
					{
						data: {
							groupId: this.groupId,
							editorId: this.id,
							epicId: epic.id,
							text: epic.description
						}
					}
				)
					.then((response) => {
						this.currentEpic = epic;
						this.formData = response.data;
						resolve(this.renderEditForm(epic));
					})
				;
			});
		});
	}

	getEpic(): Promise
	{
		return new Promise((resolve, reject) => {
			ajax.runAction(
				'bitrix:tasks.scrum.epic.getEpic',
				{
					data: {
						groupId: this.groupId,
						epicId: this.epicId
					}
				}
			).then(resolve, reject);
		});
	}

	onLoadAddForm(event)
	{
		this.sidePanel = event.getSlider();

		this.form = this.sidePanel.getContainer().querySelector('.tasks-scrum-epic-form');

		const descriptionContainer = this.form.querySelector('.tasks-scrum-epic-form-description');

		if (Type.isUndefined(this.formData.html))
		{
			return;
		}

		this.renderEditor(descriptionContainer);
	}

	onSaveAddForm()
	{
		ajax.runAction(
			'bitrix:tasks.scrum.epic.createEpic',
			{
				data: this.getRequestData()
			}
		)
			.then((response: ResponseAfter) => {
				this.sidePanel.close(false, () => {
					EventEmitter.emit(this.getEventNamespace() + ':' + 'afterAdd', response.data);
				});
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	onLoadList(event)
	{
		this.sidePanel = event.getSlider();

		const listContainer = this.sidePanel.getContainer().querySelector('.tasks-scrum-epic-list');

		if (Type.isUndefined(this.listData.html))
		{
			Dom.append(this.renderListBlank(), listContainer);

			Event.bind(
				listContainer.querySelector('.tasks-scrum-epics-empty-button'),
				'click',
				this.showAddForm.bind(this)
			);
		}
		else
		{
			top.BX.Runtime.html(listContainer, this.listData.html)
				.then(() => {
					top.BX.addCustomEvent(
						'Grid::beforeRequest',
						this.onBeforeGridRequest.bind(this)
					);
					this.prepareTagsList(listContainer);
				})
			;
		}
	}

	onLoadViewForm(event)
	{
		this.sidePanel = event.getSlider();

		if (this.epicFiles)
		{
			const filesContainer = this.sidePanel.getContainer().querySelector('.tasks-scrum-epic-form-files');

			Runtime.html(filesContainer, this.epicFiles);
		}
	}

	onLoadEditForm(event)
	{
		this.sidePanel = event.getSlider();

		this.form = this.sidePanel.getContainer().querySelector('.tasks-scrum-epic-form');

		const descriptionContainer = this.form.querySelector('.tasks-scrum-epic-form-description');

		if (Type.isUndefined(this.formData.html))
		{
			return;
		}

		this.renderEditor(descriptionContainer);
	}

	onSaveEditForm()
	{
		ajax.runAction(
			'bitrix:tasks.scrum.epic.editEpic',
			{
				data: this.getRequestData()
			}
		)
			.then((response: ResponseAfter) => {
				this.sidePanel.close(false, () => {
					top.BX.Event.EventEmitter.emit(this.getEventNamespace() + ':' + 'afterEdit', response.data);
				});
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	onBeforeGridRequest(gridObject, eventArgs)
	{
		/* eslint-disable */
		eventArgs.sessid = BX.bitrix_sessid();
		/* eslint-enable */

		eventArgs.method = 'POST';

		if (!eventArgs.url)
		{
			eventArgs.url = this.getListUrl();
		}

		eventArgs.data = {
			...eventArgs.data,
			groupId: this.groupId,
			gridId: this.gridId
		};
	}

	getListUrl(): string
	{
		return '/bitrix/services/main/ajax.php?action=bitrix:tasks.scrum.epic.getList';
	}

	renderAddForm(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-epic-form">
				<div class="tasks-scrum-epic-form-container">
					${this.renderNameField('', this.defaultColor)}
					${this.renderDescriptionField()}
				</div>
			</div>
		`;
	}

	renderList(): HTMLElement
	{
		return Tag.render`<div class="tasks-scrum-epic-list"></div>`;
	}

	renderViewForm(epic: EpicType): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-epic-form">
				<div class="tasks-scrum-epic-form-container">
					<div class="tasks-scrum-epic-form-header">
						<div class="tasks-scrum-epic-form-header-title">
							${Text.encode(epic.name)}
						</div>
						<div class="tasks-scrum-epic-form-header-separate"></div>
						<div class="tasks-scrum-epic-header-color">
							<div class="tasks-scrum-epic-header-color-current" style=
								"background-color: ${Text.encode(epic.color)};">
							</div>
						</div>
					</div>
					<div class="tasks-scrum-epic-form-body">
						<div class="tasks-scrum-epic-form-description">
							${epic.description}
						</div>
						<div class="tasks-scrum-epic-form-files"></div>
					</div>
				</div>
			</div>
		`;
	}

	renderEditForm(epic: EpicType): HTMLElement
	{
		this.selectedColor = epic.color;

		return Tag.render`
			<div class="tasks-scrum-epic-form">
				<div class="tasks-scrum-epic-form-container">
					${this.renderNameField(epic.name, this.selectedColor)}
					${this.renderDescriptionField()}
				</div>
			</div>
		`;
	}

	renderNameField(name: String, color: String): HTMLElement
	{
		const nameField = Tag.render`
			<div class="tasks-scrum-epic-form-header">
				<div class="tasks-scrum-epic-form-header-title">
					<input type="text" name="name" value="${Text.encode(name)}" class=
						"tasks-scrum-epic-form-header-title-control" placeholder=
						"${Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_EPIC_NAME_PLACEHOLDER')}">
				</div>
				<div class="tasks-scrum-epic-form-header-separate"></div>
				<div class="tasks-scrum-epic-header-color">
					<div class="tasks-scrum-epic-header-color-current" style=
						"background-color: ${Text.encode(color)};">
					</div>
					<div class="tasks-scrum-epic-header-color-btn-angle"></div>
				</div>
			</div>
		`;

		const pickerContainer = nameField.querySelector('.tasks-scrum-epic-header-color');

		Event.bind(pickerContainer, 'click', () => {
			const colorNode = pickerContainer.querySelector('.tasks-scrum-epic-header-color-current');
			const picker = this.getColorPicker(colorNode);
			picker.open();
		});

		return nameField;
	}

	renderDescriptionField(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-epic-form-body">
				<div class="tasks-scrum-epic-form-description"></div>
			</div>
		`;
	}

	renderEditor(container: HTMLElement)
	{
		setTimeout(() => {
			top.BX.Runtime.html(container, this.formData.html)
				.then(() => {
					if (window.top.LHEPostForm)
					{
						this.editorHandler = window.top.LHEPostForm.getHandler(this.id);

						EventEmitter.emit(this.editorHandler.eventNode, 'OnShowLHE', [true]);
					}
					this.focusToName();
				})
			;
		}, 300);
	}

	renderListBlank(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-epics-empty">
				<div class="tasks-scrum-epics-empty-first-title">
					${Loc.getMessage('TASKS_SCRUM_EPICS_EMPTY_FIRST_TITLE')}
				</div>
				<div class="tasks-scrum-epics-empty-image">
					<svg width="124px" height="123px" viewBox="0 0 124 123" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
						<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" opacity="0.28">
							<path d="M83,105 L83,81.4375 L105,81.4375 L105,18 L17,18 L17,81.4375 L39,81.4375 L39,105 L83,105 Z M10.9411765,0 L113.058824,0 C119.101468,0 124,4.85902727 124,10.8529412 L124,112.147059 C124,118.140973 119.101468,123 113.058824,123 L10.9411765,123 C4.89853156,123 0,118.140973 0,112.147059 L0,10.8529412 C0,4.85902727 4.89853156,0 10.9411765,0 Z M44.0142862,47.0500004 L54.2142857,57.4416671 L79.7142857,32 L87,42.75 L54.2142857,75 L36,57.0833333 L44.0142862,47.0500004 Z" fill="#A8ADB4" />
						</g>
					</svg>
				</div>
				<div class="tasks-scrum-epics-empty-second-title">
					${Loc.getMessage('TASKS_SCRUM_EPICS_EMPTY_SECOND_TITLE')}
				</div>
				<div class="tasks-scrum-epics-empty-button">
					<button class="ui-btn ui-btn-primary ui-btn-lg">
						${Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_EPIC_LIST_TOOLBAR_BUTTON')}
					</button>
				</div>
			</div>
		`;
	}

	getGrid()
	{
		/* eslint-disable */
		if (top.BX && top.BX.Main && top.BX.Main.gridManager)
		{
			return top.BX.Main.gridManager.getById(this.gridId);
		}
		/* eslint-enable */

		return null;
	}

	prepareTagsList(container: HTMLElement)
	{
		const tagsContainers = container.querySelectorAll('.tasks-scrum-epic-grid-tags');

		tagsContainers.forEach((tagsContainer) => {
			const tags = this.getTagsFromNode(tagsContainer);
			Dom.clean(tagsContainer);
			tags.forEach((tag) => {
				Dom.append(this.getTagNode(tag), tagsContainer);
			});
		});
	}

	getTagsFromNode(node: HTMLElement): Array
	{
		const tags = [];

		node.childNodes.forEach((childNode) => {
			tags.push(childNode.textContent.trim());
		});

		return tags;
	}

	getTagNode(tag: string): HTMLElement
	{
		const tagLabel = new Label({
			text: tag,
			color: Label.Color.TAG_LIGHT,
			fill: true,
			size: Label.Size.SM,
			customClass: ''
		});
		const container = tagLabel.getContainer();

		Event.bind(container, 'click', () => {
			this.sidePanel.close(false, () => {
				EventEmitter.emit(this.getEventNamespace() + ':' + 'filterByTag', tag);
			});
		});

		return container;
	}

	getColorPicker(colorNode)
	{
		/* eslint-disable */
		return new top.BX.ColorPicker({
			bindElement: colorNode,
			defaultColor: this.defaultColor,
			selectedColor: this.selectedColor ? this.selectedColor : this.defaultColor,
			onColorSelected: (color, picker) => {
				this.selectedColor = color;
				colorNode.style.backgroundColor = color;
			},
			popupOptions: {
				className: 'tasks-scrum-epic-color-popup'
			},
			allowCustomColor: false,
			colors: [
				['#aae9fc', '#bbecf1', '#98e1dc', '#e3f299', '#ffee95', '#ffdd93', '#dfd3b6', '#e3c6bb'],
				['#ffad97', '#ffbdbb', '#ffcbd8', '#ffc4e4', '#c4baed', '#dbdde0', '#bfc5cd', '#a2a8b0']
			]
		});
		/* eslint-enable */
	}

	focusToName()
	{
		setTimeout(() => {
			this.form.querySelector('.tasks-scrum-epic-form-header-title-control').focus();
		}, 50);
	}

	getRequestData()
	{
		const requestData = {};

		if (this.currentEpic)
		{
			requestData.epicId = this.currentEpic.id;
		}

		requestData.groupId = this.groupId;
		requestData.name = (this.form.querySelector('[name=name]').value).trim();
		requestData.description = this.editorHandler.getEditor().GetContent();
		requestData.color = this.selectedColor ? this.selectedColor : this.defaultColor;
		requestData.files = this.getAttachmentsFiles();

		return requestData;
	}

	getAttachmentsFiles(): Array
	{
		const files = [];

		if (
			!this.editorHandler
			|| !Type.isPlainObject(this.editorHandler.arFiles)
			|| !Type.isPlainObject(this.editorHandler.controllers)
		)
		{
			return files;
		}

		const fileControllers = [];
		Object.values(this.editorHandler.arFiles)
			.forEach((controller) => {
				if(!fileControllers.includes(controller))
				{
					fileControllers.push(controller);
				}
			})
		;

		fileControllers.forEach((fileController) => {
			if (
				this.editorHandler.controllers[fileController]
				&& Type.isPlainObject(this.editorHandler.controllers[fileController].values)
			)
			{
				Object.keys(this.editorHandler.controllers[fileController].values)
					.forEach((fileId) => {
						if (!files.includes(fileId))
						{
							files.push(fileId);
						}
					})
				;
			}
		});

		return files;
	}
}