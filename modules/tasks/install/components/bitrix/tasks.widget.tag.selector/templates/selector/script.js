/* eslint-disable */
this.BX = this.BX || {};
(function (exports) {
	'use strict';

	BX.namespace('Tasks.Component');
	(function () {
	  if (typeof BX.Tasks.Component.TasksWidgetTagSelector != 'undefined') {
	    return;
	  }

	  /**
	   * Main js controller for this template
	   */
	  BX.Tasks.Component.TasksWidgetTagSelector = BX.Tasks.Component.extend({
	    sys: {
	      code: 'tag-sel-is'
	    },
	    methods: {
	      construct: function construct() {
	        this.callConstruct(BX.Tasks.Component);
	        this.subInstance('items', function () {
	          return new this.constructor.Items({
	            scope: this.scope(),
	            data: this.option('data'),
	            groupId: this.option('groupId'),
	            taskId: this.option('taskId'),
	            userName: this.option('userName'),
	            isScrumTask: this.option('isScrumTask') === 'Y',
	            tagsAreConverting: this.option('tagsAreConverting'),
	            preRendered: true
	          });
	        });
	      }
	    }
	  });
	  BX.Tasks.Component.TasksWidgetTagSelector.Items = BX.Tasks.Util.ItemSet.extend({
	    sys: {
	      code: 'tag-sel'
	    },
	    options: {
	      controlBind: 'class',
	      itemFx: 'horizontal',
	      itemFxHoverDelete: true,
	      dialog: null,
	      dialogCallback: true
	    },
	    methods: {
	      bindEvents: function bindEvents() {
	        this.callMethod(BX.Tasks.Util.ItemSet, 'bindEvents');
	        this.bindDelegateControl('form-control', 'click', this.passCtx(this.openAddForm));
	        BX.addCustomEvent(window, 'onTaskTagSelectAlt', this.onTagsChange.bind(this));
	        var onPullTagChanged = function onPullTagChanged(params) {
	          var tagsToRemove = params.oldTagsNames;
	          if (this.opts.dialog) {
	            this.opts.dialog.hide();
	          }
	          var displayedItems = [];
	          this.each(function (item) {
	            displayedItems.push(item);
	          });
	          var names = [];
	          displayedItems.forEach(function (item) {
	            names.push(item.display());
	          });
	          var tagToChange = params.oldTagName;
	          var newTagName = params.newTagName;
	          if (!this.opts.changedItems) {
	            this.opts.changedItems = [];
	          }
	          displayedItems.forEach(function (item) {
	            if (tagToChange && newTagName !== '' && item.display() === tagToChange) {
	              this.addItem({
	                NAME: newTagName
	              });
	              this.opts.changedItems.indexOf(newTagName) === -1 && this.opts.changedItems.push(newTagName);
	              var oldIndex = this.opts.changedItems.indexOf(tagToChange);
	              this.opts.changedItems.splice(oldIndex, oldIndex);
	              this.deleteItem(item.value());
	              return;
	            }
	            if (tagToChange && newTagName === '' && item.display() === tagToChange) {
	              var index = this.opts.changedItems.indexOf(tagToChange);
	              this.opts.changedItems.splice(index, index);
	              this.deleteItem(item.value());
	              return;
	            }
	            if (tagsToRemove && tagsToRemove.length !== 0) {
	              if (tagsToRemove.indexOf(item.display()) !== -1) {
	                var multiIndex = this.opts.changedItems.indexOf(tagToChange);
	                this.opts.changedItems.splice(multiIndex, multiIndex);
	                this.deleteItem(item.value());
	                return;
	              } else {
	                this.opts.changedItems.indexOf(item.display()) === -1 && this.opts.changedItems.push(item.display());
	              }
	              return;
	            }
	            this.opts.changedItems.indexOf(item.display()) === -1 && this.opts.changedItems.push(item.display());
	          }.bind(this));
	          this.opts.dialog = null;
	        };
	        var onPullTaskProjectChanged = function onPullTaskProjectChanged(params) {
	          if (this.opts.dialog) {
	            this.selectedItems = this.opts.dialog.getSelectedItems();
	            this.opts.dialog.hide();
	            this.opts.dialog = null;
	          }
	          if (!params.data.groupId || params.data.groupId.length === 0) {
	            this.opts.groupId = 0;
	            this.tagOwner = this.option('userName');
	          } else {
	            this.opts.groupId = parseInt(params.data.groupId[0].match(/\d+/));
	            this.tagOwner = params.data.owner;
	          }
	        };
	        BX.addCustomEvent('onProjectChanged', onPullTaskProjectChanged.bind(this));
	        BX.PULL.subscribe({
	          type: BX.PullClient.SubscriptionType.Server,
	          moduleId: 'tasks',
	          command: 'tag_changed',
	          callback: onPullTagChanged.bind(this)
	        });
	        this.getTagSelector().load();
	      },
	      onTagsChange: function onTagsChange(event) {
	        var dialog = event.getTarget();
	        var selectedItem = event.getData().item;
	        selectedItem.setSort(1);
	        dialog.getTab('all').getRootNode().addItem(selectedItem);
	        var displayedItems = [];
	        this.each(function (item) {
	          displayedItems.push(item.display());
	        });
	        var items = this.getTagSelector().getItems();
	        var selectedItems = [];
	        for (var k = 0; k < items.length; k++) {
	          var item = items[k];
	          if (item.isSelected()) {
	            selectedItems.push(item.getTitle());
	            if (!BX.util.in_array(item.getTitle(), displayedItems)) {
	              this.addItem({
	                NAME: item.getTitle()
	              });
	            }
	          }
	        }

	        // delete deleted
	        this.each(function (item) {
	          if (!BX.util.in_array(item.display(), selectedItems)) {
	            this.deleteItem(item.value());
	          }
	        });
	      },
	      openAddForm: function openAddForm(node) {
	        if (this.option('tagsAreConverting')) {
	          var message = new top.BX.UI.Dialogs.MessageBox({
	            title: BX.message('TASKS_WIDGET_TAG_SELECTOR_TAGS_ARE_CONVERTING_TITLE'),
	            message: BX.message('TASKS_WIDGET_TAG_SELECTOR_TAGS_ARE_CONVERTING_TEXT'),
	            buttons: top.BX.UI.Dialogs.MessageBoxButtons.OK,
	            okCaption: BX.message('TASKS_WIDGET_TAG_SELECTOR_TAGS_ARE_CONVERTING_COME_BACK_LATER'),
	            onOk: function onOk() {
	              message.close();
	            }
	          });
	          message.show();
	          return;
	        }
	        this.getTagSelector().show();
	      },
	      onItemDeleteByCross: function onItemDeleteByCross(value) {
	        BX.onCustomEvent("onTaskTagDeleteByCross", [value.opts.data]);
	        this.callMethod(BX.Tasks.Util.ItemSet, 'onItemDeleteByCross', arguments);
	        this.unselectDialogItem(value);
	      },
	      unselectDialogItem: function unselectDialogItem(value) {
	        var dialog = this.getTagSelector();
	        if (!dialog) {
	          return;
	        }
	        this.opts.dialogCallback = false;
	        if (babelHelpers["typeof"](value) === 'object') {
	          value = value.data();
	        }
	        var item = dialog.getItem(this.prepareTagItemData(value, dialog));
	        item && item.deselect();
	        this.opts.dialogCallback = true;
	      },
	      prepareItemData: function prepareItemData(data) {
	        return ['task-tag', data.NAME];
	      },
	      prepareTagItemData: function prepareTagItemData(data, dialog) {
	        var tags = dialog.getItems();
	        var id = null;
	        tags.forEach(function (tag) {
	          if (tag.title.text === data.NAME) {
	            id = tag.id;
	            return;
	          }
	        });
	        return {
	          id: id,
	          entityId: 'task-tag'
	        };
	      },
	      extractItemDisplay: function extractItemDisplay(data) {
	        return data.NAME;
	      },
	      extractItemValue: function extractItemValue(data) {
	        if ('VALUE' in data) {
	          return data.VALUE;
	        }
	        return Math.abs(this.hashCode(data.NAME));
	      },
	      hashCode: function hashCode(str) {
	        if (!BX.type.isNotEmptyString(str)) {
	          return 0;
	        }
	        var hash = 0;
	        for (var i = 0; i < str.length; i++) {
	          var c = str.charCodeAt(i);
	          if (c > 0xFF) {
	            c -= 0x350;
	          }
	          hash = (hash << 5) - hash + c;
	          hash = hash & hash;
	        }
	        return hash;
	      },
	      //widget on task create/edit page
	      getTagSelector: function getTagSelector() {
	        var taskId = this.opts.taskId;
	        var groupId = this.opts.groupId;
	        var previousSelected = this.selectedItems;
	        var changedItems = this.opts.changedItems;
	        var tagOwner = this.tagOwner;
	        if (this.opts.groupId === 0) {
	          var projectDialog = BX.UI.EntitySelector.Dialog.getById("tasksMemberSelector_project");
	          if (projectDialog) {
	            var items = projectDialog.getSelectedItems();
	            if (items.length !== 0) {
	              this.opts.dialog = null;
	              this.opts.groupId = items[0].id;
	              groupId = this.opts.groupId;
	            }
	          }
	        }
	        var showAddButton = function showAddButton() {
	          var dialog = BX.UI.EntitySelector.Dialog.getById('tasksTagSelector');
	          dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-add-new').hidden = false;
	          dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-conjunction').hidden = false;
	        };
	        var hideAddButton = function hideAddButton() {
	          var dialog = BX.UI.EntitySelector.Dialog.getById('tasksTagSelector');
	          dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-add-new').hidden = true;
	          dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-conjunction').hidden = true;
	        };
	        var onSearch = function onSearch(event) {
	          var dialog = event.getTarget();
	          var query = event.getData().query;
	          if (query.trim() !== '') {
	            showAddButton();
	          } else {
	            hideAddButton();
	          }
	        };
	        var showAlert = function showAlert(className, error) {
	          var dialog = BX.UI.EntitySelector.Dialog.getById('tasksTagSelector');
	          if (dialog.getContainer().querySelector("div.".concat(className))) {
	            return;
	          }
	          var alert = document.createElement('div');
	          alert.className = className;
	          alert.innerHTML = "\n\t\t\t\t\t\t<div class='ui-alert ui-alert-xs ui-alert-danger'  \n\t\t\t\t\t\t\t<span class='ui-alert-message'>\n\t\t\t\t\t\t\t\t".concat(error, "\n\t\t\t\t\t\t\t</span> \n\t\t\t\t\t\t</div>\n\t\t\t\t\t");
	          dialog.getFooterContainer().before(alert);
	        };
	        var onTagsLoad = function onTagsLoad(event) {
	          var dialog = event.getTarget();
	          if (changedItems) {
	            changedItems.forEach(function (tagName) {
	              var item = dialog.addItem({
	                id: tagName,
	                entityId: 'task-tag',
	                title: tagName,
	                tabs: 'all',
	                badges: [{
	                  title: tagOwner
	                }]
	              });
	              item.select();
	            });
	          }
	          if (previousSelected) {
	            previousSelected.forEach(function (item) {
	              var tag = item.title.text;
	              var exists = false;
	              dialog.getItems().forEach(function (dialogItem) {
	                if (dialogItem.title.text === tag) {
	                  exists = true;
	                  dialogItem.select();
	                  dialogItem.setBadges([{
	                    title: tagOwner
	                  }]);
	                }
	              });
	              if (!exists) {
	                var newItem = dialog.addItem({
	                  id: item.title.text,
	                  entityId: item.entityId,
	                  title: item.title.text,
	                  tabs: 'all',
	                  badges: [{
	                    title: tagOwner
	                  }]
	                });
	                newItem.select();
	              }
	            });
	          }
	          var events = ['click', 'keydown'];
	          var handler = function handler(event) {
	            if (event.type === 'keydown') {
	              if (!((event.ctrlKey || event.metaKey) && event.keyCode === 13)) {
	                return;
	              }
	            }
	            var newTag = dialog.getTagSelectorQuery();
	            if (newTag.trim() === '') {
	              return;
	            }
	            BX.ajax.runComponentAction('bitrix:tasks.tag.list', 'addTag', {
	              mode: 'class',
	              data: {
	                newTag: newTag,
	                taskId: taskId,
	                groupId: groupId
	              }
	            }).then(function (response) {
	              if (response.data.success) {
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
	                dialog.clearSearch();
	              } else {
	                var alertClass = 'tasks-selector-add-tag-already-exists-alert';
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
	        var getTargetContainer = function () {
	          var fields = document.querySelectorAll('div.task-options-item-open-inner');
	          var target = this.control('form-control');
	          fields.forEach(function (field) {
	            if (field.contains(target)) {
	              target = field;
	            }
	          });
	          return target;
	        }.bind(this);
	        if (this.opts.dialog) {
	          return this.opts.dialog;
	        }
	        this.opts.dialog = new BX.UI.EntitySelector.Dialog({
	          id: 'tasksTagSelector',
	          targetNode: getTargetContainer(),
	          enableSearch: true,
	          width: 350,
	          height: 400,
	          multiple: true,
	          dropdownMode: true,
	          compactView: true,
	          entities: [{
	            id: 'task-tag',
	            options: {
	              taskId: this.option('taskId'),
	              groupId: this.opts.groupId
	            }
	          }],
	          searchOptions: {
	            allowCreateItem: false
	          },
	          footer: BX.Tasks.EntitySelector.Footer,
	          footerOptions: {
	            taskId: this.opts.taskId,
	            groupId: this.opts.groupId
	          },
	          clearUnavailableItems: true,
	          events: {
	            'onLoad': function (event) {
	              event.getTarget().getFooterContainer().style.zIndex = 1;
	              onTagsLoad(event);
	            }.bind(this),
	            'onSearch': function (event) {
	              onSearch(event);
	            }.bind(this),
	            'Item:onSelect': function (event) {
	              this.opts.dialogCallback && this.onTagsChange(event);
	            }.bind(this),
	            'Item:onDeselect': function (event) {
	              this.opts.dialogCallback && this.onTagsChange(event);
	            }.bind(this)
	          }
	        });
	        return this.opts.dialog;
	      }
	    }
	  });
	}).call(undefined);

}((this.BX.Tasks = this.BX.Tasks || {})));
//# sourceMappingURL=script.js.map
