/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,ui_system_skeleton_vue,ui_iconSet_api_vue,tasks_v2_component_elements_addBackground,tasks_v2_component_elements_fieldAdd,ui_vue3_components_richLoc,ui_system_typography_vue,ui_system_menu_vue,tasks_v2_component_elements_hoverPill,tasks_v2_lib_hrefClick,main_core,tasks_v2_core,tasks_v2_provider_service_crmService,tasks_v2_provider_service_taskService,tasks_v2_lib_entitySelectorDialog,ui_system_chip_vue,ui_iconSet_api_core,ui_iconSet_outline,tasks_v2_const,tasks_v2_lib_fieldHighlighter) {
	'use strict';

	// @vue/component
	const CrmItem = {
	  components: {
	    HoverPill: tasks_v2_component_elements_hoverPill.HoverPill,
	    TextMd: ui_system_typography_vue.TextMd,
	    RichLoc: ui_vue3_components_richLoc.RichLoc,
	    BMenu: ui_system_menu_vue.BMenu
	  },
	  props: {
	    /**
	     * @type CrmItemModel
	     */
	    item: {
	      type: Object,
	      required: true
	    },
	    isEdit: {
	      type: Boolean,
	      required: true
	    },
	    readonly: {
	      type: Boolean,
	      required: true
	    }
	  },
	  emits: ['edit', 'clear'],
	  data() {
	    return {
	      isMenuShown: false
	    };
	  },
	  computed: {
	    menuOptions() {
	      return {
	        id: `tasks-crm-menu-${this.item.id}`,
	        bindElement: this.$refs.pill.$el,
	        offsetTop: 8,
	        items: this.menuItems,
	        targetContainer: document.body
	      };
	    },
	    menuItems() {
	      return [{
	        title: this.loc('TASKS_V2_CRM_OPEN'),
	        icon: ui_iconSet_api_core.Outline.GO_TO_L,
	        dataset: {
	          id: 'tasks-crm-menu-open'
	        },
	        onClick: () => tasks_v2_lib_hrefClick.hrefClick(this.item.link)
	      }, {
	        title: this.loc('TASKS_V2_CRM_EDIT'),
	        icon: ui_iconSet_api_core.Outline.EDIT_L,
	        dataset: {
	          id: 'tasks-crm-menu-edit'
	        },
	        onClick: () => this.$emit('edit')
	      }, {
	        title: this.loc('TASKS_V2_CRM_UNLINK'),
	        icon: ui_iconSet_api_core.Outline.CROSS_L,
	        dataset: {
	          id: 'tasks-crm-menu-unlink'
	        },
	        onClick: this.clear
	      }];
	    }
	  },
	  methods: {
	    prepareTitle(item) {
	      return this.loc('TASKS_V2_CRM_ENTITY_TITLE', {
	        '#TYPE_NAME#': item.typeName,
	        '#TITLE#': item.title
	      });
	    },
	    handleClick() {
	      if (!this.isEdit) {
	        tasks_v2_lib_hrefClick.hrefClick(this.item.link);
	        return;
	      }
	      if (this.readonly) {
	        return;
	      }
	      this.isMenuShown = true;
	    },
	    clear() {
	      this.$emit('clear', this.item.id);
	    }
	  },
	  template: `
		<div class="tasks-field-crm-item-container">
			<HoverPill
				class="tasks-field-crm-item"
				:withClear="!readonly && !isEdit"
				:readonly="readonly"
				ref="pill"
				@click.stop="handleClick"
				@clear="clear"
			>
				<TextMd class="tasks-field-crm-item-text">
					<RichLoc tag="span" :text="prepareTitle(item)" placeholder="[a]">
						<template #a="{ text }">
							<a @click.prevent>{{ text }}</a>
						</template>
					</RichLoc>
				</TextMd>
			</HoverPill>
		</div>
		<BMenu v-if="isMenuShown" :options="menuOptions" @close="isMenuShown = false"/>
	`
	};

	const crmMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.Crm,
	  title: main_core.Loc.getMessage('TASKS_V2_CRM_TITLE')
	});

	const crmIntegration = main_core.Extension.getSettings('tasks.v2.component.fields.crm').crmIntegration;
	const dynamicTypeIds = Object.entries(crmIntegration).filter(([entityId, enabled]) => enabled === 'Y' && entityId.startsWith('DYNAMIC_')).map(([entityId]) => Number(entityId.slice(8)));
	var _taskId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("taskId");
	var _dialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialog");
	var _onUpdateOnce = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onUpdateOnce");
	var _onCloseOnce = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCloseOnce");
	var _createDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createDialog");
	var _handleItemChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleItemChange");
	var _insertSelectedCrmItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("insertSelectedCrmItems");
	var _updateTask = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateTask");
	var _mapItemToModel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mapItemToModel");
	var _clearOnUpdateOnce = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("clearOnUpdateOnce");
	var _items = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("items");
	var _task = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("task");
	var _insertCrmItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("insertCrmItems");
	class CrmDialog {
	  constructor() {
	    Object.defineProperty(this, _insertCrmItems, {
	      value: _insertCrmItems2
	    });
	    Object.defineProperty(this, _task, {
	      get: _get_task,
	      set: void 0
	    });
	    Object.defineProperty(this, _items, {
	      get: _get_items,
	      set: void 0
	    });
	    Object.defineProperty(this, _mapItemToModel, {
	      value: _mapItemToModel2
	    });
	    Object.defineProperty(this, _updateTask, {
	      value: _updateTask2
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
	    Object.defineProperty(this, _onCloseOnce, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _insertSelectedCrmItems, {
	      writable: true,
	      value: async () => {
	        const crmItems = babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getSelectedItems().map(item => babelHelpers.classPrivateFieldLooseBase(this, _mapItemToModel)[_mapItemToModel](item));
	        await babelHelpers.classPrivateFieldLooseBase(this, _insertCrmItems)[_insertCrmItems](crmItems);
	        return crmItems;
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
	  showTo(targetNode) {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _dialog))[_dialog]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[_dialog] = babelHelpers.classPrivateFieldLooseBase(this, _createDialog)[_createDialog]();
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].selectItemsByIds(babelHelpers.classPrivateFieldLooseBase(this, _items)[_items]);
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].showTo(targetNode);
	  }
	  onUpdateOnce(callback) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onUpdateOnce)[_onUpdateOnce] = callback;
	  }
	  onCloseOnce(callback) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onCloseOnce)[_onCloseOnce] = callback;
	    return this;
	  }
	  get $store() {
	    return tasks_v2_core.Core.getStore();
	  }
	}
	function _createDialog2() {
	  let changed = false;
	  const handleChanged = () => {
	    changed = true;
	  };
	  const handleClose = async () => {
	    var _babelHelpers$classPr3, _babelHelpers$classPr4;
	    if (changed) {
	      void babelHelpers.classPrivateFieldLooseBase(this, _handleItemChange)[_handleItemChange]();
	      changed = false;
	    }
	    (_babelHelpers$classPr3 = (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _onCloseOnce))[_onCloseOnce]) == null ? void 0 : _babelHelpers$classPr3.call(_babelHelpers$classPr4);
	    babelHelpers.classPrivateFieldLooseBase(this, _onCloseOnce)[_onCloseOnce] = null;
	  };
	  return new tasks_v2_lib_entitySelectorDialog.EntitySelectorDialog({
	    context: 'tasks-card',
	    multiple: true,
	    enableSearch: true,
	    entities: [tasks_v2_const.EntitySelectorEntity.Deal, tasks_v2_const.EntitySelectorEntity.Contact, tasks_v2_const.EntitySelectorEntity.Company, tasks_v2_const.EntitySelectorEntity.Lead, tasks_v2_const.EntitySelectorEntity.SmartInvoice, tasks_v2_const.EntitySelectorEntity.DynamicMultiple].map(entityId => ({
	      id: entityId,
	      dynamicLoad: true,
	      dynamicSearch: true,
	      options: {
	        dynamicTypeIds,
	        showTab: true
	      }
	    })),
	    preselectedItems: babelHelpers.classPrivateFieldLooseBase(this, _items)[_items],
	    events: {
	      'Item:onSelect': handleChanged,
	      'Item:onDeselect': handleChanged,
	      onHide: babelHelpers.classPrivateFieldLooseBase(this, _clearOnUpdateOnce)[_clearOnUpdateOnce],
	      onDestroy: babelHelpers.classPrivateFieldLooseBase(this, _clearOnUpdateOnce)[_clearOnUpdateOnce],
	      onLoad: babelHelpers.classPrivateFieldLooseBase(this, _insertSelectedCrmItems)[_insertSelectedCrmItems]
	    },
	    popupOptions: {
	      events: {
	        onClose: handleClose
	      }
	    }
	  });
	}
	async function _handleItemChange2() {
	  const crmItems = await babelHelpers.classPrivateFieldLooseBase(this, _insertSelectedCrmItems)[_insertSelectedCrmItems]();
	  babelHelpers.classPrivateFieldLooseBase(this, _updateTask)[_updateTask](crmItems.map(({
	    id
	  }) => id));
	}
	function _updateTask2(crmItemIds) {
	  var _babelHelpers$classPr5, _babelHelpers$classPr6;
	  void tasks_v2_provider_service_taskService.taskService.update(babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId], {
	    crmItemIds
	  });
	  (_babelHelpers$classPr5 = (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _onUpdateOnce))[_onUpdateOnce]) == null ? void 0 : _babelHelpers$classPr5.call(_babelHelpers$classPr6);
	  babelHelpers.classPrivateFieldLooseBase(this, _clearOnUpdateOnce)[_clearOnUpdateOnce]();
	}
	function _mapItemToModel2(item) {
	  const entityInfo = item.getCustomData().get('entityInfo');
	  return {
	    id: tasks_v2_provider_service_crmService.CrmMappers.mapId(entityInfo.type, item.getId()),
	    entityId: item.getId(),
	    type: item.getEntityId(),
	    typeName: entityInfo.typeNameTitle,
	    title: item.getTitle(),
	    link: entityInfo.url
	  };
	}
	function _get_items() {
	  var _babelHelpers$classPr7, _babelHelpers$classPr8;
	  return (_babelHelpers$classPr7 = (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _task)[_task].crmItemIds) == null ? void 0 : _babelHelpers$classPr8.map(id => tasks_v2_provider_service_crmService.CrmMappers.splitId(id))) != null ? _babelHelpers$classPr7 : [];
	}
	function _get_task() {
	  return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId]);
	}
	async function _insertCrmItems2(crmItems) {
	  await this.$store.dispatch(`${tasks_v2_const.Model.CrmItems}/upsertMany`, crmItems);
	}
	const crmDialog = new CrmDialog();

	const maxCount = 7;

	// @vue/component
	const Crm = {
	  components: {
	    AddBackground: tasks_v2_component_elements_addBackground.AddBackground,
	    TextSm: ui_system_typography_vue.TextSm,
	    BLine: ui_system_skeleton_vue.BLine,
	    FieldAdd: tasks_v2_component_elements_fieldAdd.FieldAdd,
	    CrmItem
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_vue.Outline,
	      crmMeta
	    };
	  },
	  data() {
	    return {
	      isDialogShown: false,
	      isExpanded: false
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    isEdit() {
	      return Number.isInteger(this.taskId) && this.taskId > 0;
	    },
	    crmItems() {
	      return this.$store.getters[`${tasks_v2_const.Model.CrmItems}/getByIds`](this.task.crmItemIds);
	    },
	    visibleItems() {
	      return this.crmItems.slice(0, maxCount);
	    },
	    collapsedItems() {
	      return this.crmItems.slice(maxCount);
	    },
	    isLoading() {
	      var _this$task$crmItemIds, _this$crmItems;
	      return ((_this$task$crmItemIds = this.task.crmItemIds) == null ? void 0 : _this$task$crmItemIds.length) && !((_this$crmItems = this.crmItems) != null && _this$crmItems.length);
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    },
	    expandButtonText() {
	      if (this.isExpanded) {
	        return this.loc('TASKS_V2_CRM_COLLAPSE');
	      }
	      return this.loc('TASKS_V2_CRM_AND_COUNT', {
	        '#COUNT#': this.collapsedItems.length
	      });
	    }
	  },
	  mounted() {
	    void tasks_v2_provider_service_crmService.crmService.list(this.taskId, this.task.crmItemIds);
	  },
	  methods: {
	    handleClick() {
	      if (this.readonly) {
	        return;
	      }
	      this.showDialog();
	    },
	    showDialog() {
	      crmDialog.setTaskId(this.taskId).onCloseOnce(this.handleClose).showTo(this.$refs.anchor);
	      this.isDialogShown = true;
	    },
	    handleClose() {
	      this.isDialogShown = false;
	    },
	    handleClear(crmItemId) {
	      const crmItemIds = this.task.crmItemIds.filter(id => id !== crmItemId);
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        crmItemIds
	      });
	    }
	  },
	  template: `
		<AddBackground v-if="!readonly" :isActive="isDialogShown" @click="handleClick"/>
		<div
			class="tasks-field-crm"
			:data-task-id="taskId"
			:data-task-field-id="crmMeta.id"
			:data-task-crm-item-ids="task.crmItemIds?.join(',')"
		>
			<FieldAdd v-if="!task.crmItemIds?.length" :icon="Outline.CRM"/>
			<div v-if="isLoading" class="tasks-field-crm-skeleton">
				<template v-for="crmItemId in task.crmItemIds" :key="crmItemId">
					<BLine :height="20"/>
				</template>
			</div>
			<template v-for="item in visibleItems" :key="item.id">
				<CrmItem
					:item="item"
					:isEdit="isEdit"
					:readonly="readonly"
					@edit="showDialog"
					@clear="handleClear(item.id)"
				/>
			</template>
			<template v-if="isExpanded" v-for="item in collapsedItems" :key="item.id">
				<CrmItem
					:item="item"
					:isEdit="isEdit"
					:readonly="readonly"
					@edit="showDialog"
					@clear="handleClear(item.id)"
				/>
			</template>
			<TextSm
				v-if="collapsedItems.length > 0"
				class="tasks-field-crm-expand"
				@click.capture.stop="isExpanded = !isExpanded"
			>
				{{ expandButtonText }}
			</TextSm>
		</div>
		<div class="tasks-field-crm-anchor" ref="anchor"></div>
	`
	};

	// @vue/component
	const CrmChip = {
	  components: {
	    Chip: ui_system_chip_vue.Chip
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_core.Outline,
	      crmMeta
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    design() {
	      return this.isSelected ? ui_system_chip_vue.ChipDesign.ShadowAccent : ui_system_chip_vue.ChipDesign.ShadowNoAccent;
	    },
	    isSelected() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/wasFieldFilled`](this.taskId, crmMeta.id);
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    }
	  },
	  methods: {
	    handleClick() {
	      if (this.isSelected) {
	        this.highlightField();
	        return;
	      }
	      crmDialog.setTaskId(this.taskId).showTo(this.$el);
	      crmDialog.onUpdateOnce(this.highlightField);
	    },
	    highlightField() {
	      void tasks_v2_lib_fieldHighlighter.fieldHighlighter.setContainer(this.$root.$el).highlight(crmMeta.id);
	    }
	  },
	  template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:icon="Outline.CRM"
			:text="loc('TASKS_V2_CRM_TITLE_CHIP')"
			:data-task-id="taskId"
			:data-task-chip-id="crmMeta.id"
			:data-task-crm-item-ids="task.crmItemIds?.join(',')"
			@click="handleClick"
		/>
	`
	};

	exports.Crm = Crm;
	exports.CrmChip = CrmChip;
	exports.crmMeta = crmMeta;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX.UI.System.Skeleton.Vue,BX.UI.IconSet,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component.Elements,BX.UI.Vue3.Components,BX.UI.System.Typography.Vue,BX.UI.System.Menu,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Lib,BX,BX.Tasks.V2,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Lib,BX.UI.System.Chip.Vue,BX.UI.IconSet,BX,BX.Tasks.V2.Const,BX.Tasks.V2.Lib));
//# sourceMappingURL=crm.bundle.js.map
