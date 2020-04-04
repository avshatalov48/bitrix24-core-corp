'use strict';

BX.namespace('Tasks.Util');

/**
 *
 * Usage examples
 *
 * 1) To perform one single call without creating permanent instances and buffering:
 *
 *      BX.Tasks.Util.Query.runOnce('task.get', {id: 10}).then(function(r){console.dir(r)});
 *
 */

BX.Tasks.Util.Query = BX.Tasks.Util.Base.extend({
    options: {
        url: '/bitrix/components/bitrix/tasks.base/ajax.php',
        autoExec: false,
        replaceDuplicateCode: true,
        autoExecDelay: 100,
	    translateBooleanToZeroOne: true,
	    emitter: ''
    },
    methods: {
        construct: function()
        {
	        this.callConstruct(BX.Tasks.Util.Base);

            this.vars = {
                batch: 		[], // current batch pending
                local: 		{}
            };

            this.autoExecute = BX.debounce(this.autoExecute, this.option('autoExecDelay'), this);
        },

        destruct: function()
        {
            this.vars = null;
            this.opts = null;
        },

        autoExecute: function()
        {
            if(this.option('autoExec'))
            {
                this.execute();
            }
        },

		/**
		 * @param method
		 * @param args
		 * @param remoteParams
		 * @param localParams
		 * @deprecated
		 * @see BX.Tasks.Util.Query.run
         * @returns {BX.Tasks.Util.Query}
         */
        add: function(method, args, remoteParams, localParams)
        {
            if(typeof method == 'undefined')
            {
                throw new ReferenceError('Method name was not provided');
            }
            method = method.toString();

            if(method.length == 0)
            {
                throw new ReferenceError('Method name must not be empty');
            }

	        var k;

            if(typeof args == 'undefined' || !BX.type.isPlainObject(args))
            {
                args = {};
            }
            for(k in args)
            {
	            if(args.hasOwnProperty(k))
	            {
		            args[k] = this.processArguments(BX.clone(args[k])); // clone, because we may not modify the source
	            }
            }

            if(typeof remoteParams == 'undefined' || !BX.type.isPlainObject(remoteParams))
            {
                remoteParams = {};
            }
            remoteParams.code = this.pickCode(remoteParams);

            if(this.option('replaceDuplicateCode'))
            {
                for(k = 0; k < this.vars.batch.length; k++)
                {
                    if(this.vars.batch[k].PARAMETERS.code == remoteParams.code)
                    {
                        this.vars.batch.splice(k, 1);
                        break;
                    }
                }
            }

            this.vars.batch.push({
                OPERATION: method,
                ARGUMENTS: args,
                PARAMETERS: remoteParams
            });

            if(BX.type.isFunction(localParams))
            {
                localParams = {onExecuted: localParams};
            }
            else
            {
                localParams = localParams || {};
            }
	        localParams.pr = new BX.Promise(null, localParams.promiseCtx);

            this.vars.local[remoteParams.code] = localParams;

            this.autoExecute();

            return this;
        },

	    run: function(method, args, remoteParams, localParams, ctx)
	    {
		    // todo: implement also running several methods under one single promise,
		    // todo: by checking arguments.length > 1,
		    // todo: adding multiple operations and then doing Promise.parallel() on their sub-promises

		    remoteParams = BX.type.isPlainObject(remoteParams) ? remoteParams : {};
		    remoteParams.code = this.pickCode(remoteParams);

		    this.add(method, args, remoteParams, localParams);

		    localParams = BX.type.isPlainObject(localParams) ? BX.clone(localParams) : {};
		    localParams.promiseCtx = ctx;

		    this.add(method, args, remoteParams, localParams);

		    return this.vars.local[remoteParams.code].pr;
	    },

	    pickCode: function(remoteParams)
	    {
		    var code = '';

		    if(BX.type.isPlainObject(remoteParams))
		    {
			    code = remoteParams.code;
		    }

		    if(!BX.type.isNotEmptyString(code))
		    {
			    code = 'op_'+(this.vars.batch.length);
		    }

		    return code;
	    },

	    // replace true\false with 1\0 and other stuff
        processArguments: function(args)
        {
            var type = typeof args;

            if(type == 'array')
            {
                if(args.length == 0)
                {
                    return '';
                }

                for(var k = 0; k < type.length; k++)
                {
                    args[k] = this.processArguments(args[k]);
                }
            }

            if(type == 'object')
            {
                var i = 0;
                for(var k in args)
                {
                    args[k] = this.processArguments(args[k]);
                    i++;
                }

                if(i == 0)
                {
                    return '';
                }
            }

	        if(type == 'boolean' && this.option('translateBooleanToZeroOne'))
	        {
		        return args === true ? '1' : '0';
	        }

            return args;
        },

        load: function(todo)
        {
            if(BX.type.isArray(todo))
            {
                this.clear();

                for(var k = 0; k < todo.length; k++)
                {
                    this.add(todo[k].m, todo[k].args, todo[k].rp);
                }
            }

            return this;
        },

        deleteAll: function()
        {
            this.vars.batch = [];
            this.vars.local = {};

            return this;
        },

        clear: function()
        {
            return this.deleteAll();
        },

        execute: function(params)
        {
            if(this.opts.url === false)
            {
                throw new ReferenceError('URL was not provided');
            }

            if(typeof params == 'undefined')
            {
                params = {};
            }

	        var p = new BX.Promise();
	        params.pr = p;

            if(this.vars.batch.length > 0)
            {
				params.localVars = this.vars.local;

	            var batch = this.vars.batch;
	            this.clear();

                BX.ajax({
                    url: this.opts.url,
                    method: 'post',
                    dataType: 'json',
                    async: true,
                    processData: true,
                    emulateOnload: true,
                    start: true,
                    data: {
	                    'sessid': BX.bitrix_sessid(), // make security filter feel happy, call variable "sessid" instead of "csrf"
                        'SITE_ID': BX.message('SITE_ID'),
	                    'EMITTER': this.option('emitter'),
                        'ACTION': batch
                    },
                    cache: false,
                    onsuccess: function(result){
                        try // prevent falling through onfailure section in case of some exceptions inside onsuccess
                        {
	                        if(!result)
	                        {
		                        result = {
			                        SUCCESS: false,
			                        ERROR: [{CODE: 'INTERNAL_ERROR', MESSAGE: BX.message('TASKS_ASSET_QUERY_EMPTY_RESPONSE'), TYPE: 'FATAL'}],
			                        ASSET: [],
			                        DATA: {}
		                        };
	                        }

	                        var asset = '';
	                        if(BX.type.isArray(result.ASSET))
	                        {
		                        asset = result.ASSET.join('');
	                        }

	                        // load required assets, then show the result
	                        BX.html(null, asset).then(function(){
		                        this.processResult({
			                        success: 				result.SUCCESS,
			                        clientProcessErrors: 	[],
			                        serverProcessErrors: 	result.ERROR,
			                        data: 					result.DATA || {},
			                        response : result
		                        }, params);
	                        }.bind(this));
                        }
                        catch(e)
                        {
                            BX.debug(e);
	                        this.processResult({
		                        success: 				false,
		                        clientProcessErrors: 	[{CODE: 'INTERNAL_ERROR', MESSAGE: BX.message('TASKS_ASSET_QUERY_QUERY_FAILED_EXCEPTION'), TYPE: 'FATAL'}],
		                        serverProcessErrors: 	[],
		                        data: 					{}
	                        }, params);
                        }
                    }.bind(this),
                    onfailure: function(code, extra){

						console.dir(code);
						console.dir(extra);

						var message = BX.message('TASKS_ASSET_QUERY_QUERY_FAILED');
						if(code == 'processing')
						{
							message = BX.message('TASKS_ASSET_QUERY_ILLEGAL_RESPONSE');
						}
						else if(code == 'status')
						{
							message = BX.message('TASKS_ASSET_QUERY_QUERY_FAILED_STATUS').replace('#HTTP_STATUS#', extra);
						}

                        this.processResult({
                            success: 				false,
                            clientProcessErrors: 	[{CODE: 'INTERNAL_ERROR', MESSAGE: message, TYPE: 'FATAL', ajaxExtra: {code: code, status: extra}}],
                            serverProcessErrors: 	[],
                            data: 					{}
                        }, params);

                    }.bind(this)
                });
            }

	        return p;
        },

	    processResult: function(res, params)
	    {
		    this.executeDone(res, params.done, params.pr, params.localVars); // params.done is a callback
		    this.fireEvent('executed', [res]);
	    },

        executeDone: function(res, done, pr, localVars)
        {
	        var cl = this.getErrorCollectionClass();
	        var errors = new cl();
	        var toAdd;
	        var k;

	        toAdd = res.serverProcessErrors || [];
	        for(k = 0; k < toAdd.length; k++)
	        {
				errors.add(toAdd[k], 'C');
	        }
	        toAdd = res.clientProcessErrors || [];
	        for(k = 0; k < toAdd.length; k++)
	        {
		        errors.add(toAdd[k], 'C');
	        }

	        var data = BX.clone(res.data);
	        var commonErrors = new cl(errors);
	        var privateErrors;
	        var execResult = new BX.Tasks.Util.Query.Result(errors, data);

	        // todo: rewrite this part: you must walk by list of pending requests and see if for this time response came
	        // todo: dont forget about implementing feature: grouping inside batch. group should be treated as atom
	        if(res.success) // request was parsed successfully
	        {
		        for(var m in data)
		        {
			        if(data.hasOwnProperty(m))
			        {
				        privateErrors = null;
				        privateErrors = new cl(commonErrors);

				        toAdd = res.data[m].ERRORS || [];
				        for(k = 0; k < toAdd.length; k++)
				        {
					        privateErrors.add(toAdd[k]);
				        }

				        delete(data[m].ERRORS);
				        delete(data[m].SUCCESS);

				        // execute callback
				        if(BX.type.isFunction(localVars[m].onExecuted))
				        {
							localVars[m].onExecuted.apply(this, [
						        privateErrors,
						        data[m]
					        ]);
				        }

				        // fulfill promise
						localVars[m].pr.fulfill(new BX.Tasks.Util.Query.Result(privateErrors, data[m].RESULT));

				        // sum errors
				        privateErrors.deleteByMark('C');
				        errors.load(privateErrors);
			        }
		        }

		        if(pr instanceof BX.Promise)
		        {
			        pr.fulfill(execResult);
		        }
	        }
	        else
	        {
		        // send rejects to all pending promises: request general failure
		        BX.Tasks.each(localVars, function(request){
			        request.pr.reject(new BX.Tasks.Util.Query.Result(commonErrors, null));
		        });

		        if(pr instanceof BX.Promise)
		        {
			        pr.reject(execResult);
		        }
	        }

	        if(BX.type.isFunction(done))
	        {
                done.apply(this, [errors, res]);
            }

	        if(errors.checkHasErrors())
	        {
		        BX.onCustomEvent("TaskAjaxError", [errors]);
	        }
        },

	    getErrorCollectionClass: function()
	    {
		    return BX.Tasks.Util.Query.ErrorCollection;
	    }
    }
});

