BX.namespace('BX.Tasks.Integration');

/**
 * This adapter provides suitable way of interaction with less-intuitive BX.SocNetLogDestination
 */

(function(){

	BX.Tasks.Integration.Socialnetwork = {};

	var dataCache = false;
	var dataFetchingInProgress = false;
	var popupOpenedId = false;

	BX.Tasks.Integration.Socialnetwork.NetworkSelector = BX.Tasks.Util.Widget.extend({
		sys: {
			code: 'network-selector'
		},
		options: {
			mode: 'user', // could be also "group" and "all" (the last means users, groups and departments selection)
            query: false,
			useSearch: false,
			useAdd: false,
			popupOffsetTop: 0,
			popupOffsetLeft: 0,
			syncLast: true,
			lastSelectedContext: 'TASKS'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.Widget);

				if(!("SocNetLogDestination" in BX))
				{
					throw new ReferenceError('No BX.SocNetLogDestination detected. Forgot to include socialnetwork module and/or its assets?');
				}

				this.vars.snldId = false;
				this.vars.intendSearch = '';
				this.vars.last = {};
				this.vars.intendOpen = false;
				this.vars.changed = false;
			},

			initialize: function()
			{
				if(this.dialogInitialized())
				{
					this.fireInitEvent();
				}
				else
				{
					if(dataCache === false)
					{
						// no data loaded previously
						this.fetchDestinationData();
					}
					else
					{
						this.initializeDialog();
					}
				}
			},

			dialogInitialized: function()
			{
				return this.vars.snldId !== false;
			},

			open: function()
			{
				if(!this.dialogInitialized())
				{
					this.vars.intendOpen = true;
					this.initialize();
				}
				else if(this.vars.snldId != popupOpenedId)
				{
					this.vars.intendOpen = false;

					if(popupOpenedId != false)
					{
						BX.SocNetLogDestination.openDialog(this.vars.snldId); // close
					}
					BX.SocNetLogDestination.openDialog(this.vars.snldId); // re-open
					popupOpenedId = this.vars.snldId;
				}
			},

			close: function()
			{
				this.vars.intendOpen = false;

				if(this.vars.snldId == popupOpenedId)
				{
					BX.SocNetLogDestination.closeDialog();
				}
			},

			addLast: function(entity)
			{
				this.vars.changed = true;
				this.vars.last[entity.id] = {
					id: entity.entityId,
					type: this.getEntityType(entity)
				};
			},

			deleteLast: function(entity)
			{
				this.vars.changed = true;
				delete(this.vars.last[entity.id]);
			},

			updateLast: function()
			{
				if(!this.option('syncLast'))
				{
					return;
				}

				if(!this.vars.changed)
				{
					return;
				}

				var items = this.vars.last;
				this.vars.last = {};

				var result = {};
				var i = 0;
				for(var k in items)
				{
					if(items.hasOwnProperty(k))
					{
						if(typeof result[items[k].type] == 'undefined')
						{
							result[items[k].type] = [];
						}

						result[items[k].type].push(items[k].id);
						i++;
					}
				}

				if(i > 0)
				{
					// send with delay, using query
                    this.getQuery().add('integration.socialnetwork.setdestinationlast', {items: result, context: this.option('lastSelectedContext')});
				}

				this.vars.changed = false;
			},

			fetchDestinationData: function()
			{
				if(!dataFetchingInProgress)
				{
					dataFetchingInProgress = true;
                    // send immediately, regardless the delay
                    this.getQuery().add(
	                    'integration.socialnetwork.getdestinationdata',
	                    {context: this.option('lastSelectedContext')},
	                    {code: 'get_destination_data'}
                    ).execute();
				}
			},

			getQuery: function()
			{
				if(typeof this.instances.query == 'undefined')
				{
                    if(this.option('query'))
                    {
                        this.instances.query = this.option('query');
                    }
                    else
                    {
                        this.instances.query = new BX.Tasks.Util.Query({
                            autoExec: true
                        });
                    }
					this.instances.query.bindEvent('executed', BX.delegate(this.onQueryExecuted, this));
				}

				return this.instances.query;
			},

			onQueryExecuted: function(result)
			{
				dataFetchingInProgress = false;

				if(result.success)
				{
					if(typeof result.data != 'undefined' && typeof result.data['get_destination_data'] != 'undefined')
					{
						if(result.data['get_destination_data'].SUCCESS)
						{
							dataCache = result.data['get_destination_data'].RESULT;
							this.initializeDialog();
						}
					}
				}
			},

			onSelectDestination: function(entity)
			{
				this.addLast(entity);

				entity.params = entity.params || {};

                var type = {
	                extranet: entity.isExtranet == 'Y',
					crmemail: entity.isCrmEmail == 'Y',
	                email: entity.isEmail == 'Y',
					network: entity.isNetwork == 'Y'
                };

				this.fireEvent('item-selected', [{
					id: entity.entityId,
					entityType: this.getEntityType(entity),
					networkId: type.network && entity.networkId? entity.networkId: '',
					nameFormatted: entity.name || '',
					description: entity.desc || '',
					avatar: entity.avatar || '',
					name: entity.params.name || '',
					lastName: entity.params.lastName || '',
					email: entity.email || '',
					type: type
				}]);
			},

			getEntityType: function(entity)
			{
				var type = 'U';

				if(entity.isEmail)
				{
					return type; // U - it is a email user, obviously
				}

				if(!entity.id)
				{
					return type;
				}

				// U313 => U, SG800 => SG, D400 => D
				var found = entity.id.toString().trim().match(/^[a-z]+/i);
				if(found && found[0])
				{
					type = found[0];
				}

				return type;
			},

			onUnSelectDestination: function(entity)
			{
				this.deleteLast(entity);

				this.fireEvent('item-deselected', [{
					id: entity.entityId,
					entityType: this.getEntityType(entity),
					name: entity.name
				}]);
			},

			onOpenDialogDestination: function(id)
			{
				popupOpenedId = id;
			},

			onCloseDialogDestination: function(id)
			{
                if(id == popupOpenedId) // last opened dialog was ours
                {
                    this.fireEvent('close');
                    this.updateLast();
                }

				popupOpenedId = false;
			},

			onOpenSearchDestination: function(id)
			{
				popupOpenedId = id;
			},

			onCloseSearchDestination: function(id)
			{
				if(id == popupOpenedId) // last opened dialog was ours
				{
					this.fireEvent('close');
					this.updateLast();
				}

				popupOpenedId = false;
			},

			onOpenEmailDestination: function(id)
			{
				popupOpenedId = id;
			},

			onCloseEmailDestination: function(id)
			{
				if(id == popupOpenedId) // last opened dialog was ours
				{
					this.fireEvent('close');
					this.updateLast();
				}

				popupOpenedId = false;
			},

			checkIsOpened: function()
			{
				return popupOpenedId == this.vars.snldId;
			},

			deselectItem: function(id)
			{
				if(this.vars.snldId != false)
				{
					// access to a low-level function, may fall here one day
					BX.SocNetLogDestination.deleteItem(this.checkEntityId(id), this.option('mode'), this.vars.snldId);
				}
			},

			selectItem: function(id)
			{
				if(this.vars.snldId != false)
				{
					// access to a low-level function, may fall here one day
					BX.SocNetLogDestination.selectItem(this.vars.snldId, null, null, this.checkEntityId(id), this.option('mode'));// = function(name, element, template, itemId, type, search)
				}
			},

			checkEntityId: function(id)
			{
				if(typeof id == 'undefined' || id === null)
				{
					return '';
				}

				id = id.toString();
				if(id.substring(0, 1) == 'U' || id.substring(0, 2) == 'SG' || id.substring(0, 2) == 'DR')
				{
					return id;
				}

				var u = this.option('mode') == 'user';

				return (u ? 'U' : 'SG')+id;
			},

			initializeDialog: function()
			{
				if(this.vars.snldId == false)
				{
					this.vars.snldId = BX.util.hashCode(Math.random().toString());
					var scope = this.scope();
					var inputName = 'name-'+this.id();
					var input = this.control('search');

					if(input)
					{
						BX.adjust(input, {
							attrs: {input: inputName, id: inputName}
						});
					}

					var modeAll = this.option('mode') == 'all';
					var modeUser = this.option('mode') == 'user';
					var modeGroup = this.option('mode') == 'group';

					var parameters = {
						name : this.vars.snldId,
						searchInput : input || null,
						bindMainPopup : { 'node' : scope, 'offsetTop' : parseInt(this.option('popupOffsetTop'))+'px', 'offsetLeft': parseInt(this.option('popupOffsetLeft'))+'px'},
						bindSearchPopup : { 'node' : scope, 'offsetTop' : parseInt(this.option('popupOffsetTop'))+'px', 'offsetLeft': parseInt(this.option('popupOffsetLeft'))+'px'},

						sendAjaxSearch: (
							modeUser
							|| modeAll
							|| (
								modeGroup
								&& (typeof dataCache.SONETGROUPS_LIMITED != 'undefined' && dataCache.SONETGROUPS_LIMITED == 'Y')
							)
						),
						useClientDatabase: !modeGroup,
						allowUserSearch: !modeGroup,
						allowSonetGroupsAjaxSearch: (typeof dataCache.SONETGROUPS_LIMITED != 'undefined' && dataCache.SONETGROUPS_LIMITED == 'Y'),
						enableProjects: modeGroup,
						departmentSelectDisable: !modeAll,

						// set if we can add new entities in the selector
						allowAddUser: dataCache.CAN_ADD_MAIL_USERS,//this.option('useAdd'),
						allowAddSocNetGroup: false,

						callback : {
							select : BX.proxy(this.onSelectDestination, this),
							unSelect : BX.proxy(this.onUnSelectDestination, this),
							openDialog : BX.proxy(this.onOpenDialogDestination, this),
							closeDialog : BX.proxy(this.onCloseDialogDestination, this),
							openSearch : BX.proxy(this.onOpenSearchDestination, this),
							closeSearch : BX.proxy(this.onCloseSearchDestination, this),
							openEmailAdd: BX.proxy(this.onOpenEmailDestination, this),
							closeEmailAdd: BX.proxy(this.onCloseEmailDestination, this)
						}
					};

					if (this.option('useSearch'))
					{
						parameters.showSearchInput = true;
					}

					if (this.option('forceTop'))
					{
						parameters.bindOptions = {
							position: "top",
							forceTop: true
						};
					}

					parameters.items = {
						users: modeUser || modeAll ? (dataCache.USERS || {}) : {},
						emails: modeUser || modeAll ? (dataCache.EMAILS || {}) : {},
						groups: modeAll ? {'UA' : {'id' : 'UA', 'name': BX.message('TASKS_WIDGET_ACCESS_ALL_EMPLOYEES')}} : {},
						department: modeUser || modeAll ? (dataCache.DEPARTMENT || {}) : {},
						departmentRelation: modeUser || modeAll ? (dataCache.DEPARTMENT_RELATION || {}) : {},
						sonetgroups: modeGroup || modeAll ? (dataCache.SONETGROUPS || {}) : {},
						projects: modeGroup || modeAll ? (dataCache.PROJECTS || {}) : {}
					};
					parameters.itemsLast = {
						users: modeUser || modeAll ? (dataCache.LAST.USERS || {}) : {},
						emails: modeUser || modeAll ? (dataCache.LAST.EMAILS || {}) : {},
						groups: modeAll ? {'UA' : true} : {},
						department: modeAll ? dataCache.LAST.DEPARTMENT : {},
						sonetgroups: modeGroup || modeAll ? (dataCache.LAST.SONETGROUPS || {}) : {},
						projects: modeGroup || modeAll ? (dataCache.LAST.PROJECTS || {}) : {}
					};
					parameters.itemsSelected = dataCache.SELECTED || {};
					parameters.allowSearchNetworkUsers = dataCache.NETWORK_ENABLED;
					parameters.showVacations = dataCache.SHOW_VACATIONS;
					parameters.usersVacation = (dataCache.USERS_VACATION || {});

					if (modeGroup)
					{
						parameters.allowSearchEmailUsers = false;
					}

					BX.SocNetLogDestination.init(parameters);

					if (input)
					{
						var params = {
							formName: this.vars.snldId,
							inputName: 'name-'+this.id(),
							sendAjax: (
								modeUser
								|| (
									modeGroup
									&& (typeof dataCache.SONETGROUPS_LIMITED != 'undefined' && dataCache.SONETGROUPS_LIMITED == 'Y')
								)
							)
						};

						var paramsPaste = BX.clone(params);
						paramsPaste.onPasteEvent = true;

						BX.bind(input, "keyup", BX.proxy(BX.SocNetLogDestination.BXfpSearch, params));
						BX.bind(input, "keydown", BX.proxy(BX.SocNetLogDestination.BXfpSearchBefore, params));
						BX.bind(input, "paste", BX.defer(BX.SocNetLogDestination.BXfpSearch, paramsPaste));
						BX.bind(input, "click", BX.delegate(this.open, this)); //re-open when occasionly closed
					}
				}

				this.fireInitEvent();
				if(this.vars.intendOpen)
				{
					this.open();
				}
			},

			fireInitEvent: function()
			{
				this.fireEvent('initialized');
			},

			clearDataCache: function()
			{
				dataCache = false;
			}
		}
	});

})();
