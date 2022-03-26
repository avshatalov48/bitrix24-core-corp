'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksToolbar != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksToolbar = BX.Tasks.Component.extend({
		sys: {
			code: 'toolbar'
		},
		methodsStatic: {
			instance: {},

			getInstance: function()
			{
				return BX.Tasks.Component.TasksToolbar.instance;
			},

			addInstance: function(obj)
			{
				BX.Tasks.Component.TasksToolbar.instance = obj;
			}
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
				BX.Tasks.Component.TasksToolbar.addInstance(this);

				if (!this.option('counters'))
				{
					this.rerender('');
				}
				else
				{
					this.render();
				}
			},

			bindEvents: function()
			{
				var eventHandlers = {
					user_counter: this.onUserCounter
				};

				BX.addCustomEvent('onPullEvent-tasks', function(command, params) {
					if (eventHandlers[command])
					{
						eventHandlers[command].apply(this, [params]);
					}
				}.bind(this));

				BX.addCustomEvent('Tasks.TopMenu:onItem', function(roleId) {
					this.rerender(roleId);
				}.bind(this));

				BX.addCustomEvent('BX.Kanban.ChangeGroup', function(groupId, oldGroupId) {
					this.option('groupId', groupId);
					this.rerender('');
				}.bind(this));
			},

			onUserCounter: function(data)
			{
				var userId = Number(this.option('userId'));
				var ownerId = Number(this.option('ownerId'));
				var groupId = Number(this.option('groupId'));

				if (
					!this.option('showCounters')
					|| !data.hasOwnProperty(groupId)
					|| userId !== ownerId
					|| userId !== Number(data.userId)
				)
				{
					return;
				}

				var filterId = this.option('filterId') || null;
				if (filterId)
				{
					var roleId = 'view_all';
					var filterObject = BX.Main.filterManager.getById(filterId);
					if (filterObject)
					{
						var fields = filterObject.getFilterFieldsValues();
						roleId = fields.ROLEID || roleId;
					}

					this.option('counters', this.prepareCounters(data[groupId][roleId]));
					this.render();
				}
			},

			prepareCounters: function(counters)
			{
				var codes = {
					total: '',
					expired: 6291456,
					new_comments: 12582912
				};
				var result = {};

				Object.keys(counters).forEach(function(key) {
					result[key] = {
						code: codes[key],
						counter: counters[key]
					};
				});

				return result;
			},

			rerender: function(roleId)
			{

			},

			render: function()
			{
				return;
				var templates = this.option('templates');
				var counters = this.option('counters');
				var foreign_counters = this.option('foreign_counters');
				var messages = this.option('messages');
				var classes = this.option('classes');
				var buttons = this.option('buttons');
				var project_mode = this.option('project_mode');

				var html = [];

				if (
					typeof counters.total === "object"
					&& "counter" in counters.total
					&& counters.total.counter > 0
				)
				{
					html.push(
						templates.total
							.replace('#COUNTER#', counters.total.counter)
							.replace('#TEXT#', messages.total)
					);

					Object.keys(counters).forEach(function(key) {
						var counter = counters[key];
						if (counter.counter > 0 && key !== 'total')
						{
							html.push(
								templates.counter
									.replace('#COUNTER#', counter.counter)
									.replace('#COUNTER#', counter.counter)
									.replace('#COUNTER_ID#', key)
									.replace('#COUNTER_CODE#', counter.code)
									.replace('#TEXT#', messages[key + '_' + BX.Loc.getPluralForm(counter.counter)])
									.replace('#CLASS#', classes[key])
									.replace('#BUTTON#', (buttons[key] || ''))
							);
						}
					}.bind(this));
				}

				if (
					(
						typeof foreign_counters.foreign_expired === "object"
						&& "counter" in foreign_counters.foreign_expired
						&& foreign_counters.foreign_expired.counter > 0
					)
					||
					(
						typeof foreign_counters.foreign_comments === "object"
						&& "counter" in foreign_counters.foreign_comments
						&& foreign_counters.foreign_comments.counter > 0
					)
				)
				{
					html.push(
						templates.foreign
							.replace('#TEXT#', messages.foreign)
					);

					Object.keys(foreign_counters).forEach(function(key) {
						var counter = foreign_counters[key];
						if (counter.counter > 0)
						{
							html.push(
								templates.counter
									.replace('#COUNTER#', counter.counter)
									.replace('#COUNTER#', counter.counter)
									.replace('#COUNTER_ID#', key)
									.replace('#COUNTER_CODE#', counter.code)
									.replace('#TEXT#', messages[key + '_' + BX.Loc.getPluralForm(counter.counter)])
									.replace('#CLASS#', classes[key])
									.replace('#BUTTON#', (buttons[key] || ''))
							);
						}
					}.bind(this));
				}

				if (!html.length)
				{
					html.push(templates.empty.replace('#TEXT#', messages.empty));
				}

				this.scope().innerHTML = html.join('');

				var filterId = this.option('filterId') || null;
				var roleId = this.option('roleId') || 'view_all';
				if (filterId)
				{
					var filterObject = BX.Main.filterManager.getById(filterId);
					if (filterObject)
					{
						var fields = filterObject.getFilterFieldsValues();
						roleId = fields.ROLEID || roleId;
					}
				}

				var elements = this.scope().getElementsByClassName("tasks-counter-container");
				Object.values(elements).forEach(function(element) {
					BX.bind(element, 'click', function(event) {
						BX.PreventDefault(event);
						BX.onCustomEvent("Tasks.Toolbar:onItem", [this.dataset.counterCode, window.location.href]);
					});
					if (
						element.nextSibling
						&& BX.hasClass(element.nextSibling, "tasks-counter-counter-button")
						&& element.nextSibling.dataset.counterId === 'new_comments'
					)
					{
						if (project_mode)
						{
							BX.bind(element.nextSibling, 'click', function() {
								BX.ajax.runAction('tasks.task.comment.readProject', {data: {
										groupId: this.option('groupId') || 0
									}});
							}.bind(this));
						}
						else
						{
							BX.bind(element.nextSibling, 'click', function() {
								BX.ajax.runAction('tasks.task.comment.readAll', {data: {
										groupId: this.option('groupId') || 0,
										userId: this.option('ownerId'),
										role: roleId
									}});
							}.bind(this));
						}

					}
				}.bind(this));
			},
		}
	});
}).call(this);