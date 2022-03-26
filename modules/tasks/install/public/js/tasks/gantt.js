(function() {

if (BX.GanttChart)
{
	return;
}


	
/*==================================GanttChart==================================*/
BX.GanttChart = function(domNode, currentDatetime, settings)
{
	//Dom layout
	this.layout = {
		root : null,
		list : null,
		tree : null,
		treeStub : null,
		gutter : null,
		timelineInner : null,
		scalePrimary : null,
		scaleSecondary : null,
		timelineData : null,
		currentDay : null
	};

	this.benchTime = new Date().getTime();
	this.settings = settings || {};

	this.userProfileUrl = BX.type.isNotEmptyString(settings.userProfileUrl) ? settings.userProfileUrl : "";

	this.chartContainer = {
		element : BX.type.isDomNode(domNode) ? domNode : null,
		padding : 30,
		width : 0,
		pos : { left: 0, top: 0 },
		minPageX : 0,
		maxPageX : 0
	};
	this.adjustChartContainer();

	this.gutterOffset = this.normalizeGutterOffset(BX.type.isNumber(settings.gutterOffset) ? settings.gutterOffset : 300);
	this.timelineDataOffset = null;
	this.dragClientX = 0;

	this.minBarWidth = 4;
	this.minGroupBarWidth = 8;
	this.arrowBarWidth = 4;
	this.firstDepthLevelOffset = 33;
	this.depthLevelOffset = 20;
	this.endlessBarDefaultDuration = 9;

	this.currentDatetime = BX.Tasks.Date.isDate(currentDatetime) ? BX.GanttChart.convertDateToUTC(currentDatetime) : BX.GanttChart.convertDateToUTC(new Date());
	this.currentDate = new Date(Date.UTC(this.currentDatetime.getUTCFullYear(), this.currentDatetime.getUTCMonth(), this.currentDatetime.getUTCDate(), 0, 0, 0, 0));

	this.timeline = new Timeline(this);
	this.calendar = new BX.Tasks.Calendar(this.settings);
	this.printSettings = null;
	this.dragger = new DragDrop(this);
	this.canDragTasks = this.settings.canDragTasks === true;
	this.canCreateDependency = this.settings.canCreateDependency !== false;

	this.tasks = {};
	this.projects = {
		0 : new GanttProject(this, 0, "Default Project")
	};
	this.dependencies = {};

	this.treeMode = this.settings.treeMode !== false;
	this.oneGroupMode = this.settings.oneGroupMode === true;

	this.datetimeFormat = BX.type.isNotEmptyString(settings.datetimeFormat) ? settings.datetimeFormat : "DD.MM.YYYY HH:MI:SS";
	this.dateFormat = BX.type.isNotEmptyString(settings.dateFormat) ? settings.dateFormat : "DD.MM.YYYY";

	this.tooltip = new GanttTooltip(this);
	this.pointer = new DependencyPointer(this);
	this.allowRowHover = true;

	//Chart Events
	if (this.settings.events)
	{
		for (var eventName in this.settings.events)
		{
			if (this.settings.events.hasOwnProperty(eventName))
			{
				BX.addCustomEvent(this, eventName, this.settings.events[eventName]);
			}
		}
	}

	BX.bind(window, "resize", BX.proxy(this.onWindowResize, this));
};

BX.GanttChart.prototype.draw = function()
{
	if (this.chartContainer.element === null)
	{
		return;
	}

	this.drawLayout();
	this.drawTasks();

	this.timeline.autoScroll();
};

BX.GanttChart.prototype.zoomToLevel = function(level)
{
	if (this.layout.root === null)
	{
		return;
	}

	this.timeline.setZoomLevel(level);

	//First change the timeline width
	for (var taskId in this.tasks)
	{
		var task = this.tasks[taskId];
		this.timeline.autoExpandTimeline([task.getMinDate(), task.getMaxDate()], true);
	}

	this.timeline.draw();

	//Then update tasks
	for (taskId in this.tasks)
	{
		task = this.tasks[taskId];
		task.updateBars();
	}

	this.drawDependencies();

	this.timeline.autoScroll();

	BX.onCustomEvent(this, "onZoomChange", [level]);
};

BX.GanttChart.prototype.zoomIn = function()
{
	var nextLevel = this.timeline.zoom.getNextLevel();
	var currentLevel = this.timeline.zoom.getCurrentLevel();
	if (nextLevel.id !== currentLevel.id)
	{
		this.zoomToLevel(nextLevel.id);
	}
};

BX.GanttChart.prototype.zoomOut = function()
{
	var prevLevel = this.timeline.zoom.getPrevLevel();
	var currentLevel = this.timeline.zoom.getCurrentLevel();
	if (prevLevel.id !== currentLevel.id)
	{
		this.zoomToLevel(prevLevel.id);
	}
};

BX.GanttChart.prototype.drawLayout = function()
{
	if (!this.chartContainer.element || this.layout.root != null)
	{
		return;
	}

	this.layout.root = BX.create("div", { props : {className: "task-gantt" }, style : { width : this.chartContainer.width + "px"}, children : [

		(this.layout.list = BX.create("div", {
			props : { className: "task-gantt-list"},
			style : { width : this.gutterOffset + "px" },
			children : [
				BX.create("div", { props : { className: "task-gantt-list-controls" }, children: [
					BX.Scheduler.Util.isMSBrowser() ? null : (this.layout.print = BX.create("div", { props: { className: "task-gantt-print" }, events: {
						click: BX.proxy(this.onPrintClick, this)
					}})),
					(this.layout.zoomIn = BX.create("div", { props: { className: "task-gantt-zoom-in" }, events: {
						click: BX.proxy(this.onZoomInClick, this)
					}})),
					(this.layout.zoomOut =  BX.create("div", { props: { className: "task-gantt-zoom-out" }, events: {
						click: BX.proxy(this.onZoomOutClick, this)
					}}))
				]}),
				BX.create("div", { props : { className: "task-gantt-list-title" }, text : BX.message("TASKS_GANTT_CHART_TITLE") }),
				(this.layout.tree = BX.create("div", { props : { className: "task-gantt-items" }})),
				(this.layout.gutter = BX.create("div", { props : { className: "task-gantt-gutter" }, events : {
					mousedown : BX.proxy(this.onGutterMouseDown, this)
				}})),
				(this.layout.treeStub = BX.create("div", {
					props: { className: "task-gantt-item task-gantt-item-stub"},
					attrs: { "data-project-id": "stub"}
				}))
			]
		})),

		(this.layout.timeline = BX.create("div", {
			props : {
				className: "task-gantt-timeline"
			},
			events: {
				scroll: this.handleTimelineScroll.bind(this)
			},
			children : [
				(this.layout.timelineInner =  BX.create("div", { props : { className: "task-gantt-timeline-inner" },
					events : {
						mousedown : BX.proxy(this.onTimelineMouseDown, this)
					},
					children : [
						BX.create("div", { props : { className: "task-gantt-timeline-head" }, children : [
							(this.layout.scalePrimary =  BX.create("div", { props : { className: "task-gantt-scale-primary" }})),
							(this.layout.scaleSecondary = BX.create("div", { props : { className: "task-gantt-scale-secondary" }}))
						]}),
						(this.layout.timelineData = BX.create("div", { props : { className: "task-gantt-timeline-data"}})),
						(this.layout.currentDay = BX.create("div", { props : { className: "task-gantt-current-day" } })),
						this.tooltip.getLayout(),
						this.pointer.getLayout()
					]
				}))
			]
		}))
	]});

	this.dragger.registerProject(this.layout.treeStub);

	this.timeline.draw();
	this.chartContainer.element.appendChild(this.layout.root);
};

BX.GanttChart.prototype.drawTasks = function()
{
	if (this.layout.root === null)
	{
		return;
	}

	var taskTree = document.createDocumentFragment();
	var taskData = document.createDocumentFragment();

	var projects = this.getSortedProjects();
	for (var i = 0; i < projects.length; i++)
	{
		if (projects[i].id != 0)
		{
			taskTree.appendChild(projects[i].createItem());
			taskData.appendChild(projects[i].createBars());
		}

		this.drawTasksRecursive(projects[i].tasks, taskTree, taskData);
	}

	BX.cleanNode(this.layout.tree);
	BX.cleanNode(this.layout.timelineData);

	this.layout.tree.appendChild(taskTree);
	this.layout.timelineData.appendChild(taskData);

	this.adjustChartContainer();
	this.drawDependencies();
};

BX.GanttChart.prototype.drawTasksRecursive = function(tasks, taskTree, taskData)
{
	for (var i = 0, length = tasks.length; i < length; i++)
	{
		taskTree.appendChild(tasks[i].createItem());
		taskData.appendChild(tasks[i].createBars());
		if (tasks[i].childTasks.length > 0)
		{
			this.drawTasksRecursive(tasks[i].childTasks, taskTree, taskData);
		}
	}
};

BX.GanttChart.prototype.drawDependencies = function()
{
	for (var dependency in this.dependencies)
	{
		if (this.dependencies.hasOwnProperty(dependency))
		{
			this.dependencies[dependency].draw();
		}
	}
};

BX.GanttChart.prototype.autoExpandTimeline = function(dates)
{
	this.timeline.autoExpandTimeline(dates);
};

BX.GanttChart.prototype.adjustChartContainer = function()
{
	if (this.chartContainer.element != null)
	{
		var contWidth = this.chartContainer.width;
		this.chartContainer.width = this.chartContainer.element.offsetWidth;
		this.chartContainer.pos = BX.pos(this.chartContainer.element);
		this.adjustChartContainerPadding();

		if (this.layout.root !== null && contWidth !== this.chartContainer.width)
		{
			this.layout.root.style.width = this.chartContainer.width + "px";
			this.getTimeline().renderHeader();
		}
	}
};

BX.GanttChart.prototype.adjustChartContainerPadding = function()
{
	if (this.chartContainer.element != null)
	{
		this.chartContainer.minPageX = this.chartContainer.pos.left + this.gutterOffset + this.chartContainer.padding;
		this.chartContainer.maxPageX = this.chartContainer.pos.left + this.chartContainer.width - this.chartContainer.padding;
	}
};

BX.GanttChart.prototype.addProject = function(id, name)
{
	if (id && this.projects[id])
	{
		return this.projects[id];
	}

	var project = this.__createProject(id, name);
	if (!project)
	{
		return null;
	}

	this.__addProject(project);

	if (this.layout.root != null)
	{
		this.__addProjectDynamic(project);
	}

	return project;
};

BX.GanttChart.prototype.addProjectFromJSON = function(json)
{
	if (!json || typeof(json) !== "object")
	{
		return null;
	}

	if (json.id && this.projects[json.id])
	{
		return this.projects[json.id];
	}

	var project = this.__createProject(json.id, json.name);
	if (project == null)
	{
		return null;
	}

	if (BX.type.isBoolean(json.opened))
	{
		project.opened = json.opened;
	}

	if (BX.type.isBoolean(json.canCreateTasks))
	{
		project.canCreateTasks = json.canCreateTasks;
	}

	if (BX.type.isBoolean(json.canEditTasks))
	{
		project.canEditTasks = json.canEditTasks;
	}

	if (BX.type.isArray(json.menuItems))
	{
		project.menuItems = json.menuItems;
	}

	this.__addProject(project);

	if (this.layout.root != null)
	{
		this.__addProjectDynamic(project);
	}

	return project;
};

BX.GanttChart.prototype.addProjectsFromJSON = function(arProjectJSON)
{
	if (BX.type.isArray(arProjectJSON))
	{
		for (var i = 0; i < arProjectJSON.length; i++)
		{
			this.addProjectFromJSON(arProjectJSON[i]);
		}
	}
};

BX.GanttChart.prototype.__createProject = function(id, name)
{
	if (!BX.type.isNumber(id) || !BX.type.isNotEmptyString(name))
	{
		return null;
	}

	return new GanttProject(this, id, name);
};

BX.GanttChart.prototype.__addProject = function(ganttProject)
{
	if (!ganttProject || typeof(ganttProject) != "object" || !(ganttProject instanceof GanttProject))
	{
		return null;
	}

	if (this.projects[ganttProject.id])
	{
		return this.projects[ganttProject.id];
	}

	this.projects[ganttProject.id] = ganttProject;

	return ganttProject;
};

BX.GanttChart.prototype.__addProjectDynamic = function(ganttProject)
{
	var item = ganttProject.createItem();
	var row = ganttProject.createBars();

	var projects = this.getSortedProjects();
	var position = null;
	for (var i = 0; i < projects.length; i++)
	{
		if (projects[i] === ganttProject)
		{
			position = i;
			break;
		}
	}

	if (position !== null && projects[position + 1])
	{
		this.layout.tree.insertBefore(item, projects[position + 1].layout.item);
	   	this.layout.timelineData.insertBefore(row, projects[position + 1].layout.row);
	}
	else
	{
		this.layout.tree.appendChild(item);
	   	this.layout.timelineData.appendChild(row);
	}
};

BX.GanttChart.prototype.addTask = function(id, name, status, dateCreated)
{
	if (id && this.tasks[id])
   		return this.tasks[id];

	var task = this.__createTask(id, name, status, dateCreated);
	if (!task)
		return null;

	task.setProject(0);
	this.__addTask(task);

	this.drawTasks();

	return task;
};

BX.GanttChart.prototype.addTaskFromJSON = function(taskJson, redraw)
{
	if (!taskJson || typeof(taskJson) != "object")
	{
		return null;
	}
	

	if (taskJson.id && this.tasks[taskJson.id])
	{
		return this.tasks[taskJson.id];
	}

	var task = this.__createTask(taskJson.id, taskJson.name, taskJson.status, taskJson.dateCreated);
	if (task == null)
	{
		return null;
	}

	task.setTaskFromJSON(taskJson);
	task.setProject(taskJson.projectId);
	this.__addTask(task);

	redraw = redraw !== false;
	if (taskJson.children && BX.type.isArray(taskJson.children))
	{
		for (var i = 0, l = taskJson.children.length; i < l; i++)
		{
			taskJson.children[i].parentTask = task;
			taskJson.children[i].parentTaskId = task.id;
			this.addTaskFromJSON(taskJson.children[i], false);
		}
	}

	if (redraw)
	{
		this.drawTasks();
	}

	return task;
};

BX.GanttChart.prototype.addTasksFromJSON = function(arTaskJSON)
{
	if (BX.type.isArray(arTaskJSON))
	{
		for (var i = 0; i < arTaskJSON.length; i++)
		{
			this.addTaskFromJSON(arTaskJSON[i], false);
		}

		this.drawTasks();
	}
};

BX.GanttChart.prototype.__createTask = function(id, name, status, dateCreated)
{
	if (!BX.type.isNumber(id) || !BX.type.isNotEmptyString(name) || !BX.type.isNotEmptyString(status))
		return null;

	return new GanttTask(this, id, name, status, dateCreated);
};

BX.GanttChart.prototype.__addTask = function(ganttTask)
{
	if (!ganttTask || typeof(ganttTask) != "object" || !(ganttTask instanceof GanttTask))
		return null;

	if (this.tasks[ganttTask.id])
		return this.tasks[ganttTask.id];

	this.tasks[ganttTask.id] = ganttTask;

	var taskMinDate = ganttTask.getMinDate();
	var taskMaxDate = ganttTask.getMaxDate();

	this.autoExpandTimeline([taskMinDate, taskMaxDate]);

	return ganttTask;
};

BX.GanttChart.prototype.removeTask = function(taskId)
{
	var task = this.getTaskById(taskId);
	if (!task)
		return;

	if (task.project != null)
	{
		task.project.removeTask(task);
	}

	var dependencies = task.getDependencies();
	for (var i = 0; i < dependencies.length; i++)
	{
		var dependency = dependencies[i];
		this.removeDependency(dependency);
	}

	this.__removeTaskChildren(task, task.childTasks, 1);

	if (task.parentTask !== null)
	{
		var parentTask = task.parentTask;
		parentTask.removeChild(task);
		parentTask.redraw();
	}

	delete this.tasks[taskId];

	if (this.layout.root != null)
	{
		this.layout.tree.removeChild(task.layout.item);
		this.layout.timelineData.removeChild(task.layout.row);
	}

	this.drawDependencies();

	BX.onCustomEvent(this, "onTaskDelete", [task]);
};

BX.GanttChart.prototype.__removeTaskChildren = function(deletedTask, tasks, depthLevel)
{
	if (!BX.type.isArray(tasks) || tasks.length < 1)
		return;

	depthLevel = BX.type.isNumber(depthLevel) && depthLevel > 0  ? depthLevel : 1;

	for (var i = 0, length = tasks.length; i < length; i++)
	{
		if (depthLevel == 1)
		{
			if (deletedTask.parentTask != null)
				deletedTask.parentTask.addChild(tasks[i]);
			else
			{
				tasks[i].depthLevel = depthLevel;
				tasks[i].parentTask = null;
				tasks[i].project.addTask(tasks[i]);
			}
		}
		else
			tasks[i].depthLevel = depthLevel;

		this.__removeTaskChildren(deletedTask, tasks[i].childTasks, (depthLevel == 1 && deletedTask.parentTask != null ? tasks[i].depthLevel : depthLevel) + 1);
		tasks[i].redraw();
	}
};

BX.GanttChart.prototype.updateTask = function(taskId, json)
{

	var task = this.getTaskById(taskId);
	if (!task)
	{
		return false;
	}

	if (typeof(json.parentTaskId) !== "undefined" && json.parentTaskId != task.parentTaskId && this.treeMode)
	{
		// BX.reload();
		return false;
	}

	if (typeof(json.projectId) !== "undefined" && json.projectId != task.projectId)
	{
		// BX.reload();
		return false;
	}

	delete json.parentTaskId;
	delete json.projectId;

	task.setTaskFromJSON(json);
	task.updateLags();
	task.redraw();

	BX.onCustomEvent(this, "onTaskUpdate", [task]);
	return true;
};

BX.GanttChart.prototype.moveTask = function(taskId, targetId, after)
{
	var task = this.getTaskById(taskId);
	var target = this.getTaskById(targetId);

	if (!task || !target || task === target || target.isChildOf(task))
	{
		return;
	}

	if (task.parentTask)
	{
		task.parentTask.removeChild(task);
	}
	else
	{
		task.project.removeTask(task);
	}

	var children = target.parentTask ? target.parentTask.childTasks : target.project.tasks;
	var index = BX.util.array_search(target, children);
	if (index < 0)
	{
		return;
	}

	if (after === true)
	{
		children.splice(index + 1, 0, task);
	}
	else
	{
		children.splice(index, 0, task);
	}

	task.parentTask = target.parentTask;
	task.parentTaskId = target.parentTaskId;
	task.depthLevel = target.depthLevel;
	task.projectId = target.projectId;
	task.project = target.project;

	task.shiftChildren();
	this.drawTasks();
};

BX.GanttChart.prototype.moveTaskToProject = function(taskId, projectId)
{
	var task = this.getTaskById(taskId);
	var project = this.getProjectById(projectId);

	if (!task || !project)
	{
		return;
	}

	if (task.parentTask)
	{
		task.parentTask.removeChild(task);
	}
	else
	{
		task.project.removeTask(task);
	}

	project.addTask(task);

	task.parentTask = null;
	task.parentTaskId = 0;
	task.depthLevel = 1;
	task.projectId = project.id;
	task.project = project;

	task.shiftChildren();
	this.drawTasks();
};

BX.GanttChart.prototype.indentTask = function(taskId)
{
	var task = this.getTaskById(taskId);
	if (!task || !task.canEdit || !this.treeMode)
	{
		return;
	}

	var children = task.parentTask ? task.parentTask.childTasks : task.project.tasks;
	var index = BX.util.array_search(task, children);
	if (index < 1)
	{
		return;
	}

	var newParent = children[index - 1];
	if (newParent.hasChildren === false && newParent.childTasks.length < 1)
	{
		newParent.addChild(task);
		newParent.expand();
		this.drawTasks();
		fireEvent(this);
	}
	else
	{
		newParent.expand(false, BX.proxy(function() {
			newParent.addChild(task);
			this.drawTasks();
			fireEvent(this);
		}, this));
	}

	function fireEvent(chart)
	{
		var prevTask = task.getPreviousTask();
		BX.onCustomEvent(chart, "onTaskMove", [
			taskId,
			prevTask ? prevTask.id : task.parentTaskId,
			false,
			null,
			newParent.id
		]);
	}
};

BX.GanttChart.prototype.outdentTask = function(taskId)
{
	var task = this.getTaskById(taskId);
	if (!task || !task.parentTask || !task.canEdit || !this.treeMode)
	{
		return;
	}

	var originalParentId = task.parentTaskId;
	var originalProjectId = task.projectId;

	this.moveTask(taskId, task.parentTask.id, true);

	BX.onCustomEvent(this, "onTaskMove", [
		taskId,
		originalParentId,
		false,
		originalProjectId !== task.projectId ? task.projectId : null,
		originalParentId !== task.parentTaskId ? task.parentTaskId : null
	]);
};

BX.GanttChart.prototype.autoSchedule = function()
{
	for (var i = 0, length = this.tasks.length; i < length; i++)
	{
		this.tasks[i].schedule();
	}
};

BX.GanttChart.prototype.addDependency = function(from, to, type)
{
	if (!this.isValidDependency(from, to))
	{
		return null;
	}

	var id = this.getDependencyId(from, to);
	if (this.dependencies[id])
	{
		return this.dependencies[id];
	}

	var dependency = new GanttDependency(this, id, from, to, type);
	this.dependencies[dependency.id] = dependency;

	var predecessor = this.getTaskById(from);
	var successor = this.getTaskById(to);
	predecessor.addSuccessor(dependency);
	successor.addPredecessor(dependency);

	if (this.layout.root !== null)
	{
		dependency.draw();
	}

	return dependency;
};

BX.GanttChart.prototype.getDependency = function(from, to)
{
	var id = this.getDependencyId(from, to);
	if (this.dependencies[id])
	{
		return this.dependencies[id];
	}

	return null;
};

BX.GanttChart.prototype.getDependencyId = function(from, to)
{
	var fromTask = this.getTaskById(from);
	var toTask = this.getTaskById(to);

	return fromTask && toTask ? fromTask.id + "_" + toTask.id : null;
};

BX.GanttChart.prototype.isValidDependency = function(from, to)
{
	if (!BX.type.isNumber(from) || !BX.type.isNumber(to) || from === to)
	{
		return false;
	}

	var predecessor = this.getTaskById(from);
	var successor = this.getTaskById(to);
	if (!predecessor || !successor || !predecessor.isRealDateEnd || !successor.isRealDateEnd)
	{
		return false;
	}

	if (predecessor.isChildOf(successor) || successor.isChildOf(predecessor))
	{
		return false;
	}

	return !this.isCircularDependency(from, to);
};

BX.GanttChart.prototype.isCircularDependency = function(from, to)
{
	var predecessor = this.getTaskById(from);
	var successor = this.getTaskById(to);

	return predecessor && successor && (predecessor.hasSuccessor(to) || successor.hasSuccessor(from))
};

BX.GanttChart.prototype.canAddDependency = function(from, to)
{
	var predecessor = this.getTaskById(from);
	var successor = this.getTaskById(to);

	return predecessor && successor && predecessor.canEditPlanDates && successor.canEditPlanDates;
};

BX.GanttChart.prototype.addDependencyFromJSON = function(json)
{
	if (!json || typeof(json) !== "object")
	{
		return null;
	}

	return this.addDependency(json.from, json.to, json.type);
};

BX.GanttChart.prototype.addDependenciesFromJSON = function(arDependencyJSON)
{
	if (BX.type.isArray(arDependencyJSON))
	{
		for (var i = 0; i < arDependencyJSON.length; i++)
		{
			this.addDependencyFromJSON(arDependencyJSON[i]);
		}
	}
};

BX.GanttChart.prototype.removeDependency = function(dependency)
{
	if (!dependency || !dependency instanceof GanttDependency)
	{
		return;
	}

	var predecessor = this.getTaskById(dependency.from);
	var successor = this.getTaskById(dependency.to);

	predecessor && predecessor.removeSuccessor(dependency);
	successor && successor.removePredecessor(dependency);

	dependency.clear();
	delete this.dependencies[dependency.id];
};

/**
 *
 * @return {Timeline}
 */
BX.GanttChart.prototype.getTimeline = function()
{
	return this.timeline;
};

/**
 * Returns task by id
 * @param {Number} taskId
 * @returns {GanttTask}
 */
BX.GanttChart.prototype.getTaskById = function(taskId)
{
	if (this.tasks[taskId])
		return this.tasks[taskId];

	return null;
};

BX.GanttChart.prototype.getProjectById = function(projectId)
{
	if (this.projects[projectId])
		return this.projects[projectId];

	return null;
};

BX.GanttChart.prototype.getDefaultProject = function()
{
	return this.getProjectById(0);
};

BX.GanttChart.prototype.getSortedProjects = function()
{
	var projects = [];
	for (var projectId in this.projects)
	{
		projects.push(this.projects[projectId]);
	}

	return projects.sort(function(a,b) { return a.id - b.id });
};

BX.GanttChart.prototype.getLastProject = function()
{
	var sortedProjects = this.getSortedProjects();
	return sortedProjects[sortedProjects.length - 1];
};

BX.GanttChart.prototype.getPreviousProject = function(currentProjectId)
{
	var projectId = 0;
	var projects = this.getSortedProjects();
	for (var i = 0; i < projects.length; i++)
	{
		if (projects[i].id === currentProjectId)
		{
			break;
		}
		projectId = projects[i].id;
	}

	return this.getProjectById(projectId);
};

/**
 * Returns root previous task
 * @param {Number} sourceTaskId
 * @returns {GanttTask|null}
 */
BX.GanttChart.prototype.getPreviousTask = function(sourceTaskId)
{
	var sourceTask = this.getTaskById(sourceTaskId);
	if (!sourceTask)
	{
		return null;
	}
	
	var prevTask = sourceTask.getPreviousTask();
	if (prevTask)
	{
		return prevTask;
	}
	else if (sourceTask.projectId === 0)
	{
		return null;
	}
	
	var prevProjects = [this.getDefaultProject()];
	var projects = this.getSortedProjects();
	for (var i = 0; i < projects.length; i++)
	{
		if (projects[i].id === sourceTask.projectId)
		{
			break;
		}

		prevProjects.unshift(projects[i]);
	}

	for (i = 0; i < prevProjects.length; i++)
	{
		var prevProject = prevProjects[i];
		var childrenCnt = prevProject.tasks.length;
		if (childrenCnt)
		{
			return prevProject.tasks[childrenCnt - 1];
		}
	}

	return null;
};

/**
 * Returns root next task
 * @param {Number} sourceTaskId
 * @returns {GanttTask|null}
 */
BX.GanttChart.prototype.getNextTask = function(sourceTaskId)
{
	var sourceTask = this.getTaskById(sourceTaskId);
	if (!sourceTask)
	{
		return null;
	}

	var nextTask = sourceTask.getNextTask();
	if (nextTask)
	{
		return nextTask;
	}

	var nextProjects = [];
	var projects = this.getSortedProjects();
	for (var i = 0, found = false; i < projects.length; i++)
	{
		if (found)
		{
			nextProjects.push(projects[i]);
		}

		if (projects[i].id === sourceTask.projectId)
		{
			found = true;
		}
	}

	for (i = 0; i < nextProjects.length; i++)
	{
		var nextProject = nextProjects[i];
		var childrenCnt = nextProject.tasks.length;
		if (childrenCnt)
		{
			return nextProject.tasks[0];
		}
	}

	return null;
};

BX.GanttChart.prototype.profile = function(title)
{
	if (typeof(console) !== "undefined")
	{
		var currentTime = new Date().getTime();
		console.log(title + ": " + ((currentTime - this.benchTime) / 1000) + " sec. ");
		this.benchTime = new Date().getTime();
	}
};

BX.GanttChart.prototype.normalizeGutterOffset = function(offset)
{
	var minOffset = 2;
	var maxOffset = this.chartContainer.width - 100;
	return Math.min(Math.max(offset, minOffset), maxOffset > minOffset ? maxOffset : minOffset);
};

BX.GanttChart.prototype.setGutterOffset = function(offset)
{
	this.gutterOffset = this.normalizeGutterOffset(offset);
	this.layout.list.style.width = this.gutterOffset + "px";
	return this.gutterOffset;
};

/*==========Handlers==========*/
BX.GanttChart.prototype.onGutterMouseDown = function(event)
{
	event = event || window.event;
	if (!BX.GanttChart.isLeftClick(event))
		return;

	BX.bind(document, "mouseup", BX.proxy(this.onGutterMouseUp, this));
	BX.bind(document, "mousemove", BX.proxy(this.onGutterMouseMove, this));

	this.gutterClientX = event.clientX;
	this.allowRowHover = false;

	document.onmousedown = BX.False;
	document.body.onselectstart = BX.False;
	document.body.ondragstart = BX.False;
	document.body.style.MozUserSelect = "none";
	document.body.style.cursor = "ew-resize";
};

BX.GanttChart.prototype.onGutterMouseUp = function(event)
{
	event = event || window.event;

	BX.unbind(document, "mouseup", BX.proxy(this.onGutterMouseUp, this));
	BX.unbind(document, "mousemove", BX.proxy(this.onGutterMouseMove, this));

	this.allowRowHover = true;

	document.onmousedown = null;
	document.body.onselectstart = null;
	document.body.ondragstart = null;
	document.body.style.MozUserSelect = "";
	document.body.style.cursor = "default";

	BX.onCustomEvent(this, "onGutterResize", [this.gutterOffset]);
};

BX.GanttChart.prototype.onGutterMouseMove = function(event)
{
	event = event || window.event;

	this.setGutterOffset(this.gutterOffset + (event.clientX - this.gutterClientX));
	this.adjustChartContainerPadding();
	this.gutterClientX = event.clientX;
};

BX.GanttChart.prototype.getTimelineDataOffset = function()
{
	if (this.timelineDataOffset === null)
	{
		this.timelineDataOffset = this.layout.timelineData.offsetTop;
	}

	return this.timelineDataOffset;
};

BX.GanttChart.prototype.onTimelineMouseDown = function(event)
{
	event = event || window.event;
	if (!BX.GanttChart.isLeftClick(event))
		return;

	//c onsole.log("onTimelineMouseDown");
	this.dragClientX = event.clientX;

	BX.TaskQuickInfo.hide();

	BX.GanttChart.startDrag(document.body, {
		mouseup : BX.proxy(this.onTimelineMouseUp, this),
		mousemove : BX.proxy(this.onTimelineMouseMove, this)
	});

	BX.PreventDefault(event);
};

BX.GanttChart.prototype.onTimelineMouseUp = function(event)
{
	event = event || window.event;
	//c onsole.log("onTimelineMouseUp");
	BX.GanttChart.stopDrag(document.body, {
		mouseup : BX.proxy(this.onTimelineMouseUp, this),
		mousemove : BX.proxy(this.onTimelineMouseMove, this)
	});

	this.dragClientX = 0;
};

BX.GanttChart.prototype.onTimelineMouseMove = function(event)
{
	event = event || window.event;
	//c onsole.log("onTimelineMouseMove");
	var scrollLeft = this.layout.timeline.scrollLeft + (this.dragClientX - event.clientX);
	this.layout.timeline.scrollLeft = scrollLeft < 0 ? 0 : scrollLeft;

	this.dragClientX = event.clientX;
};

BX.GanttChart.prototype.onWindowResize = function(event)
{
	this.adjustChartContainer();
};

BX.GanttChart.prototype.onPrintClick = function(event) 
{
	if (this.printSettings === null)
	{
		this.printSettings = new BX.Scheduler.PrintSettings(this.getTimeline());
	}
	this.printSettings.show();
};

BX.GanttChart.prototype.onZoomInClick = function(event)
{
	event = event || window.event;
	this.zoomIn();
};

BX.GanttChart.prototype.onZoomOutClick = function(event)
{
	event = event || window.event;
	this.zoomOut();
};

BX.GanttChart.prototype.handleTimelineScroll = function()
{
	this.timeline.renderHeader();
};

/*========Static Methods=====*/
BX.GanttChart.convertDateToUTC = function(date)
{
	if (!date)
		return null;
	return new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate(), date.getHours(), date.getMinutes(), date.getSeconds(), 0));
};

