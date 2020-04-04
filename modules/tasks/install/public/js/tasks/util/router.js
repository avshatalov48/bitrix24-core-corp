BX.namespace('Tasks.Util');

BX.Tasks.Util.Router = BX.Tasks.Util.Base.extend({
	options: {
	},
	methods: {
		construct: function()
		{
			this.vars = {routes: {}};

			/*
			var routes = this.option('routes');
			for(var k in routes)
			{
				if(routes.hasOwnProperty(k))
				{
					this.addRoute(k, routes[k].pattern);
				}
			}
			*/
		},

		/*
		addRoute: function(id, pattern)
		{
			this.vars.routes[id] = pattern;
		},

		// only history routes are supported
		setRoute: function(id, fields)
		{
			if(this.canIUseHistory() && this.vars.routes[id])
			{
				var pattern = this.vars.routes[id];

				for(var k in fields)
				{
					if(fields.hasOwnProperty(k))
					{
						var value = encodeURIComponent(fields[k]);
						pattern = pattern
							.replace('{{'+k.toString().toLowerCase()+'}}', fields[k])
							.replace('{{'+k.toString().toUpperCase()+'}}', value)
							.replace('{{'+k+'}}', value);
					}
				}

				history.replaceState(fields, document.title, pattern);
			}
		},
		*/

		setQueryString: function(queryData)
		{
			if(this.canIUseHistory())
			{
				var q = [];
				for(var k in queryData)
				{
					if(queryData.hasOwnProperty(k))
					{
						q.push(k+'='+encodeURIComponent(queryData[k]));
					}
				}

				history.replaceState(queryData, document.title, '?'+q.join('&'));

				return true;
			}

			return false;
		},

		canIUseHistory: function()
		{
			return 'history' in window;
		}
	}
});
