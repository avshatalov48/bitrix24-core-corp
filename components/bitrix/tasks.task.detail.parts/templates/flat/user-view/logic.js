BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TaskDetailPartsUserView != 'undefined')
	{
		return;
	}

    BX.Tasks.Component.TaskDetailPartsUserView = BX.Tasks.UserItemSet.extend({
        sys: {
            code: 'user-view'
        },
        options: {
            preRendered: true,
            autoSync:   true,
            role: false,
            multiple: false,
			useSearch: true
        },
        methods: {

            construct: function()
            {
	            this.callConstruct(BX.Tasks.UserItemSet);

                this.syncOnDelay = BX.debounce(this.syncOnDelay, 800);
            },

            bindEvents: function()
            {
                this.callMethod(BX.Tasks.UserItemSet, 'bindEvents');

                if(this.option('role') == 'AUDITORS')
                {
                    this.bindDelegateControl('toggle-auditor', 'click', BX.delegate(this.onToggleImAuditor, this));
                }
            },

            onToggleImAuditor: function()
            {
                var imAuditor = BX.hasClass(this.scope(), 'imauditor');
                var user = this.option('user');
                if(!user)
                {
                    return;
                }

                var value = this.extractItemValue(user);
                if(!value)
                {
                    return;
                }

                var ctx = this;
	            var taskId = parseInt(this.option('taskId'));

                var q = this.getQuery();
                if(q && parseInt(this.option('taskId')))
                {
	                var action = BX.delegate(function(){
		                q.add('task.'+(imAuditor ? 'leaveauditor' : 'enterauditor'), {
			                id: taskId
		                }, {}, function(error){
			                if(error.length == 0)
			                {
				                // add\remove self
				                if(imAuditor)
				                {
					                ctx.deleteItem(value);
				                }
				                else
				                {
					                ctx.addItem(user);
				                }

				                BX[imAuditor ? 'removeClass' : 'addClass'](ctx.scope(), 'imauditor');
			                }
		                });
		                this.attachRightsCheck(taskId, q);
	                }, this);

	                if(imAuditor)
	                {
		                BX.Tasks.confirm(BX.message('TASKS_TTDP_TEMPLATE_USER_VIEW_LEAVE_AUDITOR_CONFIRM'), function(way){
			                if(way)
			                {
				                action.call();
			                }
		                });
	                }
	                else
	                {
		                action.call();
	                }
                }
            },

            processItemAfterCreate: function(value, parameters)
            {
                parameters = parameters || {};

                if(!parameters.loadInitial) // work only for items that added lately, not with .load() inside .construct()
                {
                    var item = this.getItem(value);
                    if(item)
                    {
                        if(item.data().AVATAR == '')
                        {
                            var avatarNode = item.control('avatar');
                            if(BX.type.isElementNode(avatarNode))
                            {
                                avatarNode.style = '';
                            }
                        }
                    }
                }
            },

            // sync on popup close
            onClose: function()
            {
	            if(this.vars.changed)
	            {
		            this.syncAllIfCan();
	            }

	            this.vars.changed = false;
            },
            // and sync on item deleted by pressing "delete" button
            onItemDeleteClicked: function(node)
            {
                var value = this.doOnItem(node, this.deleteItem);
                if(value)
                {
                    this.syncOnDelay();
                }
            },
            syncOnDelay: function()
            {
                this.syncAllIfCan();
            },

            syncAll: function()
            {
                var q = this.getQuery();
                if(q && parseInt(this.option('taskId')) && this.option('role'))
                {
                    var arg = [];

                    for (var k in this.vars.items)
                    {
                        var itemData = BX.clone(this.vars.items[k].data());

                        arg.push({
	                        ID: this.vars.items[k].value(),
	                        NAME: itemData.NAME,
	                        LAST_NAME: itemData.LAST_NAME,
	                        EMAIL: itemData.EMAIL
                        });
                    }

                    var role = this.option('role');
                    if(role == 'RESPONSIBLE')
                    {
	                    role = 'SE_RESPONSIBLE';
                    }
	                else if(role == 'AUDITORS')
                    {
	                    role = 'SE_AUDITOR';
                    }
	                else if(role == 'ACCOMPLICES')
                    {
	                    role = 'SE_ACCOMPLICE';
                    }
	                else
                    {
	                    return;
                    }

	                var data = {};
	                data[role] = arg;

                    q.add('task.update', {
                        id: parseInt(this.option('taskId')),
                        data: data
                    }, {code: 'update_member'});
                }
            },

            getItemClass: function()
            {
                return BX.Tasks.Util.ItemSet.Item;
            },

	        attachRightsCheck: function(taskId, q)
	        {
		        var toTasks = this.option('pathToTasks');

		        q.add('task.checkcanread', {
			        id: taskId
		        }, {}, function(error, data){

			        if(!data.RESULT.READ) // we lost task access, sadly leaving
			        {
				        if(toTasks)
				        {
					        window.document.location = toTasks;
				        }
				        else
				        {
					        window.document.location.reload();
				        }
			        }
		        });
	        }
        }
    });

})();