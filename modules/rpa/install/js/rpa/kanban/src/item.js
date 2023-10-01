import {Kanban} from 'main.kanban';
import {ajax as Ajax, Dom, Type, Loc, Runtime, Tag, Text, Event} from 'main.core';
import {Manager} from 'rpa.manager';
import {PopupWindowManager, PopupWindow} from 'main.popup';
import Column from './column';

export default class Item extends Kanban.Item
{
	editorResolve;
	editorReject;
	currentState = {};

	setOptions(options: { droppable?: boolean; draggable?: boolean; countable?: boolean; visible?: boolean; data?: object })
	{
		super.setOptions(options);
		this.setPermissionProperties();
	}

	render()
	{
		this.layout.title = null;
		this.renderDescription();
		this.renderFieldsList();
		this.renderShadow();

		if (!this.layout.content)
		{
			this.layout.content = Tag.render`<div ondblclick="${this.onDoubleClick.bind(this)}" class="rpa-kanban-item"></div>`;
		}
		else
		{
			Dom.clean(this.layout.content);
		}

		if (this.layout.title)
		{
			this.layout.content.appendChild(this.layout.title);
		}
		if (this.layout.fieldList)
		{
			this.layout.content.appendChild(this.layout.fieldList);
		}
		if (this.layout.description)
		{
			this.layout.content.appendChild(this.layout.description);
		}

		this.layout.content.appendChild(this.renderShadow());
		//this.layout.content.appendChild(this.renderContact());
		this.layout.description.appendChild(this.renderTasksParticipants());
		this.layout.description.appendChild(this.renderTasksCounter());

		this.layout.content.appendChild(BX.Tag.render`<div class="rpa-kanban-item-line"></div>`);
		this.layout.content.style.setProperty("--rpa-kanban-item-color", "#" + this.getColumn().getColor());

		if(this.isDraggable())
		{
			this.layout.content.style.backgroundColor = "#fff";
		}
		else
		{
			this.layout.content.style.backgroundColor = "#aaa";
		}

		Event.bindOnce(this.layout.content, "animationend", () => {
			BX.removeClass(this.layout.container, "main-kanban-item-new")
		});

		return this.layout.content;
	}

	setTasksParticipants(tasksFaces: {
		completed: [],
		running: [],
		all: [],
	}): this
	{
		this.data.tasksFaces = tasksFaces;

		return this;
	}

	getTasksCounter(): number
	{
		return Text.toInteger(this.data.tasksCounter);
	}

	getTasksParticipants()
	{
		const userId = Text.toInteger(this.getData()['createdBy']);
		const currentUserId = this.getGrid().getUserId();
		const startedBy = this.getGrid().getUser(userId);
		const faces = this.data.tasksFaces;
		const completedById = faces.completed[0];
		const waitingForId = faces.running.includes(currentUserId) ? currentUserId : faces.running[0];
		const completedCnt = faces.completed.length;
		const waitingForCnt = faces.running.length;

		let completedBy = null;
		let waitingFor = null;

		if (completedById)
		{
			completedBy = this.getGrid().getUser(Text.toInteger(completedById))
		}
		if (waitingForId)
		{
			waitingFor = this.getGrid().getUser(Text.toInteger(waitingForId))
		}

		return {startedBy, completedBy, waitingFor, completedCnt, waitingForCnt};
	}