BX.GanttChart.convertDateFromUTC = function(date)
{
	if (!date)
		return null;
	return new Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate(), date.getUTCHours(), date.getUTCMinutes(), date.getUTCSeconds(), 0);
};

BX.GanttChart.allowSelection = function(domElement)
{
	if (!BX.type.isDomNode(domElement))
		return;

	domElement.onselectstart = null;
	domElement.ondragstart = null;
	domElement.style.MozUserSelect = "";
};

BX.GanttChart.isLeftClick = function(event)
{
	if (!event.which && event.button !== undefined)
	{
		if (event.button & 1)
			event.which = 1;
		else if (event.button & 4)
			event.which = 2;
		else if (event.button & 2)
			event.which = 3;
		else
			event.which = 0;
	}

	return event.which == 1 || (event.which == 0 && BX.browser.IsIE());
};

BX.GanttChart.denySelection = function(domElement)
{
	if (!BX.type.isDomNode(domElement))
		return;

	domElement.onselectstart = BX.False;
	domElement.ondragstart = BX.False;
	domElement.style.MozUserSelect = "none";
};

BX.GanttChart.startDrag = function(domElement, events, cursor)
{
	if (!domElement)
		return;

	if (events)
	{
		for (var eventId in events)
			BX.bind(document, eventId, events[eventId]);
	}

	BX.GanttChart.denySelection(domElement);
	domElement.style.cursor = BX.type.isString(cursor) ? cursor : "ew-resize";
};

BX.GanttChart.stopDrag = function(domElement, events, cursor)
{
	if (!domElement)
		return;

	if (events)
	{
		for (var eventId in events)
			BX.unbind(document, eventId, events[eventId]);
	}

	BX.GanttChart.allowSelection(domElement);

	domElement.style.cursor = BX.type.isString(cursor) ? cursor : "default";
};

/**
 *
 * @param {BX.GanttChart} chart
 * @param {Number} id
 * @param {String} name
 * @constructor
 */
var GanttProject = function(chart, id, name)
{
	this.chart = chart;
	this.id = id;
	this.name = name;
	this.tasks = [];
	this.menuItems = [];
	this.layout = {
		item : null,
		row : null
	};

	this.opened = true;
	this.canCreateTasks = true;
	this.canEditTasks = true;
};

GanttProject.prototype.addTask = function(task)
{
	if (!task || typeof(task) != "object" || !(task instanceof GanttTask))
	{
		return false;
	}

	if (task.project !== null && task.project !== this)
	{
		task.project.removeTask(task);
	}

	task.project = this;
	task.projectId = this.id;

	if (task.parentTask === null)
	{
		task.depthLevel = 1;
		this.tasks.push(task);
	}

	return true;
};

GanttProject.prototype.removeTask = function(task)
{
	for (var i = 0; i < this.tasks.length; i++)
	{
		if (this.tasks[i] === task)
		{
			this.tasks.splice(i, 1);
			break;
		}
	}
};

GanttProject.prototype.collapse = function()
{
	this.opened = false;
	BX.addClass(this.layout.item, "task-gantt-item-closed");
	for (var i = 0; i < this.tasks.length; i++)
	{
		this.tasks[i].hide();
		this.tasks[i].collapse(true);
	}

	BX.onCustomEvent(this.chart, "onProjectOpen", [this]);

	this.chart.drawDependencies();
};

GanttProject.prototype.expand = function()
{
	this.opened = true;
	BX.removeClass(this.layout.item, "task-gantt-item-closed");
	for (var i = 0; i < this.tasks.length; i++)
	{
		this.tasks[i].show();

		if (this.tasks[i].opened)
			this.tasks[i].expand();
	}

	BX.onCustomEvent(this.chart, "onProjectOpen", [this]);

	this.chart.drawDependencies();
};

GanttProject.prototype.createBars = function()
{
	if (this.layout.row !== null)
	{
		return this.layout.row;
	}

	this.layout.row = BX.create("div", {
		props : { className : "task-gantt-timeline-row", id : "task-gantt-timeline-row-p" + this.id },
		events : {
			mouseover : BX.proxy(this.onRowMouseOver, this),
			mouseout : BX.proxy(this.onRowMouseOut, this),
			contextmenu : BX.proxy(this.onRowContextMenu, this)
		}
	});

	return this.layout.row;
};

GanttProject.prototype.createItem = function()
{
	if (this.layout.item !== null)
	{
		return this.layout.item;
	}

	var itemClass = "task-gantt-item task-gantt-item-project";
	if (!this.opened)
	{
		itemClass += " task-gantt-item-closed";
	}

	this.layout.item = BX.create("div", {
		props : { className : itemClass, id : "task-gantt-item-p" + this.id },
		attrs : { "data-project-id": this.id },
		events : {
			mouseover : BX.proxy(this.onItemMouseOver, this),
			mouseout : BX.proxy(this.onItemMouseOut, this),
			click : BX.proxy(this.onFoldingClick, this)
		},
		children : [
			BX.create("span", { props : { className : "task-gantt-item-project-folding"}}),
			BX.create("span", { props : { className: "task-gantt-item-name" }, html : this.name }),
			(this.layout.menu = this.menuItems.length > 0
				?
				BX.create("div", { props: { className: "task-gantt-item-actions" }, children: [
					BX.create("span", { props : { className: "task-gantt-item-menu"}, events : {
						click : BX.proxy(this.onItemMenuClick, this)
					}})
				]})
				: null
			)
		]
	});

	//You can't drop task outside a project
	if (!this.chart.oneGroupMode)
	{
		this.chart.dragger.registerProject(this.layout.item);
	}

	return this.layout.item;
};

GanttProject.prototype.onFoldingClick = function(event)
{
	if (this.opened)
	{
		this.collapse();
	}
	else
	{
		this.expand();
	}
};

GanttProject.prototype.onItemMenuClick = function(event)
{
	var menu = BX.PopupMenu.create("p" + this.id, this.layout.menu, this.menuItems, {
		offsetLeft : 8,
		bindOptions : { forceBindPosition : true },
		events : { onPopupClose: BX.proxy(this.onItemMenuClose, this) },
		chart : this.chart,
		project : this
	});

	menu.getPopupWindow().setBindElement(this.layout.menu);
	menu.show();

	this.denyItemsHover();
	BX.addClass(this.layout.menu, "task-gantt-item-menu-selected");
};

GanttProject.prototype.onItemMenuClose = function(popupWindow, event)
{
	this.allowItemsHover(event);
	BX.removeClass(this.layout.menu, "task-gantt-item-menu-selected");
};

GanttProject.prototype.onItemMouseOver = function(event)
{
	if (!this.chart.allowRowHover)
		return;
	BX.addClass(this.layout.item, "task-gantt-item-tree-hover");
	BX.addClass(this.layout.row, "task-gantt-timeline-row-hover");
};

GanttProject.prototype.onItemMouseOut = function(event)
{
	if (!this.chart.allowRowHover)
		return;
	BX.removeClass(this.layout.item, "task-gantt-item-tree-hover");
	BX.removeClass(this.layout.row, "task-gantt-timeline-row-hover");
};

GanttProject.prototype.onRowMouseOver = function(event)
{
	if (!this.chart.allowRowHover)
		return;
	BX.addClass(this.layout.row, "task-gantt-timeline-row-hover");
};

GanttProject.prototype.onRowMouseOut = function(event)
{
	if (!this.chart.allowRowHover)
		return;

	BX.removeClass(this.layout.row, "task-gantt-timeline-row-hover");
};

GanttProject.prototype.onRowContextMenu = function(event)
{
	event = event || window.event;
	if (this.menuItems.length < 1 || event.ctrlKey)
		return;

	BX.TaskQuickInfo.hide();

	var menu = BX.PopupMenu.create("p" + this.id, event, this.menuItems, {
   		offsetLeft : 1,
		autoHide : true,
		closeByEsc : true,
   		bindOptions : { forceBindPosition : true },
   		events : { onPopupClose: BX.proxy(this.onItemMenuClose, this) },
   		chart : this.chart,
   		project : this
   	});

	menu.getPopupWindow().setBindElement(event);
	menu.show();

	var target = event.target || event.srcElement;
	if (target && BX.hasClass(target, "task-gantt-timeline-row") && BX.type.isNotEmptyString(target.id))
	{
		var project = this.chart.getProjectById(target.id.substr("task-gantt-timeline-row-p".length));
		if (project != null)
		   	BX.addClass(project.layout.row, "task-gantt-timeline-row-hover");
	}

   	this.denyItemsHover();
	BX.PreventDefault(event);
};