// perform one single call without buffering and permanent query instance creating
BX.Tasks.Util.Query.runOnce = function(method, args)
{
	return (new this({autoExec: true})).run(method, args);
};

// result
BX.Tasks.Util.Query.Result = function(errors, data)
{
	this.errors = errors ? errors : new BX.Tasks.Util.Query.ErrorCollection;
	this.data = data ? data : {};
};
BX.mergeEx(BX.Tasks.Util.Query.Result.prototype, {
	isSuccess: function()
	{
		return this.errors.filter({TYPE: 'FATAL'}).isEmpty();
	},
	getData: function()
	{
		return this.data;
	},
	getErrors: function()
	{
		return this.errors;
	}
});

// error collection
BX.Tasks.Util.Query.ErrorCollection = function(errors)
{
	this.length = 0;

	if(typeof errors != 'undefined')
	{
		this.load(errors);
	}
};
BX.mergeEx(BX.Tasks.Util.Query.ErrorCollection.prototype, {

	// common with server-side
	add: function(data, marker)
	{
		this[this.length++] = new BX.Tasks.Util.Query.Error(BX.clone(data), marker);
	},
	load: function(errors)
	{
		for(var k = 0; k < errors.length; k++)
		{
			this.add(errors[k], false);
		}
	},
	isEmpty: function()
	{
		return !this.length;
	},
	filter: function(filter)
	{
		var errors = new this.constructor();

		for(var k = 0; k < this.length; k++)
		{
			if(this.hasOwnProperty(k))
			{
				var match = true;

				if(BX.type.isPlainObject(filter))
				{
					if('TYPE' in filter)
					{
						if(this[k].getType() != filter.TYPE)
						{
							match = false;
						}
					}
				}

				if(match)
				{
					errors.add(this[k]);
				}
			}
		}

		return errors;
	},
	getMessages: function(escape)
	{
		var result = [];

		for(var k = 0; k < this.length; k++)
		{
			if (this.hasOwnProperty(k))
			{
				var msg = this[k].getMessage();
				result.push(escape ? BX.util.htmlspecialchars(msg) : msg);
			}
		}

		return result;
	},

	// different, rubbish
	getByCode: function(code)
	{
		if(!BX.type.isNotEmptyString(code))
		{
			return false;
		}

		for(var k = 0; k < this.length; k++)
		{
			if(this[k].checkIsOfCode(code))
			{
				return BX.clone(this[k]);
			}
		}
		return null;
	},

	deleteByCodeAll: function(code)
	{
		if(!BX.type.isNotEmptyString(code))
		{
			return;
		}

		this.deleteByCondition(function(item){
			return item.checkIsOfCode(code);
		});
	},
	deleteByMark: function(mark)
	{
		if(!BX.type.isNotEmptyString(mark))
		{
			return;
		}

		this.deleteByCondition(function(item){
			return item.mark() == mark;
		});
	},
	deleteByCondition: function(fn)
	{
		var errors = [];

		for(var k = 0; k < this.length; k++)
		{
			if(!fn.apply(this, [this[k]]))
			{
				errors.push(this[k]);
			}
		}

		this.deleteAll(false);

		this.load(errors);
	},
	deleteAll: function(makeNull)
	{
		for(var k = 0; k < this.length; k++)
		{
			if(makeNull !== false)
			{
				this[k] = null;
			}
			delete(this[k]);
		}
		this.length = 0;
	},
	// deprecated
	checkHasErrors: function()
	{
		return !!this.length;
	}
});

