import {Kanban} from 'main.kanban';
import {Kanban as RpaKanban} from 'rpa.kanban';
import {Tag, Uri, Type, ajax as Ajax, Runtime, Loc, Dom, Event, Text} from 'main.core';
import {Manager} from 'rpa.manager';
import {MessageBox, MessageBoxButtons} from 'ui.dialogs.messagebox';

import {Popup} from 'main.popup';

export default class Column extends Kanban.Column
{
	canMoveFrom;

	getId()
	{
		return parseInt(super.getId());
	}

	setOptions(options)
	{
		super.setOptions(options);
		this.canMoveFrom = !!options.canMoveFrom;
		this.setPermissionProperties();
	}

	isDroppable(): boolean
	{
		return super.isDroppable() || this.canMoveTo();
	}

	isFirstColumn()
	{
		return (this.data.isFirst === true);
	}

	setIsFirstColumn(isFirst: boolean = false): Column
	{
		this.data.isFirst = isFirst;

		return this;
	}

	onAfterRender()
	{
		if(!this.isDroppable())
		{
			this.getContainer().style.backgroundColor = 'rgba(204, 204, 204, 0.2)';
		}
		else
		{
			this.getContainer().style.backgroundColor = 'transparent';
		}
	}

	rerenderSubtitle()
	{
		const nodeNames = [
			'responsible',
			'subTitleTasksButton',
			'subTitleTasks',
			'subTitleAddTaskButton',
			'subTitleSettingsButton',
			'subTitleAddButton',
		];
		nodeNames.forEach((nodeName) =>
		{
			Dom.clean(this.layout[nodeName]);
			this.layout[nodeName] = null;
		});
		Dom.clean(this.layout.subtitleNode);
		if(this.tasksPopup)
		{
			this.tasksPopup.destroy();
		}
		this.renderSubTitle();
	}

	renderSubTitle(): HTMLElement
	{
		const subTitleNode = this.getSubTitleNode();
		if(this.isEditable())
		{
			const tasks = this.getTasks();
			const robotsCnt = this.getData()['robotsCount'];
			if (tasks && tasks.length > 0)
			{
				subTitleNode.appendChild(this.renderSubTitleTasks(tasks));
			}
			else
			{
				if (!this.isFirstColumn() || this.isFirstColumn() && !robotsCnt)
				{
					subTitleNode.appendChild(this.renderSubTitleAddTaskButton());
				}
			}
		}
		if(this.isFirstColumn() && this.canAddItems())
		{
			subTitleNode.appendChild(this.renderSubTitleAddButton());
		}
		else
		{
			if(this.layout.subTitleAddButton)
			{
				Dom.remove(this.layout.subTitleAddButton);
				this.layout.subTitleAddButton = null;
			}
		}

		return subTitleNode;
	}

	getSubTitleNode()
	{
		if(!this.layout.subtitleNode)
		{
			this.layout.subtitleNode = Tag.render`<div class="main-kanban-column-subtitle-box"></div>`;
		}

		return this.layout.subtitleNode;
	}

	getContainer(): HTMLElement
	{
		const container = super.getContainer();

		if(this.isFirstColumn() && this.canAddItems())
		{
			const quickFormContainer = this.renderQuickFormContainer();
			const itemsContainer = this.getItemsContainer();
			if(quickFormContainer && itemsContainer)
			{
				if(quickFormContainer.parentNode !== itemsContainer)
				{
					Dom.prepend(quickFormContainer, itemsContainer);
				}
			}
		}

		return container;
	}

	//region quick form
	renderSubTitleAddButton()
	{
		if(!this.layout.subTitleAddButton)
		{
			this.layout.subTitleAddButton = Tag.render`<div class="main-kanban-column-add-item-button" onclick="${this.handleAddItemButtonClick.bind(this)}"></div>`
		}

		return this.layout.subTitleAddButton;
	}