GanttProject.prototype.denyItemsHover = function()
{
	this.chart.allowRowHover = false;
};

GanttProject.prototype.allowItemsHover = function(event)
{
	this.chart.allowRowHover = true;

	event = event || window.event || null;
	if (!event)
		return;

	var target = event.target || event.srcElement;
	if ( target != this.layout.row && target.parentNode != this.layout.row && target.parentNode.parentNode != this.layout.row &&
		 target != this.layout.item && target.parentNode != this.layout.item
	)
	{
		BX.removeClass(this.layout.item, "task-gantt-item-tree-hover");
		BX.removeClass(this.layout.row, "task-gantt-timeline-row-hover");
	}
	else if (target != this.layout.item && target.parentNode != this.layout.item)
	{
		BX.removeClass(this.layout.item, "task-gantt-item-tree-hover");
	}

};

/**
 *
 * @param {BX.GanttChart} chart
 * @param {Number} id
 * @param {String} name
 * @param {String} status
 * @param {Date} dateCreated
 * @constructor
 */
var GanttTask = function(chart, id, name, status, dateCreated)
{
	this.chart = chart;
	this.id = id;
	this.name = name;
	this.setStatus(status);

	this.dateCreated = BX.Tasks.Date.isDate(dateCreated) ? BX.GanttChart.convertDateToUTC(dateCreated) : this.chart.currentDatetime;

	this.dateStart = null;
	this.isRealDateStart = false;

	this.dateEnd = null;
	this.isRealDateEnd = false;

	this.__setDateStart(this.dateCreated, false, true);

	this.dateStarted = null;
	this.dateCompleted = null;
	this.dateDeadline = null;

	this.matchWorkTime = true;
	this.duration = null;

	this.files = [];
	this.responsible = "";
	this.responsibleId = 0;
	this.director = "";
	this.directorId = 0;
	this.priority = 1;

	this.depthLevel = 1;

	this.parentTask = null;
	this.parentTaskId = 0;
	this.childTasks = [];
	this.hasChildren = false;

	this.projectId = 0;
	this.project = null;

	this.predecessors = {};
	this.succesors = {};

	this.menuItems = [];

	this.url = "";
	this.details = null;

	this.layout = {
		item : null,
		row : null,
		name : null,
		menu : null,
		planBar : null,
		realBar : null,
		deadlineBar : null,
		deadlineSlider : null,
		deadlineOverdue : null,
		realOverdue : null,
		completeFlag : null,
		pointStart: null,
		pointEnd: null
	};

	this.opened = false;
	this.status = null;

	this.canEditDeadline = true;
	this.canEditPlanDates = true;
	this.canEdit = true;

	this.projectPlanFromSubTasks = false;
	this.isParent = false;

	this.resizerOffsetX = 0;
	this.resizerOffsetY = 0;
	this.resizerChangePos = false;
	this.resizerPageX = 0;
	this.resizerPageY = 0;

	this.autoResizeIntID = null;
	this.autoResizeTimeout = 50;
	this.autoResizeCallback = null;
	this.autoResizeEvent = null;

	this.highlightTimeout = null;
};

GanttTask.prototype.addChild = function(child)
{
	if (!child || typeof(child) != "object" || !(child instanceof GanttTask) || child === this || child.parentTask === this)
	{
		return false;
	}

	if (this.isChildOf(child))
	{
		return false;
	}

	if (child.parentTask !== null && child.parentTask !== this)
	{
		child.parentTask.removeChild(child);
	}

	if (child.parentTask === null && child.project !== null)
	{
		child.project.removeTask(child);
	}

	this.childTasks.push(child);
	this.hasChildren = true;

	child.parentTask = this;
	child.parentTaskId = this.id;
	child.depthLevel = this.depthLevel + 1;
	child.setProject(this.projectId);
	child.shiftChildren();

	return true;
};

GanttTask.prototype.isChildOf = function(parent)
{
	var parentTask = this.parentTask;
	while (parentTask !== null)
	{
		if (parentTask === parent)
		{
			return true;
		}
		parentTask = parentTask.parentTask;
	}

	return false;
};

GanttTask.prototype.isGroup = function()
{
	return this.projectPlanFromSubTasks &&
		   this.isRealDateEnd &&
		   this.canEditPlanDates &&
		   (this.childTasks.length > 0 || this.hasChildren);
};

GanttTask.prototype.shiftChildren = function()
{
	var children = this.childTasks;
	for (var i = 0, l = children.length; i < l; i++)
	{
		children[i].depthLevel = children[i].parentTask.depthLevel + 1;
		children[i].projectId = children[i].parentTask.projectId;
		children[i].project = children[i].parentTask.project;
		children[i].shiftChildren();
	}
};

GanttTask.prototype.addChildren = function(tasks)
{
	for (var i = 0; i < tasks.length; i++)
	{
		this.addChild(tasks[i]);
	}
};

GanttTask.prototype.removeChild = function(childTask)
{
	for (var i = 0; i < this.childTasks.length; i++)
	{
		if (this.childTasks[i] == childTask)
		{
			this.childTasks.splice(i, 1);
			childTask.depthLevel = 1;
			childTask.parentTask = null;
			childTask.parentTaskId = 0;
			break;
		}
	}

	this.hasChildren = this.childTasks.length > 0;
};

GanttTask.prototype.addPredecessor = function(dependency)
{
	if (dependency instanceof GanttDependency)
	{
		this.predecessors[dependency.id] = dependency;
	}
};

GanttTask.prototype.addSuccessor = function(dependency)
{
	if (dependency instanceof GanttDependency)
	{
		this.succesors[dependency.id] = dependency;
	}
};

GanttTask.prototype.removePredecessor = function(dependency)
{
	if (dependency instanceof GanttDependency)
	{
		delete this.predecessors[dependency.id];
	}
};

GanttTask.prototype.removeSuccessor = function(dependency)
{
	if (dependency instanceof GanttDependency)
	{
		delete this.succesors[dependency.id];
	}
};

GanttTask.prototype.hasSuccessor = function(successorId)
{
	for (var linkId in this.succesors)
	{
		if (this.succesors.hasOwnProperty(linkId))
		{
			var successor = this.chart.getTaskById(this.succesors[linkId].to);
			if (successor.id === successorId)
			{
				return true;
			}

			if (successor.hasSuccessor(successorId))
			{
				return true;
			}
		}
	}

	return false;
};
GanttTask.prototype.getDependencies = function()
{
	var dependencies = [];
	for (var id in this.predecessors)
	{
		if (this.predecessors.hasOwnProperty(id))
		{
			dependencies.push(this.predecessors[id]);
		}
	}

	for (id in this.succesors)
	{
		if (this.succesors.hasOwnProperty(id))
		{
			dependencies.push(this.succesors[id]);
		}
	}

	return dependencies;
};

GanttTask.prototype.getNextTask = function()
{
	var children = this.parentTask ? this.parentTask.childTasks : this.project.tasks;
	for (var i = 0; i < children.length; i++)
	{
		if (children[i] == this)
		{
			return children[i+1] ? children[i+1] : null;
		}
	}
};

GanttTask.prototype.getPreviousTask = function()
{
	var children = this.parentTask ? this.parentTask.childTasks : this.project.tasks;
	for (var i = 0; i < children.length; i++)
	{
		if (children[i] == this)
		{
			return i > 0 && children[i-1] ? children[i-1] : null;
		}
	}
};

GanttTask.prototype.setTaskFromJSON = function(json)
{
	if (typeof(json) != "object")
	{
		return null;
	}

	this.setStatus(json.status);
	this.setDateCompleted(json.dateCompleted);
	this.setDateDeadline(json.dateDeadline);
	this.setDateStarted(json.dateStarted);

	if (BX.Tasks.Date.isDate(json.dateEnd) && json.dateEnd > this.dateStart)
	{
		this.setDateEnd(json.dateEnd);
		this.setDateStart(json.dateStart);
	}
	else
	{
		this.setDateStart(json.dateStart);
		this.setDateEnd(json.dateEnd);
	}

	this.setMatchWorkTime(json.matchWorkTime);
	this.setMenuItems(json.menuItems);
	this.setName(json.name);
	this.setUrl(json.url);
	this.setDetails(json.details || function(){
		if(BX.type.isNotEmptyString(this.url))
		{
			window.top.location = this.url;
		}
	});
	this.setFiles(json.files);

	if (BX.type.isBoolean(json.opened))
	{
		this.opened = json.opened;
	}

	if (BX.type.isBoolean(json.hasChildren))
	{
		this.hasChildren = json.hasChildren;
	}

	if (BX.type.isBoolean(json.canEditDeadline))
	{
		this.canEditDeadline = json.canEditDeadline;
	}

	if (BX.type.isBoolean(json.canEditPlanDates))
	{
		this.canEditPlanDates = json.canEditPlanDates;
	}

	if (BX.type.isBoolean(json.canEdit))
	{
		this.canEdit = json.canEdit;
	}

	if (json.parameters && BX.type.isBoolean(json.parameters.projectPlanFromSubTasks))
	{
		this.projectPlanFromSubTasks = json.parameters.projectPlanFromSubTasks;
	}

	if (BX.type.isBoolean(json.isParent))
	{
		this.isParent = json.isParent;
	}

	if (json.responsible)
	{
		this.responsible = json.responsible;
	}

	if (json.responsibleId)
	{
		this.responsibleId = json.responsibleId;
	}

	if (json.director)
	{
		this.director = json.director;
	}



	if (json.directorId)
	{
		this.directorId = json.directorId;
	}

	if (BX.type.isNumber(json.priority))
	{
		this.priority = json.priority;
	}

	if (json.parentTask != null)
	{
		json.parentTask.addChild(this);
		json.parentTask = null;
	}
	else if (json.parentTaskId)
	{
		var parentTask = this.chart.getTaskById(json.parentTaskId);
		if (parentTask)
		{
			parentTask.addChild(this);
		}
	}

	return this;
};

GanttTask.prototype.setDateStart = function(date)
{
	if (date === null)
	{
		this.isRealDateStart = false;
		return this.__setDateStart(this.dateCreated, false, true);
	}

	return this.__setDateStart(date, true, false);
};

GanttTask.prototype.__setDateStart = function(date, isRealStartDate, isUTC)
{
	if (!BX.Tasks.Date.isDate(date))
		return;

	isUTC = !!isUTC;
	if (!isUTC)
		date = BX.GanttChart.convertDateToUTC(date);

	if (this.isRealDateEnd && date > this.dateEnd)
		return;

	this.isRealDateStart = isRealStartDate;
	this.dateStart = new Date(date.getTime());

	if (!this.isRealDateEnd)
	{
		if (this.status === "completed")
		{
			if (this.dateCompleted == null ||
				this.dateCompleted <= this.dateStart ||
				BX.Tasks.Date.getDurationInHours(this.dateStart, this.dateCompleted) < this.chart.endlessBarDefaultDuration
			)
			{
				this.dateEnd = BX.Tasks.Date.add(this.dateStart, BX.Tasks.Date.Unit.Hour, this.chart.endlessBarDefaultDuration);
			}
			else
			{
				this.dateEnd = this.dateCompleted;
			}
		}
		else
		{
			this.dateEnd = BX.Tasks.Date.ceilDate(this.chart.currentDatetime, BX.Tasks.Date.Unit.Day, 1);
			if (this.dateEnd <= this.dateStart ||
				BX.Tasks.Date.getDurationInHours(this.dateStart, this.dateEnd) < this.chart.endlessBarDefaultDuration)
			{
				this.dateEnd = BX.Tasks.Date.add(this.dateStart, BX.Tasks.Date.Unit.Hour, this.chart.endlessBarDefaultDuration);
			}
		}
	}
};

GanttTask.prototype.setDateEnd = function(date)
{
	if (date === null)
	{
		this.dateEnd = null;
		this.isRealDateEnd = false;
		this.__setDateStart(this.dateStart, this.isRealDateStart, true);
	}
	else if (BX.Tasks.Date.isDate(date))
	{
		date = BX.GanttChart.convertDateToUTC(date);
		if (date > this.dateStart)
		{
			this.dateEnd = new Date(date.getTime());
			this.isRealDateEnd = true;
		}
	}
};

GanttTask.prototype.setDateCompleted = function(date)
{
	if (date === null)
	{
		this.dateCompleted = null;
	}
	else if (BX.Tasks.Date.isDate(date))
	{
		this.dateCompleted = BX.GanttChart.convertDateToUTC(date);
		if (this.status == "completed")
		{
			if (!this.isRealDateEnd && this.dateCompleted <= this.dateStart)
			{
				this.dateEnd = BX.Tasks.Date.add(this.dateStart, BX.Tasks.Date.Unit.Hour, this.chart.endlessBarDefaultDuration);
			}
			else
			{
				this.dateEnd = this.dateCompleted;
			}
		}
	}
};

GanttTask.prototype.setDateStarted = function(date)
{
	if (date === null)
	{
		this.dateStarted = null;
	}
	else if (BX.Tasks.Date.isDate(date))
	{
		this.dateStarted = BX.GanttChart.convertDateToUTC(date);
	}
};

GanttTask.prototype.setDateDeadline = function(date)
{
	if (date === null)
	{
		this.dateDeadline = null;
	}
	else if (BX.Tasks.Date.isDate(date))
	{
		this.dateDeadline = BX.GanttChart.convertDateToUTC(date);
	}
};

GanttTask.prototype.setRealDates = function()
{
	if (this.isRealDateStart && this.isRealDateEnd)
	{
		return null;
	}

	if (this.matchWorkTime)
	{
		var duration =  this.calculateDuration();
		var msInHour = BX.Tasks.Date.getUnitRatio(BX.Tasks.Date.Unit.Milli, BX.Tasks.Date.Unit.Hour);
		if (duration < this.chart.endlessBarDefaultDuration * msInHour)
		{
			duration = msInHour * this.chart.endlessBarDefaultDuration;
		}

		var calendar = this.chart.calendar;
		if (!this.isRealDateStart)
		{
			this.dateStart = calendar.getClosestWorkTime(this.dateStart, true);
			this.dateEnd = calendar.calculateEndDate(this.dateStart, duration);
			this.isRealDateEnd = true;
			this.isRealDateStart = true;
		}
		else if (!this.isRealDateEnd)
		{
			this.dateEnd = calendar.calculateEndDate(this.dateStart, duration);
			this.isRealDateEnd = true;
		}
	}
	else
	{
		this.isRealDateEnd = true;
		this.isRealDateStart = true;
	}

	this.redraw();

	return {
		dateStart: new Date(this.dateStart.getTime()),
		dateEnd: new Date(this.dateEnd.getTime())
	};
};

GanttTask.prototype.setName = function(name)
{
	if (BX.type.isNotEmptyString(name))
	{
		this.name = name;
	}
};

GanttTask.prototype.setMatchWorkTime = function(value)
{
	if (value === true || value === false)
	{
		this.matchWorkTime = value;
	}
};

GanttTask.prototype.setUrl = function(url)
{
	if (BX.type.isNotEmptyString(url))
		this.url = url;
};

GanttTask.prototype.setFiles = function(files)
{
	if (!BX.type.isArray(files))
		return;

	this.files = [];
	for (var i = 0; i < files.length; i++)
	{
		var file = files[i];
		if (typeof(file) == "object" && BX.type.isNotEmptyString(file.name))
		{
			this.files.push({
				name : file.name,
				url : file.url ? file.url : "",
				size : file.size ? file.size : ""
			});
		}
	}
};

GanttTask.prototype.setDetails = function(callback)
{
	if (BX.type.isFunction(callback))
		this.details = callback;
};

GanttTask.prototype.getTimeline = function(params)
{
	return this.chart.getTimeline();
};

GanttTask.prototype.setMenuItems = function(menuItems)
{
	if (BX.type.isArray(menuItems))
		this.menuItems = menuItems;
};

GanttTask.prototype.setStatus = function(status)
{
	if (!BX.type.isNotEmptyString(status))
		return;

	this.status = status;
};

GanttTask.prototype.allowEditDeadline = function()
{
	this.canEditDeadline = true;
};

GanttTask.prototype.denyEditDeadline = function()
{
	this.canEditDeadline = false;
};

GanttTask.prototype.allowEditPlanDates = function()
{
	this.canEditPlanDates = true;
};

GanttTask.prototype.denyEditPlanDates = function()
{
	this.canEditPlanDates = false;
};

GanttTask.prototype.setProject = function(projectId)
{
	var oldProject = this.project != null ? this.project : null;

	if (this.parentTask !== null)
	{
		this.projectId = this.parentTask.projectId;
		this.project = this.parentTask.project;
	}
	else
	{
		var project = this.chart.getProjectById(BX.type.isNumber(projectId) ? projectId : 0) ;
		if (!project)
		{
			project = this.chart.getDefaultProject();
		}

		this.projectId = project.id;
		this.project = project;
	}

	if (oldProject !== null && oldProject !== this.project)
	{
		oldProject.removeTask(this);
	}

	this.project.addTask(this);
};

GanttTask.prototype.createItem = function()
{
	if (this.layout.item != null)
	{
		this.updateItem();
		return this.layout.item;
	}

	this.layout.item = BX.create("div", {
		props : { id : "task-gantt-item-" + this.id },
		attrs: { "data-task-id": this.id },
		events : {
			mouseover : BX.proxy(this.onItemMouseOver, this),
			mouseout: BX.proxy(this.onItemMouseOut, this)
		},
		children : [
			this.chart.canDragTasks ? BX.create("span", { props : { className : "task-gantt-item-handle" } }) : null,

			BX.create("span", { props : { className : "task-gantt-item-folding"}, events : {
				click : BX.proxy(this.onFoldingClick, this)
			}}),

			(this.layout.name = BX.create("a", {
				props : { className: "task-gantt-item-name", href : ""}, events: {
				click : BX.proxy(this.onItemNameClick, this)
			}})),

			BX.create("div", { props: { className: "task-gantt-item-actions" }, children: [
				(this.layout.outdent = this.chart.canDragTasks && this.chart.treeMode ?
					BX.create("span", {
						props : { className: "task-gantt-item-outdent" },
						attrs: { title: BX.message("TASKS_GANTT_OUTDENT_TASK") },
						events : {
							click : BX.proxy(this.onOutdentClick, this)
						}
					})
					: null
				),

				(this.layout.indent = this.chart.canDragTasks && this.chart.treeMode ?
					BX.create("span", {
						props : { className: "task-gantt-item-indent" },
						attrs: { title: BX.message("TASKS_GANTT_INDENT_TASK") },
						events : {
							click : BX.proxy(this.onIndentClick, this)
						}
					})
					: null
				),

				(this.layout.menu = BX.create("span", {
					props : { className: "task-gantt-item-menu" },
					events : {
						click : BX.proxy(this.onItemMenuClick, this)
					}
				}))
			]})

		]
	});

	this.chart.dragger.registerTask(this.layout.item);

	this.updateItem();

	return this.layout.item;
};

GanttTask.prototype.updateItem = function()
{
	if (this.layout.item == null)
	{
		return null;
	}

	this.layout.name.innerHTML = this.name;
	this.layout.name.href = this.url;

	var itemClass = "task-gantt-item"; // task-gantt-item-depth-" + (this.projectId == 0 ? this.depthLevel-1 : this.depthLevel);

	if (this.chart.treeMode && (this.childTasks.length > 0 || this.hasChildren))
	{
		itemClass += " task-gantt-item-has-children";
	}

	if (this.isHidden())
	{
		itemClass += " task-gantt-item-hidden";
	}

	if (this.opened)
	{
		itemClass += " task-gantt-item-opened";
	}

	if (this.canEdit)
	{
		itemClass += " task-gantt-item-can-edit";
	}

	if (this.status)
	{
		itemClass += " task-gantt-item-status-" + this.status;
	}

	if (this.projectId == 0)
	{
		itemClass += " task-gantt-item-empty-project";
	}

	this.layout.item.className = itemClass;

	if (this.menuItems.length < 1)
	{
		BX.addClass(this.layout.menu, "task-gantt-item-menu-empty");
	}
	else
	{
		BX.removeClass(this.layout.menu, "task-gantt-item-menu-empty");
	}

	var depthLevel = this.projectId == 0 ? this.depthLevel-1 : this.depthLevel;
	if (depthLevel > 0 && this.chart.treeMode)
	{
		this.layout.item.style.paddingLeft = this.chart.firstDepthLevelOffset + ((depthLevel-1) * this.chart.depthLevelOffset) + "px";
	}
	else
	{
		this.layout.item.style.cssText = "";
	}

	return this.layout.item;
};

