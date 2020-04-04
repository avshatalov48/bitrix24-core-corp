BX.namespace('Tasks.Util');

BX.Tasks.Util.Base = function(options)
{
};

BX.mergeEx(BX.Tasks.Util.Base.prototype, {

	// top-level constructor, makes the Earth spin and other stuff
	construct: function()
	{
		// invoke early event binding
		// use it to hang on events that fire on construct
		var eb = this.option('earlyBind');
		if(eb)
		{
			for(var k in eb)
			{
				if(eb.hasOwnProperty(k) && BX.type.isFunction(eb[k]))
				{
					this.bindEvent(k, eb[k]);
				}
			}
		}
	},

	fireEvent: function(name, args)
	{
		BX.onCustomEvent(this, name, args);
	},

	bindEvent: function(name, callback, ctx)
	{
		if(ctx)
		{
			callback = BX.delegate(callback, ctx);
		}

		BX.addCustomEvent(this, name, callback);
	},

	callMethod: function(classRef, name, arguments)
	{
		if(!BX.type.isNotEmptyString(name))
		{
			throw new Error('Illegal method name: '+name);
		}
		if(!BX.type.isFunction(classRef.prototype[name]))
		{
			throw new Error('No such method in class: '+name);
		}

		return classRef.prototype[name].apply(this, arguments);
	},

	callConstruct: function(classRef)
	{
		this.callMethod(classRef, 'construct');
	},

	runParentConstructor: function(owner)
	{
		if(typeof owner.superclass == 'object')
		{
			owner.superclass.constructor.apply(this, [null, true]);
		}
	},

	walkPrototypeChain: function(obj, fn)
	{
		var ref = obj.constructor;
		while(typeof ref != 'undefined' && ref != null)
		{
			fn.apply(this, [ref.prototype, ref.superclass]);

			if(typeof ref.superclass == 'undefined')
			{
				return;
			}

			ref = ref.superclass.constructor;
		}
	},

	destroy: function()
	{
		this.walkPrototypeChain(this, function(proto){
			if(typeof proto.destruct == 'function')
			{
				proto.destruct.call(this);
			}
		});
	},

	option: function(name, value)
	{
		if(typeof value != 'undefined')
		{
			this.opts[name] = value;
		}
		else
		{
			return typeof this.opts[name] != 'undefined' ? this.opts[name] : false;
		}
	},

	optionInteger: function(name)
	{
		var value = parseInt(this.option(name));
		return isNaN(value) ? 0 : value;
	},

	subInstance: function(name, ref)
	{
		this.instances = this.instances || {};

		if(ref)
		{
			if(BX.type.isFunction(ref))
			{
				if(typeof this.instances[name] == 'undefined')
				{
					var instance = ref.call(this);
					if(instance instanceof BX.Tasks.Util.Widget)
					{
						instance.parent(this);
					}

					this.instances[name] = instance;
				}
			}
			else // force rewrite
			{
				this.instances[name] = ref;
			}

			return this.instances[name];
		}
		else
		{
			if(typeof name != 'undefined' && BX.type.isNotEmptyString(name))
			{
				return this.instances[name] ? this.instances[name] : null;
			}

			return null;
		}
	},

    initialized: function()
    {
        return this.sys.initialized;
    },

	// util
	passCtx: function(f)
	{
		// todo: return BX.Tasks.passCtx(f, this); here

		var this_ = this;
		return function()
		{
			var args = Array.prototype.slice.call(arguments);
			args.unshift(this); // this is a ctx of the node event happened on
			return f.apply(this_, args);
		}
	},

	// dispatching
	id: function(id)
	{
		if(typeof id != 'undefined' && BX.type.isNotEmptyString(id))
		{
			this.sys.id = id.toString().toLowerCase();
		}
		else
		{
			return this.sys.id;
		}
	},
	register: function()
	{
		if(this.option('registerDispatcher'))
		{
			var id = this.id();
			if(id)
			{
				BX.Tasks.Util.Dispatcher.register(id, this);
			}
		}
	}
});

