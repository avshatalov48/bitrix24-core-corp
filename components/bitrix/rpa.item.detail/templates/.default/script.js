(function (exports,main_core,main_core_events,main_loader,ui_stageflow,rpa_manager,rpa_timeline,ui_timeline,ui_notification,ui_dialogs_messagebox) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Rpa');
	var reloadTasksTimeout = 1000;

	var ItemDetailComponent = /*#__PURE__*/function () {
	  function ItemDetailComponent(params) {
	    babelHelpers.classCallCheck(this, ItemDetailComponent);
	    this.eventIds = new Set();
	    this.completedTasks = new Set();
	    this.overlay = BX.create("div", {
	      attrs: {
	        className: "rpa-entity-overlay"
	      }
	    });
	    this.currentUrl = new main_core.Uri(location.href);

	    if (main_core.Type.isPlainObject(params)) {
	      this.id = main_core.Text.toInteger(params.id);
	      this.typeId = main_core.Text.toInteger(params.typeId);

	      if (main_core.Type.isString(params.containerId)) {
	        this.container = document.getElementById(params.containerId);
	      }

	      if (main_core.Type.isArray(params.stages)) {
	        this.stages = params.stages;
	      }

	      if (main_core.Type.isPlainObject(params.item)) {
	        this.item = params.item;
	      }

	      if (main_core.Type.isString(params.itemUpdatedPullTag)) {
	        this.itemUpdatedPullTag = params.itemUpdatedPullTag;
	      }

	      if (main_core.Type.isString(params.timelinePullTag)) {
	        this.timelinePullTag = params.timelinePullTag;
	      }

	      if (main_core.Type.isString(params.taskCountersPullTag)) {
	        this.taskCountersPullTag = params.taskCountersPullTag;
	      }

	      if (main_core.Type.isString(params.editorId)) {
	        this.editorId = params.editorId;
	        this.editor = BX.UI.EntityEditor.get(this.editorId);
	      }

	      if (params.stream instanceof ui_timeline.Timeline.Stream) {
	        this.stream = params.stream;
	      }
	    }
	  }

	  babelHelpers.createClass(ItemDetailComponent, [{
	    key: "init",
	    value: function init() {
	      this.initStageFlow();
	      this.initTabs();
	      this.initPull();
	      this.bindEvents();

	      if (this.id <= 0) {
	        this.container.appendChild(this.overlay);
	      }
	    }
	  }, {
	    key: "initStageFlow",
	    value: function initStageFlow() {
	      if (this.container && this.stages && this.item) {
	        var stageFlowContainer = this.container.querySelector('[data-role="stageflow-wrap"]');

	        if (stageFlowContainer) {
	          this.stageflowChart = new ui_stageflow.StageFlow.Chart({
	            backgroundColor: 'd3d7dc',
	            currentStage: this.item.stageId,
	            isActive: this.item.id > 0 && this.item.permissions.draggable,
	            onStageChange: this.handleStageChange.bind(this),
	            labels: {
	              finalStageName: main_core.Loc.getMessage('RPA_ITEM_DETAIL_FINAL_STAGE_NAME'),
	              finalStagePopupTitle: main_core.Loc.getMessage('RPA_ITEM_DETAIL_FINAL_STAGE_POPUP_TITLE'),
	              finalStagePopupFail: main_core.Loc.getMessage('RPA_ITEM_DETAIL_FINAL_STAGE_POPUP_FAIL'),
	              finalStageSelectorTitle: main_core.Loc.getMessage('RPA_ITEM_DETAIL_FINAL_STAGE_SELECTOR_TITLE')
	            }
	          }, this.stages);
	          stageFlowContainer.appendChild(this.stageflowChart.render());
	        }
	      }
	    }
	  }, {
	    key: "initTabs",
	    value: function initTabs() {
	      var _this = this;

	      if (this.container) {
	        var tabMenu = this.container.querySelector('[data-role="tab-menu"]');

	        if (tabMenu) {
	          var tabs = tabMenu.querySelectorAll('.rpa-item-detail-tabs-item');

	          if (tabs) {
	            tabs.forEach(function (tab) {
	              main_core.Event.bind(tab, 'click', function () {
	                _this.handleTabClick(tab);
	              });
	            });
	          }
	        }
	      }
	    }
	  }, {
	    key: "initPull",
	    value: function initPull() {
	      var _this2 = this;

	      main_core.Event.ready(function () {
	        var Pull = BX.PULL;

	        if (!Pull) {
	          console.error('pull is not initialized');
	          return;
	        }

	        if (_this2.itemUpdatedPullTag) {
	          Pull.subscribe({
	            moduleId: 'rpa',
	            command: _this2.itemUpdatedPullTag,
	            callback: _this2.handlePullItemUpdated.bind(_this2)
	          });
	          Pull.extendWatch(_this2.itemUpdatedPullTag);
	        }

	        if (_this2.timelinePullTag) {
	          Pull.subscribe({
	            moduleId: 'rpa',
	            command: _this2.timelinePullTag,
	            callback: _this2.handlePullTimelineEvent.bind(_this2)
	          });
	          Pull.extendWatch(_this2.timelinePullTag);
	        }

	        if (_this2.taskCountersPullTag) {
	          Pull.subscribe({
	            moduleId: 'rpa',
	            command: _this2.taskCountersPullTag,
	            callback: _this2.handlePullTasksCounters.bind(_this2)
	          });
	          Pull.extendWatch(_this2.taskCountersPullTag);
	        }
	      });
	    }
	  }, {
	    key: "handlePullItemUpdated",
	    value: function handlePullItemUpdated(params) {
	      if (main_core.Type.isString(params.eventId) && params.eventId.length > 0 && this.eventIds.has(params.eventId)) {
	        return;
	      }

	      if (main_core.Type.isPlainObject(params.item)) {
	        if (params.item.stageId !== this.item.stageId) {
	          this.item.stageId = params.item.stageId;
	          this.stageflowChart.setCurrentStageId(this.item.stageId);
	          this.reloadTasks();
	        }
	      }

	      if (main_core.Type.isArray(params.itemChangedUserFieldNames) && params.itemChangedUserFieldNames.length) {
	        this.handleItemExternalUpdate();
	      }
	    }
	  }, {
	    key: "handleItemExternalUpdate",
	    value: function handleItemExternalUpdate() {
	      var slider = BX.getClass('BX.SidePanel.Instance');

	      if (!this.editor || !slider) {
	        return;
	      }

	      var thisSlider = slider.getSliderByWindow(window); //TODO: reload only editor data & layout :)

	      if (this.editor.getMode() === BX.UI.EntityEditorMode.edit) {
	        ui_dialogs_messagebox.MessageBox.show({
	          message: main_core.Loc.getMessage('RPA_ITEM_DETAIL_ITEM_EXTERNAL_UPDATE_NOTIFY'),
	          modal: true,
	          buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	          onOk: function onOk(messageBox) {
	            thisSlider.reload();
	            messageBox.close();
	          }
	        });
	      } else {
	        thisSlider.reload();
	      }
	    }
	  }, {
	    key: "handleTabClick",
	    value: function handleTabClick(tab) {
	      if (!tab.classList.contains('rpa-item-detail-tabs-item-current')) {
	        var tabs = this.container.querySelectorAll('.rpa-item-detail-tabs-item');

	        if (tabs) {
	          tabs.forEach(function (tab) {
	            tab.classList.remove('rpa-item-detail-tabs-item-current');
	          });
	        }

	        tab.classList.add('rpa-item-detail-tabs-item-current');
	        var tabId = tab.dataset.tabId;
	        var contents = this.container.querySelectorAll('.rpa-item-detail-tab-content');
	        contents.forEach(function (content) {
	          if (tabId && content.dataset.tabContent && content.dataset.tabContent === tabId) {
	            content.classList.remove('rpa-item-detail-tab-content-hidden');
	          } else {
	            content.classList.add('rpa-item-detail-tab-content-hidden');
	          }
	        });
	      }
	    }
	  }, {
	    key: "handleStageChange",
	    value: function handleStageChange(stage) {
	      var _this3 = this;

	      if (this.isProgress()) {
	        return;
	      }

	      this.startProgress();
	      this.progress = false;
	      var eventId = main_core.Text.getRandom();
	      this.eventIds.add(eventId);
	      main_core.ajax.runAction('rpa.item.update', {
	        analyticsLabel: 'rpaItemDetailUpdateStage',
	        data: {
	          id: this.item.id,
	          typeId: this.item.typeId,
	          fields: {
	            stageId: stage.getId()
	          },
	          eventId: eventId
	        }
	      }).then(function (response) {
	        _this3.stopProgress();

	        _this3.item = response.data.item;

	        _this3.stageflowChart.setCurrentStageId(response.data.item.stageId);

	        _this3.stageflowChart.render();
	      }).catch(function (response) {
	        _this3.stopProgress();

	        var isShowTasks = false;
	        response.errors.forEach(function (error) {
	          if (error.code && error.code === 'RPA_ITEM_USER_HAS_TASKS') {
	            isShowTasks = true;
	          } else {
	            BX.UI.Notification.Center.notify({
	              content: error.message
	            });
	          }
	        });

	        if (isShowTasks) {
	          rpa_manager.Manager.Instance.openTasks(_this3.item.typeId, _this3.item.id);
	        }
	      });
	    }
	  }, {
	    key: "getLoader",
	    value: function getLoader() {
	      if (!this.loader) {
	        this.loader = new main_loader.Loader({
	          size: 200
	        });
	      }

	      return this.loader;
	    }
	  }, {
	    key: "startProgress",
	    value: function startProgress() {
	      this.progress = true;

	      if (!this.getLoader().isShown()) {
	        this.getLoader().show(this.container);
	      }
	    }
	  }, {
	    key: "stopProgress",
	    value: function stopProgress() {
	      this.progress = false;

	      if (this.getLoader().isShown()) {
	        this.getLoader().hide();
	      }
	    }
	  }, {
	    key: "isProgress",
	    value: function isProgress() {
	      return this.progress === true;
	    }
	  }, {
	    key: "showErrors",
	    value: function showErrors(errors) {}
	  }, {
	    key: "handlePullTimelineEvent",
	    value: function handlePullTimelineEvent(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        return;
	      }

	      if (main_core.Type.isString(params.eventId) && params.eventId.length > 0 && this.eventIds.has(params.eventId)) {
	        return;
	      }

	      if (params.command === 'add') {
	        this.handlePullTimelineAdd(params);
	      } else if (params.command === 'update') {
	        this.handlePullTimelineUpdate(params);
	      } else if (params.command === 'pin') {
	        this.handlePullTimelinePin(params);
	      } else if (params.command === 'delete') {
	        this.handlePullTimelineDelete(params);
	      }
	    }
	  }, {
	    key: "handlePullTimelineAdd",
	    value: function handlePullTimelineAdd(params) {
	      var timeline = params.timeline;

	      if (!timeline) {
	        timeline = params.comment;
	      }

	      if (main_core.Type.isPlainObject(timeline)) {
	        this.stream.addUsers(timeline.users);
	        var item = this.stream.createItem(timeline);

	        if (item instanceof rpa_timeline.Timeline.TaskComplete) {
	          if (this.completedTasks.has(item.data.task.ID)) {
	            return;
	          }

	          this.reloadTasks();
	        }

	        if (item) {
	          this.stream.insertItem(item);
	        }
	      }
	    }
	  }, {
	    key: "handlePullTimelinePin",
	    value: function handlePullTimelinePin(params) {
	      if (main_core.Type.isPlainObject(params.timeline)) {
	        var item = this.stream.getItem(params.timeline.id);
	        item.isFixed = params.timeline.isFixed;
	        item.renderPin();

	        if (item.isFixed) {
	          this.stream.pinItem(item);
	        } else {
	          this.stream.unPinItem(item);
	        }
	      }
	    }
	  }, {
	    key: "handlePullTimelineDelete",
	    value: function handlePullTimelineDelete(params) {
	      if (params && params.timeline && params.timeline.id > 0) {
	        var item = this.stream.getItem(params.timeline.id);

	        if (item) {
	          this.stream.deleteItem(item);
	        }
	      }
	    }
	  }, {
	    key: "handlePullTimelineUpdate",
	    value: function handlePullTimelineUpdate(params) {
	      if (params && params.timeline && params.timeline.id > 0) {
	        var item = this.stream.getItem(params.timeline.id);

	        if (item) {
	          item.update(params.timeline);
	        }

	        item = this.stream.getPinnedItem(params.timeline.id);

	        if (item) {
	          item.update(params.timeline);
	        }
	      }
	    }
	  }, {
	    key: "handlePullTasksCounters",
	    value: function handlePullTasksCounters(params) {
	      if (params.typeId === this.typeId && params.itemId === this.id) {
	        this.reloadTasks();
	      }
	    }
	  }, {
	    key: "reloadTasks",
	    value: function reloadTasks() {
	      var _this4 = this;

	      if (this.isProgress()) {
	        return;
	      }

	      if (this.reloadTasksTimeoutId) {
	        return;
	      }

	      this.reloadTasksTimeoutId = setTimeout(function () {
	        _this4.startProgress();

	        main_core.ajax.runAction('rpa.item.getTasks', {
	          analyticsLabel: 'rpaItemTimelineGetTasks',
	          data: {
	            typeId: _this4.typeId,
	            id: _this4.id
	          }
	        }).then(function (response) {
	          _this4.stopProgress();

	          _this4.reloadTasksTimeoutId = null;

	          if (main_core.Type.isArray(response.data.tasks)) {
	            _this4.stream.updateTasks(response.data.tasks);
	          }
	        }).catch(function () {
	          _this4.reloadTasksTimeoutId = null;

	          _this4.stopProgress();
	        });
	      }, reloadTasksTimeout);
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this5 = this;

	      main_core.Event.ready(function () {
	        main_core_events.EventEmitter.subscribe('BX.UI.EntityEditor:onSave', function (event) {
	          if (main_core.Type.isArray(event.getData())) {
	            var editor = event.getData()[0];

	            if (editor._ajaxForm && main_core.Type.isFunction(editor._ajaxForm.addUrlParams)) {
	              var eventId = main_core.Text.getRandom();

	              _this5.eventIds.add(eventId);

	              editor._ajaxForm.addUrlParams({
	                eventId: eventId
	              });
	            }
	          }
	        });

	        if (!_this5.item.id) {
	          main_core_events.EventEmitter.subscribe('BX.UI.EntityEditorAjax:onSubmit', function (event) {
	            var data = event.getData();

	            if (main_core.Type.isArray(data)) {
	              var response = data[1];

	              if (response && response.data && response.data.item) {
	                var url = rpa_manager.Manager.Instance.getItemDetailUrl(_this5.typeId, response.data.item.id);

	                if (url) {
	                  var _this5$currentUrl$get = _this5.currentUrl.getQueryParams(),
	                      iframe = _this5$currentUrl$get.IFRAME,
	                      iframeType = _this5$currentUrl$get.IFRAME_TYPE;

	                  var isSlider = iframe === 'Y' && iframeType === 'SIDE_SLIDER';

	                  if (isSlider) {
	                    url.setQueryParams({
	                      IFRAME: 'Y',
	                      IFRAME_TYPE: 'SIDE_SLIDER'
	                    });
	                  }

	                  location.href = url.toString();
	                }
	              }
	            }
	          });
	        }

	        _this5.stream.subscribe('onScrollToTheBottom', _this5.loadItems.bind(_this5));

	        _this5.stream.subscribe('onPinClick', _this5.handleItemPinClick.bind(_this5));

	        main_core_events.EventEmitter.subscribe('BX.UI.Timeline.CommentEditor:onLoadVisualEditor', function (event) {
	          return new Promise(function (resolve, reject) {
	            main_core.ajax.runAction('rpa.comment.getVisualEditor', {
	              analyticsLabel: 'rpaTimelineCommentLoadVisualEditor',
	              data: {
	                name: event.getData().name,
	                commentId: event.getData().commentId
	              }
	            }).then(function (response) {
	              event.getData().html = response.data.html;
	              resolve();
	            }).catch(function () {
	              reject();
	            });
	          });
	        });
	        main_core_events.EventEmitter.subscribe('BX.UI.Timeline.CommentEditor:onSave', function (event) {
	          return new Promise(function (resolve, reject) {
	            var eventId = main_core.Text.getRandom();

	            _this5.eventIds.add(eventId);

	            var analyticsLabel = 'rpaTimelineCommentAdd';
	            var action = 'rpa.comment.add';
	            var data = {
	              typeId: _this5.typeId,
	              itemId: _this5.id,
	              fields: {
	                description: event.getData().description,
	                files: event.getData().files
	              },
	              eventId: eventId
	            };
	            var commentId = main_core.Text.toInteger(event.getData().commentId);

	            if (commentId > 0) {
	              action = 'rpa.comment.update';
	              data.id = commentId;
	              analyticsLabel = 'rpaTimelineCommentUpdate';
	            }

	            main_core.ajax.runAction(action, {
	              analyticsLabel: analyticsLabel,
	              data: data
	            }).then(function (response) {
	              event.getData().comment = response.data.comment;

	              if (commentId <= 0) {
	                if (response.data && response.data.comment) {
	                  _this5.stream.addUsers(response.data.comment.users);

	                  var item = _this5.stream.createItem(response.data.comment);

	                  if (item) {
	                    _this5.stream.insertItem(item);
	                  }
	                }
	              } else {
	                var comment = _this5.stream.createItem(response.data.comment);

	                var commonComment = _this5.stream.getItem(comment.getId());

	                if (commonComment) {
	                  commonComment.update(response.data.comment);
	                }

	                var pinnedComment = _this5.stream.getPinnedItem(comment.getId());

	                if (pinnedComment) {
	                  pinnedComment.update(response.data.comment);
	                }
	              }

	              resolve();
	            }).catch(function (response) {
	              event.getData().message = response.errors.map(function (_ref) {
	                var message = _ref.message;
	                return message;
	              }).join("; ");
	              reject();
	            });
	          });
	        });
	        main_core_events.EventEmitter.subscribe('BX.UI.Timeline.Comment:onLoadContent', function (event) {
	          return new Promise(function (resolve, reject) {
	            var commentId = main_core.Text.toInteger(event.getData().commentId);

	            if (!commentId) {
	              reject();
	              return;
	            }

	            main_core.ajax.runAction('rpa.comment.get', {
	              analyticsLabel: 'rpaTimelineCommentGetContent',
	              data: {
	                id: commentId
	              }
	            }).then(function (response) {
	              if (response.data && response.data.comment) {
	                event.getData().comment = response.data.comment;
	                resolve();
	              } else {
	                reject();
	              }
	            }).catch(function (response) {
	              event.getData().message = response.errors.map(function (_ref2) {
	                var message = _ref2.message;
	                return message;
	              }).join("; ");
	              reject();
	            });
	          });
	        });
	        main_core_events.EventEmitter.subscribe('BX.UI.Timeline.Comment:onLoadFilesContent', function (event) {
	          return new Promise(function (resolve, reject) {
	            var commentId = main_core.Text.toInteger(event.getData().commentId);

	            if (!commentId) {
	              reject();
	              return;
	            }

	            main_core.ajax.runAction('rpa.comment.getFilesContent', {
	              analyticsLabel: 'rpaTimelineCommentGetFilesContent',
	              data: {
	                id: commentId
	              }
	            }).then(function (response) {
	              if (response.data && main_core.Type.isString(response.data.html) && response.data.html.length > 0) {
	                event.getData().html = response.data.html;
	                resolve();
	              } else {
	                reject();
	              }
	            }).catch(function (response) {
	              event.getData().message = response.errors.map(function (_ref3) {
	                var message = _ref3.message;
	                return message;
	              }).join("; ");
	              reject();
	            });
	          });
	        });
	        main_core_events.EventEmitter.subscribe('BX.UI.Timeline.Comment:onDelete', function (event) {
	          return new Promise(function (resolve, reject) {
	            var commentId = main_core.Text.toInteger(event.getData().commentId);

	            if (!commentId) {
	              reject();
	              return;
	            }

	            var eventId = main_core.Text.getRandom();

	            _this5.eventIds.add(eventId);

	            main_core.ajax.runAction('rpa.comment.delete', {
	              analyticsLabel: 'rpaTimelineCommentDelete',
	              data: {
	                id: commentId,
	                eventId: eventId
	              }
	            }).then(function () {
	              resolve();
	            }).catch(function (response) {
	              event.getData().message = response.errors.map(function (_ref4) {
	                var message = _ref4.message;
	                return message;
	              }).join("; ");
	              reject();
	            });
	          });
	        });
	        main_core_events.EventEmitter.subscribe('BX.Rpa.Timeline.Task:onBeforeCompleteTask', function (event) {
	          _this5.completedTasks.add(event.getData().taskId);
	        });
	      });
	    }
	  }, {
	    key: "loadItems",
	    value: function loadItems() {
	      var _this6 = this;

	      if (this.isProgress()) {
	        return;
	      }

	      this.startProgress();
	      this.stream.currentPage++;
	      main_core.ajax.runAction('rpa.timeline.listForItem', {
	        analyticsLabel: 'rpaItemDetailTimelineLoadOnScroll',
	        data: {
	          typeId: this.typeId,
	          itemId: this.id
	        },
	        navigation: {
	          page: this.stream.currentPage,
	          size: this.stream.pageSize
	        }
	      }).then(function (response) {
	        _this6.stopProgress();

	        var items = response.data.timeline;

	        if (main_core.Type.isArray(items)) {
	          if (items.length <= 0) {
	            _this6.stream.disableLoadOnScroll();
	          } else {
	            items.forEach(function (itemData) {
	              var item = _this6.stream.createItem(itemData);

	              if (item) {
	                _this6.stream.addItem(item);
	              }
	            });

	            _this6.stream.renderItems();
	          }
	        } else {
	          _this6.stream.disableLoadOnScroll();
	        }
	      }).catch(function () {
	        _this6.stopProgress();

	        _this6.stream.disableLoadOnScroll();
	      });
	    }
	  }, {
	    key: "handleItemPinClick",
	    value: function handleItemPinClick(event) {
	      var item = event.getData().item;

	      if (item instanceof ui_timeline.Timeline.Item) {
	        main_core.ajax.runAction('rpa.timeline.updateIsFixed', {
	          analyticsLabel: 'rpaTimelinePinClick',
	          data: {
	            id: item.getId(),
	            isFixed: item.isFixed ? 'y' : 'n',
	            eventId: this.registerRandomEventId()
	          }
	        });
	      }
	    }
	  }, {
	    key: "registerRandomEventId",
	    value: function registerRandomEventId() {
	      var eventId = main_core.Text.getRandom();
	      this.eventIds.add(eventId);
	      return eventId;
	    }
	  }]);
	  return ItemDetailComponent;
	}();

	namespace.ItemDetailComponent = ItemDetailComponent;

}((this.window = this.window || {}),BX,BX.Event,BX,BX.UI,BX.Rpa,BX.Rpa,BX.UI,BX,BX.UI.Dialogs));
//# sourceMappingURL=script.js.map