GanttTask.prototype.createBars = function()
{
	if (this.layout.row !== null)
	{
		this.updateBars();
		return this.layout.row;
	}

	this.createRow();
	this.createPlanBars();
	this.createRealBar();
	this.createCompleteFlag();
	this.createDeadlineBars();

	BX.onCustomEvent(window, 'onGanttTaskCreateBars', this);

	return this.layout.row;
};

GanttTask.prototype.updateBars = function()
{
	if (this.layout.row === null)
	{
		return null;
	}

	this.updateRow();
	this.updatePlanBars();
	this.updateRealBar();
	this.updateCompleteFlag();
	this.updateDeadlineBars();

	BX.onCustomEvent(window, 'onGanttTaskUpdateBars', this);

	return this.layout.row;
};

GanttTask.prototype.redraw = function()
{
	if (!this.layout.item || !this.layout.row)
	{
		return;
	}

	this.chart.autoExpandTimeline([this.getMinDate(), this.getMaxDate()]);
	this.updateItem();
	this.updateBars();
	this.redrawDependencies();
};

GanttTask.prototype.redrawDependencies = function()
{
	for (var linkId in this.predecessors)
	{
		if (this.predecessors.hasOwnProperty(linkId))
		{
			this.predecessors[linkId].draw();
		}
	}

	for (linkId in this.succesors)
	{
		if (this.succesors.hasOwnProperty(linkId))
		{
			this.succesors[linkId].draw();
		}
	}
};

GanttTask.prototype.updateLags = function()
{
	for (var linkId in this.predecessors)
	{
		if (this.predecessors.hasOwnProperty(linkId))
		{
			this.predecessors[linkId].updateLag();
		}
	}

	for (linkId in this.succesors)
	{
		if (this.succesors.hasOwnProperty(linkId))
		{
			this.succesors[linkId].updateLag();
		}
	}
};

GanttTask.prototype.createRow = function()
{
	if (this.layout.row != null)
		return;

	this.layout.row = BX.create("div", {
		props : { id : "task-gantt-timeline-row-" + this.id },
		events : {
			mouseover : BX.proxy(this.onRowMouseOver, this),
			mouseout : BX.proxy(this.onRowMouseOut, this),
			dblclick : BX.proxy(this.onRowDoubleClick, this),
			contextmenu : BX.proxy(this.onRowContextMenu, this)
		}
	});

	this.updateRow();
};

GanttTask.prototype.updateRow = function()
{
	if (this.layout.row == null)
		return;

	var rowClass = "task-gantt-timeline-row";
	if (this.isHidden())
	{
		rowClass += " task-gantt-item-hidden";
	}

	if (this.status)
	{
		rowClass += " task-gantt-item-status-" + this.status;
	}

	if (this.isGroup())
	{
		rowClass += " task-gantt-row-group";
	}

	this.layout.row.className = rowClass;
};

GanttTask.prototype.createPlanBars = function()
{
	if (this.layout.row == null)
		return;

	this.layout.planBar = BX.create("div", {
		attrs: { "data-task-id": this.id },
		events: { mousedown : BX.proxy(this.onPlanBarMouseDown, this) },
		children: [
			(this.layout.planBarStart = BX.create("div", { props : { className: "task-gantt-bar-plan-start" }, style : { zIndex : 0}, events : {
				mousedown: BX.proxy(this.onStartDateMouseDown, this)
			}})),
			(this.layout.planBarEnd = BX.create("div", { props : { className: "task-gantt-bar-plan-end" }, style : { zIndex : 0}, events : {
				mousedown: BX.proxy(this.onEndDateMouseDown, this)
			}})),
			(this.layout.pointStart = this.chart.canCreateDependency ?
				BX.create("div", { props : { className: "task-gantt-point task-gantt-point-start" }, style : { }, events : {
					mousedown: BX.proxy(this.onStartPointMouseDown, this)
				}})
				: null
			),
			(this.layout.pointEnd = this.chart.canCreateDependency ?
				BX.create("div", { props : { className: "task-gantt-point task-gantt-point-end" }, style : { }, events : {
					mousedown: BX.proxy(this.onEndPointMouseDown, this)
				}})
				: null
			)
		]
	});

	this.layout.row.appendChild(this.layout.planBar);
	this.updatePlanBars();
};

GanttTask.prototype.updatePlanBars = function()
{
	if (this.layout.row == null)
		return;

	//var isEndless = (!this.isRealDateEnd && this.status != "completed") ||
	//				(this.status == "completed" && this.dateCompleted != null && this.dateCompleted <= this.dateStart);

	var isEndless = !this.isRealDateEnd;

	this.layout.planBar.className = "task-gantt-bar-plan" +
					   (isEndless ? " task-gantt-bar-plan-endless" : "") +
					   (!this.canEditPlanDates ? " task-gantt-bar-plan-read-only" : "");
	this.resizePlanBar(this.dateStart, this.dateEnd);

};

GanttTask.prototype.createRealBar = function()
{
	if (this.layout.row == null || this.dateStarted == null)
		return;

	this.layout.realBar = BX.create("div", { props : { className : "task-gantt-bar-real" } });
	this.layout.row.appendChild(this.layout.realBar);

	this.updateRealBar();
};

GanttTask.prototype.updateRealBar = function()
{
	if (this.layout.row == null)
	{
		return;
	}

	if (this.dateStarted != null)
   	{
		if (this.layout.realBar == null)
		{
			this.createRealBar();
		}
		else
		{
			var left = this.getTimeline().getPixelsFromDate(this.dateStarted);

			var dateRealBarEnd = this.dateCompleted != null ? this.dateCompleted : this.chart.currentDatetime;
			var width = dateRealBarEnd > this.dateStarted ? this.getTimeline().getPixelsFromDate(dateRealBarEnd) - left : 0;

			this.layout.realBar.style.left = left + "px";
			this.layout.realBar.style.width = width + "px";
		}
	}
	else
	{
		if (this.layout.realBar != null)
		{
			this.layout.row.removeChild(this.layout.realBar);
		}

		this.layout.realBar = null;
	}
};

GanttTask.prototype.createCompleteFlag = function()
{
	if (this.layout.row == null || this.dateCompleted == null)
		return;

	this.layout.completeFlag = BX.create("div", { props : { className : "task-gantt-complete-flag" }});
	this.layout.row.appendChild(this.layout.completeFlag);

	this.updateCompleteFlag();
};

GanttTask.prototype.updateCompleteFlag = function()
{
	if (this.layout.row == null)
	{
		return;
	}

	if (this.dateCompleted != null)
	{
		if (this.layout.completeFlag == null)
		{
			this.createCompleteFlag();
		}
		else
		{
			this.layout.completeFlag.style.left = this.getTimeline().getPixelsFromDate(this.dateCompleted) - 8 + "px";
		}
	}
	else
	{
		if (this.layout.completeFlag != null)
		{
			this.layout.row.removeChild(this.layout.completeFlag);
		}

		this.layout.completeFlag = null;
	}
};

GanttTask.prototype.createDeadlineBars = function()
{
	if (this.layout.row == null || this.dateDeadline == null)
	{
		return;
	}

	this.layout.deadlineSlider = BX.create("div", {
		props : { className: "task-gantt-deadline-slider" },
		events : { mousedown :  BX.proxy(this.onDeadlineMouseDown, this) }
	});

	this.layout.row.appendChild(this.layout.deadlineSlider);

	this.layout.deadlineBar = BX.create("div", { props : { className : "task-gantt-bar-deadline" }});
	this.layout.row.appendChild(this.layout.deadlineBar);

	this.layout.deadlineOverdue = BX.create("div", { props : { className: "task-gantt-bar-deadline-overdue" }});
	this.layout.row.appendChild(this.layout.deadlineOverdue);

	this.layout.realOverdue = BX.create("div", { props : { className : "task-gantt-bar-real-overdue" }});
	this.layout.row.appendChild(this.layout.realOverdue);

	this.updateDeadlineBars();
};

GanttTask.prototype.updateDeadlineBars = function()
{
	if (this.layout.row == null)
	{
		return;
	}

	if (this.dateDeadline != null)
	{
		if (this.layout.deadlineSlider == null)
		{
			this.createDeadlineBars();
		}
		else
		{
			if (this.canEditDeadline)
			{
				BX.removeClass(this.layout.deadlineSlider, "task-gantt-deadline-read-only");
			}
			else
			{
				BX.addClass(this.layout.deadlineSlider, "task-gantt-deadline-read-only");
			}

			this.layout.deadlineSlider.style.left = this.getTimeline().getPixelsFromDate(this.dateDeadline) + "px";
			this.resizeDeadlineBar(this.dateDeadline);
			this.resizeDeadlineOverdueBar(this.dateDeadline);
			this.resizeRealOverdueBar(this.dateDeadline);
		}
	}
	else
	{
		if (this.layout.deadlineSlider != null)
		{
			this.layout.row.removeChild(this.layout.deadlineBar);
			this.layout.row.removeChild(this.layout.deadlineSlider);
			this.layout.row.removeChild(this.layout.deadlineOverdue);
			this.layout.row.removeChild(this.layout.realOverdue);
		}

		this.layout.deadlineBar = null;
		this.layout.deadlineSlider = null;
		this.layout.deadlineOverdue = null;
		this.layout.realOverdue = null;
	}
};

GanttTask.prototype.offsetBars = function(offset)
{
	this.resizerOffsetX += offset;

	if (this.layout.planBar)
		this.layout.planBar.style.left = (parseInt(this.layout.planBar.style.left) || 0) + offset + "px";

	if (this.layout.realBar)
		this.layout.realBar.style.left = (parseInt(this.layout.realBar.style.left) || 0) + offset + "px";

	if (this.layout.completeFlag)
		this.layout.completeFlag.style.left = (parseInt(this.layout.completeFlag.style.left) || 0) + offset + "px";

	if (this.layout.deadlineSlider)
	{
		this.layout.deadlineSlider.style.left = (parseInt(this.layout.deadlineSlider.style.left) || 0) + offset + "px";
		this.layout.deadlineBar.style.left = (parseInt(this.layout.deadlineBar.style.left) || 0) + offset + "px";
		this.layout.deadlineOverdue.style.left = (parseInt(this.layout.deadlineOverdue.style.left) || 0) + offset + "px";
		this.layout.realOverdue.style.left = (parseInt(this.layout.realOverdue.style.left) || 0) + offset + "px";
	}
};

/*==================Resize Task Bars=========*/
GanttTask.prototype.resizePlanBar = function(dateStart, dateEnd)
{
	if (this.layout.planBar == null)
		return;

	var left = this.getTimeline().getPixelsFromDate(dateStart);
	var right = this.getTimeline().getPixelsFromDate(dateEnd);
	var width = right - left;

	width = Math.max(width, this.chart.minBarWidth);
	if (!this.isRealDateEnd)
	{
		var widthWithArrow = width - this.chart.arrowBarWidth;
		if (widthWithArrow < this.chart.minBarWidth)
		{
			BX.removeClass(this.layout.planBar, "task-gantt-bar-plan-endless");
		}
		else
		{
			width = widthWithArrow;
			BX.addClass(this.layout.planBar, "task-gantt-bar-plan-endless");
		}
	}

	if (this.isGroup())
	{
		if (width < this.chart.minGroupBarWidth)
		{
			BX.addClass(this.layout.planBar, "task-gantt-min-group-bar");
		}
		else
		{
			BX.removeClass(this.layout.planBar, "task-gantt-min-group-bar");
		}
	}

	this.layout.planBar.style.left = left + "px";
	this.layout.planBar.style.width = width + "px";
};

GanttTask.prototype.resizeDeadlineBar = function(dateDeadline)
{
	if (this.layout.deadlineBar == null)
	{
		return;
	}

	var left = 0;
	var right = 0;
	if (dateDeadline > this.dateEnd)
	{
		left = this.getTimeline().getPixelsFromDate(this.dateEnd);
		right = this.getTimeline().getPixelsFromDate(dateDeadline);
	}
	else if (dateDeadline < this.dateStart)
	{
		left = this.getTimeline().getPixelsFromDate(this.dateDeadline);
		right = this.getTimeline().getPixelsFromDate(this.dateStart);
	}

	//if (dateDeadline < this.chart.currentDatetime)
	//{
	//	BX.addClass(this.layout.planBar, "task-gantt-deadline-overdued");
	//}
	//else
	//{
	//	BX.removeClass(this.layout.planBar, "task-gantt-deadline-overdued");
	//}

	this.layout.deadlineBar.style.left = left + "px";
	this.layout.deadlineBar.style.width = (right - left) + "px";
};

GanttTask.prototype.resizeDeadlineOverdueBar = function(dateDeadline)
{
	if (this.layout.deadlineOverdue == null)
	{
		return;
	}

	var left = 0;
	var right = 0;

	if (this.dateCompleted == null && this.chart.currentDatetime > dateDeadline)
	{
		left = this.getTimeline().getPixelsFromDate(dateDeadline);
		right = this.getTimeline().getPixelsFromDate(this.chart.currentDatetime);
	}
	else if (this.dateCompleted != null && this.dateCompleted > dateDeadline)
	{
		left = this.getTimeline().getPixelsFromDate(dateDeadline);
		right = this.getTimeline().getPixelsFromDate(this.dateCompleted);
	}

	this.layout.deadlineOverdue.style.left = left + "px";
	this.layout.deadlineOverdue.style.width = (right - left) + "px";
};

GanttTask.prototype.resizeRealOverdueBar = function(dateDeadline)
{
	if (this.layout.realOverdue == null || this.dateStarted == null)
	{
		return;
	}

	if (this.dateStarted > dateDeadline)
	{
		dateDeadline = this.dateStarted;
	}

	var left = 0;
	var right = 0;
	if (this.dateCompleted == null && this.chart.currentDatetime > dateDeadline)
	{
		left = this.getTimeline().getPixelsFromDate(dateDeadline);
		right = this.getTimeline().getPixelsFromDate(this.chart.currentDatetime);
	}
	else if (this.dateCompleted != null && this.dateCompleted > dateDeadline)
	{
		left = this.getTimeline().getPixelsFromDate(dateDeadline);
		right = this.getTimeline().getPixelsFromDate(this.dateCompleted);
	}

	this.layout.realOverdue.style.left = left + "px";
	this.layout.realOverdue.style.width = (right - left) + "px";
};

GanttTask.prototype.onIndentClick = function(event)
{
	this.chart.indentTask(this.id);
};

GanttTask.prototype.onOutdentClick = function(event)
{
	this.chart.outdentTask(this.id);
};

GanttTask.prototype.onFoldingClick = function(event)
{
	if (this.opened)
	{
		this.collapse();
	}
	else
	{
		this.expand();
	}
};

GanttTask.prototype.onItemNameClick = function(event)
{
	event = event || window.event;

	if (!BX.GanttChart.isLeftClick(event))
		return;

	if (!this.chart.settings.disableItemNameClickHandler && BX.type.isFunction(this.details))
	{
		this.details({ event : event });
		BX.PreventDefault(event);
	}
};

GanttTask.prototype.onItemMenuClick = function(event)
{
	var menu = BX.PopupMenu.create(
		this.id,
		this.layout.menu,
		this.menuItems,
		{
			offsetLeft: 8,
			bindOptions: {
				forceBindPosition: true
			},
			events: {
				onPopupClose: function() {
					this.destroy();
				},
				onPopupDestroy: BX.proxy(this.onItemMenuClose, this)
			},
			chart: this.chart,
			task: this
		}
	);
	menu.getPopupWindow().setBindElement(this.layout.menu);
	menu.show();

	this.denyItemsHover();
	BX.addClass(this.layout.menu, "task-gantt-item-menu-selected");
};

GanttTask.prototype.onItemMenuClose = function(popupWindow, event)
{
	this.allowItemsHover(event);
	BX.removeClass(this.layout.menu, "task-gantt-item-menu-selected");
};

GanttTask.prototype.onItemMouseOver = function(event)
{
	if (!this.chart.allowRowHover)
		return;

	event = event || window.event;
	//if (this.isShowQuickInfo(event))
	//{
	//	BX.fixEventPageX(event);
	//	var top = this.layout.item.offsetTop + this.chart.chartContainer.pos.top + 10;
	//	var left = event.pageX;
	//	var bottom = top + 17;
	//
	//	BX.TaskQuickInfo.show(
	//		{
	//			left: left,
	//			top: top,
	//			bottom: bottom
	//		},
	//		this.getQuickInfoData(),
	//		{
	//			dateFormat : this.chart.dateFormat,
	//			dateInUTC : true,
	//			onDetailClick: BX.proxy(this.onQuickInfoDetails, this),
	//			userProfileUrl : this.chart.userProfileUrl
	//		}
	//	);
	//}

	BX.addClass(this.layout.item, "task-gantt-item-hover task-gantt-item-tree-hover");
	BX.addClass(this.layout.row, "task-gantt-timeline-row-hover");
};

GanttTask.prototype.onItemMouseOut = function(event)
{
	if (!this.chart.allowRowHover)
		return;

	if (this.isShowQuickInfo(event))
		BX.TaskQuickInfo.hide();

	BX.removeClass(this.layout.item, "task-gantt-item-hover task-gantt-item-tree-hover");
	BX.removeClass(this.layout.row, "task-gantt-timeline-row-hover");
};

GanttTask.prototype.onRowMouseOver = function(event)
{
	if (!this.chart.allowRowHover)
		return;

	event = event || window.event;
	if (this.isShowQuickInfo(event))
	{
		BX.fixEventPageX(event);

		BX.TaskQuickInfo.show(
			function() {

				this.chart.adjustChartContainer();

				var top = this.layout.row.offsetTop + this.chart.chartContainer.pos.top + 58;
				var left = event.pageX;
				var bottom = top + 27;

				return {
					left: left,
					top: top,
					bottom: bottom
				};
			}.bind(this),
			this.getQuickInfoData(),
			{
				dateFormat : this.chart.dateFormat,
				dateInUTC : true,
				onDetailClick: this.chart.settings.disableDetailClickHandler ? null : BX.proxy(this.onQuickInfoDetails, this),
				userProfileUrl : this.chart.userProfileUrl
			}
		);
	}

	BX.addClass(this.layout.item, "task-gantt-item-hover");
	BX.addClass(this.layout.row, "task-gantt-timeline-row-hover");
};

GanttTask.prototype.onRowMouseOut = function(event)
{
	if (!this.chart.allowRowHover)
		return;

	if (this.isShowQuickInfo(event))
		BX.TaskQuickInfo.hide();

	BX.removeClass(this.layout.item, "task-gantt-item-hover task-gantt-item-tree-hover");
	BX.removeClass(this.layout.row, "task-gantt-timeline-row-hover");
};

GanttTask.prototype.onRowDoubleClick = function(event)
{
	event = event || window.event;
	if (BX.type.isFunction(this.details))
		return this.details({ event : event });
};

GanttTask.prototype.onRowContextMenu = function(event)
{
	event = event || window.event;
	if (this.menuItems.length < 1 || event.ctrlKey)
		return;

	BX.TaskQuickInfo.hide();

	BX.fireEvent(document.body, "click");

	BX.PopupMenu.destroy(this.id);

	var menu = BX.PopupMenu.create(this.id, event, this.menuItems, {
		offsetLeft : 1,
		autoHide : true,
		closeByEsc : true,
		bindOptions : { forceBindPosition : true },
		events : { onPopupClose: BX.proxy(this.onItemMenuClose, this) },
		chart : this.chart,
		task : this
	});

	menu.getPopupWindow().setBindElement(event);
	menu.show();

	var row = null;
	var target = event.target || event.srcElement;
	if (BX.hasClass(target, "task-gantt-timeline-row"))
		row = target;
	else if (BX.hasClass(target.parentNode, "task-gantt-timeline-row"))
		row = target.parentNode;
	else if (BX.hasClass(target.parentNode.parentNode, "task-gantt-timeline-row"))
		row = target.parentNode.parentNode;

	if (row != null && BX.type.isNotEmptyString(row.id))
	{
		var task = this.chart.getTaskById(row.id.substr("task-gantt-timeline-row-".length));
		if (task != null)
		{
			BX.addClass(task.layout.item, "task-gantt-item-hover");
		   	BX.addClass(task.layout.row, "task-gantt-timeline-row-hover");
		}
	}

	this.denyItemsHover();

	BX.PreventDefault(event);
};

