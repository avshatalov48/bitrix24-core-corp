/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,ui_iconSet_api_vue,tasks_v2_component_elements_addBackground,tasks_v2_component_elements_fieldAdd,main_core,tasks_entitySelector,tasks_v2_lib_entitySelectorDialog,tasks_v2_core,tasks_v2_provider_service_taskService,ui_iconSet_api_core,ui_iconSet_outline,tasks_v2_const,tasks_v2_component_elements_chip,tasks_v2_lib_fieldHighlighter) {
	'use strict';

	const tagsMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.Tags,
	  title: main_core.Loc.getMessage('TASKS_V2_TAGS_TITLE')
	});

	const dialogs = {};
	var _taskId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("taskId");
	var _onCloseOnce = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCloseOnce");
	var _onUpdateOnce = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onUpdateOnce");
	var _getItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getItems");
	var _getDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDialog");
	var _updateTask = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateTask");
	var _getTask = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTask");
	var _currentUserId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentUserId");
	class TagsDialog {
	  constructor() {
	    Object.defineProperty(this, _currentUserId, {
	      get: _get_currentUserId,
	      set: void 0
	    });
	    Object.defineProperty(this, _getTask, {
	      value: _getTask2
	    });
	    Object.defineProperty(this, _updateTask, {
	      value: _updateTask2
	    });
	    Object.defineProperty(this, _getDialog, {
	      value: _getDialog2
	    });
	    Object.defineProperty(this, _getItems, {
	      value: _getItems2
	    });
	    Object.defineProperty(this, _taskId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _onCloseOnce, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _onUpdateOnce, {
	      writable: true,
	      value: null
	    });
	  }
	  setTaskId(taskId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId] = taskId;
	    return this;
	  }
	  onCloseOnce(callback) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onCloseOnce)[_onCloseOnce] = callback;
	    return this;
	  }
	  onUpdateOnce(callback) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onUpdateOnce)[_onUpdateOnce] = callback;
	  }
	  showTo(targetNode) {
	    const dialog = babelHelpers.classPrivateFieldLooseBase(this, _getDialog)[_getDialog](babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId]);
	    if (dialog.isLoaded() && !dialog.isOpen()) {
	      dialog.selectItemsByIds(babelHelpers.classPrivateFieldLooseBase(this, _getItems)[_getItems](babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId]));
	    }
	    dialog.showTo(targetNode);
	  }
	  updateItems() {
	    const dialog = babelHelpers.classPrivateFieldLooseBase(this, _getDialog)[_getDialog](babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId]);
	    if (dialog.isLoaded() && dialog.isOpen()) {
	      dialog.selectItemsByIds(babelHelpers.classPrivateFieldLooseBase(this, _getItems)[_getItems](babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId]));
	    }
	  }
	  get $store() {
	    return tasks_v2_core.Core.getStore();
	  }
	}
	function _getItems2(taskId) {
	  const tags = new Set(babelHelpers.classPrivateFieldLooseBase(this, _getTask)[_getTask](taskId).tags);
	  return babelHelpers.classPrivateFieldLooseBase(this, _getDialog)[_getDialog](taskId).getItems().filter(item => tags.has(item.getTitle())).map(item => [tasks_v2_const.EntitySelectorEntity.Tag, item.getId()]);
	}
	function _getDialog2(taskId) {
	  var _babelHelpers$classPr;
	  const userId = babelHelpers.classPrivateFieldLooseBase(this, _currentUserId)[_currentUserId];
	  const groupId = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _getTask)[_getTask](taskId).groupId) != null ? _babelHelpers$classPr : 0;
	  const key = `${taskId}-${groupId}`;
	  if (dialogs[key]) {
	    return dialogs[key];
	  }
	  let changed = false;
	  const handleChanged = () => {
	    changed = true;
	  };
	  const handleClose = () => {
	    var _babelHelpers$classPr2, _babelHelpers$classPr3;
	    if (changed) {
	      void babelHelpers.classPrivateFieldLooseBase(this, _updateTask)[_updateTask](taskId);
	      changed = false;
	    }
	    (_babelHelpers$classPr2 = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _onCloseOnce))[_onCloseOnce]) == null ? void 0 : _babelHelpers$classPr2.call(_babelHelpers$classPr3);
	    babelHelpers.classPrivateFieldLooseBase(this, _onCloseOnce)[_onCloseOnce] = null;
	  };
	  dialogs[key] = new tasks_v2_lib_entitySelectorDialog.EntitySelectorDialog({
	    multiple: true,
	    enableSearch: true,
	    dropdownMode: true,
	    compactView: true,
	    entities: [{
	      id: tasks_v2_const.EntitySelectorEntity.Tag,
	      options: {
	        taskId,
	        groupId
	      }
	    }],
	    searchOptions: {
	      allowCreateItem: true
	    },
	    footer: tasks_entitySelector.Footer,
	    footerOptions: {
	      userId,
	      groupId
	    },
	    clearUnavailableItems: true,
	    events: {
	      'Item:onSelect': handleChanged,
	      'Item:onDeselect': handleChanged,
	      'Search:onItemCreateAsync': event => {
	        const tag = event.getData().searchQuery.getQuery();
	        if (babelHelpers.classPrivateFieldLooseBase(this, _getTask)[_getTask](taskId).tags.includes(tag)) {
	          return;
	        }
	        dialogs[key].addItem({
	          id: tag,
	          entityId: tasks_v2_const.EntitySelectorEntity.Tag,
	          title: tag,
	          tabs: 'all',
	          selected: true
	        });
	        changed = true;
	      }
	    },
	    popupOptions: {
	      events: {
	        onClose: handleClose
	      }
	    }
	  });
	  return dialogs[key];
	}
	async function _updateTask2(taskId) {
	  var _babelHelpers$classPr4, _babelHelpers$classPr5;
	  const tags = babelHelpers.classPrivateFieldLooseBase(this, _getDialog)[_getDialog](taskId).getSelectedItems().map(item => item.getTitle());
	  void tasks_v2_provider_service_taskService.taskService.update(taskId, {
	    tags
	  });
	  (_babelHelpers$classPr4 = (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _onUpdateOnce))[_onUpdateOnce]) == null ? void 0 : _babelHelpers$classPr4.call(_babelHelpers$classPr5, tags);
	  babelHelpers.classPrivateFieldLooseBase(this, _onUpdateOnce)[_onUpdateOnce] = null;
	}
	function _getTask2(id) {
	  return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](id);
	}
	function _get_currentUserId() {
	  return this.$store.getters[`${tasks_v2_const.Model.Interface}/currentUserId`];
	}
	const tagsDialog = new TagsDialog();

	// @vue/component
	const Tags = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    AddBackground: tasks_v2_component_elements_addBackground.AddBackground,
	    FieldAdd: tasks_v2_component_elements_fieldAdd.FieldAdd
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
	      tagsMeta
	    };
	  },
	  data() {
	    return {
	      isDialogShown: false,
	      tagsIndexes: {}
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    tags() {
	      return [...this.task.tags].sort((a, b) => this.tagsIndexes[a] - this.tagsIndexes[b]);
	    },
	    isFilled() {
	      return this.tags.length > 0;
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    }
	  },
	  watch: {
	    tags() {
	      tagsDialog.setTaskId(this.taskId).updateItems();
	    }
	  },
	  created() {
	    this.rememberTagsIndexes(this.tags);
	  },
	  methods: {
	    handleClick() {
	      if (this.readonly) {
	        return;
	      }
	      tagsDialog.setTaskId(this.taskId).onCloseOnce(this.handleClose).showTo(this.$refs.anchor);
	      tagsDialog.onUpdateOnce(this.rememberTagsIndexes);
	      this.isDialogShown = true;
	    },
	    handleClose() {
	      this.isDialogShown = false;
	    },
	    handleCrossClick(tag) {
	      const tags = this.tags.filter(it => it !== tag);
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        tags
	      });
	    },
	    rememberTagsIndexes(tags) {
	      this.tagsIndexes = tags.reduce((acc, tag, index) => {
	        acc[tag] = index;
	        return acc;
	      }, {});
	    }
	  },
	  template: `
		<div
			class="tasks-field-tags"
			:class="{ '--empty': !isFilled }"
			:data-task-id="taskId"
			:data-task-field-id="tagsMeta.id"
			:data-task-field-value="task.tags.join(',')"
			@click="handleClick"
		>
			<AddBackground v-if="!readonly && isFilled" :isActive="isDialogShown"/>
			<template v-for="tag in tags" :key="tag">
				<div class="tasks-field-tag">
					<span>{{ tag }}</span>
					<div v-if="!readonly" class="tasks-field-tag-cross" @click.capture.stop="handleCrossClick(tag)">
						<BIcon :name="Outline.CROSS_L" :hoverable="true"/>
					</div>
				</div>
			</template>
			<FieldAdd v-if="!isFilled" :icon="Outline.TAG"/>
			<div class="tasks-field-tags-anchor" ref="anchor"></div>
		</div>
	`
	};

	// @vue/component
	const TagsChip = {
	  components: {
	    Chip: tasks_v2_component_elements_chip.Chip
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
	      tagsMeta
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    design() {
	      return this.isSelected ? tasks_v2_component_elements_chip.ChipDesign.ShadowAccent : tasks_v2_component_elements_chip.ChipDesign.ShadowNoAccent;
	    },
	    isSelected() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/wasFieldFilled`](this.taskId, tagsMeta.id);
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
	      tagsDialog.setTaskId(this.taskId).showTo(this.$el);
	      tagsDialog.onUpdateOnce(this.highlightField);
	    },
	    highlightField() {
	      void tasks_v2_lib_fieldHighlighter.fieldHighlighter.setContainer(this.$root.$el).highlight(tagsMeta.id);
	    }
	  },
	  template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:icon="Outline.TAG"
			:text="loc('TASKS_V2_TAGS_TITLE_CHIP')"
			:data-task-id="taskId"
			:data-task-chip-id="tagsMeta.id"
			:data-task-chip-value="task.tags.join(',')"
			@click="handleClick"
		/>
	`
	};

	exports.Tags = Tags;
	exports.TagsChip = TagsChip;
	exports.tagsMeta = tagsMeta;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX.UI.IconSet,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component.Elements,BX,BX.Tasks.EntitySelector,BX.Tasks.V2.Lib,BX.Tasks.V2,BX.Tasks.V2.Provider.Service,BX.UI.IconSet,BX,BX.Tasks.V2.Const,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Lib));
//# sourceMappingURL=tags.bundle.js.map
