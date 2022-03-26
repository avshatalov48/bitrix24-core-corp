'use strict';

BX.Mobile.Tasks.CheckList = (function()
{
	var CheckList = function(options)
	{
		this.renderTo = options.renderTo;
		this.optionManager = new BX.Mobile.Tasks.CheckList.OptionManager(options);
		this.treeStructure = this.buildTreeStructure(options.items);
		this.loader = new BX.Loader({target: this.renderTo});

		this.setOptionManager(this.treeStructure);
		this.saveStableTreeStructure();
		this.setListeners();
		this.bindControls();
		this.render();
		this.treeStructure.handleTaskOptions();

		BXMobileApp.Events.postToComponent(
			'onChecklistInit',
			{taskId: this.optionManager.entityId, taskGuid: this.optionManager.taskGuid},
			(this.optionManager.isEditMode() ? 'tasks.edit' : 'tasks.view')
		);
	};

	CheckList.prototype.setListeners = function()
	{
		var eventNames = {
			addAuditor: this.treeStructure.onMemberSelectedEvent,
			addAccomplice: this.treeStructure.onMemberSelectedEvent,
			addAttachment: this.treeStructure.onAddAttachmentEvent,
			removeAttachment: this.treeStructure.onRemoveAttachmentEvent,
			attachFiles: this.treeStructure.onAttachFilesEvent,
			removeFiles: this.treeStructure.onRemoveFilesEvent,
			fakeAttachFiles: this.treeStructure.onFakeAttachFilesEvent,
			fakeRemoveFiles: this.treeStructure.onFakeRemoveFilesEvent,
			rename: this.treeStructure.onRenameEvent,
			remove: this.treeStructure.onRemoveEvent,
			tabIn: this.treeStructure.onTabInEvent,
			tabOut: this.treeStructure.onTabOutEvent,
			important: this.treeStructure.onImportantEvent,
			toAnotherChecklist: this.treeStructure.onToAnotherCheckListEvent,
		};

		Object.keys(eventNames).forEach(function(name) {
			BXMobileApp.addCustomEvent('tasks.view.native::checklist.' + name, function(eventData) {
				eventNames[name].apply(this.treeStructure, [eventData]);
			}.bind(this));
		}.bind(this));

		BX.addCustomEvent('onKeyboardWillHide', function() {
			this.treeStructure.disableAllUpdateModes();
			this.treeStructure.handleTaskOptions();
		}.bind(this));
	};

	CheckList.prototype.bindControls = function()
	{
		BX.bind(BX('addCheckList'), 'click', this.onAddCheckListClick.bind(this));
		BX.bind(document, 'mousedown', this.onDocumentMouseDown.bind(this));
		BX.bind(document, 'mouseup', this.onDocumentMouseUp.bind(this));
	};

	CheckList.prototype.buildTreeStructure = function(items)
	{
		var treeStructure = new BX.Tasks.MobileCheckListItem();
		treeStructure.setNodeId(0);

		if (items.length !== 0)
		{
			var self = this;
			var descendants = items.DESCENDANTS;

			Object.keys(descendants).forEach(function(key) {
				treeStructure.add(self.makeTree(descendants[key]));
			});
		}

		return treeStructure;
	};

	CheckList.prototype.makeTree = function(root)
	{
		var fields = root.FIELDS;
		var descendants = root.DESCENDANTS;

		fields.action = root.ACTION;

		var tree = new BX.Tasks.MobileCheckListItem(fields);
		tree.fields.setTotalCount(0);

		if (typeof descendants !== 'undefined')
		{
			var self = this;

			Object.keys(descendants).forEach(function(key) {
				tree.add(self.makeTree(descendants[key]));
				tree.fields.setTotalCount(tree.fields.getTotalCount() + 1);
			});
		}

		return tree;
	};

	CheckList.prototype.setOptionManager = function(treeStructure)
	{
		var self = this;

		treeStructure.optionManager = this.optionManager;
		treeStructure.getDescendants().forEach(function(descendant) {
			self.setOptionManager(descendant);
		});
	};

	CheckList.prototype.getTreeStructure = function()
	{
		return this.treeStructure;
	};

	CheckList.prototype.render = function()
	{
		var layoutToRender = this.treeStructure.getLayout();

		if (this.optionManager.converted)
		{
			BX.append(layoutToRender, this.renderTo);
		}
		else
		{
			var text = '<div>PART_1</div><br><div>PART_2</div><div>PART_3</div><!--<br><div>PART_4</div>-->';
			var search = [
				'PART_1',
				'PART_2',
				'PART_3'
				// 'PART_4'
			];

			search.forEach(function(key) {
				text = text.replace(key, BX.message('TASKS_CHECKLIST_MOBILE_COMPONENT_JS_CHECKLIST_NOT_CONVERTED_MESSAGE_' + key));
			});

			var alert = new BX.UI.Alert({
				text: text,
				color: BX.UI.Alert.Color.PRIMARY,
				icon: BX.UI.Alert.Icon.DANGER
			});

			BX.append(alert.getContainer(), this.renderTo);
		}
	};

	CheckList.prototype.activateLoading = function()
	{
		this.loader.show();

		BX.addClass(this.renderTo.parentElement, 'tasks-checklist-zone-disabled');
		BX.bind(window, 'keydown', BX.proxy(this.disableTabbing, this));
	};

	CheckList.prototype.deactivateLoading = function()
	{
		BX.removeClass(this.renderTo.parentElement, 'tasks-checklist-zone-disabled');
		BX.unbind(window, 'keydown', BX.proxy(this.disableTabbing, this));

		this.loader.hide();
	};

	CheckList.prototype.disableTabbing = function(e)
	{
		if (e.keyCode === 9)
		{
			e.preventDefault();
		}
	};

	CheckList.prototype.getDestructedTreeStructure = function(treeStructure)
	{
		var self = this;
		var fields = {};
		var memberTypes = {
			accomplice: 'A',
			auditor: 'U'
		};

		Object.keys(treeStructure.fields).forEach(function(key) {
			if (key === 'members')
			{
				fields[key] = {};

				treeStructure.fields[key].forEach(function(value, id) {
					fields[key][id] = {
						TYPE: memberTypes[value.type],
						NAME: BX.util.htmlspecialcharsback(value.nameFormatted),
					}
				});

				return;
			}
			else if (key === 'attachments')
			{
				fields[key] = {};

				Object.keys(treeStructure.fields[key]).forEach(function(id) {
					fields[key][id] = treeStructure.fields[key][id];
				});

				return;
			}

			fields[key] = treeStructure.fields[key];
		});

		var destructedTreeStructure = {
			FIELDS: fields,
			ACTION: {
				MODIFY: treeStructure.checkCanUpdate(),
				REMOVE: treeStructure.checkCanRemove(),
				TOGGLE: treeStructure.checkCanToggle()
			},
			DESCENDANTS: [],
		};


		treeStructure.getDescendants().forEach(function(descendant) {
			destructedTreeStructure.DESCENDANTS.push(self.getDestructedTreeStructure(descendant));
		});

		return destructedTreeStructure;
	};

	CheckList.prototype.saveStableTreeStructure = function()
	{
		this.optionManager.setStableTreeStructure(this.getDestructedTreeStructure(this.treeStructure));
	};

	CheckList.prototype.loadStableTreeStructure = function()
	{
		return this.buildTreeStructure(this.optionManager.getStableTreeStructure());
	};

	CheckList.prototype.rerender = function()
	{
		BX.remove(this.treeStructure.panel);

		this.treeStructure = this.loadStableTreeStructure();
		this.dragndrop.treeStructure = this.treeStructure;
		this.setOptionManager(this.treeStructure);

		while (this.renderTo.lastChild)
		{
			this.renderTo.removeChild(this.renderTo.lastChild);
		}

		this.render();
		this.treeStructure.handleTaskOptions();
	};

	CheckList.prototype.onAddCheckListClick = function()
	{
		if (this.treeStructure.checkActiveUpdateExist())
		{
			return;
		}

		this.addCheckList().then(function(newCheckList) {
			if (!this.optionManager.isEditMode())
			{
				newCheckList.sendAddAjaxAction();
			}
			newCheckList.addCheckListItem();
		}.bind(this));

		BX.Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	};

	CheckList.prototype.addCheckList = function()
	{
		if (!this.optionManager.getCanAdd())
		{
			return;
		}

		var p = new BX.Promise();
		var title = BX.message('TASKS_CHECKLIST_MOBILE_COMPONENT_JS_NEW_CHECKLIST_TITLE').replace('#ITEM_NUMBER#', this.treeStructure.getDescendantsCount() + 1);
		var newCheckList = new BX.Tasks.MobileCheckListItem({TITLE: title});

		this.treeStructure.addCheckListItem(newCheckList).then(function(resolve) {
			p.resolve(resolve);
		});

		return p;
	};

	CheckList.prototype.onDocumentMouseDown = function(e)
	{
		if (e.button !== 0)
		{
			return;
		}

		this.focusElement = e.target.closest('.ui-ctl-textbox');
	};

	CheckList.prototype.onDocumentMouseUp = function(e)
	{
		if (e.button !== 0)
		{
			return;
		}

		var validAreaDetected = false;
		var validAreas = [
			e.target.closest('.mobile-task-checklist-item-title'),
			e.target.closest('.mobile-task-checklist-head-title'),
		];

		validAreas.forEach(function(area) {
			if (area !== null)
			{
				validAreaDetected = true;
			}
		});

		if (validAreaDetected || this.focusElement)
		{
			return;
		}

		this.treeStructure.disableAllUpdateModes();
		this.treeStructure.handleTaskOptions();
	};

	return CheckList;
})();

