/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_vue3,main_popup,main_core,main_core_events,ui_entitySelector,ui_notification,main_date,crm_timeline_tools,crm_activity_fileUploader,crm_activity_settingsPopup) {
	'use strict';

	const TodoEditorActionBtn = {
	  props: {
	    icon: {
	      type: String,
	      required: true,
	      default: ''
	    },
	    action: {
	      type: Function,
	      required: true,
	      default: () => {}
	    },
	    description: {
	      type: String,
	      required: false,
	      default: ''
	    }
	  },
	  data() {
	    return {
	      popup: null
	    };
	  },
	  computed: {
	    iconClassname() {
	      return ['crm-activity__todo-editor_action-btn-icon', `--${this.icon}`];
	    }
	  },
	  methods: {
	    onMouseEnter(event) {
	      if (!this.description) {
	        return;
	      }
	      this.popup = new main_popup.Popup({
	        content: this.description,
	        bindElement: event.target,
	        darkMode: true
	      });
	      setTimeout(() => {
	        if (!this.popup) {
	          return;
	        }
	        this.popup.show();
	      }, 400);
	    },
	    onMouseLeave() {
	      if (!this.popup || !this.description) {
	        return;
	      }
	      this.popup.close();
	      this.popup = null;
	    },
	    onButtonClick() {
	      this.action.call(this);
	    }
	  },
	  template: `
		<button
			@mouseenter="onMouseEnter"
			@mouseleave="onMouseLeave"
			@click="onButtonClick"
			class="crm-activity__todo-editor_action-btn"
		>
			<i :class="iconClassname"></i>
		</button>
	`
	};

	const TodoEditorActionDelimiter = {
	  computed: {
	    className() {
	      return ['crm-activity__todo-editor_action-delimiter'];
	    }
	  },
	  template: `
		<span :class="className">
			<i class="crm-activity__todo-editor_action-delimiter-icon"></i>
		</span>
	`
	};

	const TodoEditorResponsibleUserSelector = {
	  props: {
	    userId: {
	      type: Number,
	      required: true,
	      default: 0
	    },
	    userName: {
	      type: String,
	      required: true,
	      default: ''
	    },
	    imageUrl: {
	      type: String,
	      required: true,
	      default: ''
	    }
	  },
	  data() {
	    return {
	      isTickFlipped: false,
	      userAvatarUrl: this.imageUrl
	    };
	  },
	  computed: {
	    userIconClassName() {
	      return ['ui-icon', 'ui-icon-common-user', 'crm-timeline__user-icon'];
	    },
	    tickIconClassName() {
	      return ['crm-activity__todo-editor_action-user-selector-tick', 'crm-activity__todo-editor_action-user-selector-tick-icon', {
	        '--flipped': this.isTickFlipped
	      }];
	    },
	    userIconStyles() {
	      if (!this.userAvatarUrl) {
	        return {};
	      }
	      return {
	        backgroundImage: "url('" + encodeURI(main_core.Text.encode(this.userAvatarUrl)) + "')",
	        backgroundSize: '21px'
	      };
	    }
	  },
	  methods: {
	    onDialogShow(event) {
	      this.isTickFlipped = true;
	    },
	    onDialogHide(event) {
	      this.isTickFlipped = false;
	    },
	    onSelectUser(event) {
	      const selectedItem = event.getData().item.getDialog().getSelectedItems()[0];
	      if (selectedItem) {
	        this.userAvatarUrl = selectedItem.getAvatar();
	        this.$Bitrix.eventEmitter.emit(Events.EVENT_RESPONSIBLE_USER_CHANGE, {
	          responsibleUserId: selectedItem.getId()
	        });
	      }
	    },
	    onDeselectUser() {
	      setTimeout(() => {
	        const selectedItems = this.userSelectorDialog.getSelectedItems();
	        if (selectedItems.length === 0) {
	          this.userAvatarUrl = this.imageUrl;
	          this.userSelectorDialog.hide();
	          this.$Bitrix.eventEmitter.emit(Events.EVENT_RESPONSIBLE_USER_CHANGE, {
	            responsibleUserId: this.userId
	          });
	        }
	      }, 100);
	    },
	    showUserDialog() {
	      if (this.userSelectorDialog) {
	        setTimeout(() => {
	          this.userSelectorDialog.show();
	        }, 5);
	      }
	    },
	    resetToDefault() {
	      this.userAvatarUrl = this.imageUrl;
	      if (this.userSelectorDialog) {
	        const defaultUserItem = this.userSelectorDialog.getItem({
	          id: this.userId,
	          entityId: 'user'
	        });
	        if (defaultUserItem) {
	          defaultUserItem.select(true);
	        }
	      }
	    }
	  },
	  mounted() {
	    this.userSelectorDialog = new ui_entitySelector.Dialog({
	      id: 'responsible-user-selector-dialog',
	      targetNode: this.$refs.userSelector,
	      context: 'CRM_ACTIVITY_TODO_RESPONSIBLE_USER',
	      multiple: false,
	      dropdownMode: true,
	      showAvatars: true,
	      enableSearch: true,
	      width: 450,
	      zIndex: 2500,
	      entities: [{
	        id: 'user'
	      }],
	      preselectedItems: [['user', this.userId]],
	      undeselectedItems: [['user', this.userId]],
	      events: {
	        'onShow': this.onDialogShow,
	        'onHide': this.onDialogHide,
	        'Item:onSelect': this.onSelectUser,
	        'Item:onDeselect': this.onDeselectUser
	      }
	    });
	  },
	  template: `
		<div 
			class="crm-activity__todo-editor_responsible-user-selector"
			ref="userSelector"
			@click="showUserDialog"
		>
			<span :class="userIconClassName">
				<i :style="userIconStyles"></i>
			</span>
			<span 
				:class="tickIconClassName"
				ref="tickIcon"
			>
			</span>
		</div>
	`
	};

	const TEXTAREA_MAX_HEIGHT = 126;
	const Events = {
	  EVENT_RESPONSIBLE_USER_CHANGE: 'crm:timeline:todo:responsible-user-changed'
	};
	const TodoEditor = {
	  components: {
	    TodoEditorActionBtn,
	    TodoEditorActionDelimiter,
	    TodoEditorResponsibleUserSelector
	  },
	  props: {
	    onFocus: Function,
	    onChangeDescription: Function,
	    onSaveHotkeyPressed: Function,
	    deadline: Date,
	    defaultDescription: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    additionalButtons: Array,
	    popupMode: Boolean,
	    currentUser: Object
	  },
	  data() {
	    var _this$deadline;
	    return {
	      description: this.defaultDescription,
	      currentDeadline: (_this$deadline = this.deadline) !== null && _this$deadline !== void 0 ? _this$deadline : new Date(),
	      responsibleUserId: this.currentUser.userId,
	      showFileUploader: false,
	      isTextareaToLong: false,
	      wasUsed: false
	    };
	  },
	  computed: {
	    deadlineFormatted() {
	      const converter = new crm_timeline_tools.DatetimeConverter(this.currentDeadline);
	      return converter.toDatetimeString({
	        withDayOfWeek: true,
	        delimiter: ', '
	      });
	    }
	  },
	  watch: {
	    description() {
	      main_core.Dom.style(this.$refs.textarea, 'height', 'auto');
	      void this.$nextTick(() => {
	        const currentTextareaHeight = this.$refs.textarea.scrollHeight;
	        main_core.Dom.style(this.$refs.textarea, 'height', `${currentTextareaHeight}px`);
	        if (this.popupMode === true) {
	          this.isTextareaToLong = currentTextareaHeight > TEXTAREA_MAX_HEIGHT;
	        }
	      });
	    }
	  },
	  methods: {
	    clearDescription() {
	      this.description = '';
	      main_core.Dom.style(this.$refs.textarea, 'height', 'auto');
	    },
	    setDescription(description) {
	      this.description = description;
	    },
	    onTextareaFocus() {
	      this.wasUsed = true;
	      this.onFocus();
	    },
	    onTextareaKeydown(event) {
	      if (event.keyCode !== 13) {
	        return;
	      }
	      const isMacCtrlKeydown = main_core.Browser.isMac() && (event.metaKey === true || event.altKey === true);
	      if (event.ctrlKey === true || isMacCtrlKeydown) {
	        this.onSaveHotkeyPressed();
	      }
	    },
	    setTextareaFocused() {
	      this.wasUsed = true;
	      this.$refs.textarea.focus();
	    },
	    onDeadlineClick() {
	      BX.calendar({
	        node: this.$refs.deadline,
	        bTime: true,
	        bHideTime: false,
	        bSetFocus: false,
	        value: main_date.DateTimeFormat.format(crm_timeline_tools.DatetimeConverter.getSiteDateTimeFormat(), this.currentDeadline),
	        callback: this.setDeadline.bind(this)
	      });
	    },
	    setDeadline(newDeadline) {
	      this.currentDeadline = newDeadline;
	    },
	    onResponsibleUserChange(event) {
	      const data = event.getData();
	      if (data) {
	        this.setResponsibleUserId(data.responsibleUserId);
	      }
	    },
	    setResponsibleUserId(userId) {
	      this.responsibleUserId = userId;
	    },
	    resetResponsibleUserToDefault() {
	      this.setResponsibleUserId(this.currentUser.userId);
	      const userSelector = this.$refs.userSelector;
	      if (userSelector) {
	        userSelector.resetToDefault();
	      }
	    },
	    getData() {
	      return {
	        description: this.description,
	        deadline: this.currentDeadline,
	        responsibleUserId: this.responsibleUserId
	      };
	    },
	    onTextareaInput(event) {
	      this.setDescription(event.target.value);
	      this.onChangeDescription(event.target.value);
	    }
	  },
	  mounted() {
	    this.$Bitrix.eventEmitter.subscribe(Events.EVENT_RESPONSIBLE_USER_CHANGE, this.onResponsibleUserChange);
	  },
	  beforeUnmount() {
	    this.$Bitrix.eventEmitter.unsubscribe(Events.EVENT_RESPONSIBLE_USER_CHANGE, this.onResponsibleUserChange);
	  },
	  template: `
		<label class="crm-activity__todo-editor_body">
			<textarea
				rows="1"
				ref="textarea"
				@focus="onTextareaFocus"
				@keydown="onTextareaKeydown"
				class="crm-activity__todo-editor_textarea"
				:placeholder="$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_ADD_PLACEHOLDER')"
				@input="onTextareaInput"
				:value="description"
				:class="{ '--has-scroll': isTextareaToLong }"
			></textarea>
			<div class="crm-activity__todo-editor_tools" v-if="wasUsed">
				<div
					ref="deadline"
					@click="onDeadlineClick"
					class="crm-activity__todo-editor_deadline"
				>
					<span class="crm-activity__todo-editor_deadline-icon"><i></i></span>
					<span class="crm-activity__todo-editor_deadline-text">{{ deadlineFormatted }}</span>
				</div>
				<div class="crm-activity__todo-editor_action-btns">
					<TodoEditorActionBtn
						v-for="btn in additionalButtons"
						:key="btn.id"
						:icon="btn.icon"
						:description="btn.description"
						:action="btn.action"
					/>
					<TodoEditorActionDelimiter/>
					<TodoEditorResponsibleUserSelector
						:userId="currentUser.userId"
						:userName="currentUser.title"
						:imageUrl="currentUser.imageUrl"
						ref="userSelector"
						class="crm-activity__todo-editor_action-btn"
					>
					</TodoEditorResponsibleUserSelector>
				</div>
			</div>
		</label>
	`
	};

	let TodoEditorBorderColor = function TodoEditorBorderColor() {
	  babelHelpers.classCallCheck(this, TodoEditorBorderColor);
	};
	babelHelpers.defineProperty(TodoEditorBorderColor, "DEFAULT", 'default');
	babelHelpers.defineProperty(TodoEditorBorderColor, "PRIMARY", 'primary');

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	const TodoEditorMode = {
	  ADD: 'add',
	  UPDATE: 'update'
	};

	/**
	 * @memberOf BX.Crm.Activity
	 */
	var _container = /*#__PURE__*/new WeakMap();
	var _layoutApp = /*#__PURE__*/new WeakMap();
	var _layoutComponent = /*#__PURE__*/new WeakMap();
	var _loadingPromise = /*#__PURE__*/new WeakMap();
	var _mode = /*#__PURE__*/new WeakMap();
	var _ownerTypeId = /*#__PURE__*/new WeakMap();
	var _ownerId = /*#__PURE__*/new WeakMap();
	var _currentUser = /*#__PURE__*/new WeakMap();
	var _defaultDescription = /*#__PURE__*/new WeakMap();
	var _deadline = /*#__PURE__*/new WeakMap();
	var _parentActivityId = /*#__PURE__*/new WeakMap();
	var _borderColor = /*#__PURE__*/new WeakMap();
	var _activityId = /*#__PURE__*/new WeakMap();
	var _eventEmitter = /*#__PURE__*/new WeakMap();
	var _fileUploader = /*#__PURE__*/new WeakMap();
	var _settingsPopup = /*#__PURE__*/new WeakMap();
	var _settings = /*#__PURE__*/new WeakMap();
	var _enableCalendarSync = /*#__PURE__*/new WeakMap();
	var _popupMode = /*#__PURE__*/new WeakMap();
	var _getAdditionalButtons = /*#__PURE__*/new WeakSet();
	var _getSettingsButton = /*#__PURE__*/new WeakSet();
	var _onSettingsButtonClick = /*#__PURE__*/new WeakSet();
	var _getSettingsSection = /*#__PURE__*/new WeakSet();
	var _getFileUploaderButton = /*#__PURE__*/new WeakSet();
	var _getSaveActionData = /*#__PURE__*/new WeakSet();
	var _getSaveActionPath = /*#__PURE__*/new WeakSet();
	var _getSaveActionDataSettings = /*#__PURE__*/new WeakSet();
	var _syncAndGetSaveActionDataSettingsFromPopup = /*#__PURE__*/new WeakSet();
	var _getSaveActionDefaultDataSettings = /*#__PURE__*/new WeakSet();
	var _getSectionSettings = /*#__PURE__*/new WeakSet();
	var _getDefaultCalendarParams = /*#__PURE__*/new WeakSet();
	var _getDefaultPingParams = /*#__PURE__*/new WeakSet();
	var _getDefaultDescription = /*#__PURE__*/new WeakSet();
	var _onInputFocus = /*#__PURE__*/new WeakSet();
	var _onChangeDescription = /*#__PURE__*/new WeakSet();
	var _onSaveHotkeyPressed = /*#__PURE__*/new WeakSet();
	var _isValidBorderColor = /*#__PURE__*/new WeakSet();
	var _getClassname = /*#__PURE__*/new WeakSet();
	var _onFileUploadButtonClick = /*#__PURE__*/new WeakSet();
	let TodoEditor$1 = /*#__PURE__*/function () {
	  /**
	   * @event onFocus
	   * @event onChangeDescription
	   * @event onSaveHotkeyPressed
	   */

	  function TodoEditor$$1(params) {
	    babelHelpers.classCallCheck(this, TodoEditor$$1);
	    _classPrivateMethodInitSpec(this, _onFileUploadButtonClick);
	    _classPrivateMethodInitSpec(this, _getClassname);
	    _classPrivateMethodInitSpec(this, _isValidBorderColor);
	    _classPrivateMethodInitSpec(this, _onSaveHotkeyPressed);
	    _classPrivateMethodInitSpec(this, _onChangeDescription);
	    _classPrivateMethodInitSpec(this, _onInputFocus);
	    _classPrivateMethodInitSpec(this, _getDefaultDescription);
	    _classPrivateMethodInitSpec(this, _getDefaultPingParams);
	    _classPrivateMethodInitSpec(this, _getDefaultCalendarParams);
	    _classPrivateMethodInitSpec(this, _getSectionSettings);
	    _classPrivateMethodInitSpec(this, _getSaveActionDefaultDataSettings);
	    _classPrivateMethodInitSpec(this, _syncAndGetSaveActionDataSettingsFromPopup);
	    _classPrivateMethodInitSpec(this, _getSaveActionDataSettings);
	    _classPrivateMethodInitSpec(this, _getSaveActionPath);
	    _classPrivateMethodInitSpec(this, _getSaveActionData);
	    _classPrivateMethodInitSpec(this, _getFileUploaderButton);
	    _classPrivateMethodInitSpec(this, _getSettingsSection);
	    _classPrivateMethodInitSpec(this, _onSettingsButtonClick);
	    _classPrivateMethodInitSpec(this, _getSettingsButton);
	    _classPrivateMethodInitSpec(this, _getAdditionalButtons);
	    _classPrivateFieldInitSpec(this, _container, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _layoutApp, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _layoutComponent, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _loadingPromise, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _mode, {
	      writable: true,
	      value: TodoEditorMode.ADD
	    });
	    _classPrivateFieldInitSpec(this, _ownerTypeId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _ownerId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _currentUser, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _defaultDescription, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _deadline, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _parentActivityId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _borderColor, {
	      writable: true,
	      value: ''
	    });
	    _classPrivateFieldInitSpec(this, _activityId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _eventEmitter, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _fileUploader, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _settingsPopup, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _settings, {
	      writable: true,
	      value: {}
	    });
	    _classPrivateFieldInitSpec(this, _enableCalendarSync, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _popupMode, {
	      writable: true,
	      value: false
	    });
	    if (!main_core.Type.isDomNode(params.container)) {
	      throw new Error('TodoEditor container must be a DOM Node');
	    }
	    babelHelpers.classPrivateFieldSet(this, _container, params.container);
	    babelHelpers.classPrivateFieldSet(this, _borderColor, _classPrivateMethodGet(this, _isValidBorderColor, _isValidBorderColor2).call(this, params.borderColor) ? params.borderColor : TodoEditor$$1.BorderColor.DEFAULT);
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _container), _classPrivateMethodGet(this, _getClassname, _getClassname2).call(this));
	    if (!main_core.Type.isNumber(params.ownerTypeId)) {
	      throw new Error('OwnerTypeId must be set');
	    }
	    babelHelpers.classPrivateFieldSet(this, _ownerTypeId, params.ownerTypeId);
	    if (!main_core.Type.isNumber(params.ownerId)) {
	      throw new Error('OwnerId must be set');
	    }
	    babelHelpers.classPrivateFieldSet(this, _ownerId, params.ownerId);
	    if (!main_core.Type.isObject(params.currentUser)) {
	      throw new Error('Current user must be set');
	    }
	    babelHelpers.classPrivateFieldSet(this, _currentUser, params.currentUser);
	    babelHelpers.classPrivateFieldSet(this, _defaultDescription, main_core.Type.isString(params.defaultDescription) ? params.defaultDescription : _classPrivateMethodGet(this, _getDefaultDescription, _getDefaultDescription2).call(this));
	    babelHelpers.classPrivateFieldSet(this, _deadline, main_core.Type.isDate(params.deadline) ? params.deadline : null);
	    if (!babelHelpers.classPrivateFieldGet(this, _deadline)) {
	      this.setDefaultDeadLine(false);
	    }
	    babelHelpers.classPrivateFieldSet(this, _eventEmitter, new main_core_events.EventEmitter());
	    babelHelpers.classPrivateFieldGet(this, _eventEmitter).setEventNamespace('Crm.Activity.TodoEditor');
	    if (main_core.Type.isObject(params.events)) {
	      for (const eventName in params.events) {
	        if (main_core.Type.isFunction(params.events[eventName])) {
	          babelHelpers.classPrivateFieldGet(this, _eventEmitter).subscribe(eventName, params.events[eventName]);
	        }
	      }
	    }
	    babelHelpers.classPrivateFieldSet(this, _enableCalendarSync, main_core.Type.isBoolean(params.enableCalendarSync) ? params.enableCalendarSync : false);
	    babelHelpers.classPrivateFieldSet(this, _popupMode, main_core.Type.isBoolean(params.popupMode) ? params.popupMode : false);
	  }
	  babelHelpers.createClass(TodoEditor$$1, [{
	    key: "setMode",
	    value: function setMode(mode) {
	      if (!Object.values(TodoEditorMode).some(value => value === mode)) {
	        throw new Error(`Unknown TodoEditor mode ${mode}`);
	      }
	      babelHelpers.classPrivateFieldSet(this, _mode, mode);
	      return this;
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      babelHelpers.classPrivateFieldSet(this, _layoutApp, ui_vue3.BitrixVue.createApp(TodoEditor, {
	        deadline: babelHelpers.classPrivateFieldGet(this, _deadline),
	        defaultDescription: babelHelpers.classPrivateFieldGet(this, _defaultDescription),
	        onFocus: _classPrivateMethodGet(this, _onInputFocus, _onInputFocus2).bind(this),
	        onChangeDescription: _classPrivateMethodGet(this, _onChangeDescription, _onChangeDescription2).bind(this),
	        onSaveHotkeyPressed: _classPrivateMethodGet(this, _onSaveHotkeyPressed, _onSaveHotkeyPressed2).bind(this),
	        additionalButtons: _classPrivateMethodGet(this, _getAdditionalButtons, _getAdditionalButtons2).call(this),
	        popupMode: babelHelpers.classPrivateFieldGet(this, _popupMode),
	        currentUser: babelHelpers.classPrivateFieldGet(this, _currentUser)
	      }));
	      babelHelpers.classPrivateFieldSet(this, _layoutComponent, babelHelpers.classPrivateFieldGet(this, _layoutApp).mount(babelHelpers.classPrivateFieldGet(this, _container)));
	    }
	  }, {
	    key: "onSettingsChange",
	    value: function onSettingsChange(settings) {
	      this.setSettings(settings);
	      if (settings !== null && settings !== void 0 && settings.calendar) {
	        this.setDeadlineFromTimestamp(settings['calendar'].from);
	      }
	    }
	  }, {
	    key: "setSettings",
	    value: function setSettings(settings = {}) {
	      babelHelpers.classPrivateFieldSet(this, _settings, settings);
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      if (babelHelpers.classPrivateFieldGet(this, _loadingPromise)) {
	        return babelHelpers.classPrivateFieldGet(this, _loadingPromise);
	      }

	      // wrap BX.Promise in native js promise
	      babelHelpers.classPrivateFieldSet(this, _loadingPromise, new Promise((resolve, reject) => {
	        _classPrivateMethodGet(this, _getSaveActionData, _getSaveActionData2).call(this).then(data => {
	          main_core.ajax.runAction(_classPrivateMethodGet(this, _getSaveActionPath, _getSaveActionPath2).call(this), {
	            data
	          }).then(resolve).catch(reject);
	        });
	      }).catch(response => {
	        ui_notification.UI.Notification.Center.notify({
	          content: response.errors[0].message,
	          autoHideDelay: 5000
	        });

	        //so that on error returned promise is marked as rejected
	        throw response;
	      }).finally(() => {
	        babelHelpers.classPrivateFieldSet(this, _loadingPromise, null);
	      }));
	      return babelHelpers.classPrivateFieldGet(this, _loadingPromise);
	    }
	  }, {
	    key: "getDeadline",
	    value: function getDeadline() {
	      var _babelHelpers$classPr, _babelHelpers$classPr2;
	      return (_babelHelpers$classPr = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(this, _layoutComponent)) === null || _babelHelpers$classPr2 === void 0 ? void 0 : _babelHelpers$classPr2.getData()['deadline']) !== null && _babelHelpers$classPr !== void 0 ? _babelHelpers$classPr : null;
	    }
	  }, {
	    key: "getDescription",
	    value: function getDescription() {
	      var _babelHelpers$classPr3, _babelHelpers$classPr4;
	      return (_babelHelpers$classPr3 = (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldGet(this, _layoutComponent)) === null || _babelHelpers$classPr4 === void 0 ? void 0 : _babelHelpers$classPr4.getData()['description']) !== null && _babelHelpers$classPr3 !== void 0 ? _babelHelpers$classPr3 : '';
	    }
	  }, {
	    key: "setParentActivityId",
	    value: function setParentActivityId(activityId) {
	      babelHelpers.classPrivateFieldSet(this, _parentActivityId, activityId);
	      return this;
	    }
	  }, {
	    key: "setActivityId",
	    value: function setActivityId(activityId) {
	      babelHelpers.classPrivateFieldSet(this, _activityId, activityId);
	      return this;
	    }
	  }, {
	    key: "setDeadline",
	    value: function setDeadline(deadLine) {
	      const value = main_date.DateTimeFormat.parse(deadLine);
	      if (main_core.Type.isDate(value)) {
	        babelHelpers.classPrivateFieldGet(this, _layoutComponent).setDeadline(value);
	        babelHelpers.classPrivateFieldSet(this, _deadline, value);
	      }
	      return this;
	    }
	  }, {
	    key: "setDeadlineFromTimestamp",
	    value: function setDeadlineFromTimestamp(timestamp) {
	      const value = new Date(timestamp * 1000);
	      babelHelpers.classPrivateFieldGet(this, _layoutComponent).setDeadline(value);
	      babelHelpers.classPrivateFieldSet(this, _deadline, value);
	      return this;
	    }
	  }, {
	    key: "setDefaultDeadLine",
	    value: function setDefaultDeadLine(isNeedUpdateLayout = true) {
	      let defaultDate = BX.parseDate(main_core.Loc.getMessage('CRM_TIMELINE_TODO_EDITOR_DEFAULT_DATETIME'));
	      if (main_core.Type.isDate(defaultDate)) {
	        babelHelpers.classPrivateFieldSet(this, _deadline, defaultDate);
	      } else {
	        babelHelpers.classPrivateFieldSet(this, _deadline, new Date());
	        babelHelpers.classPrivateFieldGet(this, _deadline).setMinutes(0);
	        babelHelpers.classPrivateFieldGet(this, _deadline).setTime(babelHelpers.classPrivateFieldGet(this, _deadline).getTime() + 60 * 60 * 1000); // next hour
	      }

	      if (isNeedUpdateLayout) {
	        babelHelpers.classPrivateFieldGet(this, _layoutComponent).setDeadline(babelHelpers.classPrivateFieldGet(this, _deadline));
	      }
	      return this;
	    }
	  }, {
	    key: "setFocused",
	    value: function setFocused() {
	      babelHelpers.classPrivateFieldGet(this, _layoutComponent).setTextareaFocused();
	    }
	  }, {
	    key: "setDescription",
	    value: function setDescription(description) {
	      babelHelpers.classPrivateFieldGet(this, _layoutComponent).setDescription(description);
	      return this;
	    }
	  }, {
	    key: "clearValue",
	    value: function clearValue() {
	      babelHelpers.classPrivateFieldGet(this, _layoutComponent).clearDescription();
	      babelHelpers.classPrivateFieldSet(this, _parentActivityId, null);
	      this.setDefaultDeadLine();
	      babelHelpers.classPrivateFieldGet(this, _layoutComponent).resetResponsibleUserToDefault();
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(this, _container), '--is-edit');
	      if (babelHelpers.classPrivateFieldGet(this, _fileUploader)) {
	        main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(this, _fileUploader).getContainer(), '--is-displayed');
	      }
	      babelHelpers.classPrivateFieldSet(this, _fileUploader, null);
	      babelHelpers.classPrivateFieldSet(this, _settingsPopup, null);
	      return new Promise(resolve => {
	        setTimeout(resolve, 10);
	      });
	    }
	  }, {
	    key: "resetToDefaults",
	    value: function resetToDefaults() {
	      babelHelpers.classPrivateFieldGet(this, _layoutComponent).setDescription(_classPrivateMethodGet(this, _getDefaultDescription, _getDefaultDescription2).call(this));
	      this.setDefaultDeadLine();
	      babelHelpers.classPrivateFieldGet(this, _layoutComponent).resetResponsibleUserToDefault();
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(this, _container), '--is-edit');
	      if (babelHelpers.classPrivateFieldGet(this, _fileUploader)) {
	        main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(this, _fileUploader).getContainer(), '--is-displayed');
	      }
	      babelHelpers.classPrivateFieldSet(this, _fileUploader, null);
	      babelHelpers.classPrivateFieldSet(this, _settingsPopup, null);
	      return new Promise(resolve => {
	        setTimeout(resolve, 10);
	      });
	    }
	  }, {
	    key: "setStorageElementIds",
	    value: function setStorageElementIds(ids) {
	      this.initFileUploader(ids);
	    }
	  }, {
	    key: "initFileUploader",
	    value: function initFileUploader(files = []) {
	      if (!babelHelpers.classPrivateFieldGet(this, _fileUploader)) {
	        babelHelpers.classPrivateFieldSet(this, _fileUploader, new crm_activity_fileUploader.FileUploader({
	          baseContainer: babelHelpers.classPrivateFieldGet(this, _container),
	          events: {
	            'File:onRemove': event => {
	              babelHelpers.classPrivateFieldGet(this, _eventEmitter).emit('onChangeUploaderContainerSize');
	            },
	            'onUploadStart': event => {
	              babelHelpers.classPrivateFieldGet(this, _eventEmitter).emit('onChangeUploaderContainerSize');
	            }
	            // TODO: not implemented yet
	            //		'File:onComplete'
	            //		'onUploadComplete'
	          },

	          ownerId: babelHelpers.classPrivateFieldGet(this, _ownerId),
	          ownerTypeId: babelHelpers.classPrivateFieldGet(this, _ownerTypeId),
	          activityId: babelHelpers.classPrivateFieldGet(this, _activityId),
	          files
	        }));
	      }
	      const fileUploaderContainer = babelHelpers.classPrivateFieldGet(this, _fileUploader).getContainer();
	      const displayedClass = '--is-displayed';
	      if (files && !main_core.Dom.hasClass(fileUploaderContainer, displayedClass)) {
	        main_core.Dom.addClass(fileUploaderContainer, displayedClass);
	      } else {
	        main_core.Dom.toggleClass(fileUploaderContainer, displayedClass);
	      }
	      babelHelpers.classPrivateFieldGet(this, _eventEmitter).emit('onChangeUploaderContainerSize');
	    }
	  }]);
	  return TodoEditor$$1;
	}();
	function _getAdditionalButtons2() {
	  const buttons = [];
	  if (babelHelpers.classPrivateFieldGet(this, _enableCalendarSync)) {
	    buttons.push(_classPrivateMethodGet(this, _getSettingsButton, _getSettingsButton2).call(this));
	  }
	  buttons.push(_classPrivateMethodGet(this, _getFileUploaderButton, _getFileUploaderButton2).call(this));
	  return buttons;
	}
	function _getSettingsButton2() {
	  return {
	    id: 'settings',
	    icon: 'settings',
	    description: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_SETTINGS_BUTTON_HINT'),
	    action: _classPrivateMethodGet(this, _onSettingsButtonClick, _onSettingsButtonClick2).bind(this)
	  };
	}
	function _onSettingsButtonClick2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _settingsPopup)) {
	    babelHelpers.classPrivateFieldSet(this, _settingsPopup, new crm_activity_settingsPopup.SettingsPopup({
	      onSettingsChange: this.onSettingsChange.bind(this),
	      sections: _classPrivateMethodGet(this, _getSectionSettings, _getSectionSettings2).call(this),
	      settings: babelHelpers.classPrivateFieldGet(this, _settings)
	    }));
	  }
	  if (babelHelpers.classPrivateFieldGet(this, _layoutComponent)) {
	    babelHelpers.classPrivateFieldGet(this, _settingsPopup).syncSettings(babelHelpers.classPrivateFieldGet(this, _layoutComponent).getData());
	  }
	  babelHelpers.classPrivateFieldGet(this, _settingsPopup).show();
	}
	function _getFileUploaderButton2() {
	  return {
	    id: 'file-uploader',
	    icon: 'attach',
	    description: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_UPLOAD_FILE_BUTTON_HINT'),
	    action: _classPrivateMethodGet(this, _onFileUploadButtonClick, _onFileUploadButtonClick2).bind(this)
	  };
	}
	function _getSaveActionData2() {
	  return new Promise(resolve => {
	    _classPrivateMethodGet(this, _getSaveActionDataSettings, _getSaveActionDataSettings2).call(this).then(settings => {
	      const userData = babelHelpers.classPrivateFieldGet(this, _layoutComponent).getData();
	      const data = {
	        ownerTypeId: babelHelpers.classPrivateFieldGet(this, _ownerTypeId),
	        ownerId: babelHelpers.classPrivateFieldGet(this, _ownerId),
	        description: userData.description,
	        responsibleId: userData.responsibleUserId,
	        deadline: main_date.DateTimeFormat.format(crm_timeline_tools.DatetimeConverter.getSiteDateTimeFormat(), userData.deadline),
	        parentActivityId: babelHelpers.classPrivateFieldGet(this, _parentActivityId),
	        fileTokens: babelHelpers.classPrivateFieldGet(this, _fileUploader) ? babelHelpers.classPrivateFieldGet(this, _fileUploader).getServerFileIds() : [],
	        settings
	      };
	      if (babelHelpers.classPrivateFieldGet(this, _mode) === TodoEditorMode.UPDATE) {
	        data.id = babelHelpers.classPrivateFieldGet(this, _activityId);
	      }
	      resolve(data);
	    });
	  });
	}
	function _getSaveActionPath2() {
	  return babelHelpers.classPrivateFieldGet(this, _mode) === TodoEditorMode.ADD ? 'crm.activity.todo.add' : 'crm.activity.todo.update';
	}
	function _getSaveActionDataSettings2() {
	  if (babelHelpers.classPrivateFieldGet(this, _settingsPopup)) {
	    return _classPrivateMethodGet(this, _syncAndGetSaveActionDataSettingsFromPopup, _syncAndGetSaveActionDataSettingsFromPopup2).call(this);
	  }
	  return _classPrivateMethodGet(this, _getSaveActionDefaultDataSettings, _getSaveActionDefaultDataSettings2).call(this);
	}
	function _syncAndGetSaveActionDataSettingsFromPopup2() {
	  if (babelHelpers.classPrivateFieldGet(this, _layoutComponent)) {
	    babelHelpers.classPrivateFieldGet(this, _settingsPopup).syncSettings(babelHelpers.classPrivateFieldGet(this, _layoutComponent).getData());
	  }

	  // must first work out vue reactivity in nested components
	  return new Promise(resolve => {
	    setTimeout(() => {
	      resolve(babelHelpers.classPrivateFieldGet(this, _settingsPopup).getSettings());
	    }, 0);
	  });
	}
	function _getSaveActionDefaultDataSettings2() {
	  const result = [];
	  _classPrivateMethodGet(this, _getSectionSettings, _getSectionSettings2).call(this).forEach(section => {
	    if (!section.active) {
	      return;
	    }
	    const sectionSettings = section.params;
	    sectionSettings.id = section.id;
	    result.push(sectionSettings);
	  });
	  return Promise.resolve(result);
	}
	function _getSectionSettings2() {
	  const ping = {
	    id: crm_activity_settingsPopup.Ping.methods.getId(),
	    component: crm_activity_settingsPopup.Ping,
	    active: true,
	    showToggleSelector: false
	  };
	  const calendar = {
	    id: crm_activity_settingsPopup.Calendar.methods.getId(),
	    component: crm_activity_settingsPopup.Calendar
	  };
	  if (babelHelpers.classPrivateFieldGet(this, _settingsPopup)) {
	    const settings = babelHelpers.classPrivateFieldGet(this, _settingsPopup).getSettings();
	    if (settings.ping) {
	      const pingSettings = settings.ping;
	      ping.params = {
	        selectedItems: pingSettings.selectedItems
	      };
	      ping.active = true;
	      ping.showToggleSelector = false;
	    }
	    if (settings.calendar) {
	      const calendarSettings = settings.calendar;
	      calendar.params = {
	        from: calendarSettings.from,
	        to: calendarSettings.to,
	        duration: calendarSettings.duration
	      };
	      calendar.active = true;
	    }
	  }
	  if (!ping.params) {
	    ping.params = _classPrivateMethodGet(this, _getDefaultPingParams, _getDefaultPingParams2).call(this);
	  }
	  if (!calendar.params) {
	    calendar.params = _classPrivateMethodGet(this, _getDefaultCalendarParams, _getDefaultCalendarParams2).call(this);
	    calendar.active = false;
	  }
	  return [ping, calendar];
	}
	function _getDefaultCalendarParams2() {
	  const fromDate = this.getDeadline() || babelHelpers.classPrivateFieldGet(this, _deadline);
	  const from = fromDate.getTime() / 1000;
	  const duration = 3600;
	  const to = from + duration;
	  return {
	    from,
	    duration,
	    to
	  };
	}
	function _getDefaultPingParams2() {
	  // TODO: get real default values from server-side
	  return {
	    selectedItems: ['at_the_time_of_the_onset', 'in_15_minutes']
	  };
	}
	function _getDefaultDescription2() {
	  let messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_DEFAULT_TEXT';
	  switch (babelHelpers.classPrivateFieldGet(this, _ownerTypeId)) {
	    case BX.CrmEntityType.enumeration.deal:
	      messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_DEFAULT_TEXT_DEAL';
	  }
	  return main_core.Loc.getMessage(messagePhrase);
	}
	function _onInputFocus2() {
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _container), '--is-edit');
	  babelHelpers.classPrivateFieldGet(this, _eventEmitter).emit('onFocus');
	}
	function _onChangeDescription2(description) {
	  const event = new main_core_events.BaseEvent({
	    data: {
	      description
	    }
	  });
	  babelHelpers.classPrivateFieldGet(this, _eventEmitter).emit('onChangeDescription', event);
	}
	function _onSaveHotkeyPressed2() {
	  babelHelpers.classPrivateFieldGet(this, _eventEmitter).emit('onSaveHotkeyPressed');
	}
	function _isValidBorderColor2(borderColor) {
	  return main_core.Type.isString(borderColor) && TodoEditor$1.BorderColor[borderColor.toUpperCase()];
	}
	function _getClassname2() {
	  return `crm-activity__todo-editor --border-${babelHelpers.classPrivateFieldGet(this, _borderColor)}`;
	}
	function _onFileUploadButtonClick2() {
	  this.initFileUploader();
	}
	babelHelpers.defineProperty(TodoEditor$1, "BorderColor", TodoEditorBorderColor);
	const namespace = main_core.Reflection.namespace('BX.Crm.Activity');
	namespace.TodoEditor = TodoEditor$1;

	exports.TodoEditorMode = TodoEditorMode;
	exports.TodoEditor = TodoEditor$1;

}((this.BX.Crm.Activity = this.BX.Crm.Activity || {}),BX.Vue3,BX.Main,BX,BX.Event,BX.UI.EntitySelector,BX,BX.Main,BX.Crm.Timeline,BX.Crm.Activity,BX.Crm.Activity));
//# sourceMappingURL=todo-editor.bundle.js.map
