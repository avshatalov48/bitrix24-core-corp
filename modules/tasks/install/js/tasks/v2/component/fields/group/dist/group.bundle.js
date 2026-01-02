/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,tasks_v2_component_elements_hoverPill,tasks_v2_component_elements_fieldAdd,tasks_v2_lib_heightTransition,ui_vue3_components_menu,ui_iconSet_crm,tasks_v2_component_elements_userCustomTagSelector,ui_iconSet_api_vue,tasks_v2_lib_color,ui_vue3_directives_hint,ui_vue3_components_popup,ui_vue3_components_button,tasks_v2_lib_openGroup,tasks_v2_provider_service_groupService,tasks_v2_provider_service_userService,tasks_v2_lib_entitySelectorDialog,main_core,ui_iconSet_api_core,ui_iconSet_outline,tasks_v2_core,tasks_v2_const,tasks_v2_component_elements_chip,tasks_v2_component_elements_hint,tasks_v2_lib_fieldHighlighter,tasks_v2_lib_analytics,tasks_v2_provider_service_taskService) {
	'use strict';

	const groupMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.Group,
	  title: main_core.Loc.getMessage('TASKS_V2_GROUP_TITLE'),
	  getTitle: groupId => {
	    var _GroupType$Collab$Gro;
	    const group = tasks_v2_core.Core.getStore().getters[`${tasks_v2_const.Model.Groups}/getById`](groupId);
	    return (_GroupType$Collab$Gro = {
	      [tasks_v2_const.GroupType.Collab]: main_core.Loc.getMessage('TASKS_V2_GROUP_TITLE_COLLAB'),
	      [tasks_v2_const.GroupType.Scrum]: main_core.Loc.getMessage('TASKS_V2_GROUP_TITLE_SCRUM')
	    }[group == null ? void 0 : group.type]) != null ? _GroupType$Collab$Gro : main_core.Loc.getMessage('TASKS_V2_GROUP_TITLE');
	  }
	});

	let _ = t => t,
	  _t;

	// @vue/component
	const Stage = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    Loader: tasks_v2_component_elements_userCustomTagSelector.Loader,
	    BMenu: ui_vue3_components_menu.BMenu
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_core.Outline
	    };
	  },
	  data() {
	    return {
	      isMenuShown: false
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    isEdit() {
	      return Number.isInteger(this.taskId) && this.taskId > 0;
	    },
	    group() {
	      return this.$store.getters[`${tasks_v2_const.Model.Groups}/getById`](this.task.groupId);
	    },
	    groupId() {
	      return this.task.groupId;
	    },
	    stage() {
	      var _this$$store$getters;
	      return (_this$$store$getters = this.$store.getters[`${tasks_v2_const.Model.Stages}/getById`](this.task.stageId)) != null ? _this$$store$getters : null;
	    },
	    menuOptions() {
	      return () => ({
	        id: 'tasks-field-group-stage-menu',
	        bindElement: this.$refs.stage,
	        offsetTop: 8,
	        items: this.menuItems,
	        maxHeight: window.innerHeight / 2,
	        targetContainer: document.body
	      });
	    },
	    menuItems() {
	      var _this$group$stagesIds;
	      const stages = this.$store.getters[`${tasks_v2_const.Model.Stages}/getByIds`]((_this$group$stagesIds = this.group.stagesIds) != null ? _this$group$stagesIds : []);
	      return stages == null ? void 0 : stages.map(stage => ({
	        title: stage.title,
	        svg: this.getStageSvg(new tasks_v2_lib_color.Color(stage.color).limit(250).toRgb()),
	        isSelected: stage.id === this.stage.id,
	        onClick: () => this.setStage(stage.id)
	      }));
	    },
	    stageColor() {
	      return `#${this.stage.color}`;
	    },
	    backgroundColor() {
	      return new tasks_v2_lib_color.Color(this.stage.color).setOpacity(0.1).limit(250).toRgb();
	    },
	    isDarkColor() {
	      return new tasks_v2_lib_color.Color(this.stage.color).isDark();
	    },
	    tooltip() {
	      return () => tasks_v2_component_elements_hint.tooltip({
	        text: this.loc('TASKS_V2_GROUP_STAGE_HINT'),
	        popupOptions: {
	          offsetLeft: this.$refs.stage.offsetWidth / 2
	        }
	      });
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    }
	  },
	  watch: {
	    groupId() {
	      void this.loadStagesForCreation();
	    }
	  },
	  created() {
	    void this.loadStagesForCreation();
	  },
	  methods: {
	    getStageSvg(color) {
	      return main_core.Tag.render(_t || (_t = _`
				<div class="ui-icon-set --${0}" style="--ui-icon-set__icon-color: ${0}"></div>
			`), ui_iconSet_api_core.CRM.STAGE, color);
	    },
	    async handleClick() {
	      if (this.readonly) {
	        return;
	      }
	      if (!this.group.stagesIds) {
	        await tasks_v2_provider_service_groupService.groupService.getStages(this.groupId);
	      }
	      this.isMenuShown = true;
	    },
	    setStage(stageId) {
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        stageId
	      });
	    },
	    async loadStagesForCreation() {
	      if (this.isEdit || this.group.stagesIds) {
	        return;
	      }
	      await tasks_v2_provider_service_groupService.groupService.getStages(this.groupId);
	      if (!this.task.stageId) {
	        this.setStage(this.group.stagesIds[0]);
	      }
	    }
	  },
	  template: `
		<div
			v-if="stage"
			v-hint="tooltip"
			class="tasks-field-group-stage"
			:class="{ '--dark': isDarkColor, '--readonly': readonly }"
			:style="{
				'--stage-color': stageColor,
				'--stage-background': backgroundColor,
			}"
			:data-task-id="taskId"
			:data-task-field-id="'stageId'"
			:data-task-field-value="task.stageId"
			:data-task-stage-title="stage?.title"
			ref="stage"
			@click="handleClick"
		>
			<div class="tasks-field-group-stage-text-container">
				<div class="tasks-field-group-stage-text">{{ stage.title }}</div>
			</div>
			<div class="tasks-field-group-stage-arrow"></div>
			<BIcon v-if="!readonly" :name="Outline.CHEVRON_DOWN_S"/>
		</div>
		<div v-else class="tasks-field-group-stage-loader">
			<Loader/>
		</div>
		<BMenu v-if="isMenuShown" :options="menuOptions()" @close="isMenuShown = false"/>
	`
	};

	// @vue/component
	const Epic = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_core.Outline
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    epic() {
	      return this.$store.getters[`${tasks_v2_const.Model.Epics}/getById`](this.task.epicId);
	    },
	    preselectedEpic() {
	      return this.epic ? [['epic-selector', this.epic.id]] : [];
	    },
	    epicColor() {
	      if (!this.epic) {
	        return '';
	      }
	      return new tasks_v2_lib_color.Color(this.epic.color).toRgb();
	    },
	    backgroundColor() {
	      if (!this.epic) {
	        return '';
	      }
	      return new tasks_v2_lib_color.Color(this.epic.color).setOpacity(0.3).limit(250).toRgb();
	    },
	    isDarkColor() {
	      if (!this.epic) {
	        return false;
	      }
	      return new tasks_v2_lib_color.Color(this.epic.color).isDark();
	    },
	    tooltip() {
	      return () => tasks_v2_component_elements_hint.tooltip({
	        text: this.loc('TASKS_V2_GROUP_EPIC_HINT'),
	        popupOptions: {
	          offsetLeft: this.$el.offsetWidth / 2
	        }
	      });
	    }
	  },
	  methods: {
	    handleClick() {
	      this.showDialog();
	    },
	    showDialog() {
	      var _this$handleEpicSelec, _this$dialog;
	      (_this$handleEpicSelec = this.handleEpicSelectedDebounced) != null ? _this$handleEpicSelec : this.handleEpicSelectedDebounced = main_core.Runtime.debounce(this.handleEpicSelected, 10, this);
	      (_this$dialog = this.dialog) != null ? _this$dialog : this.dialog = new tasks_v2_lib_entitySelectorDialog.EntitySelectorDialog({
	        multiple: false,
	        dropdownMode: true,
	        enableSearch: true,
	        compactView: true,
	        hideOnDeselect: true,
	        entities: [{
	          id: tasks_v2_const.EntitySelectorEntity.Epic,
	          options: {
	            groupId: this.task.groupId
	          },
	          dynamicLoad: true,
	          dynamicSearch: true
	        }],
	        preselectedItems: this.preselectedEpic,
	        events: {
	          'Item:onSelect': this.handleEpicSelectedDebounced,
	          'Item:onDeselect': this.handleEpicSelectedDebounced
	        }
	      });
	      this.dialog.selectItemsByIds(this.preselectedEpic);
	      this.dialog.showTo(this.$el);
	    },
	    handleEpicSelected() {
	      var _item$getId;
	      const item = this.dialog.getSelectedItems()[0];
	      if (item) {
	        void this.$store.dispatch(`${tasks_v2_const.Model.Epics}/insert`, {
	          id: item.getId(),
	          title: item.getTitle(),
	          color: item.getAvatarOption('bgColor')
	        });
	      }
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        epicId: (_item$getId = item == null ? void 0 : item.getId()) != null ? _item$getId : 0
	      });
	    }
	  },
	  template: `
		<div
			v-hint="tooltip"
			class="tasks-field-group-scrum-epic"
			:class="{ '--dark': isDarkColor, '--filled': epic }"
			:style="{
				'--epic-color': epicColor,
				'--epic-background': backgroundColor,
			}"
			@click="handleClick"
		>
			<span class="tasks-field-group-scrum-epic-title">
				{{ epic?.title || loc('TASKS_V2_GROUP_EPIC_EMPTY') }}
			</span>
			<BIcon :name="Outline.CHEVRON_DOWN_S"/>
		</div>
	`
	};

	// @vue/component
	const StoryPoints = {
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  data() {
	    return {
	      isFocused: false
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    storyPoints() {
	      var _this$task$storyPoint;
	      return (_this$task$storyPoint = this.task.storyPoints) == null ? void 0 : _this$task$storyPoint.trim();
	    },
	    tooltip() {
	      return () => tasks_v2_component_elements_hint.tooltip({
	        text: this.loc('TASKS_V2_GROUP_STORY_POINTS_HINT'),
	        popupOptions: {
	          offsetLeft: this.$refs.storyPoints.offsetWidth / 2
	        }
	      });
	    }
	  },
	  methods: {
	    async handleClick() {
	      this.isFocused = true;
	      await this.$nextTick();
	      this.$refs.input.focus();
	    },
	    handleBlur() {
	      this.isFocused = false;
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        storyPoints: this.$refs.input.value.trim()
	      });
	    }
	  },
	  template: `
		<input
			v-if="isFocused"
			class="tasks-field-group-scrum-story-points-input"
			:value="storyPoints"
			ref="input"
			@blur="handleBlur"
		/>
		<div
			v-else
			v-hint="tooltip"
			class="tasks-field-group-scrum-story-points"
			:class="{ '--filled': storyPoints }"
			ref="storyPoints"
			@click="handleClick"
		>
			{{ storyPoints || '-' }}
		</div>
	`
	};

	// @vue/component
	const Scrum = {
	  components: {
	    Epic,
	    StoryPoints,
	    Loader: tasks_v2_component_elements_userCustomTagSelector.Loader
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  data() {
	    return {
	      hasScrumInfo: tasks_v2_provider_service_groupService.groupService.hasScrumInfo(this.taskId)
	    };
	  },
	  async mounted() {
	    await tasks_v2_provider_service_groupService.groupService.getScrumInfo(this.taskId);
	    this.hasScrumInfo = tasks_v2_provider_service_groupService.groupService.hasScrumInfo(this.taskId);
	  },
	  template: `
		<div v-if="hasScrumInfo" class="tasks-field-group-scrum">
			<Epic :taskId="taskId"/>
			<StoryPoints :taskId="taskId"/>
		</div>
		<div v-else class="tasks-field-group-scrum-loader">
			<Loader/>
		</div>
	`
	};

	const GroupPopup = {
	  components: {
	    Popup: ui_vue3_components_popup.Popup,
	    UiButton: ui_vue3_components_button.Button
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    getBindElement: {
	      type: Function,
	      required: true
	    }
	  },
	  emits: ['close'],
	  setup() {
	    return {
	      AirButtonStyle: ui_vue3_components_button.AirButtonStyle,
	      ButtonSize: ui_vue3_components_button.ButtonSize
	    };
	  },
	  data() {
	    return {
	      isPopupShown: false,
	      /** @type GroupInfo */
	      groupInfo: {}
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    group() {
	      return this.$store.getters[`${tasks_v2_const.Model.Groups}/getById`](this.task.groupId);
	    },
	    bindElement() {
	      return this.getBindElement();
	    },
	    popupId() {
	      return 'tasks-field-group-popup';
	    },
	    popupOptions() {
	      return {
	        bindElement: this.bindElement,
	        padding: 24,
	        minWidth: 260,
	        maxWidth: 400,
	        offsetTop: 8,
	        targetContainer: document.body
	      };
	    },
	    groupOwnerUrl() {
	      return tasks_v2_provider_service_userService.userService.getUrl(this.groupInfo.ownerId);
	    },
	    groupMembersCountFormatted() {
	      return main_core.Loc.getMessagePlural('TASKS_V2_GROUP_COUNT_MEMBERS', this.groupInfo.numberOfMembers, {
	        '#COUNT#': this.groupInfo.numberOfMembers
	      });
	    },
	    groupAboutFormatted() {
	      var _GroupType$Collab$Gro, _this$group;
	      return (_GroupType$Collab$Gro = {
	        [tasks_v2_const.GroupType.Collab]: this.loc('TASKS_V2_GROUP_ABOUT_COLLAB'),
	        [tasks_v2_const.GroupType.Scrum]: this.loc('TASKS_V2_GROUP_ABOUT_SCRUM')
	      }[(_this$group = this.group) == null ? void 0 : _this$group.type]) != null ? _GroupType$Collab$Gro : this.loc('TASKS_V2_GROUP_ABOUT');
	    }
	  },
	  mounted() {
	    main_core.Event.bind(this.bindElement, 'click', this.handleClick);
	    main_core.Event.bind(this.bindElement, 'mouseenter', this.handleMouseEnter);
	    main_core.Event.bind(this.bindElement, 'mouseleave', this.handleMouseLeave);
	  },
	  methods: {
	    openGroup() {
	      void tasks_v2_lib_openGroup.openGroup(this.group.id, this.group.type);
	    },
	    handleClick() {
	      this.clearTimeouts();
	    },
	    handleMouseEnter() {
	      if (!this.group) {
	        return;
	      }
	      this.groupInfoPromise = tasks_v2_provider_service_groupService.groupService.getGroupInfo(this.group.id);
	      this.clearTimeouts();
	      this.showTimeout = setTimeout(() => this.showPopup(), 400);
	    },
	    handleMouseLeave() {
	      main_core.Event.unbind(document, 'mouseover', this.updateHoverElement);
	      main_core.Event.bind(document, 'mouseover', this.updateHoverElement);
	      this.clearTimeouts();
	      this.closeTimeout = setTimeout(() => {
	        var _this$$refs$container;
	        if (!((_this$$refs$container = this.$refs.container) != null && _this$$refs$container.contains(this.hoverElement)) && !this.bindElement.contains(this.hoverElement)) {
	          this.closePopup();
	        }
	      }, 100);
	    },
	    updateHoverElement(event) {
	      this.hoverElement = event.target;
	    },
	    async showPopup() {
	      if (this.groupInfoPromise) {
	        this.groupInfo = await this.groupInfoPromise;
	      }
	      if (!this.group) {
	        return;
	      }
	      this.clearTimeouts();
	      this.isPopupShown = true;
	      await this.$nextTick();
	      this.$refs.popup.adjustPosition();
	    },
	    closePopup() {
	      this.clearTimeouts();
	      this.isPopupShown = false;
	      main_core.Event.unbind(this.$refs.container, 'mouseleave', this.handleMouseLeave);
	      main_core.Event.unbind(document, 'mouseover', this.updateHoverElement);
	    },
	    clearTimeouts() {
	      clearTimeout(this.closeTimeout);
	      clearTimeout(this.showTimeout);
	    }
	  },
	  template: `
		<Popup
			v-if="isPopupShown"
			:id="popupId"
			:options="popupOptions"
			ref="popup"
			@close="closePopup"
		>
			<div class="tasks-field-group-popup" ref="container">
				<div class="tasks-field-group-popup-header">
					<img class="tasks-field-group-popup-image" :src="group?.image" :alt="group?.name">
					<div class="tasks-field-group-popup-title-container">
						<div class="tasks-field-group-popup-title" :title="group?.name">{{ group?.name }}</div>
						<div class="tasks-field-group-popup-subtitle">{{ groupMembersCountFormatted }}</div>
					</div>
				</div>
				<div class="tasks-field-group-popup-button">
					<UiButton
						:text="groupAboutFormatted"
						:style="AirButtonStyle.OUTLINE_ACCENT_2"
						:size="ButtonSize.SMALL"
						:wide="true"
						@click="openGroup"
					/>
				</div>
				<div class="tasks-field-group-popup-info">
					<div class="tasks-field-group-popup-field">
						<div class="tasks-field-group-popup-field-title">{{ loc('TASKS_V2_GROUP_OWNER') }}</div>
						<div class="tasks-field-group-popup-field-value">
							<a :href="groupOwnerUrl">{{ groupInfo.ownerName }}</a>
						</div>
					</div>
					<div class="tasks-field-group-popup-field">
						<div class="tasks-field-group-popup-field-title">{{ loc('TASKS_V2_GROUP_DATE_CREATE') }}</div>
						<div class="tasks-field-group-popup-field-value">{{ groupInfo.dateCreate }}</div>
					</div>
					<div class="tasks-field-group-popup-field">
						<div class="tasks-field-group-popup-field-title">{{ loc('TASKS_V2_GROUP_SUBJECT') }}</div>
						<div class="tasks-field-group-popup-field-value">{{ groupInfo.subjectTitle }}</div>
					</div>
				</div>
			</div>
		</Popup>
	`
	};

	var _taskId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("taskId");
	var _dialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialog");
	var _onUpdateOnce = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onUpdateOnce");
	var _onUpdate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onUpdate");
	var _onHide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onHide");
	var _createDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createDialog");
	var _handleHide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleHide");
	var _handleItemChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleItemChange");
	var _insertSelectedGroup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("insertSelectedGroup");
	var _items = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("items");
	var _task = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("task");
	var _insertGroup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("insertGroup");
	var _updateGroup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateGroup");
	var _clearOnUpdateOnce = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("clearOnUpdateOnce");
	class GroupDialog {
	  constructor() {
	    Object.defineProperty(this, _updateGroup, {
	      value: _updateGroup2
	    });
	    Object.defineProperty(this, _insertGroup, {
	      value: _insertGroup2
	    });
	    Object.defineProperty(this, _task, {
	      get: _get_task,
	      set: void 0
	    });
	    Object.defineProperty(this, _items, {
	      get: _get_items,
	      set: void 0
	    });
	    Object.defineProperty(this, _handleItemChange, {
	      value: _handleItemChange2
	    });
	    Object.defineProperty(this, _createDialog, {
	      value: _createDialog2
	    });
	    Object.defineProperty(this, _taskId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _dialog, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _onUpdateOnce, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _onUpdate, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _onHide, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _handleHide, {
	      writable: true,
	      value: () => {
	        var _babelHelpers$classPr, _babelHelpers$classPr2;
	        (_babelHelpers$classPr = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _onHide))[_onHide]) == null ? void 0 : _babelHelpers$classPr.call(_babelHelpers$classPr2);
	        babelHelpers.classPrivateFieldLooseBase(this, _clearOnUpdateOnce)[_clearOnUpdateOnce]();
	      }
	    });
	    Object.defineProperty(this, _insertSelectedGroup, {
	      writable: true,
	      value: async () => {
	        const item = babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getSelectedItems()[0];
	        if (!item) {
	          return null;
	        }
	        await babelHelpers.classPrivateFieldLooseBase(this, _insertGroup)[_insertGroup]({
	          id: item.getId(),
	          name: item.getTitle(),
	          image: item.getAvatar(),
	          type: item.getEntityType()
	        });
	        return item;
	      }
	    });
	    Object.defineProperty(this, _clearOnUpdateOnce, {
	      writable: true,
	      value: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _onUpdateOnce)[_onUpdateOnce] = null;
	      }
	    });
	  }
	  setTaskId(taskId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId] = taskId;
	    return this;
	  }
	  init() {
	    var _babelHelpers$classPr3, _babelHelpers$classPr4;
	    (_babelHelpers$classPr4 = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _dialog))[_dialog]) != null ? _babelHelpers$classPr4 : _babelHelpers$classPr3[_dialog] = babelHelpers.classPrivateFieldLooseBase(this, _createDialog)[_createDialog]();
	  }
	  showTo(targetNode) {
	    this.init();
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].selectItemsByIds(babelHelpers.classPrivateFieldLooseBase(this, _items)[_items]);
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].showTo(targetNode);
	  }
	  onUpdateOnce(callback) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onUpdateOnce)[_onUpdateOnce] = callback;
	  }
	  onUpdate(callback) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onUpdate)[_onUpdate] = callback;
	  }
	  onHide(callback) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onHide)[_onHide] = callback;
	  }
	}
	function _createDialog2() {
	  const handleItemChangeDebounced = main_core.Runtime.debounce(babelHelpers.classPrivateFieldLooseBase(this, _handleItemChange)[_handleItemChange], 10, this);
	  return new tasks_v2_lib_entitySelectorDialog.EntitySelectorDialog({
	    context: 'tasks-card',
	    multiple: false,
	    hideOnDeselect: true,
	    enableSearch: true,
	    entities: [{
	      id: tasks_v2_const.EntitySelectorEntity.Project
	    }],
	    preselectedItems: babelHelpers.classPrivateFieldLooseBase(this, _items)[_items],
	    events: {
	      'Item:onSelect': handleItemChangeDebounced,
	      'Item:onDeselect': handleItemChangeDebounced,
	      onHide: babelHelpers.classPrivateFieldLooseBase(this, _handleHide)[_handleHide],
	      onDestroy: babelHelpers.classPrivateFieldLooseBase(this, _clearOnUpdateOnce)[_clearOnUpdateOnce],
	      onLoad: babelHelpers.classPrivateFieldLooseBase(this, _insertSelectedGroup)[_insertSelectedGroup]
	    }
	  });
	}
	async function _handleItemChange2() {
	  const item = await babelHelpers.classPrivateFieldLooseBase(this, _insertSelectedGroup)[_insertSelectedGroup]();
	  babelHelpers.classPrivateFieldLooseBase(this, _updateGroup)[_updateGroup](item == null ? void 0 : item.getId());
	}
	function _get_items() {
	  return [[tasks_v2_const.EntitySelectorEntity.Project, babelHelpers.classPrivateFieldLooseBase(this, _task)[_task].groupId]];
	}
	function _get_task() {
	  return tasks_v2_core.Core.getStore().getters[`${tasks_v2_const.Model.Tasks}/getById`](babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId]);
	}
	function _insertGroup2(group) {
	  return tasks_v2_core.Core.getStore().dispatch(`${tasks_v2_const.Model.Groups}/insert`, group);
	}
	function _updateGroup2(groupId) {
	  var _babelHelpers$classPr5, _babelHelpers$classPr6, _babelHelpers$classPr7, _babelHelpers$classPr8;
	  void tasks_v2_provider_service_taskService.taskService.update(babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId], {
	    groupId,
	    stageId: 0
	  });
	  (_babelHelpers$classPr5 = (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _onUpdate))[_onUpdate]) == null ? void 0 : _babelHelpers$classPr5.call(_babelHelpers$classPr6);
	  (_babelHelpers$classPr7 = (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _onUpdateOnce))[_onUpdateOnce]) == null ? void 0 : _babelHelpers$classPr7.call(_babelHelpers$classPr8);
	  babelHelpers.classPrivateFieldLooseBase(this, _clearOnUpdateOnce)[_clearOnUpdateOnce]();
	}
	const groupDialog = new GroupDialog();

	// @vue/component
	const Group = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    BMenu: ui_vue3_components_menu.BMenu,
	    Stage,
	    Scrum,
	    Hint: tasks_v2_component_elements_hint.Hint,
	    HoverPill: tasks_v2_component_elements_hoverPill.HoverPill,
	    FieldAdd: tasks_v2_component_elements_fieldAdd.FieldAdd,
	    GroupPopup
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      groupMeta,
	      Outline: ui_iconSet_api_core.Outline
	    };
	  },
	  data() {
	    return {
	      isMenuShown: false,
	      isHintShown: false
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    isEdit() {
	      return Number.isInteger(this.taskId) && this.taskId > 0;
	    },
	    group() {
	      return this.$store.getters[`${tasks_v2_const.Model.Groups}/getById`](this.task.groupId);
	    },
	    isScrum() {
	      var _this$group;
	      return ((_this$group = this.group) == null ? void 0 : _this$group.type) === tasks_v2_const.GroupType.Scrum;
	    },
	    menuOptions() {
	      return {
	        id: 'tasks-field-group-menu',
	        bindElement: this.$refs.group,
	        offsetTop: 8,
	        items: [{
	          title: this.getAboutItemTitle(),
	          icon: ui_iconSet_api_core.Outline.FOLDER,
	          onClick: this.openGroup
	        }, {
	          title: this.loc('TASKS_V2_GROUP_CHANGE'),
	          icon: ui_iconSet_api_core.Outline.EDIT_L,
	          onClick: this.showDialog
	        }, {
	          design: ui_vue3_components_menu.MenuItemDesign.Alert,
	          title: this.loc('TASKS_V2_GROUP_CLEAR'),
	          icon: ui_iconSet_api_core.Outline.CROSS_L,
	          onClick: this.clearField
	        }],
	        targetContainer: document.body
	      };
	    },
	    groupName() {
	      var _this$group$name, _this$group2;
	      return (_this$group$name = (_this$group2 = this.group) == null ? void 0 : _this$group2.name) != null ? _this$group$name : this.loc('TASKS_V2_GROUP_HIDDEN');
	    },
	    groupImage() {
	      var _this$group3;
	      return encodeURI((_this$group3 = this.group) == null ? void 0 : _this$group3.image);
	    },
	    hintText() {
	      return this.loc('TASKS_V2_GROUP_CANT_CHANGE_FLOW');
	    },
	    isSecret() {
	      return Boolean(this.task.groupId) && !this.group;
	    },
	    hasFlow() {
	      return this.task.flowId > 0;
	    },
	    readonly() {
	      return !this.task.rights.edit || this.hasFlow;
	    }
	  },
	  mounted() {
	    tasks_v2_lib_heightTransition.heightTransition.animate(this.$refs.container);
	  },
	  updated() {
	    tasks_v2_lib_heightTransition.heightTransition.animate(this.$refs.container);
	  },
	  methods: {
	    getAboutItemTitle() {
	      var _GroupType$Collab$Gro, _this$group4;
	      return (_GroupType$Collab$Gro = {
	        [tasks_v2_const.GroupType.Collab]: this.loc('TASKS_V2_GROUP_ABOUT_COLLAB'),
	        [tasks_v2_const.GroupType.Scrum]: this.loc('TASKS_V2_GROUP_ABOUT_SCRUM')
	      }[(_this$group4 = this.group) == null ? void 0 : _this$group4.type]) != null ? _GroupType$Collab$Gro : this.loc('TASKS_V2_GROUP_ABOUT');
	    },
	    handleClick() {
	      if (!this.isEdit && this.hasFlow) {
	        this.isHintShown = true;
	        return;
	      }
	      if (this.readonly) {
	        if (!this.isSecret) {
	          void this.openGroup();
	        }
	        return;
	      }
	      if (this.isEdit && this.group) {
	        this.showMenu();
	      } else {
	        this.showDialog();
	      }
	    },
	    showMenu() {
	      this.isMenuShown = true;
	    },
	    openGroup() {
	      void tasks_v2_lib_openGroup.openGroup(this.group.id, this.group.type);
	    },
	    showDialog() {
	      groupDialog.setTaskId(this.taskId).showTo(this.$refs.group);
	    },
	    clearField() {
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        groupId: 0,
	        stageId: 0
	      });
	    }
	  },
	  template: `
		<div
			:data-task-id="taskId"
			:data-task-field-id="groupMeta.id"
			:data-task-field-value="task.groupId"
			ref="container"
		>
			<div class="tasks-field-group-group" :class="{ '--secret': isSecret }" ref="group" @click="handleClick">
				<HoverPill
					v-if="task.groupId"
					:withClear="!readonly && !isEdit && (task.flowId ?? 0) <= 0"
					@clear="clearField"
				>
					<img v-if="groupImage" class="tasks-field-group-image" :src="groupImage" :alt="groupName"/>
					<BIcon v-else class="tasks-field-group-icon" :name="Outline.FOLDER"/>
					<div class="tasks-field-group-title">{{ groupName }}</div>
				</HoverPill>
				<FieldAdd v-else :icon="Outline.FOLDER_PLUS"/>
			</div>
			<Stage v-if="isEdit && group && !task.flowId" :taskId="taskId"/>
			<Scrum v-if="isScrum && !task.flowId" :taskId="taskId"/>
		</div>
		<Hint
			v-if="isHintShown"
			:bindElement="$refs.container"
			@close="isHintShown = false"
		>
			{{ hintText }}
		</Hint>
		<BMenu v-if="isMenuShown" :options="menuOptions" @close="isMenuShown = false"/>
		<GroupPopup :taskId="taskId" :getBindElement="() => $refs.group"/>
	`
	};

	// @vue/component
	const GroupChip = {
	  components: {
	    Chip: tasks_v2_component_elements_chip.Chip,
	    Hint: tasks_v2_component_elements_hint.Hint,
	    GroupPopup
	  },
	  inject: ['analytics', 'cardType'],
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    isAutonomous: {
	      type: Boolean,
	      default: false
	    }
	  },
	  setup() {
	    return {
	      groupMeta
	    };
	  },
	  data() {
	    return {
	      doShowHint: false
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    group() {
	      return this.$store.getters[`${tasks_v2_const.Model.Groups}/getById`](this.task.groupId);
	    },
	    design() {
	      return {
	        [!this.isAutonomous && !this.isSelected]: tasks_v2_component_elements_chip.ChipDesign.ShadowNoAccent,
	        [!this.isAutonomous && this.isSelected]: tasks_v2_component_elements_chip.ChipDesign.ShadowAccent,
	        [this.isAutonomous && !this.isSelected]: tasks_v2_component_elements_chip.ChipDesign.OutlineNoAccent,
	        [this.isAutonomous && this.isSelected]: tasks_v2_component_elements_chip.ChipDesign.OutlineAccent
	      }.true;
	    },
	    isSelected() {
	      if (this.isAutonomous) {
	        return this.task.groupId > 0;
	      }
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/wasFieldFilled`](this.taskId, groupMeta.id);
	    },
	    isFilled() {
	      return Boolean(this.isAutonomous && this.group);
	    },
	    isFlowFilled() {
	      return this.task.flowId > 0;
	    },
	    text() {
	      if (this.isFilled) {
	        var _this$group$name, _this$group;
	        return (_this$group$name = (_this$group = this.group) == null ? void 0 : _this$group.name) != null ? _this$group$name : this.loc('TASKS_V2_GROUP_HIDDEN');
	      }
	      return this.loc('TASKS_V2_GROUP_TITLE_CHIP');
	    },
	    icon() {
	      if (this.isFilled) {
	        return null;
	      }
	      return ui_iconSet_api_core.Outline.FOLDER;
	    },
	    image() {
	      var _this$group2, _this$group3;
	      if (!this.isFilled) {
	        return null;
	      }
	      return {
	        src: encodeURI((_this$group2 = this.group) == null ? void 0 : _this$group2.image),
	        alt: (_this$group3 = this.group) == null ? void 0 : _this$group3.name
	      };
	    },
	    canChange() {
	      var _this$task$flowId;
	      return ((_this$task$flowId = this.task.flowId) != null ? _this$task$flowId : 0) <= 0;
	    },
	    hintText() {
	      return this.loc('TASKS_V2_GROUP_CANT_CHANGE_FLOW');
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    },
	    isLocked() {
	      return !tasks_v2_core.Core.getParams().limits.project;
	    }
	  },
	  created() {
	    groupDialog.setTaskId(this.taskId).init();
	  },
	  methods: {
	    handleClick() {
	      if (this.isLocked) {
	        void main_core.Runtime.loadExtension('tasks.limit').then(exports => {
	          const {
	            Limit
	          } = exports;
	          Limit.showInstance({
	            featureId: tasks_v2_core.Core.getParams().limits.projectFeatureId
	          });
	        });
	        return;
	      }
	      if (!this.isAutonomous && this.isSelected) {
	        this.highlightField();
	        return;
	      }
	      if (this.isFlowFilled) {
	        this.doShowHint = true;
	        return;
	      }
	      groupDialog.setTaskId(this.taskId).showTo(this.$refs.chip.$el);
	      if (!this.isAutonomous) {
	        groupDialog.onUpdateOnce(this.highlightField);
	      }
	      groupDialog.onHide(this.handleFieldClose);
	      groupDialog.onUpdate(this.handleUpdate);
	    },
	    highlightField() {
	      void tasks_v2_lib_fieldHighlighter.fieldHighlighter.setContainer(this.$root.$el).highlight(groupMeta.id);
	    },
	    handleClear() {
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        groupId: 0,
	        stageId: 0
	      });
	    },
	    handleFieldClose() {
	      if (this.isAutonomous) {
	        var _this$$refs$chip;
	        (_this$$refs$chip = this.$refs.chip) == null ? void 0 : _this$$refs$chip.$el.focus();
	      }
	    },
	    handleUpdate() {
	      tasks_v2_lib_analytics.analytics.sendAddProject(this.analytics, {
	        cardType: this.cardType,
	        viewersCount: this.task.auditorsIds.length,
	        coexecutorsCount: this.task.accomplicesIds.length
	      });
	    }
	  },
	  template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:icon="icon"
			:image="image"
			:text="text"
			:withClear="isFilled && !isFlowFilled"
			:lock="isLocked"
			:trimmable="isFilled"
			:data-task-id="taskId"
			:data-task-chip-id="groupMeta.id"
			:data-task-chip-value="task.groupId"
			ref="chip"
			@click="handleClick"
			@clear="handleClear"
		/>
		<Hint
			v-if="doShowHint"
			:bindElement="$refs.chip.$el"
			@close="doShowHint = false"
		>
			{{ hintText }}
		</Hint>
		<GroupPopup v-if="isAutonomous" :taskId="taskId" :getBindElement="() => $refs.chip.$el"/>
	`
	};

	exports.Group = Group;
	exports.Stage = Stage;
	exports.GroupChip = GroupChip;
	exports.groupMeta = groupMeta;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Lib,BX.UI.Vue3.Components,BX,BX.Tasks.V2.Component.Elements,BX.UI.IconSet,BX.Tasks.V2.Lib,BX.Vue3.Directives,BX.UI.Vue3.Components,BX.Vue3.Components,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Lib,BX,BX.UI.IconSet,BX,BX.Tasks.V2,BX.Tasks.V2.Const,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Lib,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service));
//# sourceMappingURL=group.bundle.js.map
