BX.namespace('Tasks.Util');

BX.Tasks.Util.Widget = BX.Tasks.Util.Base.extend({
	options: {
		scope: 				false,
		removeTemplates: 	false, // remove template nodes from DOM after initialization. Setting to true MAY and WILL increase page load time when widget is massively used
		controlBind:        'data-attr', // by default, bind controls using 'data-bx-id-xxx' attribute. Also could be 'class' and bind with 'js-id-xxx' class name
		overrideCodeWith:   false
	},
	sys: {
		code: 				'generic-widget', // class code, hardcoded
		instanceCode: 		false, // UNUSED, deprecated
		scope: 				false, // widget DOM scope
		parent: 			false // parent widget, if any
	},
	methods: {
		construct: function()
		{
			this.callConstruct(BX.Tasks.Util.Base);

			if(typeof this.vars == 'undefined')
			{
				this.vars = {};
			}
			this.vars.cache = {control: {}};
			if(typeof this.ctrls == 'undefined')
			{
				this.ctrls = {};
			}
			if(typeof this.instances == 'undefined')
			{
				this.instances = {};
			}
			if(typeof this.tmpls == 'undefined')
			{
				this.tmpls = false;
			}

			if(!('querySelector' in document))
			{
				throw new Error('Your browser does not support querySelector');
			}

            this.parent(this.option('parent'));

            if(this.option('removeTemplates'))
            {
                this.findTemplates();
            }
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
			return this.detectScope();
		},

		// search down the tree, find first match
		control: function(id, scope, avoidCache)
		{
			if(!scope)
			{
				if(typeof this.vars == 'undefined')
				{
					throw new Error('Forgot to do this.callConstruct()?');
				}

				if(avoidCache || !this.vars.cache.control[id])
				{
					var control = this.scope().querySelector(this.getControlSearchQuery(id));
					if(control !== null)
					{
						this.vars.cache.control[id] = control;
					}

					return control;
				}

				return this.vars.cache.control[id];
			}
			else
			{
				return scope.querySelector(this.getControlSearchQuery(id));
			}
		},

		// search down the tree, find all matches
		controlAll: function(id, scope)
		{
			scope = scope || this.scope();

			return scope.querySelectorAll(this.getControlSearchQuery(id));
		},

		// search up the tree till scope reached, find first match
		controlP: function(id, node, scope)
		{
			return BX.findParent(node, this.getControlMatchCondition(id), scope || this.scope());
		},

		detectScope: function()
		{
            if(this.sys.scope === false)
            {
                if(this.opts.scope !== false)
                {
                    if(BX.type.isNotEmptyString(this.opts.scope))
                    {
                        var scope = BX(this.opts.scope);
                        if(BX.type.isElementNode(scope))
                        {
                            this.sys.scope = scope;
                        }
                        else if(this.parent())
                        {
                            this.sys.scope = this.parent().control(this.opts.scope);
                        }
                    }
                    else if(BX.type.isElementNode(this.opts.scope))
                    {
                        this.sys.scope = this.opts.scope;
                    }
                }
                else if(this.id())
                {
                    this.sys.scope = BX('bx-component-scope-'+this.id());
                }

                if(!BX.type.isElementNode(this.sys.scope))
                {
                    throw new Error('Cant find correct scope for '+this.code()+(this.id() ? '.'+this.id() : ''));
                }
            }

            return this.sys.scope;
		},

		getFullBxId: function(id)
		{
			var s = [];
			if(this.code() !== false)
			{
				s.push(this.code());
			}
			if(this.sys.instanceCode !== false)
			{
				s.push(this.sys.instanceCode);
			}
			s.push(id);

			return s.join('-');
		},

		addId: function(id)
		{
			if(this.opts.controlBind == 'class')
			{
				BX.addClass(this.scope(), 'js-id-'+this.getFullBxId(id));
			}
			else
			{
				// todo
			}
		},

		getControlSearchQuery: function(id)
		{
			if(this.opts.controlBind == 'class')
			{
				return '.js-id-'+this.getFullBxId(id);
			}

			// todo: support also className == js-bx-id + this.getFullBxId(id)
			return '[data-bx-id~="'+this.getFullBxId(id)+'"]';
		},
		getControlMatchCondition: function(id)
		{
			if(this.opts.controlBind == 'class')
			{
				return {className: 'js-id-'+this.getFullBxId(id)};
			}

			// todo: support also className == js-bx-id + this.getFullBxId(id)
			return {attr: {'data-bx-id': new RegExp('(\\s+|^)'+this.getFullBxId(id)+'(\\s+|$)')}}; // search substring, not exact match
		},

		code: function()
		{
            return this.opts.overrideCodeWith ? this.opts.overrideCodeWith : this.sys.code;
		},

		parent: function(widgetInstance)
		{
			if(typeof widgetInstance != 'undefined' && widgetInstance != null)
			{
				this.sys.parent = widgetInstance;
			}
			else
			{
				return this.sys.parent;
			}
		},

        optionP: function(name, value)
        {
            if(typeof value != 'undefined')
            {
                this.callMethod(BX.Tasks.Base, 'option', [name, value]);
            }
            else
            {
                if(typeof this.opts[name] != 'undefined' && this.opts[name] != null)
                {
                    return this.callMethod(BX.Tasks.Base, 'option', [name]);
                }
                if(this.parent() != false)
                {
                    return this.parent().option(name);
                }

                return null;
            }
        },

        // unused
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
            if(this.tmpls === false)
            {
                this.tmpls = {};

	            // todo: also search for <template> tag when get supported
                var templates = this.scope().querySelectorAll('script[type="text/html"]');
                for(var k = 0; k < templates.length; k++)
                {
                    var id = BX.data(templates[k], 'bx-id');

                    if(typeof id == 'string' && id.length > 0)
                    {
                        this.tmpls[id] = templates[k].innerHTML;

                        // todo: remove only own templates!
                        if(this.option('removeTemplates'))
                        {
                            BX.remove(templates[k]);
                        }
                    }
                }
            }
		},

		// deprecated, use getFragmentByTemplate() instead
		getNodeByTemplate: function(id, data)
		{
			var template = this.template(id);

			return template.getNode(data, false);
		},

		// build-in default templates. you can overriding it by including the corresponding <script> tag inside scope
		getDefaultTemplates: function()
		{
			return {};
		},

		getFragmentByTemplate: function(id, data)
		{
			var d = document.createDocumentFragment();
			var nodes = this.getNodeByTemplate(id, data);
			for(var k = 0; k < nodes.length; k++)
			{
				d.appendChild(nodes[k]);
			}

			return d;
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
				throw new ReferenceError('Optional template API does not seem to be included (include \'tasks_util_template\' asset)');
			}

            this.findTemplates();

			var bxId = this.getFullBxId(id);

			var template = null;
			if(this.tmpls[bxId])
			{
				template = this.tmpls[bxId];
			}
			else
			{
				template = this.getDefaultTemplates()[id];
				if(template)
				{
					this.tmpls[bxId] = template;
				}
			}

			if(!template)
			{
				throw new ReferenceError('No such template: '+id+' ('+bxId+')');
			}

			if(BX.type.isNotEmptyString(this.tmpls[bxId]))
			{
				// compile that template and save it in the template hash table
				this.tmpls[bxId] = BX.Tasks.Util.Template.compile(template);
			}

			return this.tmpls[bxId];
		},

        /*
        // todo
		fireEvent: function(name, args)
		{
			this.callMethod(BX.Tasks.Base, 'fireEvent', arguments);

            if(this.id() && this.option('registerDispatcher'))
            {
                BX.Tasks.Dispatcher.fireEvent(this.id(), name, args);
            }
		},
        */

		// util
		// todo: introduce here ...
		bindControl: function(id, eventName, callback)
		{
			BX.bind(this.control(id), eventName, callback);
		},

		// todo: ... and here event classes (like "click.buy" or "keypress.search") to be able to fire\unbind events by "class name", not by callback
        bindDelegateControl: function(id, eventName, callback, scope)
        {
            scope = scope || this.scope();
            BX.bindDelegate(scope, eventName, this.getControlMatchCondition(id), callback);
        },

		bindControlThis: function(id, eventName, callback)
		{
			this.bindControl(id, eventName, BX.delegate(callback, this));
		},

		bindControlPassCtx: function(id, eventName, callback)
		{
			this.bindControl(id, eventName, this.passCtx(callback));
		},

		bindDelegateControlPassCtx: function(id, eventName, callback, scope)
		{
			this.bindDelegateControl(id, eventName, this.passCtx(callback), scope);
		},

		// css flags
        setCSSMode: function(mode, value, scope)
        {
            this.dropCSSFlags(mode+'-*', scope);
            this.setCSSFlag(mode+'-'+value, scope);
        },

		dropCSSFlags: function(flagPattern, scope)
		{
			scope = scope || this.scope();

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

        setCSSFlag: function(flagName, scope)
        {
            this.changeCSSFlag(flagName, true, scope);
        },

        dropCSSFlag: function(flagName, scope)
        {
            this.changeCSSFlag(flagName, false, scope);
        },

        changeCSSFlag: function(flagName, way, scope)
        {
            scope = scope || this.scope();
            if(typeof flagName != 'string' || flagName.length == 0)
            {
                return;
            }

            BX[way ? 'addClass' : 'removeClass'](scope, flagName);
        },

		toggleCSSMap: function(map, scope)
		{
			scope = scope || this.scope();
			var classes = scope.className.split(' ');
			var result = [];
			for(var k = 0; k < classes.length; k++)
			{
				if(!(classes[k] in map))
				{
					result.push(classes[k]); // left unchanged
				}
			}
			for(k in map)
			{
				if(map[k])
				{
					result.push(k);
				}
			}

			scope.className = result.join(' ');
		},

		getDispatcher: function()
		{
			return BX.Tasks.Util.Dispatcher;
		}
	}
});
