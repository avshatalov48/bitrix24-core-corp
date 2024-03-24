/*
This file is temporal. Its content will be lately relocated to "main" module.
*/

BX.namespace('BX.Mobile.Tasks');
window.Application = window.Application || window.BXMobileAppContext;
//////////////////////////////
// base widget
//////////////////////////////

BX.Mobile.Tasks.widget = function(opts){

	BX.merge(this, {
		opts: {
			// mandatory
			scope:						false, // it should be either native dom object, or string that represents node id

			// optional
			controls:					{}, // known controls
			instances:					{}, // known instances
			instanceCode:				false,

			// behaviour
			bindEvents:					{}, // event pre-binding (when use this, keep in mind that the resulting instance could not be fully formed yet)
			removeTemplates:			true, // remove script nodes after search
			useSpawn:					false, // if set to true, you can do .spawn() on this object
			initializeByGlobalEvent:	false, // if equals to a not-empty string, initialization will be performed only by event with that name, being fired on document
			globalEventScope:			'document', // initializeByGlobalEvent scope (could be 'document' or 'window')
			registerDispatcher:			true, // if true and instanceCode is not empty, register this instance at instance dispatcher
			usePull:					false, // if true, a watch will hang on pull events
			setTitle:					true,
			setPullDown:				true
		},
		vars: {}, // significant variables
		ctrls: {'A':{},'S':{}}, // cache of controls
		tmpls: {}, // templates
		sys: {
			scope:				null,
			stack:				{init:[]},
			instances:			{},
			/*
			*	Two instances of widget with the same code CAN NOT have the same scope.
			*	One scope can contain multiple inner scopes for widgets with different codes.
			*	If you intend to have one scope inside another scope for the same class of widget,
				please provide some instanceCode.
			*/
			classCode:					'widget', // only [a-z0-9_-] allowed, should be set for each derived class

			// If differs from null, used to register an instace at dispatcher object.
			// Also, can be used to share the same scope between two instances of the same class
			instanceCode:				false,

			initialized:				false
		}
	});

	this.pushFuncStack('init', BX.Mobile.Tasks.widget);

	this.isuiWidget = true; // prevent BX.merge() from going deeper on a widget instance
};
// the following functions can be overrided with inheritance
BX.merge(BX.Mobile.Tasks.widget.prototype, {

	////////////////////////////
	/// about initialization

	// only basic things here
	preInit: function(){
		var ctx = this,
			so = this.opts,
			sc = this.ctrls,
			code = this.sys.classCode;

		if(!('querySelector' in document))
			throw new Error('Your browser does not support querySelector');

		if(!code.match(/^[a-zA-Z0-9-_]+$/))
			throw new Error('Only letters, digitis, "-" and "_" allowed in code');

		if(BX.type.isNotEmptyString(so.instanceCode))
		{
			this.sys.instanceCode = so.instanceCode;
		}
	},

	// member of stack of initializers, must be defined even if do nothing
	init: function(){

		var ctx = this,
			sc = this.ctrls,
			so = this.opts,
			s = this.sys,
			code = this.sys.classCode,
			k;

		if(so.scope !== false)
		{ // some widgets may have no scope

			s.scope = BX.type.isNotEmptyString(so.scope) ? BX(so.scope) : so.scope;
			if(!BX.type.isElementNode(s.scope))
				throw new Error('Bad scope: invalid node passed');

			if(so.useSpawn && s.scope)
				ctx.tmpls['scope'] = s.scope.outerHTML;

			this.findTemplates();
		}

		// events
		if(typeof so.bindEvents == 'object')
		{
			for(k in so.bindEvents)
			{
				if(BX.type.isFunction(so.bindEvents[k]))
					this.bindEvent(k, so.bindEvents[k]);
			}
		}
		so.bindEvents = null;

		// instances
		// not working
		/*
		if(typeof so.instances == 'object')
		{
			for(k in so.instances)
			{
				this.instance(k, so.instances[k]);
			}
		}
		so.instances = null;
		*/
	},

	destroy: function()
	{
	},

	////////////////////////////
	/// about system

	option: function(name, value)
	{
		if(typeof value == 'undefined')
		{
			return this.opts[name];
		}
		else
		{
			this.opts[name] = value;
		}
	},

	variable: function(id, value)
	{
		if(typeof value == 'undefined')
		{
			return this.vars[id];
		}
		else
		{
			this.vars[id] = value;
		}
	},

	/*
	Searches a control inside widget scope. Returns native node or list of native nodes, or null on nothing found.
	*/
	control: function(id, instance, params)
	{
		if(!BX.type.isNotEmptyString(id))
			throw new Error('Requested control id is incorrect');

		// save instance & exit
		if(instance !== false && typeof instance !== 'undefined' && instance !== null)
		{
			if(!BX.type.isDomNode(instance))
				throw new TypeError('Bad control instance to keep');

			this.ctrls['S'] = instance;
			return;
		}

		if(!BX.type.isPlainObject(params))
			params = {};

		if(typeof params.scope != 'undefined')
		{
			if(!BX.type.isDomNode(params.scope))
				throw new Error('Scope provided is not a valid dom node');
		}
		else
		{
			if(!BX.type.isDomNode(this.sys.scope))
				throw new Error('Widget scope is not a valid dom node');
		}

		var scope = params.scope || this.sys.scope,
			all = !!params.all,
			reCache = !!params.reCache,
			noCache = !!params.noCache;

		// if there is some in options...
		if(BX.type.isElementNode(this.opts.controls[id]))
			return this.opts.controls[id];

		var loc = all ? 'A' : 'S';

		if(typeof this.ctrls[loc][id] === 'undefined' || reCache)
		{
			var node = scope[all ? 'querySelectorAll' : 'querySelector'](this.getControlSearchString(id));
			if(node !== null)
				this.ctrls[loc][id] = node;
		}

		var retVal = this.ctrls[loc][id];

		if(noCache)
			delete(this.ctrls[loc][id]);

		return retVal;
	},

	getRequiredControl: function(id, params)
	{
		return this.controlR.apply(this, [id, params]);
	},

	// an alias to getRequiredControl
	controlR: function(id, params)
	{
		var c = this.control(id, false, params);
		if(typeof c == 'undefined' || c === null)
			throw new ReferenceError('Requested control can not be found: '+this.getControlSearchValue(id));

		return c;
	},

	instance: function(code, ref)
	{
		if(typeof ref != 'undefined')
		{
			if(typeof ref == 'undefined' || typeof ref === null)
				throw new TypeError('Bad instance reference');

			if(typeof this.sys.instances[code] != 'undefined')
				throw new ReferenceError('Instance is already defined under code: '+code);

			this.sys.instances[code] = ref;
		}

		return this.sys.instances[code];
	},

	// search by an xpath selector inside scope
	// single
	find: function(selector)
	{
		if(!BX.type.isNotEmptyString(selector))
			return null;

		return this.sys.scope.querySelector(selector);
	},

	// all
	findAll: function(selector)
	{
		if(!BX.type.isNotEmptyString(selector))
			return [];

		return Array.prototype.slice.call(this.sys.scope.querySelectorAll(selector));
	},

	////////////////////////////
	/// about templating

	template: function(id, html)
	{
		if(typeof html == 'undefined')
		{
			return this.tmpls[id];
		}
		else
		{
			if(!BX.type.isString(html))
				throw new TypeError('Bad template html');

			this.tmpls[id] = html;
		}
	},

	findTemplates: function()
	{
		var templates = this.scope().querySelectorAll('script[type="text/html"]');
		for (var k = 0; k < templates.length; k++)
		{
			var id = BX.data(templates[k], 'bx-template-id');

			if(typeof id == 'string' && id.length > 0 && id.search(this.classCode()) == 0)
			{
				id = id.replace(this.classCode()+'-', '');
				this.tmpls[id] = templates[k].innerHTML;

				if(this.opts.removeTemplates)
					BX.remove(templates[k]);
			}
		}
	},

	getHTMLByTemplate: function(templateId, replacements)
	{
		var html = this.tmpls[templateId];

		if(!BX.type.isNotEmptyString(html))
			return '';

		for(var k in replacements)
		{
			if(typeof replacements[k] != 'undefined' && replacements.hasOwnProperty(k))
			{
				var replaceWith = '';
				if(k.toString().indexOf('=') == 0){ // leading '=' stands for an unsafe replace - no escaping
					replaceWith = replacements[k].toString();
					k = k.toString().substr(1);
				}else
					replaceWith = BX.util.htmlspecialchars(replacements[k]).toString();

				var placeHolder = '{{'+k.toString().toLowerCase()+'}}';

				if(replaceWith.search(placeHolder) >= 0) // you must be joking
					replaceWith = '';

				while(html.search(placeHolder) >= 0) // new RegExp('', 'g') on user-controlled data seems not so harmless
					html = html.replace(placeHolder, replaceWith);
			}
		}

		return html;
	},

	createNodesByTemplate: function(templateId, replacements, onlyTags)
	{
		//var template = this.tmpls[templateId].trim(); // not working in IE8
		var template = this.tmpls[templateId];

		if(!BX.type.isNotEmptyString(template))
			return null;

		template = template.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
		var html = this.getHTMLByTemplate(templateId, replacements);

		// table makeup behaves not so well when being parsed by a browser, so a little hack is on route:

		var isTableRow = false;
		var isTableCell = false;

		if(template.search(/^<\s*(tr|th)[^<]*>/) >= 0)
			isTableRow = true;
		else if(template.search(/^<\s*td[^<]*>/) >= 0)
			isTableCell = true;

		var keeper = document.createElement('div');

		if(isTableRow || isTableCell){

			if(isTableRow){
				keeper.innerHTML = '<table><tbody>'+html+'</tbody></table>';
				keeper = keeper.childNodes[0].childNodes[0];
			}else{
				keeper.innerHTML = '<table><tbody><tr>'+html+'</tr></tbody></table>';
				keeper = keeper.childNodes[0].childNodes[0].childNodes[0];
			}
		}else
			keeper.innerHTML = html;

		if(onlyTags){

			var children = keeper.childNodes;
			var result = [];

			// we need only non-text nodes
			for(var k = 0; k < children.length; k++)
				if(BX.type.isElementNode(children[k]))
					result.push(children[k]);

			return result;
		}else
			return Array.prototype.slice.call(keeper.childNodes);
	},

	////////////////////////////
	/// about inheritance

	parentConstruct: function(owner, opts)
	{
		var c = owner.superclass;
		if(typeof c == 'object')
			c.constructor.apply(this, [opts, true]);
	},

	handleInitStack: function(nf, owner, opts)
	{
		this.pushFuncStack('init', owner);

		if(!nf){
			BX.merge(this.opts, opts);

			BX.Mobile.Tasks.widget.prototype.preInit.call(this);

			var init = function(){

				if(this.sys.initialized) // already initialized once
					return;

				this.resolveFuncStack('init'); // resove init stacks

				for(var i in this.sys.stack){
					if(i != 'init')
						this.resolveFuncStack(i, true); // resolve all other stacks
				}

				this.sys.initialized = true;
				this.fireEvent('init', [this]);
			}

			if(BX.type.isString(this.opts.initializeByGlobalEvent) && this.opts.initializeByGlobalEvent.length > 0){
				var scope = this.opts.globalEventScope == 'window' ? window : document;
				BX.addCustomEvent(scope, this.opts.initializeByGlobalEvent, BX.proxy(init, this));
			}else
				init.call(this);
		}
	},

	// when you add fName to the stack, function with the corresponding name must exist, at least equal to BX.DoNothing()
	pushFuncStack: function(fName, owner)
	{
		if(BX.type.isFunction(owner.prototype[fName]))
		{

			if(typeof this.sys.stack[fName] == 'undefined')
				this.sys.stack[fName] = [];

			this.sys.stack[fName].push({owner: owner, f: owner.prototype[fName]});
		}
	},

	disableInFuncStack: function(fName, owner)
	{
		var stack = this.sys.stack[fName];

		if(typeof stack == 'undefined')
			return;

		for(var k = 0; k < stack.length; k++)
		{
			if(stack[k].owner == owner)
				stack[k].f = BX.DoNothing;
		}
	},

	resolveFuncStack: function(fName, fire)
	{
		var stack = this.sys.stack[fName];

		if(typeof stack == 'undefined')
			return;

		for(var k = 0; k < stack.length; k++){
			stack[k].f.call(this);
		}

		if(fire)
			this.fireEvent(fName, [this], document);

		this.sys.stack[fName] = null;
	},

	////////////////////////////
	/// about events

	// custom events, called on instance object and through dispatcher

	fireEvent: function(eventName, args, scope)
	{
		scope = scope || this;
		args = args || [];
		BX.onCustomEvent(scope, 'bx-tasks-ui-'+this.sys.classCode+'-'+eventName, args);

		// same on dispatcher

		return this;
	},

	bindEvent: function(eventName, callback)
	{
		BX.addCustomEvent(this, 'bx-tasks-ui-'+this.sys.classCode+'-'+eventName, callback);
		return this;
	},

	////////////////////////////
	/// private

	getControlSearchValue: function(id)
	{
		return this.sys.classCode+'-'+(this.sys.instanceCode === false ? '' : this.sys.instanceCode.toLowerCase()+'-')+id;
	},

	getControlSearchString: function(id)
	{
		return '[data-bx-id~="'+this.getControlSearchValue(id)+'"]';
	},

	getClassCode: function()
	{
		return this.sys.classCode;
	},
	classCode: function()
	{
		return this.getClassCode();
	},

	getInstanceCode: function()
	{
		return this.sys.instanceCode;
	},

	getScope: function()
	{
		return this.sys.scope;
	},
	scope: function()
	{
		return this.getScope();
	},

	passCtx: function(f, ctx)
	{
		return function()
		{
			var args = Array.prototype.slice.call(arguments);
			args.unshift(ctx);
			f.apply(this, args);
		}
	}
});

