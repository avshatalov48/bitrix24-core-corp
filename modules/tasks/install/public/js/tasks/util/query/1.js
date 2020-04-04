BX.namespace('Tasks.Util');

BX.Tasks.Util.Query = BX.Tasks.Base.extend({
    options: {
        url: '/bitrix/components/bitrix/tasks.base/ajax.php'
    },
    methods: {
        construct: function()
        {
            this.vars = {
                batch: 		[], // current batch pending
                local: 		{}
            };
        },

        destruct: function()
        {
            this.vars = null;
            this.opts = null;
        },

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

            if(typeof args == 'undefined' || !BX.type.isPlainObject(args))
            {
                args = {};
            }

            if(typeof args == 'undefined' || !BX.type.isPlainObject(remoteParams))
            {
                remoteParams = {};
            }
            if(typeof remoteParams.code == 'undefined')
            {
                remoteParams.code = '';
            }
            remoteParams.code = remoteParams.code.toString();
            if(remoteParams.code.length == 0)
            {
                remoteParams.code = 'op_'+(this.vars.batch.length);
            }

            this.vars.batch.push({
                OPERATION: method,
                ARGUMENTS: args,
                PARAMETERS: remoteParams
            });
            this.vars.local[remoteParams.code] = localParams || {};

            return this;
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
            this.vars.batch = 		[];
            this.vars.local = 	{};

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

            if(this.vars.batch.length > 0)
            {
                BX.ajax({
                    url: this.opts.url,
                    method: 'post',
                    dataType: 'json',
                    async: true,
                    processData: true,
                    emulateOnload: true,
                    start: true,
                    data: {
                        'CSRF': BX.bitrix_sessid(),
                        'SITE_ID': BX.message('SITE_ID'),
                        'ACTION': this.vars.batch
                    },
                    cache: false,
                    onsuccess: BX.delegate(function(result){
                        try // avoid falling through onfailure section in case of some exceptions in "executed" event handlers
                        {
                            var res = {
                                success: 				result.SUCCESS,
                                clientProcessErrors: 	[],
                                serverProcessErrors: 	result.ERROR,
                                data: 					result.DATA || {},
	                            response : result
                            };

                            this.executeCallbacks(res);
                            this.executeDone(params.done, res);
                            this.fireEvent('executed', [res]);

                            if(result.SUCCESS)
                            {
                                this.deleteAll();
                            }
                        }
                        catch(e)
                        {
                            BX.debug(e);
                        }
                    }, this),
                    onfailure: BX.delegate(function(type, e){

                        var res = {
                            success: 				false,
                            clientProcessErrors: 	[{CODE: 'INTERNAL_ERROR', MESSAGE: 'Client process error', TYPE: 'FATAL'}],
                            serverProcessErrors: 	[],
                            data: 					{}
                        };

                        this.executeCallbacks(res);
                        this.executeDone(params.done, res);
                        this.fireEvent('executed', [res]);

                    }, this)
                });
            }
        },

        executeDone: function(done, res)
        {
            if(BX.type.isFunction(done))
            {
                var errors = [];
                errors = BX.util.array_merge(errors, res.serverProcessErrors || []);
                errors = BX.util.array_merge(errors, res.clientProcessErrors || []);

                for(var k in res.data)
                {
                    errors = BX.util.array_merge(errors, res.data[k].ERRORS || []);
                }

                done.apply(this, [errors, res]);
            }
        },

        executeCallbacks: function(res)
        {
            var opData = {};
            var opErrors = [];

            for(var k in this.vars.local)
            {
                if(BX.type.isFunction(this.vars.local[k].onExecuted))
                {
                    if(typeof res.data[k] != 'undefined')
                    {
                        opData = 	BX.clone(res.data[k]);
                        delete(opData.ERRORS);
                        delete(opData.SUCCESS);

                        opErrors = 	res.data[k].ERRORS;
                    }

                    this.vars.local[k].onExecuted.apply(this, [
                        this.combineErrors(res.serverProcessErrors, res.clientProcessErrors, opErrors),
                        opData
                    ]);
                }
            }
        },

        combineErrors: function(server, client, data)
        {
            var result = [];
            result = BX.util.array_merge(result, server || []);
            result = BX.util.array_merge(result, client || []);
            result = BX.util.array_merge(result, data || []);

            return result;
        }
    }
});