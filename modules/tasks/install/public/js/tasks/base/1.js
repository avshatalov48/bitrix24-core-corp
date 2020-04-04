BX.namespace('Tasks');

BX.Tasks.Base = function(options)
{
}

BX.merge(BX.Tasks.Base.prototype, {

	fireEvent: function(name, args)
	{
		BX.onCustomEvent(this, name, args);
	},

	bindEvent: function(name, callback)
	{
		BX.addCustomEvent(this, name, callback);
	},

	getEventPrefix: function()
	{
		return typeof this.opts.prefix == 'undefined' ? '' : this.opts.prefix.toString();
	},

	debounceBuffered: function(fn, mergeFn, timeout, ctx)
	{
		var timer = 0;
		var buffer = {};

		return function()
		{
			var args = Array.prototype.slice.call(arguments);
			var bufferized = args.shift();

			if(BX.type.isFunction(mergeFn))
			{
				mergeFn.apply(buffer, [bufferized]);
			}

			ctx = ctx || this;

			clearTimeout(timer);

			timer = setTimeout(function()
			{
				args.unshift(BX.clone(buffer));

				fn.apply(ctx, args);
				buffer = {};
			}, timeout);
		}
	},

	callMethod: function(classRef, name, arguments)
	{
		return classRef.prototype[name].apply(this, arguments);
	},

	runPostConstructors: function()
	{
		var stack = [];

		this.walkPrototypeChain(this, function(proto){
			if(typeof proto.construct == 'function')
			{
				stack.unshift(proto.construct);
			}
		});

		for(var k = 0; k < stack.length; k++)
		{
			stack[k].call(this);
		}
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

	getClassByChain: function(chain)
	{
		var scope = window;
		for(var i = 0; i < chain.length; i++)
		{
			if(typeof scope[chain[i]] == 'undefined')
			{
				return null;
			}

			scope = scope[chain[i]];
		}

		return scope;
	},

	option: function(name)
	{
		return this.opts[name];
	},

	// util
	passCtx: function(f)
	{
		var this_ = this;
		return function()
		{
			var args = Array.prototype.slice.call(arguments);
			args.unshift(this); // this is a ctx of the node event happened on
			return f.apply(this_, args);
		}
	}
});

BX.Tasks.Base.extend = function(parameters){

	if(typeof parameters == 'undefined' || !BX.type.isPlainObject(parameters)) 
	{
		parameters = {};
	}

	var child = function(opts, middle){

		// inheritance
		this.isuiWidget = true; // prevent BX.merge() from going deeper on a widget instance
		this.runParentConstructor(child); // apply all parent constructors

		if(typeof this.opts == 'undefined')
		{
			this.opts = {};
		}
		if(typeof parameters.options != 'undefined' && BX.type.isPlainObject(parameters.options))
		{
			BX.merge(this.opts, parameters.options);
		}

		// spike for BX.Tasks.UI.Widget, other keys of parameters are either future-reserved, or equal to "options" and "methods"
		if(typeof parameters.sys != 'undefined' && BX.type.isPlainObject(parameters.sys))
		{
			if(typeof this.sys == 'undefined')
			{
				this.sys = {};
			}
			BX.merge(this.sys, parameters['sys']);
		}

		delete(parameters);
		delete(child);

		// in the last constructor we run this
		if(!middle)
		{
			// final version of opts array should be ready before "post-constructors" are called
			if(typeof opts != 'undefined' && BX.type.isPlainObject(opts))
			{
				BX.merge(this.opts, opts);
			}

			this.runPostConstructors(); // event bind, aux data struct init, etc ...
		}
	};
	child.extend = BX.Tasks.Base.extend; // just a short-cut to extend() function

	BX.extend(child, this);
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

	// anonymous construct & destruct to prevent prototype hierarchy fall through the proto-chain walking
	if(typeof parameters.methods.construct != 'function')
	{
		child.prototype.construct = BX.DoNothing();
	}
	if(typeof parameters.methods.destruct != 'function')
	{
		child.prototype.destruct = BX.DoNothing();
	}

	return child;
};