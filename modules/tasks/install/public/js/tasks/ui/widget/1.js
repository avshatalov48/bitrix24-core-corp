BX.namespace('Tasks.UI');

BX.Tasks.UI.Widget = BX.Tasks.Base.extend({
	options: {
		id: 				false, // instance id, may vary very wide, must be unique on the page if used
		code: 				false, // instance code, may be hardcoded somewhere, non-unique
		scope: 				false,
		removeTemplates: 	false, // remove template nodes from DOM after initialization. Setting to true MAY and WILL increase page load time when widget is massively used
		registerDispatcher: false
	},
	sys: {
		id: false,
		code: false, // class code, hardcoded
		instanceCode: false,
		scope: false,
	},
	methods: {
		construct: function()
		{
			this.vars = {
				cache: {
					control: {}
				}
			};
			this.ctrls = {
			};
			this.tmpls = {
			};

			if(!('querySelector' in document))
			{
				throw new Error('Your browser does not support querySelector');
			}

			if(BX.type.isNotEmptyString(this.option('code')))
			{
				this.instanceCode(this.option('code'));
			}

			if(BX.type.isNotEmptyString(this.opts.id))
			{
				this.sys.id = this.opts.id;
			}

			this.detectScope();
			this.findTemplates();

			this.register();
		},

		destruct: function()
		{
			this.vars = null;
			this.sys = null;
			this.ctrls = null;
			this.tmpls = null;
			this.sys = null;

			// unbind events here
		},

		scope: function()
		{
			return this.sys.scope;
		},

		control: function(id, scope)
		{
			if(!scope)
			{
				if(typeof this.vars.cache.control[id] == 'undefined')
				{
					var control = this.scope().querySelector(this.getControlSearchQuery(id));
					if(control !== null)
					{
						this.vars.cache.control[id] = control;
					}
				}

				return this.vars.cache.control[id];
			}
			else
			{
				return scope.querySelector(this.getControlSearchQuery(id));
			}
		},

		controlAll: function(id, scope)
		{
			scope = scope || this.scope();

			return scope.querySelectorAll(this.getControlSearchQuery(id));
		},

		detectScope: function()
		{
			if(this.opts.scope !== false)
			{
				if(BX.type.isNotEmptyString(this.opts.scope))
				{
					this.sys.scope = BX(this.opts.scope);
				}
				else if(BX.type.isElementNode(this.opts.scope))
				{
					this.sys.scope = this.opts.scope;
				}
			}
			else if(this.opts.id !== false)
			{
				this.sys.scope = BX('bx-component-scope-'+this.opts.id);
			}

			if(!BX.type.isElementNode(this.sys.scope))
			{
				throw new Error('Cant find correct scope ('+this.opts.id+')');
			}
		},

		getFullBxId: function(id)
		{
			var s = [];
			if(this.sys.code !== false)
			{
				s.push(this.sys.code);
			}
			if(this.sys.instanceCode !== false)
			{
				s.push(this.sys.instanceCode);
			}
			s.push(id);

			return s.join('-');
		},

		getControlSearchQuery: function(id)
		{
			return '[data-bx-id~="'+this.getFullBxId(id)+'"]';
		},

		code: function(code)
		{
			if(typeof code != 'undefined' && BX.type.isNotEmptyString(code))
			{
				this.sys.code = code.toString().toLowerCase();
			}
			else
			{
				return this.sys.code;
			}
		},

		id: function()
		{
			return this.opts.id;
		},

		instanceCode: function(code)
		{
			if(typeof code != 'undefined' && BX.type.isNotEmptyString(code))
			{
				this.sys.instanceCode = code.toString().toLowerCase();
			}
			else
			{
				return this.sys.instanceCode;
			}
		},

		findTemplates: function()
		{
			var templates = this.scope().querySelectorAll('script[type="text/html"]');
			for(k = 0; k < templates.length; k++)
			{
				var id = BX.data(templates[k], 'bx-id');

				if(typeof id == 'string' && id.length > 0)
				{
					this.tmpls[id] = templates[k].innerHTML;

					// todo: remove only own templates!
					if(this.opts.removeTemplates)
					{
						BX.remove(templates[k]);
					}
				}
			}
		},

		getNodeByTemplate: function(id, data)
		{
			var template = this.template(id);

			return template.getNode(data, false);
		},

		getHTMLByTemplate: function(id, data)
		{
			var template = this.template(id);

			return template.get(data);
		},

		template: function(id)
		{
			if(typeof BX.Tasks.Util.Template == 'undefined')
			{
				throw new ReferenceError('Template API does not seem to be included');
			}

			var bxId = this.getFullBxId(id);

			if(typeof this.tmpls[bxId] == 'undefined')
			{
				throw new ReferenceError('No such template: '+id+' ('+bxId+')');
			}

			if(typeof this.tmpls[bxId] == 'string')
			{
				this.tmpls[bxId] = BX.Tasks.Util.Template.compile(this.tmpls[bxId]); 
			}

			return this.tmpls[bxId];
		},

		register: function()
		{
			if(this.id() && this.option('registerDispatcher'))
			{
				BX.Tasks.UI.Dispatcher.register(this.id(), this);
			}
		},

		fireEvent: function(name, args)
		{
			this.callMethod(BX.Tasks.Base, 'fireEvent', arguments);

			BX.Tasks.UI.Dispatcher.fireEvent(this, name, args);
		},

		// util
		bindDelegateControl: function(eventName, id, callback, scope)
		{
			scope = scope || this.scope();
			BX.bindDelegate(scope, eventName, {attr: {'data-bx-id': this.getFullBxId(id)}}, callback);
		},

		// css flags
		setCSSFlag: function(flagName, scope)
		{
			this.changeCSSFlag(flagName, scope, true);
		},

		dropCSSFlag: function(flagName, scope)
		{
			this.changeCSSFlag(flagName, scope, false);
		},

		toggleCSSFlag: function(flagName, scope)
		{
			this.changeCSSFlag(flagName, scope, !BX.hasClass(scope, flagName));
		},

		dropCSSFlags: function(flagPattern, scope)
		{
			scope = scope || this.sys.scope;

			var cList = scope.classList;
			flagPattern = new RegExp('^'+flagPattern.replace('*', '[a-z0-9-]*')+'$');

			for(var k = 0; k < cList.length; k++)
			{
				if(cList[k].toString().match(flagPattern))
				{
					BX.removeClass(scope, cList[k]);
				}
			}
		},

		dropAllCSSFlags: function(scope)
		{
			this.dropCSSFlags('*', scope);
		},

		changeCSSFlag: function(flagName, scope, way)
		{
			scope = scope || this.sys.scope;
			if(typeof flagName != 'string' || flagName.length == 0)
			{
				return;
			}

			BX[way ? 'addClass' : 'removeClass'](scope, flagName);
		}
	}
});