GanttTask.prototype.onQuickInfoDetails = function(event, popupWindow, quickInfo)
{
	popupWindow.close();
	if (BX.type.isFunction(this.details))
		return this.details({ event : event, popupWindow : popupWindow, quickInfo : quickInfo});
};

GanttTask.prototype.isShowQuickInfo = function(event)
{
	if (this.chart.dragClientX != 0 || !this.chart.allowRowHover)
		return false;

	event = event || window.event;
	var target = event.target || event.srcElement;
	return  target == this.layout.planBar ||
			target == this.layout.realBar ||
			target == this.layout.name ||
			target == this.layout.deadlineBar ||
			target == this.layout.deadlineOverdue ||
			target == this.layout.realOverdue ||
			target == this.layout.completeFlag ||
			target == this.layout.deadlineSlider;
};

GanttTask.prototype.getQuickInfoData = function()
{
	var dateStart = this.isRealDateStart ? this.dateStart : null;
	var dateEnd = this.isRealDateEnd ? this.dateEnd : null;

	return {
		id : this.id,
		name : this.name,

		dateCreated : this.dateCreated,
		dateStart : dateStart,
		dateEnd : dateEnd,
		dateDeadline : this.dateDeadline,
		dateCompleted : this.dateCompleted,
		dateStarted : this.dateStarted,

		files : this.files,
		priority : this.priority,
		status : this.status,
		responsible : this.responsible,
		responsibleId : this.responsibleId,
		director : this.director,
		directorId : this.directorId,

		url: this.url
	};
};

GanttTask.prototype.denyItemsHover = function()
{
	this.chart.allowRowHover = false;
};

GanttTask.prototype.allowItemsHover = function(event)
{
	this.chart.allowRowHover = true;

	event = event || window.event || null;
	if (!event)
		return;

	var target = event.target || event.srcElement;

	if ( event.keyCode == 27 ||
		(
			target != this.layout.row &&
			target.parentNode != this.layout.row &&
			target.parentNode.parentNode != this.layout.row &&
		    target != this.layout.item && target.parentNode != this.layout.item
		)
	)
	{
		BX.removeClass(this.layout.item, "task-gantt-item-hover task-gantt-item-tree-hover");
		BX.removeClass(this.layout.row, "task-gantt-timeline-row-hover");
	}
	else if (target != this.layout.item && target.parentNode != this.layout.item)
	{
		BX.removeClass(this.layout.item, "task-gantt-item-tree-hover");
	}
};

GanttTask.prototype.isHidden = function()
{
	if (this.project.opened === false)
		return true;

	var parentTask = this.parentTask;
	while (parentTask != null)
	{
		if (parentTask.opened === false)
			return true;

		parentTask = parentTask.parentTask;
	}

	return false;
};

GanttTask.prototype.show = function()
{
	BX.removeClass(this.layout.item, "task-gantt-item-hidden");
	BX.removeClass(this.layout.row, "task-gantt-item-hidden");
};

GanttTask.prototype.hide = function()
{
	BX.addClass(this.layout.item, "task-gantt-item-hidden");
	BX.addClass(this.layout.row, "task-gantt-item-hidden");
};

GanttTask.prototype.collapse = function(skip, callback)
{
	var children = this.childTasks.length;
	callback = BX.type.isFunction(callback) ? callback : BX.DoNothing;
	if (children < 1)
	{
		callback();
		return;
	}

	if (!skip)
	{
		BX.removeClass(this.layout.item, "task-gantt-item-opened");
		this.opened = false;
		BX.onCustomEvent(this.chart, "onTaskOpen", [this, callback]);
	}

	for (var i = 0; i < children; i++)
	{
		this.childTasks[i].hide();
		this.childTasks[i].collapse(true);
	}

	if (!skip)
	{
		this.chart.drawDependencies();
	}
};

GanttTask.prototype.expand = function(skip, callback)
{
	var children = this.childTasks.length;
	callback = BX.type.isFunction(callback) ? callback : BX.DoNothing;
	if (children < 1 && this.hasChildren === false)
	{
		callback();
		return;
	}

	if (!skip)
	{
		this.opened = true;
		BX.addClass(this.layout.item, "task-gantt-item-opened");
		BX.onCustomEvent(this.chart, "onTaskOpen", [this, callback]);
	}

	for (var i = 0; i < children; i++)
	{
		this.childTasks[i].show();
		if (this.childTasks[i].opened)
		{
			this.childTasks[i].expand(true);
		}
	}

	if (!skip)
	{
		this.chart.drawDependencies();
	}
};

GanttTask.prototype.highlight = function()
{
	if (!this.highlightTimeout)
	{
		BX.addClass(this.layout.item, "task-gantt-item-highlighted");
		BX.addClass(this.layout.row, "task-gantt-row-highlighted");
		this.highlightTimeout = setTimeout(BX.proxy(this.stopHighlight, this), 1000);
	}
};

GanttTask.prototype.stopHighlight = function()
{
	if (this.highlightTimeout)
	{
		clearTimeout(this.highlightTimeout);
	}

	this.highlightTimeout = null;
	BX.removeClass(this.layout.item, "task-gantt-item-highlighted");
	BX.removeClass(this.layout.row, "task-gantt-row-highlighted");
};

GanttTask.prototype.scrollIntoView = function(alignToTop)
{
	this.layout.item.scrollIntoView(alignToTop);
};

GanttTask.prototype.getMinDate = function()
{
	var dates = [this.dateStart, this.dateEnd, this.dateCreated, this.dateStarted, this.dateCompleted, this.dateDeadline];
	for (var i = dates.length-1; i >= 0; i--)
		if (dates[i] == null)
			dates.splice(i, 1);

	return new Date(Math.min.apply(null, dates));
};

GanttTask.prototype.getMaxDate = function()
{
	var dates = [this.dateStart, this.dateEnd, this.dateCreated, this.dateStarted, this.dateCompleted, this.dateDeadline];
	for (var i = dates.length-1; i >= 0; i--)
		if (dates[i] == null)
			dates.splice(i, 1);

	return new Date(Math.max.apply(null, dates));
};

/*==================Handlers==============*/
GanttTask.prototype.startResize = function(event, mouseUpHandler, mouseMoveHandler, cursor)
{
	event = event || window.event;

	BX.bind(document, "mouseup", mouseUpHandler);
	BX.bind(document, "mousemove", mouseMoveHandler);

	document.body.onselectstart = BX.False;
	document.body.ondragstart = BX.False;
	document.body.style.MozUserSelect = "none";
	document.body.style.cursor = cursor ? cursor : "ew-resize";
	//this.chart.layout.root.style.cursor = "ew-resize";

	this.denyItemsHover();
	BX.TaskQuickInfo.hide();
	BX.PreventDefault(event);
};

GanttTask.prototype.endResize = function(event, mouseUpHandler, mouseMoveHandler)
{
	event = event || window.event;

	BX.unbind(document, "mouseup", mouseUpHandler);
	BX.unbind(document, "mousemove", mouseMoveHandler);

	document.body.onselectstart = null;
	document.body.ondragstart = null;
	document.body.style.MozUserSelect = "";
	document.body.style.cursor = "default";
	//this.chart.layout.root.style.cursor = "default";

	this.stopAutoResize();
	this.allowItemsHover(event);
	this.chart.tooltip.hide();
};

/*==========================Dealine Resize=========================*/
GanttTask.prototype.onDeadlineMouseDown = function(event)
{
	if (!this.canEditDeadline)
	{
		return;
	}

	event = event || window.event;
	BX.fixEventPageX(event);
	if (!BX.GanttChart.isLeftClick(event))
	{
		return;
	}

	this.resizerOffsetX = this.getTimeline().getPixelsFromDate(this.dateDeadline);
	this.resizerPageX = event.pageX;
	this.resizerChangePos = false;

	this.startResize(event, BX.proxy(this.onDeadlineMouseUp, this), BX.proxy(this.onDeadlineMouseMove, this));
};

GanttTask.prototype.onDeadlineMouseUp = function(event)
{
	this.endResize(event, BX.proxy(this.onDeadlineMouseUp, this), BX.proxy(this.onDeadlineMouseMove, this));

	if (this.resizerChangePos)
	{
		if (this.matchWorkTime)
		{
			this.dateDeadline = this.chart.calendar.getClosestWorkTime(this.dateDeadline, true);
			this.redraw();
		}

		BX.onCustomEvent(this.chart, "onTaskChange", [[this.getEventObject(["dateDeadline"])]]);
	}
};

GanttTask.prototype.onDeadlineMouseMove = function(event)
{
	this.autoResize(event, this.resizeDeadlineDate);
};

GanttTask.prototype.resizeDeadlineDate = function(offset)
{
	this.resizerOffsetX = this.resizerOffsetX + offset;
	this.layout.deadlineSlider.style.left = this.resizerOffsetX + "px";

	this.dateDeadline = this.getTimeline().getDateFromPixels(this.resizerOffsetX);

	this.resizeDeadlineBar(this.dateDeadline);
	this.resizeDeadlineOverdueBar(this.dateDeadline);
	this.resizeRealOverdueBar(this.dateDeadline);

	this.chart.tooltip.show(this.resizerOffsetX, this);

	this.chart.autoExpandTimeline(this.dateDeadline);
};

/*=========================== Date Start Resize =====================*/
GanttTask.prototype.onStartDateMouseDown = function(event)
{
	if (!this.canEditPlanDates || this.isGroup())
	{
		return;
	}

	if (this.isParent && this.projectPlanFromSubTasks && this.isRealDateEnd)
	{
		return;
	}

	event = event || window.event;
	BX.fixEventPageX(event);
	if (!BX.GanttChart.isLeftClick(event))
	{
		return;
	}

	this.layout.planBarStart.style.zIndex = parseInt(this.layout.planBarEnd.style.zIndex) + 1;
	this.resizerOffsetX = this.getTimeline().getPixelsFromDate(this.dateStart);
	this.resizerPageX = event.pageX;
	this.resizerChangePos = false;

	this.startResize(event, BX.proxy(this.onStartDateMouseUp, this), BX.proxy(this.onStartDateMouseMove, this));
};

GanttTask.prototype.onStartDateMouseUp = function(event)
{
	this.endResize(event, BX.proxy(this.onStartDateMouseUp, this), BX.proxy(this.onStartDateMouseMove, this));

	if (this.resizerChangePos)
	{
		this.matchWorkingTime(this.dateStart);
		this.schedule();
		this.chart.drawDependencies();
		BX.onCustomEvent(this.chart, "onTaskChange", [[this.getEventObject(["dateStart"])]]);
	}
};

GanttTask.prototype.onStartDateMouseMove = function(event)
{
	this.autoResize(event, this.resizeStartDate);
};

GanttTask.prototype.resizeStartDate = function(offset)
{
	this.resizerOffsetX = this.resizerOffsetX + offset;
	var dateEndOffset = this.getTimeline().getPixelsFromDate(this.dateEnd);
	if ( (dateEndOffset - this.resizerOffsetX) < this.chart.minBarWidth)
	{
		this.dateStart = this.getTimeline().getDateFromPixels(dateEndOffset - this.chart.minBarWidth);
		this.isRealDateStart = true;
		this.resizePlanBar(this.dateStart, this.dateEnd);
		this.resizeDeadlineBar(this.dateDeadline);
		this.chart.tooltip.show(this.resizerOffsetX, this);
	}
	else
	{
		this.dateStart = this.getTimeline().getDateFromPixels(this.resizerOffsetX);
		this.isRealDateStart = true;
		this.resizePlanBar(this.dateStart, this.dateEnd);
		this.resizeDeadlineBar(this.dateDeadline);
		this.chart.tooltip.show(this.resizerOffsetX, this);
		this.chart.autoExpandTimeline(this.dateStart);
	}

	this.redrawDependencies();
};

/*===========================Date End Resize=========================*/
GanttTask.prototype.onEndDateMouseDown = function(event)
{
	if (!this.canEditPlanDates || this.isGroup())
	{
		return;
	}

	if (this.isParent && this.projectPlanFromSubTasks && this.isRealDateEnd)
	{
		return;
	}

	event = event || window.event;
	BX.fixEventPageX(event);
	if (!BX.GanttChart.isLeftClick(event))
		return;

	this.layout.planBarEnd.style.zIndex = parseInt(this.layout.planBarStart.style.zIndex) + 1;
	this.resizerOffsetX = this.getTimeline().getPixelsFromDate(this.dateEnd);
	this.resizerPageX = event.pageX;
	this.resizerChangePos = false;

	this.startResize(event, BX.proxy(this.onEndDateMouseUp, this), BX.proxy(this.onEndDateMouseMove, this));
};

GanttTask.prototype.onEndDateMouseUp = function(event)
{
	this.endResize(event, BX.proxy(this.onEndDateMouseUp, this), BX.proxy(this.onEndDateMouseMove, this));

	if (this.resizerChangePos)
	{
		this.matchWorkingTime(null, this.dateEnd);
		this.schedule();
		this.chart.drawDependencies();
		BX.onCustomEvent(this.chart, "onTaskChange", [[this.getEventObject(["dateEnd"])]]);
	}
};

GanttTask.prototype.onEndDateMouseMove = function(event)
{
	if (!this.isRealDateEnd)
	{
		this.isRealDateEnd = true;
		BX.removeClass(this.layout.planBar, "task-gantt-bar-plan-endless");
	}

	this.autoResize(event, this.resizeEndDate);
};

GanttTask.prototype.resizeEndDate = function(offset)
{
	this.resizerOffsetX = this.resizerOffsetX + offset;
	var dateStartOffset = this.getTimeline().getPixelsFromDate(this.dateStart);
	if ((this.resizerOffsetX - dateStartOffset) < this.chart.minBarWidth)
	{
		//Min Task Width
		this.dateEnd = this.getTimeline().getDateFromPixels(dateStartOffset + this.chart.minBarWidth);
		this.resizePlanBar(this.dateStart, this.dateEnd);
		this.resizeDeadlineBar(this.dateDeadline);
		this.chart.tooltip.show(this.resizerOffsetX, this);
	}
	else
	{
		this.dateEnd = this.getTimeline().getDateFromPixels(this.resizerOffsetX);
		this.resizePlanBar(this.dateStart, this.dateEnd);
		this.resizeDeadlineBar(this.dateDeadline);
		this.chart.tooltip.show(this.resizerOffsetX, this);
		this.chart.autoExpandTimeline(this.dateEnd);
	}

	this.redrawDependencies();
};

/* Move Plan Bar */
GanttTask.prototype.onPlanBarMouseDown = function(event)
{
	if (!this.canEditPlanDates)
	{
		return;
	}

	event = event || window.event;
	BX.fixEventPageX(event);
	if (!BX.GanttChart.isLeftClick(event))
	{
		return;
	}

	this.resizerOffsetX = this.getTimeline().getPixelsFromDate(this.dateStart);
	this.resizerPageX = event.pageX;
	this.resizerChangePos = false;

	this.startResize(event, BX.proxy(this.onPlanBarMouseUp, this), BX.proxy(this.onPlanBarMouseMove, this), "move");

	this.duration = this.calculateDuration();
	//console.log("duration", this.duration, this.duration / 1000 / 3600);
};

GanttTask.prototype.onPlanBarMouseUp = function(event)
{
	this.endResize(event, BX.proxy(this.onPlanBarMouseUp, this), BX.proxy(this.onPlanBarMouseMove, this));

	if (this.resizerChangePos)
	{
		this.matchWorkingTime(this.dateStart, this.dateEnd, this.duration);
		this.schedule();
		this.chart.drawDependencies();
		BX.onCustomEvent(this.chart, "onTaskChange", [[this.getEventObject(["dateStart", "dateEnd"])]]);
	}
};

GanttTask.prototype.onPlanBarMouseMove = function(event)
{
	if (!this.isRealDateEnd)
	{
		this.isRealDateEnd = true;
		BX.removeClass(this.layout.planBar, "task-gantt-bar-plan-endless");
		//this.redraw();
	}

	this.autoResize(event, this.resizeStartEndDate, true);
};

GanttTask.prototype.resizeStartEndDate = function(offset)
{
	this.resizerOffsetX = this.resizerOffsetX + offset;

	var edges = this.getCoords();

	//var left = parseInt(this.layout.planBar.style.left);
	//this.layout.planBar.style.left = left + offset + "px";

	this.dateStart = this.getTimeline().getDateFromPixels(edges.startX + offset);
	this.dateEnd = this.getTimeline().getDateFromPixels(edges.endX + offset);
	this.layout.planBar.style.left = edges.startX + offset + "px";

	this.isRealDateStart = true;
	this.isRealDateEnd = true;

	this.resizeDeadlineBar(this.dateDeadline);

	//this.chart.tooltip.show(this.resizerOffsetX, this);
	this.chart.tooltip.show(
		this.autoResizeEvent.pageX
		- this.chart.chartContainer.pos.left - this.chart.gutterOffset
		+ this.getTimeline().getScrollLeft(), this);

	this.chart.autoExpandTimeline([this.dateStart, this.dateEnd]);

	this.redrawDependencies();
};

GanttTask.prototype.onStartPointMouseDown = function(event)
{
	this.chart.pointer.startDrag(this, true, event);
};

GanttTask.prototype.onEndPointMouseDown = function(event)
{
	this.chart.pointer.startDrag(this, false, event);
};

/*============== Auto Resize =========================================*/
GanttTask.prototype.autoResize = function(event, autoResizeCallback, skipSnap)
{
	event = event || window.event;
	BX.fixEventPageXY(event);
	this.autoResizeEvent = {
		clientX: event.clientX,
		clientY: event.clientY,
		pageX: event.pageX,
		pageY: event.pageY
	};

	this.autoResizeCallback = autoResizeCallback;
	this.resizerChangePos = true;
	skipSnap = !!skipSnap;

	if (event.pageX > this.chart.chartContainer.maxPageX && (event.pageX - this.resizerPageX) > 0 && !this.autoResizeIntID)
	{
		this.stopAutoResize();
		if (!skipSnap)
		{
			var maxOffset = this.chart.chartContainer.width - this.chart.chartContainer.padding - this.chart.gutterOffset + this.getTimeline().getScrollLeft();
			this.autoResizeCallback(maxOffset - this.resizerOffsetX, event.pageY - this.resizerPageY);
			this.resizerPageX = this.chart.chartContainer.maxPageX;
			this.resizerPageY = event.pageY;
		}

		this.autoResizeIntID = setInterval(BX.proxy(this.autoResizeRight, this), this.autoResizeTimeout);
	}
	else if (event.pageX < this.chart.chartContainer.minPageX && (event.pageX - this.resizerPageX) < 0 && !this.autoResizeIntID)
	{
		this.stopAutoResize();
		if (!skipSnap)
		{
			var minOffset = this.chart.chartContainer.padding + this.getTimeline().getScrollLeft();
			this.autoResizeCallback(minOffset - this.resizerOffsetX, event.pageY - this.resizerPageY);
			this.resizerPageX = this.chart.chartContainer.minPageX;
			this.resizerPageY = event.pageY;
		}

		this.autoResizeIntID = setInterval(BX.proxy(this.autoResizeLeft, this), this.autoResizeTimeout);
	}
	else if ((event.pageX > this.chart.chartContainer.minPageX && event.pageX < this.chart.chartContainer.maxPageX) || !this.autoResizeIntID)
	{
		this.stopAutoResize();
		this.autoResizeCallback(event.pageX - this.resizerPageX, event.pageY - this.resizerPageY);
		this.resizerPageX = event.pageX;
		this.resizerPageY = event.pageY;
	}
	else
	{
		this.resizerPageX = event.pageX;
		this.resizerPageY = event.pageY;
	}
};

GanttTask.prototype.stopAutoResize = function()
{
	if (this.autoResizeIntID)
		clearInterval(this.autoResizeIntID);
	this.autoResizeIntID = null;
};

GanttTask.prototype.autoResizeRight = function()
{
	this.getTimeline().setScrollLeft(this.getTimeline().getScrollLeft() + this.chart.chartContainer.padding);
	this.autoResizeCallback(this.chart.chartContainer.padding, 0);
};

GanttTask.prototype.autoResizeLeft = function()
{
	this.getTimeline().setScrollLeft(this.getTimeline().getScrollLeft() - this.chart.chartContainer.padding);
	this.autoResizeCallback(-this.chart.chartContainer.padding, 0);
};

GanttTask.prototype.getMouseOffset = function(event)
{
	return event.pageX - (this.chart.chartContainer.pos.left + this.chart.gutterOffset) + this.getTimeline().getScrollLeft();
};