BX.Mobile.Tasks.CheckList.OptionManager = (function()
{
	var OptionManager = function(options)
	{
		this.userId = options.userId;
		this.entityId = options.entityId;
		this.entityType = options.entityType;
		this.userPath = options.userPath;
		this.prefix = options.prefix;
		this.taskGuid = options.taskGuid;
		this.mode = options.mode;

		this.commonAction = options.commonAction;
		this.converted = options.converted;

		this.ajaxActions = options.ajaxActions;
		this.diskOptions = {
			folderId: options.diskFolderId,
			urls: options.diskUrls,
		};

		this.showCompleteAllButton = options.showCompleteAllButton;
		this.collapseOnCompleteAll = options.collapseOnCompleteAll;
		this.showCompleted = options.options.SHOW_COMPLETED;
		this.defaultMemberSelectorType = options.options.DEFAULT_MEMBER_SELECTOR_TYPE;
		this.showOnlyMine = false;

		this.stableTreeStructure = null;
		this.slider = BX.SidePanel.Instance.getTopSlider();
	};

	OptionManager.prototype.getUserPath = function()
	{
		return this.userPath;
	};

	OptionManager.prototype.getUserId = function()
	{
		return this.userId;
	};

	OptionManager.prototype.getPrefix = function()
	{
		return this.prefix;
	};

	OptionManager.prototype.getCanAdd = function()
	{
		return this.commonAction.canAdd;
	};

	OptionManager.prototype.getCanDrag = function()
	{
		return this.commonAction.canDrag;
	};

	OptionManager.prototype.isEditMode = function()
	{
		return (this.mode === 'edit');
	};

	OptionManager.prototype.getShowCompleteAllButton = function()
	{
		return this.showCompleteAllButton;
	};

	OptionManager.prototype.getCollapseOnCompleteAll = function()
	{
		return this.collapseOnCompleteAll;
	};

	OptionManager.prototype.getCanAddAccomplice = function()
	{
		return this.commonAction.canAddAccomplice;
	};

	OptionManager.prototype.getShowCompleted = function()
	{
		return this.showCompleted;
	};

	OptionManager.prototype.setShowCompleted = function(showCompleted)
	{
		this.showCompleted = showCompleted;
		this.updateTaskOption('show_completed', showCompleted);
	};

	OptionManager.prototype.getShowOnlyMine = function()
	{
		return this.showOnlyMine;
	};

	OptionManager.prototype.setShowOnlyMine = function(showOnlyMine)
	{
		this.showOnlyMine = showOnlyMine;
	};

	OptionManager.prototype.getDefaultMemberSelectorType = function()
	{
		return this.defaultMemberSelectorType;
	};

	OptionManager.prototype.getStableTreeStructure = function()
	{
		return this.stableTreeStructure;
	};

	OptionManager.prototype.setStableTreeStructure = function(stableTreeStructure)
	{
		this.stableTreeStructure = stableTreeStructure;
	};

	OptionManager.prototype.updateTaskOption = function(option, value)
	{
		BX.ajax.runComponentAction('bitrix:tasks.widget.checklist.new', 'updateTaskOption', {
			mode: 'class',
			data: {
				option: option,
				value: value,
				userId: this.userId,
				entityType: this.entityType
			}
		}).then(
			function(response)
			{

			}.bind(this),
			function(response)
			{

			}.bind(this)
		);
	};

	return OptionManager;
})();