	renderQuickFormContainer()
	{
		if(!this.layout.quickFormContainer)
		{
			let className = 'rpa-kanban-form';
			if(this.getGrid().canAddColumns())
			{
				className += ' rpa-kanban-form-with-settings';
			}
			this.layout.quickFormContainer = Tag.render`<div class="${className}"></div>`
		}

		return this.layout.quickFormContainer;
	}

 	renderQuickFormButtons()
	{
		if(!this.layout.quickFormButtons)
		{
			this.layout.quickFormButtons = Tag.render`<div class="rpa-kanban-form-buttons">
				<button class="ui-btn ui-btn-sm ui-btn-primary" onclick="${this.handleFormSaveButtonClick.bind(this)}">${Loc.getMessage('RPA_KANBAN_QUICK_FORM_SAVE_BUTTON')}</button>
				<button class="ui-btn ui-btn-sm ui-btn-link" onclick="${this.handleFormCancelButtonClick.bind(this)}">${Loc.getMessage('RPA_KANBAN_QUICK_FORM_CANCEL_BUTTON')}</button>
			</div>`
		}

		return this.layout.quickFormButtons;
	}

	handleAddItemButtonClick()
	{
		if(this.getGrid().isProgress())
		{
			return;
		}
		if(this.getGrid().isCreateItemRestricted())
		{
			Manager.Instance.showFeatureSlider();
			return;
		}
		if(this.isFormVisible())
		{
			return;
		}
		this.getGrid().startProgress();
		Ajax.runAction('rpa.item.getEditor', {
			analyticsLabel: 'rpaItemOpenQuickForm',
			data: {
				typeId: this.getGrid().getTypeId(),
				id: 0,
				stageId: this.getId(),
				eventId: this.getGrid().pullManager.registerRandomEventId(),
			}
		}).then((response) =>
		{
			this.getGrid().stopProgress();
			Runtime.html(this.layout.quickFormContainer, response.data.html).then(() =>
			{
				this.addSelectButtonToEditor();
				Dom.append(this.renderQuickFormButtons(), this.layout.quickFormContainer);
				this.showForm();
				this.bindKeyDownEvents();
			});
		}).catch((response) =>
		{
			this.getGrid().stopProgress();
			this.getGrid().showErrorFromResponse(response);
		});
	}

	showForm()
	{
		this.getBody().scrollTop = 0;
		this.layout.quickFormContainer.style.display = 'block';
		this.layout.quickFormButtons.style.display = 'block';
	}

	hideForm()
	{
		this.layout.quickFormContainer.style.display = 'none';
		this.layout.quickFormButtons.style.display = 'none';
	}

	isFormVisible()
	{
		return (
			this.layout.quickFormContainer.style.display === 'block' &&
			this.layout.quickFormButtons.style.display === 'block'
		);
	}

	getEditor()
	{
		return Manager.getEditor(this.getGrid().getTypeId(), 0);
	}

	handleFormCancelButtonClick()
	{
		const editor = this.getEditor();
		if(editor)
		{
			editor.rollback();
			editor.refreshLayout();
		}
		this.hideForm();
	}

	handleFormSaveButtonClick()
	{
		const editor = this.getEditor();
		if(editor)
		{
			editor.save();
			this.bindEditorEvents();
		}
		else
		{
			this.hideForm();
		}
	}

	onEditorSubmit(entityData, response)
	{
		if(this.isFormVisible())
		{
			this.hideForm();
			const itemData = response.data.item;
			this.getGrid().addUsers(response.data.item.users);

			const oldItem = this.getGrid().getItem(itemData.id);
			if(oldItem)
			{
				return;
			}
			const item = new RpaKanban.Item({
				id: itemData.id,
				columnId: itemData.stageId,
				name: itemData.name,
				data: itemData,
			});
			item.setGrid(this.getGrid());
			this.getGrid().items[item.getId()] = item;

			const column = this.getGrid().getColumn(item.getStageId());
			if(column)
			{
				column.addItem(item, column.getFirstItem());
			}
		}
	}

