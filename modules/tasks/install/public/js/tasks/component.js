BX.namespace('Tasks');

var prevNameSpace = null;
if(typeof BX.Tasks.Component != 'undefined' && BX.type.isPlainObject(BX.Tasks.Component))
{
	prevNameSpace = BX.Tasks.Component;
}

BX.Tasks.Component = BX.Tasks.Util.Widget.extend({
	sys: {
		code: 'bx-comp'
	},
	options: {
		url: '',
		registerDispatcher: true,
		controlBind: 'class',
		componentId: '',
		componentClassName: '',
		modulesAvailable: {}
	},
	methods: {
		construct: function()
		{
			this.callConstruct(BX.Tasks.Util.Widget);

			this.bindEvents();
			this.disableDisposableHints();
		},

		// todo: implement batch call here
		callRemote: function(op, args, remoteParams, cb)
		{
			// paranoid disorder. each ajax hit goes to the own controller, so conflicts are not possible
			// but still...
			var cName = this.option('componentClassName');
			if(BX.type.isNotEmptyString(cName))
			{
				cName = cName.toLowerCase();
				op = op.replace(/^this\./, cName+'.');
			}

			return this.getQuery().run(op, args, remoteParams, false, this).then(cb); // promise
		},

		callRemoteTemplate: function(op, args, remoteParams)
		{
			return this.getQueryTemplate().run('runtime:templateaction'+op, args, remoteParams, false, this); // promise
		},

		getQueryTemplate: function()
		{
			return this.subInstance('query', function(){
				var params = {
					autoExec: true,
					emitter: this.option('componentId')
				};
				if(BX.type.isNotEmptyString(this.option('viewUrl')))
				{
					params.url = this.option('viewUrl');
				}
				return new BX.Tasks.Util.Query(params);
			});
		},

		getQuery: function()
		{
			return this.subInstance('query', function(){
				var params = {
					autoExec: true,
					emitter: this.option('componentId')
				};
				if(BX.type.isNotEmptyString(this.option('url')))
				{
					params.url = this.option('url');
				}
				return new BX.Tasks.Util.Query(params);
			});
		},

		bindEvents: function()
		{
			// do some event binding
		},

		disableDisposableHints: function()
		{
			try
			{
				BX.Tasks.Util.hintManager.disableSeveral(this.option('hintState'));
			}
			catch(e)
			{
			}
		}
	}
});

if(prevNameSpace)
{
	for(var k in prevNameSpace)
	{
		if(prevNameSpace.hasOwnProperty(k))
		{
			BX.Tasks.Component[k] = prevNameSpace[k];
		}
	}
}