// error
BX.Tasks.Util.Query.Error = function(error, mark)
{
	for(var k in error)
	{
		if(error.hasOwnProperty(k))
		{
			this[k] = BX.clone(error[k]);
		}
	}
	this.vars = {mark: mark};
};
BX.mergeEx(BX.Tasks.Util.Query.Error.prototype, {
	// common with server-side
	getCode: function()
	{
		return this.CODE;
	},
	getType: function()
	{
		return this.TYPE;
	},
	getMessage: function()
	{
		return this.MESSAGE;
	},

	checkIsOfCode: function(code)
	{
		return this.CODE == code || BX.util.in_array(code, this.CODE.toString().split('.'));
	},
	code: function()
	{
		return this.getCode();
	},
	mark: function()
	{
		return this.vars.mark;
	},
	data: function()
	{
		if(BX.type.isPlainObject(this.DATA))
		{
			return this.DATA;
		}

		return {};
	}
});

BX.Tasks.Util.Query.Iterator = BX.Tasks.Util.Base.extend({
	options: {
		url: '',
		timeout: 500
	},
	methods: {

		construct: function()
		{
			this.callConstruct(BX.Tasks.Util.Base);

			this.reset();
		},

		getQuery: function()
		{
			return this.subInstance('query', function(){
				return new BX.Tasks.Util.Query({
					url: this.option('url'),
					autoExec: true,
					autoExecDelay: 1
				});
			});
		},

		reset: function()
		{
			this.vars = this.vars || {};

			this.vars.running = false;
			this.vars.step = 0;
			this.vars.timer = null;
			this.vars.ajaxRun = false;
			this.vars.ajaxAbort = false;
		},

		setStopped: function(reason)
		{
			this.vars.running = false;
			this.fireEvent('stop', [reason]);
		},

		start: function()
		{
			if(this.vars.running)
			{
				return;
			}

			this.reset();
			this.vars.running = true;

			this.fireEvent('start');
			this.hit();
		},

		stop: function()
		{
			if(!this.vars.running)
			{
				return;
			}

			clearInterval(this.vars.timer); // kill timer between hits

			if(this.vars.ajaxRun) // have to wait ajax an stop only when the result arrives
			{
				this.vars.ajaxAbort = true;
			}
			else
			{
				this.setStopped();
			}
		},

		hit: function()
		{
			this.vars.ajaxRun = true;

			this.getQuery().run(this.option('handler'), {parameters: {
				step: this.vars.step++
			}}).then(BX.delegate(function(result){

				this.vars.ajaxRun = false;
				if(this.vars.ajaxAbort)
				{
					this.setStopped();
				}
				else
				{
					if(result.isSuccess())
					{
						var p = new BX.Promise(null, this);

						this.fireEvent('hit', [p, result.getData(), result]);

						p.then(function(){
							this.vars.timer = setTimeout(BX.delegate(this.hit, this), this.optionInteger('timeout'));
						}, function(){
							this.setStopped();
						});
					}
					else
					{
						this.fireEvent('error', [result.getErrors(), result]);
						this.setStopped(result.getErrors());
					}
				}

			}, this), BX.delegate(function(reason){
				this.fireEvent('error', [reason.getErrors(), reason]);
				this.setStopped(reason.getErrors());
			}, this));
		}
	}
});

BX.Tasks.Util.InputGrabber = function()
{
};
BX.Tasks.Util.InputGrabber.grabFrom = function(area, struct) // currently, only form as area is supported
{
	var query = {};

	if(area && BX.type.isElementNode(area) && area.nodeName == 'FORM')
	{
		var k = 0;
		for(var i = 0; i < area.length; i++)
		{
			// want only legal enabled inputs
			if(area[i].name != '' && !area[i].disabled)
			{
				// dont want unchecked checkboxes
				if((area[i].nodeName == 'INPUT' && area[i].getAttribute('type') == 'checkbox') && !area[i].checked)
				{
					continue;
				}

				var name = area[i].name;

				if(struct)
				{
					// decode name
					var dName = area[i].name.toString().replace(/\]/g, '').split('[');
					var top = query;
					for(k = 0; k < dName.length; k++)
					{
						if(typeof top[dName[k]] == 'undefined')
						{
							top[dName[k]] = k == dName.length - 1 ? area[i].value : {};
						}
						top = top[dName[k]];
					}
				}
				else
				{
					query[name] = area[i].value;
				}
			}
		}

		return query;
	}
};