	onEditorErrors(errors)
	{
		if(this.isFormVisible())
		{
			this.hideForm();

			this.getGrid().showErrorFromResponse(errors);
		}
	}

	bindEditorEvents()
	{
		if(!this.isEditorEventsBinded)
		{
			this.isEditorEventsBinded = true;

			BX.addCustomEvent(window, 'BX.UI.EntityEditorAjax:onSubmitFailure', this.onEditorErrors.bind(this));
			BX.addCustomEvent(window, 'BX.UI.EntityEditorAjax:onSubmit', this.onEditorSubmit.bind(this));
		}
	}

	bindKeyDownEvents()
	{
		if(!this.isKeyDownEventsBinded)
		{
			this.isKeyDownEventsBinded = true;

			const onEnterKeyDown = (event) =>
			{
				if(
					(event.code === 'Enter' || event.code === 'NumpadEnter') &&
					this.isFormVisible()
				)
				{
					this.handleFormSaveButtonClick();
				}
			};

			const isCtrlKey = function(code: string)
			{
				return (
					code === 'MetaRight' ||
					code === 'MetaLeft' ||
					code === 'ControlRight' ||
					code === 'ControlLeft'
				);
			};

			Event.bind(window, 'keydown', (event) =>
			{
				if(isCtrlKey(event.code))
				{
					Event.bind(window, 'keydown', onEnterKeyDown);
				}
				else if(event.code === 'Escape')
				{
					this.handleFormCancelButtonClick();
				}
			});

			Event.bind(window, 'keyup', (event) =>
			{
				if(isCtrlKey(event.code))
				{
					Event.unbind(window, 'keydown', onEnterKeyDown);
				}
			});
		}
	}

	//endregion

	//region settings
	renderSubTitleSettingsButton()
	{
		if(!this.layout.subTitleSettingsButton)
		{
			this.layout.subTitleSettingsButton = Tag.render`
			<div class="main-kanban-column-settings-button" onclick="${this.openSettings.bind(this)}">
				<button class="ui-btn ui-btn-xs ui-btn-link ui-btn-icon-setting"></button>
			</div>`
		}

		return this.layout.subTitleSettingsButton;
	}

	openSettings()
	{
		const url = this.data.settingsUrl;
		if(url)
		{
			Manager.openSlider(url).then((slider) =>
			{
				const response = slider.getData().get('response');
				if(response)
				{
					this.update(response.data);
				}
			});
		}
	}
	//endregion

	//region task
	renderSubTitleAddTaskButton()
	{
		if(!this.layout.subTitleAddTaskButton)
		{
			const url = this.buildAddRobotUrl();
			this.layout.subTitleAddTaskButton =
				Tag.render`
					<div class="main-kanban-column-settings-button">
						<a class="ui-btn ui-btn-xs ui-btn-light-border ui-btn-no-caps ui-btn-round ui-btn-themes main-kanban-column-settings-button-rpa" href="${url}">
							${Loc.getMessage('RPA_KANBAN_COLUMN_ADD_TASK_BTN')}
						</a>
					</div>
				`;
		}

		return this.layout.subTitleAddTaskButton;
	}

	renderSubTitleTasks(tasks): Element
	{
		if(!this.layout.subTitleTasks)
		{
			this.layout.subTitleTasks =
				Tag.render`
					<div class="rpa-kanban-column-task-block">
						<div class="rpa-kanban-column-task-inner">
							${this.renderSubTitleResponsible(tasks, true)}
							${this.renderSubTitleTasksButton(tasks)}
						</div>
					</div>
				`;
		}

		return this.layout.subTitleTasks;
	}