//////////////////////////////
// base page
//////////////////////////////

BX.Mobile.Tasks.page = function(opts, nf){

	this.parentConstruct(BX.Mobile.Tasks.page, opts);

	BX.merge(this, {
		sys: {
			classCode: 'page'
		},
		vars: {
			userId: false // current user, set on dynamic 
		},
		appCtrls: {
			menu: false
		}
	});

	this.handleInitStack(nf, BX.Mobile.Tasks.page, opts);
}
BX.extend(BX.Mobile.Tasks.page, BX.Mobile.Tasks.widget);

// the following functions can be overrided with inheritance
BX.merge(BX.Mobile.Tasks.page.prototype, {

	// member of stack of initializers, must be defined even if do nothing
	init: function()
	{
		// ready device here?

		if(typeof window.BXMobileApp != 'undefined')
		{
			var title = BX.message('PAGE_TITLE');
			if(BX.type.isNotEmptyString(title) && this.option('setTitle'))
			{
				window.BXMobileApp.UI.Page.TopBar.title.setText(title);
				window.BXMobileApp.UI.Page.TopBar.title.show();
			}
		}
		else
			throw new ReferenceError('BXMobileApp is not defined, no init is possible');

		if (typeof window.app == 'undefined')
			throw new ReferenceError('app is not defined, no init is possible');
		else if (this.option('setPullDown'))
		{
			window.app.pullDown({
				enable:   false,
				pulltext: BX.message('MB_TASKS_PULLDOWN_PULL'),
				downtext: BX.message('MB_TASKS_PULLDOWN_DOWN'),
				loadtext: BX.message('MB_TASKS_PULLDOWN_LOADING'),
				action:   'RELOAD',
				callback: function(){ window.app.reload(); }
			});

			// page open handler

			// if page is already in memory, this will fire
			BX.addCustomEvent(
				'onOpenPageBefore',
				BX.delegate(this.getPageParameters, this)
			);

			// otherwise, this will fire instantly when the widget is ready and the device is ready
			BX.ready(BX.delegate(this.getPageParameters, this));
		}

		if(this.option('usePull'))
		{
			BX.addCustomEvent(
				'onPull',
				BX.delegate(this.pullHandler, this)
			);
		}
	},

	////////// PUBLIC: free to use outside

	addFastClick: function(scope)
	{
		if(BX.type.isElementNode(scope) && typeof window.FastButton != 'undefined')
		{
			document.addEventListener("DOMContentLoaded", function(){
				new window.FastButton(
					scope,
					function(event) {
						event.target.click();
					}
				);
			}, false);
		}
	},

	////////// CLASS-SPECIFIC: free to modify in a child class

	// push&pull handler by default
	pullHandler: function(data)
	{
	},

	// page open hanlder by default
	pageOpenHandler: function(data)
	{
	},

	// actions that are performed in the dynamic part(s) of the application page, in case of using appcache
	dynamicActionsCustom: function(data)
	{
	},

	// menu by default that will be auto-added at the page
	getDefaultMenu: function()
	{
		return [];
	},

	////////// PRIVATE: forbidden to use outside (for compatibility reasons)

	resetMenu: function(menuItems)
	{
		var mcb = '';

		if(BX.type.isArray(menuItems) && menuItems.length > 0)
		{
			this.appCtrls.menu = new window.BXMobileApp.UI.Menu({
				items: menuItems
			});
			mcb = BX.delegate(this.appCtrls.menu.show, this.appCtrls.menu);
		}

		window.BXMobileApp.UI.Page.TopBar.title.setCallback(mcb);
	},

	setUser: function(userId)
	{
		if(+userId > 0)
			this.variable('userId', +userId);
	},
	getUser: function()
	{
		return this.variable('userId');
	},

	dynamicActions: function(data)
	{
		// common dynamic actions
		try
		{
			this.setUser(data.userId);
		}
		catch(e)
		{
			// no user in here
		}

		this.dynamicActionsCustom(data);
	},

	getPageParameters: function()
	{
		if(typeof window.app == 'undefined')
			throw new ReferenceError('app is not defined, pageOpenHandler() wont work');

		// this will work async
		window.app.getPageParams({
			'callback': BX.delegate(this.pageOpenHandler, this)
		});
	}
});