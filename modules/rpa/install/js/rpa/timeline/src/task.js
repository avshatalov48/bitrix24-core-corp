import {ajax, Tag, Text, Loc} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Timeline} from 'ui.timeline';
import {Manager} from "rpa.manager";
import {Popup} from "main.popup";

/**
 * @memberOf BX.Rpa.Timeline
 * @mixes EventEmitter
 */
export class Task extends Timeline.Item
{
	statusWait = 0;
	statusYes = 1;
	statusNo = 2;
	statusOk = 3;
	statusCancel = 4;

	constructor(props)
	{
		super(props);
		this.setEventNamespace('BX.Rpa.Timeline.Task');
	}

	getId(): string
	{
		return 'task-' + this.id;
	}

	getTaskUsers(): Array
	{
		return this.data.users;
	}

	render(): Element
	{
		this.layout.container = this.renderContainer();

		this.layout.container.appendChild(this.renderIcon());
		this.layout.container.appendChild(this.renderContent());

		return this.layout.container;
	}

	renderContainer(): Element
	{
		return Tag.render`<div class="ui-item-detail-stream-section ui-item-detail-stream-section-task ${(this.isLast ? 'ui-item-detail-stream-section-last' : '')}"></div>`;
	}

	renderHeader(): Element
	{
		return Tag.render`
			<div class="ui-item-detail-stream-content-header">
				<div class="ui-item-detail-stream-content-title">
					<span class="ui-item-detail-stream-content-title-text">${Loc.getMessage('RPA_TIMELINE_TASKS_TITLE')}</span>
				</div>
			</div>`;
	}

	renderMain(): Element
	{
		return Tag.render`
			<div class="ui-item-detail-stream-content-detail">
				<div class="ui-item-detail-stream-content-detail-subject">
					${this.renderParticipants()}
					<div class="ui-item-detail-stream-content-detail-subject-inner">
						<a class="ui-item-detail-stream-content-detail-subject-text" href="${Text.encode(this.data.url)}">${Text.encode(this.getTitle())}</a>
						${this.renderParticipantsLine()}
					</div>
				</div>
				<div class="ui-item-detail-stream-content-detail-main">
					<span class="ui-item-detail-stream-content-detail-main-text">${Text.encode(this.description)}</span>
				</div>
				${this.renderTaskFields()}
				${this.renderTaskButtons()}
			</div>`;
	}

	renderParticipants(): Element
	{
		let photos = this.getTaskUsers().map(({id, status}) => {
			return this.renderParticipantPhoto(id)
		});

		if (photos.length > 4)
		{
			let counter = photos.length - 4;
			photos = photos.slice(0, 4);

			photos.push(
				Tag.render`<span class="ui-item-detail-stream-content-other">
						<span class="ui-item-detail-stream-content-other-text">+${counter}</span>
					</span>`
			);
		}

		return Tag.render`<div class="ui-item-detail-stream-content-employee-wrap" onclick="${this.showParticipants.bind(this)}">
				${photos}
			</div>
		`;
	}

	renderParticipantsLine(): Element
	{
		let elements = [];
		let taskUsers = this.getTaskUsers();

		taskUsers.forEach(({id, status}, i) => {
			let node = Tag.render`<span class="ui-item-detail-stream-content-detail-subject-resp">${Text.encode(this.getTaskUserName(id))}</span>`;

			if (status > this.statusWait)
			{
				node.classList.add('ui-item-detail-stream-content-detail-subject-resp-past');
			}
			else if (id === this.getUserId())
			{
				node.classList.add('ui-item-detail-stream-content-detail-subject-resp-current');
			}

			elements.push(node);

			if (this.data.participantJoint !== 'queue' && taskUsers.length - 1 !== i)
			{
				let msg = this.data.participantJoint === 'and' ? 'RPA_TIMELINE_TASKS_SEPARATOR_AND' : 'RPA_TIMELINE_TASKS_SEPARATOR_OR';
				elements.push(Tag.render`<span class="ui-item-detail-stream-content-detail-subject-separator">${Loc.getMessage(msg)}</span>`);
			}
		});

		let queueCls = (
			this.data.participantJoint === 'queue'? 'ui-item-detail-stream-content-detail-subject-resp-wrap-queue' : ''
		);

		return Tag.render`
			<div class="ui-item-detail-stream-content-detail-subject-resp-wrap ${queueCls}">
				${elements}
			</div>
		`;
	}

