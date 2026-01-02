/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,main_sidepanel,ui_vue3_vuex,ui_vue3_components_menu,ui_iconSet_api_vue,tasks_v2_component_elements_hoverPill,tasks_v2_component_elements_fieldAdd,tasks_v2_component_fields_group,tasks_v2_provider_service_flowService,main_core,tasks_flow_entitySelector,tasks_v2_core,tasks_v2_lib_entitySelectorDialog,tasks_v2_provider_service_groupService,ui_iconSet_api_core,ui_iconSet_outline,tasks_v2_const,tasks_v2_component_elements_chip,tasks_v2_lib_fieldHighlighter,tasks_v2_provider_service_taskService) {
	'use strict';

	const flowMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.Flow,
	  title: main_core.Loc.getMessage('TASKS_V2_FLOW_TITLE')
	});

	var _taskId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("taskId");
	var _dialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialog");
	var _onUpdateOnce = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onUpdateOnce");
	var _createDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createDialog");
	var _onItemChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onItemChange");
	var _items = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("items");
	var _task = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("task");
	var _insertFlow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("insertFlow");
	var _updateFlow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateFlow");
	var _clearOnUpdateOnce = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("clearOnUpdateOnce");
	class FlowDialog {
	  constructor() {
	    Object.defineProperty(this, _updateFlow, {
	      value: _updateFlow2
	    });
	    Object.defineProperty(this, _insertFlow, {
	      value: _insertFlow2
	    });
	    Object.defineProperty(this, _task, {
	      get: _get_task,
	      set: void 0
	    });
	    Object.defineProperty(this, _items, {
	      get: _get_items,
	      set: void 0
	    });
	    Object.defineProperty(this, _onItemChange, {
	      value: _onItemChange2
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
	  showTo(targetNode) {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _dialog))[_dialog]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[_dialog] = babelHelpers.classPrivateFieldLooseBase(this, _createDialog)[_createDialog]();
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].selectItemsByIds(babelHelpers.classPrivateFieldLooseBase(this, _items)[_items]);
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].showTo(targetNode);
	  }
	  onUpdateOnce(callback) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onUpdateOnce)[_onUpdateOnce] = callback;
	  }
	}
	function _createDialog2() {
	  var _Core$getParams$right, _Core$getParams$right2;
	  const onItemChangeDebounced = main_core.Runtime.debounce(babelHelpers.classPrivateFieldLooseBase(this, _onItemChange)[_onItemChange], 10, this);
	  const dialog = new tasks_v2_lib_entitySelectorDialog.EntitySelectorDialog({
	    context: 'tasks-card',
	    width: 380,
	    height: 370,
	    multiple: false,
	    hideOnDeselect: true,
	    enableSearch: true,
	    entities: [{
	      id: tasks_v2_const.EntitySelectorEntity.Flow,
	      options: {
	        onlyActive: true
	      }
	    }],
	    preselectedItems: babelHelpers.classPrivateFieldLooseBase(this, _items)[_items],
	    events: {
	      'Item:onSelect': onItemChangeDebounced,
	      'Item:onDeselect': onItemChangeDebounced,
	      onHide: babelHelpers.classPrivateFieldLooseBase(this, _clearOnUpdateOnce)[_clearOnUpdateOnce],
	      onDestroy: babelHelpers.classPrivateFieldLooseBase(this, _clearOnUpdateOnce)[_clearOnUpdateOnce]
	    },
	    recentTabOptions: {
	      stub: tasks_flow_entitySelector.EmptyStub,
	      stubOptions: {
	        showArrow: false
	      }
	    }
	  });
	  if ((_Core$getParams$right = tasks_v2_core.Core.getParams().rights) != null && (_Core$getParams$right2 = _Core$getParams$right.flow) != null && _Core$getParams$right2.create) {
	    const isFeatureTriable = main_core.Extension.getSettings('tasks.v2.component.fields.flow').get('isFeatureTriable');
	    const footer = new tasks_flow_entitySelector.Footer(dialog, {
	      isFeatureTriable
	    });
	    dialog.setFooter(footer.render());
	  }
	  return dialog;
	}
	async function _onItemChange2() {
	  var _item$getId;
	  const item = babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getSelectedItems()[0];
	  const groupId = item == null ? void 0 : item.getCustomData().get('groupId');
	  if (item) {
	    babelHelpers.classPrivateFieldLooseBase(this, _insertFlow)[_insertFlow]({
	      id: item.getId(),
	      name: item.getTitle()
	    });
	    const group = tasks_v2_core.Core.getStore().getters[`${tasks_v2_const.Model.Groups}/getById`](groupId);
	    if (!group) {
	      await tasks_v2_provider_service_groupService.groupService.getGroup(groupId);
	    }
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _updateFlow)[_updateFlow]((_item$getId = item == null ? void 0 : item.getId()) != null ? _item$getId : 0, groupId != null ? groupId : 0);
	}
	function _get_items() {
	  return [[tasks_v2_const.EntitySelectorEntity.Flow, babelHelpers.classPrivateFieldLooseBase(this, _task)[_task].flowId]];
	}
	function _get_task() {
	  return tasks_v2_core.Core.getStore().getters[`${tasks_v2_const.Model.Tasks}/getById`](babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId]);
	}
	function _insertFlow2(flow) {
	  void tasks_v2_core.Core.getStore().dispatch(`${tasks_v2_const.Model.Flows}/insert`, flow);
	}
	function _updateFlow2(flowId, groupId) {
	  var _babelHelpers$classPr3, _babelHelpers$classPr4;
	  const creatorId = tasks_v2_core.Core.getStore().getters[`${tasks_v2_const.Model.Interface}/currentUserId`];
	  void tasks_v2_provider_service_taskService.taskService.update(babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId], {
	    creatorId,
	    flowId,
	    groupId,
	    stageId: 0
	  });
	  (_babelHelpers$classPr3 = (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _onUpdateOnce))[_onUpdateOnce]) == null ? void 0 : _babelHelpers$classPr3.call(_babelHelpers$classPr4);
	  babelHelpers.classPrivateFieldLooseBase(this, _clearOnUpdateOnce)[_clearOnUpdateOnce]();
	}
	const flowDialog = new FlowDialog();

	// @vue/component
	const Flow = {
	  name: 'TaskFlow',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    BMenu: ui_vue3_components_menu.BMenu,
	    HoverPill: tasks_v2_component_elements_hoverPill.HoverPill,
	    FieldAdd: tasks_v2_component_elements_fieldAdd.FieldAdd,
	    Stage: tasks_v2_component_fields_group.Stage
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      flowMeta,
	      Outline: ui_iconSet_api_vue.Outline
	    };
	  },
	  data() {
	    return {
	      isMenuShown: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      currentUserId: `${tasks_v2_const.Model.Interface}/currentUserId`
	    }),
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    flow() {
	      return this.$store.getters[`${tasks_v2_const.Model.Flows}/getById`](this.task.flowId);
	    },
	    isEdit() {
	      return Number.isInteger(this.taskId) && this.taskId > 0;
	    },
	    menuOptions() {
	      return {
	        id: 'tasks-field-flow-menu',
	        bindElement: this.$refs.container,
	        offsetTop: 8,
	        items: [{
	          title: this.loc('TASKS_V2_FLOW_ABOUT'),
	          icon: ui_iconSet_api_vue.Outline.BOTTLENECK,
	          onClick: this.openFlow
	        }, {
	          title: this.loc('TASKS_V2_FLOW_CHANGE'),
	          icon: ui_iconSet_api_vue.Outline.EDIT_L,
	          onClick: this.showDialog
	        }, {
	          design: ui_vue3_components_menu.MenuItemDesign.Alert,
	          title: this.loc('TASKS_V2_FLOW_DETACH'),
	          icon: ui_iconSet_api_vue.Outline.CROSS_L,
	          onClick: this.clearField
	        }],
	        targetContainer: document.body
	      };
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    }
	  },
	  methods: {
	    handleClick() {
	      if (this.flow && this.readonly) {
	        this.openFlow();
	        return;
	      }
	      if (this.isEdit && this.flow) {
	        this.showMenu();
	      } else {
	        this.showDialog();
	      }
	    },
	    showMenu() {
	      this.isMenuShown = true;
	    },
	    handleClear() {
	      this.clearField();
	    },
	    openFlow() {
	      const href = tasks_v2_provider_service_flowService.flowService.getUrl(this.flow.id, this.currentUserId);
	      main_sidepanel.SidePanel.Instance.open(href);
	    },
	    clearField() {
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        flowId: 0,
	        groupId: 0,
	        stageId: 0
	      });
	    },
	    showDialog() {
	      flowDialog.setTaskId(this.taskId).showTo(this.$refs.container);
	    }
	  },
	  template: `
		<div
			:data-task-id="taskId"
			:data-task-field-id="flowMeta.id"
			:data-task-field-value="task.flowId"
		>
			<HoverPill
				v-if="flow"
				:withClear="!readonly && !isEdit"
				@click="handleClick"
				@clear="handleClear"
			>
				<div class="tasks-field-flow">
					<BIcon :name="Outline.BOTTLENECK"/>
					<div class="tasks-field-flow-title">{{ flow.name }}</div>
				</div>
			</HoverPill>
			<FieldAdd v-else :icon="Outline.BOTTLENECK" @click="handleClick"/>
			<Stage v-if="isEdit && flow" :taskId="taskId"/>
		</div>
		<BMenu v-if="isMenuShown" :options="menuOptions" @close="isMenuShown = false"/>
	`
	};

	// @vue/component
	const FlowChip = {
	  components: {
	    Chip: tasks_v2_component_elements_chip.Chip
	  },
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
	      Outline: ui_iconSet_api_core.Outline,
	      flowMeta
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    flow() {
	      return this.$store.getters[`${tasks_v2_const.Model.Flows}/getById`](this.task.flowId);
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
	        return this.task.flowId > 0;
	      }
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/wasFieldFilled`](this.taskId, flowMeta.id);
	    },
	    isFilled() {
	      return this.isAutonomous && this.task.flowId > 0;
	    },
	    text() {
	      if (this.isFilled) {
	        return this.flow.name;
	      }
	      return this.loc('TASKS_V2_FLOW_TITLE_CHIP');
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    }
	  },
	  methods: {
	    handleClick() {
	      if (!this.isAutonomous && this.isSelected) {
	        this.highlightField();
	        return;
	      }
	      flowDialog.setTaskId(this.taskId).showTo(this.$el);
	      if (!this.isAutonomous) {
	        flowDialog.onUpdateOnce(this.highlightField);
	      }
	    },
	    highlightField() {
	      void tasks_v2_lib_fieldHighlighter.fieldHighlighter.setContainer(this.$root.$el).highlight(flowMeta.id);
	    },
	    handleClear() {
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        flowId: 0,
	        groupId: 0
	      });
	    }
	  },
	  // TODO: remove title prop when flow popup added
	  template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:icon="Outline.BOTTLENECK"
			:text="text"
			:withClear="isFilled"
			:trimmable="isFilled"
			:data-task-id="taskId"
			:data-task-chip-id="flowMeta.id"
			:data-task-chip-value="task.flowId"
			@click="handleClick"
			@clear="handleClear"
			:title="flow?.name ?? ''"
		/>
	`
	};

	exports.Flow = Flow;
	exports.FlowChip = FlowChip;
	exports.flowMeta = flowMeta;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX.SidePanel,BX.Vue3.Vuex,BX.UI.Vue3.Components,BX.UI.IconSet,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Provider.Service,BX,BX.Tasks.Flow,BX.Tasks.V2,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service,BX.UI.IconSet,BX,BX.Tasks.V2.Const,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service));
//# sourceMappingURL=flow.bundle.js.map
