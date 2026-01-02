/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,main_popup,ui_entitySelector,ui_iconSet_animated,tasks_v2_component_elements_participantList,tasks_v2_lib_entitySelectorDialog,ui_vue3_components_button,ui_vue3_components_popup,tasks_v2_component_elements_bottomSheet,ui_vue3_components_menu,tasks_v2_component_elements_userCheckbox,tasks_v2_component_elements_progressBar,ui_vue3_vuex,tasks_v2_provider_service_checkListService,ui_system_skeleton_vue,disk_uploader_userFieldWidget,tasks_v2_component_elements_growingTextArea,tasks_v2_component_elements_userAvatarList,ui_iconSet_actions,ui_vue3_directives_hint,ui_iconSet_api_vue,tasks_v2_core,tasks_v2_component_elements_hint,main_core_events,ui_notification,main_core,ui_iconSet_api_core,ui_iconSet_outline,tasks_v2_lib_fieldHighlighter,tasks_v2_component_elements_chip,tasks_v2_provider_service_fileService,tasks_v2_const) {
	'use strict';

	// @vue/component
	const CheckListStub = {
	  name: 'CheckListStub',
	  components: {
	    UiButton: ui_vue3_components_button.Button
	  },
	  emits: ['click'],
	  setup() {
	    return {
	      ButtonSize: ui_vue3_components_button.ButtonSize
	    };
	  },
	  template: `
		<div class="check-list-stub">
			<div class="check-list-stub-title">
				{{ loc('TASKS_V2_CHECK_LIST_STUB_TITLE') }}
			</div>
			<div class="check-list-stub-icon"></div>
			<div class="check-list-stub-btn">
				<UiButton
					:text="loc('TASKS_V2_CHECK_LIST_STUB_BTN')"
					:size="ButtonSize.MEDIUM"
					@click="$emit('click')"
				/>
			</div>
		</div>
	`
	};

	// @vue/component
	const CheckListPopup = {
	  name: 'TaskCheckListPopup',
	  components: {
	    Popup: ui_vue3_components_popup.Popup
	  },
	  inheritAttrs: false,
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  emits: ['show', 'close', 'resize'],
	  setup() {
	    return {
	      resizeObserver: null
	    };
	  },
	  computed: {
	    popupId() {
	      return `tasks-check-list-popup-${this.taskId}`;
	    },
	    popupOptions() {
	      return {
	        className: 'tasks-check-list-popup',
	        width: 580,
	        height: 500,
	        borderRadius: '18px',
	        offsetTop: 0,
	        padding: 0,
	        autoHide: true,
	        closeByEsc: true,
	        animation: {
	          showClassName: 'tasks-check-list-popup-show',
	          closeClassName: 'tasks-check-list-popup-close',
	          closeAnimationType: 'animation'
	        },
	        events: {
	          onClose: this.handleClose.bind(this)
	        }
	      };
	    },
	    ...ui_vue3_vuex.mapGetters({
	      titleFieldOffsetHeight: `${tasks_v2_const.Model.Interface}/titleFieldOffsetHeight`
	    })
	  },
	  watch: {
	    async titleFieldOffsetHeight() {
	      if (!this.$refs.childComponent) {
	        return;
	      }
	      await this.$nextTick();
	      this.resize();
	    }
	  },
	  created() {
	    this.resizeObserver = new ResizeObserver(entries => {
	      for (const entry of entries) {
	        if (entry.target === this.$refs.wrapper) {
	          this.resize();
	        }
	      }
	    });
	  },
	  mounted() {
	    main_core.Event.bind(window, 'resize', this.resize);
	  },
	  beforeUnmount() {
	    main_core.Event.unbind(window, 'resize', this.resize);
	  },
	  methods: {
	    resize() {
	      var _this$$refs$childComp;
	      const popupInstance = (_this$$refs$childComp = this.$refs.childComponent) == null ? void 0 : _this$$refs$childComp.getPopupInstance();
	      if (popupInstance) {
	        this.$emit('resize');
	        popupInstance.adjustPosition();
	      }
	    },
	    handleShow() {
	      var _this$$refs$childComp2;
	      this.$emit('show', {
	        popupInstance: this.$refs.childComponent.getPopupInstance()
	      });
	      (_this$$refs$childComp2 = this.$refs.childComponent) == null ? void 0 : _this$$refs$childComp2.getPopupInstance().adjustPosition();
	      setTimeout(() => this.resizeObserver.observe(this.$parent.$refs.wrapper), 300);
	    },
	    handleClose() {
	      this.resizeObserver.disconnect();
	      this.$bitrix.eventEmitter.emit(tasks_v2_const.EventName.CloseCheckList);
	      this.$emit('close');
	    }
	  },
	  template: `
		<Popup :options="popupOptions" ref="childComponent">
			<slot
				:handleShow="handleShow"
				:handleClose="handleClose"
			></slot>
		</Popup>
	`
	};

	// @vue/component
	const CheckListSheet = {
	  name: 'TaskCheckListSheet',
	  components: {
	    BottomSheet: tasks_v2_component_elements_bottomSheet.BottomSheet
	  },
	  props: {
	    isShown: {
	      type: Boolean,
	      required: true
	    },
	    getBindElement: {
	      type: Function,
	      default: null
	    },
	    getTargetContainer: {
	      type: Function,
	      default: null
	    }
	  },
	  emits: ['show', 'close', 'isShown'],
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      titleFieldOffsetHeight: `${tasks_v2_const.Model.Interface}/titleFieldOffsetHeight`
	    })
	  },
	  watch: {
	    titleFieldOffsetHeight() {
	      var _this$$refs$childComp;
	      (_this$$refs$childComp = this.$refs.childComponent) == null ? void 0 : _this$$refs$childComp.adjustPosition();
	    },
	    async isShown(value) {
	      await this.$nextTick();
	      if (value === true) {
	        main_core.Event.bind(document, 'keydown', this.handleKeyDown, {
	          capture: true
	        });
	      } else {
	        main_core.Event.unbind(document, 'keydown', this.handleKeyDown, {
	          capture: true
	        });
	      }
	      this.$emit('isShown', value);
	    }
	  },
	  methods: {
	    handleShow() {
	      this.$emit('show');
	    },
	    handleClose() {
	      this.$emit('close');
	    },
	    handleKeyDown(event) {
	      if (event.key === 'Escape') {
	        this.handleClose();
	        event.stopPropagation();
	      }
	    }
	  },
	  template: `
		<BottomSheet
			v-if="isShown"
			:padding="0"
			:getBindElement="getBindElement"
			:getTargetContainer="getTargetContainer"
			ref="childComponent"
		>
			<slot
				:handleShow="handleShow"
				:handleClose="handleClose"
			></slot>
		</BottomSheet>
	`
	};

	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _setItemsVisibility = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setItemsVisibility");
	var _findNestedChildren = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("findNestedChildren");
	var _getRootParent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRootParent");
	var _getCheckLists = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCheckLists");
	class CheckListManager {
	  constructor(params) {
	    Object.defineProperty(this, _getCheckLists, {
	      value: _getCheckLists2
	    });
	    Object.defineProperty(this, _getRootParent, {
	      value: _getRootParent2
	    });
	    Object.defineProperty(this, _findNestedChildren, {
	      value: _findNestedChildren2
	    });
	    Object.defineProperty(this, _setItemsVisibility, {
	      value: _setItemsVisibility2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = params;
	  }
	  getItem(itemId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().find(item => item.id === itemId);
	  }
	  getItemsOnLevel(parentId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().filter(item => item.parentId === parentId).sort((a, b) => a.sortIndex - b.sortIndex);
	  }
	  getItemLevel(checkListItem) {
	    let level = 0;
	    let current = checkListItem;
	    const visitedIds = new Set();
	    const findParent = parentId => babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().find(item => item.id === parentId);
	    while (current.parentId !== 0) {
	      if (visitedIds.has(current.id)) {
	        break;
	      }
	      visitedIds.add(current.id);
	      current = findParent(current.parentId);
	      if (!current) {
	        break;
	      }
	      level++;
	    }
	    return level;
	  }
	  isItemDescendant(potentialAncestor, item) {
	    if ((item == null ? void 0 : item.parentId) === (potentialAncestor == null ? void 0 : potentialAncestor.id)) {
	      return true;
	    }
	    if ((item == null ? void 0 : item.parentId) === 0) {
	      return false;
	    }
	    const parent = babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().find(i => i.id === (item == null ? void 0 : item.parentId));
	    if (!parent) {
	      return false;
	    }
	    return this.isItemDescendant(potentialAncestor, parent);
	  }
	  showItems(itemIds, updateFn) {
	    const updates = [];
	    itemIds.forEach(itemId => babelHelpers.classPrivateFieldLooseBase(this, _setItemsVisibility)[_setItemsVisibility](itemId, false, updates));
	    if (updates.length > 0) {
	      updateFn(updates);
	    }
	  }
	  hideItems(itemIds, updateFn) {
	    const updates = [];
	    itemIds.forEach(itemId => babelHelpers.classPrivateFieldLooseBase(this, _setItemsVisibility)[_setItemsVisibility](itemId, true, updates));
	    if (updates.length > 0) {
	      updateFn(updates);
	    }
	  }
	  syncParentCompletionState(itemId, updateFn, parentItemId) {
	    var _parentItem$localComp;
	    const changedItem = babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().find(item => item.id === itemId);
	    if ((!changedItem || !changedItem.parentId) && !parentItemId) {
	      return;
	    }
	    const parentId = parentItemId || changedItem.parentId;
	    const parentItem = babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().find(item => item.id === parentId);
	    if (!parentItem) {
	      return;
	    }
	    const childrenItems = babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().filter(item => item.parentId === parentItem.id);
	    const isEmptyParent = childrenItems.length === 0;
	    const allChildrenCompleted = childrenItems.every(child => {
	      var _child$localCompleteS;
	      return (_child$localCompleteS = child.localCompleteState) != null ? _child$localCompleteS : child.isComplete;
	    });
	    const someChildrenIncomplete = !allChildrenCompleted;
	    const parentCompleted = (_parentItem$localComp = parentItem.localCompleteState) != null ? _parentItem$localComp : parentItem.isComplete;
	    const shouldUpdateParent = isEmptyParent || allChildrenCompleted && !parentCompleted || someChildrenIncomplete && parentCompleted;
	    if (!shouldUpdateParent) {
	      return;
	    }
	    updateFn(parentItem.id, {
	      isComplete: allChildrenCompleted && !isEmptyParent
	    });
	    if (parentItem.parentId) {
	      this.syncParentCompletionState(parentItem.id, updateFn);
	    }
	  }
	  getAllGroupModeItems() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().filter(item => {
	      var _item$groupMode;
	      return ((_item$groupMode = item.groupMode) == null ? void 0 : _item$groupMode.active) === true;
	    });
	  }
	  getAllSelectedItems() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().filter(item => {
	      var _item$groupMode2;
	      return item.parentId !== 0 && ((_item$groupMode2 = item.groupMode) == null ? void 0 : _item$groupMode2.selected) === true;
	    });
	  }
	  getAllSelectedItemsWithChildren() {
	    const result = new Map();
	    const selectedItems = this.getAllSelectedItems();
	    const allItems = babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]();
	    selectedItems.forEach(item => result.set(item.id, item));
	    const getChildren = parentIds => {
	      const children = allItems.filter(item => {
	        return parentIds.includes(item.parentId) && !result.has(item.id);
	      });
	      children.forEach(child => result.set(child.id, child));
	      if (children.length > 0) {
	        getChildren(children.map(child => child.id));
	      }
	    };
	    getChildren(selectedItems.map(item => item.id));
	    return [...result.values()];
	  }
	  getAllChildren(itemId) {
	    const visited = new Set();
	    const result = [];
	    const collectChildren = currentId => {
	      if (visited.has(currentId)) {
	        return;
	      }
	      visited.add(currentId);
	      const children = babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().filter(item => item.parentId === currentId).sort((a, b) => a.sortIndex - b.sortIndex);
	      children.forEach(child => {
	        if (!visited.has(child.id)) {
	          result.push(child);
	          collectChildren(child.id);
	        }
	      });
	    };
	    collectChildren(itemId);
	    return result;
	  }
	  getAllCompletedChildren(itemId) {
	    return this.getAllChildren(itemId).filter(item => {
	      var _item$localCompleteSt;
	      return ((_item$localCompleteSt = item.localCompleteState) != null ? _item$localCompleteSt : item.isComplete) === true;
	    });
	  }
	  getChildren(itemId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().filter(item => {
	      return item.parentId === itemId;
	    });
	  }
	  getSiblings(itemId, parentId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().filter(sibling => sibling.parentId === parentId && sibling.id !== itemId).sort((a, b) => a.sortIndex - b.sortIndex);
	  }
	  resortItemsOnLevel(parentId, updateFn) {
	    const allItems = babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().filter(item => item.parentId === parentId);
	    const sortedItems = [...allItems].sort((a, b) => a.sortIndex - b.sortIndex);
	    const updates = sortedItems.map((item, newIndex) => ({
	      ...item,
	      sortIndex: newIndex
	    }));
	    if (updates.length > 0) {
	      updateFn(updates);
	    }
	  }
	  resortItemsAfterIndex(parentId, sortIndex, updateFn) {
	    const allItems = babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().filter(item => item.parentId === parentId);
	    const itemsToResort = allItems.filter(item => item.sortIndex >= sortIndex).sort((a, b) => a.sortIndex - b.sortIndex);
	    const updates = itemsToResort.map(item => ({
	      ...item,
	      sortIndex: item.sortIndex + 1
	    }));
	    if (updates.length > 0) {
	      updateFn(updates);
	    }
	  }
	  moveRight(item, updateFn) {
	    if (item.parentId === 0 || this.getItemLevel(item) > 5) {
	      return;
	    }
	    const itemsOnLevel = this.getItemsOnLevel(item.parentId);
	    const currentIndex = itemsOnLevel.findIndex(sibling => sibling.id === item.id);
	    if (currentIndex <= 0) {
	      return;
	    }
	    let newParent = null;
	    for (let i = currentIndex - 1; i >= 0; i--) {
	      const candidate = itemsOnLevel[i];
	      if (!this.isItemDescendant(candidate, item)) {
	        newParent = candidate;
	        break;
	      }
	    }
	    if (!newParent) {
	      return;
	    }
	    const newParentChildren = babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().filter(child => child.parentId === newParent.id).sort((a, b) => a.sortIndex - b.sortIndex);
	    const updates = itemsOnLevel.filter((sibling, index) => index > currentIndex).map(sibling => ({
	      ...sibling,
	      sortIndex: sibling.sortIndex - 1
	    }));
	    updates.push({
	      ...item,
	      parentId: newParent.id,
	      parentNodeId: newParent.nodeId,
	      sortIndex: newParentChildren.length > 0 ? newParentChildren[newParentChildren.length - 1].sortIndex + 1 : 0
	    });
	    updateFn(updates);
	  }
	  moveLeft(item, updateFn) {
	    if (item.parentId === 0 || this.getItemLevel(item) <= 1) {
	      return;
	    }
	    const currentParent = babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().find(parent => parent.id === item.parentId);
	    if (!currentParent) {
	      return;
	    }
	    const itemsOnLevel = this.getItemsOnLevel(currentParent.parentId);
	    const parentInNewListIndex = itemsOnLevel.findIndex(sibling => sibling.id === currentParent.id);
	    const currentSiblingsUpdates = babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().filter(sibling => sibling.parentId === item.parentId && sibling.sortIndex > item.sortIndex).map(sibling => ({
	      ...sibling,
	      sortIndex: sibling.sortIndex - 1
	    }));
	    let newSortIndex = 0;
	    if (parentInNewListIndex === -1 || parentInNewListIndex === itemsOnLevel.length - 1) {
	      newSortIndex = itemsOnLevel.length > 0 ? itemsOnLevel[itemsOnLevel.length - 1].sortIndex + 1 : 0;
	    } else {
	      newSortIndex = itemsOnLevel[parentInNewListIndex].sortIndex + 1;
	      const shiftUpdates = itemsOnLevel.filter(sibling => sibling.sortIndex >= newSortIndex).map(sibling => ({
	        ...sibling,
	        sortIndex: sibling.sortIndex + 1
	      }));
	      currentSiblingsUpdates.push(...shiftUpdates);
	    }
	    const movedItemUpdate = {
	      ...item,
	      parentId: currentParent.parentId,
	      parentNodeId: currentParent.parentNodeId || null,
	      sortIndex: newSortIndex
	    };
	    updateFn([...currentSiblingsUpdates, movedItemUpdate]);
	  }
	  findNearestItem(initialItem, selected, excludeChildrenOf = []) {
	    if (!initialItem) {
	      return null;
	    }
	    const rootParent = babelHelpers.classPrivateFieldLooseBase(this, _getRootParent)[_getRootParent](initialItem);
	    if (!rootParent) {
	      return null;
	    }
	    const currentSortIndex = initialItem.sortIndex;
	    const excludedParentIds = new Set(excludeChildrenOf.map(item => item.id));
	    const eligibleItems = babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().sort((a, b) => a.sortIndex - b.sortIndex).filter(item => {
	      var _item$groupMode3, _babelHelpers$classPr;
	      const isChildOfExcluded = excludedParentIds.has(item.parentId);
	      return item.id !== initialItem.id && item.parentId !== 0 && ((_item$groupMode3 = item.groupMode) == null ? void 0 : _item$groupMode3.selected) === selected && ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _getRootParent)[_getRootParent](item)) == null ? void 0 : _babelHelpers$classPr.id) === rootParent.id && !isChildOfExcluded;
	    });
	    if (eligibleItems.length === 0) {
	      return null;
	    }
	    return eligibleItems.reduce((nearest, item) => {
	      return item.sortIndex > currentSortIndex && (item.sortIndex < nearest.sortIndex || nearest.sortIndex <= currentSortIndex) ? item : nearest;
	    });
	  }
	  getFirstChild(itemId) {
	    const children = babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().filter(item => item.parentId === itemId).sort((a, b) => a.sortIndex - b.sortIndex);
	    return children[0] || null;
	  }
	  hasEmptyItemWithFiles(hasItemFiles) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().some(item => {
	      return item.title === '' && hasItemFiles(item);
	    });
	  }
	  hasItemWithFiles(hasItemFiles) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().some(item => {
	      return hasItemFiles(item);
	    });
	  }
	  hasEmptyParentItem() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().some(item => {
	      return item.parentId === 0 && item.title === '';
	    });
	  }
	  getFirstEmptyItem() {
	    const items = babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().filter(item => item.title === '').sort((a, b) => a.sortIndex - b.sortIndex);
	    return items[0] || null;
	  }
	  getChildWithEmptyTitle(itemId) {
	    const children = babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().filter(item => item.parentId === itemId).sort((a, b) => b.sortIndex - a.sortIndex).find(item => item.title === '');
	    return children || null;
	  }
	  isItemCollapsed(item, isPreview, positionIndex) {
	    if (item.localCollapsedState !== null) {
	      return item.localCollapsedState;
	    }
	    if (!isPreview) {
	      return false;
	    }
	    if (item.collapsed && !item.expanded) {
	      return true;
	    }
	    if (item.expanded) {
	      return false;
	    }
	    return positionIndex !== 0;
	  }
	  getRootParentByChildId(itemId) {
	    var _currentItem;
	    const childItem = this.getItem(itemId);
	    if (!childItem) {
	      return null;
	    }
	    if (childItem.parentId === 0) {
	      return childItem;
	    }
	    let currentItem = childItem;
	    const visitedIds = new Set();
	    while (currentItem && currentItem.parentId !== 0) {
	      if (visitedIds.has(currentItem.id)) {
	        break;
	      }
	      visitedIds.add(currentItem.id);
	      const parent = this.getItem(currentItem.parentId);
	      if (!parent) {
	        break;
	      }
	      currentItem = parent;
	    }
	    return ((_currentItem = currentItem) == null ? void 0 : _currentItem.parentId) === 0 ? currentItem : null;
	  }
	  expandIdsWithChildren(itemIds) {
	    const fullSet = new Set(itemIds);
	    if (itemIds.size === 0 || babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().length === 0) {
	      return fullSet;
	    }
	    const checkListMap = new Map(babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().map(item => [item.id, item]));
	    const processedIds = new Set();
	    itemIds.forEach(id => {
	      if (!processedIds.has(id)) {
	        babelHelpers.classPrivateFieldLooseBase(this, _findNestedChildren)[_findNestedChildren](id, checkListMap, fullSet, processedIds);
	      }
	    });
	    return fullSet;
	  }
	  findItemIdsWithUser(rootId, userId) {
	    const allItems = this.getAllChildren(rootId);
	    const rootItem = this.getItem(rootId);
	    if (rootItem) {
	      allItems.unshift(rootItem);
	    }
	    const result = new Set();
	    allItems.forEach(item => {
	      var _item$accomplices, _item$auditors;
	      const hasUser = ((_item$accomplices = item.accomplices) == null ? void 0 : _item$accomplices.some(user => user.id === userId)) || ((_item$auditors = item.auditors) == null ? void 0 : _item$auditors.some(user => user.id === userId));
	      if (hasUser && item.parentId !== 0) {
	        result.add(item.id);
	      }
	    });
	    return result;
	  }
	}
	function _setItemsVisibility2(itemId, hidden, updates) {
	  const item = this.getItem(itemId);
	  if (!item || item.hidden === hidden) {
	    return;
	  }
	  const updatedItem = {
	    ...item,
	    hidden
	  };
	  updates.push(updatedItem);
	  const children = this.getChildren(itemId);
	  children.forEach(child => {
	    babelHelpers.classPrivateFieldLooseBase(this, _setItemsVisibility)[_setItemsVisibility](child.id, hidden, updates);
	  });
	}
	function _findNestedChildren2(parentId, checkListMap, resultSet, processedIds) {
	  if (processedIds.has(parentId)) {
	    return;
	  }
	  processedIds.add(parentId);
	  checkListMap.forEach(item => {
	    if (item.parentId === parentId && !resultSet.has(item.id)) {
	      resultSet.add(item.id);
	      babelHelpers.classPrivateFieldLooseBase(this, _findNestedChildren)[_findNestedChildren](item.id, checkListMap, resultSet, processedIds);
	    }
	  });
	}
	function _getRootParent2(item) {
	  if (!item || item.parentId === 0) {
	    return item || null;
	  }
	  const parentItem = babelHelpers.classPrivateFieldLooseBase(this, _getCheckLists)[_getCheckLists]().find(parent => parent.id === item.parentId);
	  if (!parentItem) {
	    return null;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _getRootParent)[_getRootParent](parentItem);
	}
	function _getCheckLists2() {
	  var _babelHelpers$classPr2, _babelHelpers$classPr3, _babelHelpers$classPr4;
	  return (_babelHelpers$classPr2 = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params]) == null ? void 0 : (_babelHelpers$classPr4 = _babelHelpers$classPr3.computed) == null ? void 0 : _babelHelpers$classPr4.checkLists()) != null ? _babelHelpers$classPr2 : [];
	}

	const checkListMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.CheckList,
	  title: main_core.Loc.getMessage('TASKS_V2_CHECK_LIST_TITLE')
	});

	// @vue/component
	const CheckListList = {
	  name: 'TaskCheckListList',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    isEmpty: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['open', 'addFastCheckList'],
	  setup() {
	    return {
	      Animated: ui_iconSet_api_vue.Animated,
	      Outline: ui_iconSet_api_vue.Outline,
	      checkListMeta
	    };
	  },
	  data() {
	    return {
	      isLoading: null
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      deletingCheckListIds: `${tasks_v2_const.Model.Interface}/deletingCheckListIds`,
	      disableCheckListAnimations: `${tasks_v2_const.Model.Interface}/disableCheckListAnimations`
	    }),
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    isEdit() {
	      return main_core.Type.isNumber(this.taskId) && this.taskId > 0;
	    },
	    checkLists() {
	      return this.$store.getters[`${tasks_v2_const.Model.CheckList}/getByIds`](this.task.checklist);
	    },
	    isFilledEmpty() {
	      return this.checkListLength === 0 && this.wasFilled;
	    },
	    checkListLength() {
	      const deletingRootIds = Object.values(this.deletingCheckListIds);
	      const deletingIds = new Set();
	      deletingRootIds.forEach(rootId => {
	        deletingIds.add(rootId);
	        this.checkListManager.getAllChildren(rootId).forEach(child => {
	          deletingIds.add(child.id);
	        });
	      });
	      return this.checkLists.filter(({
	        id
	      }) => !deletingIds.has(id)).length;
	    },
	    containsChecklist() {
	      return this.task.containsChecklist;
	    },
	    wasFilled() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/wasFieldFilled`](this.taskId, checkListMeta.id);
	    },
	    loading() {
	      return this.isLoading === true && this.isEdit;
	    },
	    canCheckListAdd() {
	      if (!this.isEdit) {
	        return true;
	      }
	      return this.task.rights.checklistAdd === true;
	    }
	  },
	  async created() {
	    this.checkListManager = new CheckListManager({
	      computed: {
	        checkLists: () => this.checkLists
	      }
	    });
	    if (this.containsChecklist && this.checkLists.length === 0) {
	      this.isLoading = true;
	      await this.loadData();
	      this.isLoading = false;
	    }
	  },
	  methods: {
	    async loadData() {
	      await tasks_v2_provider_service_checkListService.checkListService.load(this.taskId);
	    }
	  },
	  template: `
		<div
			:data-task-field-id="checkListMeta.id"
			class="tasks-check-list-list"
			:class="{ '--default': loading || isFilledEmpty }"
		>
			<div
				class="tasks-check-list-list-content"
				:class="{ '--default': loading || isFilledEmpty }"
			>
				<Transition name="check-list-fade" mode="in-out" :css="!disableCheckListAnimations">
					<div
						v-if="loading"
						key="loading"
						class="tasks-check-list-list-transition-content"
					>
						<div class="tasks-check-list-list-content-row">
							<BIcon :name="Animated.LOADER_WAIT"/>
							<div class="tasks-check-list-list-content-text">
								{{ loc('TASKS_V2_CHECK_LIST_LOADING') }}
							</div>
						</div>
					</div>
				</Transition>
				<Transition name="check-list-fade" mode="in-out" :css="!disableCheckListAnimations">
					<div
						v-if="!loading && isFilledEmpty"
						key="empty"
						class="tasks-check-list-list-transition-content"
					>
						<div class="tasks-check-list-list-content-row --stub" @click="$emit('addFastCheckList')">
							<BIcon :name="Outline.CHECK_LIST"/>
							<div class="tasks-check-list-list-content-text">
								{{ loc('TASKS_V2_CHECK_LIST_CHIP_TITLE') }}
							</div>
							<BIcon class="tasks-check-list-list-content-row-plus" :name="Outline.PLUS_L"/>
						</div>
					</div>
				</Transition>
				<div
					v-if="!loading && !isFilledEmpty"
					key="content"
					class="tasks-check-list-list-transition-content"
				>
					<slot></slot>
					<div
						v-if="canCheckListAdd"
						class="tasks-check-list-list-content-row --footer"
					>
						<div
							class="tasks-check-list-list-content-btn"
							:class="{ '--empty': isEmpty }"
							@click="$emit('addFastCheckList')"
						>
							<BIcon :name="Outline.PLUS_L"/>
							<div class="tasks-check-list-list-content-btn-text">
								{{ loc('TASKS_V2_CHECK_LIST_ADD_LABEL') }}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	var _activeHighlights = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("activeHighlights");
	var _startAnimation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("startAnimation");
	var _cleanup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cleanup");
	var _nextTick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("nextTick");
	class Highlighter {
	  constructor() {
	    Object.defineProperty(this, _nextTick, {
	      value: _nextTick2
	    });
	    Object.defineProperty(this, _cleanup, {
	      value: _cleanup2
	    });
	    Object.defineProperty(this, _startAnimation, {
	      value: _startAnimation2
	    });
	    Object.defineProperty(this, _activeHighlights, {
	      writable: true,
	      value: new WeakMap()
	    });
	  }
	  async highlight(element) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _nextTick)[_nextTick]();
	    babelHelpers.classPrivateFieldLooseBase(this, _cleanup)[_cleanup](element);
	    babelHelpers.classPrivateFieldLooseBase(this, _activeHighlights)[_activeHighlights].set(element, {
	      animationStart: null,
	      timeoutId: null,
	      handler: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _startAnimation)[_startAnimation](element);
	    const handler = () => babelHelpers.classPrivateFieldLooseBase(this, _cleanup)[_cleanup](element);
	    main_core.Event.bind(window, 'click', handler);
	    main_core.Event.bind(window, 'keydown', handler);
	    const state = babelHelpers.classPrivateFieldLooseBase(this, _activeHighlights)[_activeHighlights].get(element);
	    state.handler = handler;
	    state.timeoutId = setTimeout(() => babelHelpers.classPrivateFieldLooseBase(this, _cleanup)[_cleanup](element), Highlighter.ANIMATION_DURATION);
	  }
	}
	function _startAnimation2(element) {
	  main_core.Dom.addClass(element, [Highlighter.HIGHLIGHT_CLASS, Highlighter.ANIMATE_CLASS]);
	  const state = babelHelpers.classPrivateFieldLooseBase(this, _activeHighlights)[_activeHighlights].get(element);
	  if (state) {
	    state.animationStart = Date.now();
	  }
	}
	function _cleanup2(element) {
	  const state = babelHelpers.classPrivateFieldLooseBase(this, _activeHighlights)[_activeHighlights].get(element);
	  if (!state) {
	    return;
	  }
	  main_core.Dom.removeClass(element, [Highlighter.HIGHLIGHT_CLASS, Highlighter.ANIMATE_CLASS]);
	  if (state.timeoutId) {
	    clearTimeout(state.timeoutId);
	  }
	  if (state.handler) {
	    main_core.Event.unbind(window, 'click', state.handler);
	    main_core.Event.unbind(window, 'keydown', state.handler);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _activeHighlights)[_activeHighlights].delete(element);
	}
	function _nextTick2() {
	  return new Promise(resolve => {
	    // eslint-disable-next-line no-promise-executor-return
	    return setTimeout(resolve, 0);
	  });
	}
	Highlighter.HIGHLIGHT_CLASS = 'element-highlight';
	Highlighter.ANIMATE_CLASS = '--animate';
	Highlighter.ANIMATION_DURATION = 1500;

	// @vue/component
	const CheckListItemMixin = {
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    id: {
	      type: [Number, String],
	      required: true
	    },
	    isPreview: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['update', 'addItem', 'removeItem', 'focus', 'blur', 'emptyBlur', 'show', 'hide'],
	  data() {
	    return {
	      isHovered: false,
	      scrollContainer: null
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      currentUserId: `${tasks_v2_const.Model.Interface}/currentUserId`
	    }),
	    isEdit() {
	      return main_core.Type.isNumber(this.taskId) && this.taskId > 0;
	    },
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    checkLists() {
	      return this.$store.getters[`${tasks_v2_const.Model.CheckList}/getByIds`](this.task.checklist);
	    },
	    item() {
	      return this.$store.getters[`${tasks_v2_const.Model.CheckList}/getById`](this.id);
	    },
	    canSave() {
	      if (!this.isEdit) {
	        return true;
	      }
	      return this.task.rights.checklistSave === true;
	    },
	    canAdd() {
	      if (!this.isEdit) {
	        return true;
	      }
	      return this.task.rights.checklistAdd === true;
	    },
	    canEdit() {
	      var _this$item$creator;
	      if (!this.isEdit) {
	        return true;
	      }
	      return this.canSave && (this.task.rights.checklistEdit === true || ((_this$item$creator = this.item.creator) == null ? void 0 : _this$item$creator.id) === this.currentUserId);
	    },
	    canCheckListToggle() {
	      if (!this.isEdit) {
	        return true;
	      }
	      return this.task.rights.checklistToggle === true;
	    },
	    canModify() {
	      if (this.canEdit === true) {
	        return true;
	      }
	      return this.item.actions.modify === true;
	    },
	    canRemove() {
	      if (this.canEdit === true) {
	        return true;
	      }
	      return this.item.actions.remove === true;
	    },
	    canToggle() {
	      if (this.canCheckListToggle === true) {
	        return true;
	      }
	      return this.item.actions.toggle === true;
	    },
	    hasAttachments() {
	      return this.hasUsers;
	    },
	    hasUsers() {
	      return this.hasAccomplices || this.hasAuditors;
	    },
	    hasAccomplices() {
	      var _this$accomplices;
	      return ((_this$accomplices = this.accomplices) == null ? void 0 : _this$accomplices.length) > 0;
	    },
	    hasAuditors() {
	      var _this$auditors;
	      return ((_this$auditors = this.auditors) == null ? void 0 : _this$auditors.length) > 0;
	    },
	    accomplices() {
	      return this.item.accomplices;
	    },
	    auditors() {
	      return this.item.auditors;
	    },
	    files() {
	      return this.item.attachments;
	    },
	    textColor() {
	      return this.completed ? 'var(--ui-color-base-4)' : 'var(--ui-color-base-1)';
	    },
	    groupMode() {
	      var _this$item$groupMode;
	      return ((_this$item$groupMode = this.item.groupMode) == null ? void 0 : _this$item$groupMode.active) === true;
	    },
	    groupModeSelected() {
	      var _this$item$groupMode2;
	      return ((_this$item$groupMode2 = this.item.groupMode) == null ? void 0 : _this$item$groupMode2.selected) === true;
	    },
	    completed() {
	      var _this$item$localCompl;
	      return (_this$item$localCompl = this.item.localCompleteState) != null ? _this$item$localCompl : this.item.isComplete;
	    }
	  },
	  methods: {
	    ...ui_vue3_vuex.mapActions(tasks_v2_const.Model.Interface, ['addCheckListCompletionCallback']),
	    handleFocus() {
	      this.$emit('focus', this.id);
	    },
	    handleBlur() {
	      this.$emit('blur', this.id);
	    },
	    handleEmptyBlur() {
	      this.$emit('emptyBlur', this.id);
	    },
	    updateCheckList(id, fields) {
	      this.$emit('update', this.id);
	      return this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/update`, {
	        id,
	        fields
	      });
	    },
	    upsertCheckLists(items) {
	      this.$emit('update', this.id);
	      return this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/upsertMany`, items);
	    },
	    updateTitle(title = '') {
	      this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/update`, {
	        id: this.id,
	        fields: {
	          title
	        }
	      });
	      this.$emit('update', this.id);
	    },
	    addItem(sort) {
	      this.$emit('addItem', {
	        id: this.id,
	        sort: main_core.Type.isNumber(sort) ? sort : null
	      });
	    },
	    removeItem() {
	      this.$emit('removeItem', this.id);
	    },
	    async complete(isComplete) {
	      if (this.canToggle === false) {
	        return;
	      }
	      await this.updateCheckList(this.id, {
	        localCompleteState: isComplete
	      });
	      const listParents = new Map();
	      this.checkListManager.syncParentCompletionState(this.id, async (id, fields) => {
	        listParents.set(id, fields);
	        await this.updateCheckList(id, {
	          localCompleteState: fields.isComplete
	        });
	      });
	      this.$emit('update', this.id);
	      const completionCallback = async () => {
	        await this.updateCheckList(this.id, {
	          isComplete
	        });
	        listParents.forEach(async (fields, id) => {
	          await this.updateCheckList(id, fields);
	          this.saveCompleteState(id, fields.isComplete);
	        });
	      };
	      this.addCheckListCompletionCallback({
	        id: this.id,
	        callback: completionCallback
	      });
	      this.saveCompleteState(this.id, isComplete);
	    },
	    saveCompleteState(itemId, isComplete) {
	      if (!this.isPreview || !this.isEdit) {
	        return;
	      }
	      if (isComplete) {
	        void tasks_v2_provider_service_checkListService.checkListService.complete(this.taskId, itemId);
	      } else {
	        void tasks_v2_provider_service_checkListService.checkListService.renew(this.taskId, itemId);
	      }
	    },
	    focusToItem() {
	      var _this$$parent$$el;
	      const item = this.$refs.item;
	      const scrollContainer = (_this$$parent$$el = this.$parent.$el) == null ? void 0 : _this$$parent$$el.closest('[data-list]');
	      const itemRect = main_core.Dom.getPosition(item);
	      const containerRect = main_core.Dom.getPosition(scrollContainer);
	      const offsetTopInsideContainer = itemRect.top - containerRect.top + scrollContainer.scrollTop;
	      scrollContainer.scrollTo({
	        top: offsetTopInsideContainer - 200,
	        behavior: 'smooth'
	      });
	    }
	  }
	};

	// @vue/component
	const CheckListParentItem = {
	  name: 'CheckListParentItem',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    BMenu: ui_vue3_components_menu.BMenu,
	    GrowingTextArea: tasks_v2_component_elements_growingTextArea.GrowingTextArea,
	    UserAvatarList: tasks_v2_component_elements_userAvatarList.UserAvatarList,
	    UserCheckbox: tasks_v2_component_elements_userCheckbox.UserCheckbox,
	    ProgressBar: tasks_v2_component_elements_progressBar.ProgressBar
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  mixins: [CheckListItemMixin],
	  inject: ['setItemsRef'],
	  props: {
	    positionIndex: {
	      type: Number,
	      required: true
	    }
	  },
	  emits: ['startGroupMode', 'openCheckList'],
	  setup() {
	    return {
	      Actions: ui_iconSet_api_core.Actions,
	      Outline: ui_iconSet_api_core.Outline
	    };
	  },
	  data() {
	    return {
	      isSticky: false,
	      isMenuShown: false,
	      menuRemoveSectionCode: 'removeSection'
	    };
	  },
	  computed: {
	    menuOptions() {
	      return {
	        id: `check-list-parent-item-action-menu-${main_core.Text.getRandom()}`,
	        bindElement: this.$refs.more.$el,
	        minWidth: 250,
	        offsetLeft: -100,
	        sections: [{
	          code: this.menuRemoveSectionCode
	        }],
	        items: this.menuItems,
	        targetContainer: document.body,
	        closeByEsc: true
	      };
	    },
	    menuItems() {
	      const collapseItem = {
	        title: this.item.areCompletedCollapsed ? this.loc('TASKS_V2_CHECK_LIST_ITEM_MENU_SHOW') : this.loc('TASKS_V2_CHECK_LIST_ITEM_MENU_HIDE'),
	        icon: this.item.areCompletedCollapsed ? ui_iconSet_api_core.Outline.OBSERVER : ui_iconSet_api_core.Outline.CROSSED_EYE,
	        dataset: {
	          id: `MenuProfileHide-${this.id}`
	        },
	        onClick: () => {
	          const newValue = !this.item.areCompletedCollapsed;
	          void this.updateCheckList(this.id, {
	            areCompletedCollapsed: newValue
	          });
	          this.toggleCompleted(this.id, newValue);
	        }
	      };
	      const groupActionsItem = {
	        title: this.loc('TASKS_V2_CHECK_LIST_ITEM_MENU_GROUP'),
	        icon: ui_iconSet_api_core.Outline.MULTICHOICE_ON,
	        dataset: {
	          id: `MenuProfileGroup-${this.id}`
	        },
	        onClick: () => {
	          if (this.isCollapsed(this.item)) {
	            this.toggleCollapse();
	          }
	          this.$emit('startGroupMode', this.id);
	        }
	      };
	      const editItem = {
	        sectionCode: this.menuRemoveSectionCode,
	        title: this.loc('TASKS_V2_CHECK_LIST_ITEM_MENU_EDIT'),
	        icon: ui_iconSet_api_core.Outline.EDIT_L,
	        dataset: {
	          id: `MenuProfileEdit-${this.id}`
	        },
	        onClick: () => {
	          if (this.canEdit) {
	            this.$emit('openCheckList', this.id);
	          }
	        }
	      };
	      const removeItem = {
	        sectionCode: this.menuRemoveSectionCode,
	        design: 'alert',
	        title: this.loc('TASKS_V2_CHECK_LIST_ITEM_MENU_REMOVE'),
	        icon: ui_iconSet_api_core.Outline.TRASHCAN,
	        dataset: {
	          id: `MenuProfileRemove-${this.id}`
	        },
	        onClick: this.removeItem.bind(this)
	      };
	      if (this.isPreview) {
	        return [collapseItem, this.canModify ? editItem : null, this.canModify ? removeItem : null];
	      }
	      return [collapseItem, groupActionsItem, removeItem];
	    },
	    itemIcon() {
	      return this.completed ? ui_iconSet_api_core.Outline.CHECK_L : ui_iconSet_api_core.Outline.CHECK_LIST;
	    },
	    checkListStatus() {
	      const label = this.loc('TASKS_V2_CHECK_LIST_STATUS_LABEL_NEW');
	      return label.replace('#completed#', this.completedCount).replace('#total#', this.totalCount);
	    },
	    completedCount() {
	      return this.checkLists.filter(checklist => {
	        return checklist.parentId === this.id && (checklist.isComplete || checklist.localCompleteState);
	      }).length;
	    },
	    totalCount() {
	      return this.checkLists.filter(checklist => {
	        return checklist.parentId === this.id;
	      }).length;
	    },
	    currentUserResponsible() {
	      return this.currentUserId === this.task.responsibleId;
	    },
	    currentUser() {
	      return this.$store.getters[`${tasks_v2_const.Model.Users}/getById`](this.currentUserId);
	    },
	    numberMyItems() {
	      return this.checkListManager.findItemIdsWithUser(this.id, this.currentUserId).size;
	    },
	    myFilterTooltip() {
	      return () => tasks_v2_component_elements_hint.tooltip({
	        text: this.myFilterActive ? this.loc('TASKS_V2_CHECK_LIST_MY_FILTER_HINT_ALL') : this.loc('TASKS_V2_CHECK_LIST_MY_FILTER_HINT_MY'),
	        popupOptions: {
	          offsetLeft: this.$refs.myFilter.offsetWidth / 2
	        }
	      });
	    },
	    myFilterActive() {
	      return this.item.myFilterActive;
	    }
	  },
	  watch: {
	    myFilterActive(value) {
	      this.handleMyFilter(value);
	    }
	  },
	  created() {
	    this.checkListManager = new CheckListManager({
	      computed: {
	        checkLists: () => this.checkLists
	      }
	    });
	  },
	  mounted() {
	    var _this$$parent$$el;
	    this.scrollContainer = (_this$$parent$$el = this.$parent.$el) == null ? void 0 : _this$$parent$$el.closest('[data-list]');
	    if (this.setItemsRef) {
	      this.setItemsRef(this.id, this);
	    }
	    if (this.scrollContainer) {
	      main_core.Event.bind(this.scrollContainer, 'scroll', this.handleScroll);
	      void this.$nextTick(this.checkSticky);
	      this.mutationObserver = new MutationObserver(() => {
	        this.checkSticky();
	      });
	      this.mutationObserver.observe(this.scrollContainer, {
	        childList: true,
	        subtree: true
	      });
	    }
	  },
	  beforeUnmount() {
	    if (this.scrollContainer) {
	      main_core.Event.unbind(this.scrollContainer, 'scroll', this.handleScroll);
	    }
	    if (this.mutationObserver) {
	      this.mutationObserver.disconnect();
	    }
	    if (this.setItemsRef) {
	      this.setItemsRef(this.id, null);
	    }
	  },
	  methods: {
	    handleScroll() {
	      this.checkSticky();
	    },
	    handleTextClick() {
	      if (this.isPreview && this.canModify) {
	        this.$emit('openCheckList', this.id);
	      }
	    },
	    handleMyFilter(checked) {
	      const myItemIds = this.checkListManager.findItemIdsWithUser(this.id, this.currentUserId);
	      this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/update`, {
	        id: this.id,
	        fields: {
	          myFilterActive: checked
	        }
	      });
	      if (checked === true) {
	        const idsToHide = this.checkListManager.getAllChildren(this.id).filter(item => !myItemIds.has(item.id)).map(item => item.id);
	        this.checkListManager.hideItems(idsToHide, updates => this.upsertCheckLists(updates));
	      } else {
	        const childrenIds = this.checkListManager.getAllChildren(this.id).map(item => item.id);
	        this.checkListManager.showItems(childrenIds, updates => this.upsertCheckLists(updates));
	      }
	    },
	    checkSticky() {
	      if (!this.scrollContainer || !this.$refs.item) {
	        return;
	      }
	      const stickyRect = this.$refs.item.getBoundingClientRect();
	      const containerRect = this.scrollContainer.getBoundingClientRect();
	      this.isSticky = stickyRect.top <= containerRect.top + stickyRect.height / 2;
	    },
	    showMenu() {
	      this.isMenuShown = true;
	    },
	    toggleCollapse() {
	      const localCollapsedState = !this.isCollapsed(this.item);
	      if (this.isPreview && this.isEdit) {
	        if (localCollapsedState === true) {
	          void tasks_v2_provider_service_checkListService.checkListService.collapse(this.taskId, this.id);
	        } else {
	          void tasks_v2_provider_service_checkListService.checkListService.expand(this.taskId, this.id);
	        }
	      }
	      this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/update`, {
	        id: this.id,
	        fields: {
	          localCollapsedState
	        }
	      });
	    },
	    isCollapsed(item) {
	      return this.checkListManager.isItemCollapsed(item, this.isPreview, this.positionIndex);
	    },
	    toggleCompleted(itemId, collapsed) {
	      this.checkListManager.getAllCompletedChildren(itemId).forEach(item => {
	        if (collapsed === false) {
	          this.checkListManager.showItems([item.id], updates => this.upsertCheckLists(updates));
	        } else {
	          this.checkListManager.hideItems([item.id], updates => this.upsertCheckLists(updates));
	        }
	      });
	    }
	  },
	  template: `
		<div
			ref="item"
			class="check-list-widget-parent-item"
			:class="{
				'--complete': completed,
				'--collapsed': isCollapsed(item),
				'--preview': isPreview,
				'--editable': canModify,
			}"
			:data-id="id"
			:data-parent="id"
		>
			<div class="check-list-widget-parent-item-label-container">
				<BIcon :name="itemIcon"/>
			</div>
			<div class="check-list-widget-parent-item-title-container">
				<GrowingTextArea
					ref="growingTextArea"
					class="check-list-widget-parent-item-title"
					:data-check-list-id="'check-list-parent-item-title-' + id"
					:modelValue="item.title"
					:placeholder="loc('TASKS_V2_CHECK_LIST_ITEM_PLACEHOLDER')"
					:readonly="groupMode || isPreview"
					:fontColor="textColor"
					:fontSize="17"
					:lineHeight="20"
					:fontWeight="500"
					@click="handleTextClick"
					@update:modelValue="updateTitle"
					@input="updateTitle"
					@focus="handleFocus"
					@emptyFocus="focusToItem"
					@blur="handleBlur"
					@emptyBlur="handleEmptyBlur"
				/>
				<template v-if="hasAttachments">
					<div class="check-list-widget-item-attach --parent">
						<div v-if="hasUsers" class="check-list-widget-item-attach-users">
							<div v-if="hasAccomplices" class="check-list-widget-item-attach-users-list">
								<BIcon :name="Outline.GROUP"/>
								<UserAvatarList :users="accomplices"/>
							</div>
							<div v-if="hasAuditors" class="check-list-widget-item-attach-users-list">
								<BIcon :name="Outline.OBSERVER"/>
								<UserAvatarList :users="auditors"/>
							</div>
						</div>
					</div>
				</template>
				<div class="check-list-widget-parent-item-title-status">
					<div class="check-list-widget-parent-item-title-status-label">
						{{ checkListStatus }}
					</div>
					<ProgressBar
						:totalValue="totalCount"
						:completedValue="completedCount"
						:width="56"
						:height="5"
						color="var(--ui-color-accent-main-primary-alt)"
						bgColor="var(--ui-color-base-7)"
						:borderRadius="30"
					/>
				</div>
			</div>
			<div class="check-list-widget-parent-item-action">
				<div class="check-list-widget-parent-item-main-action">
					<div
						ref="myFilter"
						class="check-list-widget-parent-item-main-action-filter"
						v-hint="myFilterTooltip"
					>
						<UserCheckbox
							v-if="!currentUserResponsible && numberMyItems > 0"
							:init-user="currentUser"
							:number="numberMyItems"
							v-model:checked="item.myFilterActive"
						/>
					</div>
					<div class="check-list-widget-parent-item-main-action-actions">
						<BIcon ref="more" :name="Outline.MORE_L" @click="showMenu" />
						<BIcon
							:name="isCollapsed(item) ? Actions.CHEVRON_DOWN : Actions.CHEVRON_UP"
							@click="toggleCollapse()"
						/>
					</div>
				</div>
				<div v-if="isSticky && !isPreview" class="check-list-widget-parent-item-empty"></div>
			</div>
			<BMenu v-if="isMenuShown" :options="menuOptions" @close="isMenuShown = false"/>
		</div>
	`
	};

	// @vue/component
	const CheckListChildItem = {
	  name: 'CheckListChildItem',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    BLine: ui_system_skeleton_vue.BLine,
	    GrowingTextArea: tasks_v2_component_elements_growingTextArea.GrowingTextArea,
	    UserAvatarList: tasks_v2_component_elements_userAvatarList.UserAvatarList,
	    UserFieldWidgetComponent: disk_uploader_userFieldWidget.UserFieldWidgetComponent
	  },
	  mixins: [CheckListItemMixin],
	  inject: ['setItemsRef'],
	  props: {
	    itemOffset: {
	      type: String,
	      default: '0'
	    }
	  },
	  emits: ['toggleGroupModeSelected'],
	  setup(props) {
	    const fileServiceInstance = tasks_v2_provider_service_fileService.fileService.get(props.id, tasks_v2_provider_service_fileService.EntityTypes.CheckListItem, {
	      parentEntityId: props.taskId
	    });
	    return {
	      Outline: ui_iconSet_api_core.Outline,
	      fileService: fileServiceInstance,
	      uploaderAdapter: fileServiceInstance.getAdapter()
	    };
	  },
	  data() {
	    return {
	      uploadingFiles: this.fileService.getFiles(),
	      filesLoading: false
	    };
	  },
	  computed: {
	    widgetOptions() {
	      return {
	        isEmbedded: true,
	        withControlPanel: false,
	        canCreateDocuments: false,
	        tileWidgetOptions: {
	          compact: true,
	          hideDropArea: true,
	          enableDropzone: false,
	          readonly: this.isPreview,
	          autoCollapse: false,
	          removeFromServer: !this.isEdit
	        }
	      };
	    },
	    hasAttachments() {
	      return this.hasUsers || this.hasFilesAttach;
	    },
	    hasFilesAttach() {
	      return this.hasFiles || this.fileService.isUploading() || this.fileService.hasUploadingError();
	    },
	    hasFiles() {
	      return this.filesNumber > 0;
	    },
	    filesNumber() {
	      if (!this.files) {
	        return 0;
	      }
	      return this.files.length;
	    },
	    hasTrashcanIcon() {
	      return this.isHovered && !this.item.panelIsShown && !this.groupMode && !this.isPreview;
	    },
	    readOnly() {
	      return this.isPreview;
	    }
	  },
	  created() {
	    if (this.hasFilesAttach) {
	      void this.loadFiles();
	    }
	    this.checkListManager = new CheckListManager({
	      computed: {
	        checkLists: () => this.checkLists
	      }
	    });
	  },
	  mounted() {
	    if (this.setItemsRef) {
	      this.setItemsRef(this.id, this);
	    }
	  },
	  beforeUnmount() {
	    if (this.setItemsRef) {
	      this.setItemsRef(this.id, null);
	    }
	  },
	  methods: {
	    toggleGroupModeSelected() {
	      this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/update`, {
	        id: this.id,
	        fields: {
	          groupMode: {
	            active: true,
	            selected: !this.groupModeSelected
	          }
	        }
	      });
	      this.$emit('toggleGroupModeSelected', this.id);
	    },
	    async loadFiles() {
	      var _this$files;
	      this.filesLoading = true;
	      const ids = (_this$files = this.files) == null ? void 0 : _this$files.map(file => {
	        var _file$id;
	        return (_file$id = file == null ? void 0 : file.id) != null ? _file$id : file;
	      });
	      await this.fileService.list(ids != null ? ids : []);
	      this.filesLoading = false;
	    },
	    handleEnter() {
	      if (!this.item) {
	        return;
	      }
	      this.addItem(this.item.sortIndex + 1);
	    },
	    handleClick(event) {
	      const filesWidget = this.$refs['files-widget'];
	      if (this.isClickInsideFilesWidget(filesWidget == null ? void 0 : filesWidget.$el, event.target)) {
	        return;
	      }
	      if (this.groupMode) {
	        this.toggleGroupModeSelected();
	      }
	      if (this.isPreview) {
	        this.complete(!this.completed);
	      }
	    },
	    isClickInsideFilesWidget(filesNode, target) {
	      if (!filesNode || !target) {
	        return false;
	      }
	      const excludedClasses = ['ui-tile-uploader-items'];
	      const isInsideWidget = filesNode.contains(target);
	      if (!isInsideWidget) {
	        return false;
	      }
	      const hasExcludedClass = excludedClasses.some(className => main_core.Dom.hasClass(target, className));
	      return !hasExcludedClass;
	    }
	  },
	  template: `
		<div
			ref="item"
			class="check-list-widget-child-item"
			:class="{
				'--complete': completed,
				'--group-mode': groupMode,
				'--group-mode-selected': groupModeSelected,
				'--preview': isPreview,
				'--toggleable': canToggle,
			}"
			:data-id="id"
			:style="{ marginLeft: itemOffset }"
			@mouseover="isHovered = true"
			@mouseleave="isHovered = false"
			@click="handleClick"
		>
			<div class="check-list-widget-child-item-base">
				<label
					class="check-list-widget-child-item-checkbox"
					:class="{'--important': !item.isImportant}"
				>
					<input
						ref="checkbox"
						type="checkbox"
						:checked="completed"
						:disabled="!canToggle || groupMode"
						@click.stop="complete(!completed)"
					>
				</label>
				<div
					v-if="item.isImportant"
					class="check-list-widget-child-item-important"
				>
					<BIcon :name="Outline.FIRE_SOLID" :hoverable="false"/>
				</div>
				<GrowingTextArea
					ref="growingTextArea"
					class="check-list-widget-child-item-title"
					:data-check-list-id="'check-list-child-item-title-' + item.id"
					:modelValue="item.title"
					:placeholder="loc('TASKS_V2_CHECK_LIST_ITEM_PLACEHOLDER')"
					:readonly="groupMode || isPreview"
					:fontColor="textColor"
					:fontSize="15"
					:lineHeight="20"
					@update:modelValue="updateTitle"
					@input="updateTitle"
					@focus="handleFocus"
					@blur="handleBlur"
					@emptyBlur="handleEmptyBlur"
					@emptyFocus="focusToItem"
					@enterBlur="handleEnter"
				/>
				<div
					v-if="hasTrashcanIcon"
					class="check-list-widget-child-item-action"
					@click="removeItem"
				>
					<BIcon :name="Outline.TRASHCAN"/>
				</div>
				<div v-else-if="groupMode" class="check-list-widget-child-item-action-checkbox">
					<input
						ref="checkbox"
						type="checkbox"
						:checked="groupModeSelected"
					>
				</div>
				<div v-else class="check-list-widget-child-item-action-stub"></div>
			</div>
			<template v-if="hasAttachments">
				<div class="check-list-widget-item-attach">
					<div v-if="hasUsers" class="check-list-widget-item-attach-users">
						<div v-if="hasAccomplices" class="check-list-widget-item-attach-users-list">
							<BIcon :name="Outline.GROUP"/>
							<UserAvatarList :users="accomplices"/>
						</div>
						<div v-if="hasAuditors" class="check-list-widget-item-attach-users-list">
							<BIcon :name="Outline.OBSERVER"/>
							<UserAvatarList :users="auditors"/>
						</div>
					</div>
					<div v-if="hasFilesAttach" class="check-list-widget-item-attach-files">
						<div class="check-list-widget-item-attach-files-list">
							<template v-if="filesLoading">
								<div class="check-list-widget-item-attach-files-list-skeleton">
									<BLine
										v-for="index in filesNumber"
										:key="index"
										:width="114"
										:height="90"
									/>
								</div>
							</template>
							<template v-else>
								<UserFieldWidgetComponent
									ref="files-widget"
									:uploaderAdapter="uploaderAdapter"
									:widgetOptions="widgetOptions"
								/>
							</template>
						</div>
					</div>
				</div>
			</template>
		</div>
	`
	};

	// @vue/component
	const CheckListAddItem = {
	  name: 'CheckListAddItem',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    isPreview: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['addItem'],
	  setup() {
	    return {
	      Outline: ui_iconSet_api_core.Outline
	    };
	  },
	  template: `
		<div
			class="check-list-widget-add-item"
			:class="{'--preview': isPreview}"
			@mousedown="$emit('addItem')"
		>
			<div class="check-list-widget-add-item-icon">
				<BIcon :name="Outline.PLUS_L"/>
			</div>
			<div class="check-list-widget-add-item-title">
				{{ loc('TASKS_V2_CHECK_LIST_ITEM_ADD_BTN') }}
			</div>
		</div>
	`
	};

	// @vue/component
	const CheckListGroupCompletedList = {
	  name: 'CheckListGroupCompletedList',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    totalCompletedParents: {
	      type: Number,
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Actions: ui_iconSet_api_vue.Actions,
	      Outline: ui_iconSet_api_vue.Outline
	    };
	  },
	  computed: {
	    completedParentsLabel() {
	      return this.loc('TASKS_V2_CHECK_LIST_PREVIEW_COMPLETED', {
	        '#number#': this.totalCompletedParents
	      });
	    }
	  },
	  template: `
		<div class="check-list-widget-group-completed-list">
			<div class="check-list-widget-group-completed-list-icon">
				<BIcon :name="Outline.CHECK_L"/>
			</div>
			<div class="check-list-widget-group-completed-list-title">
				{{ completedParentsLabel }}
			</div>
			<div class="check-list-widget-group-completed-list-action">
				<BIcon :name="Actions.CHEVRON_RIGHT" />
			</div>
		</div>
	`
	};

	// @vue/component
	const CheckListWidget = {
	  name: 'CheckListWidget',
	  components: {
	    CheckListParentItem,
	    CheckListChildItem,
	    CheckListAddItem,
	    CheckListGroupCompletedList
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    checkListId: {
	      type: [Number, String],
	      default: 0
	    },
	    parentId: {
	      type: [Number, String],
	      default: 0
	    },
	    isPreview: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['show', 'update', 'addItem', 'addItemFromBtn', 'removeItem', 'focus', 'blur', 'emptyBlur', 'startGroupMode', 'toggleGroupModeSelected', 'openCheckList'],
	  data() {
	    return {};
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      currentUserId: `${tasks_v2_const.Model.Interface}/currentUserId`,
	      disableCheckListAnimations: `${tasks_v2_const.Model.Interface}/disableCheckListAnimations`
	    }),
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    isEdit() {
	      return main_core.Type.isNumber(this.taskId) && this.taskId > 0;
	    },
	    checkLists() {
	      return this.$store.getters[`${tasks_v2_const.Model.CheckList}/getByIds`](this.task.checklist);
	    },
	    parentCheckLists() {
	      return this.checkLists.filter(checkList => {
	        if (checkList.parentId !== 0 || checkList.hidden) {
	          return false;
	        }
	        if (this.isEdit && !this.isPreview && !this.canEditCheckList(checkList)) {
	          return false;
	        }
	        return !(this.isPreview && checkList.isComplete);
	      }).sort((a, b) => {
	        if (a.isComplete === b.isComplete) {
	          return a.sortIndex - b.sortIndex;
	        }
	        return a.isComplete ? 1 : -1;
	      });
	    },
	    totalCompletedParents() {
	      return this.checkLists.filter(checkList => {
	        return checkList.parentId === 0 && checkList.isComplete;
	      }).length;
	    },
	    siblings() {
	      return this.checkLists.filter(item => item.parentId === this.parentId).sort((a, b) => a.sortIndex - b.sortIndex);
	    },
	    canAddItem() {
	      if (!this.isEdit) {
	        return true;
	      }
	      return this.task.rights.checklistAdd === true;
	    }
	  },
	  created() {
	    this.checkListManager = new CheckListManager({
	      computed: {
	        checkLists: () => this.checkLists
	      }
	    });
	  },
	  mounted() {
	    if (this.checkListManager.getItem(this.checkListId)) {
	      var _this$$root$$el;
	      this.scrollContainer = (_this$$root$$el = this.$root.$el) == null ? void 0 : _this$$root$$el.querySelector(['[data-list]']);
	      const childWithEmptyTitle = this.checkListManager.getChildWithEmptyTitle(this.checkListId);
	      const isFocusToCheckList = !childWithEmptyTitle;
	      const targetItemId = isFocusToCheckList ? this.checkListId : childWithEmptyTitle.id;
	      const scrollOffset = isFocusToCheckList ? 0 : 140;
	      const targetItem = this.scrollContainer.querySelector([`[data-id="${targetItemId}"]`]);
	      setTimeout(() => {
	        if (isFocusToCheckList) {
	          const highlightElement = this.scrollContainer.querySelector([`[data-parent-container="${this.checkListId}"]`]);
	          if (highlightElement) {
	            void new Highlighter().highlight(highlightElement);
	          }
	        }
	        if (targetItem) {
	          this.scrollContainer.scrollTop = targetItem.offsetTop - scrollOffset;
	        }
	      }, 0);
	    }
	    this.$emit('show');
	  },
	  methods: {
	    getItemOffset(item) {
	      if (item.parentId === 0) {
	        return '0';
	      }
	      const level = this.checkListManager.getItemLevel(item);
	      if (level === 1) {
	        return '0';
	      }
	      return `${(level - 1) * 12}px`;
	    },
	    getChildren(parent) {
	      return this.checkListManager.getAllChildren(parent.id).filter(checkList => !checkList.hidden);
	    },
	    isCollapsed(item, positionIndex) {
	      return this.checkListManager.isItemCollapsed(item, this.isPreview, positionIndex);
	    },
	    getFirstCompletedCheckList() {
	      const completedCheckLists = this.checkLists.filter(checkList => {
	        return checkList.parentId === 0 && checkList.isComplete === true;
	      }).sort((a, b) => a.sortIndex - b.sortIndex);
	      return completedCheckLists[0];
	    },
	    showFirstCompletedCheckList() {
	      const firstCompletedCheckList = this.getFirstCompletedCheckList();
	      this.$emit('openCheckList', firstCompletedCheckList.id);
	    },
	    canEditCheckList(item) {
	      if (!item.creator) {
	        return true;
	      }
	      return this.task.rights.checklistEdit === true || item.creator.id === this.currentUserId;
	    }
	  },
	  template: `
		<div class="check-list-widget-container">
			<TransitionGroup
				:css="!disableCheckListAnimations"
				name="check-list"
				mode="out-in"
				tag="ul"
				class="check-list-widget --parent"
				:class="{'--preview': isPreview}"
			>
				<li
					v-for="(parentItem, parentItemIndex) in parentCheckLists"
					:key="parentItem.id"
					class="check-list-widget-item --parent"
					:class="{
						'--preview': isPreview,
						'--collapsed': isCollapsed(parentItem, parentItemIndex),
						'--hidden': parentItem.hidden,
					}"
					:data-parent-container="parentItem.id"
				>
					<CheckListParentItem
						:id="parentItem.id"
						:taskId="taskId"
						:isPreview="isPreview"
						:positionIndex="parentItemIndex"
						@update="(id) => $emit('update', id)"
						@removeItem="(id) => $emit('removeItem', id)"
						@focus="(id) => $emit('focus', id)"
						@blur="(id) => $emit('blur', id)"
						@emptyBlur="(id) => $emit('emptyBlur', id)"
						@startGroupMode="(id) => $emit('startGroupMode', id)"
						@openCheckList="(id) => $emit('openCheckList', id)"
					/>
					<TransitionGroup
						:css="!disableCheckListAnimations"
						name="check-list"
						mode="out-in"
						tag="ul"
						class="check-list-widget"
					>
						<li
							v-if="!isCollapsed(parentItem, parentItemIndex)"
							v-for="childItem in getChildren(parentItem)"
							:key="childItem.id"
							class="check-list-widget-item"
						>
							<CheckListChildItem
								:id="childItem.id"
								:taskId="taskId"
								:itemOffset="getItemOffset(childItem)"
								:isPreview="isPreview"
								@update="(id) => $emit('update', id)"
								@addItem="(data) => $emit('addItem', data)"
								@removeItem="(id) => $emit('removeItem', id)"
								@focus="(id) => $emit('focus', id)"
								@blur="(id) => $emit('blur', id)"
								@emptyBlur="(id) => $emit('emptyBlur', id)"
								@toggleGroupModeSelected="(id) => $emit('toggleGroupModeSelected', id)"
							/>
						</li>
						<li
							v-if="!isCollapsed(parentItem, parentItemIndex) && canAddItem"
							key="add-item"
							class="check-list-widget-item"
						>
							<CheckListAddItem
								:isPreview="isPreview"
								@addItem="$emit('addItemFromBtn', parentItem.id)"
							/>
						</li>
					</TransitionGroup>
				</li>
				<li
					v-if="isPreview && totalCompletedParents > 0"
					key="completed-list"
					class="check-list-widget-item --completed-list"
				>
					<CheckListGroupCompletedList
						:totalCompletedParents="totalCompletedParents"
						@click="showFirstCompletedCheckList"
					/>
				</li>
			</TransitionGroup>
		</div>
	`
	};

	const PanelSection = Object.freeze({
	  Important: 'important',
	  Attachments: 'attachments',
	  Movement: 'movement',
	  Accomplice: 'accomplice',
	  Auditor: 'auditor',
	  Forward: 'forward',
	  Delete: 'delete',
	  Cancel: 'cancel'
	});
	const PanelAction = Object.freeze({
	  SetImportant: 'setImportant',
	  AttachFile: 'attachFile',
	  MoveRight: 'moveRight',
	  MoveLeft: 'moveLeft',
	  AssignAccomplice: 'assignAccomplice',
	  AssignAuditor: 'assignAuditor',
	  Forward: 'forward',
	  Delete: 'delete',
	  Cancel: 'cancel'
	});
	const PanelMeta = Object.freeze({
	  defaultSections: [{
	    name: PanelSection.Important,
	    items: [{
	      icon: ui_iconSet_api_core.Outline.FIRE,
	      activeIcon: ui_iconSet_api_core.Outline.FIRE_SOLID,
	      action: PanelAction.SetImportant,
	      hint: 'TASKS_V2_CHECK_LIST_ITEM_IMPORTANT_HINT',
	      className: '--important',
	      hoverable: false
	    }]
	  }, {
	    name: PanelSection.Attachments,
	    items: [{
	      icon: ui_iconSet_api_core.Outline.ATTACH,
	      action: PanelAction.AttachFile,
	      hint: 'TASKS_V2_CHECK_LIST_ITEM_ATTACH_HINT'
	    }]
	  }, {
	    name: PanelSection.Movement,
	    items: [{
	      icon: ui_iconSet_api_core.Outline.POINT_RIGHT,
	      action: PanelAction.MoveRight,
	      hint: 'TASKS_V2_CHECK_LIST_ITEM_MOVE_RIGHT_HINT'
	    }, {
	      icon: ui_iconSet_api_core.Outline.POINT_LEFT,
	      action: PanelAction.MoveLeft,
	      hint: 'TASKS_V2_CHECK_LIST_ITEM_MOVE_LEFT_HINT'
	    }]
	  }, {
	    name: PanelSection.Accomplice,
	    items: [{
	      icon: ui_iconSet_api_core.Outline.PERSON,
	      action: PanelAction.AssignAccomplice,
	      hint: 'TASKS_V2_CHECK_LIST_ITEM_ACCOMPLICE_HINT'
	    }]
	  }, {
	    name: PanelSection.Auditor,
	    items: [{
	      icon: ui_iconSet_api_core.Outline.OBSERVER,
	      action: PanelAction.AssignAuditor,
	      hint: 'TASKS_V2_CHECK_LIST_ITEM_AUDITOR_HINT'
	    }]
	  }, {
	    name: PanelSection.Forward,
	    items: [{
	      icon: ui_iconSet_api_core.Outline.FORWARD,
	      action: PanelAction.Forward,
	      hint: 'TASKS_V2_CHECK_LIST_ITEM_FORWARD_HINT'
	    }]
	  }, {
	    name: PanelSection.Delete,
	    items: [{
	      icon: ui_iconSet_api_core.Outline.TRASHCAN,
	      action: PanelAction.Delete,
	      hint: 'TASKS_V2_CHECK_LIST_ITEM_REMOVE_HINT'
	    }]
	  }, {
	    name: PanelSection.Cancel,
	    items: [{
	      icon: ui_iconSet_api_core.Outline.CROSS_L,
	      action: PanelAction.Cancel,
	      hint: 'TASKS_V2_CHECK_LIST_ITEM_CANCEL_HINT'
	    }]
	  }]
	});

	// @vue/component
	const CheckListItemPanel = {
	  name: 'CheckListItemPanel',
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
	    },
	    currentItem: {
	      type: Object,
	      default: () => null
	    }
	  },
	  emits: ['action'],
	  data() {
	    return {
	      currentHintElement: null,
	      currentHintText: ''
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    checkLists() {
	      return this.$store.getters[`${tasks_v2_const.Model.CheckList}/getByIds`](this.task.checklist);
	    },
	    sections() {
	      return PanelMeta.defaultSections.filter(section => this.visibleSections.includes(section.name)).map(section => ({
	        ...section,
	        items: section.items.filter(item => this.visibleActions.includes(item.action)).map(item => {
	          var _item$hoverable;
	          return {
	            ...item,
	            disabled: this.isItemDisabled(item),
	            active: this.isItemActive(item),
	            hoverable: (_item$hoverable = item.hoverable) != null ? _item$hoverable : true
	          };
	        })
	      })).filter(section => section.items.length > 0);
	    },
	    tooltip() {
	      return () => {
	        var _this$currentHintElem, _this$currentHintElem2;
	        return {
	          text: this.currentHintText,
	          timeout: 500,
	          popupOptions: {
	            className: 'tasks-hint',
	            background: 'var(--ui-color-bg-content-inapp)',
	            darkMode: false,
	            offsetLeft: -((_this$currentHintElem = (_this$currentHintElem2 = this.currentHintElement) == null ? void 0 : _this$currentHintElem2.offsetWidth) != null ? _this$currentHintElem : 0),
	            padding: 6,
	            bindOptions: {
	              forceBindPosition: true,
	              forceTop: true,
	              position: 'top'
	            },
	            targetContainer: document.body
	          }
	        };
	      };
	    },
	    visibleSections() {
	      return PanelMeta.defaultSections.map(section => section.name);
	    },
	    visibleActions() {
	      if (!this.currentItem) {
	        return [];
	      }
	      let actions = [PanelAction.SetImportant, PanelAction.MoveRight, PanelAction.MoveLeft, PanelAction.AssignAccomplice, PanelAction.AssignAuditor, PanelAction.Forward, PanelAction.Delete];
	      if (this.itemGroupModeSelected) {
	        actions.push(PanelAction.Cancel);
	      } else {
	        actions.push(PanelAction.AttachFile);
	      }
	      if (this.currentItem.parentId === 0) {
	        actions = [PanelAction.AssignAccomplice, PanelAction.AssignAuditor];
	      }
	      const limits = tasks_v2_core.Core.getParams().limits;
	      const stakeholdersActions = new Set([PanelAction.AssignAccomplice, PanelAction.AssignAuditor]);
	      return actions.filter(action => {
	        const isDisabledStakeholders = stakeholdersActions.has(action) && !limits.stakeholders;
	        return !isDisabledStakeholders;
	      });
	    },
	    disabledActions() {
	      if (!this.currentItem) {
	        return [];
	      }
	      const disabledActions = [];
	      const itemLevel = this.checkListManager.getItemLevel(this.currentItem);
	      const canModify = this.currentItem.actions.modify === true;
	      const canRemove = this.currentItem.actions.remove === true;
	      const conditionHandlers = {
	        [PanelAction.SetImportant]: () => {
	          return canModify === false;
	        },
	        [PanelAction.AttachFile]: () => {
	          return canModify === false;
	        },
	        [PanelAction.MoveLeft]: () => {
	          return itemLevel === 1 || canModify === false;
	        },
	        [PanelAction.MoveRight]: () => {
	          return itemLevel === 5 || this.currentItem.sortIndex === 0 || canModify === false;
	        },
	        [PanelAction.AssignAccomplice]: () => {
	          return canModify === false;
	        },
	        [PanelAction.AssignAuditor]: () => {
	          return canModify === false;
	        },
	        [PanelAction.Forward]: () => {
	          return canModify === false || this.currentItem.title === '';
	        },
	        [PanelAction.Delete]: () => {
	          return canRemove === false;
	        }
	      };
	      Object.entries(conditionHandlers).forEach(([action, condition]) => {
	        if (condition()) {
	          disabledActions.push(action);
	        }
	      });
	      return disabledActions;
	    },
	    activeActions() {
	      if (!this.currentItem) {
	        return [];
	      }
	      const actions = [];
	      if (this.currentItem.isImportant) {
	        actions.push(PanelAction.SetImportant);
	      }
	      return actions;
	    },
	    itemGroupModeSelected() {
	      var _this$currentItem$gro;
	      if (!this.currentItem) {
	        return false;
	      }
	      return ((_this$currentItem$gro = this.currentItem.groupMode) == null ? void 0 : _this$currentItem$gro.selected) === true;
	    }
	  },
	  created() {
	    this.checkListManager = new CheckListManager({
	      computed: {
	        checkLists: () => this.checkLists
	      }
	    });
	  },
	  methods: {
	    isItemDisabled(item) {
	      var _item$disabled;
	      return (_item$disabled = item.disabled) != null ? _item$disabled : this.disabledActions.includes(item.action);
	    },
	    isItemActive(item) {
	      var _item$active;
	      return (_item$active = item.active) != null ? _item$active : this.activeActions.includes(item.action);
	    },
	    getItemIcon(item) {
	      return item.active && item.activeIcon ? item.activeIcon : item.icon;
	    },
	    handleItemClick(event, item) {
	      if (!item.disabled) {
	        this.$emit('action', {
	          action: item.action,
	          node: event.currentTarget
	        });
	      }
	    },
	    handleItemMouseEnter(event, item) {
	      this.currentHintElement = event.currentTarget;
	      this.currentHintText = item.hint ? this.loc(item.hint) : null;
	    }
	  },
	  template: `
		<div v-if="sections.length > 0" class="check-list-widget-item-panel" @mousedown.prevent>
			<template v-for="section in sections" :key="section.name">
				<div class="check-list-widget-item-panel-section" :class="'--' + section.name">
					<template v-for="item in section.items" :key="item.action" >
						<div
							v-hint="tooltip"
							class="check-list-widget-item-panel-section-item"
							:class="{
								'--disabled': item.disabled,
								'--active': item.active,
								[item.className]: Boolean(item.className),
							}"
							@click="handleItemClick($event, item)"
							@mouseenter="handleItemMouseEnter($event, item)"
						>
							<BIcon :name="getItemIcon(item)" :hoverable="item.hoverable"/>
							<span v-if="item.label">{{ loc(item.label) }}</span>
						</div>
					</template>
				</div>
			</template>
		</div>
	`
	};

	var _interval = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("interval");
	var _timerValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("timerValue");
	var _counter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("counter");
	var _content = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("content");
	var _balloonWithTimer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("balloonWithTimer");
	var _startTimer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("startTimer");
	var _handleCancelClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleCancelClick");
	var _handleClosingBalloon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleClosingBalloon");
	var _getBalloonContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBalloonContent");
	class CheckListNotifier extends main_core_events.EventEmitter {
	  constructor(params) {
	    super();
	    Object.defineProperty(this, _getBalloonContent, {
	      value: _getBalloonContent2
	    });
	    Object.defineProperty(this, _handleClosingBalloon, {
	      value: _handleClosingBalloon2
	    });
	    Object.defineProperty(this, _handleCancelClick, {
	      value: _handleCancelClick2
	    });
	    Object.defineProperty(this, _startTimer, {
	      value: _startTimer2
	    });
	    Object.defineProperty(this, _interval, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _timerValue, {
	      writable: true,
	      value: 5
	    });
	    Object.defineProperty(this, _counter, {
	      writable: true,
	      value: 5
	    });
	    Object.defineProperty(this, _content, {
	      writable: true,
	      value: ''
	    });
	    Object.defineProperty(this, _balloonWithTimer, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('Tasks.V2.CheckList.CheckListNotifier');
	    babelHelpers.classPrivateFieldLooseBase(this, _content)[_content] = params.content;
	    babelHelpers.classPrivateFieldLooseBase(this, _timerValue)[_timerValue] = main_core.Type.isUndefined(params.timerValue) ? babelHelpers.classPrivateFieldLooseBase(this, _timerValue)[_timerValue] : params.timerValue;
	  }
	  showBalloonWithTimer() {
	    babelHelpers.classPrivateFieldLooseBase(this, _counter)[_counter] = babelHelpers.classPrivateFieldLooseBase(this, _timerValue)[_timerValue];
	    babelHelpers.classPrivateFieldLooseBase(this, _balloonWithTimer)[_balloonWithTimer] = ui_notification.UI.Notification.Center.notify({
	      id: `check-list-balloon-${main_core.Text.getRandom()}`,
	      content: babelHelpers.classPrivateFieldLooseBase(this, _getBalloonContent)[_getBalloonContent](),
	      actions: [{
	        title: main_core.Loc.getMessage('TASKS_V2_CHECK_LIST_BALLOON_CANCEL'),
	        events: {
	          mouseup: babelHelpers.classPrivateFieldLooseBase(this, _handleCancelClick)[_handleCancelClick].bind(this)
	        }
	      }],
	      events: {
	        onClose: babelHelpers.classPrivateFieldLooseBase(this, _handleClosingBalloon)[_handleClosingBalloon].bind(this)
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _startTimer)[_startTimer]();
	  }
	  stopTimer() {
	    this.emit('complete', true);
	    babelHelpers.classPrivateFieldLooseBase(this, _balloonWithTimer)[_balloonWithTimer].close();
	  }
	}
	function _startTimer2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _interval)[_interval] = setInterval(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _counter)[_counter]--;
	    babelHelpers.classPrivateFieldLooseBase(this, _balloonWithTimer)[_balloonWithTimer].update({
	      content: babelHelpers.classPrivateFieldLooseBase(this, _getBalloonContent)[_getBalloonContent]()
	    });
	    if (babelHelpers.classPrivateFieldLooseBase(this, _counter)[_counter] <= 0) {
	      this.emit('complete', true);
	      babelHelpers.classPrivateFieldLooseBase(this, _balloonWithTimer)[_balloonWithTimer].close();
	    }
	  }, 1000);
	}
	function _handleCancelClick2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _balloonWithTimer)[_balloonWithTimer].close();
	}
	function _handleClosingBalloon2() {
	  clearInterval(babelHelpers.classPrivateFieldLooseBase(this, _interval)[_interval]);
	  this.emit('complete', false);
	}
	function _getBalloonContent2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _content)[_content].replace('#countdown#', babelHelpers.classPrivateFieldLooseBase(this, _counter)[_counter]);
	}

	const Context = Object.freeze({
	  Sheet: 'sheet',
	  Popup: 'popup',
	  Preview: 'preview'
	});

	// @vue/component
	const CheckList = {
	  name: 'TaskCheckList',
	  components: {
	    CheckListWidget,
	    CheckListItemPanel,
	    CheckListStub,
	    UiButton: ui_vue3_components_button.Button,
	    BIcon: ui_iconSet_api_vue.BIcon,
	    BMenu: ui_vue3_components_menu.BMenu
	  },
	  provide() {
	    return {
	      setItemsRef: this.setItemsRef,
	      getItemsRef: this.getItemsRef
	    };
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    isAutonomous: {
	      type: Boolean,
	      default: false
	    },
	    isPreview: {
	      type: Boolean,
	      default: false
	    },
	    isComponentShown: {
	      type: Boolean,
	      default: true
	    },
	    checkListId: {
	      type: [Number, String],
	      default: 0
	    },
	    isShown: {
	      type: Boolean,
	      default: false
	    },
	    getBindElement: {
	      type: Function,
	      default: null
	    },
	    getTargetContainer: {
	      type: Function,
	      default: null
	    }
	  },
	  emits: ['show', 'close', 'resize', 'open'],
	  setup() {
	    return {
	      resizeObserver: null,
	      AirButtonStyle: ui_vue3_components_button.AirButtonStyle,
	      ButtonSize: ui_vue3_components_button.ButtonSize,
	      ButtonIcon: ui_vue3_components_button.ButtonIcon,
	      Outline: ui_iconSet_api_core.Outline
	    };
	  },
	  data() {
	    return {
	      itemPanelIsShown: false,
	      checkListWasUpdated: false,
	      lastUpdatedCheckListId: 0,
	      itemId: null,
	      itemPanelStyles: {
	        top: '0',
	        display: 'flex'
	      },
	      isItemPanelFreeze: false,
	      itemPanelTopOffset: this.isAutonomous ? 5 : 2,
	      itemPanelTopLimit: this.isAutonomous ? 450 : 700,
	      itemsRefs: {},
	      isForwardMenuShown: false,
	      forwardMenuSectionCode: 'createSection',
	      forwardBindElement: null,
	      shownPopups: new Set(),
	      notifiers: new Map()
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      deletingCheckListIds: `${tasks_v2_const.Model.Interface}/deletingCheckListIds`,
	      checkListCompletionCallback: `${tasks_v2_const.Model.Interface}/checkListCompletionCallback`
	    }),
	    componentName() {
	      return {
	        [Context.Sheet]: CheckListSheet,
	        [Context.Popup]: CheckListPopup,
	        [Context.Preview]: CheckListList
	      }[this.context];
	    },
	    context() {
	      return {
	        [true]: Context.Sheet,
	        [this.isAutonomous]: Context.Popup,
	        [this.isPreview]: Context.Preview
	      }.true;
	    },
	    contextClass() {
	      return `--${this.context}`;
	    },
	    componentShown() {
	      if (this.isPreview) {
	        return this.isComponentShown;
	      }
	      return true;
	    },
	    isEdit() {
	      return main_core.Type.isNumber(this.taskId) && this.taskId > 0;
	    },
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    canSaveCheckList() {
	      return this.task.rights.checklistSave === true;
	    },
	    checkLists() {
	      return this.$store.getters[`${tasks_v2_const.Model.CheckList}/getByIds`](this.task.checklist);
	    },
	    parentCheckLists() {
	      return this.checkLists.filter(checkList => checkList.parentId === 0);
	    },
	    hasFewParentCheckLists() {
	      return this.parentCheckLists.length > 1;
	    },
	    currentItem() {
	      return this.$store.getters[`${tasks_v2_const.Model.CheckList}/getById`](this.itemId);
	    },
	    itemGroupModeSelected() {
	      var _this$currentItem$gro;
	      if (!this.currentItem) {
	        return false;
	      }
	      return ((_this$currentItem$gro = this.currentItem.groupMode) == null ? void 0 : _this$currentItem$gro.selected) === true;
	    },
	    forwardMenuOptions() {
	      return {
	        id: `check-list-item-forward-menu-${this.currentItem.id}`,
	        bindElement: this.forwardBindElement,
	        maxWidth: 400,
	        maxHeight: 300,
	        offsetLeft: -110,
	        sections: [{
	          code: this.forwardMenuSectionCode
	        }],
	        items: this.forwardMenuItems,
	        targetContainer: document.body
	      };
	    },
	    forwardMenuItems() {
	      const checklistItems = this.parentCheckLists.filter(checkList => checkList.id !== this.currentItem.parentId).map(checkList => ({
	        title: checkList.title,
	        dataset: {
	          id: `ForwardMenuCheckList-${checkList.id}`
	        },
	        onClick: () => {
	          this.hideItemPanel();
	          if (this.itemGroupModeSelected) {
	            void this.forwardGroupItemsToChecklist(checkList.id);
	          } else {
	            this.forwardToChecklist(checkList.id);
	          }
	        }
	      }));
	      return [...checklistItems, {
	        sectionCode: this.forwardMenuSectionCode,
	        title: this.loc('TASKS_V2_CHECK_LIST_ITEM_FORWARD_MENU_CREATE'),
	        dataset: {
	          id: `ForwardMenuCreateNew-${this.currentItem.id}`
	        },
	        onClick: this.forwardToNewChecklist.bind(this)
	      }];
	    },
	    stub() {
	      return this.checkLists.length === 0 || this.emptyList === true;
	    },
	    emptyList() {
	      const siblings = this.parentCheckLists.filter(item => !this.deletingCheckListIds[item.id]);
	      return siblings.length === 0;
	    }
	  },
	  watch: {
	    async titleFieldOffsetHeight() {
	      if (!this.$refs.popupComponent) {
	        return;
	      }
	      await this.$nextTick();
	      this.resize();
	    },
	    componentShown(value) {
	      if (!this.isPreview) {
	        return;
	      }
	      this.executeCheckListCompletionCallbacks();
	      void this.$nextTick(() => {
	        if (value) {
	          this.subscribeToEvents();
	          if (this.checkListManager.getItem(this.checkListId) && this.$refs.list) {
	            const checkListNode = this.$refs.list.querySelector([`[data-id="${this.checkListId}"]`]);
	            checkListNode == null ? void 0 : checkListNode.scrollIntoView({
	              block: 'center',
	              behavior: 'instant'
	            });
	          }
	        } else {
	          this.unsubscribeFromEvents();
	        }
	      });
	    },
	    checkListWasUpdated(value) {
	      if (!this.currentItem) {
	        return;
	      }
	      if (value === true) {
	        const parentItem = this.checkListManager.getRootParentByChildId(this.currentItem.id);
	        this.lastUpdatedCheckListId = parentItem ? parentItem.id : 0;
	      }
	    },
	    checkListId(value) {
	      const isNewCheckList = main_core.Type.isString(value);
	      if (isNewCheckList) {
	        this.checkListWasUpdated = true;
	      }
	    }
	  },
	  created() {
	    this.checkListManager = new CheckListManager({
	      computed: {
	        checkLists: () => this.checkLists
	      }
	    });
	  },
	  mounted() {
	    if (this.isAutonomous || this.isPreview) {
	      this.subscribeToEvents();
	    }
	  },
	  beforeUnmount() {
	    if (this.isAutonomous || this.isPreview) {
	      this.unsubscribeFromEvents();
	    }
	    if (this.isPreview) {
	      this.executeCheckListCompletionCallbacks();
	    }
	  },
	  methods: {
	    ...ui_vue3_vuex.mapActions(tasks_v2_const.Model.Interface, ['addCheckListItemToDeleting', 'removeCheckListItemFromDeleting', 'executeCheckListCompletionCallbacks', 'clearCheckListCompletionCallbacks']),
	    subscribeToEvents() {
	      main_core.Event.bind(this.$refs.list, 'scroll', this.handleScroll);
	      main_core_events.EventEmitter.subscribe('BX.Main.Popup:onInit', this.handleInitPopup);
	      main_core_events.EventEmitter.subscribe('BX.Main.Popup:onShow', this.handleShowPopup);
	      main_core_events.EventEmitter.subscribe('BX.Main.Popup:onClose', this.handleClosePopup);
	    },
	    unsubscribeFromEvents() {
	      main_core.Event.unbind(this.$refs.list, 'scroll', this.handleScroll);
	      main_core_events.EventEmitter.unsubscribe('BX.Main.Popup:onInit', this.handleInitPopup);
	      main_core_events.EventEmitter.unsubscribe('BX.Main.Popup:onShow', this.handleShowPopup);
	      main_core_events.EventEmitter.unsubscribe('BX.Main.Popup:onClose', this.handleClosePopup);
	    },
	    handleUpdate(itemId) {
	      this.itemId = itemId;
	      this.checkListWasUpdated = true;
	    },
	    handleRemove(itemId) {
	      this.itemId = itemId;
	      this.freeze();
	      this.addItemToDelete(itemId);
	      this.checkListManager.hideItems([itemId], updates => this.upsertCheckLists(updates));
	      const messageKey = this.currentItem.parentId === 0 ? 'TASKS_V2_CHECK_LIST_ITEM_REMOVE_BALLOON_PARENT' : 'TASKS_V2_CHECK_LIST_ITEM_REMOVE_BALLOON_CHILD';
	      const notifier = new CheckListNotifier({
	        content: this.loc(messageKey)
	      });
	      notifier.subscribeOnce('complete', baseEvent => {
	        const timerHasEnded = baseEvent.getData();
	        if (timerHasEnded) {
	          this.removeItem(itemId);
	        } else {
	          this.checkListManager.showItems([itemId], updates => this.upsertCheckLists(updates));
	        }
	        this.removeItemFromDelete(itemId);
	        this.unfreeze();
	        this.notifiers.delete(itemId);
	      });
	      this.notifiers.set(itemId, notifier);
	      notifier.showBalloonWithTimer();
	      if (this.isCurrentItemEmpty()) {
	        notifier.stopTimer();
	      }
	    },
	    handleScroll() {
	      this.isForwardMenuShown = false;
	      this.updatePanelPosition();
	    },
	    handleShow(data) {
	      this.$emit('show', data);
	    },
	    async handleClose() {
	      this.cleanNotifiers();
	      this.cancelGroupMode();
	      this.cleanCollapsedState();
	      this.executeCheckListCompletionCallbacks();
	      if (this.checkListManager.hasEmptyItemWithFiles(this.hasItemFiles) || this.checkListManager.hasEmptyParentItem()) {
	        const firstEmptyItem = this.checkListManager.getFirstEmptyItem();
	        this.focusToItem(firstEmptyItem.id, true);
	        return;
	      }
	      const getItemIdWithUploadingFiles = () => {
	        var _find;
	        return (_find = [...this.fileServiceInstances.values()].find(fileServiceInstance => fileServiceInstance.isUploading())) == null ? void 0 : _find.getEntityId();
	      };
	      const itemIdWithUploadingFiles = this.fileServiceInstances ? getItemIdWithUploadingFiles() : null;
	      if (itemIdWithUploadingFiles) {
	        this.focusToItem(itemIdWithUploadingFiles, true);
	        return;
	      }
	      this.cleanEmptyCurrentItem();
	      const checkListId = this.lastUpdatedCheckListId === 0 ? this.checkListId : this.lastUpdatedCheckListId;
	      this.saveCheckList();
	      this.$emit('close', this.deletingCheckListIds[checkListId] ? 0 : checkListId);
	    },
	    handleIsShown(isShown) {
	      if (isShown) {
	        this.subscribeToEvents();
	      } else {
	        this.unsubscribeFromEvents();
	      }
	    },
	    handleInitPopup(baseEvent) {
	      const [id, bindElement, params] = baseEvent.getCompatData();
	      if (id === 'b24-bottom-sheet' || id === 'tasks-relation-error') {
	        return;
	      }
	      if (main_core.Type.isDomNode(bindElement)) {
	        var _this$$refs$list;
	        const excludedIds = ['popup-submenu-'];
	        const shouldExclude = excludedIds.some(excludedId => id.includes(excludedId));
	        if ((_this$$refs$list = this.$refs.list) != null && _this$$refs$list.contains(bindElement) && !shouldExclude && !this.isPreview) {
	          params.targetContainer = this.$refs.list;
	        } else {
	          params.targetContainer = document.body;
	        }
	      }
	    },
	    handleShowPopup(baseEvent) {
	      const [popup] = baseEvent.getCompatData();
	      this.shownPopups.add(popup.getId());
	      this.freeze();
	    },
	    handleClosePopup(baseEvent) {
	      const [popup] = baseEvent.getCompatData();
	      this.shownPopups.delete(popup.getId());
	      this.unfreeze();
	    },
	    async handleGroupRemove(itemId) {
	      this.itemId = itemId;
	      this.freeze();
	      this.addItemToDelete(itemId);
	      this.hideItemPanel(itemId);
	      const allSelectedItems = this.checkListManager.getAllSelectedItems();
	      const nearestItem = this.checkListManager.findNearestItem(this.currentItem, false);
	      if (nearestItem) {
	        await this.updateCheckList(nearestItem.id, {
	          groupMode: {
	            active: true,
	            selected: true
	          }
	        });
	        setTimeout(() => {
	          this.showItemPanel(nearestItem.id);
	        }, 0);
	      }
	      const allSelectedItemIds = allSelectedItems.map(item => item.id);
	      this.checkListManager.hideItems(allSelectedItemIds, updates => this.upsertCheckLists(updates));
	      const messageKey = allSelectedItems.length > 1 ? 'TASKS_V2_CHECK_LIST_ITEM_REMOVE_BALLOON_CHILDREN' : 'TASKS_V2_CHECK_LIST_ITEM_REMOVE_BALLOON_CHILD';
	      const notifier = new CheckListNotifier({
	        content: this.loc(messageKey)
	      });
	      notifier.subscribeOnce('complete', baseEvent => {
	        const timerHasEnded = baseEvent.getData();
	        const idsToShow = [];
	        allSelectedItems.forEach(item => {
	          if (timerHasEnded) {
	            this.removeItem(item.id);
	          } else {
	            idsToShow.push(item.id);
	          }
	          this.removeItemFromDelete(item.id);
	        });
	        this.checkListManager.showItems(idsToShow, updates => this.upsertCheckLists(updates));
	        if (timerHasEnded) {
	          if (nearestItem && !this.deletingCheckListIds[nearestItem.id]) {
	            this.showItemPanel(nearestItem.id);
	          } else {
	            this.cancelGroupMode();
	          }
	        } else {
	          this.showItemPanel(this.currentItem.id);
	        }
	        this.unfreeze();
	        this.notifiers.delete(itemId);
	      });
	      this.notifiers.set(itemId, notifier);
	      notifier.showBalloonWithTimer();
	    },
	    handleFocus(itemId) {
	      this.isItemPanelFreeze = false;
	      this.showItemPanel(itemId);
	    },
	    handleBlur(itemId) {
	      this.itemId = itemId;
	      if (this.isCurrentItemEmpty() && this.hasItemFiles(this.currentItem)) {
	        return;
	      }
	      if (this.isItemPanelFreeze === false) {
	        this.hideItemPanel(itemId);
	      }
	    },
	    handleEmptyBlur(itemId) {
	      this.itemId = itemId;
	      if (this.currentItem.parentId === 0) {
	        this.setDefaultCheckListTitle(itemId);
	        return;
	      }
	      if (this.hasItemFiles(this.currentItem)) {
	        return;
	      }
	      if (this.isItemPanelFreeze === false) {
	        this.removeItem(itemId);
	      }
	    },
	    handleGroupMode(itemId) {
	      this.itemId = itemId;
	      this.cancelGroupMode();
	      const firstChild = this.checkListManager.getFirstChild(itemId);
	      if (!firstChild) {
	        return;
	      }
	      this.activateGroupMode(itemId);
	      this.showItemPanel(firstChild.id);
	    },
	    handleGroupModeSelect(itemId) {
	      this.itemId = itemId;
	      if (this.itemGroupModeSelected) {
	        this.showItemPanel(itemId);
	      } else {
	        this.showItemPanelOnNearestSelectedItem(itemId);
	      }
	    },
	    handlePanelAction({
	      action,
	      node
	    }) {
	      var _actionHandlers$actio;
	      const actionHandlers = {
	        [PanelAction.SetImportant]: n => this.setImportant(n),
	        [PanelAction.AttachFile]: n => this.attachFile(n),
	        [PanelAction.MoveRight]: n => this.moveGroupToRight(n),
	        [PanelAction.MoveLeft]: n => this.moveGroupToLeft(n),
	        [PanelAction.AssignAccomplice]: n => {
	          if (!this.isItemPanelFreeze) {
	            this.showParticipantDialog(n, 'accomplices');
	          }
	        },
	        [PanelAction.AssignAuditor]: n => {
	          if (!this.isItemPanelFreeze) {
	            this.showParticipantDialog(n, 'auditors');
	          }
	        },
	        [PanelAction.Forward]: n => this.forward(n),
	        [PanelAction.Delete]: n => this.delete(n),
	        [PanelAction.Cancel]: n => this.cancelGroupMode(n)
	      };
	      (_actionHandlers$actio = actionHandlers[action]) == null ? void 0 : _actionHandlers$actio.call(actionHandlers, node);
	    },
	    handleOpenCheckList(checkListId) {
	      this.cleanNotifiers();
	      this.cleanCollapsedState();
	      this.$emit('open', checkListId);
	    },
	    updateCheckList(id, fields) {
	      return this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/update`, {
	        id,
	        fields
	      });
	    },
	    updateTask(fields) {
	      return this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/update`, {
	        id: this.taskId,
	        fields
	      });
	    },
	    upsertCheckLists(items) {
	      return this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/upsertMany`, items);
	    },
	    insertCheckList(item) {
	      return this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/insert`, item);
	    },
	    insertManyCheckLists(items) {
	      return this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/insertMany`, items);
	    },
	    deleteCheckList(id) {
	      return this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/delete`, id);
	    },
	    saveCheckList() {
	      if (!this.isDemoCheckListModified()) {
	        this.removeChecklists();
	        this.checkListWasUpdated = false;
	      }
	      if (this.checkListWasUpdated && this.isEdit && this.canSaveCheckList) {
	        const deletingIds = new Set(Object.values(this.deletingCheckListIds));
	        const fullListDeletingIds = this.checkListManager.expandIdsWithChildren(deletingIds);
	        const checkListsToSave = this.checkLists.filter(checkList => {
	          return !fullListDeletingIds.has(checkList.id);
	        });
	        void tasks_v2_provider_service_checkListService.checkListService.save(this.taskId, checkListsToSave);
	      }
	      this.checkListWasUpdated = false;
	    },
	    isDemoCheckListModified() {
	      if (this.getCheckListsNumber() > 1) {
	        return true;
	      }
	      const [checkList] = this.checkLists;
	      if (!checkList) {
	        return false;
	      }
	      const demoTitle = this.loc('TASKS_V2_CHECK_LIST_TITLE_NUMBER', {
	        '#number#': 1
	      });
	      return checkList.title !== demoTitle || this.checkListManager.getChildren(checkList.id).length > 0 || this.hasItemUsers(checkList) || this.hasItemFiles(checkList);
	    },
	    removeChecklists() {
	      this.checkLists.filter(checklist => checklist.parentId === 0).forEach(item => {
	        this.removeItem(item.id);
	      });
	    },
	    async addCheckList(empty = false) {
	      const parentId = main_core.Text.getRandom();
	      const childId = main_core.Text.getRandom();
	      const items = [this.getDataForNewCheckList(parentId)];
	      if (!empty) {
	        items.push({
	          id: childId,
	          nodeId: childId,
	          parentId,
	          sortIndex: 0
	        });
	      }
	      await this.insertManyCheckLists(items);
	      void this.updateTask({
	        checklist: [...this.task.checklist, parentId, childId]
	      });
	      this.checkListWasUpdated = true;
	      return parentId;
	    },
	    async addFastCheckList() {
	      const checkListId = await this.addCheckList();
	      this.handleOpenCheckList(checkListId);
	    },
	    showForwardMenu(node) {
	      this.forwardBindElement = node;
	      this.isForwardMenuShown = true;
	    },
	    getCheckListsNumber() {
	      return this.checkLists.filter(checklist => {
	        return checklist.parentId === 0 && !this.deletingCheckListIds[checklist.id];
	      }).length;
	    },
	    getDataForNewCheckList(parentId) {
	      return {
	        id: parentId,
	        nodeId: parentId,
	        title: this.loc('TASKS_V2_CHECK_LIST_TITLE_NUMBER', {
	          '#number#': this.getCountForNewCheckList()
	        }),
	        sortIndex: this.getSortForNewCheckList()
	      };
	    },
	    getSortForNewCheckList() {
	      return this.getCheckListsNumber();
	    },
	    getCountForNewCheckList() {
	      return this.getCheckListsNumber() + 1;
	    },
	    setItemsRef(id, ref) {
	      this.itemsRefs[id] = ref;
	    },
	    getItemsRef(id) {
	      return this.itemsRefs[id];
	    },
	    focusToItem(itemId, highlight = false) {
	      void this.$nextTick(() => {
	        var _itemRef$$refs$growin;
	        const itemRef = this.getItemsRef(itemId);
	        itemRef == null ? void 0 : (_itemRef$$refs$growin = itemRef.$refs.growingTextArea) == null ? void 0 : _itemRef$$refs$growin.focusTextarea();
	        if (highlight) {
	          void new Highlighter().highlight(itemRef == null ? void 0 : itemRef.$refs.item);
	        }
	      });
	    },
	    addItem({
	      id,
	      sort
	    }) {
	      if (this.hasActiveGroupMode()) {
	        return;
	      }
	      this.itemId = id;
	      const childId = main_core.Text.getRandom();
	      const parentId = this.currentItem.parentId;
	      this.resortItemsAfterIndex(parentId, sort);
	      this.insertItem(parentId, childId, sort);
	    },
	    addItemFromBtn(checkListId) {
	      if (this.isPreview) {
	        this.handleOpenCheckList(checkListId);
	      }
	      if (this.hasActiveGroupMode()) {
	        return;
	      }
	      const childId = main_core.Text.getRandom();
	      const sortIndex = this.checkListManager.getChildren(checkListId).length;
	      this.insertItem(checkListId, childId, sortIndex);
	    },
	    insertItem(parentId, childId, sortIndex) {
	      void this.insertCheckList({
	        id: childId,
	        nodeId: childId,
	        parentId,
	        sortIndex
	      });
	      void this.updateTask({
	        checklist: [...this.task.checklist, childId]
	      });
	      this.syncParentCompletionState(childId);
	    },
	    removeItem(id, isRootCall = true) {
	      if (!this.task) {
	        return;
	      }
	      const item = this.checkListManager.getItem(id);
	      if (!item) {
	        return;
	      }
	      const children = this.checkListManager.getChildren(item.id);
	      if (item != null && item.title || item.parentId === 0) {
	        this.checkListWasUpdated = true;
	      }
	      if (children.length > 0) {
	        children.forEach(child => {
	          this.removeItem(child.id, false);
	        });
	      }
	      const checkListIds = this.task.checklist.filter(itemId => itemId !== item.id);
	      void this.updateTask({
	        containsChecklist: checkListIds.length > 0,
	        checklist: checkListIds
	      });
	      void this.deleteCheckList(item.id);
	      if (isRootCall) {
	        this.resortItemsOnLevel(item.parentId);
	      }
	      this.syncParentCompletionState(item.id, item.parentId);
	      tasks_v2_provider_service_fileService.fileService.delete(item.id, tasks_v2_provider_service_fileService.EntityTypes.CheckListItem);
	    },
	    addItemToDelete(itemId) {
	      this.addCheckListItemToDeleting(itemId);
	    },
	    removeItemFromDelete(itemId) {
	      this.removeCheckListItemFromDeleting(itemId);
	      if (this.checkListWasUpdated === true && this.isEdit && main_core.Type.isNumber(itemId)) {
	        void tasks_v2_provider_service_checkListService.checkListService.delete(this.taskId, itemId);
	      }
	    },
	    resortItemsAfterIndex(parentId, sortIndex) {
	      this.checkListManager.resortItemsAfterIndex(parentId, sortIndex, updates => {
	        void this.upsertCheckLists(updates);
	      });
	    },
	    resortItemsOnLevel(parentId) {
	      this.checkListManager.resortItemsOnLevel(parentId, updates => this.upsertCheckLists(updates));
	    },
	    syncParentCompletionState(itemId, parentId) {
	      this.checkListManager.syncParentCompletionState(itemId, (id, fields) => this.updateCheckList(id, fields), parentId);
	    },
	    showItemPanel(itemId) {
	      if (this.isPreview) {
	        return;
	      }
	      this.itemId = itemId;
	      this.itemPanelIsShown = true;
	      void this.updateCheckList(itemId, {
	        panelIsShown: true
	      });
	      void this.$nextTick(() => this.updatePanelPosition());
	    },
	    hideItemPanel(itemId) {
	      if (this.isPreview) {
	        return;
	      }
	      this.itemPanelIsShown = false;
	      if (this.hasActiveGroupMode() && this.checkListManager.getAllSelectedItems().length === 0) {
	        this.deactivateGroupMode();
	      }
	      const item = this.checkListManager.getItem(itemId);
	      if (item) {
	        void this.updateCheckList(itemId, {
	          panelIsShown: false
	        });
	      }
	      this.isItemPanelFreeze = false;
	    },
	    showItemPanelOnNearestSelectedItem(itemId) {
	      // eslint-disable-next-line no-lonely-if
	      const nearestSelectedItem = this.checkListManager.findNearestItem(this.currentItem, true);
	      if (nearestSelectedItem) {
	        this.showItemPanel(nearestSelectedItem.id);
	      } else {
	        this.hideItemPanel(itemId);
	      }
	    },
	    updatePanelPosition() {
	      if (this.itemPanelIsShown === false) {
	        return;
	      }
	      const itemRef = this.$refs.list.querySelector([`[data-id="${this.currentItem.id}"]`]);
	      const panelRect = main_core.Dom.getPosition(this.$refs.panel.$el);
	      const listRect = main_core.Dom.getPosition(this.$refs.list);
	      const itemRect = main_core.Dom.getRelativePosition(itemRef, this.$refs.list);
	      const isParentItem = this.currentItem.parentId === 0;
	      const paddingOffset = 18;
	      const panelWidth = panelRect.width === 0 ? 304 : panelRect.width;
	      const top = itemRect.top - 28;
	      if (isParentItem) {
	        const left = listRect.width - panelWidth - paddingOffset * 2 - 80;
	        const display = top > -30 && top < this.itemPanelTopLimit ? 'flex' : 'none';
	        this.itemPanelStyles = {
	          top: `${top}px`,
	          left: `${left}px`,
	          display
	        };
	      } else {
	        const left = listRect.width - panelWidth - paddingOffset;
	        const display = top > 40 && top < this.itemPanelTopLimit ? 'flex' : 'none';
	        this.itemPanelStyles = {
	          top: `${top}px`,
	          left: `${left}px`,
	          display
	        };
	      }
	    },
	    setImportant() {
	      if (this.itemGroupModeSelected) {
	        const updates = this.checkListManager.getAllSelectedItems().map(item => ({
	          ...item,
	          isImportant: !item.isImportant
	        }));
	        void this.upsertCheckLists(updates);
	      } else {
	        void this.updateCheckList(this.currentItem.id, {
	          isImportant: !this.currentItem.isImportant
	        });
	      }
	      this.checkListWasUpdated = true;
	    },
	    attachFile(node) {
	      var _this$fileServiceInst;
	      (_this$fileServiceInst = this.fileServiceInstances) != null ? _this$fileServiceInst : this.fileServiceInstances = new Map();
	      const fileServiceInstance = this.getCurrentFileService();
	      this.fileServiceInstances.set(this.currentItem.id, fileServiceInstance);
	      fileServiceInstance.browse({
	        bindElement: node,
	        onShowCallback: () => {
	          this.isItemPanelFreeze = true;
	        },
	        onHideCallback: () => {
	          this.isItemPanelFreeze = false;
	        }
	      });
	      fileServiceInstance.subscribe('onFileComplete', () => {
	        this.isItemPanelFreeze = fileServiceInstance.isUploading();
	        this.focusToItem(this.currentItem.id);
	        this.checkListWasUpdated = true;
	      });
	    },
	    getCurrentFileService() {
	      if (!this.currentItem) {
	        return null;
	      }
	      return tasks_v2_provider_service_fileService.fileService.get(this.currentItem.id, tasks_v2_provider_service_fileService.EntityTypes.CheckListItem, {
	        parentEntityId: this.taskId
	      });
	    },
	    hasItemFiles(item) {
	      if (!item) {
	        return false;
	      }
	      const fileServiceInstance = this.getCurrentFileService();
	      const files = item.attachments;
	      return files.length > 0 || (fileServiceInstance == null ? void 0 : fileServiceInstance.isUploading()) || (fileServiceInstance == null ? void 0 : fileServiceInstance.hasUploadingError());
	    },
	    hasItemUsers(item) {
	      return item.accomplices.length > 0 || item.auditors.length > 0;
	    },
	    isCurrentItemEmpty() {
	      if (!this.currentItem) {
	        return true;
	      }
	      return this.currentItem.title === '';
	    },
	    moveGroupToRight() {
	      if (this.itemGroupModeSelected) {
	        this.checkListManager.getAllSelectedItems().sort((a, b) => a.sortIndex - b.sortIndex).forEach(item => {
	          this.moveRight(item);
	        });
	      } else {
	        this.moveRight(this.currentItem);
	      }
	    },
	    moveRight(item) {
	      this.checkListManager.moveRight(item, updates => {
	        var _item$groupMode;
	        void this.upsertCheckLists(updates);
	        this.checkListWasUpdated = true;
	        if (!((_item$groupMode = item.groupMode) != null && _item$groupMode.active)) {
	          this.focusToItem(item.id);
	        }
	      });
	    },
	    moveGroupToLeft() {
	      if (this.itemGroupModeSelected) {
	        this.checkListManager.getAllSelectedItems().sort((a, b) => b.sortIndex - a.sortIndex).forEach(item => {
	          this.moveLeft(item);
	        });
	      } else {
	        this.moveLeft(this.currentItem);
	      }
	    },
	    moveLeft(item) {
	      this.checkListManager.moveLeft(item, updates => {
	        var _item$groupMode2;
	        void this.upsertCheckLists(updates);
	        this.checkListWasUpdated = true;
	        if (!((_item$groupMode2 = item.groupMode) != null && _item$groupMode2.active)) {
	          this.focusToItem(item.id);
	        }
	      });
	    },
	    async forward(node) {
	      if (this.hasFewParentCheckLists) {
	        this.showForwardMenu(node);
	      } else {
	        this.hideItemPanel();
	        void this.forwardToNewChecklist();
	      }
	      this.checkListWasUpdated = true;
	    },
	    async forwardToNewChecklist() {
	      const newParentId = await this.addCheckList(true);
	      if (this.itemGroupModeSelected) {
	        void this.forwardGroupItemsToChecklist(newParentId);
	      } else {
	        this.forwardToChecklist(newParentId);
	      }
	    },
	    forwardToChecklist(checkListId) {
	      const finalSortIndex = this.checkListManager.getChildren(checkListId).length;
	      void this.updateCheckList(this.currentItem.id, {
	        parentId: checkListId,
	        sortIndex: finalSortIndex
	      });
	      this.resortItemsOnLevel(checkListId);
	      this.resortItemsOnLevel(this.currentItem.parentId);
	    },
	    async forwardGroupItemsToChecklist(checkListId) {
	      const finalSortIndex = this.checkListManager.getChildren(checkListId).length;
	      const checkListIdsFromWhichWereForwarded = new Set();
	      const allSelectedItems = this.checkListManager.getAllSelectedItems();
	      const nearestItem = this.checkListManager.findNearestItem(this.currentItem, false, allSelectedItems);
	      if (nearestItem) {
	        this.showItemPanel(nearestItem.id);
	      } else {
	        this.cancelGroupMode();
	      }
	      const allSelectedWithChildren = this.checkListManager.getAllSelectedItemsWithChildren();
	      const selectedItemsIds = new Set(allSelectedItems.map(item => item.id));
	      const updates = [];
	      allSelectedItems.forEach(item => {
	        const shouldUpdateParentId = !selectedItemsIds.has(item.parentId);
	        checkListIdsFromWhichWereForwarded.add(item.parentId);
	        updates.push({
	          ...item,
	          parentId: shouldUpdateParentId ? checkListId : item.parentId,
	          groupMode: {
	            active: false,
	            selected: false
	          },
	          sortIndex: shouldUpdateParentId ? finalSortIndex : item.sortIndex
	        });
	      });
	      allSelectedWithChildren.forEach(item => {
	        if (!selectedItemsIds.has(item.id)) {
	          updates.push({
	            ...item,
	            groupMode: {
	              active: false,
	              selected: false
	            }
	          });
	        }
	      });
	      await this.upsertCheckLists(updates);
	      if (nearestItem) {
	        void this.updateCheckList(nearestItem.id, {
	          groupMode: {
	            active: true,
	            selected: true
	          }
	        });
	      }
	      this.resortItemsOnLevel(checkListId);
	      checkListIdsFromWhichWereForwarded.forEach(id => {
	        this.resortItemsOnLevel(id);
	      });
	    },
	    delete() {
	      if (this.itemGroupModeSelected) {
	        void this.handleGroupRemove(this.currentItem.id);
	      } else {
	        this.hideItemPanel();
	        this.handleRemove(this.currentItem.id);
	      }
	    },
	    cancelGroupMode() {
	      this.deactivateGroupMode();
	      this.hideItemPanel();
	    },
	    cleanCollapsedState() {
	      const updates = this.parentCheckLists.map(item => ({
	        ...item,
	        localCollapsedState: null
	      }));
	      void this.upsertCheckLists(updates);
	    },
	    cleanEmptyCurrentItem() {
	      if (this.currentItem && this.isCurrentItemEmpty()) {
	        this.removeItem(this.currentItem.id);
	      }
	    },
	    showParticipantDialog(targetNode, type) {
	      var _this$selector;
	      this.isItemPanelFreeze = true;
	      const preselected = this.currentItem[type].map(user => ['user', user.id]);
	      (_this$selector = this.selector) != null ? _this$selector : this.selector = new tasks_v2_lib_entitySelectorDialog.EntitySelectorDialog({
	        ...tasks_v2_component_elements_participantList.participantMeta.dialogOptions(this.taskId, 'check-list'),
	        preselectedItems: preselected,
	        popupOptions: {
	          events: {
	            onShow: baseEvent => {
	              const popup = baseEvent.getTarget();
	              const popupWidth = popup.getPopupContainer().offsetWidth;
	              const targetNodeWidth = 10;
	              const offsetLeft = targetNodeWidth - popupWidth / 2;
	              const angleShift = main_popup.Popup.getOption('angleLeftOffset') - main_popup.Popup.getOption('angleMinTop');
	              popup.setAngle({
	                offset: popupWidth / 2 - angleShift
	              });
	              popup.setOffset({
	                offsetLeft: offsetLeft + main_popup.Popup.getOption('angleLeftOffset')
	              });
	            },
	            onClose: () => {
	              const users = this.selector.getSelectedItems().map(item => ({
	                id: item.getId(),
	                name: item.getTitle(),
	                image: item.getAvatar(),
	                type: item.getEntityType()
	              }));
	              this.saveParticipants(this.selector.params.itemId, this.selector.params.type, users);
	            }
	          }
	        },
	        events: {
	          onHide: () => {
	            this.isItemPanelFreeze = false;
	            if (!this.itemGroupModeSelected && this.currentItem.id === this.selector.params.itemId) {
	              this.focusToItem(this.selector.params.itemId);
	            }
	            this.updatePanelPosition();
	          }
	        }
	      });
	      this.selector.selectItemsByIds(preselected);
	      this.selector.params = {
	        itemId: this.currentItem.id,
	        type
	      };
	      this.selector.showTo(targetNode);
	    },
	    saveParticipants(id, type, users) {
	      if (this.itemGroupModeSelected) {
	        const updates = this.checkListManager.getAllSelectedItems().map(item => ({
	          ...item,
	          [type]: users
	        }));
	        void this.upsertCheckLists(updates);
	      } else {
	        void this.updateCheckList(id, {
	          [type]: users
	        });
	      }
	      const ids = users.map(user => user.id);
	      const fields = {
	        [`${type}Ids`]: ids
	      };
	      void this.updateTask(fields);
	      this.checkListWasUpdated = true;
	    },
	    activateGroupMode(parentItemId) {
	      this.itemId = parentItemId;
	      const updates = this.checkListManager.getAllChildren(parentItemId).map((item, index) => ({
	        ...item,
	        groupMode: {
	          active: true,
	          selected: index === 0
	        }
	      }));
	      updates.push({
	        ...this.currentItem,
	        groupMode: {
	          active: true,
	          selected: false
	        }
	      });
	      void this.upsertCheckLists(updates);
	    },
	    deactivateGroupMode() {
	      const updates = this.checkListManager.getAllGroupModeItems().map(item => ({
	        ...item,
	        groupMode: {
	          active: false,
	          selected: false
	        }
	      }));
	      void this.upsertCheckLists(updates);
	    },
	    hasActiveGroupMode() {
	      return this.checkListManager.getAllGroupModeItems().length > 0;
	    },
	    freeze() {
	      var _this$$refs$childComp, _this$$refs$childComp2, _this$$refs$childComp3;
	      (_this$$refs$childComp = this.$refs.childComponent) == null ? void 0 : (_this$$refs$childComp2 = _this$$refs$childComp.$refs) == null ? void 0 : (_this$$refs$childComp3 = _this$$refs$childComp2.childComponent) == null ? void 0 : _this$$refs$childComp3.freeze();
	    },
	    unfreeze() {
	      if (this.shownPopups.size === 0 && Object.keys(this.deletingCheckListIds).length === 0) {
	        var _this$$refs$childComp4, _this$$refs$childComp5, _this$$refs$childComp6;
	        (_this$$refs$childComp4 = this.$refs.childComponent) == null ? void 0 : (_this$$refs$childComp5 = _this$$refs$childComp4.$refs) == null ? void 0 : (_this$$refs$childComp6 = _this$$refs$childComp5.childComponent) == null ? void 0 : _this$$refs$childComp6.unfreeze();
	      }
	    },
	    setDefaultCheckListTitle(itemId) {
	      void this.updateCheckList(itemId, {
	        title: this.loc('TASKS_V2_CHECK_LIST_TITLE_NUMBER', {
	          '#number#': this.getCheckListsNumber()
	        })
	      });
	    },
	    cleanNotifiers() {
	      this.notifiers.forEach(notifier => notifier.stopTimer());
	      this.notifiers.clear();
	    }
	  },
	  template: `
		<component
			v-if="componentShown"
			ref="childComponent"
			:is="componentName"
			:taskId="taskId"
			:isShown="isShown"
			:getBindElement="getBindElement"
			:getTargetContainer="getTargetContainer"
			:isEmpty="emptyList"
			@show="handleShow"
			@close="handleClose"
			@isShown="handleIsShown"
			@addFastCheckList="addFastCheckList"
			@resize="$emit('resize')"
		>
			<template v-slot:default="{ handleShow, handleClose }">
				<div ref="wrapper" class="tasks-check-list-wrapper" :class="contextClass">
					<div v-if="!isPreview" class="tasks-check-list-close-icon" :class="contextClass">
						<BIcon :name="Outline.CROSS_L" @click="handleClose"/>
					</div>
					<div ref="list" data-list class="tasks-check-list-content" :class="contextClass">
						<CheckListWidget
							v-show="!stub"
							:taskId="taskId"
							:checkListId="checkListId"
							:isPreview="isPreview"
							@update="handleUpdate"
							@show="handleShow"
							@addItem="addItem"
							@addItemFromBtn="addItemFromBtn"
							@removeItem="handleRemove"
							@focus="handleFocus"
							@blur="handleBlur"
							@emptyBlur="handleEmptyBlur"
							@startGroupMode="handleGroupMode"
							@toggleGroupModeSelected="handleGroupModeSelect"
							@openCheckList="handleOpenCheckList"
						/>
						<CheckListStub v-if="stub && !isPreview" @click="addCheckList" />
					</div>
					<div v-show="!stub && !isPreview" class="tasks-check-list-footer" :class="contextClass">
						<UiButton
							:text="loc('TASKS_V2_CHECK_LIST_NEW_BTN')"
							:size="ButtonSize.MEDIUM"
							:leftIcon="ButtonIcon.ADD"
							:style="AirButtonStyle.PLAIN_NO_ACCENT"
							@click="addCheckList"
						/>
						<UiButton
							:text="loc('TASKS_V2_CHECK_LIST_READY_BTN')"
							:size="ButtonSize.MEDIUM"
							@click="handleClose"
						/>
					</div>
					<CheckListItemPanel
						v-if="itemPanelIsShown && !isPreview"
						ref="panel"
						:taskId="taskId"
						:currentItem="currentItem"
						:style="itemPanelStyles"
						@action="handlePanelAction"
					/>
					<BMenu
						v-if="isForwardMenuShown"
						:options="forwardMenuOptions"
						@close="isForwardMenuShown = false"
					/>
				</div>
			</template>
		</component>
	`
	};

	// @vue/component
	const CheckListChip = {
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
	  emits: ['showCheckList'],
	  setup() {
	    return {
	      Outline: ui_iconSet_api_core.Outline,
	      checkListMeta
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    checkLists() {
	      return this.$store.getters[`${tasks_v2_const.Model.CheckList}/getByIds`](this.task.checklist);
	    },
	    isUploading() {
	      var _this$task$checklist;
	      return (_this$task$checklist = this.task.checklist) == null ? void 0 : _this$task$checklist.some(itemId => {
	        return tasks_v2_provider_service_fileService.fileService.get(itemId, tasks_v2_provider_service_fileService.EntityTypes.CheckListItem, {
	          parentEntityId: this.taskId
	        }).isUploading();
	      });
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
	        return this.checkLists.length > 0;
	      }
	      return this.wasFilled || this.checkLists.length > 0;
	    },
	    wasFilled() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/wasFieldFilled`](this.taskId, checkListMeta.id);
	    },
	    checkListItemCount() {
	      return this.checkLists.filter(checkList => checkList.parentId !== 0).length;
	    },
	    text() {
	      if (this.isAutonomous && this.checkListItemCount > 0) {
	        const completedCount = this.getCompletedCount();
	        return this.loc('TASKS_V2_CHECK_LIST_COUNT_TITLE', {
	          '#count#': completedCount,
	          '#total#': this.checkListItemCount
	        });
	      }
	      return this.loc('TASKS_V2_CHECK_LIST_CHIP_TITLE');
	    },
	    icon() {
	      if (this.isUploading && !this.wasFilled) {
	        return ui_iconSet_api_core.Animated.LOADER_WAIT;
	      }
	      return ui_iconSet_api_core.Outline.CHECK_LIST;
	    }
	  },
	  mounted() {
	    this.$bitrix.eventEmitter.subscribe(tasks_v2_const.EventName.CloseCheckList, this.handleFieldClose);
	  },
	  beforeUnmount() {
	    this.$bitrix.eventEmitter.unsubscribe(tasks_v2_const.EventName.CloseCheckList, this.handleFieldClose);
	  },
	  methods: {
	    handleClick() {
	      if (this.isAutonomous) {
	        void this.showCheckList();
	      } else {
	        // eslint-disable-next-line no-lonely-if
	        if (this.isSelected) {
	          this.highlightField();
	        } else {
	          void this.showCheckList();
	        }
	      }
	    },
	    async showCheckList() {
	      if (!this.isSelected) {
	        await this.buildEmptyCheckList();
	      }
	      this.$emit('showCheckList');
	    },
	    async buildEmptyCheckList() {
	      const parentId = main_core.Text.getRandom();
	      const childId = main_core.Text.getRandom();
	      await this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/insertMany`, [{
	        id: parentId,
	        nodeId: parentId,
	        title: this.loc('TASKS_V2_CHECK_LIST_TITLE_NUMBER', {
	          '#number#': 1
	        })
	      }, {
	        id: childId,
	        nodeId: childId,
	        parentId
	      }]);
	      await this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/update`, {
	        id: this.taskId,
	        fields: {
	          checklist: [parentId, childId]
	        }
	      });
	    },
	    highlightField() {
	      void tasks_v2_lib_fieldHighlighter.fieldHighlighter.setContainer(this.$root.$el).highlight(checkListMeta.id);
	    },
	    getCompletedCount() {
	      return this.checkLists.filter(checklist => {
	        return checklist.isComplete && checklist.parentId !== 0;
	      }).length;
	    },
	    handleFieldClose() {
	      if (this.isAutonomous) {
	        this.$el.focus();
	      }
	    }
	  },
	  template: `
		<Chip
			:design="design"
			:icon="icon"
			:text="text"
			:data-task-id="taskId"
			:data-task-chip-id="checkListMeta.id"
			@click="handleClick"
		/>
	`
	};

	exports.CheckList = CheckList;
	exports.CheckListChip = CheckListChip;
	exports.CheckListList = CheckListList;
	exports.checkListMeta = checkListMeta;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX.Main,BX.UI.EntitySelector,BX,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Lib,BX.Vue3.Components,BX.UI.Vue3.Components,BX.Tasks.V2.Component.Elements,BX.UI.Vue3.Components,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component.Elements,BX.Vue3.Vuex,BX.Tasks.V2.Provider.Service,BX.UI.System.Skeleton.Vue,BX.Disk.Uploader,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component.Elements,BX,BX.Vue3.Directives,BX.UI.IconSet,BX.Tasks.V2,BX.Tasks.V2.Component.Elements,BX.Event,BX,BX,BX.UI.IconSet,BX,BX.Tasks.V2.Lib,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Const));
//# sourceMappingURL=check-list.bundle.js.map