GanttTask.prototype.getRealDates = function()
{
	var dateStart = this.isRealDateStart ? this.dateStart : null;
	var dateEnd = this.isRealDateEnd ? this.dateEnd : null;

	return {
		dateCreated : BX.GanttChart.convertDateFromUTC(this.dateCreated),
		dateStart : BX.GanttChart.convertDateFromUTC(dateStart),
		dateEnd : BX.GanttChart.convertDateFromUTC(dateEnd),
		dateDeadline : BX.GanttChart.convertDateFromUTC(this.dateDeadline),
		dateCompleted : BX.GanttChart.convertDateFromUTC(this.dateCompleted),
		dateStarted : BX.GanttChart.convertDateFromUTC(this.dateStarted)
	};
};

GanttTask.prototype.getEventObject = function(changes)
{
	var obj = this.getRealDates();
	obj.task = this;
	obj.changes = BX.type.isArray(changes) ? changes : [];
	return obj;
};

GanttTask.prototype.getCoords = function()
{
	var x = this.layout.planBar.offsetLeft;
	var y = this.layout.row.offsetTop + 16;
	return {
		startX: x,
		startY : y,
		endX : x + this.layout.planBar.offsetWidth,
		endY : y
	};
};

GanttTask.prototype.schedule = function(constraint, transaction)
{
	if (!this.canEditPlanDates)
	{
		return;
	}

	transaction = transaction || new Date().getTime();
	if (constraint && this.transaction !== transaction)
	{
		var startDate = constraint.getMinDate();
		var duration = this.calculateDuration();
		var endDate = BX.Tasks.Date.add(startDate, BX.Tasks.Date.Unit.Milli, duration);

		this.setTaskFromJSON({
			dateStart: BX.GanttChart.convertDateFromUTC(startDate),
			dateEnd: BX.GanttChart.convertDateFromUTC(endDate)
		});

		this.correctWorkTime(startDate, endDate, duration);
		this.redraw();
	}

	this.transaction = transaction;

	for (var linkId in this.predecessors)
	{
		if (this.predecessors.hasOwnProperty(linkId))
		{
			var predecessor = this.predecessors[linkId];
			predecessor.updateLag();
		}
	}

	for (linkId in this.succesors)
	{
		if (this.succesors.hasOwnProperty(linkId))
		{
			var successor = this.chart.getTaskById(this.succesors[linkId].to);
			successor.schedule(this.succesors[linkId], transaction);
		}
	}
};

GanttTask.prototype.calculateDuration = function()
{
	if (this.matchWorkTime)
	{
		var duration = this.chart.calendar.calculateDuration(this.dateStart, this.dateEnd);
		return duration > 0 ? duration : this.dateEnd - this.dateStart;
	}
	else
	{
		return this.dateEnd - this.dateStart;
	}
};

GanttTask.prototype.getMinimalStartDate = function()
{
	var minStartDate = null;
	for (var linkId in this.predecessors)
	{
		if (!this.predecessors.hasOwnProperty(linkId))
		{
			continue;
		}

		var link = this.predecessors[linkId];
		var date = link.getMinDate();
		if (minStartDate === null || minStartDate < date)
		{
			minStartDate = date;
		}
	}

	return minStartDate;
};

GanttTask.prototype.correctWorkTime = function(startDate, endDate, duration)
{
	if (!this.matchWorkTime)
	{
		return;
	}

	var calendar = this.chart.calendar;
	if (!calendar.isWorkTime(startDate))
	{
		this.dateStart = calendar.getClosestWorkTime(startDate, true);
		this.dateEnd = calendar.calculateEndDate(startDate, duration);
	}
	else
	{
		this.dateEnd = calendar.calculateEndDate(startDate, duration);
	}
};

GanttTask.prototype.matchWorkingTime = function(startDate, endDate, duration)
{
	if (!this.matchWorkTime)
	{
		return;
	}

	if (startDate && endDate)
	{
		this.correctWorkTime(startDate, endDate, duration);
	}
	else if (startDate)
	{
		this.dateStart = this.chart.calendar.getClosestWorkTime(startDate, true);
		if (this.dateStart > this.dateEnd)
		{
			this.dateEnd = this.chart.calendar.calculateEndDate(startDate, this.dateEnd - startDate);
		}
	}
	else if (endDate)
	{
		this.dateEnd = this.chart.calendar.getClosestWorkTime(endDate, false);
		if (this.dateStart > this.dateEnd)
		{
			this.dateStart = this.chart.calendar.calculateStartDate(this.dateEnd, endDate - this.dateStart);
		}
	}

	this.redraw();
};

/**
 *
 * @param {BX.GanttChart} chart
 * @constructor
 */
var Timeline = function(chart)
{
	this.chart = chart;
	this.firstWeekDay = 1;

	this.setFirstWeekDay(chart.settings.firstWeekDay);

	this.headerViewportWidth = null;

	this.zoom = new TimelineZoom(chart.settings.zoomLevel);
	this.reconfigure(this.zoom.getPreset());
};

Timeline.prototype.reconfigure = function(config)
{
	//Scale options
	this.topUnit = config.topUnit || this.topUnit || BX.Tasks.Date.Unit.Month;
	this.topIncrement = config.topIncrement || this.topIncrement || 1;
	this.topDateFormat = config.topDateFormat || this.topDateFormat || "F Y";

	this.bottomUnit = config.bottomUnit || this.bottomUnit || BX.Tasks.Date.Unit.Day;
	this.bottomIncrement = config.bottomIncrement || this.bottomIncrement || 1;
	this.bottomDateFormat = config.bottomDateFormat || this.bottomDateFormat || "j";

	this.snapUnit = config.snapUnit || this.snapUnit || BX.Tasks.Date.Unit.Hour;
	this.snapIncrement = config.snapIncrement || this.snapIncrement || 1;
	this.snapWidth = config.snapWidth || this.snapWidth || 1;

	this.columnWidth = config.columnWidth || this.columnWidth || 24;

	//Start-End
	var currentDateMin = BX.Tasks.Date.floorDate(this.chart.currentDatetime, this.topUnit, this.firstWeekDay);
	var currentDateMax = BX.Tasks.Date.ceilDate(this.chart.currentDatetime, this.topUnit, this.topIncrement, this.firstWeekDay);

	var snapUnitsInViewport = Math.ceil(this.chart.chartContainer.width / this.getUnitInPixels(this.snapUnit));
	var snapUnitsInTopHeader = BX.Tasks.Date.getDurationInUnit(currentDateMin, currentDateMax, this.snapUnit);

	var increment = Math.ceil(snapUnitsInViewport / snapUnitsInTopHeader);
	this.startDate = BX.Tasks.Date.add(currentDateMin, this.topUnit, -increment);
	this.endDate = BX.Tasks.Date.add(currentDateMax, this.topUnit, increment);
};

Timeline.prototype.setFirstWeekDay = function(day)
{
	if (BX.type.isNumber(day) && day >= 0 && day <= 6)
	{
		this.firstWeekDay = day;
	}
};

Timeline.prototype.setZoomLevel = function(level)
{
	this.zoom.setLevel(level);
	this.reconfigure(this.zoom.getPreset());
};

Timeline.prototype.draw = function()
{
	this.setTimelineWidth();
};

Timeline.prototype.getTimelineWidth = function()
{
	return this.getTimespanInPixels(this.startDate, this.endDate, this.snapUnit);
};

Timeline.prototype.getTimespanWidth = function(startDate, endDate)
{
	return this.getTimespanInPixels(startDate, endDate, this.snapUnit);
};

Timeline.prototype.getTimespanInPixels = function(startDate, endDate, unit)
{
	var duration = BX.Tasks.Date.getDurationInUnit(startDate, endDate, unit);
	var unitSize = this.getUnitInPixels(unit);

	return duration * unitSize;
};

Timeline.prototype.setTimelineWidth = function()
{
	this.chart.layout.timelineInner.style.width = this.getTimelineWidth() + "px";
};

Timeline.prototype.getColumnWidth = function() 
{
	return this.chart.gutterOffset;
};

Timeline.prototype.getScrollHeight = function()
{
	return this.chart.layout.root.scrollHeight;
};

/**
 *
 * @return {Element}
 */
Timeline.prototype.getRootContainer = function()
{
	return this.chart.layout.root;
};

Timeline.prototype.autoExpandTimeline = function(dates, changeOnlyDates)
{
	if (!BX.type.isArray(dates))
	{
		dates = [dates];
	}

	var snapUnitsInViewport = Math.ceil(this.chart.chartContainer.width / this.getUnitInPixels(this.snapUnit));
	for (var i = 0; i < dates.length; i++)
	{
		var date = dates[i];

		var currentDateMin = BX.Tasks.Date.floorDate(date, this.topUnit, this.firstWeekDay);
		var currentDateMax = BX.Tasks.Date.ceilDate(date, this.topUnit, this.topIncrement, this.firstWeekDay);
		var snapUnitsInTopHeader = BX.Tasks.Date.getDurationInUnit(currentDateMin, currentDateMax, this.snapUnit);
		var increment = Math.ceil(snapUnitsInViewport / snapUnitsInTopHeader);

		var duration = BX.Tasks.Date.getDurationInUnit(this.startDate, date, this.snapUnit);
		if (duration <= snapUnitsInViewport)
		{
			var newStartDate = BX.Tasks.Date.add(currentDateMin, this.topUnit, -increment);
			this.expandTimelineLeft(newStartDate, changeOnlyDates);
			continue;
		}

		duration = BX.Tasks.Date.getDurationInUnit(date, this.endDate, this.snapUnit);
		if (duration <= snapUnitsInViewport)
		{
			var newEndDate = BX.Tasks.Date.add(currentDateMax, this.topUnit, increment);
			this.expandTimelineRight(newEndDate, changeOnlyDates);
		}
	}
};

Timeline.prototype.expandTimelineLeft = function(date, changeOnlyDate)
{
	if (date >= this.startDate)
	{
		return;
	}

	var oldDate = new Date(this.startDate.getTime());
	this.startDate = date;
	if (this.chart.layout.root === null || changeOnlyDate === true)
	{
		return;
	}

	var duration = BX.Tasks.Date.getDurationInUnit(this.startDate, oldDate, this.snapUnit);
	var unitSize = this.getUnitInPixels(this.snapUnit);
	var offset = duration * unitSize;

	var scrollLeft = this.getScrollLeft();
	this.setTimelineWidth();
	for	(var taskId in this.chart.tasks)
	{
		this.chart.tasks[taskId].offsetBars(offset);
	}

	this.chart.drawDependencies();
	this.chart.pointer.offsetLine(offset);

	this.setScrollLeft(scrollLeft + offset);
};

Timeline.prototype.expandTimelineRight = function(date, changeOnlyDate)
{
	if (date <= this.endDate)
	{
		return;
	}

	this.endDate = date;
	if (this.chart.layout.root === null || changeOnlyDate === true)
	{
		return;
	}

	var scrollLeft = this.getScrollLeft();
	this.setTimelineWidth();
	this.setScrollLeft(scrollLeft);
};

Timeline.prototype.getStart = function()
{
	return this.startDate;
};

Timeline.prototype.getEnd = function()
{
	return this.endDate;
};

Timeline.prototype.getHeaderViewportWidth = function()
{
	return this.headerViewportWidth !== null ? this.headerViewportWidth : this.chart.chartContainer.width;
};

Timeline.prototype.setHeaderViewportWidth = function(width)
{
	if (BX.type.isNumber(width) || width === null)
	{
		this.headerViewportWidth = width;
	}
};

Timeline.prototype.renderHeader = function()
{
	var viewport = this.getHeaderViewportWidth();
	var scrollLeft = this.getScrollLeft();

	var startDate = this.getDateFromPixels(scrollLeft);
	var endDate = this.getDateFromPixels(scrollLeft + viewport);

	startDate = BX.Tasks.Date.floorDate(startDate, this.topUnit, this.firstWeekDay);
	endDate = BX.Tasks.Date.ceilDate(endDate, this.topUnit, this.topIncrement, this.firstWeekDay);

	var topUnitsInViewport = Math.ceil(viewport / this.getUnitInPixels(this.topUnit));
	startDate = BX.Tasks.Date.add(startDate, this.topUnit, -topUnitsInViewport);
	endDate = BX.Tasks.Date.add(endDate, this.topUnit, topUnitsInViewport);

	startDate = BX.Tasks.Date.max(startDate, this.getStart());
	endDate = BX.Tasks.Date.min(endDate, this.getEnd());

	this.chart.layout.scalePrimary.innerHTML =
		this.createHeader(startDate, endDate, "top", this.topUnit, this.topIncrement);

	this.chart.layout.scaleSecondary.innerHTML =
		this.createHeader(startDate, endDate, "bottom", this.bottomUnit, this.bottomIncrement);
};

Timeline.prototype.createHeader = function(start, end, position, unit, increment)
{
	var startDate = this.getStart();
	var endDate = end;
	var result = "";

	var offset = 0;
	while (startDate < endDate)
	{
		var nextDate = BX.Tasks.Date.min(BX.Tasks.Date.getNext(startDate, unit, increment, this.firstWeekDay), endDate);

		if (startDate >= start)
		{
			result += position === "top" ? this.renderTopHeader(startDate, nextDate) : this.renderBottomHeader(startDate, nextDate);
		}
		else
		{
			var duration = BX.Tasks.Date.getDurationInUnit(startDate, nextDate, this.snapUnit);
			var unitSize = this.getUnitInPixels(this.snapUnit);
			offset += this.getTimespanInPixels(startDate, nextDate, this.snapUnit);
		}

		startDate = nextDate;
	}

	return '<div style="position: absolute; left: ' + offset + 'px">' + result + '</div>';
};

Timeline.prototype.renderTopHeader = function(start, end)
{
	var duration = BX.Tasks.Date.getDurationInUnit(start, end, this.snapUnit);
	var unitSize = this.getUnitInPixels(this.snapUnit);

	return '<span class="task-gantt-top-column" ' +
		'style="width:' + (duration * unitSize) + 'px"><span class="task-gantt-scale-month-text">' +
		BX.date.format(this.topDateFormat, start, null, true) + '</span></span>';
};

Timeline.prototype.renderBottomHeader = function(start, end)
{
	var duration = BX.Tasks.Date.getDurationInUnit(start, end, this.snapUnit);
	var unitSize = this.getUnitInPixels(this.snapUnit);

	var columnClass = "task-gantt-bottom-column";
	if (this.bottomUnit !== BX.Tasks.Date.Unit.Month &&
		this.bottomUnit !== BX.Tasks.Date.Unit.Quarter &&
		this.bottomUnit !== BX.Tasks.Date.Unit.Year)
	{
		if (this.isToday(start, end))
		{
			columnClass += " task-gantt-today-column";
		}

		if (this.isWeekend(start, end))
		{
			columnClass += " task-gantt-weekend-column";
		}

		if (this.isHoliday(start, end))
		{
			columnClass += " task-gantt-holiday-column";
		}
	}

	return '<span class="'+ columnClass +'" style="width:' + (duration * unitSize) + 'px">' +
				BX.date.format(this.bottomDateFormat, start, null, true) +
			'</span>';
};

Timeline.prototype.isToday = function(start, end)
{
	return this.chart.currentDate.getUTCMonth() === start.getUTCMonth() &&
			this.chart.currentDate.getUTCDate() === start.getUTCDate();

};

Timeline.prototype.isHoliday = function(start, end)
{
	return this.chart.calendar.isHoliday(start);
};

Timeline.prototype.isWeekend = function(start, end)
{
	return this.chart.calendar.isWeekend(start);
};

Timeline.prototype.getPixelsFromDate = function(date)
{
	var duration = BX.Tasks.Date.getDurationInUnit(this.startDate, date, this.snapUnit);
	return duration * this.getUnitInPixels(this.snapUnit);
};

Timeline.prototype.getDateFromPixels = function(pixels)
{
	var date = BX.Tasks.Date.add(this.startDate, this.snapUnit, Math.floor(pixels / this.getUnitInPixels(this.snapUnit)));
	return this.snapDate(date);

};

Timeline.prototype.getUnitInPixels = function(unit)
{
	return BX.Tasks.Date.getUnitRatio(this.bottomUnit, unit) * this.columnWidth / this.bottomIncrement;
};

Timeline.prototype.snapDate = function(date)
{
	var newDate = new Date(date.getTime());
	if (this.snapUnit === BX.Tasks.Date.Unit.Day)
	{
		var days = BX.Tasks.Date.getDurationInDays(this.startDate, newDate);
		var snappedDays = Math.round(days / this.snapIncrement) * this.snapIncrement;
		newDate = BX.Tasks.Date.add(this.startDate, BX.Tasks.Date.Unit.Day, snappedDays);
	}
	else if (this.snapUnit === BX.Tasks.Date.Unit.Hour)
	{
		var hours = BX.Tasks.Date.getDurationInHours(this.startDate, newDate);
		var snappedHours = Math.round(hours / this.snapIncrement) * this.snapIncrement;
		newDate = BX.Tasks.Date.add(this.startDate, BX.Tasks.Date.Unit.Minute, snappedHours * 60);
	}
	else if (this.snapUnit === BX.Tasks.Date.Unit.Minute)
	{
		var minutes = BX.Tasks.Date.getDurationInMinutes(this.startDate, newDate);
		var snappedMinutes = Math.round(minutes / this.snapIncrement) * this.snapIncrement;
		newDate = BX.Tasks.Date.add(this.startDate, BX.Tasks.Date.Unit.Second, snappedMinutes * 60);
	}
	else if (this.snapUnit === BX.Tasks.Date.Unit.Week)
	{
		newDate.setUTCHours(0, 0, 0, 0);
		var firstWeekDayDelta = newDate.getUTCDay() - this.firstWeekDay;
		if (firstWeekDayDelta < 0)
		{
			firstWeekDayDelta = 7 + firstWeekDayDelta;
		}
		var daysToSnap = Math.round(firstWeekDayDelta / 7) === 1 ? 7 - firstWeekDayDelta : -firstWeekDayDelta;
		newDate = BX.Tasks.Date.add(newDate, BX.Tasks.Date.Unit.Day, daysToSnap);
	}
	else if (this.snapUnit === BX.Tasks.Date.Unit.Month)
	{
		var months = BX.Tasks.Date.getDurationInMonths(this.startDate, newDate) + (newDate.getUTCDate() / BX.Tasks.Date.getDaysInMonth(newDate));
		var snappedMonth = Math.round(months / this.snapIncrement) * this.snapIncrement;
		newDate = BX.Tasks.Date.add(this.startDate, BX.Tasks.Date.Unit.Month, snappedMonth);
	}
	else if (this.snapUnit === BX.Tasks.Date.Unit.Second)
	{
		var seconds = BX.Tasks.Date.getDurationInSeconds(this.startDate, newDate);
		var snappedSeconds = Math.round(seconds / this.snapIncrement) * this.snapIncrement;
		newDate = BX.Tasks.Date.add(this.startDate, BX.Tasks.Date.Unit.Milli, snappedSeconds * 1000);
	}
	else if (this.snapUnit === BX.Tasks.Date.Unit.Milli)
	{
		var millis = BX.Tasks.Date.getDurationInMilliseconds(this.startDate, newDate);
		var snappedMilli = Math.round(millis / this.snapIncrement) * this.snapIncrement;
		newDate = BX.Tasks.Date.add(this.startDate, BX.Tasks.Date.Unit.Milli, snappedMilli);
	}
	else if (this.snapUnit === BX.Tasks.Date.Unit.Year)
	{
		var years = BX.Tasks.Date.getDurationInYears(this.startDate, newDate);
		var snappedYears = Math.round(years / this.snapIncrement) * this.snapIncrement;
		newDate = BX.Tasks.Date.add(this.startDate, BX.Tasks.Date.Unit.Year, snappedYears);
	}
	else if (this.snapUnit === BX.Tasks.Date.Unit.Quarter)
	{
		newDate.setUTCHours(0, 0, 0, 0);
		newDate.setDate(1);
		newDate = BX.Tasks.Date.add(newDate, BX.Tasks.Date.Unit.Month, 3 - (newDate.getUTCMonth() % 3));
	}

	return newDate;
};

Timeline.prototype.scrollToDate = function(date)
{
	if (!BX.Tasks.Date.isDate(date) || date < this.startDate || date > this.endDate)
	{
		return;
	}

	var maxScrollLeft = this.getMaxScrollLeft();
	var dateOffset = this.getPixelsFromDate(date);
	this.setScrollLeft(dateOffset > maxScrollLeft ? maxScrollLeft : dateOffset);
};

