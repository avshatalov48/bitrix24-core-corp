this.BX = this.BX || {};
(function (exports) {
	'use strict';

	BX.namespace('BX.Tasks');
	BX.Tasks.TagsSelector = function (options) {
	  this.tagsAreConverting = options.tagsAreConverting;
	  this.groupId = options.groupId || 0;
	  this.taskId = options.taskId || 0;
	  this.isScrumTask = options.isScrumTask === 'Y';
	  this.templateId = options.templateId || 0;
	  this.tags = options.tags;
	  this.userId = options.userId || 0;
	  this.entity = {
	    id: 'task-tag'
	  };
	  if (this.taskId) {
	    this.entity.options = {
	      taskId: this.taskId
	    };
	    this.tags = [];
	  }
	  if (this.groupId) {
	    this.entity.options.groupId = this.groupId;
	  }
	  this.bindEvents();
	};
	BX.Tasks.TagsSelector.prototype = {
	  constructor: BX.Tasks.TagsSelector,
	  show: function show() {
	    this.getSelector() && this.getSelector().show();
	  },
	  bindEvents: function bindEvents() {
	    var onPullTagChanged = function onPullTagChanged() {
	      this.dialog.hide();
	      this.dialog = null;
	    };
	    var onPullTaskProjectChanged = function onPullTaskProjectChanged(params) {
	      this.dialog = null;
	      this.groupId = 0;
	      if (params) {
	        this.groupId = params.data;
	      }
	    };
	    BX.addCustomEvent('onProjectChanged', onPullTaskProjectChanged.bind(this));
	    BX.PULL.subscribe({
	      type: BX.PullClient.SubscriptionType.Server,
	      moduleId: 'tasks',
	      command: 'tag_changed',
	      callback: onPullTagChanged.bind(this)
	    });
	  },
	  //widget on task/template detail page
	  getSelector: function getSelector() {
	    if (!this.dialog) {
	      var statusSuccess = {
	        status: false
	      };
	      var showAddButton = function showAddButton() {
	        var dialog = BX.UI.EntitySelector.Dialog.getById('tasks-selector-task-tag-widget');
	        dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-add-new').hidden = false;
	        dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-conjunction').hidden = false;
	      };
	      var hideAddButton = function hideAddButton() {
	        var dialog = BX.UI.EntitySelector.Dialog.getById('tasks-selector-task-tag-widget');
	        dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-add-new').hidden = true;
	        dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-conjunction').hidden = true;
	      };
	      var onSearch = function onSearch(event) {
	        var query = event.getData().query;
	        if (query.trim() !== '') {
	          showAddButton();
	        } else {
	          hideAddButton();
	        }
	      };
	      var showAlert = function showAlert(className, error) {
	        var dialog = BX.UI.EntitySelector.Dialog.getById('tasks-selector-task-tag-widget');
	        if (dialog.getContainer().querySelector("div.".concat(className))) {
	          return;
	        }
	        var alert = document.createElement('div');
	        alert.className = className;
	        alert.innerHTML = "\n\t\t\t\t<div class='ui-alert ui-alert-xs ui-alert-danger'  \n\t\t\t\t\t<span class='ui-alert-message'>\n\t\t\t\t\t\t".concat(error, "\n\t\t\t\t\t</span> \n\t\t\t\t</div>\n\t\t\t\t");
	        dialog.getFooterContainer().before(alert);
	      };
	      var onTagsLoad = function onTagsLoad(event) {
	        var dialog = event.getTarget();
	        var events = ['click', 'keydown'];
	        var handler = function handler(event) {
	          if (event.type === 'keydown') {
	            if (!((event.ctrlKey || event.metaKey) && event.keyCode === 13)) {
	              return;
	            }
	          }
	          var tags = dialog.getSelectedItems().map(function (item) {
	            return item.getTitle();
	          });
	          var newTag = dialog.getTagSelectorQuery();
	          if (newTag.trim() === '') {
	            return;
	          }
	          BX.ajax.runComponentAction('bitrix:tasks.tags.selector', 'updateTags', {
	            mode: 'class',
	            data: {
	              taskId: taskId,
	              tagIds: tags,
	              newTag: newTag
	            }
	          }).then(function (response) {
	            if (response.data.success) {
	              statusSuccess.status = true;
	              var item = dialog.addItem({
	                id: newTag,
	                entityId: 'task-tag',
	                title: newTag,
	                sort: 1,
	                badges: [{
	                  title: response.data.owner
	                }]
	              });
	              dialog.getTab('all').getRootNode().addItem(item);
	              item.select();
	            } else {
	              var alertClass = 'tasks-selector-tag-already-exists-alert';
	              showAlert(alertClass, response.data.error);
	              var removeAlert = function removeAlert() {
	                var notification = dialog.getContainer().querySelector("div.".concat(alertClass));
	                notification && notification.remove();
	              };
	              setTimeout(removeAlert, 2000);
	            }
	          });
	        };
	        events.forEach(function (ev) {
	          if (ev === 'click') {
	            dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-add-new').addEventListener(ev, handler);
	          } else {
	            dialog.getContainer().addEventListener(ev, handler);
	          }
	        });
	      };
	      var taskId = this.taskId;
	      var groupId = this.groupId;
	      var templateId = this.templateId;
	      if (taskId !== 0) {
	        if (this.tagsAreConverting) {
	          var message = new top.BX.UI.Dialogs.MessageBox({
	            title: BX.message('TASKS_TAG_SELECTOR_TAGS_ARE_CONVERTING_TITLE'),
	            message: BX.message('TASKS_TAG_SELECTOR_TAGS_ARE_CONVERTING_TEXT'),
	            buttons: top.BX.UI.Dialogs.MessageBoxButtons.OK,
	            okCaption: BX.message('TASKS_TAG_SELECTOR_TAGS_ARE_CONVERTING_COME_BACK_LATER'),
	            onOk: function onOk() {
	              message.close();
	            }
	          });
	          message.show();
	          return;
	        }
	        this.dialog = new BX.UI.EntitySelector.Dialog({
	          id: 'tasks-selector-task-tag-widget',
	          targetNode: BX('task-tags-link'),
	          enableSearch: true,
	          width: 350,
	          height: 400,
	          multiple: true,
	          dropdownMode: true,
	          compactView: true,
	          entities: [{
	            id: 'task-tag',
	            options: {
	              taskId: this.taskId,
	              groupId: groupId
	            }
	          }],
	          selectedItems: this.tags.map(function (tag) {
	            return {
	              id: BX.util.htmlspecialcharsback(tag),
	              entityId: 'task-tag',
	              title: BX.util.htmlspecialcharsback(tag),
	              tabs: 'all'
	            };
	          }),
	          searchOptions: {
	            allowCreateItem: false
	          },
	          footer: BX.Tasks.EntitySelector.Footer,
	          footerOptions: {
	            userId: this.userId,
	            taskId: this.taskId,
	            groupId: groupId
	          },
	          clearUnavailableItems: true,
	          events: {
	            'onLoad': function (event) {
	              event.getTarget().getFooterContainer().style.zIndex = 1;
	              onTagsLoad(event);
	            }.bind(this),
	            'Item:onSelect': function (event) {
	              this.onTagsChange(event, statusSuccess);
	            }.bind(this),
	            'onSearch': function (event) {
	              onSearch(event);
	            }.bind(this),
	            'Item:onDeselect': function (event) {
	              this.onTagsChange(event, statusSuccess);
	            }.bind(this)
	          }
	        });
	      }
	      if (templateId !== 0) {
	        this.dialog = new BX.UI.EntitySelector.Dialog({
	          id: 'tasks-widget-tag-selector-template-detail',
	          targetNode: BX('task-tags-link'),
	          enableSearch: true,
	          width: 350,
	          height: 400,
	          multiple: true,
	          dropdownMode: true,
	          compactView: true,
	          context: 'TEMPLATE_TAG',
	          entities: [{
	            id: 'template-tag',
	            options: {},
	            dynamicLoad: true,
	            dynamicSearch: true
	          }],
	          selectedItems: this.tags.map(function (tag) {
	            return {
	              id: BX.util.htmlspecialcharsback(tag),
	              entityId: 'template-tag',
	              title: BX.util.htmlspecialcharsback(tag),
	              tabs: 'all'
	            };
	          }),
	          searchOptions: {
	            allowCreateItem: true
	          },
	          events: {
	            'Search:onItemCreateAsync': function SearchOnItemCreateAsync(event) {
	              var promise = new BX.Promise();
	              var searchQuery = event.getData().searchQuery;
	              var dialog = event.getTarget();
	              setTimeout(function () {
	                var item = dialog.addItem({
	                  id: searchQuery.getQuery(),
	                  entityId: 'template-tag',
	                  title: searchQuery.getQuery(),
	                  tabs: 'all'
	                });
	                if (item) {
	                  item.select();
	                }
	                promise.fulfill();
	              }, 1000);
	              return promise;
	            },
	            'Item:onSelect': function (event) {
	              this.onTagsChange(event);
	            }.bind(this),
	            'Item:onDeselect': function (event) {
	              this.onTagsChange(event);
	            }.bind(this)
	          }
	        });
	      }
	    }
	    return this.dialog;
	  },
	  onTagsChange: function onTagsChange(event, statusSuccess) {
	    // to pop up an item
	    var selectedItem = event.getData().item;
	    selectedItem.setSort(1);
	    this.dialog.getTab('all').getRootNode().addItem(selectedItem);
	    var selectedItems = this.getSelector().getSelectedItems();
	    var tagIds = selectedItems.map(function (item) {
	      return item.getTitle();
	    });
	    var tagsString = tagIds.join(', ');
	    this.updateNode(tagsString);

	    // event goes to tasks.task.template to update tags in task's template
	    var tags = tagIds.map(function (tag) {
	      return {
	        name: tag
	      };
	    });
	    BX.onCustomEvent('onTaskTagSelect', [tags]);
	    if (typeof statusSuccess !== 'undefined' && statusSuccess.status) {
	      statusSuccess.status = false;
	      this.dialog.clearSearch();
	      this.dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-add-new').hidden = true;
	      this.dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-conjunction').hidden = true;
	      return;
	    }
	    if (this.taskId) {
	      this.updateTask(tagIds);
	    }
	  },
	  updateNode: function updateNode(tagsString) {
	    var tagLine = BX('task-tags-line');
	    var tagLink = BX('task-tags-link');
	    BX.cleanNode(tagLine);
	    BX.adjust(tagLine, {
	      text: tagsString
	    });
	    if (tagsString.length > 0) {
	      tagLine.innerHTML += '&nbsp;&nbsp;';
	      tagLink.innerHTML = BX.message('TAGS_BUTTON_CHANGE');
	    } else {
	      tagLink.innerHTML = BX.message('TAGS_BUTTON_ADD');
	    }
	  },
	  updateTask: function updateTask(tagIds) {
	    BX.ajax.runComponentAction('bitrix:tasks.tags.selector', 'updateTags', {
	      mode: 'class',
	      data: {
	        taskId: this.taskId,
	        tagIds: tagIds
	      }
	    })["catch"](function (response) {
	      if (response.errors) {
	        BX.Tasks.alert(response.errors);
	      }
	    });
	  }
	};

}((this.BX.Tasks = this.BX.Tasks || {})));
//# sourceMappingURL=script.js.map