	renderSubTitleTasksButton(tasks): Element
	{
		if(!this.layout.subTitleTasksButton)
		{
			this.layout.subTitleTasksButton =
				Tag.render`
					<div class="rpa-kanban-column-task-btn" onclick="${this.showTasks.bind(this, tasks)}">
						<span class="rpa-kanban-column-task-btn-title">${Loc.getMessage('RPA_KANBAN_TASKS')}</span>
						<span class="rpa-kanban-column-task-btn-counter">${tasks.length}</span>
					</div>
				`;
		}

		return this.layout.subTitleTasksButton;
	}

	renderSubTitleResponsible(tasks, showTaskListMenu): Element
	{
		let responsibleElements = [];
		const plusHandler = this.showTasks.bind(this, tasks);

		tasks.forEach((task) => {
			task.users.forEach((user) => {
				let style = 'border-color: #' + this.getColor() + ';';
				if (user.photoSrc)
				{

					style += ' background-image: url(\'' + Text.encode(encodeURI(user.photoSrc)) + '\');';
				}
				else
				{
					style += ' background-size: 60%;';
				}
				responsibleElements.push(Tag.render`<span class="rpa-kanban-column-task-responsible-item" title="${user.name}">
					<span class="rpa-kanban-column-task-responsible-img" style="${style}">
					</span>
				</span>`);
			});
		});

		responsibleElements = this.sliceResponsibleListElements(responsibleElements);

		let plusNode = Tag.render`<span class="rpa-kanban-column-task-responsible-add"></span>`;
		if (showTaskListMenu)
		{
			BX.bind(plusNode, 'click', plusHandler);
		}
		else
		{
			let task = tasks[0];

			if (task.canAppendResponsibles)
			{
				BX.Bizproc.UserSelector.decorateNode(plusNode, {
					isOnlyDialogMode: true,
					callbacks: {
						select: this.addTaskUserHandler.bind(this, task)
					}
				});
			}
			else
			{
				plusNode = Tag.render`<a href="${this.buildEditRobotUrl(task['robotName'])}" class="rpa-kanban-column-task-responsible-add"></a>`;
			}
		}

		return Tag.render`
				<div class="rpa-kanban-column-task-responsible">
					<div class="rpa-kanban-column-task-responsible-list" style="background-color: ${"#" + this.getColor()}" onclick="${plusHandler}">
						${responsibleElements}
					</div>
					${plusNode}
				</div>
			`;
	}

	sliceResponsibleListElements(elements: Array): Array
	{
		if (elements.length > 4)
		{
			let counter = elements.length - 4;
			elements = elements.slice(0, 4);

			elements.push(
				Tag.render`<span class="rpa-kanban-column-task-responsible-item rpa-kanban-column-task-responsible-item-other">
						<span class="rpa-kanban-column-task-responsible-other-text">+${counter}</span>
					</span>`
			);
		}
		return elements;
	}

	showTasks(tasks)
	{
		this.getTasksPopup(tasks).show();
	}

	addTaskUserHandler(task, value, selector)
	{
		Ajax.runAction('rpa.task.addUser', {
			analyticsLabel: 'rpaTaskAddUser',
			data: {
				typeId: this.getGrid().getData().typeId,
				stageId: this.getId(),
				robotName: task.robotName,
				userValue: value,
			},
			getParameters: {
				context: 'kanban',
			}
		}).then((response) =>
		{

		});
	}

	getTasksPopup(tasks): Popup
	{
		if(!this.tasksPopup)
		{
			let button = this.layout.subTitleTasksButton;
			if(!button)
			{
				button = this.renderSubTitleTasksButton(this.getTasks());
			}
			this.tasksPopup = new Popup('rpa-tasks-' + this.getId(), button, {
				autoHide: true,
				draggable: false,
				offsetTop: -5,
				offsetLeft: 30,
				noAllPaddings: true,
				bindOptions: { forceBindPosition: true },
				closeByEsc: true,
				cacheable: false,
				angle: {
					offset: 81,
					position: 'top',
				},
				events: {
					onPopupDestroy: () =>
					{
						this.tasksPopup = null;
					}
				},
				overlay: { backgroundColor: 'transparent' },
				content: this.renderTasksPopup(tasks)
			});
		}

		return this.tasksPopup;
	}