Timeline.prototype.scrollTo = function(x)
{
	this.setScrollLeft(Math.min(Math.max(0, x), this.getMaxScrollLeft()));
	this.renderHeader();
};

Timeline.prototype.getScrollLeft = function()
{
	return this.chart.layout.timeline.scrollLeft;
};

Timeline.prototype.setScrollLeft = function(offset)
{
	this.chart.layout.timeline.scrollLeft = offset;
};

Timeline.prototype.getMaxScrollLeft = function()
{
	var scrollWidth = this.getPixelsFromDate(this.endDate);
	var viewport = this.getViewportWidth();
	return scrollWidth - viewport;
};

Timeline.prototype.autoScroll = function()
{
	var viewport = this.getViewportWidth();
	var currentDateInPixels = this.getPixelsFromDate(BX.Tasks.Date.floorDate(this.chart.currentDatetime, this.snapUnit, this.firstWeekDay));
	this.scrollToDate(this.getDateFromPixels(currentDateInPixels - viewport / 4));
};

Timeline.prototype.getViewportWidth = function()
{
	return this.chart.chartContainer.width - this.chart.gutterOffset;
};

Timeline.prototype.getRelativeXY = function(event)
{
	BX.fixEventPageXY(event);

	return {
		x: event.pageX - this.chart.chartContainer.pos.left - this.chart.gutterOffset + this.getScrollLeft(),
		y: event.pageY - this.chart.chartContainer.pos.top + this.chart.layout.timeline.scrollTop
	};
};

function TimelineZoom(levelId)
{
	this.setLevel(levelId);
}

TimelineZoom.prototype.presets = {
	yearquarter:
	{
		columnWidth: 200,
		topUnit: BX.Tasks.Date.Unit.Year,
		topIncrement: 1,
		topDateFormat: "Y",
		bottomUnit: BX.Tasks.Date.Unit.Quarter,
		bottomIncrement: 1,
		bottomDateFormat: "f",
		snapUnit: BX.Tasks.Date.Unit.Day,
		snapIncrement: 1,
		firstWeekDay: 1
	},

	yearmonth:
	{
		columnWidth: 100,
		topUnit: BX.Tasks.Date.Unit.Year,
		topIncrement: 1,
		topDateFormat: "Y",
		bottomUnit: BX.Tasks.Date.Unit.Month,
		bottomIncrement: 1,
		bottomDateFormat: "f",
		snapUnit: BX.Tasks.Date.Unit.Day,
		snapIncrement: 1,
		firstWeekDay: 1
	},

	monthday:
	{
		columnWidth: 24,
		topUnit: BX.Tasks.Date.Unit.Month,
		topIncrement: 1,
		topDateFormat: "f Y",
		bottomUnit: BX.Tasks.Date.Unit.Day,
		bottomIncrement: 1,
		bottomDateFormat: "j",
		snapUnit: BX.Tasks.Date.Unit.Hour,
		snapIncrement: 1
	},

	weekday:
	{
		columnWidth: 48,
		topUnit: BX.Tasks.Date.Unit.Week,
		topIncrement: 1,
		topDateFormat: "j F",
		bottomUnit: BX.Tasks.Date.Unit.Day,
		bottomIncrement: 1,
		bottomDateFormat: "D",
		snapUnit: BX.Tasks.Date.Unit.Hour,
		snapIncrement: 1,
		firstWeekDay: 1
	},

	dayhour: {
		columnWidth: 60,
		topUnit: BX.Tasks.Date.Unit.Day,
		topIncrement: 1,
		topDateFormat: "j F",
		bottomUnit: BX.Tasks.Date.Unit.Hour,
		bottomIncrement: 6,
		bottomDateFormat: "G:i",
		snapUnit: BX.Tasks.Date.Unit.Minute,
		snapIncrement: 15
	},

	hourminute: {
		columnWidth: 48,
		topUnit: BX.Tasks.Date.Unit.Hour,
		topIncrement: 1,
		topDateFormat: "j F H:i",
		bottomUnit: BX.Tasks.Date.Unit.Minute,
		bottomIncrement: 15,
		bottomDateFormat: "i:s",
		snapUnit: BX.Tasks.Date.Unit.Minute,
		snapIncrement: 1
	}
};

TimelineZoom.prototype.levels = [
	/*
	 levelId: {
	 preset
	 columnWidth
	 snapUnit
	 snapIncrement
	 bottomIncrement
	 }
	 */
	{
		id: "yearquarter",
		preset: "yearquarter"
	},
	{
		id: "yearmonth",
		preset: "yearmonth"
	},
	{
		id: "monthday",
		preset: "monthday"
	},
	{
		id: "monthday2x",
		preset: "monthday",
		columnWidth: 48
	},
	{
		id: "weekday",
		preset: "weekday"
	},
	{
		id: "dayhour",
		preset: "dayhour"
	}
].concat(!BX.Scheduler.Util.isMSBrowser() ? [
	{
		id: "daysecondhour",
		preset: "dayhour",
		bottomIncrement: 2,
		snapIncrement: 15
	},
	{
		id: "dayeveryhour",
		preset: "dayhour",
		bottomIncrement: 1,
		snapIncrement: 5
	},
	{
		id: "hourminute",
		preset: "hourminute"
	}
]: []);

TimelineZoom.prototype.setLevel = function(levelId)
{
	var levelIndex = this.getLevelIndex(levelId);
	if (levelIndex === null)
	{
		this.levelIndex = typeof(this.levelIndex) !== "undefined" ? this.levelIndex : this.getLevelIndex("monthday");
	}
	else
	{
		this.levelIndex = levelIndex;
	}
};

TimelineZoom.prototype.getLevelIndex = function(levelId)
{
	for (var i = 0, l = this.levels.length; i < l; i++)
	{
		var level = this.levels[i];
		if (level.id === levelId)
		{
			return i;
		}
	}

	return null;
};

TimelineZoom.prototype.getCurrentLevel = function()
{
	return this.levels[this.levelIndex];
};

TimelineZoom.prototype.getNextLevel = function()
{
	return this.levelIndex === this.levels.length - 1 ?
			this.levels[this.levels.length - 1] :
			this.levels[this.levelIndex + 1];
};

TimelineZoom.prototype.getPrevLevel = function()
{
	return this.levelIndex > 0 ? this.levels[this.levelIndex - 1] : this.levels[0];
};

TimelineZoom.prototype.getPreset = function()
{
	var level = this.levels[this.levelIndex];
	var preset = BX.clone(this.presets[level.preset]);
	for (var option in level)
	{
		if (level.hasOwnProperty(option))
		{
			preset[option] = level[option];
		}
	}

	return preset;
};

/**
 *
 * @param {BX.GanttChart} chart
 * @param {String} id
 * @param {Number} from
 * @param {Number} to
 * @param {GanttDependency.Type} type
 * @constructor
 */
var GanttDependency = function(chart, id, from, to, type)
{
	this.chart = chart;
	this.id = id;
	this.from = from;
	this.to = to;
	this.type = typeof(type) !== "undefined" ? type : GanttDependency.Type.EndToStart;
	this.layout = null;

	this.fromTask = this.chart.getTaskById(this.from);
	this.toTask = this.chart.getTaskById(this.to);
	this.matchWorkTime = this.toTask.matchWorkTime;

	this.updateLag();
};

GanttDependency.prototype.updateLag = function()
{
	if (this.type === GanttDependency.Type.StartToStart)
	{
		if (this.matchWorkTime)
		{
			this.lag = this.chart.calendar.calculateDuration(this.fromTask.dateStart, this.toTask.dateStart);
		}
		else
		{
			this.lag = this.toTask.dateStart - this.fromTask.dateStart;
		}
	}
	else if (this.type === GanttDependency.Type.StartToEnd)
	{
		if (this.matchWorkTime)
		{
			this.lag = this.chart.calendar.calculateDuration(this.fromTask.dateStart, this.toTask.dateEnd);
		}
		else
		{
			this.lag = this.toTask.dateEnd - this.fromTask.dateStart;
		}
	}
	else if (this.type === GanttDependency.Type.EndToEnd)
	{
		if (this.matchWorkTime)
		{
			this.lag = this.chart.calendar.calculateDuration(this.fromTask.dateEnd, this.toTask.dateEnd);
		}
		else
		{
			this.lag = this.toTask.dateEnd - this.fromTask.dateEnd;
		}
	}
	else
	{
		if (this.matchWorkTime)
		{
			this.lag = this.chart.calendar.calculateDuration(this.fromTask.dateEnd, this.toTask.dateStart);
		}
		else
		{
			this.lag = this.toTask.dateStart - this.fromTask.dateEnd;
		}
	}
};

GanttDependency.Type = {
	StartToStart: 0,
	StartToEnd: 1,
	EndToStart: 2,
	EndToEnd: 3
};

GanttDependency.prototype.draw = function()
{
	var lines = this.getPath();
	if (!lines.length)
	{
		return;
	}

	if (this.fromTask.isHidden() || this.toTask.isHidden())
	{
		if (this.layout !== null)
		{
			BX.cleanNode(this.layout, true);
			this.layout = null;
		}

		return;
	}

	var divs = document.createDocumentFragment();
	var line = null;
	for (var i = 0, length = lines.length; i < length; i++)
	{
		line = lines[i];

		var div = document.createElement("div");
		div.className = "task-gantt-link-line";
		div.style.left = line.x + "px";
		div.style.top = line.y + "px";

		if (line.direction === "left")
		{
			div.style.left = (line.x - line.size) + "px";
			div.style.width = line.size + "px";
			div.style.height = "2px";
		}
		else if (line.direction === "right")
		{
			div.style.width = line.size + "px";
			div.style.height = "2px";
		}
		else if (line.direction === "up")
		{
			div.style.top = (line.y - line.size) + "px";
			div.style.height = line.size + "px";
			div.style.width = "2px";
		}
		else
		{
			div.style.height = line.size + "px";
			div.style.width = "2px";
		}

		divs.appendChild(div);
	}

	var arrow = document.createElement("div");
	if (line.direction === "right")
	{
		arrow.style.left = line.x + line.size - 6 + "px";
		arrow.style.top = line.y - 5 + "px";
		arrow.className = "task-gantt-link-arrow task-gantt-link-arrow-right";
	}
	else
	{
		arrow.style.left = line.x - line.size - 6 + "px";
		arrow.style.top = line.y - 5 + "px";
		arrow.className = "task-gantt-link-arrow task-gantt-link-arrow-left";
	}

	divs.appendChild(arrow);

	if (this.layout === null)
	{
		var readOnly = !this.chart.canAddDependency(this.fromTask.id, this.toTask.id);

		this.layout = document.createElement("div");
		this.layout.className = "task-gantt-link" + (readOnly ? " task-gantt-link-read-only" : "");

		if (!readOnly)
		{
			BX.bind(this.layout, "click", BX.proxy(this.onMenuClick, this));
		}

		this.chart.layout.timelineData.appendChild(this.layout);
	}
	else
	{
		if (!this.layout.parentNode)
		{
			this.chart.layout.timelineData.appendChild(this.layout);
		}

		BX.cleanNode(this.layout, false);
	}

	this.layout.appendChild(divs);
};

GanttDependency.prototype.clear = function()
{
	if (this.layout !== null)
	{
		BX.cleanNode(this.layout, true);
		this.layout = null;
	}
};

GanttDependency.prototype.getPath = function()
{
	var edges = this.getEdges();

	var tail = 20;
	var rowHeight = 32;

	var deltaX = edges.endX - edges.startX;
	var deltaY = Math.abs(edges.endY - edges.startY);

	var forward = edges.endX > edges.startX;
	var verticalDirection = edges.endY > edges.startY ? "down" : "up";

	var path = new DependencyPath(edges.startX, edges.startY);

	if (this.type == GanttDependency.Type.StartToStart)
	{
		path.addPoint("left", tail);
		if (forward)
		{
			path.addPoint(verticalDirection, deltaY);
			path.addPoint("right", deltaX);
		}
		else
		{
			path.addPoint("left", Math.abs(deltaX));
			path.addPoint(verticalDirection, deltaY);
		}

		path.addPoint("right", tail);
	}
	else if (this.type == GanttDependency.Type.StartToEnd)
	{
		forward = (edges.endX > (edges.startX - 2 * tail));
		path.addPoint("left", tail);
		if (forward)
		{
			deltaX += 2 * tail;
			path.addPoint(verticalDirection, rowHeight / 2);
			path.addPoint("right", deltaX);
			path.addPoint(verticalDirection, deltaY - (rowHeight / 2));
			path.addPoint("left", tail);
		}
		else
		{
			deltaX += tail;
			path.addPoint(verticalDirection, deltaY);
			path.addPoint("left", Math.abs(deltaX));
		}
	}
	else if (this.type == GanttDependency.Type.EndToStart)
	{
		forward = (edges.endX > (edges.startX + 2 * tail));
		path.addPoint("right", tail);
		if (forward)
		{
			path.addPoint(verticalDirection, deltaY);
			path.addPoint("right", deltaX - tail);
		}
		else
		{
			deltaX -= 2 * tail;

			path.addPoint(verticalDirection, rowHeight / 2);
			path.addPoint("left", Math.abs(deltaX));
			path.addPoint(verticalDirection, deltaY - (rowHeight / 2) );
			path.addPoint("right", tail);
		}
	}
	else if (this.type == GanttDependency.Type.EndToEnd)
	{
		path.addPoint("right", tail);
		if (forward)
		{
			path.addPoint("right", deltaX);
			path.addPoint(verticalDirection, deltaY);
		}
		else
		{
			path.addPoint(verticalDirection, deltaY);
			path.addPoint("left", Math.abs(deltaX));
		}

		path.addPoint("left", tail);
	}

	return path.getPath();
};

GanttDependency.prototype.getEdges = function()
{
	var from = this.fromTask.getCoords();
	var to = this.toTask.getCoords();

	if (this.type === GanttDependency.Type.StartToStart)
	{
		return {
			startX: from.startX,
			startY : from.startY,
			endX : to.startX,
			endY : to.startY
		};
	}
	else if (this.type === GanttDependency.Type.StartToEnd)
	{
		return {
			startX: from.startX,
			startY : from.startY,
			endX : to.endX,
			endY : to.endY
		};
	}

	else if (this.type === GanttDependency.Type.EndToEnd)
	{
		return {
			startX: from.endX,
			startY : from.endY,
			endX : to.endX,
			endY : to.endY
		};
	}
	else
	{
		return {
			startX: from.endX,
			startY : from.endY,
			endX : to.startX,
			endY : to.startY
		};
	}
};
	
GanttDependency.prototype.getMinDate = function()
{
	var startDate = null;
	var duration = this.toTask.calculateDuration();

	if (this.type === GanttDependency.Type.StartToStart)
	{
		startDate = this.fromTask.dateStart;
	}
	else if (this.type === GanttDependency.Type.StartToEnd)
	{
		if (this.matchWorkTime)
		{
			startDate = this.chart.calendar.calculateStartDate(this.fromTask.dateStart, duration);
		}
		else
		{
			startDate = BX.Tasks.Date.add(this.fromTask.dateStart, BX.Tasks.Date.Unit.Milli, -duration);
		}

	}
	else if (this.type === GanttDependency.Type.EndToEnd)
	{
		if (this.matchWorkTime)
		{
			startDate = this.chart.calendar.calculateStartDate(this.fromTask.dateEnd, duration);
		}
		else
		{
			startDate = BX.Tasks.Date.add(this.fromTask.dateEnd, BX.Tasks.Date.Unit.Milli, -duration);
		}
	}
	else
	{
		startDate = this.fromTask.dateEnd;
	}

	if (this.matchWorkTime)
	{
		return this.lag > 0 ?
			this.chart.calendar.calculateEndDate(startDate, this.lag) :
			this.chart.calendar.calculateStartDate(startDate, Math.abs(this.lag));
	}
	else
	{
		return BX.Tasks.Date.add(startDate, BX.Tasks.Date.Unit.Milli, this.lag);
	}
};

GanttDependency.prototype.onMenuClick = function(event)
{
	BX.PopupMenu.show("task-dep-menu", event,
		[{
			text: BX.message("TASKS_GANTT_DELETE_DEPENDENCY"),
			title: BX.message("TASKS_GANTT_DELETE_DEPENDENCY"),
			className: "menu-popup-item-delete",
			onclick: BX.proxy(function() {
				this.chart.removeDependency(this);
				BX.onCustomEvent(this.chart, "onDependencyDelete", [this]);
				BX.PopupMenu.destroy("task-dep-menu");
			}, this)
		}],
		{
			offsetTop: 5,
			offsetLeft: 0,
			angle: true,
			autoHide : true,
			closeByEsc : true,

			bindOptions: { forceBindPosition : true },
			events: { onPopupClose: BX.proxy(this.onMenuClose, this) }
		}
	);
	
	//console.log("Lag", this.lag / 1000 / 3600);

	BX.addClass(this.layout, "task-gantt-link-selected");
};

GanttDependency.prototype.onMenuClose = function(popupWindow, event)
{
	BX.removeClass(this.layout, "task-gantt-link-selected");
	BX.PopupMenu.destroy("task-dep-menu");
};

/**
 *
 * @param {BX.GanttChart} chart
 * @constructor
 */
function DependencyPointer(chart)
{
	this.chart = chart;
	this.layout = BX.create("div", { props: { className: "task-gantt-pointer" }});

	/**
	 * @var {GanttTask}
	 */
	this.fromTask = null;
	this.fromStart = null;

	/**
	 * @var {GanttTask}
	 */
	this.toTask = null;
	this.toStart = null;
	this.edges = null;
	this.error = null;

	this.startX = 0;
	this.startY = 0;

	this.popup = new DependencyPopup(this);
}

DependencyPointer.prototype.getLayout = function()
{
	return this.layout;
};

DependencyPointer.prototype.getTimeline = function(params)
{
	return this.chart.getTimeline();
};

DependencyPointer.prototype.startDrag = function(fromTask, fromStart, event)
{
	event = event || window.event;
	BX.fixEventPageXY(event);
	if (!BX.GanttChart.isLeftClick(event))
	{
		return;
	}

	this.chart.adjustChartContainer();

	this.fromTask = fromTask;
	this.fromStart = fromStart;
	this.toTask = null;
	this.toStart = null;
	this.edges = this.fromTask.getCoords();

	this.startX = this.fromStart ? this.edges.startX - 9 : this.edges.endX + (this.fromTask.isRealDateEnd ? 8 : 12);
	this.startY = (this.fromStart ? this.edges.startY: this.edges.endY) + this.chart.getTimelineDataOffset();

	this.fromTask.startResize(event, BX.proxy(this.endDrag, this), BX.proxy(this.onDrag, this), "default");
};

DependencyPointer.prototype.onDrag = function(event)
{
	this.markDraggableTask();
	this.fromTask.autoResize(event, BX.proxy(this.resizePointer, this));
	this.popup.move(event);
};

DependencyPointer.prototype.endDrag = function(event)
{
	this.hidePointerLine();
	this.unmarkDraggableTask();
	this.unmarkDroppableTask();
	this.popup.hide();

	this.fromTask.endResize(event, BX.proxy(this.endDrag, this), BX.proxy(this.onDrag, this));

	if (this.toTask)
	{
		this.fromTask.setRealDates();
		this.toTask.setRealDates();

		var dependency = this.chart.addDependency(this.fromTask.id, this.toTask.id, this.getType());
		BX.onCustomEvent(this.chart, "onDependencyAdd", [dependency]);
	}
};

DependencyPointer.prototype.getType = function()
{
	if (this.fromStart === false && this.toStart === true)
	{
		return GanttDependency.Type.EndToStart;
	}
	else if (this.fromStart === true && this.toStart === true)
	{
		return GanttDependency.Type.StartToStart;
	}
	else if (this.fromStart === false && this.toStart === false)
	{
		return GanttDependency.Type.EndToEnd;
	}
	else if (this.fromStart === true && this.toStart === false)
	{
		return GanttDependency.Type.StartToEnd;
	}
	else
	{
		return GanttDependency.Type.EndToStart;
	}
};

DependencyPointer.prototype.resizePointer = function(offsetX, offsetY)
{
	this.markDroppableTask();

	var point = this.getTimeline().getRelativeXY(this.fromTask.autoResizeEvent);
	this.drawPointerLine(this.startX, this.startY, point.x, point.y);
};

