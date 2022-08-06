'use strict';

BX.Tasks.CheckList = (function()
{
	var CheckList = function(options)
	{
		this.renderTo = options.renderTo;
		this.optionManager = new BX.Tasks.CheckList.OptionManager(options);
		this.treeStructure = this.buildTreeStructure(options.items);
		this.dragndrop = new BX.Tasks.CheckList.DragManager({
			dndZone: this.renderTo,
			treeStructure: this.treeStructure,
			canDrag: this.optionManager.getCanDrag()
		});
		this.clickEventHandler = new BX.Tasks.CheckList.ClickEventHandler({
			treeStructure: this.treeStructure
		});
		this.loader = new BX.Loader({target: this.renderTo});

		this.suffixDomId = options.suffixDomId;

		this.setOptionManager(this.treeStructure);
		this.setClickEventHandler(this.treeStructure);

		this.saveStableTreeStructure();
		this.bindControls();
		this.render();
		this.treeStructure.handleTaskOptions();
	};

	CheckList.prototype.bindControls = function()
	{
		var form = this.renderTo.closest('form');
		if (form)
		{
			BX.bind(form, 'submit', BX.proxy(function() {
				this.treeStructure.appendRequestLayout();
			}, this));
		}

		var addChecklistNode = document.getElementById('addCheckList_' + this.suffixDomId);
		if (!addChecklistNode)
		{
			addChecklistNode = top.document.getElementById('addCheckList_' + this.suffixDomId);
		}

		BX.bind(addChecklistNode, 'click', this.onAddCheckListClick.bind(this));
		BX.bind(document, 'mousedown', this.onDocumentMouseDown.bind(this));
		BX.bind(document, 'mouseup', this.onDocumentMouseUp.bind(this));
	};

	CheckList.prototype.buildTreeStructure = function(items)
	{
		var treeStructure = new BX.Tasks.CheckListItem();
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

		var tree = new BX.Tasks.CheckListItem(fields);
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

	CheckList.prototype.setClickEventHandler = function(treeStructure)
	{
		var self = this;

		treeStructure.clickEventHandler = this.clickEventHandler;
		treeStructure.getDescendants().forEach(function(descendant) {
			self.setClickEventHandler(descendant);
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
			var text = '<div>PART_1</div><br><div>PART_2</div><div>PART_3</div><br><div>PART_4</div>';
			var search = [
				'PART_1',
				'PART_2',
				'PART_3',
				'PART_4'
			];

			search.forEach(function(key) {
				text = text.replace(key, BX.message('TASKS_CHECKLIST_COMPONENT_JS_CHECKLIST_NOT_CONVERTED_MESSAGE_' + key));
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
			else if (key === 'title')
			{
				fields[key] = BX.util.htmlspecialcharsback(treeStructure.fields[key]);
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
		this.clickEventHandler.treeStructure = this.treeStructure;

		this.setOptionManager(this.treeStructure);
		this.setClickEventHandler(this.treeStructure);

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
			newCheckList.addCheckListItem();
		});

		BX.Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	};

	CheckList.prototype.addCheckList = function()
	{
		if (!this.optionManager.getCanAdd())
		{
			return;
		}

		var p = new BX.Promise();
		var title = BX.message('TASKS_CHECKLIST_COMPONENT_JS_NEW_CHECKLIST_TITLE_2').replace('#ITEM_NUMBER#', this.treeStructure.getDescendantsCount() + 1);
		var newCheckList = new BX.Tasks.CheckListItem({TITLE: title});

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
			e.target.closest('.tasks-checklist-item-content-block'),
			e.target.closest('.tasks-checklist-item-editor-panel-container'),
			e.target.closest('.tasks-checklist-item-attachment-file'),
			e.target.closest('.tasks-checklist-header-name'),
			e.target.closest('#files_chooser'),
			e.target.closest('#DiskFileDialog'),
			e.target.closest('.ui-selector-dialog')
		];

		validAreas.forEach(function(area) {
			if (!validAreaDetected && area !== null)
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

	CheckList.prototype.onSave = function()
	{
		this.treeStructure.disableAllGroup();
	};

	return CheckList;
})();

BX.Tasks.CheckList.ClickEventHandler = (function()
{
	var ClickEventHandler = function()
	{
		this.pos = {x: 0, y: 0};
		this.time = 0;
		this.timeoutHandle = 0;
		this.clicked = 0;
	};

	ClickEventHandler.prototype.registerClickDoneCallback = function(callback)
	{
		this.callback = callback;
	};

	ClickEventHandler.prototype.handleMouseDown = function(e)
	{
		if (this.timeoutHandle > 0)
		{
			this.clearTimeout();
		}

		this.time = new Date().valueOf();
		this.pos = {x: e.clientX, y: e.clientY};
	};

	ClickEventHandler.prototype.handleMouseUp = function(e)
	{
		if (this.timeoutHandle > 0)
		{
			this.clearTimeout();
		}

		if ((new Date().valueOf() - this.time) < 400 && Math.abs(this.pos.x - e.clientX) < 2)
		{
			this.clicked += 1;

			if (this.clicked < 2)
			{
				this.timeoutHandle = window.setTimeout(this.callback, 150);
				setTimeout(this.clearClicked.bind(this), 200);
			}
			else
			{
				this.clearTimeout();
				setTimeout(this.clearClicked.bind(this), 200);
			}
		}

		this.time = 0;
	};

	ClickEventHandler.prototype.clearTimeout = function()
	{
		window.clearTimeout(this.timeoutHandle);
		this.timeoutHandle = 0;
	};

	ClickEventHandler.prototype.clearClicked = function()
	{
		this.clicked = 0;
	};

	return ClickEventHandler;
})();

BX.Tasks.CheckList.OptionManager = (function()
{
	var OptionManager = function(options)
	{
		this.userId = options.userId;
		this.entityId = options.entityId;
		this.entityType = options.entityType;
		this.userPath = options.userPath;
		this.prefix = options.prefix;

		this.commonAction = options.commonAction;
		this.converted = options.converted;

		this.ajaxActions = options.ajaxActions;
		this.attachments = options.attachments;
		this.diskUrls = options.diskUrls;

		this.showCompleteAllButton = options.showCompleteAllButton;
		this.collapseOnCompleteAll = options.collapseOnCompleteAll;
		this.isNetworkEnabled = options.isNetworkEnabled;
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

	OptionManager.prototype.getCanAddAccomplice = function()
	{
		return this.commonAction.canAddAccomplice;
	};

	OptionManager.prototype.getShowCompleteAllButton = function()
	{
		return this.showCompleteAllButton;
	};

	OptionManager.prototype.getCollapseOnCompleteAll = function()
	{
		return this.collapseOnCompleteAll;
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

BX.Tasks.CheckList.DragManager = (function()
{
	var DragManager = function(options)
	{
		this.dragObject = {};
		this.dropObject = {};

		this.itemDropPlace = BX.create('div', {
			props: {
				className: 'tasks-checklist-item tasks-checklist-item-drop-place droppable'
			},
			children: [
				BX.create('div', {
					props: {
						className: 'tasks-checklist-item-inner'
					}
				})
			]
		});
		this.checkListDropPlace = BX.create('div', {
			props: {
				className: 'tasks-checklist-wrapper-drop-place droppable'
			}
		});

		this.canDrag = options.canDrag;
		this.dndZone = options.dndZone;
		this.treeStructure = options.treeStructure;

		BX.bind(document, 'mousedown', this.onMouseDown.bind(this));
	};

	DragManager.prototype.onMouseDown = function(e)
	{
		if (e.button !== 0 || !this.canDrag)
		{
			return;
		}

		var dragButton = e.target.closest('.tasks-checklist-item-dragndrop') || e.target.closest('.tasks-checklist-wrapper-dragndrop');
		if (!dragButton)
		{
			return;
		}

		var dragObjectNode = this.getNodeByDragButton(dragButton);
		if (!dragObjectNode || dragObjectNode.getCheckList().fields.getIsSelected())
		{
			return;
		}

		this.dragObject = {};
		this.dropObject = {};

		this.dragObject.node = dragObjectNode;

		this.dragObject.downX = e.pageX;
		this.dragObject.downY = e.pageY;

		this.disableSelection(this.dndZone);

		BX.bind(document, 'mousemove', BX.proxy(this.onMouseMove, this));
		BX.bind(document, 'mouseup', BX.proxy(this.onMouseUp, this));

		return false;
	};

	DragManager.prototype.getNodeByDragButton = function(dragButton)
	{
		return this.treeStructure.findChild(dragButton.parentElement.parentElement.id);
	};

	DragManager.prototype.getDropObjectType = function(targetDrop)
	{
		if (BX.hasClass(targetDrop, 'tasks-checklist-item-drop-place') || BX.hasClass(targetDrop, 'tasks-checklist-wrapper-drop-place'))
		{
			return 'dropPlace';
		}

		if (BX.hasClass(targetDrop, 'tasks-checklist-item-inner'))
		{
			return 'node';
		}

		if (BX.hasClass(targetDrop, 'tasks-checklist-items-list-actions'))
		{
			return 'addButton';
		}

		if (BX.hasClass(targetDrop, 'tasks-checklist-header-wrapper'))
		{
			return 'checklist';
		}
	};

	DragManager.prototype.getDropObjectNode = function()
	{
		var dropObjectNode = null;
		var targetDrop = this.dropObject.targetDrop;

		switch (this.dropObject.type)
		{
			case 'node':
				dropObjectNode = this.treeStructure.findChild(targetDrop.parentElement.id);
				if (this.dropObject.position === 'bottom' && dropObjectNode.getDescendantsCount() > 0)
				{
					this.dropObject.position = 'top';
					dropObjectNode = dropObjectNode.getFirstDescendant();
				}
				break;

			case 'addButton':
				dropObjectNode = this.treeStructure.findChild(targetDrop.parentElement.parentElement.id);
				this.dropObject.position = 'bottom';
				break;

			case 'checklist':
				dropObjectNode = this.treeStructure.findChild(targetDrop.parentElement.id);
				if (!this.dragObject.node.isCheckList())
				{
					this.dropObject.position = 'top';
				}
				break;
		}

		return dropObjectNode;
	};

	DragManager.prototype.spawnDropPlace = function()
	{
		switch (this.dropObject.type)
		{
			case 'node':
				if (this.dropObject.position === 'top')
				{
					BX.insertBefore(this.itemDropPlace, this.dropObject.node.getContainer());
				}
				else
				{
					BX.insertAfter(this.itemDropPlace, this.dropObject.node.getContainer());
				}
				break;

			case 'addButton':
				if (this.dropObject.node.getDescendantsCount() > 0)
				{
					BX.insertAfter(this.itemDropPlace, this.dropObject.node.getLastDescendant().getContainer());
				}
				else
				{
					BX.append(this.itemDropPlace, this.dropObject.node.getSubItemsContainer());
				}
				break;

			case 'checklist':
				if (this.dragObject.node.isCheckList())
				{
					if (this.dropObject.position === 'top')
					{
						BX.insertBefore(this.checkListDropPlace, this.dropObject.node.getContainer());
					}
					else
					{
						BX.insertAfter(this.checkListDropPlace, this.dropObject.node.getContainer())
					}
				}
				else if (this.dropObject.node.getDescendantsCount() > 0)
				{
					BX.insertBefore(this.itemDropPlace, this.dropObject.node.getFirstDescendant().getContainer());
				}
				else
				{
					BX.append(this.itemDropPlace, this.dropObject.node.getSubItemsContainer());
				}
				break;
		}
	};

	DragManager.prototype.onMouseMove = function(e)
	{
		if (!this.dragObject.node)
		{
			return;
		}

		if (!this.dragObject.avatar)
		{
			var moveX = e.pageX - this.dragObject.downX;
			var moveY = e.pageY - this.dragObject.downY;

			if (Math.abs(moveX) < 5 && Math.abs(moveY) < 5)
			{
				return;
			}

			this.dragObject.avatar = this.createAvatar();
			if (!this.dragObject.avatar)
			{
				this.dragObject = {};
				return;
			}

			this.startDrag();
		}

		this.moveAvatar(e);

		var targetDrop = this.findDroppable(e);
		if (!targetDrop)
		{
			return false;
		}

		var dropObjectType = this.getDropObjectType(targetDrop);
		if (dropObjectType === 'node' || (dropObjectType === 'checklist' && this.dragObject.node.isCheckList()))
		{
			var coords = this.getCoords(targetDrop);

			this.dropObject.position = (e.pageY < coords.top + coords.height / 2 ? 'top' : 'bottom');
			this.dropObject.targetDrop = targetDrop;
			this.dropObject.type = dropObjectType;
			this.dropObject.node = this.getDropObjectNode();

			this.spawnDropPlace();
		}
		else if (dropObjectType !== 'dropPlace' && targetDrop !== this.dropObject.targetDrop)
		{
			this.dropObject.position = (dropObjectType === 'checklist' ? 'top' : 'bottom');
			this.dropObject.targetDrop = targetDrop;
			this.dropObject.type = dropObjectType;
			this.dropObject.node = this.getDropObjectNode();

			this.spawnDropPlace();
		}

		return false;
	};

	DragManager.prototype.createAvatar = function()
	{
		var dragNodeContainer = this.dragObject.node.getContainer();
		var avatar = dragNodeContainer.cloneNode(true);

		if (this.dragObject.node.isCheckList())
		{
			BX.addClass(avatar, 'tasks-checklist-wrapper-draggable');

			if (!BX.hasClass(avatar, 'tasks-checklist-collapse'))
			{
				BX.addClass(avatar, 'tasks-checklist-collapse');
			}

			avatar.querySelector('.tasks-checklist-items-wrapper').style.height = 0;
		}
		else
		{
			BX.addClass(avatar, 'tasks-checklist-item-draggable');

			if (this.dragObject.node.getDescendantsCount() > 0)
			{
				BX.append(BX.create('div' , {props: {className: 'tasks-checklist-group-draggable'}}), avatar);
			}
		}
		BX.removeClass(avatar.querySelector('.droppable'), 'droppable');

		avatar.style.left = this.dragObject.downX + 'px';
		avatar.style.width = dragNodeContainer.offsetWidth / 2 + 'px';

		return avatar;
	};

	DragManager.prototype.moveAvatar = function(e)
	{
		var dragButtonLeft = this.dragObject.node.isCheckList() ? 10 : 20;
		var dragButtonTop = 25;

		this.dragObject.avatar.style.left = e.pageX - dragButtonLeft + 'px';
		this.dragObject.avatar.style.top = e.pageY - dragButtonTop + 'px';
	};

	DragManager.prototype.startDrag = function()
	{
		var avatar = this.dragObject.avatar;
		BX.hide(this.dragObject.node.getContainer());

		BX.append(avatar, document.body);

		avatar.style.zIndex = 9999;
		avatar.style.position = 'absolute';

		if (this.dragObject.node.isCheckList())
		{
			this.treeStructure.getDescendants().forEach(function(descendant) {
				if (!descendant.fields.getIsCollapse())
				{
					descendant.toggleCollapse();
					descendant.fields.setIsCollapse(false);
				}
			});
		}
	};

	DragManager.prototype.onMouseUp = function(e)
	{
		if (this.dragObject.avatar)
		{
			this.finishDrag(e);
		}

		this.dragObject = {};

		this.enableSelection();

		BX.unbind(document, 'mousemove', BX.proxy(this.onMouseMove, this));
		BX.unbind(document, 'mouseup', BX.proxy(this.onMouseUp, this));
	};

	DragManager.prototype.finishDrag = function(e)
	{
		var targetDrop = this.findDroppable(e);

		BX.remove(this.itemDropPlace);
		BX.remove(this.checkListDropPlace);

		if (!targetDrop || this.dragObject.node.isCheckList() && this.dropObject.type !== 'checklist')
		{
			this.cancelDrag();
		}
		else
		{
			this.endDrag();
		}

		if (this.dragObject.node.isCheckList())
		{
			this.treeStructure.getDescendants().forEach(function(descendant) {
				if (!descendant.fields.getIsCollapse())
				{
					descendant.toggleCollapse();
				}
			});
		}
	};

	DragManager.prototype.cancelDrag = function()
	{
		this.dragObject.node.getContainer().style.display = '';
		BX.remove(this.dragObject.avatar);
	};

	DragManager.prototype.endDrag = function()
	{
		var dragNode = this.dragObject.node;
		var dropNode = this.dropObject.node;

		BX.remove(this.dragObject.avatar);

		if (this.dropObject.type === 'node' || (this.dropObject.type === 'checklist' && dragNode.isCheckList()))
		{
			dragNode.move(dropNode, this.dropObject.position);
		}
		else
		{
			dragNode.makeChildOf(dropNode, this.dropObject.position);
		}

		dragNode.getContainer().style.display = '';
	};

	DragManager.prototype.findDroppable = function(e)
	{
		BX.hide(this.dragObject.avatar);
		var deepestElement = document.elementFromPoint(e.clientX, e.clientY);
		BX.show(this.dragObject.avatar);

		if (deepestElement == null)
		{
			return null;
		}

		return deepestElement.closest('.droppable');
	};

	DragManager.prototype.getCoords = function(element)
	{
		var box = element.getBoundingClientRect();

		return {
			top: box.top + pageYOffset,
			left: box.left + pageXOffset,
			width: box.width,
			height: box.height
		};

	};

	DragManager.prototype.enableSelection = function()
	{
		BX.removeClass(document.body, 'tasks-checklist-zone-noselect');
	};

	DragManager.prototype.disableSelection = function()
	{
		BX.addClass(document.body, 'tasks-checklist-zone-noselect');
	};

	return DragManager;
})();

(function()
{
	if (typeof BX.Tasks.Component.TasksWidgetCheckListNew != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetCheckListNew = BX.Tasks.Component.extend({
		sys: {
			code: 'checklist'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
			},

			bindEvents: function()
			{

			}

			// add more methods, then call them like this.methodName()
		}
	});

	// may be some sub-controllers here...
}).call(this);