	renderTasksPopup(tasks): Element
	{
		const elements = tasks.map((task) => {
			return Tag.render`<div class="rpa-kanban-tasks-popup-item">
						<a href="${this.buildEditRobotUrl(task['robotName'])}" class="rpa-kanban-tasks-popup-name">${Text.encode(task['title'])}</a>
						<div class="rpa-kanban-tasks-popup-desc">
							${this.renderSubTitleResponsible([task])}
							<span class="rpa-kanban-tasks-popup-delete" onclick="${this.deleteTaskHandler.bind(this, task)}">${Loc.getMessage('RPA_KANBAN_COLUMN_DELETE_TASK_BTN')}</span>
						</div>
					</div>`;
		});

		return Tag.render`
			<div class="rpa-kanban-tasks-popup-inner" data-role="rpa-kanban-column-tasks-item">
				<div class="rpa-kanban-tasks-popup-list">
					${elements}
				</div>
				<a href="${this.buildAddRobotUrl()}" class="rpa-kanban-tasks-popup-add">${Loc.getMessage('RPA_KANBAN_COLUMN_ADD_TASK_BTN')}</a>
			</div>`;
	}

	deleteTaskHandler(task, event)
	{
		MessageBox.show({
			message: Loc.getMessage('RPA_KANBAN_COLUMN_DELETE_TASK_CONFIRM'),
			buttons: MessageBoxButtons.OK_CANCEL,
			onOk: () => {
				if (this.getGrid().isProgress())
				{
					return;
				}
				this.getGrid().startProgress();
				const promise = new BX.Promise();
				Ajax.runAction('rpa.task.delete', {
					analyticsLabel: 'rpaKanbanTaskDelete',
					data: {
						typeId: this.getGrid().getData().typeId,
						stageId: this.getId(),
						robotName: task['robotName'],
						eventId: this.getGrid().pullManager.registerRandomEventId(),
					},
					getParameters: {
						context: 'kanban',
					}
				}).then(() =>
				{
					if(this.tasksPopup)
					{
						this.tasksPopup.destroy();
					}
					this.setTasks(this.getTasks().filter((filteredTask) =>
					{
						return (filteredTask.robotName !== task.robotName);
					}));
					this.rerenderSubtitle();
					this.getGrid().stopProgress();
					promise.fulfill();
				}).catch((response) =>
				{
					this.getGrid().stopProgress();
					promise.reject();
				});

				return promise;
			},
			popupOptions: {
				zIndexAbsolute: 1200
			}
		});
	}

	buildAddRobotUrl()
	{
		const typeId = this.getGrid().getData().typeId;
		const url = new Uri(`/rpa/automation/${typeId}/addrobot/`); //TODO use URI from urlManager

		url.setQueryParams({
			stage: this.getId()
		});
		return url;
	}
	buildEditRobotUrl(robotName)
	{
		const typeId = this.getGrid().getData().typeId;
		const url = new Uri(`/rpa/automation/${typeId}/editrobot/`); //TODO use URI from urlManager

		url.setQueryParams({
			stage: this.getId(),
			robotName
		});
		return url;
	}
	//endregion

	getFields(): Object
	{
		let fields = this.getData().userFields;
		if(!fields || !Type.isPlainObject(fields))
		{
			fields = this.getGrid().getFields();
		}
		if(!fields || !Type.isPlainObject(fields))
		{
			fields = {};
		}

		return fields;
	}

	getPossibleNextStages()
	{
		return this.getData().possibleNextStages;
	}

	getSort()
	{
		return parseInt(this.getData().sort);
	}

	isCanMoveFrom()
	{
		return !!this.canMoveFrom;
	}