/**
 *
 * @param options
 * @extends {BX.UI.ProgressRound}
 * @constructor
 */
BX.Mobile.Tasks.CheckList.ProgressRound = function(options)
{
	BX.UI.ProgressRound.apply(this, arguments);
};

BX.Mobile.Tasks.CheckList.ProgressRound.prototype = {
	__proto__: BX.UI.ProgressRound.prototype,
	constructor: BX.Mobile.Tasks.CheckList.ProgressRound,

	getStatusCounter: function()
	{
		this.statusCounter = Math.round(this.getValue()) + "/" + Math.round(this.getMaxValue());
		if (Math.round(this.getValue()) > Math.round(this.getMaxValue()))
		{
			this.statusCounter = Math.round(this.getMaxValue()) + "/" + Math.round(this.getMaxValue());
		}

		return this.statusCounter;
	},

	getStatus: function()
	{
		if (this.status === null)
		{
			if (this.getStatusType() === BX.UI.ProgressRound.Status.COUNTER)
			{
				this.status = BX.create("div", {
					props: { className: "ui-progressround-status mobile-tasks-progressround-status" },
					text: this.getStatusCounter()
				});
			}
			else if (this.getStatusType() === BX.UI.ProgressRound.Status.INCIRCLE)
			{
				this.status = BX.create("div", {
					props: { className: "ui-progressround-status-percent-incircle" },
					text: this.getStatusPercent()
				});
			}
			else if (this.getStatusType() === BX.UI.ProgressRound.Status.PERCENT)
			{
				this.status = BX.create("div", {
					props: { className: "ui-progressround-status-percent" },
					text: this.getStatusPercent()
				});
			}
			else
			{
				this.status = BX.create("span", {});
			}
		}

		return this.status;
	},

	getContainer: function()
	{
		if (this.container === null)
		{
			this.container = BX.create("div", {
				props: { className: "ui-progressround" },
				children: [
					this.getTextAfter(),
					this.getTextBefore(),
					BX.create("div", {
						props: { className: "ui-progressround-track mobile-tasks-ui-progressround-track" },
						children: [
							this.getStatus(),
							this.getBar(),
							this.animateProgressBar()
						]
					})
				]
			});
		}

		return this.container;
	}
};