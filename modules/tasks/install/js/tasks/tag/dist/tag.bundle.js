/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,ui_sidepanel_layout,main_core_events,ui_notification) {
	'use strict';

	var _templateObject;
	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var TagActions = /*#__PURE__*/function () {
	  function TagActions(pathToTask, pathToUser) {
	    var _parseInt;
	    var tagId = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;
	    var tasksCount = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : null;
	    var groupId = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : null;
	    babelHelpers.classCallCheck(this, TagActions);
	    this.tagId = tagId;
	    this.tasksCount = tasksCount;
	    this.groupId = (_parseInt = parseInt(groupId, 10)) !== null && _parseInt !== void 0 ? _parseInt : 0;
	    this.tagStorage = [];
	    this.timeoutId = 0;
	    this.gridId = 'tasks_by_tag_list';
	    this.pathToTask = pathToTask;
	    this.pathToUser = pathToUser;
	    this.sidePanel = null;
	    this.listData = null;
	    this.pullTaskCommands = ['task_update', 'task_add', 'task_remove'];
	    this.pullTagCommands = ['tag_added', 'tag_changed'];
	    this.sidePanelManager = BX.SidePanel.Instance;
	    this.balloon = null;
	    this.timeoutDelete = null;
	    this.bindEvents();
	  }
	  babelHelpers.createClass(TagActions, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this = this;
	      this.pullTagCommands.forEach(function (command) {
	        BX.PULL.subscribe({
	          type: BX.PullClient.SubscriptionType.Server,
	          moduleId: 'tasks',
	          command: command,
	          callback: _this.onPullTag.bind(_this)
	        });
	      });
	      this.pullTaskCommands.forEach(function (command) {
	        BX.PULL.subscribe({
	          type: BX.PullClient.SubscriptionType.Server,
	          moduleId: 'tasks',
	          command: command,
	          callback: _this.onPullTask.bind(_this)
	        });
	      });
	      main_core_events.EventEmitter.subscribe('Grid::beforeRequest', this.onBeforeTagsGridRequest.bind(this));
	    }
	  }, {
	    key: "onPullTag",
	    value: function onPullTag(params, extra, command) {
	      if (!this.getTagsGrid()) {
	        return;
	      }
	      var userId = parseInt(main_core.Loc.getMessage('USER_ID'), 10);
	      if (this.groupId === 0 && command === 'tag_added') {
	        this.getTagsGrid().reload();
	      }
	      if (this.groupId !== 0 && params.groupId === this.groupId) {
	        if (userId !== params.userId) {
	          this.getTagsGrid().reload();
	        }
	      }
	    }
	  }, {
	    key: "onPullTask",
	    value: function onPullTask(params, extra, command) {
	      var _this2 = this;
	      if (!this.getTagsGrid()) {
	        return;
	      }
	      clearTimeout(this.timeoutId);
	      this.timeoutId = setTimeout(function () {
	        _this2.getTagsGrid().reload();
	      }, 500);
	    }
	  }, {
	    key: "getTagsGrid",
	    value: function getTagsGrid() {
	      return BX.Main.gridManager.getInstanceById('tags_list');
	    }
	  }, {
	    key: "deleteTag",
	    value: function deleteTag(tagId) {
	      var _this3 = this;
	      if (!this.getTagsGrid()) {
	        return;
	      }
	      clearTimeout(this.timeoutDelete);
	      if (this.balloon) {
	        this.balloon.close();
	      }
	      this.tagStorage.push(tagId);
	      var groupDelete = false;
	      if (this.tagStorage.length > 1) {
	        groupDelete = true;
	      }
	      var cancelRequest = false;
	      this.getTagsGrid().getRows().getById(tagId).hide();
	      this.getTagsGrid().showEmptyStub();
	      this.balloon = ui_notification.UI.Notification.Center.notify({
	        content: this.tagStorage.length > 1 ? main_core.Loc.getMessage('ALL_TAGS_SUCCESSFULLY_DELETED') : main_core.Loc.getMessage('TAG_IS_SUCCESSFULLY_DELETED'),
	        autoHideDelay: TagActions.balloonLifeTime,
	        events: {
	          onMouseEnter: function onMouseEnter() {
	            cancelRequest = true;
	            clearTimeout(_this3.timeoutDelete);
	          },
	          onMouseLeave: function onMouseLeave() {
	            cancelRequest = false;
	            _this3.timeoutDelete = setTimeout(sendRequest, TagActions.balloonLifeTime);
	          }
	        },
	        actions: [{
	          title: main_core.Loc.getMessage('TAG_CANCEL'),
	          events: {
	            click: function click(event, balloon, action) {
	              cancelRequest = true;
	              _this3.tagStorage.forEach(function (id) {
	                _this3.getTagsGrid().getRows().getById(id).show();
	              });
	              balloon.close();
	            }
	          }
	        }]
	      });
	      var sendRequest = function sendRequest() {
	        if (cancelRequest) {
	          return;
	        }
	        if (groupDelete) {
	          groupDelete = false;
	          main_core.ajax.runComponentAction('bitrix:tasks.tag.list', 'deleteTagGroup', {
	            mode: 'class',
	            data: {
	              tags: _this3.tagStorage,
	              groupId: _this3.groupId
	            }
	          }).then(function (response) {
	            if (response.status === 'success') {
	              _this3.balloon.close();
	              _this3.tagStorage = [];
	              _this3.getTagsGrid().reload();
	            }
	          });
	        } else {
	          main_core.ajax.runComponentAction('bitrix:tasks.tag.list', 'deleteTag', {
	            mode: 'class',
	            data: {
	              tagId: tagId,
	              groupId: _this3.groupId
	            }
	          }).then(function (response) {
	            if (response.status === 'success') {
	              _this3.balloon.close();
	              _this3.tagStorage = [];
	              _this3.getTagsGrid().removeRow(tagId);
	            }
	          });
	        }
	      };
	      this.timeoutDelete = setTimeout(sendRequest, TagActions.balloonLifeTime);
	    }
	  }, {
	    key: "updateTag",
	    value: function updateTag(tagId) {
	      var _this4 = this;
	      if (!this.getTagsGrid()) {
	        return;
	      }
	      if (this.balloon) {
	        this.balloon.close();
	      }
	      var editingRowsCount = this.getTagsGrid().container.querySelectorAll('div.main-grid-editor-container').length;
	      if (editingRowsCount === 1) {
	        var id = this.getTagsGrid().container.querySelector('.main-grid-row.main-grid-row-body.main-grid-row-edit').dataset.id;
	        this.getTagsGrid().getRows().getById(id).editCancel();
	      }
	      this.getTagsGrid().getRows().getById(tagId).edit();
	      var newName = '';
	      var cell = '';
	      var result = '';
	      this.getTagsGrid().container.querySelector('div.main-grid-editor-container input').addEventListener('keydown', function (event) {
	        if (event.key === 'Enter') {
	          if (_this4.balloon) {
	            _this4.balloon.close();
	          }
	          cell = _this4.getTagsGrid().getRows().getById(tagId).getCellById('NAME');
	          newName = _this4.getTagsGrid().getRows().getById(tagId).getEditorContainer(cell).firstChild.value.trim();
	          if (newName === '') {
	            _this4.balloon = ui_notification.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('TAG_EMPTY_NEW_NAME'),
	              autoHideDelay: TagActions.balloonLifeTime
	            });
	            return;
	          }
	          main_core.ajax.runComponentAction('bitrix:tasks.tag.list', 'updateTag', {
	            mode: 'class',
	            data: {
	              tagId: tagId,
	              newName: newName,
	              groupId: _this4.groupId
	            }
	          }).then(function (response) {
	            result = response.data;
	            return result;
	          }).then(function (result) {
	            if (!result.success) {
	              if (!result.error) {
	                _this4.getTagsGrid().getRows().getById(tagId).editCancel();
	                return;
	              }
	              _this4.balloon = ui_notification.UI.Notification.Center.notify({
	                content: result.error,
	                autoHideDelay: TagActions.balloonLifeTime
	              });
	              return;
	            }
	            _this4.getTagsGrid().updateRow(tagId);
	            _this4.balloon = ui_notification.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('TAG_IS_SUCCESSFULLY_UPDATED'),
	              autoHideDelay: TagActions.balloonLifeTime
	            });
	          });
	        }
	      });
	    }
	  }, {
	    key: "groupDelete",
	    value: function groupDelete() {
	      var _this5 = this;
	      if (!this.getTagsGrid()) {
	        return;
	      }
	      if (this.balloon) {
	        this.balloon.close();
	      }
	      var cancelRequest = false;
	      var tags = [];
	      var selected = this.getTagsGrid().getRows().getSelected();
	      selected.forEach(function (row) {
	        tags.push(row.getId());
	        _this5.getTagsGrid().getRows().getById(row.getId()).hide();
	      });
	      document.querySelector('div.main-grid-action-panel').className = 'main-grid-action-panel main-grid-disable';
	      this.balloon = ui_notification.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('ALL_TAGS_SUCCESSFULLY_DELETED'),
	        autoHideDelay: TagActions.balloonLifeTime,
	        events: {
	          onMouseEnter: function onMouseEnter() {
	            cancelRequest = true;
	            clearTimeout(_this5.timeoutDelete);
	          },
	          onMouseLeave: function onMouseLeave() {
	            cancelRequest = false;
	            _this5.timeoutDelete = setTimeout(sendRequest, TagActions.balloonLifeTime);
	          }
	        },
	        actions: [{
	          title: main_core.Loc.getMessage('TAG_CANCEL'),
	          events: {
	            click: function click(event, balloon, action) {
	              cancelRequest = true;
	              tags.forEach(function (id) {
	                var row = _this5.getTagsGrid().getRows().getById(id);
	                row && row.show();
	              });
	              balloon.close();
	            }
	          }
	        }]
	      });
	      var sendRequest = function sendRequest() {
	        if (cancelRequest) {
	          return;
	        }
	        main_core.ajax.runComponentAction('bitrix:tasks.tag.list', 'deleteTagGroup', {
	          mode: 'class',
	          data: {
	            tags: tags,
	            groupId: _this5.groupId
	          }
	        }).then(function (response) {
	          if (response.status === 'success') {
	            _this5.balloon.close();
	            tags.forEach(function (tagId) {
	              _this5.getTagsGrid().removeRow(tagId);
	            });
	          }
	        });
	      };
	      this.timeoutDelete = setTimeout(sendRequest, TagActions.balloonLifeTime);
	    }
	  }, {
	    key: "showTasksList",
	    value: function showTasksList() {
	      var _this6 = this;
	      this.sidePanelManager.open('tasks-tag-tasks-list-side-panel', {
	        width: 700,
	        cacheable: false,
	        contentCallback: function contentCallback() {
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['tasks.tag'],
	            title: main_core.Loc.getMessage('TASKS_BY_TAG_LIST'),
	            content: _this6.createTasksListContent.bind(_this6),
	            design: {
	              section: false
	            },
	            buttons: []
	          });
	        },
	        events: {
	          onLoad: this.onLoadTasksList.bind(this)
	        }
	      });
	    }
	  }, {
	    key: "onLoadTasksList",
	    value: function onLoadTasksList(event) {
	      var _this7 = this;
	      var slider = event.getSlider();
	      var listContainer = slider.getContainer().querySelector('.tasks-tags-tag-tasks-list');
	      this.pullTaskCommands.forEach(function (command) {
	        BX.PULL.subscribe({
	          type: BX.PullClient.SubscriptionType.Server,
	          moduleId: 'tasks',
	          command: command,
	          callback: function callback() {
	            slider.destroy();
	          }
	        });
	      });
	      main_core.Runtime.html(listContainer, this.listData.html).then(function () {
	        main_core_events.EventEmitter.subscribe('Grid::beforeRequest', _this7.onBeforeTaskGridRequest.bind(_this7));
	      });
	    }
	  }, {
	    key: "onBeforeTaskGridRequest",
	    value: function onBeforeTaskGridRequest(event) {
	      var _event$getCompatData = event.getCompatData(),
	        _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 2),
	        gridObject = _event$getCompatData2[0],
	        eventArgs = _event$getCompatData2[1];
	      eventArgs.sessid = BX.bitrix_sessid();
	      eventArgs.method = 'POST';
	      eventArgs.data = _objectSpread(_objectSpread({}, eventArgs.data), {}, {
	        gridId: this.gridId,
	        pathToTask: this.pathToTask,
	        pathToUser: this.pathToUser,
	        tagId: this.tagId,
	        tasksCount: this.tasksCount
	      });
	    }
	  }, {
	    key: "createTasksListContent",
	    value: function createTasksListContent() {
	      var _this8 = this;
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runComponentAction('bitrix:tasks.tag.list', 'getTasksByTag', {
	          mode: 'class',
	          data: {
	            gridId: _this8.gridId,
	            tagId: _this8.tagId,
	            pathToTask: _this8.pathToTask,
	            pathToUser: _this8.pathToUser,
	            tasksCount: _this8.tasksCount
	          }
	        }).then(function (response) {
	          _this8.listData = response.data;
	          resolve(_this8.renderTasksList());
	        });
	      });
	    }
	  }, {
	    key: "renderTasksList",
	    value: function renderTasksList() {
	      return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"tasks-tags-tag-tasks-list\"></div>"])));
	    }
	  }, {
	    key: "show",
	    value: function show(tagId, tasksCount) {
	      var _this9 = this;
	      return top.BX.Runtime.loadExtension('tasks.tag').then(function (exports) {
	        var ext = new exports['TagActions'](_this9.pathToTask, _this9.pathToUser, tagId, tasksCount);
	        ext.showTasksList();
	      });
	    }
	  }, {
	    key: "onLoadTagList",
	    value: function onLoadTagList() {
	      main_core_events.EventEmitter.subscribe('Grid::beforeRequest', this.onBeforeTagsGridRequest.bind(this));
	    }
	  }, {
	    key: "onBeforeTagsGridRequest",
	    value: function onBeforeTagsGridRequest(event) {
	      var _event$getCompatData3 = event.getCompatData(),
	        _event$getCompatData4 = babelHelpers.slicedToArray(_event$getCompatData3, 2),
	        gridObject = _event$getCompatData4[0],
	        eventArgs = _event$getCompatData4[1];
	      eventArgs.sessid = BX.bitrix_sessid();
	      eventArgs.method = 'POST';
	      eventArgs.data = _objectSpread(_objectSpread({}, eventArgs.data), {}, {
	        groupId: this.groupId
	      });
	    }
	  }]);
	  return TagActions;
	}();
	babelHelpers.defineProperty(TagActions, "balloonLifeTime", 3000);

	exports.TagActions = TagActions;

}((this.BX.Tasks = this.BX.Tasks || {}),BX,BX.UI.SidePanel,BX.Event,BX));
//# sourceMappingURL=tag.bundle.js.map