	showParticipants(event): void
	{
		let taskUsers = this.getTaskUsers();
		let users = taskUsers.map(({id, status}, i) => {

			let user = this.users.get(id);
			let sep = (taskUsers.length -1 !== i) ?
				Tag.render`<span class="ui-item-detail-popup-item-separator">
					${Loc.getMessage(this.data.participantJoint === 'and'
					? 'RPA_TIMELINE_TASKS_SEPARATOR_AND' : 'RPA_TIMELINE_TASKS_SEPARATOR_OR')}
					</span>`
				: '';

			let node = Tag.render`<div class="ui-item-detail-popup-item">
					<a class="ui-item-detail-stream-content-employee"
					   ${user.link? `href="${user.link}"`:''}
					   target="_blank"
					   title="${Text.encode(user.fullName)}"
					   ${user.photo? `style="background-image: url('${user.photo}'); background-size: 100%;"`:''}></a>
					<div class="ui-item-detail-popup-item-inner">
						<span class="ui-item-detail-popup-item-name">${Text.encode(user.fullName)}</span>
						<span class="ui-item-detail-popup-item-position">${user.workPosition}</span>
					</div>
					${sep}
				</div>`;

			if (status > this.statusWait)
			{
				node.classList.add('ui-item-detail-popup-item-'
					+ (status === this.statusOk || status === this.statusYes ? 'success' : 'fail')
				);
			}
			else
			{
				node.classList.add('ui-item-detail-popup-item-'
					+ (id === this.getUserId() ? 'current' : 'wait')
				);
			}

			return node;
		});

		let content = Tag.render`
					<div class="ui-item-detail-popup">
						${users}
					</div>`;

		if (this.data.participantJoint !== 'queue')
		{
			content.classList.add('ui-item-detail-popup-option');
		}

		let popup = new Popup('rpa-detail-task-participant-' + this.getId(), event.target, {
			autoHide: true,
			draggable: false,
			bindOptions: { forceBindPosition: true },
			noAllPaddings: true,
			closeByEsc: true,
			cacheable: false,
			width: 280,
			angle: {
				position: 'top',
			},
			overlay: { backgroundColor: 'transparent' },
			content: content
		});

		popup.show();
	}

	renderTaskButtons(): Element|string
	{
		const controls = this.data.controls;

		if (!controls)
		{
			return '';
		}

		const elements = this.data.type === 'RpaRequestActivity'
			? this.getLinkButtonElements(controls.BUTTONS)
			: this.getActionButtonElements(controls.BUTTONS);

		return Tag.render`
			<div class="ui-item-detail-stream-content-detail-status-block">
				${elements}
			</div>
		`;
	}

	getActionButtonElements(buttons: Array)
	{
		return buttons.map(
			(button) => {
				let bgColor = button.COLOR;
				let fgColor = Manager.calculateTextColor(button.COLOR);

				return Tag.render`<button class="ui-btn ui-btn-sm ui-btn-default" 
					name="${button.NAME}"
					value="${button.VALUE}"
					style="background-color: #${bgColor};border-color: #${bgColor};color:${fgColor}"
					onclick="${this.doTaskHandler.bind(this, button)}"
					>${Text.encode(button.TEXT)}</button>
				`;
			}
		)
	}

	getLinkButtonElements(buttons: Array)
	{
		return [
			Tag.render`<a class="ui-btn ui-btn-sm ui-btn-default ui-btn-primary" 
					href="${Text.encode(this.data.url)}"
					>${Loc.getMessage('RPA_TIMELINE_TASKS_OPEN_TASK')}</a>
			`
		];
	}

	renderTaskFields(): Element
	{
		if (!this.data.fieldsToSet)
		{
			return '';
		}

		const elements = this.data.fieldsToSet.map(
			(field) => {
				return Tag.render`
					<div class="ui-item-detail-stream-content-detail-main-field-value">&ndash; ${Text.encode(field)}</div>
				`;
			}
		);

		return Tag.render`
			<div class="ui-item-detail-stream-content-detail-main-field">
				<div class="ui-item-detail-stream-content-detail-main-field-title">${Loc.getMessage('RPA_TIMELINE_TASKS_FIELDS_TO_SET')}</div>
				<div class="ui-item-detail-stream-content-detail-main-field-value-block">
					${elements}
				</div>
			</div>		
		`;
	}

	getTaskUserName(id): string
	{
		if (!id)
		{
			id = this.getUserId();
		}

		let userData = this.users.get(Text.toInteger(id));
		return userData ? userData.fullName : '-?-';
	}

	renderParticipantPhoto(userId: ?number): Element
	{
		userId = Text.toInteger(userId);
		let userData = {
			fullName: '',
			photo: null,
		};
		if(userId > 0)
		{
			userData = this.users.get(userId);
		}
		if(!userData)
		{
			return Tag.render`<span></span>`;
		}

		const safeFullName = Text.encode(userData.fullName);
		return Tag.render`<span class="ui-item-detail-stream-content-employee" title="${safeFullName}" ${userData.photo ? 'style="background-image: url(\'' + userData.photo + '\'); background-size: 100%;"' : ''}></span>`;
	}

	doTaskHandler(button)
	{
		const ajaxData = {};
		ajaxData[button.NAME] = button.VALUE;
		ajaxData['taskId'] = this.id;

		this.emit('onBeforeCompleteTask', {
			taskId: this.id,
		});

		ajax.runAction('rpa.task.do', {
			analyticsLabel: 'rpaTaskDo',
			data: ajaxData,
		}).then((response) =>
		{
			if (response.data.completed)
			{
				if(response.data.timeline)
				{
					this.completedData = response.data.timeline;
				}
				this.onDelete();
				this.emit('onCompleteTask', {
					taskId: this.id,
				});
			}
		});
	}
}