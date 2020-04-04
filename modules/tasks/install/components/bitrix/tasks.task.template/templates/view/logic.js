'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksTaskTemplate != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksTaskTemplate = BX.Tasks.Component.extend({
		sys: {
			code: 'task-template-view'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
				this.initFileView();
			},

			bindEvents: function()
			{
				BX.Tasks.Util.Dispatcher.bindEvent(this.id()+'-buttons', 'button-click', this.onButtonClick.bind(this));

				if(this.option('can').edit)
				{
					// tag update
					BX.addCustomEvent("onTaskTagSelect", BX.proxy(this.syncTags, this));

					// importance update
					this.bindControl('importance-switch', 'click', BX.Tasks.passCtx(this.onImportantButtonClick, this));
				}

				// show alert on ajax errors, reload page then
				BX.addCustomEvent("TaskAjaxError", function(errors) {
					BX.Tasks.alert(errors).then(function(){
						BX.reload();
					});
				});
			},

			onImportantButtonClick: function(node)
			{
				var priority = this.option('data').PRIORITY;
				var newPriority = priority == 2 ? 1 : 2;

				this.callRemote('task.template.update', {id: this.option('data').ID, data: {
					PRIORITY: newPriority
				}}).then(function(result){
					if(result.isSuccess())
					{
						this.option('can').PRIORITY = newPriority;
						BX.toggleClass(node, 'no');
					}
				}.bind(this));
			},

			syncTags: function(tags)
			{
				tags = tags || [];
				if(tags.length)
				{
					var tmpTags = [];
					BX.Tasks.each(tags, function(tag){
						tmpTags.push({NAME: tag.name});
					});
					tags = tmpTags;
				}

				this.callRemote('task.template.update', {id: this.option('data').ID, data: {
					SE_TAG: tags
				}});
			},

			onButtonClick: function(code)
			{
				if (code == 'DELETE')
				{
					this.callRemote('task.template.delete', {id: this.option('data').ID}, {},
						function()
						{
							BX.UI.Notification.Center.notify({
								content: BX.message('TASKS_NOTIFY_TASK_DELETED')
							});

							window.location = this.option('backUrl');
						}
					);
				}
			},

			initFileView: function()
			{
				// "task-detail-description", "task-detail-files", "task-comments-block", "task-files-block"
				if(!this.option('publicMode'))
				{
					BX.Tasks.each(this.controlAll('file-area'), function(area){

						top.BX.viewElementBind(
							area,
							{},
							function(node){
								return BX.type.isElementNode(node) &&
									(node.getAttribute("data-bx-viewer") || node.getAttribute("data-bx-image"));
							}
						);

					});
				}
			}
		}
	});

}).call(this);