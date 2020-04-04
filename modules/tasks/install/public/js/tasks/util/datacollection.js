'use strict';

BX.namespace('Tasks.Util');

(function(){

	BX.Tasks.Util.Collection = BX.Tasks.Util.Base.extend({
		options: {
			keyField: 'ID'
		},
		methods: {

			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.Base);
				this.init();
				this.load(this.option('data'));
			},

			load: function(data)
			{
				var kf = this.option('keyField');

				BX.Tasks.each(data, function(item){
					this.vars.data[item[kf]] = item;
					this.vars.order.push(item[kf]);
				}, this);

				return this;
			},

			export: function(clone)
			{
				var data = [];

				this.each(function(item){
					data.push(clone ? BX.clone(item) : item);
				});

				return data;
			},

			first: function()
			{
				return this.nth(0);
			},

			last: function()
			{
				return this.nth(this.vars.order.length - 1);
			},

			nth: function(num)
			{
				if(this.vars.order.length == 0)
				{
					return null;
				}

				return this.getByKey(this.vars.order[num]);
			},

			contains: function(key)
			{
				return typeof this.vars.data[key] != 'undefined';
			},

			each: function(cb, ctx)
			{
				ctx = ctx || this;

				// todo: can be replaced with map
				BX.Tasks.each(this.vars.order, function(value){
					cb.apply(ctx, [this.vars.data[value], value]);
				}, this);

				return this;
			},

			count: function()
			{
				return this.vars.order.length;
			},

			/**
			 * Get a single item by its number in the current order
			 *
			 * @param num
			 * @returns {*}
			 */
			get: function(num)
			{
				return this.getByKey(this.vars.order[num]);
			},

			/**
			 * Get a single record by its key
			 *
			 * @param key
			 * @returns {*}
			 */
			getByKey: function(key)
			{
				var item = this.vars.data[key];
				if(typeof item == 'undefined')
				{
					return null;
				}

				return item;
			},

			/**
			 * Get a sub-collection of records that match the filter given
			 *
			 * @param filter
			 * @param limit
			 */
			find: function(filter, limit)
			{
				var item;
				var match;
				var found = [];

				// prepare filter
				if(BX.type.isPlainObject(filter))
				{
					filter = this.decompileFilter(filter);
				}
				else if(BX.type.isString(filter) || filter instanceof RegExp)
				{
					var dFilter = {};
					dFilter['*'] = {
						exactMatch: false,
						typeMatch: false,
						value: filter,
						negate: false
					};
					filter = dFilter;
				}

				for(var k = 0; k < this.vars.order.length; k++)
				{
					item = this.vars.data[this.vars.order[k]];

					match = false;
					// scan each field
					BX.Tasks.each(item, function(value, field){

						if((BX.type.isFunction(filter) && filter.apply(this, [field, value, this.vars.order[k], item])))
						{
							match = true;
						}

						if(BX.type.isPlainObject(filter) && this.matchFilter(filter, field, value))
						{
							match = true;
						}

						if(match)
						{
							return false; // to break each()
						}

					}, this);

					if(match)
					{
						found.push(item);

						if(BX.type.isNumber(limit) && limit <= found.length)
						{
							break;
						}
					}
				}

				var result = new this.constructor(this.opts);
				result.load(found);

				return result;
			},

			/**
			 * Get a single item matched by the filter
			 *
			 * @param filter
			 * @returns {*}
			 */
			findOne: function(filter)
			{
				return this.find(filter, 1).get(0);
			},

			/**
			 * Return a new collection with the same data set, sorted in the way specified
			 * // todo: currently only one level of sorting is supported
			 * @param rules
			 * @returns {BX.Tasks.Util.Collection}
			 */
			sort: function(rules)
			{
				if(!BX.type.isArray(rules) || !rules.length)
				{
					return this;
				}

				var rule = rules[0];
				var key = rule[0].toString().split('.');
				var way = rule[1].toString().toLowerCase();

				if(way !== 'asc' && way !== 'desc' && !BX.type.isFunction(way))
				{
					throw new Error('Invalid sort order');
				}

				var ix = this.vars.order.map(BX.delegate(function(e, i){
					return {ix: e, v: this.dereferenceKey(key, this.vars.data[e])}
				}, this));

				var fn = way;
				if(way == 'asc' || way == 'desc')
				{
					fn = function(a, b)
					{
						if(a.v == b.v)
						{
							return 0;
						}
						else if(a.v < b.v)
						{
							return way == 'asc' ? -1 : 1;
						}
						else if(a.v > b.v)
						{
							return way == 'asc' ? 1 : -1;
						}
					}
				}

				ix = ix.sort(fn);

				var result = [];
				ix.map(BX.delegate(function(e){
					result.push(this.vars.data[e.ix]);
				}, this));

				var c = new this.constructor(this.opts);
				c.load(result);

				return c;
			},

			init: function()
			{
				if(typeof this.vars == 'undefined')
				{
					this.vars = {};
				}

				this.vars.data = null;
				this.vars.order = null;

				this.vars.data = {};
				this.vars.order = [];
			},

			clear: function()
			{
				// do smth to release the resources
				this.init();

				return this;
			},

			push: function()
			{
				// todo
			},

			pop: function()
			{
				// todo
			},

			shift: function()
			{
				// todo
			},

			unShift: function()
			{
				// todo
			},

			insertAfter: function()
			{
				// todo
			},

			remove: function()
			{
				// todo
			},

			/**
			 * @private
			 */
			dereferenceKey: function(key, data)
			{
				var top = data;
				for(var k = 0; k < key.length; k++)
				{
					if(key.hasOwnProperty(k))
					{
						top = top[key[k]];
						if(typeof top == 'undefined' || top === null)
						{
							return null;
						}
					}
				}

				return top;
			},

			/**
			 * @private
			 */
			matchFilter: function(filter, field, value)
			{
				var rule = null;

				if(typeof filter[field] != 'undefined')
				{
					rule = filter[field];
				}
				else if(typeof filter['*'] != 'undefined')
				{
					rule = filter['*'];
				}

				if(rule)
				{
					if(rule.typeMatch)
					{
						return rule.value === value;
					}
					else if(rule.exactMatch)
					{
						return rule.value == value;
					}
					else if(rule.wordIntersect)
					{
						var needle = rule.value;
						var haystack = value;
						var result;
						var marked;

						// ensure that every item in needle find its pair in haystack
						return needle.every(function(nValue){

							result = false;
							marked = {};

							haystack.forEach(function(hValue, hI){

								if(hValue.match(new RegExp('^'+nValue, 'i')) && !marked[hI])
								{
									marked[hI] = true;
									result = true;
								}
							});

							return result;
						});
					}
					else if(typeof rule.value != 'undefined' && rule.value !== null && typeof value != 'undefined' && value !== null)
					{
						return value.toString().match(rule.value instanceof RegExp ? rule.value : new RegExp(rule.value, 'i'))
					}
				}

				return false;
			},

			/**
			 * @private
			 *
			 * todo: introduce also negate modifier (!) and "logic: or" modifier
			 */
			decompileFilter: function(filter)
			{
				var dFilter = {};
				var exactMatch = false;
				var typeMatch = false;
				var value = null;
				var wordIntersect = false;

				for(var k in filter)
				{
					if(filter.hasOwnProperty(k))
					{
						value = filter[k];

						k = k.toString().trim();

						if(k.substr(0, 2) == '==')
						{
							exactMatch = true;
							typeMatch = true;
							k = k.substr(2, k.length - 2);
						}
						else if(k.substr(0, 1) == '=')
						{
							exactMatch = true;
							k = k.substr(1, k.length - 1);
						}
						else if(k.substr(0, 1) == '#')
						{
							wordIntersect = true;
							value = value.toString().toLowerCase().split(' ').map(function(iVal){
								return iVal.trim();
							});
							k = k.substr(1, k.length - 1);
						}

						dFilter[k] = {
							exactMatch: exactMatch,
							typeMatch: typeMatch,
							value: value,
							wordIntersect: wordIntersect,
							negate: false // todo
						};
					}
				}

				return dFilter;
			}
		}
	});

	// todo: BX.Tasks.Util.RemoteCollection extends BX.Tasks.Util.Collection
	BX.Tasks.Util.RemoteCollection = BX.Tasks.Util.Base.extend({
		methods: {
			construct: function()
			{
				if(typeof this.vars == 'undefined')
				{
					this.vars = {};
				}

				this.vars.hash = {};

				this.vars.source = this.option('source');
				if(!BX.type.isFunction(this.vars.source))
				{
					throw new ReferenceError('No source function provided. Note that it must return new Promise()');
				}

				this.vars.transformResult = this.option('transformer');
				if(!BX.type.isFunction(this.vars.transformResult))
				{
					this.vars.transformResult = this.transformResultDataDefault;
				}
			},

			getSeveral: function(ids)
			{
				var p = new BX.Promise();
				var result = {};

				if(typeof ids !== 'undefined' && !BX.type.isArray(ids))
				{
					ids = [ids];
				}

				if(BX.type.isArray(ids) && ids.length)
				{
					var toDownload = [];

					for(var k = 0; k < ids.length; k++)
					{
						if(ids.hasOwnProperty(k))
						{
							if(typeof this.vars.hash[ids[k]] == 'undefined')
							{
								toDownload.push(ids[k]);
							}
						}
					}

					if(toDownload.length)
					{
						// downloading
						this.vars.source.apply(this, [toDownload]).then(BX.delegate(function(r){

							// downloaded
							if(r.errors.filter({TYPE: 'FATAL'}).isEmpty())
							{
								var data = this.vars.transformResult.apply(this, [r.data, toDownload]);

								// load
								var ok = false;
								if(data)
								{
									if(BX.type.isArray(data))
									{
										for(var k = 0; k < data.length; k++)
										{
											if(data.hasOwnProperty(k))
											{
												this.vars.hash[k] = data[k];
											}
										}
										ok = true;
									}
									else if(BX.type.isPlainObject(data))
									{
										for(var m in data)
										{
											if(data.hasOwnProperty(m))
											{
												this.vars.hash[m] = data[m];
											}
										}
										ok = true;
									}
								}

								if(!ok)
								{
									p.reject();
								}
								else
								{
									// get from storage
									p.fulfill(this.grabHash(ids));
								}
							}
							else
							{
								p.reject(r.errors);
							}
						}, this));
					}
					else
					{
						// get from storage
						p.fulfill(this.grabHash(ids));
					}
				}
				else
				{
					p.fulfill(result);
				}

				return p;
			},

			clear: function()
			{
				this.vars.hash = {};
			},

			grabHash: function(ids)
			{
				var result = {};

				for(var k = 0; k < ids.length; k++)
				{
					if(ids.hasOwnProperty(k))
					{
						result[ids[k]] = this.vars.hash[ids[k]];
					}
				}

				return result;
			},

			transformResultDataDefault: function(data, ids)
			{
				return data;
			}
		}
	});

})();