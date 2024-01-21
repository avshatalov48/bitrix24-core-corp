'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.EmployeePlan != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.EmployeePlan = BX.Tasks.Component.extend({
		options: {
			pageSize: 15, // page size for user list
			userProfileUrl: "/company/personal/user/#user_id#/",
			userNameTemplate: '#NAME#',
			gutterOffset: 300,
			zoomLevel: 'monthday2x'
		},
		sys: {
			code: 'empplan'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);

				this.vars.lastPage = 1;
				this.vars.lastCount = 0; // actually, unknown
				this.vars.lastFilter = this.option('filter');
				this.vars.queryLock = false;

				this.subInstance('users', new BX.Tasks.Util.Collection({keyField: 'VALUE'})).load(
					this.option('departmentUsers')
				);
				this.subInstance('departments', new BX.Tasks.Util.Collection({keyField: 'VALUE'})).load(
					this.option('companyDepartments')
				);

				this.initSelectors();
				this.initRouter();
				this.initGrid();

				this.onSearch = BX.debounce(this.onSearch, 300, this);
			},

			initSelectors: function()
			{
				const filter = this.vars.lastFilter;

				this.subInstance('status-selector', new BX.Tasks.Util.SelectBox({
					scope: this.control('status-selector'),
					items: this.option('statusList'),
					selected: filter.TASK.STATUS,
					notSelectedLabel: BX.message('TASKS_COMMON_ANY')
				})).bindEvent('change', BX.delegate(this.statusChanged, this));

				this.subInstance('department-selector', new BX.Tasks.Util.SelectBox({
					scope: this.control('department-selector'),
					items: this.subInstance('departments').export(),
					selected: filter.MEMBER.DEPARTMENT[0],
					notSelectedLabel: BX.message('TASKS_COMMON_ALL')
				})).bindEvent('change', BX.delegate(this.departmentChanged, this));

				const us = this.subInstance('user-selector', new BX.Tasks.Util.ComboBox({
					scope: this.control('user-selector'),
					items: this.getDepartmentUsers(filter.MEMBER.DEPARTMENT[0]),
					selected: filter.MEMBER.USER[0],
					notSelectedLabel: BX.message('TASKS_COMMON_ALL'),
				}));
				us.bindEvent('change', BX.delegate(this.userChanged, this));
				us.setFilterHandlerFabric(function(value){
					return {'#IX': value};
				});

				this.subInstance('range', new BX.Tasks.Component.EmployeePlan.DateRange({
					scope: this.control('date-range')
				})).bindEvent('change', BX.delegate(this.datesChanged, this));
			},

			statusChanged: function(value)
			{
				if(value)
				{
					this.vars.lastFilter.TASK.STATUS = value;
				}
				else
				{
					delete(this.vars.lastFilter.TASK.STATUS);
				}

				this.onSearch();
			},

			departmentChanged: function(value)
			{
				this.vars.lastFilter.MEMBER.DEPARTMENT = value ? [value] : [];

				this.onSearch();
				this.updateUserSelector(value);
			},

			getDepartmentUsers: function(dep)
			{
				var result = result = this.subInstance('users');

				if(dep)
				{
					var deps = this.subInstance('departments');
					var uD;
					var D = deps.getByKey(dep);

					// filter by department
					result = result.find(function(field, value){

						if(field == 'DEP')
						{
							if(!value) // no department, skip
							{
								return false;
							}

							uD = deps.getByKey(value);
							if(!uD) // no department existed, skip
							{
								return false;
							}

							return uD.L >= D.L && uD.R <= D.R;
						}

					}, this);
				}

				return result.each(function(item){

					// make index
					if(typeof item.IX == 'undefined')
					{
						var ix = item.DISPLAY.toLowerCase().split(' ').map(function(iVal){
							return iVal.trim();
						});
						ix.push(item.VALUE.toString().trim());

						item.IX = ix;
					}

				}).export();
			},

			updateUserSelector: function(value)
			{
				var us = this.subInstance('user-selector');
				us.clear();
				us.load(this.getDepartmentUsers(this.vars.lastFilter.MEMBER.DEPARTMENT[0]));
			},

			userChanged: function(value)
			{
				this.vars.lastFilter.MEMBER.USER = value ? [value] : [];

				this.onSearch();
			},

			datesChanged: function(from, to)
			{
				this.vars.lastFilter.TASK.DATE_RANGE = {
					FROM: from,
					TO: to
				};

				this.onSearch();
			},

			initRouter: function()
			{
				this.instances.router = new BX.Tasks.Util.Router();
				if(this.vars.lastFilter.FILTER_OWNER == BX.message('USER_ID'))
				{
					this.updateQueryString();
				}
			},

			updateQueryString: function()
			{
				var f = this.vars.lastFilter;
				var data = {};

				if(typeof f.FILTER_OWNER == 'undefined')
				{
					data.FILTER_OWNER = BX.message('USER_ID');
				}
				else
				{
					data.FILTER_OWNER = f.FILTER_OWNER;
				}

				// pack filter...
				// todo: make it automatic

				if(f.MEMBER.DEPARTMENT[0])
				{
					data['MEMBER[DEPARTMENT][]'] = f.MEMBER.DEPARTMENT[0];
				}
				if(f.MEMBER.USER[0])
				{
					data['MEMBER[USER][]'] = f.MEMBER.USER[0];
				}
				if(f.TASK.STATUS)
				{
					data['TASK[STATUS]'] = f.TASK.STATUS;
				}

				data['TASK[DATE_RANGE][FROM]'] = f.TASK.DATE_RANGE.FROM;
				data['TASK[DATE_RANGE][TO]'] = f.TASK.DATE_RANGE.TO;

				this.instances.router.setQueryString(data);
			},

			initGrid: function()
			{
				var calendarSettings = this.option('calendarSettings');

				this.instances.grid = new BX.Scheduler.View({

					headerText: BX.message('TASKS_EMPLOYEEPLAN_TEMPLATE_GRID_TITLE'),
					renderTo: this.control('result'),

					currentDatetime: BX.parseDate(this.option('currentDayTime')),
					datetimeFormat: BX.message('FORMAT_DATETIME'),
					dateFormat: BX.message('FORMAT_DATE'),

					gutterOffset: parseInt(this.option('gutterOffset'), 10),
					zoomLevel: this.option('zoomLevel'),

					// calendar settings
					weekends: calendarSettings.WEEK_END,
					holidays: calendarSettings.HOLIDAYS,
					firstWeekDay: calendarSettings.WEEK_START,
					worktime: calendarSettings.HOURS,

					events : {
						onGutterResize: function(gutterOffset)
						{
							BX.userOptions.save('tasks', 'scheduler', 'gutter_offset', gutterOffset);
						},

						onZoomChange: function(zoomLevel)
						{
							BX.userOptions.save('tasks', 'scheduler', 'zoom_level', zoomLevel);
						}
					}
				});

				// render initial data
				this.appendGridData(this.option('gridData').DATA);
				this.setGridVerticalCountTotal(this.option('gridData').COUNT_TOTAL);
				this.redrawGrid(true);
			},

			bindEvents: function()
			{
				this.bindControlThis('search', 'click', this.onSearch);
				this.bindControlThis('search-more', 'click', this.onSearchMore);
			},

			getOpenTaskPath: function(taskId)
			{
				return this.option('pathToUserTasks')
					.replace('#action#', 'view')
					.replace('#task_id#', taskId)
				;
			},

			adaptGridData: function(data)
			{
				var gridData = data;

				var users = [];
				var userIx = {};
				var userPx = {};
				var tasks = [];
				var k;

				var user;
				for (k = 0; k < gridData.USERS.length; k++)
				{
					user = gridData.USERS[k];

					users.push({
						id: (user.DEPARTMENT_ID ? 'D' + user.DEPARTMENT_ID : '') + 'U' + user.ID,
						name: BX.formatName({
								NAME: user['NAME'],
								LAST_NAME: user['LAST_NAME'],
								SECOND_NAME: user['SECOND_NAME'],
								LOGIN: user['LOGIN']
							},
							this.option('userNameTemplate'),
							'Y'
						),
						parentId: (user.DEPARTMENT_ID ? 'D' + user.DEPARTMENT_ID : false),
						parentDepartment: (user.DEPARTMENT_ID ? user.DEPARTMENT_ID : null),
						link: this.option('userProfileUrl').replace('#user_id#', user.ID)
					});
					userIx[user.ID] = [];
					userPx[user.ID] = (userPx[user.ID] || []).concat(user.DEPARTMENT_ID ? [user.DEPARTMENT_ID] : []);
				}

				var task;
				for (k = 0; k < gridData.TASKS.length; k++)
				{
					task = gridData.TASKS[k];

					if (task.RESPONSIBLE_ID in userIx && !userIx[task.RESPONSIBLE_ID].includes(task.ID))
					{
						for (var i = 0, list = userPx[task.RESPONSIBLE_ID]; i < list.length; i++)
						{
							tasks.push(this.createTaskItem(task, 'D' + list[i] + 'U' + task.RESPONSIBLE_ID));
						}

						tasks.push(this.createTaskItem(task, 'U' + task.RESPONSIBLE_ID));
						userIx[task.RESPONSIBLE_ID].push(task.ID);
					}

					if (task.ACCOMPLICE_ID in userIx && !userIx[task.ACCOMPLICE_ID].includes(task.ID))
					{
						for (var i = 0, list = userPx[task.ACCOMPLICE_ID]; i < list.length; i++)
						{
							tasks.push(this.createTaskItem(task, 'D' + list[i] + 'U' + task.ACCOMPLICE_ID));
						}

						tasks.push(this.createTaskItem(task, 'U' + task.ACCOMPLICE_ID));
						userIx[task.ACCOMPLICE_ID].push(task.ID);
					}
				}

				return {tasks: tasks, users: users};
			},

			createTaskItem: function(task, resourceId)
			{
				const item = {
					id: resourceId + 'T' + task.ID,
					resourceId: resourceId,
					entityId: task.ID,
					name: task.TITLE,
					startDate: BX.parseDate(task.START_DATE_PLAN),
					endDate: BX.parseDate(task.END_DATE_PLAN),
				};

				if (task.ACTION.READ)
				{
					item.pathToTask = this.getOpenTaskPath(task.ID);
				}
				else
				{
					item.className = 'scheduler-event-inaccessible';
				}

				return item;
			},

			resetGrid: function()
			{
				this.vars.lastPage = 0;
				this.instances.grid.clearAll();
			},

			redrawGrid: function(onInit)
			{
				if (!onInit)
				{
					var scrollDate = this.instances.grid.getCurrentScrollDate();
					this.instances.grid.render();
					this.instances.grid.timeline.scrollToDate(scrollDate);
				}
				else
				{
					this.instances.grid.render();
				}
			},

			setGridVerticalCountTotal: function(count)
			{
				if(BX.type.isNumber(count))
				{
					this.vars.lastCount = parseInt(count);
				}

				var showBtn = this.vars.lastCount - (this.vars.lastPage * this.optionInteger('pageSize')) > 0;
				BX[showBtn ? 'removeClass' : 'addClass'](this.control('search-more'), 'no-display');
			},

			getDepartments: function()
			{
				if(!this.vars.deps)
				{
					this.vars.deps = {};
					BX.Tasks.each(this.option('companyDepartments'), function(dep){
						this.vars.deps[dep.ID] = dep;
					}, this);
				}

				return this.vars.deps;
			},

			appendGridData: function(data)
			{
				data = this.adaptGridData(data);

				var depsData = this.getDepartments();
				var deps = [];
				var depUsers = [];
				var noDepUsers = [];
				var dep = null;
				for(var k = 0; k < data.users.length; k++)
				{
					if(data.users[k].parentDepartment !== null && typeof depsData[data.users[k].parentDepartment] != 'undefined')
					{
						dep = depsData[data.users[k].parentDepartment];
						deps.push({
							id: 'D'+dep.ID,
							name: dep.NAME,
							type: 'BX.Scheduler.ResourceGroup'
						});
						depUsers.push(data.users[k]);
					}
					else
					{
						noDepUsers.push(data.users[k]);
					}
				}

				this.instances.grid.getResourceStore().load(noDepUsers);
				this.instances.grid.getResourceStore().load(deps);
				this.instances.grid.getResourceStore().load(depUsers);
				this.instances.grid.getEventStore().load(data.tasks);
			},

			toggleSearchLoading: function(way)
			{
				if(way && this.vars.queryLock)
				{
					return false;
				}

				this.vars.queryLock = way;
				BX[way ? 'addClass' : 'removeClass'](this.control('search-more'), 'ui-btn-clock');

				return true;
			},

			onSearch: function()
			{
				this.resetGrid();
				this.updateQueryString();
				this.showNextGridPage();
			},

			onSearchMore: function()
			{
				if(!this.toggleSearchLoading(true))
				{
					return;
				}

				this.showNextGridPage();
			},

			showNextGridPage: function()
			{
				BX.ajax.runComponentAction('bitrix:tasks.employee.plan', 'getGridRegion', {
					mode: 'class',
					data: {
						filter: this.vars.lastFilter,
						nav: {
							PAGE: this.vars.lastPage + 1
						},
						parameters: {
							GET_COUNT_TOTAL: this.vars.lastPage === 0
						}
					}
				}).then(
					function(response)
					{
						if (
							!response.status
							|| response.status !== 'success'
						)
						{
							BX.reload();
							return;
						}

						this.vars.lastPage++;
						this.appendGridData(response.data.DATA);
						this.setGridVerticalCountTotal(response.data.COUNT_TOTAL);
						this.toggleSearchLoading(false);
						this.redrawGrid();

					}.bind(this),
					function(response)
					{
						BX.reload();
					}.bind(this)
				);
			}
		}
	});

	/**
	 * Sub controller for date range widget
	 */
	BX.Tasks.Component.EmployeePlan.DateRange = BX.Tasks.Util.Widget.extend({
		sys: {
			code: 'date-range'
		},
		options: {
			controlBind: 'class',
			datePlanLimit: 7776000 // 90 days in seconds
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.Widget);

				this.instances.from = new BX.Tasks.Util.DatePicker({
					scope: this.control('from-container'),
					controlBind: 'class',
					displayFormat: 'system-short'
				});
				this.instances.to = new BX.Tasks.Util.DatePicker({
					scope: this.control('to-container'),
					controlBind: 'class',
					displayFormat: 'system-short'
				});

				this.bindEvents();
			},

			bindEvents: function()
			{
				this.bindControlThis('show', 'click', this.showSelector);

				this.instances.from.bindEvent('change', BX.delegate(function(){
					this.onBorderChange(true, false);
				}, this));
				this.instances.to.bindEvent('change', BX.delegate(function(){
					this.onBorderChange(false, true);
				}, this));
			},

			showSelector: function()
			{
				this.changeCSSFlag('active-calendar', true);
			},

			onBorderChange: function(fromChanged, toChanged)
			{
				if(this.vars.changeLock)
				{
					return;
				}

				// check range
				var from = this.instances.from.getTimeStamp();
				var fromAfter = from;

				var to = this.instances.to.getTimeStamp();
				var toAfter = to;

				var limit = this.optionInteger('datePlanLimit');

				if(Math.abs(toAfter - fromAfter) > limit)
				{
					if((fromChanged && toChanged) || fromChanged)
					{
						toAfter = fromAfter + limit; // adjust right border
					}
					else if(toChanged)
					{
						fromAfter = toAfter - limit; // adjust left border
					}
				}

				if(fromAfter > toAfter)
				{
					var tmp = toAfter;
					toAfter = fromAfter;
					fromAfter = tmp;
				}

				this.vars.changeLock = true;

				var node = null;

				if(from != fromAfter)
				{
					this.instances.from.setTimeStamp(fromAfter);
					node = this.instances.from.scope();
				}
				if(to != toAfter)
				{
					this.instances.to.setTimeStamp(toAfter);
					node = this.instances.to.scope();
				}

				if(node)
				{
					BX.Tasks.Util.hintManager.showDisposable(
						node,
						BX.message('TASKS_EMPLOYEEPLAN_TEMPLATE_DATE_AUTO_CHANGE').replace('#NUM#', 90),
						'TASK_EMPLOYEEPLAN_DAC'
					);
				}

				this.vars.changeLock = false;

				this.fireEvent('change', [
					this.instances.from.getValue(),
					this.instances.to.getValue()
				]);
			}
		}
	});

}).call(this);