DependencyPointer.prototype.offsetLine = function(offset)
{
	this.startX += offset;
	this.layout.style.left = (parseInt(this.layout.style.left) || 0) + offset + "px";
};

DependencyPointer.prototype.markDraggableTask = function()
{
	BX.addClass(
		this.fromTask.layout.planBar,
		"task-gantt-bar-plan-draggable task-gantt-pointer-" + (this.fromStart ? "start" : "end")
	);
};

DependencyPointer.prototype.unmarkDraggableTask = function()
{
	BX.removeClass(
		this.fromTask.layout.planBar,
		"task-gantt-bar-plan-draggable task-gantt-pointer-" + (this.fromStart ? "start" : "end")
	);
};

DependencyPointer.prototype.markDroppableTask = function()
{
	this.error = null;

	this.layout.hidden = true;

	var droppableBar = null;
	var droppablePoint = null;
	var element = document.elementFromPoint(this.fromTask.autoResizeEvent.clientX, this.fromTask.autoResizeEvent.clientY);
	if (!element)
	{
		droppablePoint = null;
		droppableBar = null;
	}
	else if (BX.hasClass(element, "task-gantt-bar-plan"))
	{
		droppablePoint = null;
		droppableBar = element;
	}
	else if (BX.hasClass(element.parentNode, "task-gantt-bar-plan"))
	{
		droppablePoint = element;
		droppableBar = element.parentNode;
	}
	else if (BX.hasClass(element.parentNode.parentNode, "task-gantt-bar-plan"))
	{
		droppablePoint = element.parentNode;
		droppableBar = element.parentNode.parentNode;
	}

	if (droppableBar)
	{
		if (this.droppable)
		{
			BX.removeClass(this.droppable, "task-gantt-bar-plan-droppable");
		}

		this.droppable = droppableBar;
		var droppableTask = this.chart.getTaskById(this.droppable.getAttribute("data-task-id"));

		if (!droppableTask || droppableTask === this.fromTask)
		{
			this.droppable = null;
			this.toTask = null;
			this.toStart = null;
		}
		else if (this.chart.isCircularDependency(this.fromTask.id, droppableTask.id))
		{
			this.toTask = null;
			this.toStart = null;
			this.error = BX.message("TASKS_GANTT_CIRCULAR_DEPENDENCY");
		}
		else if (this.fromTask.isChildOf(droppableTask) || droppableTask.isChildOf(this.fromTask))
		{
			this.toTask = null;
			this.toStart = null;
			this.error = BX.message("TASKS_GANTT_RELATION_ERROR");
		}
		else if (!this.chart.canAddDependency(this.fromTask.id, droppableTask.id))
		{
			this.toTask = null;
			this.toStart = null;
			this.error = BX.message("TASKS_GANTT_PERMISSION_ERROR");
		}
		else if (droppablePoint !== null && BX.hasClass(droppablePoint, "task-gantt-point-start"))
		{
			BX.addClass(this.droppable, "task-gantt-pointer-start");
			this.toTask = droppableTask;
			this.toStart = true;
		}
		else if (droppablePoint !== null && BX.hasClass(droppablePoint, "task-gantt-point-end"))
		{
			BX.addClass(this.droppable, "task-gantt-pointer-end");
			this.toTask = droppableTask;
			this.toStart = false;
		}
		else
		{
			BX.removeClass(this.droppable, "task-gantt-pointer-start task-gantt-pointer-end");
			this.toTask = null;
			this.toStart = null;
		}

		if (this.droppable)
		{
			BX.addClass(this.droppable, "task-gantt-bar-plan-droppable");
		}
	}
	else if (this.droppable)
	{
		BX.removeClass(this.droppable, "task-gantt-bar-plan-droppable task-gantt-pointer-start task-gantt-pointer-end");
		this.droppable = null;
		this.toTask = null;
		this.toStart = null;
		this.error = null;
	}

	this.layout.hidden = false;
};

DependencyPointer.prototype.unmarkDroppableTask = function()
{
	if (this.droppable)
	{
		BX.removeClass(this.droppable, "task-gantt-bar-plan-droppable task-gantt-pointer-start task-gantt-pointer-end");
		this.droppable = null;
	}
};

DependencyPointer.prototype.drawPointerLine = function(startX, startY, endX, endY)
{
	//console.log("startX:", startX, " startY:", startY, " endX:", endX, " endY:", endY);

	var width = Math.sqrt( (Math.pow(endX - startX, 2)) + (Math.pow(endY - startY, 2)) ); //http://www.purplemath.com/modules/distform.htm
	var angle = Math.atan((endY - startY) / (endX - startX));
	var quarter = this.getQuarter(startX, endX, startY, endY);
	if (quarter === 2)
	{
		angle += Math.PI;
	}
	else if (quarter === 3)
	{
		angle -= Math.PI;
	}

	var rotate = "rotate(" + angle + "rad)";
	this.layout.style.webkitTransform = rotate;
	this.layout.style.msTransform = rotate;
	this.layout.style.transform = rotate;
	this.layout.style.width = width + "px";

	var left = startX;
	var top = startY;

	if (BX.browser.IsIE8())
	{
		var sin = Math.sin(angle);
		var cos = Math.cos(angle);

		this.layout.style.filter =
			"progid:DXImageTransform.Microsoft.Matrix("+
				"M11 = " + cos + "," +
				"M12 = -" + sin + ","+
				"M21 = " + sin + "," +
				"M22 = " + cos + "," +
			"SizingMethod = 'auto expand')";

		var shiftLeft = Math.abs(startX - endX);
		var shiftTop = Math.abs(endY - startY);

		if (quarter === 1)
		{
			top -= shiftTop;
		}
		else if (quarter === 2)
		{
			left -= shiftLeft;
			top -= shiftTop;
		}
		else if (quarter === 3)
		{
			left -= shiftLeft;
		}
	}

	this.layout.style.left = left + "px";
	this.layout.style.top = top + "px";
};

DependencyPointer.prototype.getQuarter = function(startX, endX, startY, endY)
{
	if (endX >= startX)
	{
		return endY <= startY ? 1 : 4;
	}
	else
	{
		return endY <= startY ? 2 : 3;
	}
};

DependencyPointer.prototype.hidePointerLine = function()
{
	this.layout.style.cssText = "";
};

function DependencyPath(startX, startY)
{
	this.currentPoint = { x: startX, y: startY };
	this.prevDirection = null;
	this.path = [];
}

DependencyPath.prototype.addPoint = function(direction, offset)
{
	var point = {
		x: this.currentPoint.x,
		y: this.currentPoint.y,
		direction: direction,
		size: offset
	};

	if (direction === "up")
	{
		if (this.prevDirection === "right")
		{
			point.y += 2;
			point.size += 2;
		}

		this.currentPoint.y -= offset;
	}
	else if (direction === "right")
	{
		if (this.prevDirection === "up")
		{
			point.x += 2;
			point.size -= 2;
		}

		this.currentPoint.x += offset;
	}
	else if (direction === "down")
	{
		if (this.prevDirection === "left")
		{
			point.y += 2;
			point.size -= 2;
		}

		this.currentPoint.y += offset;
	}
	else if (direction === "left")
	{
		if (this.prevDirection === "down")
		{
			point.x += 2;
			point.size += 2;
		}

		this.currentPoint.x -= offset;
	}

	this.prevDirection = direction;
	this.path.push(point);
};

DependencyPath.prototype.getPath = function()
{
	return this.path;
};

function DependencyPopup(pointer)
{
	this.pointer = pointer;
	this.layout = {
		fromTitle: null,
		fromEdge: null,
		toTitle: null,
		toEdge: null,
		error: null
	};

	this.popup = BX.PopupWindowManager.create("task-gantt-pointer", null, {
		offsetLeft: 5,
		offsetTop: 30,
		content: BX.create("div", { props: { className: "task-gantt-pointer-popup" }, children: [
			BX.create("div", { props: { className: "task-gantt-pointer-popup-row task-gantt-pointer-popup-from"}, children: [
				BX.create("span", { props: { className: "task-gantt-pointer-popup-label"}, text: BX.message("TASKS_GANTT_DEPENDENCY_FROM") + ":"}),
				(this.layout.fromTitle = BX.create("span", { props: { className: "task-gantt-pointer-popup-title"}})),
				(this.layout.fromEdge = BX.create("span", { props: { className: "task-gantt-pointer-popup-edge"}}))
			]}),
			BX.create("div", { props: { className: "task-gantt-pointer-popup-row task-gantt-pointer-popup-to"}, children: [
				BX.create("span", { props: { className: "task-gantt-pointer-popup-label"}, text: BX.message("TASKS_GANTT_DEPENDENCY_TO") + ":"}),
				(this.layout.toTitle = BX.create("span", { props: { className: "task-gantt-pointer-popup-title"}})),
				(this.layout.toEdge = BX.create("span", { props: { className: "task-gantt-pointer-popup-edge"}}))
			]}),
			(this.layout.error = BX.create("div", { props: { className: "task-gantt-pointer-popup-row task-gantt-pointer-popup-error"}}))
		]})
	});
};

DependencyPopup.prototype.move = function(bindElement)
{
	this.layout.fromTitle.innerHTML = this.pointer.fromTask.name;
	this.layout.fromEdge.innerHTML = "(" + (this.pointer.fromStart ? BX.message("TASKS_GANTT_START") : BX.message("TASKS_GANTT_END")) + ")";

	if (this.pointer.error)
	{
		this.layout.error.innerHTML =  this.pointer.error;
		this.layout.toTitle.parentNode.style.display = "none";
	}
	else
	{
		this.layout.error.innerHTML = "";
		this.layout.toTitle.parentNode.style.display = "block";
		this.layout.toTitle.innerHTML = this.pointer.toTask ? this.pointer.toTask.name : "";
		this.layout.toEdge.innerHTML = (this.pointer.toTask ? "(" + (this.pointer.toStart ? BX.message("TASKS_GANTT_START") : BX.message("TASKS_GANTT_END")) + ")" : "&mdash;");
	}

	this.popup.setBindElement(bindElement);
	this.popup.adjustPosition();
	this.popup.show();
};

DependencyPopup.prototype.hide = function()
{
	this.popup.close();
};

/**
 *
 * @param {BX.GanttChart} chart
 * @constructor
 */
var GanttTooltip = function(chart)
{
	this.chart = chart;
	this.initTop = false;
	this.window = null;
	this.start = null;
	this.end = null;
	this.deadline = null;
	this.windowSize = 0;

	var initDate = this.formatDate(this.chart.currentDatetime);

	(this.window = BX.create("div", { props : { className: "task-gantt-hint" }, children :[
		BX.create("span", { props : { className: "task-gantt-hint-names"}, children : [
			BX.create("span", { props : { className: "task-gantt-hint-name"}, text : BX.message("TASKS_GANTT_DATE_START") + ":" }),
			BX.create("span", { props : { className: "task-gantt-hint-name"}, text : BX.message("TASKS_GANTT_DATE_END") + ":" }),
			BX.create("span", { props : { className: "task-gantt-hint-name"}, text : BX.message("TASKS_GANTT_DEADLINE") + ":" })
		]}),
		BX.create("span", { props : { className: "task-gantt-hint-values"}, children : [
			(this.start = BX.create("span", { props : { className: "task-gantt-hint-value"}, html : initDate })),
			(this.end = BX.create("span", { props : { className: "task-gantt-hint-value"}, html : initDate })),
			(this.deadline = BX.create("span", { props : { className: "task-gantt-hint-value"}, html : initDate }))
		]})
	]}));
};

GanttTooltip.prototype.getLayout = function()
{
	return this.window;
};

GanttTooltip.prototype.getTimeline = function(params)
{
	return this.chart.getTimeline();
};

GanttTooltip.prototype.init = function(task)
{
	this.window.style.top = task.layout.row.offsetTop + 9 + "px";
	this.initTop = true;
	this.windowSize = this.window.offsetWidth;
};

GanttTooltip.prototype.show = function(resizerOffset, task)
{
	if (!this.initTop)
		this.init(task);

	var dateStart = task.isRealDateStart ? task.dateStart : null;
	var dateEnd = task.isRealDateEnd ? task.dateEnd : null;

	this.start.innerHTML = this.formatDate(dateStart);
	this.end.innerHTML = this.formatDate(dateEnd);
	this.deadline.innerHTML = this.formatDate(task.dateDeadline);

	var maxOffset = this.chart.chartContainer.width - this.chart.chartContainer.padding - this.chart.gutterOffset + this.getTimeline().getScrollLeft();
	var minOffset = this.chart.chartContainer.padding + this.getTimeline().getScrollLeft();

	if ( (resizerOffset + this.windowSize/2) >= maxOffset)
		this.window.style.left = maxOffset - this.windowSize + "px";
	else if ( (resizerOffset - this.windowSize/2) <= minOffset)
		this.window.style.left = minOffset + "px";
	else if (resizerOffset >= maxOffset)
		this.window.style.left = resizerOffset - this.windowSize + "px";
	else
		this.window.style.left = resizerOffset - this.windowSize/2 + "px";
};

GanttTooltip.prototype.formatDate = function(date)
{
	if (!date)
		return BX.message("TASKS_GANTT_EMPTY_DATE");

	var format = this.chart.dateFormat
		.replace(/YYYY/ig, "<span class=\"task-gantt-hint-year\">" + date.getUTCFullYear().toString().substr(2) + "</span>")
		.replace(/MMMM/ig, BX.util.str_pad_left((date.getUTCMonth()+1).toString(), 2, "0"))
		.replace(/MM/ig, BX.util.str_pad_left((date.getUTCMonth()+1).toString(), 2, "0"))
		.replace(/M/ig, BX.util.str_pad_left((date.getUTCMonth()+1).toString(), 2, "0"))
		.replace(/DD/ig, BX.util.str_pad_left(date.getUTCDate().toString(), 2, "0"));

	var hours = date.getUTCHours().toString();
	if (BX.isAmPmMode())
	{
		var amPm = ' am';
		if (hours > 12)
		{
			hours = hours - 12;
			amPm = ' pm';
		}
		else if (hours == 12)
		{
			amPm = ' pm';
		}
	}

	return format + " " + BX.util.str_pad_left(hours, 2, "0") + ":" + BX.util.str_pad_left(date.getUTCMinutes().toString(), 2, "0") + (amPm != undefined ? amPm : '');
};

GanttTooltip.prototype.hide = function()
{
	this.window.style.left = "-400px";
	this.initTop = false;
};

/**
 *
 * @param {BX.GanttChart} chart
 * @constructor
 */
var DragDrop = function(chart)
{
	this.chart = chart;
	this.stub = null;
	this.dropLine = null;

	/**
	 * @var {GanttTask}
	 */
	this.draggableTask = null;
	this.droppable = null;
	this.error = null;
};

DragDrop.prototype.registerTask = function(object)
{
	if (this.chart.canDragTasks === false)
	{
		return;
	}

	object.onbxdragstart = BX.proxy(this.onDragStart, this);
	object.onbxdrag = BX.proxy(this.onDrag, this);
	object.onbxdragstop = BX.proxy(this.onDragStop, this);
	object.onbxdraghover = BX.proxy(this.onDragOver, this);

	jsDD.registerObject(object);
	jsDD.registerDest(object);
};

DragDrop.prototype.registerProject = function(object)
{
	if (this.chart.canDragTasks === false)
	{
		return;
	}

	jsDD.registerDest(object);
};

DragDrop.prototype.onDragStart = function()
{
	var div = BX.proxy_context;
	var taskId = BX.type.isDomNode(div) ? div.getAttribute("data-task-id") : null;
	this.draggableTask = this.chart.getTaskById(taskId);
	if (!this.draggableTask)
	{
		jsDD.stopCurrentDrag();
		return;
	}

	if (!this.stub)
	{
		this.stub = BX.create("div", { props: { className: "task-gantt-drag-stub"}});
		document.body.appendChild(this.stub);
	}

	if (!this.dropLine)
	{
		this.dropLine = BX.create("div", { props: { className: "task-gantt-drop-line"}});
	}

	if (!this.dropLine.parentNode)
	{
		this.chart.layout.tree.appendChild(this.dropLine);
	}

	this.error = null;
	this.draggableTask.denyItemsHover();
	this.stub.innerHTML = this.draggableTask.name;
	this.stub.style.display = "block";

	BX.TaskQuickInfo.hide();
};

DragDrop.prototype.onDrag = function(x, y)
{
	this.stub.style.left = x + "px";
	this.stub.style.top = y + "px";
};

DragDrop.prototype.onDragStop = function(x, y, event)
{
	this.draggableTask.allowItemsHover(event);
	this.stub.style.display = "none";
	this.dropLine.style.cssText = "";

	if (this.error !== null || !this.droppable || this.draggableTask.id === this.droppable.id)
	{
		return;
	}

	var originalProjectId = this.draggableTask.projectId;
	var originalParentId = this.draggableTask.parentTaskId;
	var target = { id: null, before: true };

	if (this.droppable instanceof GanttProject)
	{
		var previousProject = this.chart.getPreviousProject(this.droppable.id);
		this.chart.moveTaskToProject(this.draggableTask.id, previousProject.id);
		target = this.getTargetTask(this.draggableTask);
	}
	else if (this.droppable === this.chart.layout.treeStub)
	{
		var lastProject = this.chart.getLastProject();
		this.chart.moveTaskToProject(this.draggableTask.id, lastProject.id);
		target = this.getTargetTask(this.draggableTask);
	}
	else
	{
		this.chart.moveTask(this.draggableTask.id, this.droppable.id);
		target.id = this.droppable.id;
		target.before = true;
	}

	BX.onCustomEvent(this.chart, "onTaskMove", [
		this.draggableTask.id,
		target.id,
		target.before,
		originalProjectId !== this.draggableTask.projectId ? this.draggableTask.projectId : null,
		originalParentId !== this.draggableTask.parentTaskId && this.chart.treeMode ? this.draggableTask.parentTaskId : null
	]);
};

DragDrop.prototype.getTargetTask = function(sourceTask)
{
	var target = {
		id: null,
		before: true
	};

	var prev = this.chart.getPreviousTask(sourceTask.id);
	if (prev)
	{
		target.id = prev.id;
		target.before = false;
	}
	else
	{
		var next = this.chart.getNextTask(sourceTask.id);
		if (next)
		{
			target.id = next.id;
			target.before = true;
		}
	}

	return target;
};

DragDrop.prototype.onDragOver = function(destination, x, y)
{
	this.error = null;
	var taskId = destination.getAttribute("data-task-id");
	var projectId = destination.getAttribute("data-project-id");
	var offset = 0;

	var newProject = null;
	var newParentId = null;

	if (taskId)
	{
		this.droppable = this.chart.getTaskById(taskId);
		if (this.droppable && this.droppable.depthLevel === 1 && this.droppable.projectId !== 0)
		{
			offset = -this.chart.firstDepthLevelOffset;
		}

		if (this.droppable)
		{
			newParentId = this.droppable.parentTaskId;
			newProject = this.droppable.project;
		}
	}
	else if (projectId === "stub")
	{
		this.droppable = this.chart.layout.treeStub;
		newParentId = 0;
		newProject = this.chart.getLastProject();
	}
	else if (projectId)
	{
		this.droppable = this.chart.getProjectById(projectId);
		if (this.droppable)
		{
			newParentId = 0;
			newProject = this.chart.getPreviousProject(this.droppable.id);
		}
	}

	if (!this.droppable)
	{
		jsDD.stopCurrentDrag();
		return;
	}

	if (this.droppable instanceof GanttTask && this.droppable.isChildOf(this.draggableTask))
	{
		this.error = true;
	}

	if (
		newProject !== null &&
		this.draggableTask.projectId !== newProject.id &&
		(!this.draggableTask.canEdit || !newProject.canCreateTasks)
	)
	{
		this.error = true;
	}
	
	if (this.draggableTask.parentTaskId !== newParentId && !this.draggableTask.canEdit)
	{
		this.error = true;
	}

	var left = (parseInt(destination.style.paddingLeft, 10) || 0) + offset;
	var top = destination.offsetTop;

	if (this.error === null)
	{
		BX.removeClass(this.dropLine, "task-gantt-drop-line-error");
		BX.removeClass(this.stub, "task-gantt-drag-stub-error");
	}
	else
	{
		BX.addClass(this.dropLine, "task-gantt-drop-line-error");
		BX.addClass(this.stub, "task-gantt-drag-stub-error");
	}

	this.dropLine.style.left = left + "px";
	this.dropLine.style.top = top + "px";
	this.dropLine.style.width = "100%";
};


})();