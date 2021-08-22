;(function ()
{
	'use strict';

	var namespace = BX.namespace('BX.Crm.Tracking.Channel');
	if (namespace.Pool)
	{
		return;
	}

	/**
	 * Editor.
	 *
	 */
	function Editor()
	{
		this.context = null;
		this.editor = null;

		this.colors = {
			used: '#e9e9e9'
		};
	}
	Editor.prototype.init = function (params)
	{
		this.context = BX(params.containerId);
		this.componentName = params.componentName;
		this.signedParameters = params.signedParameters;
		this.featureCode = params.featureCode;
		this.mess = params.mess || {};
		this.typeId = params.typeId;

		BX.bind(BX('item-allocate'), 'click', this.allocate.bind(this));

		this.poolSelector = BX.UI.TileSelector.getById('available-list');
		this.organicSelector = BX.UI.TileSelector.getById('organic-list');
		BX.addCustomEvent(this.poolSelector, this.poolSelector.events.tileRemove, this.onRemoveTile.bind(this));
		BX.addCustomEvent(this.poolSelector, this.poolSelector.events.tileClick, this.onTileClick.bind(this));

		this.selectors = BX.UI.TileSelector.getList().filter(function (selector) {
			return selector !== this.poolSelector && selector !== this.organicSelector;
		}, this);
		this.selectors.forEach(function (selector) {
			BX.addCustomEvent(selector, selector.events.tileAdd, this.actualizePool.bind(this));
			BX.addCustomEvent(selector, selector.events.tileRemove, this.actualizePool.bind(this));
			BX.addCustomEvent(selector, selector.events.tileClick, this.onTileClick.bind(this));
			BX.addCustomEvent(selector, selector.events.buttonSelect, this.showSelectorPopup.bind(this, selector));
		}, this);

		this.actualizePool();

		this.itemDialogBtn = BX('item-add');
		BX.bind(this.itemDialogBtn, 'click', this.showAddItemDialog.bind(this));

		if (this.featureCode)
		{
			BX.UI.InfoHelper.show(this.featureCode);
		}
	};
	Editor.prototype.showSelectorPopup = function (selector)
	{
		var items = this.poolSelector.getTiles().filter(function (tile) {
			return !tile.data.used;
		});
		selector.showSearcher(this.mess.searcherTitle);
		selector.setSearcherData([{
			id: 'all',
			name: this.mess.searcherCategory,
			data: {},
			items: items
		}]);
	};
	Editor.prototype.onRemoveTile = function (tile)
	{
		if (!tile.id)
		{
			return;
		}

		this.request(
			'removeItem',
			{
				typeId: this.typeId,
				value: tile.id
			},
			function (response)
			{
			},
			function ()
			{
			}
		);
	};
	Editor.prototype.onTileClick = function (tile)
	{
		if (!tile.data.using)
		{
			return;
		}

		if (!this.tester)
		{
			this.tester = new Tester({manager: this});
		}
		this.tester.open(tile.id, tile.data.using);
	};
	Editor.prototype.showAddItemDialog = function ()
	{
		if (!this.itemDialog)
		{
			this.itemDialog = new BX.PopupWindow(
				'crm-tracking-phone-add',
				this.itemDialogBtn,
				{
					content: BX('crm-tracking-dialog-add'),
					minWidth: 300
				}
			);
			this.itemDialogName = BX('item-add-name');
			var buttonAdd = BX('item-add-name-btn-add');
			BX.bind(buttonAdd, 'click', function () {

				var value = this.itemDialogName.value.trim();
				if (!value)
				{
					this.itemDialog.close();
					return;
				}

				BX.addClass(buttonAdd, 'ui-btn-wait');
				this.request(
					'addItem',
					{
						typeId: this.typeId,
						value: value
					},
					function (response)
					{
						BX.removeClass(buttonAdd, 'ui-btn-wait');
						this.itemDialog.close();

						if (!response.data || !response.data.value)
						{
							return;
						}

						if (response.data.value === true)
						{
							return;
						}

						var tile = this.poolSelector.addTile(
							response.data.value,
							{
								canRemove: true,
								using: response.data.using
							},
							response.data.value
						);

						this.onTileClick(tile);
					},
					function ()
					{
						BX.removeClass(buttonAdd, 'ui-btn-wait');
						this.itemDialog.close();
					}
				);
			}.bind(this));
			BX.bind(BX('item-add-name-btn-close'), 'click', function () {
				this.itemDialog.close();
			}.bind(this));
		}

		if (this.itemDialog.isShown())
		{
			this.itemDialog.close();
		}
		else
		{
			this.itemDialogName.value = '';
			this.itemDialog.show();
			this.itemDialogName.focus();
		}
	};
	Editor.prototype.actualizePool = function ()
	{
		var tiles = this.selectors.reduce(function (accumulator, selector) {
			return accumulator.concat(selector.getTiles());
		}, []);

		this.organicSelector.removeTiles();
		this.poolSelector.getTiles().forEach(function (poolTile) {
			poolTile.data.used = tiles.some(function (tile) {
				return poolTile.id === tile.id;
			});
			poolTile.changeRemoving(!poolTile.data.used && poolTile.data.canRemove);
			this.poolSelector.updateTile(
				poolTile,
				null,
				poolTile.data,
				poolTile.data.used ? this.colors.used : null
			);

			if (!poolTile.data.used)
			{
				this.organicSelector.addTile(
					poolTile.name || poolTile.id,
					poolTile.data,
					poolTile.id
				).changeRemoving(false);
			}

		}, this);

		var statusClassName = 'crm-tracking-channel-pool-item-status';
		var statusClassNameGray = statusClassName + '-gray';
		var statusClassNameGreen = statusClassName + '-green';
		var usingList = {};
		[this.poolSelector, this.organicSelector]
			.concat(this.selectors)
			.forEach(function (selector) {
				selector.getTiles().forEach(function (tile) {
					if (!tile.data.using)
					{
						if (!usingList[tile.id])
						{
							return;
						}

						tile.data.using = usingList[tile.id];
					}

					var isSuccess = tile.data.using.cnt > 0;
					tile.node.title = isSuccess ? this.mess.phoneStatusSuccess : this.mess.phoneStatusUnknown;
					usingList[tile.id] = tile.data.using;
					var statusNode = tile.node.querySelector('[data-role="tile-item-status"]');
					if (!statusNode)
					{
						statusNode = document.createElement('span');
						statusNode.setAttribute('data-role', 'tile-item-status');
						tile.node.insertBefore(statusNode, tile.node.firstChild);
						BX.addClass(statusNode, statusClassName);
					}

					BX.removeClass(statusNode, statusClassNameGray);
					BX.removeClass(statusNode, statusClassNameGreen);

					BX.addClass(
						statusNode,
						isSuccess ? statusClassNameGreen : statusClassNameGray
					);
				}, this);
		}, this);
	};
	Editor.prototype.allocate = function ()
	{
		var pool = this.poolSelector.getTiles().filter(function (tile) {
			return !tile.data.used;
		});
		if (pool.length === 0)
		{
			return;
		}

		this.selectors.filter(function (selector) {
			return selector.getTiles().length === 0;
		}).forEach(function (selector) {
			if (pool.length === 0)
			{
				return;
			}

			var tile = pool.shift();
			selector.addTile(tile.name || tile.id, tile.data, tile.id);
		});
	};
	Editor.prototype.request = function (action, data, callbackSuccess, callbackFailure)
	{
		BX.ajax.runComponentAction(this.componentName, action, {
			'mode': 'class',
			'signedParameters': this.signedParameters,
			'data': data
		}).then(
			function (response)
			{
				var data = response.data || {};
				if(data.error)
				{
					callbackFailure.apply(this, [data]);
				}
				else if(callbackSuccess)
				{
					callbackSuccess.apply(this, [data]);
				}
			}.bind(this),
			function()
			{
				var data = {'error': true, 'text': ''};
				callbackFailure.apply(this, [data]);
			}.bind(this)
		);
	};

	function Tester(options)
	{
		this.manager = options.manager;
		BX.addCustomEvent('onPullEvent-crm', function (command, data)
		{
			if (command !== 'tracking-call-end')
			{
				return;
			}

			if (!this.listening)
			{
				return;
			}

			this.end(data.numberFrom, data.numberTo)
		}.bind(this));

		top.BX.loadCSS('/bitrix/components/bitrix/crm.tracking.pool/templates/.default/tester.css');
		top.BX.loadExt('ui.forms');
		top.BX.loadExt('ui.buttons');
		//top.BX.loadExt('loader');
	}
	Tester.prototype = {
		manager: null,
		container: null,
		numberTo: null,
		listening: false,
		numberFrom: null,
		classSuccess: 'crm-tracking-call-tester-status-success',
		open: function (value, using)
		{
			if (!using)
			{
				return;
			}

			if (!this.container)
			{
				this.init();
			}

			this.clear();
			this.numberTo = value;
			var isSuccess = using.cnt > 0;
			this.nodeTitle.textContent = value;
			this.nodeDate.textContent = using.date || this.manager.mess.noPhoneTesting;
			this.nodeFromInput.value = this.numberFrom;
			isSuccess
				? BX.addClass(this.nodeStatus, this.classSuccess)
				: BX.removeClass(this.nodeStatus, this.classSuccess);

			BX.SidePanel.Instance.open(
				'crm-tracking-pool-tester',
				{
					width: 550,
					cacheable: false,
					contentCallback: function ()
					{
						var promise = new BX.Promise();
						promise.fulfill(this.container);
						return promise;
					}.bind(this),
					events: {
						onLoad: function(event) {

						}
					}
				}
			);
		},
		start: function ()
		{
			this.clear();

			if (this.manager.featureCode)
			{
				BX.UI.InfoHelper.show(this.manager.featureCode);
				return;
			}

			BX.removeClass(this.nodeFrom, 'ui-ctl-danger');
			var numberFrom = this.nodeFromInput.value;
			if (!numberFrom)
			{
				BX.addClass(this.nodeFrom, 'ui-ctl-danger');
				return;
			}

			this.numberFrom = numberFrom;
			this.listening = true;

			this.manager.request(
				'startTesting',
				{numberFrom: this.numberFrom},
				function (response)
				{
					if (response.data && response.data.numberFrom)
					{
						this.numberFrom = response.data.numberFrom;
						this.nodeFromInput.value = this.numberFrom;
					}
					else
					{
						this.end(null, null, true);
						BX.addClass(this.nodeFrom, 'ui-ctl-danger');
					}
				}.bind(this),
				function ()
				{
					this.end(null, null, true);
					BX.addClass(this.nodeFrom, 'ui-ctl-danger');
				}.bind(this)
			);

			this.nodeWaitNumber.textContent = this.numberTo;
			this.nodeWait.style.display = '';
			BX.removeClass(this.nodeButton, 'ui-btn-primary');
			this.nodeButton.textContent = this.nodeButton.getAttribute('data-lang-stop');
		},
		clear: function (result, manual)
		{
			this.listening = false;

			this.nodeWait.style.display = 'none';
			this.nodeButton.textContent = this.nodeButton.getAttribute('data-lang-start');
			BX.addClass(this.nodeButton, 'ui-btn-primary');

			this.nodeResultSuccess.style.display = result === true ? '' : 'none';
			this.nodeResultFail.style.display = result === false ? '' : 'none';
			this.nodeResultUnknown.style.display = manual && !BX.type.isBoolean(result) ? '' : 'none';

			if (result)
			{
				BX.addClass(this.nodeStatus, this.classSuccess)
			}
		},
		end: function (numberFrom, numberTo, manual)
		{
			if (numberFrom && numberFrom !== this.numberFrom)
			{
				return;
			}
			if (numberTo && numberTo !== this.numberTo)
			{
				return;
			}

			var result = null;
			if (numberFrom)
			{
				result = false;
			}
			if (numberTo)
			{
				result = true;
				var tile = this.manager.poolSelector.getTile(numberTo);
				if (tile)
				{
					tile.data.using = tile.data.using || {};
					tile.data.using.cnt = tile.data.using.cnt || 1;
					tile.data.using.date = tile.data.using.date || BX.formatDate();
					this.manager.actualizePool();
				}
			}
			this.clear(result, manual);
		},
		onButtonClick: function ()
		{
			this.listening ? this.end(null, null, true) : this.start();
		},
		init: function ()
		{
			this.container = document.createElement('div');
			this.container.innerHTML = BX('crm-tracking-pool-tester').innerHTML;
			this.nodeTitle = this.getNode('tester/title');
			this.nodeStatus = this.getNode('tester/status');
			this.nodeDate = this.getNode('tester/date');

			this.nodeFrom = this.getNode('tester/from');
			this.nodeFromInput = this.getNode('tester/from/input');
			this.nodeButton = this.getNode('tester/btn');

			this.nodeWait = this.getNode('tester/wait');
			this.nodeWaitNumber = this.getNode('tester/wait/number');
			this.nodeWaitLoader = this.getNode('tester/wait/loader');
			this.nodeResultSuccess = this.getNode('tester/result/success');
			this.nodeResultFail = this.getNode('tester/result/fail');
			this.nodeResultUnknown = this.getNode('tester/result/unknown');

			BX.bind(this.nodeButton, 'click', this.onButtonClick.bind(this));
			(new BX.Loader({})).show(this.nodeWaitLoader);
		},
		getNode: function(code)
		{
			var node = this.container.querySelector('[data-role="crm/tracking/pool/' + code + '"]');
			return node ? node : null;
		}
	};

	namespace.Pool = new Editor();
})();