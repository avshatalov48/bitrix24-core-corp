/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core_events,rpa_manager,main_popup,main_core,ui_timeline) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7,
	  _t8,
	  _t9,
	  _t10,
	  _t11,
	  _t12,
	  _t13,
	  _t14,
	  _t15,
	  _t16,
	  _t17,
	  _t18;

	/**
	 * @memberOf BX.Rpa.Timeline
	 * @mixes EventEmitter
	 */
	class Task extends ui_timeline.Timeline.Item {
	  constructor(props) {
	    super(props);
	    this.statusWait = 0;
	    this.statusYes = 1;
	    this.statusNo = 2;
	    this.statusOk = 3;
	    this.statusCancel = 4;
	    this.setEventNamespace('BX.Rpa.Timeline.Task');
	  }
	  getId() {
	    return 'task-' + this.id;
	  }
	  getTaskUsers() {
	    return this.data.users;
	  }
	  render() {
	    this.layout.container = this.renderContainer();
	    this.layout.container.appendChild(this.renderIcon());
	    this.layout.container.appendChild(this.renderContent());
	    return this.layout.container;
	  }
	  renderContainer() {
	    return main_core.Tag.render(_t || (_t = _`<div class="ui-item-detail-stream-section ui-item-detail-stream-section-task ${0}"></div>`), this.isLast ? 'ui-item-detail-stream-section-last' : '');
	  }
	  renderHeader() {
	    return main_core.Tag.render(_t2 || (_t2 = _`
			<div class="ui-item-detail-stream-content-header">
				<div class="ui-item-detail-stream-content-title">
					<span class="ui-item-detail-stream-content-title-text">${0}</span>
				</div>
			</div>`), main_core.Loc.getMessage('RPA_TIMELINE_TASKS_TITLE'));
	  }
	  renderMain() {
	    return main_core.Tag.render(_t3 || (_t3 = _`
			<div class="ui-item-detail-stream-content-detail">
				<div class="ui-item-detail-stream-content-detail-subject">
					${0}
					<div class="ui-item-detail-stream-content-detail-subject-inner">
						<a class="ui-item-detail-stream-content-detail-subject-text" href="${0}">${0}</a>
						${0}
					</div>
				</div>
				<div class="ui-item-detail-stream-content-detail-main">
					<span class="ui-item-detail-stream-content-detail-main-text">${0}</span>
				</div>
				${0}
				${0}
			</div>`), this.renderParticipants(), main_core.Text.encode(this.data.url), main_core.Text.encode(this.getTitle()), this.renderParticipantsLine(), main_core.Text.encode(this.description), this.renderTaskFields(), this.renderTaskButtons());
	  }
	  renderParticipants() {
	    let photos = this.getTaskUsers().map(({
	      id,
	      status
	    }) => {
	      return this.renderParticipantPhoto(id);
	    });
	    if (photos.length > 4) {
	      let counter = photos.length - 4;
	      photos = photos.slice(0, 4);
	      photos.push(main_core.Tag.render(_t4 || (_t4 = _`<span class="ui-item-detail-stream-content-other">
						<span class="ui-item-detail-stream-content-other-text">+${0}</span>
					</span>`), counter));
	    }
	    return main_core.Tag.render(_t5 || (_t5 = _`<div class="ui-item-detail-stream-content-employee-wrap" onclick="${0}">
				${0}
			</div>
		`), this.showParticipants.bind(this), photos);
	  }
	  renderParticipantsLine() {
	    let elements = [];
	    let taskUsers = this.getTaskUsers();
	    taskUsers.forEach(({
	      id,
	      status
	    }, i) => {
	      let node = main_core.Tag.render(_t6 || (_t6 = _`<span class="ui-item-detail-stream-content-detail-subject-resp">${0}</span>`), main_core.Text.encode(this.getTaskUserName(id)));
	      if (status > this.statusWait) {
	        node.classList.add('ui-item-detail-stream-content-detail-subject-resp-past');
	      } else if (id === this.getUserId()) {
	        node.classList.add('ui-item-detail-stream-content-detail-subject-resp-current');
	      }
	      elements.push(node);
	      if (this.data.participantJoint !== 'queue' && taskUsers.length - 1 !== i) {
	        let msg = this.data.participantJoint === 'and' ? 'RPA_TIMELINE_TASKS_SEPARATOR_AND' : 'RPA_TIMELINE_TASKS_SEPARATOR_OR';
	        elements.push(main_core.Tag.render(_t7 || (_t7 = _`<span class="ui-item-detail-stream-content-detail-subject-separator">${0}</span>`), main_core.Loc.getMessage(msg)));
	      }
	    });
	    let queueCls = this.data.participantJoint === 'queue' ? 'ui-item-detail-stream-content-detail-subject-resp-wrap-queue' : '';
	    return main_core.Tag.render(_t8 || (_t8 = _`
			<div class="ui-item-detail-stream-content-detail-subject-resp-wrap ${0}">
				${0}
			</div>
		`), queueCls, elements);
	  }
	  showParticipants(event) {
	    let taskUsers = this.getTaskUsers();
	    let users = taskUsers.map(({
	      id,
	      status
	    }, i) => {
	      let user = this.users.get(id);
	      let sep = taskUsers.length - 1 !== i ? main_core.Tag.render(_t9 || (_t9 = _`<span class="ui-item-detail-popup-item-separator">
					${0}
					</span>`), main_core.Loc.getMessage(this.data.participantJoint === 'and' ? 'RPA_TIMELINE_TASKS_SEPARATOR_AND' : 'RPA_TIMELINE_TASKS_SEPARATOR_OR')) : '';
	      let node = main_core.Tag.render(_t10 || (_t10 = _`<div class="ui-item-detail-popup-item">
					<a class="ui-item-detail-stream-content-employee"
					   ${0}
					   target="_blank"
					   title="${0}"
					   ${0}></a>
					<div class="ui-item-detail-popup-item-inner">
						<span class="ui-item-detail-popup-item-name">${0}</span>
						<span class="ui-item-detail-popup-item-position">${0}</span>
					</div>
					${0}
				</div>`), user.link ? `href="${user.link}"` : '', main_core.Text.encode(user.fullName), user.photo ? `style="background-image: url('${user.photo}'); background-size: 100%;"` : '', main_core.Text.encode(user.fullName), user.workPosition, sep);
	      if (status > this.statusWait) {
	        node.classList.add('ui-item-detail-popup-item-' + (status === this.statusOk || status === this.statusYes ? 'success' : 'fail'));
	      } else {
	        node.classList.add('ui-item-detail-popup-item-' + (id === this.getUserId() ? 'current' : 'wait'));
	      }
	      return node;
	    });
	    let content = main_core.Tag.render(_t11 || (_t11 = _`
					<div class="ui-item-detail-popup">
						${0}
					</div>`), users);
	    if (this.data.participantJoint !== 'queue') {
	      content.classList.add('ui-item-detail-popup-option');
	    }
	    let popup = new main_popup.Popup('rpa-detail-task-participant-' + this.getId(), event.target, {
	      autoHide: true,
	      draggable: false,
	      bindOptions: {
	        forceBindPosition: true
	      },
	      noAllPaddings: true,
	      closeByEsc: true,
	      cacheable: false,
	      width: 280,
	      angle: {
	        position: 'top'
	      },
	      overlay: {
	        backgroundColor: 'transparent'
	      },
	      content: content
	    });
	    popup.show();
	  }
	  renderTaskButtons() {
	    const controls = this.data.controls;
	    if (!controls) {
	      return '';
	    }
	    const elements = this.data.type === 'RpaRequestActivity' ? this.getLinkButtonElements(controls.BUTTONS) : this.getActionButtonElements(controls.BUTTONS);
	    return main_core.Tag.render(_t12 || (_t12 = _`
			<div class="ui-item-detail-stream-content-detail-status-block">
				${0}
			</div>
		`), elements);
	  }
	  getActionButtonElements(buttons) {
	    return buttons.map(button => {
	      let bgColor = button.COLOR;
	      let fgColor = rpa_manager.Manager.calculateTextColor(button.COLOR);
	      return main_core.Tag.render(_t13 || (_t13 = _`<button class="ui-btn ui-btn-sm ui-btn-default" 
					name="${0}"
					value="${0}"
					style="background-color: #${0};border-color: #${0};color:${0}"
					onclick="${0}"
					>${0}</button>
				`), button.NAME, button.VALUE, bgColor, bgColor, fgColor, this.doTaskHandler.bind(this, button), main_core.Text.encode(button.TEXT));
	    });
	  }
	  getLinkButtonElements(buttons) {
	    return [main_core.Tag.render(_t14 || (_t14 = _`<a class="ui-btn ui-btn-sm ui-btn-default ui-btn-primary" 
					href="${0}"
					>${0}</a>
			`), main_core.Text.encode(this.data.url), main_core.Loc.getMessage('RPA_TIMELINE_TASKS_OPEN_TASK'))];
	  }
	  renderTaskFields() {
	    if (!this.data.fieldsToSet) {
	      return '';
	    }
	    const elements = this.data.fieldsToSet.map(field => {
	      return main_core.Tag.render(_t15 || (_t15 = _`
					<div class="ui-item-detail-stream-content-detail-main-field-value">&ndash; ${0}</div>
				`), main_core.Text.encode(field));
	    });
	    return main_core.Tag.render(_t16 || (_t16 = _`
			<div class="ui-item-detail-stream-content-detail-main-field">
				<div class="ui-item-detail-stream-content-detail-main-field-title">${0}</div>
				<div class="ui-item-detail-stream-content-detail-main-field-value-block">
					${0}
				</div>
			</div>		
		`), main_core.Loc.getMessage('RPA_TIMELINE_TASKS_FIELDS_TO_SET'), elements);
	  }
	  getTaskUserName(id) {
	    if (!id) {
	      id = this.getUserId();
	    }
	    let userData = this.users.get(main_core.Text.toInteger(id));
	    return userData ? userData.fullName : '-?-';
	  }
	  renderParticipantPhoto(userId) {
	    userId = main_core.Text.toInteger(userId);
	    let userData = {
	      fullName: '',
	      photo: null
	    };
	    if (userId > 0) {
	      userData = this.users.get(userId);
	    }
	    if (!userData) {
	      return main_core.Tag.render(_t17 || (_t17 = _`<span></span>`));
	    }
	    const safeFullName = main_core.Text.encode(userData.fullName);
	    return main_core.Tag.render(_t18 || (_t18 = _`<span class="ui-item-detail-stream-content-employee" title="${0}" ${0}></span>`), safeFullName, userData.photo ? `style="background-image: url('${userData.photo}'); background-size: 100%;"` : '');
	  }
	  doTaskHandler(button) {
	    const ajaxData = {};
	    ajaxData[button.NAME] = button.VALUE;
	    ajaxData['taskId'] = this.id;
	    this.emit('onBeforeCompleteTask', {
	      taskId: this.id
	    });
	    main_core.ajax.runAction('rpa.task.do', {
	      analyticsLabel: 'rpaTaskDo',
	      data: ajaxData
	    }).then(response => {
	      if (response.data.completed) {
	        if (response.data.timeline) {
	          this.completedData = response.data.timeline;
	        }
	        this.onDelete();
	        this.emit('onCompleteTask', {
	          taskId: this.id
	        });
	      }
	    });
	  }
	}

	let _$1 = t => t,
	  _t$1,
	  _t2$1,
	  _t3$1,
	  _t4$1,
	  _t5$1,
	  _t6$1;
	class TaskComplete extends ui_timeline.Timeline.History {
	  renderContainer() {
	    const container = super.renderContainer();
	    container.classList.add('ui-item-detail-stream-section-history');
	    return container;
	  }
	  renderTaskInfo() {
	    let taskName = this.renderTaskName();
	    if (!taskName) {
	      taskName = '';
	    }
	    let taskResponsible = this.renderTaskResponsible();
	    if (!taskResponsible) {
	      taskResponsible = '';
	    }
	    return main_core.Tag.render(_t$1 || (_t$1 = _$1`<div class="ui-item-detail-stream-content-detail-subject">
			${0}
			<div class="ui-item-detail-stream-content-detail-subject-inner">
				${0}
				${0}
			</div>
		</div>`), this.renderHeaderUser(this.getUserId(), 30), taskName, taskResponsible);
	  }
	  renderTaskName() {
	    const task = this.getTask();
	    if (task) {
	      return main_core.Tag.render(_t2$1 || (_t2$1 = _$1`<a class="ui-item-detail-stream-content-detail-subject-text">${0}</a>`), main_core.Text.encode(task.NAME));
	    }
	    return null;
	  }
	  renderTaskResponsible() {
	    let user = this.users.get(main_core.Text.toInteger(this.getUserId()));
	    if (user) {
	      return main_core.Tag.render(_t3$1 || (_t3$1 = _$1`<span class="ui-item-detail-stream-content-detail-subject-resp">${0}</span>`), main_core.Text.encode(user.fullName));
	    }
	    return null;
	  }
	  renderMain() {
	    let taskInfo = this.renderTaskInfo();
	    let detailMain = this.renderDetailMain();
	    if (!detailMain) {
	      taskInfo.classList.add('rpa-item-detail-stream-content-detail-no-main');
	    }
	    return main_core.Tag.render(_t4$1 || (_t4$1 = _$1`<div class="ui-item-detail-stream-content-detail">
			${0}
			${0}
		</div>`), taskInfo, detailMain || '');
	  }
	  getTask() {
	    if (main_core.Type.isPlainObject(this.data.task)) {
	      return this.data.task;
	    }
	    return null;
	  }
	  renderDetailMain() {
	    const task = this.getTask();
	    let taskDescription = '';
	    if (task && task.DESCRIPTION) {
	      taskDescription = main_core.Tag.render(_t5$1 || (_t5$1 = _$1`<span class="ui-item-detail-stream-content-detail-main-text">${0}</span>`), main_core.Text.encode(task.DESCRIPTION));
	    }
	    let stageChange = this.renderStageChange();
	    let fieldsChange = this.renderFieldsChange();
	    if (taskDescription || stageChange || fieldsChange) {
	      return main_core.Tag.render(_t6$1 || (_t6$1 = _$1`<div class="ui-item-detail-stream-content-detail-main">
				${0}
				${0}
				${0}
			</div>`), taskDescription, fieldsChange ? [this.renderFieldsChangeTitle(), fieldsChange] : '', stageChange ? [this.renderStageChangeTitle(), stageChange] : '');
	    }
	    return null;
	  }
	}

	/**
	 * @memberOf BX.Rpa
	 */
	const Timeline = {
	  Task,
	  TaskComplete
	};

	exports.Timeline = Timeline;

}((this.BX.Rpa = this.BX.Rpa || {}),BX.Event,BX.Rpa,BX.Main,BX,BX.UI));
//# sourceMappingURL=timeline.bundle.js.map