BX.Tasks.Util.Base.extend = function(parameters){

	// here "this" refers to the class constructor function

	if(typeof parameters == 'undefined' || !BX.type.isPlainObject(parameters)) 
	{
		parameters = {};
	}

	var child = function(opts, middle){

		// here "this" refers to the object instance to be created

		if(!('runParentConstructor' in this))
		{
			throw new TypeError('Did you miss "new" when creating an instance?');
		}

		this.runParentConstructor(child); // apply all parent constructors

		if(typeof this.opts == 'undefined')
		{
			this.opts = {
				registerDispatcher: false
			};
		}
		if(typeof parameters.options != 'undefined' && BX.type.isPlainObject(parameters.options))
		{
			BX.mergeEx(this.opts, parameters.options);
		}

		if(typeof this.sys == 'undefined')
		{
			this.sys = {
				id: 		    false, // instance id, a unique hash that can be used to refer to an instance among other (must be unique on the page if used)
				initialized:    false
			};
		}
		if(typeof parameters.sys != 'undefined' && BX.type.isPlainObject(parameters.sys))
		{
			BX.mergeEx(this.sys, parameters['sys']);
		}

		delete(parameters);
		delete(child);

		// in the last constructor we run this
		if(!middle)
		{
			// final version of opts array should be ready before "post-constructors" are called
			if(typeof opts != 'undefined' && BX.type.isPlainObject(opts))
			{
				BX.mergeEx(this.opts, opts);
			}

			this.id(this.option('id'));
			this.register(); // register instance in dispatcher, if needed
			this.construct(); // run the top-level constructor

            this.sys.initialized = true;
			// todo: init event here
		}
	};
	child.extend = BX.Tasks.Util.Base.extend; // just a short-cut to extend() function

	BX.extend(child, this);
    parameters.methods = parameters.methods || {};
    parameters.constants = parameters.constants || {};

	if(typeof parameters.methods != 'undefined' && BX.type.isPlainObject(parameters.methods))
	{
		for(var k in parameters.methods)
		{
			if(parameters.methods.hasOwnProperty(k))
			{
				child.prototype[k] = parameters.methods[k];
			}
		}
	}
	if(BX.type.isPlainObject(parameters.methodsStatic))
	{
		for(var ms in parameters.methodsStatic)
		{
			if(parameters.methodsStatic.hasOwnProperty(ms))
			{
				child[ms] = parameters.methodsStatic[ms]; // put function directly to the constructor
			}
		}
	}
	if(typeof parameters.constants != 'undefined' && BX.type.isPlainObject(parameters.constants))
	{
		for(var p in parameters.constants)
		{
			if(parameters.constants.hasOwnProperty(p))
			{
				child.prototype[p] = parameters.constants[p];
			}
		}
	}

	// "virtual" constructor to prevent constructor chain break
	if(typeof parameters.methods.construct != 'function')
	{
		var parent = this;
		child.prototype.construct = function(){
			this.callConstruct(parent);
			delete(parent);
		};
	}
	if(typeof parameters.methods.destruct != 'function')
	{
		child.prototype.destruct = BX.DoNothing();
	}

	return child;
};