	canMoveTo()
	{
		let result = false;
		this.getGrid().getColumns().forEach((column) =>
		{
			if(column.isCanMoveFrom() && column.getPossibleNextStages().includes(this.getId()))
			{
				result = true;
			}
		});

		return result;
	}

	update(data)
	{
		if(
			Type.isPlainObject(data) &&
			data.stage &&
			Type.isPlainObject(data.stage) &&
			parseInt(data.stage.id) === this.getId()
		)
		{
			const stageData = data.stage;
			this.setName(stageData.name);
			this.setColor(stageData.color);
			this.setData(stageData);
			this.processPermissions(stageData);

			this.getGrid().moveColumn(this, this.getTargetColumn());
			this.render();

			this.getGrid().getColumns().forEach((column) =>
			{
				if(column !== this)
				{
					column.processPermissions();
					column.onAfterRender();
				}
			});
		}
	}

	getTargetColumn()
	{
		const columns = this.getGrid().getColumns();
		let targetColumn = null;
		columns.forEach((gridColumn) =>
		{
			if(
				gridColumn.getId() !== this.getId() &&
				gridColumn.getSort() >= this.getSort() &&
				((!targetColumn) || (targetColumn.getSort() > gridColumn.getSort()))
			)
			{
				targetColumn = gridColumn;
			}
		});

		return targetColumn;
	}

	processPermissions()
	{
		this.setPermissionProperties();
		this.processDraggingOptions();
	}

	setPermissionProperties()
	{
		const data = this.getData();
		let permissions = {};
		if(data.permissions && Type.isPlainObject(data.permissions))
		{
			permissions = data.permissions;
		}

		Object.keys(permissions).forEach((name) =>
		{
			this[name] = permissions[name];
		});
	}

	processDraggingOptions()
	{
		if(this.isDraggable())
		{
			this.makeDraggable();
		}
		else
		{
			this.disableDragging();
		}
		if(this.isDroppable())
		{
			this.makeDroppable();
		}
		else
		{
			this.disableDropping();
		}

		this.getItems().forEach((item) =>
		{
			item.processPermissions();
		});
	}

	loadTasks(): Promise<null, null>
	{
		return new Promise((resolve, reject) =>
		{
			this.getGrid().startProgress();
			Ajax.runAction('rpa.stage.getTasks', {
				data: {
					id: this.getId(),
				}
			}).then((response) =>
			{
				this.getGrid().stopProgress();
				this.setTasks(response.data.tasks);
				resolve();
			}).catch((response) =>
			{
				this.getGrid().stopProgress().showErrorFromResponse(response);
				reject();
			});
		});
	}

	getTasks(): Array
	{
		if(!this.data)
		{
			this.data = {};
		}
		if(!this.data.tasks || !Type.isArray(this.data.tasks))
		{
			this.data.tasks = [];
		}
		return Array.from(this.data.tasks);
	}

	setTasks(tasks: Array): Column
	{
		if(!Type.isArray(tasks))
		{
			tasks = [];
		}

		this.data.tasks = tasks;

		return this;
	}

	addSelectButtonToEditor()
	{
		const editor = this.getEditor();
		if(!editor)
		{
			return;
		}

		let editorMainSection = this.getGrid().getEditorMainSection(editor);
		if(!editorMainSection)
		{
			return;
		}

		if(editorMainSection._addChildButton)
		{
			return;
		}

		editorMainSection.ensureButtonPanelCreated();
		editorMainSection._addChildButton = BX.create("span",
			{
				props: { className: "ui-entity-editor-content-add-lnk" },
				text: BX.message("UI_ENTITY_EDITOR_SELECT_FIELD"),
				events: { click: BX.delegate(editorMainSection.onAddChildBtnClick, editorMainSection) }
			});
		editorMainSection.addButtonElement(editorMainSection._addChildButton, { position: "left" });
	}
}