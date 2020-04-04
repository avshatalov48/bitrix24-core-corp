BX.namespace('BX.Crm.ClientPortrait');

(function(BX)
{
	'use strict';

	if (typeof BX.Crm.ClientPortrait.Loadbar !== 'undefined')
		return;

	var SERVICE_URL = '/bitrix/components/bitrix/crm.client.portrait/ajax.php?site_id='
		+ BX.message('SITE_ID') + '&sessid=' + BX.bitrix_sessid();

	var Loadbar = function(barNode, entityContext)
	{
		if (!(barNode instanceof Element))
			throw 'Loadbar: barNode is incorrect!';

		this.entityContext = entityContext;
		this.loadData = {
			min: 0,
			max: 20,
			target: 5,
			current: 0,
			currentOriginal: 0,
			manualTarget: false
		};
		this.nodes = {};

		this.nodes.bar = barNode;
		this.nodes.level1 = barNode.querySelector('[data-role="level-1"]');
		this.nodes.level2 = barNode.querySelector('[data-role="level-2"]');
		this.nodes.level3 = barNode.querySelector('[data-role="level-3"]');
		this.nodes.level4 = barNode.querySelector('[data-role="level-4"]');
		this.nodes.level5 = barNode.querySelector('[data-role="level-5"]');
		this.nodes.level6 = barNode.querySelector('[data-role="level-6"]');
		this.nodes.level7 = barNode.querySelector('[data-role="level-7"]');

		this.nodes.current = barNode.querySelector('[data-role="current"]');
		this.nodes.currentText = barNode.querySelector('[data-role="current-text"]');

		this.nodes.configBtn = barNode.querySelector('[data-role="config-btn"]');
	};

	Loadbar.messages = {};

	Loadbar.prototype = {
		setLoadData: function (data)
		{
			if (!BX.type.isPlainObject(this.loadData))
				throw 'Loadbar: load data is incorrect!';

			var min = 0,
				max = parseInt(data.max),
				target = parseInt(data.target),
				current = parseInt(data.current),
				currentOriginal = current;

			if (isNaN(max))
				max = 0;
			if (isNaN(target))
				target = 0;
			if (isNaN(current))
				current = 0;
			if (isNaN(currentOriginal))
				currentOriginal = 0;

			if (max < 8)
				max = 8; //set magic value :)

			if (target < min)
				target = min;
			if (target > max)
				target = max;

			if (current < min)
				current = min;
			else if (current > max)
				current = max;

			this.loadData = {
				min: min,
				max: max,
				target: target,
				current: current,
				currentOriginal: currentOriginal,
				manualTarget: (data.manualTarget)
			};

			return this;
		},

		setLoadTarget: function(value)
		{
			value = parseInt(value);
			if (isNaN(value))
				value = 0;

			if (value < this.loadData.min)
				value = this.loadData.min;
			if (value > this.loadData.max)
				value = this.loadData.max;

			this.loadData.target = value;

			return this;
		},

		init: function()
		{
			var loadDataJson = this.nodes.bar.getAttribute('data-loaddata');
			if (loadDataJson)
			{
				var loadData = JSON.parse(loadDataJson);
				if (BX.type.isPlainObject(loadData))
					this.setLoadData(loadData);
			}

			if (!BX.type.isPlainObject(this.entityContext))
			{
				var entityContextJson = this.nodes.bar.getAttribute('data-context');
				if (entityContextJson)
				{
					this.entityContext = JSON.parse(entityContextJson);
					if (!BX.type.isPlainObject(this.entityContext))
						this.entityContext = {};
				}
			}

			if (this.nodes.configBtn)
			{
				BX.bind(this.nodes.configBtn, 'click', BX.delegate(this.onConfigBtnClick, this))
			}

			return this;
		},

		refreshView: function(loadData)
		{
			if (loadData)
				this.setLoadData(loadData);
			this.renderLevels();
			this.renderCurrent();

			return this;
		},

		renderLevels: function()
		{
			var range = this.loadData.max - this.loadData.min;
			var targetPosition = this.loadData.target / range * 100;
			var smallLevelSize = 1;

			if (targetPosition < 50)
			{
				if (targetPosition !== 0)
					smallLevelSize = targetPosition / 3.5;

				this.setLevelWidth(this.nodes.level1, smallLevelSize);
				this.setLevelWidth(this.nodes.level2, smallLevelSize);
				this.setLevelWidth(this.nodes.level3, smallLevelSize);

				this.setLevelWidth(this.nodes.level5);
				this.setLevelWidth(this.nodes.level6);
				this.setLevelWidth(this.nodes.level7);
			}
			else
			{
				if (targetPosition !== 100)
					smallLevelSize = (100 - targetPosition) / 3.5;

				this.setLevelWidth(this.nodes.level1);
				this.setLevelWidth(this.nodes.level2);
				this.setLevelWidth(this.nodes.level3);

				this.setLevelWidth(this.nodes.level5, smallLevelSize);
				this.setLevelWidth(this.nodes.level6, smallLevelSize);
				this.setLevelWidth(this.nodes.level7, smallLevelSize);
			}

			this.setLevelWidth(this.nodes.level4, smallLevelSize);
		},

		setLevelWidth: function(level, widthPercent)
		{
			level.style.maxWidth = widthPercent ? widthPercent + '%' : 'none';
			level.style.minWidth = widthPercent ? widthPercent + '%' : 'auto';

			BX[widthPercent < 10 ? 'addClass' : 'removeClass'](level, 'crm-portrait-load-hide');
		},

		renderCurrent: function()
		{
			var range = this.loadData.max - this.loadData.min;
			var currentPosition = this.loadData.current / range * 100;

			this.nodes.current.style.left = currentPosition + '%';
			this.nodes.currentText.innerHTML = this.loadData.currentOriginal.toString();
		},

		onConfigBtnClick: function()
		{
			this.getConfigPopup().show();
		},

		getConfigPopup: function()
		{
			var me = this, ajaxInProgress = false;
			if (!this.configPopup)
				this.configPopup = new BX.PopupWindow("crm-client-portrait-loadbar" + Math.random(), null, {
					content: this.getConfigPopupHtml(),
					closeIcon: true,
					titleBar: BX.util.htmlspecialchars(Loadbar.messages.CONFIG_TITLE),
					overlay: {backgroundColor: 'black', opacity: '80'},
					buttons: [
						new BX.PopupWindowButton({
							text: BX.util.htmlspecialchars(Loadbar.messages.SAVE),
							className: "popup-window-button-accept",
							events: {
								click: function ()
								{
									var popup = this.popupWindow;
									var isManual = me.configPopup.contentContainer.querySelector('[data-role="is_manual"]').checked;
									var loadTarget = me.configPopup.contentContainer.querySelector('[data-role="load_target"]').value;

									me.loadData.manualTarget = isManual;

									ajaxInProgress = true;
									BX.ajax.post(
										SERVICE_URL,
										{
											ajax_action: 'SET_LOAD_TARGET',
											is_manual: isManual ? 'Y' : 'N',
											load_target: loadTarget,
											entity_context: me.entityContext
										},
										function()
										{
											ajaxInProgress = false;
											me.setLoadTarget(loadTarget);
											me.refreshView();
											popup.close();
										}
									);
								}
							}
						}),
						new BX.PopupWindowButton({
							text: BX.util.htmlspecialchars(Loadbar.messages.CANCEL),
							className: "popup-window-button",
							events: {
								click: function ()
								{
									this.popupWindow.close();
								}
							}
						})
					]
				});

			return this.configPopup;
		},

		getConfigPopupHtml: function()
		{
			var idPrefix = "crm-client-portrait-loadbar" + Math.random();
			var val = this.loadData.target.toString();

			return [
				'<div class="crm-portriat-popup-text">',
					BX.util.htmlspecialchars(Loadbar.messages.CONFIG_DESCRIPTION), '<br>', '<br>',
					BX.util.htmlspecialchars(Loadbar.messages.CONFIG_HELP),
				'</div>',
				'<div class="crm-portriat-popup-set">',
					'<form action="">',
						'<div class="crm-portriat-popup-set-item">',
							'<input class="crm-portriat-popup-radio" type="radio" name="is_manual" value="N" id="',idPrefix,'-interval-1" ',
							(this.loadData.manualTarget ? '' : 'checked'),
							'>',
							'<label class="crm-portriat-popup-label" for="',idPrefix,'-interval-1">',
								BX.util.htmlspecialchars(Loadbar.messages.CONFIG_AUTO),
							'</label>',
						'</div>',
						'<div class="crm-portriat-popup-set-item">',
							'<input class="crm-portriat-popup-radio" type="radio" name="is_manual" data-role="is_manual" value="Y" id="',idPrefix,'-interval-2" ',
							(this.loadData.manualTarget ? 'checked' : ''),
							'>',
							'<label class="crm-portriat-popup-label" for="',idPrefix,'-interval-2">',
								BX.util.htmlspecialchars(Loadbar.messages.CONFIG_MANUAL), ':',
								'<input class="crm-portriat-popup-input" type="text" name="load_manual" data-role="load_target" value="', val ,'">',
							'</label>',
						'</div>',
					'</form>',
				'</div>'
			].join('');
		}
	};

	var LoadbarMenu = function(menuNode, containerNode, entityContext)
	{
		if (!(menuNode instanceof Element))
			throw 'LoadbarMenu: menuNode is incorrect!';

		if (!(containerNode instanceof Element))
			throw 'LoadbarMenu: containerNode is incorrect!';

		if (!BX.type.isPlainObject(entityContext))
			entityContext = {};

		this.entityContext = entityContext;
		this.nodes = {
			menu: menuNode,
			container: containerNode
		};
		/** {Loadbar}[] **/
		this.loadbars = [];
	};

	LoadbarMenu.prototype = {
		init: function()
		{
			var i;

			BX.bind(this.nodes.menu, 'click', BX.delegate(this.onMenuClick, this));

			var loadbars = this.nodes.container.querySelectorAll('[data-role="loadbar"]');
			if (loadbars)
			{
				for (i = 0; i < loadbars.length; ++i)
				{
					this.loadbars.push(
						(new Loadbar(loadbars[i])).init()
					);
				}
			}

			return this;
		},

		onMenuClick: function()
		{
			var isOpen = BX.hasClass(this.nodes.container, 'crm-portrait-item-show');

			BX[isOpen? 'removeClass' : 'addClass'](this.nodes.menu, 'crm-portrait-direction-show');
			BX.toggleClass(this.nodes.container, 'crm-portrait-item-show');

			if (!isOpen)
				this.refreshView();
		},

		refreshView: function()
		{
			var i;
			for (i = 0; i < this.loadbars.length; ++i)
			{
				this.loadbars[i].refreshView();
			}
		}
	};

	BX.Crm.ClientPortrait.Loadbar = Loadbar;
	BX.Crm.ClientPortrait.LoadbarMenu = LoadbarMenu;
})(window.BX || window.top.BX);