BX.Tasks.Util.Dispatcher = BX.Tasks.Util.Base.extend({
	methods: {
		construct: function()
		{
			this.callConstruct(BX.Tasks.Util.Base);

			this.vars = {
				registry: {},
				pend: {
					bind: {},
					call: {},
					find: {}
				}
			};
		},
		destruct: function()
		{
			this.vars = null;
		},
		registerInstance: function(id, instance)
		{
			if(!BX.type.isNotEmptyString(id))
			{
				throw new ReferenceError('Trying to register while id is empty');
			}

			if(instance == null || instance == false)
			{
				throw new ReferenceError('Bad instance');
			}

			if(typeof this.vars.registry[id] != 'undefined')
			{
				throw new ReferenceError('The id "'+id.toString()+'" is already in use in registry');
			}

			this.vars.registry[id] = instance;

			// bind deferred
			if(typeof this.vars.pend.bind[id] != 'undefined')
			{
				for(var k in this.vars.pend.bind[id])
				{
					this.vars.registry[id].bindEvent(this.vars.pend.bind[id][k].event, this.vars.pend.bind[id][k].cb);
				}

				delete(this.vars.pend.bind[id]);
			}

			// call deferred
			if(typeof this.vars.pend.call[id] != 'undefined')
			{
				BX.Tasks.Util.each(this.vars.pend.call[id], function(item){

					if(!(item.method in instance))
					{
						item.pr.reject();
					}
					else
					{
						item.pr.resolve(instance[item.method].call(instance, item.args));
					}
				});

				delete(this.vars.pend.call[id]);
			}

			// get deferred
			if(typeof this.vars.pend.find[id] != 'undefined')
			{
				BX.Tasks.Util.each(this.vars.pend.find[id], function(item){
					item.pr.resolve(instance);
				});

				delete(this.vars.pend.find[id]);
			}
		},
		getRegistry: function()
		{
			var res = {};
			BX.Tasks.each(this.vars.registry, function(inst, k){
				res[k] = inst;
			});

			return res;
		},
		get: function(id)
		{
			if(typeof this.vars.registry[id] == 'undefined')
			{
				return null;
			}

			return this.vars.registry[id];
		},
		find: function(id)
		{
			var p = new BX.Promise();

			id = this.castToLiteralString(id);
			if(!id)
			{
				p.reject(); // todo: pass a error collection as a reason here
				return p;
			}

			var inst = this.get(id);
			if(inst)
			{
				p.resolve(inst);
			}
			else
			{
				if(typeof this.vars.pend.find[id] == 'undefined')
				{
					this.vars.pend.find[id] = [];
				}

				// have to pend
				this.vars.pend.find[id].push({
					pr: p
				});
			}

			return p;
		},
		call: function(id, methodName, args)
		{
			var p = new BX.Promise();

			id = this.castToLiteralString(id);
			methodName = this.castToLiteralString(methodName);

			if(!id || !methodName)
			{
				p.reject(); // todo: pass a error collection as a reason here
				return p;
			}

			var inst = this.get(id);
			if(inst !== null)
			{
				if(!(methodName in inst))
				{
					p.reject(); // todo: pass a error collection as a reason here
					return p;
				}
				else
				{
					p.resolve(inst[methodName].call(inst, args || []));
				}
			}
			else
			{
				// have to pend...
				this.vars.pend.call[id].push({
					method: methodName,
					args: args || [],
					pr: p
				});
			}

			return p;
		},
		/**
		 * bad pattern (use promises instead), will be deprecated
 		 */
		addDeferredBind: function(id, name, cb)
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

			if(typeof this.vars.registry[id] != 'undefined') // no pend, just bind
			{
				this.vars.registry[id].bindEvent(name, cb);
			}
			else
			{
				if(typeof this.vars.pend.bind[id] == 'undefined')
				{
					this.vars.pend.bind[id] = [];
				}
				this.vars.pend.bind[id].push({
					event: name,
					cb: cb
				});
			}
		},
		/**
		 * bad pattern (use promises instead), will be deprecated
		 */
		addDeferredFire: function(id, name, args, params)
		{
			if(!BX.type.isNotEmptyString(id))
			{
				throw new TypeError('Bad id: '+id);
			}

			if(!BX.type.isNotEmptyString(name))
			{
				throw new TypeError('Bad event name: '+name);
			}

			args = args || [];

			if(typeof this.vars.registry[id] != 'undefined') // no pend, just fire
			{
				this.vars.registry[id].fireEvent(name, args);
			}
			else
			{
				// todo (await for 'init' event and then fire)
			}
		},

		/**
		 * @access private
		 * @param arg
		 */
		castToLiteralString: function(arg)
		{
			if(typeof arg == 'undefined' || arg === null)
			{
				return null;
			}

			arg = arg.toString().trim();

			if(!BX.type.isNotEmptyString(arg))
			{
				return null;
			}

			return arg;
		}
	}
});
BX.Tasks.Util.Dispatcher.register = function(id, instance)
{
	BX.Tasks.Util.Dispatcher.getInstance().registerInstance(id, instance);
};
BX.Tasks.Util.Dispatcher.getRegistry = function()
{
	return BX.Tasks.Util.Dispatcher.getInstance().getRegistry();
};
BX.Tasks.Util.Dispatcher.call = function(id, methodName, args)
{
	return BX.Tasks.Util.Dispatcher.getInstance().call(id, methodName, args);
};
BX.Tasks.Util.Dispatcher.find = function(id)
{
	return BX.Tasks.Util.Dispatcher.getInstance().find(id);
};
BX.Tasks.Util.Dispatcher.getInstance = function()
{
	if(typeof BX.Tasks.Singletons == 'undefined')
	{
		BX.Tasks.Singletons = {};
	}
	if(typeof BX.Tasks.Singletons.dispatcher == 'undefined')
	{
		BX.Tasks.Singletons.dispatcher = new BX.Tasks.Util.Dispatcher({
			registerDispatcher: false
		});
	}

	return BX.Tasks.Singletons.dispatcher;
};

/**
 * bad pattern (use promises instead), will be deprecated
 */
BX.Tasks.Util.Dispatcher.bindEvent = function(id, name, cb)
{
	BX.Tasks.Util.Dispatcher.getInstance().addDeferredBind(id, name, cb);
};
/**
 * bad pattern (use promises instead), will be deprecated
 */
BX.Tasks.Util.Dispatcher.fireEvent = function(id, name, args, params)
{
	BX.Tasks.Util.Dispatcher.getInstance().addDeferredFire(id, name, args, params);
};
/**
 * bad pattern (use promises instead), will be deprecated
 */
BX.Tasks.Util.Dispatcher.get = function(id)
{
	return BX.Tasks.Util.Dispatcher.getInstance().get(id);
};