BX.Tasks.UI.Dispatcher = BX.Tasks.Base.extend({
	options: {
	},
	methods: {
		construct: function()
		{
			this.vars = {
				registry: {},
				events: {}
			};
		},
		destruct: function()
		{
			this.vars = null;
		},
		register: function(id, instance)
		{
			if(!BX.type.isNotEmptyString(id))
			{
				throw new ReferenceError('Id must not be empty');
			}

			if(instance == null || instance == false)
			{
				throw new ReferenceError('Strange instance');
			}

			if(typeof this.vars.registry[id] != 'undefined')
			{
				throw new ReferenceError('The id "'+id.toString()+'" is already in use in registry');
			}

			this.vars.registry[id] = instance;
		},
		get: function(id)
		{
			if(typeof this.vars.registry[id] == 'undefined')
			{
				throw new ReferenceError('No such id "'+id.toString()+'" in registry');
			}

			return this.vars.registry[id];
		}
	}
});
BX.Tasks.UI.Dispatcher.register = function(id, instance)
{
	BX.Tasks.UI.Dispatcher.makeInstance();

	BX.Tasks.Instances.dispatcher.register(id, instance);
}
BX.Tasks.UI.Dispatcher.get = function(id)
{
	BX.Tasks.UI.Dispatcher.makeInstance();

	return BX.Tasks.Instances.dispatcher.get(id);
}
BX.Tasks.UI.Dispatcher.bindEvent = function(id, name, cb)
{
	if(!BX.type.isNotEmptyString(id))
	{
		throw new TypeError('Bad id: '+id);
	}

	if(!BX.type.isNotEmptyString(name))
	{
		throw new TypeError('Bad event name: '+name);
	}

	if(!BX.type.isFunction(cb))
	{
		throw new TypeError('Callback is not a function to call for: '+id+' '+name);
	}

	BX.Tasks.UI.Dispatcher.makeInstance();

	var dInst = BX.Tasks.Instances.dispatcher;

	if(typeof dInst.vars.events[name] == 'undefined')
	{
		dInst.vars.events[name] = {};
	}

	dInst.vars.events[name][id] = cb;
},
BX.Tasks.UI.Dispatcher.fireEvent = function(ref, name, args)
{
	BX.Tasks.UI.Dispatcher.makeInstance();

	if(typeof ref == 'undefined' || ref == null)
	{
		throw new TypeError('Bad reference to fire event on');
	}

	if(!BX.type.isNotEmptyString(name))
	{
		throw new TypeError('Bad event name: '+name);
	}

	args = args || [];

	var dInst = BX.Tasks.Instances.dispatcher;

	// find instance
	for(var k in dInst.vars.registry)
	{
		if(dInst.vars.registry[k] == ref)
		{
			// find callback
			if(typeof dInst.vars.events[name][k] != 'undefined')
			{
				dInst.vars.events[name][k].apply(ref, args);
			}

			return;
		}
	}
},
BX.Tasks.UI.Dispatcher.makeInstance = function()
{
	if(typeof BX.Tasks.Instances == 'undefined')
	{
		BX.Tasks.Instances = {};
	}
	if(typeof BX.Tasks.Instances.dispatcher == 'undefined')
	{
		BX.Tasks.Instances.dispatcher = new BX.Tasks.UI.Dispatcher();
	}
}