	setTasksCounter(counter: number): Item
	{
		this.data.tasksCounter = counter;
		return this;
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

	showEditor(columnId: number): Promise<Object, Object>
	{
		Dom.addClass(this.layout.container, 'main-kanban-item-waiting');
		this.bindEditorEvents();
		return new Promise((resolve, reject) =>
		{
			Ajax.runAction('rpa.item.getEditor', {
				analyticsLabel: 'rpaItemMovedMandatoryFieldsPopupOpen',
				data: {
					typeId: this.getTypeId(),
					id: this.getId(),
					stageId: columnId > 0 ? columnId : null,
					eventId: this.getGrid().pullManager.registerRandomEventId(),
				}
			}).then((response) =>
			{
				const popup = this.getPopup();
				if(popup)
				{
					Runtime.html(popup.getContentContainer(), response.data.html).then(() =>
					{
						popup.show();
						this.editorResolve = resolve;
						this.editorReject = reject;
					});
				}
			}).catch((response) =>
			{
				Dom.removeClass(this.layout.container, 'main-kanban-item-waiting');
				reject(response.errors);
			});
		});
	}

	onEditorSaveClick()
	{
		const editor = this.getEditor();
		if(!editor)
		{
			this.getPopup().close();
			if(Type.isFunction(this.editorReject))
			{
				this.editorReject('Editor not found');
				this.editorResolve = null;
				this.editorReject = null;
			}
		}
		else
		{
			editor.save();
		}
	}

	onEditorCancelClick()
	{
		Dom.removeClass(this.layout.container, 'main-kanban-item-waiting');
		this.getPopup().close();
		if(Type.isFunction(this.editorResolve))
		{
			this.editorResolve({
				cancel: true,
			});
			this.editorResolve = null;
			this.editorReject = null;
		}
	}

	onEditorSubmit(entityData, response)
	{
		if(this.getPopup().isShown())
		{
			Dom.removeClass(this.layout.container, 'main-kanban-item-waiting');
			this.getPopup().close();
			this.setData(response.data.item);
			this.saveCurrentState();
			this.render();
		}
	}

	onEditorErrors(errors)
	{
		if(this.getPopup().isShown())
		{
			Dom.removeClass(this.layout.container, 'main-kanban-item-waiting');
			this.getPopup().close();

			if(Type.isFunction(this.editorReject))
			{
				this.editorReject({errors}, false);
				this.editorResolve = null;
				this.editorReject = null;
			}
		}
	}

	getPopup(): PopupWindow
	{
		const popupId = 'rpa-kanban-item-popup-' + this.getId();
		let popup = PopupWindowManager.getPopupById(popupId);

		if(!popup)
		{
			popup = new PopupWindow(popupId, null, {
				zIndex: 200,
				className: "",
				autoHide: false,
				closeByEsc: false,
				closeIcon: false,
				width: 600,
				overlay: true,
				lightShadow: false,
				buttons: this.getItemPopupButtons(),
			});
		}

		return popup;
	}

	getEditor()
	{
		return Manager.getEditor(this.getTypeId(), parseInt(this.getId()));
	}

	getItemPopupButtons(): Array
	{
		return [
			new BX.PopupWindowButton({
				text : Loc.getMessage('RPA_KANBAN_POPUP_SAVE'),
				className : "ui-btn ui-btn-md ui-btn-primary",
				events : {
					click : this.onEditorSaveClick.bind(this),
				}
			}),
			new BX.PopupWindowButton({
				text : Loc.getMessage('RPA_KANBAN_POPUP_CANCEL'),
				className : "ui-btn ui-btn-md",
				events : {
					click : this.onEditorCancelClick.bind(this),
				}
			})
		];
	}

	showTasks(): Promise<Object>
	{
		Dom.addClass(this.layout.container, 'main-kanban-item-waiting');
		return new Promise((resolve) =>
		{
			Manager.Instance.openTasks(this.getTypeId(), this.getId()).then((result) =>
			{
				resolve(result);
				Dom.removeClass(this.layout.container, 'main-kanban-item-waiting');
			});
		});
	}

	getStageId(): number
	{
		return Text.toInteger(this.getData().stageId);
	}

	setStageId(stageId: number): Item
	{
		this.data.stageId = stageId;
		return this;
	}

	getTypeId(): number
	{
		return Text.toInteger(this.getData().typeId);
	}

	saveCurrentState(): Item
	{
		const column = this.getColumn();
		const nextItem = column.getNextItemSibling(this);
		const previousItem = column.getPreviousItemSibling(this);
		this.data.stageId = this.getColumnId();

		this.currentState.nextItemId = nextItem ? nextItem.getId() : 0;
		this.currentState.previousItemId = previousItem ? previousItem.getId() : 0;
		this.currentState.stageId = this.data.stageId;

		return this;
	}

	savePosition(): Promise<Object, Object>
	{
		const data = {
			id: this.getId(),
			typeId: this.getTypeId(),
			fields: {
				stageId: this.getStageId(),
				previousItemId: this.currentState.previousItemId || null,
			},
			eventId: this.getGrid().pullManager.registerRandomEventId(),
		};

		Dom.addClass(this.layout.container, 'main-kanban-item-waiting');
		return new Promise((resolve, reject) =>
		{
			Ajax.runAction('rpa.item.update', {
				analyticsLabel: 'rpaItemMoved',
				data: data
			}).then((response) =>
			{
				this.data = response.data.item;
				if(!this.moveToActualColumn())
				{
					this.render();
				}
				Dom.removeClass(this.layout.container, 'main-kanban-item-waiting');
				resolve(response);
			}).catch((response) =>
			{
				reject(response);
				Dom.removeClass(this.layout.container, 'main-kanban-item-waiting');
			});
		});
	}

	moveToActualColumn(): boolean
	{
		if(this.getStageId() !== this.getColumn().getId())
		{
			const column = this.getGrid().getColumn(this.getStageId());
			if(column)
			{
				this.getGrid().moveItem(this, column, column.getFirstItem());
			}
			else
			{
				this.getGrid().moveItem(this, this.getStageId());
			}

			return true;
		}

		return false;
	}

	saveSort(): Promise<Object, Object>
	{
		const data = {
			id: this.getId(),
			typeId: this.getTypeId(),
			previousItemId: this.currentState.previousItemId,
		};

		Dom.addClass(this.layout.container, 'main-kanban-item-waiting');
		return new Promise((resolve, reject) =>
		{
			Ajax.runAction('rpa.item.sort', {
				analyticsLabel: 'rpaItemSorted',
				data: data
			}).then((response) =>
			{
				Dom.removeClass(this.layout.container, 'main-kanban-item-waiting');
				resolve(response);
			}).catch((response) =>
			{
				reject(response);
				Dom.removeClass(this.layout.container, 'main-kanban-item-waiting');
			});
		});
	}

	getCurrentState(): Object
	{
		return Object.assign({}, this.currentState);
	}

	restoreState(previousState): Item
	{
		this.currentState = previousState;
		this.data.stageId = this.currentState.stageId;

		return this;
	}

	onDoubleClick()
	{
		if(this.data.detailUrl)
		{
			Manager.openSlider(this.data.detailUrl);
		}
	}

	getMovedBy(): number
	{
		return Text.toInteger(this.getData().movedBy);
	}

	getUpdatedBy(): number
	{
		return Text.toInteger(this.getData().updatedBy);
	}

	getCreatedBy(): number
	{
		return Text.toInteger(this.getData().createdBy);
	}

	getName(): string
	{
		return this.data.name;
	}

	renderTitle(title: string): Element
	{
		if (Type.isArray(title))
		{
			title = title[0];
		}
		title = Text.encode(title);
		let href = 'javascript:void(0);';
		if(this.data.detailUrl)
		{
			href = this.data.detailUrl;
		}
		if (!this.layout.title)
		{
			this.layout.title = Tag.render`<a class="rpa-kanban-item-title" href="${href}">${title}</a>`;
		}
		else
		{
			this.layout.title.innerText = title;
		}

		return this.layout.title;
	}

	renderFieldsList(): Element
	{
		if (!this.layout.fieldList)
		{
			this.layout.fieldList = Tag.render`<div class="rpa-kanban-item-field-list"></div>`;
		}
		this.layout.fieldList.innerHTML = '';

		const fields = this.getGrid().getFields();
		Object.keys(fields).forEach((fieldName) =>
		{
			if(fields[fieldName]['isVisibleOnKanban'] && !this.isEmptyValue(this.getData()[fieldName]))
			{
				if(fields[fieldName]['isTitle'])
				{
					this.renderTitle(this.getData()[fieldName]);
				}
				else if(fieldName === 'createdBy' || fieldName === 'updatedBy' || fieldName === 'movedBy')
				{
					const renderedUser = this.renderUser(fieldName);
					if(renderedUser)
					{
						this.layout.fieldList.appendChild(Tag.render`
							<div class="rpa-kanban-item-field-item">
								<span class="rpa-kanban-item-field-item-name">${Text.encode(fields[fieldName].title)}</span>
								${renderedUser}
							</div>`
						);
					}
				}
				else
				{
					this.layout.fieldList.appendChild(Tag.render`
						<div class="rpa-kanban-item-field-item">
							<span class="rpa-kanban-item-field-item-name">${Text.encode(fields[fieldName].title)}</span>
							<span class="rpa-kanban-item-field-item-value">${this.getDisplayableValue(fieldName)}</span>
						</div>`
					);

					// field with link
					/*this.layout.fieldList.appendChild(Tag.render`
						<div class="rpa-kanban-item-field-item">
							<span class="rpa-kanban-item-field-item-name">Link</span>
							<a class="rpa-kanban-item-field-item-value-link" href="#">Bitrix Inc.</a>
						</div>`
					);*/
				}
			}
		});

		return this.layout.fieldList;
	}

	renderShadow(): Element
	{
		return Tag.render`
			<div class="rpa-kanban-item-shadow"></div>
		`;
	}

	renderDescription(): Element
	{
		this.layout.description = Tag.render`
			<div class="rpa-kanban-item-description"></div>
		`;
	}

	renderUserPhoto({
		link,
		photo,
	}): ?Element
	{
		if(Type.isString(link) && Type.isString(photo))
		{
			return Tag.render`<a class="rpa-kanban-item-user-photo" href="${Text.encode(link)}" style="background-image: url(${Text.encode(photo)})"></a>`;
		}

		return null;
	}

	renderUser(fieldName: string): ?Element
	{
		const userId = Text.toInteger(this.getData()[fieldName]);
		const userInfo = this.getGrid().getUser(userId);

		if(userInfo)
		{
			const photo = this.renderUserPhoto(userInfo);

			return Tag.render`<div class="rpa-kanban-item-user">
				${(photo) ? photo : ''}
				<a class="rpa-kanban-item-user-name rpa-kanban-item-field-item-value" href="${Text.encode(userInfo.link)}">${Text.encode(userInfo.fullName)}</a>
			</div>`;
		}

		return null;
	}

	renderContact(): Element
	{
		return Tag.render`
				<div class="rpa-kanban-item-contact">
					<span class="rpa-kanban-item-contact-im"></span>
				</div>
		`;
	}

	renderTasksParticipants(): Element
	{
		const {startedBy, completedBy, waitingFor, completedCnt, waitingForCnt} = this.getTasksParticipants();
		const elements = [];

		if (startedBy)
		{
			elements.push(this.renderTaskParticipant(startedBy));
		}
		if (completedBy)
		{
			elements.push(this.renderTaskParticipant(completedBy, completedCnt > 1));
		}
		if (waitingFor)
		{
			elements.push(this.renderTaskParticipant(waitingFor, waitingForCnt > 1));
		}

		return Tag.render`
				<div class="rpa-kanban-column-task-responsible-list">
					${elements}
				</div>
		`;
	}

	renderTaskParticipant({link, photo, fullName}, isMore): Element
	{
		return Tag.render`
			<a class="rpa-kanban-column-task-responsible-item ${isMore? 'rpa-kanban-column-task-responsible-item-more':''}" 
			 href="${Text.encode(link)}" title="${Text.encode(fullName)}">
				<span class="rpa-kanban-column-task-responsible-img" ${photo ? 'style="background-image: url(\'' + Text.encode(encodeURI(photo)) + '\')"' : ''}>	
				</span>
			</a>
		`;
	}

	renderTasksCounter(): Element
	{
		return Tag.render`
				<div class="rpa-kanban-item-counter" onclick="${this.showTasks.bind(this)}" ${(this.getTasksCounter() <= 0 ? 'style="display: none;"' : '')}>
					<div class="rpa-kanban-item-counter-text">${Loc.getMessage('RPA_KANBAN_TASKS')}</div>
					<div class="rpa-kanban-item-counter-value">${this.getTasksCounter()}</div>
				</div>
		`;
	}

	hasEmptyMandatoryFields(column: Column): boolean
	{
		let result = false;
		if(!column)
		{
			column = this.getStageId();
		}
		column = this.getGrid().getColumn(column);
		if(!column)
		{
			throw new Error("Column not found");
		}
		const fields = column.getFields();
		Object.keys(fields).forEach((fieldName) =>
		{
			if(fields[fieldName].mandatory && this.isEmptyValue(this.getData()[fieldName]))
			{
				result = true;
			}
		});

		return result;
	}

	isEmptyValue(value): boolean
	{
		return (
			Type.isNil(value) ||
			value === false ||
			((Type.isString(value) || Type.isArray(value)) && value.length <= 0) ||
			(Type.isNumber(value) && value === 0)
		);
	}

	update(data: Object): Item
	{
		if(
			Type.isPlainObject(data) &&
			data.item &&
			Type.isPlainObject(data.item) &&
			parseInt(data.item.id) === this.getId()
		)
		{
			this.data = data.item;
			this.processPermissions();
			this.render();
		}

		return this;
	}

	processPermissions(): Item
	{
		this.setPermissionProperties();
		this.processDraggingOptions();

		return this;
	}

	setPermissionProperties(): Item
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

		return this;
	}

	processDraggingOptions(): Item
	{
		if(this.isDraggable())
		{
			this.makeDraggable();
		}
		else
		{
			this.disableDragging();
		}
		this.render();

		return this;
	}

	getDisplayableValue(fieldName: string): ?string
	{
		let result = null;
		if(this.data.display && this.data.display[fieldName])
		{
			result = this.data.display[fieldName];
		}
		else if(this.data[fieldName])
		{
			result = this.data[fieldName];
		}

		if(Type.isArray(result))
		{
			result = result.join(', ');
		}

		return result;
	}

	isDeletable(): boolean
	{
		return (this.canDelete !== false);
	}
}