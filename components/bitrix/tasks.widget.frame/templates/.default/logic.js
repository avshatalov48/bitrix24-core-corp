'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksWidgetFrameEditForm != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetFrameEditForm = BX.Tasks.Component.extend({
		sys: {
			code: 'wfr-edit-form'
		},
		methods: {
			bindEvents: function()
			{
				// all block openers
				this.bindDelegateControl('toggler', 'click', this.passCtx(this.onToggleBlock));

				// all block pinners
				this.bindDelegateControl('pinner', 'click', this.passCtx(this.onPinBlock));

				// block of un-pinned blocks
				this.bindDelegateControl('additional-header', 'click', this.passCtx(this.onToggleAdditionalBlock));

				this.bindDelegateControl('pin-footer', 'click', this.onPinFooterClick.bind(this));
			},

			onToggleBlock: function(node)
			{
				var target = BX.data(node, 'target');

				if(typeof target != 'undefined' && BX.type.isNotEmptyString(target))
				{
					this.toggleBlock(target).then(function(){
						this.fireEvent('block-toggle', [target, !BX.hasClass(this.control(target), 'invisible')]);
					}.bind(this));
				}
			},

			onToggleAdditionalBlock: function(node)
			{
				var opened = BX.hasClass(node, 'opened');
				BX.toggleClass(node, 'opened');

				var state = this.getState();

				var allOpened = state.isAllDynamicOpened();
				var allClosed = state.isAllDynamicClosed();

				// open all closed un-chosen blocks
				BX.Tasks.each(state.getDynamicBlocks(), function(block){

					if(block && !block.PINNED)
					{
						var jsCode = this.toJsCode(block.CODE)+'-block';

						if(allOpened)
						{
							// need to close all
							if(block.OPENED)
							{
								this.toggleBlock(jsCode);
								block.OPENED = false;
							}
						}
						else if(allClosed)
						{
							// need to open all
							if(!block.OPENED)
							{
								this.toggleBlock(jsCode);
								block.OPENED = true;
							}
						}
						else
						{
							// manual, open
							if(!block.OPENED)
							{
								this.toggleBlock(jsCode);
								block.OPENED = true;
							}
						}
					}

				}.bind(this));
			},

			toggleBlock: function(target, duration)
			{
				var node = null;
				if(BX.type.isElementNode(target))
				{
					node = target;
				}
				else
				{
					node = this.control(target);
				}

				if(node)
				{
					return BX.Tasks.Util.fadeSlideToggleByClass(node, 400);
				}

				return null;
			},

			onPinBlock: function(node)
			{
				var chosenContainer = this.control('chosen-blocks');
				var unChosenContainer = this.control('unchosen-blocks');

				if(!BX.type.isElementNode(chosenContainer) || !BX.type.isElementNode(unChosenContainer))
				{
					return;
				}

				// get block name
				var target = BX.data(node, 'target');
				if(typeof target == 'undefined' || !BX.type.isNotEmptyString(target))
				{
					return;
				}

				// get block node
				node = this.control(target);
				var blockName = BX.data(node, 'block-name');

				if(!BX.type.isNotEmptyString(blockName) || !BX.type.isElementNode(node))
				{
					return;
				}

				var blockPinned = this.getState().getBlock(blockName).PINNED;

				// update state
				this.getState().set('BLOCKS', blockName, !blockPinned);
				var allChosen = this.getState().isAllDynamicPinned();

				if(typeof blockPinned != 'undefined')
				{
					var toPin = !blockPinned;

					// find block exact place
					var moveTo = this.control(target+'-place', toPin ? chosenContainer : unChosenContainer);
					var duration = 400;

					if(moveTo) // if there is an exact place, relocate to it
					{
						BX.Tasks.Util.fadeSlideToggleByClass(node, duration).then(function(){

							BX.append(node, moveTo);
							BX.toggleClass(node, 'pinned');
							if(!allChosen)
							{
								BX.removeClass(this.control('additional'), 'hidden')
							}

							return BX.Tasks.Util.fadeSlideToggleByClass(node, duration);

						}.bind(this)).then(function(){

							if(allChosen)
							{
								BX.addClass(this.control('additional'), 'hidden');
							}

						}.bind(this));
					}
					else // static block, then just pin it
					{
						BX.toggleClass(node, 'pinned');
					}
				}
			},

			onPinFooterClick: function()
			{
				var pinned = !this.getState().getFlag('FORM_FOOTER_PIN');
				var footer = this.control('footer');

				if(footer)
				{
					BX[pinned ? 'addClass' : 'removeClass'](footer, 'pinned');
				}
				this.getState().set('FLAGS', 'FORM_FOOTER_PIN', pinned);
			},

			getState: function()
			{
				return this.subInstance('state', function(){
					return new BX.Tasks.Component.TasksWidgetFrameEditForm.State({
						scope: this.control('state'),
						parent: this
					});
				});
			},

			toJsCode: function(code)
			{
				return code.toString().trim().toLowerCase();
			},

			showLoader: function()
			{
				BX.addClass(this.control('submit'), 'webform-small-button-wait');
			}
		}
	});

	BX.Tasks.Component.TasksWidgetFrameEditForm.State = BX.Tasks.Util.Widget.extend({
		sys: {
			code: 'wfr-edit-form-state'
		},
		options: {
			controlBind: 'class'
		},
		methods: {

			/*
			redraw: function()
			{
				var container = this.control('inputs');
				var html = '';

				var state = this.optionP('state');

				if(BX.type.isElementNode(container))
				{
					if(typeof state.BLOCKS != 'undefined')
					{
						BX.Tasks.each(state.BLOCKS, function(block, bName){

							var pinned = block.PINNED;
							if(typeof pinned != 'undefined')
							{
								html += this.getHTMLByTemplate('block', {
									NAME: bName,
									TYPE: 'PINNED',
									VALUE: pinned === true || pinned === 'true' ? '1' : '0'
								});
							}

						}.bind(this));
					}

					if(typeof state.FLAGS != 'undefined')
					{
						BX.Tasks.each(state.FLAGS, function(checked, fName){

							html += this.getHTMLByTemplate('flag', {
								NAME: fName,
								VALUE: checked === true || checked === 'true' ? '1' : '0'
							});

						}.bind(this));
					}

					container.innerHTML = html;

					this.control('operation').removeAttribute('disabled');
				}
			},
			*/

			getBlock: function(name)
			{
				return this.optionP('state').BLOCKS[name];
			},

			getFlag: function(name)
			{
				return this.optionP('state').FLAGS[name];
			},

			getDynamicBlocks: function()
			{
				if(!this.vars.dynamic)
				{
					this.vars.dynamic = [];
					BX.Tasks.each(this.optionP('state').BLOCKS, function(block){
						if(block.CATEGORY == 'DYNAMIC')
						{
							this.vars.dynamic.push(block);
						}
					}.bind(this));
				}

				return this.vars.dynamic;
			},

			isAllDynamicPinned: function()
			{
				var hasUnPinned = false;
				var dynamic = this.getDynamicBlocks();
				BX.Tasks.each(dynamic, function(block){
					if(!block.PINNED)
					{
						hasUnPinned = true;
						return false;
					}
				});

				return !hasUnPinned;
			},

			isAllDynamicOpened: function()
			{
				var hasClosed = false;
				var dynamic = this.getDynamicBlocks();
				BX.Tasks.each(dynamic, function(block){
					if(!block.OPENED && !block.PINNED)
					{
						hasClosed = true;
						return false;
					}
				});

				return !hasClosed;
			},

			isAllDynamicClosed: function()
			{
				var hasOpened = false;
				var dynamic = this.getDynamicBlocks();
				BX.Tasks.each(dynamic, function(block){
					if(block.OPENED && !block.PINNED)
					{
						hasOpened = true;
						return false;
					}
				});

				return !hasOpened;
			},

			get: function(type, name)
			{
				if (type == 'BLOCKS')
				{
					return this.vars.state[type][name].C;
				}
				if (type == 'FLAGS') {
					return this.vars.state[type][name];
				}
			},

			set: function(type, name, value)
			{
				if(!BX.type.isNotEmptyString(name))
				{
					return;
				}

				var state = this.optionP('state');

				if(typeof state[type] == 'undefined')
				{
					state[type] = {};
				}
				if(typeof state[type][name] == 'undefined')
				{
					state[type][name] = {};
				}

				if(type == 'BLOCKS')
				{
					state[type][name].PINNED = value;
				}
				if(type == 'FLAGS')
				{
					state[type][name] = value;
				}

				//this.redraw();
				this.sync();
			},

			sync: function()
			{
				this.parent().callRemoteTemplate('setState', {
					structure: this.optionP('structure'),
					value: this.optionP('state')
				});
			}
		}
	});

}).call(this);