;(function () {

	"use strict";

	if (window["SBPETabs"])
		return;

	BX.namespace('BX.ContactCenter');

	BX.ContactCenter.TileGrid = function (params)
	{
 		if (typeof params === "object")
 		{
 			this.wrapper = params.wrapper;
 			this.inner = params.inner;
 			this.innerPartnersBlock = params.innerPartnersBlock;
 			this.tiles = params.tiles;
 			this.minTileWidth = 0;
 			this.maxTileWidth = 0;
			this.tileRowLength = 0;

			// You can set min. max. width or amount of tiles in one row
			if(params.sizeSettings)
 			{
 				this.minTileWidth = params.sizeSettings.minWidth;
				this.maxTileWidth = params.sizeSettings.maxWidth;
 			}
			else if (params.tileRowLength)
			{
				this.tileRowLength = params.tileRowLength;
			}
			else
			{
				this.minTileWidth = 180;
				this.maxTileWidth = 250;
 			}

 			this.tileRatio = params.tileRatio || 1.8;
 			this.maxTileHeight = this.maxTileWidth / this.tileRatio;
 		}

 		this.setTileWidth();
 		BX.bind(window, 'resize', this.setTileWidth.bind(this));

 	};

 	BX.ContactCenter.TileGrid.prototype =
	{
		setTileWidth : function ()
		{
			var obj =  this.getTileCalculating();

			var width = obj.width;
			var height = obj.height;

			if(this.minTileWidth)
			{
				width = width <= this.maxTileWidth ? obj.width : this.maxTileWidth;
				height = height <= this.maxTileHeight ? obj.height : this.maxTileHeight;
			}

			requestAnimationFrame(function() {
				for(var i=0; i<this.tiles.length; i++)
				{
					this.tiles[i].style.width = width + 'px';
					this.tiles[i].style.height = height + 'px';
					this.tiles[i].style.marginLeft = obj.margin + 'px';
					this.tiles[i].style.marginTop = obj.margin + 'px';
				}
				this.inner.style.marginLeft = (obj.margin * -1) + 'px';
				this.inner.style.marginTop = (obj.margin * -1) + 'px';
				this.innerPartnersBlock.style.marginLeft = (obj.margin * -1) + 'px';
				this.innerPartnersBlock.style.marginTop = (obj.margin * -1) + 'px';
			}.bind(this));
		},

		getTileCalculating : function()
		{
			var wrapperWidth = this.wrapper.clientWidth;
			var wholeMarginSize =  wrapperWidth / 100 * 5; // 4% of whole width for margins
			var width = 0,
				tileAmountInRow = 0;

			if(this.tileRowLength)
			{
				tileAmountInRow = this.tileRowLength;
				width = (wrapperWidth - wholeMarginSize) / this.tileRowLength;
			}
			else
			{
				width = this.minTileWidth;
				tileAmountInRow = (wrapperWidth - wholeMarginSize) / width;

				// if tiles in one line can fit more than tiles amount
				if(tileAmountInRow > this.tiles.length)
				{
					width = (wrapperWidth - wholeMarginSize) / this.tiles.length;
					width = width > this.maxTileWidth ? this.maxTileWidth : width;
				}
				// if there is an hole (width doesn't fit) in the end tile row, increase tile width
				else if((tileAmountInRow - Math.floor(tileAmountInRow)) > 0)
				{
					tileAmountInRow = Math.floor(tileAmountInRow);
					width = (wrapperWidth - wholeMarginSize) / tileAmountInRow;
				}
			}

			return {
				width: width,
				margin: wholeMarginSize / (tileAmountInRow-1),
				height: width / this.tileRatio
			};
		}


	};

 	BX.ContactCenter.Menu = function(params)
	{
		this.element = params.element;
		this.bindElement = document.getElementById(params.bindElement);
		this.items = this.prepareItems(params.items);

		this.init();
	};

	BX.ContactCenter.Menu.prototype =
	{
		init: function ()
		{
			var params = {
				maxHeight: 300
			};

			var existedPopup = BX.PopupWindowManager.getPopupById('menu-popup-' + this.element);
			if (existedPopup)
			{
				existedPopup.destroy();
			}

			this.menu = new BX.PopupMenuWindow(
				this.element,
				this.bindElement,
				this.items,
				params
			);

			BX.bind(this.bindElement, 'click', BX.delegate(this.show, this));
		},

		show: function()
		{
			this.menu.show();
		},

		close: function()
		{
			this.menu.close();
		},

		prepareItems: function (items)
		{
			if (typeof items === "object")
			{
				items = Object.values(items)
			}

			var newItems = [];
			var newItem;

			for (var i = 0; i < items.length; i++)
			{
				newItem = this.prepareItem(items[i]);

				if (newItem.delimiterBefore)
				{
					newItems.push({delimiter: true});
				}

				newItems.push(newItem);

				if (newItem.delimiterAfter)
				{
					newItems.push({delimiter: true});
				}
			}

			return newItems;
		},

		prepareItem: function (item)
		{
			const newItem = {
				title: BX.util.htmlspecialcharsback(item.NAME),
				text: item.NAME,
				delimiterAfter: item.DELIMITER_AFTER,
				delimiterBefore: item.DELIMITER_BEFORE,
			};

			if (item.IS_ACTIVE === 'Y')
			{
				newItem.className = 'contact-center-list-item-status-active';
			}
			else if (item.IS_ACTIVE === 'N')
			{
				newItem.className = 'contact-center-list-item-status-inactive';
			}

			if (item.DISABLED)
			{
				newItem.disabled = true;
			}

			if (item.FIXED)
			{
				newItem.className = 'menu-popup-no-icon intranet-contact-list-item-add';
			}

			if (item.ONCLICK)
			{
				newItem.onclick = BX.delegate(
					function (e) {
						eval(item.ONCLICK);
						this.close();
					},
					this
				);
			}

			if (item.LIST)
			{
				newItem.items = this.prepareItems(item.LIST);
			}

			return newItem;
		},
	};

	BX.ContactCenter.Loader = function(parentSelector)
	{
		this.parentNode = document.querySelector("#" + parentSelector + "");
		this.blockNode = this.parentNode.querySelector(".intranet-contact-item");
		this.loaderIndicator = new BX.Loader({
			size: 40,
			color: '#868d95'
		});
		this.body = BX.create("div", {
			props: {
				className: "intranet-side-panel-overlay"
			}
		});

		this.setSizeDelegated();

		BX.bind(window, 'resize', this.setSizeDelegated.bind(this));
	};

	BX.ContactCenter.Loader.prototype =
	{
		show: function (item)
		{
			if (item)
			{
				this.parentNode = item.parentNode;
				this.blockNode = item;
			}
			this.parentNode.insertBefore(this.body, this.blockNode);
			this.loaderIndicator.show(this.body);
		},
		hide: function ()
		{
			this.loaderIndicator.hide();
			this.parentNode.removeChild(this.body);
		},
		setSize: function () {
			this.body.style.width = this.blockNode.clientWidth + "px";
			this.body.style.height = this.blockNode.clientHeight + "px";
			this.body.style.marginTop = this.blockNode.style.marginTop;
			this.body.style.marginLeft = this.blockNode.style.marginLeft;
		},
		setSizeDelegated: function () {
			setTimeout(
				BX.delegate(function(event) {
					this.setSize();
				}, this),
				500
			)
		}
	};

	BX.ContactCenter.Ajax = function(params, appearance)
	{
		this.signedParameters = params.signedParameters;
		this.componentName = params.componentName;
		this.sliderUrls = params.sliderUrls;
		this.loader = new BX.ContactCenter.Loader(params.parentSelector);
		this.appearance = appearance;

		this.init();
	};

	BX.ContactCenter.Ajax.prototype =
	{
		init: function ()
		{
			BX.addCustomEvent(
				"SidePanel.Slider:onMessage",
				BX.delegate(function(event) {
					if (event.getEventId() === "ContactCenter:reload")
					{
						this.reload();
					}
					else if (event.getEventId() === "ContactCenter:reloadItem")
					{
						var data = event.getData();
						var item = document.querySelector("#intranet-contact-list [data-module=\""+data.moduleId+"\"][data-item=\""+data.itemCode+"\"]");
						this.reloadItem(item);
					}
				}, this)
			);

			BX.addCustomEvent(
				"Rest:AppLayout:ApplicationInstall",
				BX.delegate(function(installed, eventResult) {
					this.reload();
				}, this)
			);
		},
		reload: function ()
		{
			//this.loader.show();

			BX.ajax.runComponentAction(this.componentName, 'reload', {
				mode: 'class',
				signedParameters: this.signedParameters
			}).then(
				BX.delegate(
					function(response) {
						var elem = BX.create('div');
						elem.innerHTML = response.data.html;
						BX('intranet-contact-list').innerHTML = elem.querySelector('#intranet-contact-list').innerHTML;
						this.appearance.loadPage(response.data.js_data);
						BX.remove(elem);
						//this.loader.hide();
					},
					this
				),
				BX.delegate(
					function(response) {
						//this.loader.hide();
					},
					this
				)
			);
		},
		reloadItem: function (item)
		{
			this.loader.show(item);
			var data = {
				moduleId: item.dataset.module,
				itemCode: item.dataset.item,
			};
			BX.ajax.runComponentAction(this.componentName, 'reloadItem', {
				mode: 'class',
				signedParameters: this.signedParameters,
				data: data
			}).then(
				BX.delegate(
					function(response) {
						this.appearance.loadItem(item, response.data);
						this.loader.hide();
					},
					this
				),
				BX.delegate(
					function(response) {
						this.loader.hide();
					},
					this
				)
			);
		},
		isContactCenterBlockUrl: function(url)
		{
			var result = false,
				reg;

			if (url)
			{
				for (var i = 0; i < this.sliderUrls.length; i++)
				{
					reg = new RegExp(this.sliderUrls[i], 'ig');
					if (url.match(reg) !== null)
					{
						result = true;
						break;
					}
				}
			}

			return result;
		}
	};

 	BX.ContactCenter.Appearance = function(params)
	{
		this.loadPage(params);
	};

	BX.ContactCenter.Appearance.prototype =
	{
		loadPage: function(params)
		{
			var wrapper = BX('intranet-contact-wrap');
			var title_list = Array.prototype.slice.call(wrapper.getElementsByClassName('intranet-contact-item'));

			this.setItemBlockColorAll(wrapper);

			new BX.ContactCenter.TileGrid({
				wrapper: wrapper,
				inner: BX(params.parentSelector),
				innerPartnersBlock: BX(params.parentSelectorPartnersBlock),
				tiles: title_list,
				sizeSettings : {
					minWidth : 190,
					maxWidth: 250
				}
			});

			if (params.handleMailLinks)
			{
				this.bindMailPagesSlider();
			}

			if (params.menu)
			{
				for (var i = 0; i < params.menu.length; i++)
				{
					new BX.ContactCenter.Menu(params.menu[i]);
				}
			}
		},
		loadItem: function(item, params)
		{
			if (params.data.SELECTED)
			{

				item.classList.add('intranet-contact-item-selected');
				this.setBlockColor(item, '');
				if (params.data.COLOR_CLASS)
				{
					item.classList.add(params.data.COLOR_CLASS);
				}

				if (
					BX.type.isArray(params.data.LIST)
					&& params.data.LIST.length > 0
					&& params.data.ITEM_CODE
				)
				{
					item.setAttribute('onclick', null);
					item.setAttribute('id', 'feed-add-post-form-link-text-' + params.data.ITEM_CODE);
				}
				if (params.data.LINK_TYPE === 'newWindow')
				{
					item.setAttribute('onclick', 'top.window.location="' + BX.Text.encode(params.data.LINK) + '"');
				}
			}
			else
			{
				item.classList.remove('intranet-contact-item-selected');
				item.style.backgroundColor = '#ffffff';
			}
			if (params.data.IS_NEW)
			{
				var labelClassName = params.data.SELECTED
					? 'intranet-contact-center-item-label-new-active'
					: 'intranet-contact-center-item-label-new';
				var newLabel = item.querySelector('[data-role="item-new-label"]');
				if (newLabel)
				{
					newLabel.classList.remove([
						'intranet-contact-center-item-label-new',
						'intranet-contact-center-item-label']
					);
					newLabel.classList.add(labelClassName);
					var labelTextNode = newLabel.children[0];
					if (labelTextNode)
					{
						var textClassName = params.data.SELECTED
							? 'intranet-contact-center-item-label-new-text-active'
							: 'intranet-contact-center-item-label-new-text';
						labelTextNode.classList.remove([
							'intranet-contact-center-item-label-new-text-active',
							'intranet-contact-center-item-label-new-text']
						);
						labelTextNode.classList.add(textClassName);
					}
				}
			}

			if (params.js_data.menu)
			{
				for (var i = 0; i < params.js_data.menu.length; i++)
				{
					new BX.ContactCenter.Menu(params.js_data.menu[i]);
				}
			}
		},
		bindMailPagesSlider: function ()
		{
			if (window === window.top)
			{
				top.BX.SidePanel.Instance.bindAnchors({
					rules: [
						{
							condition: [
								'^/mail/config/(new|edit)',
							],
							options: {
								width: 760,
								cacheable: false,
								allowChangeHistory: false
							}
						}
					]
				});
			}
		},
		setItemBlockColorAll: function (wrapper)
		{
			var items = wrapper.querySelectorAll('.intranet-contact-item');

			//special for rest-app blocks
			var colorList = [
				"#90be00",
				"#2fc6f6",
				"#ff5752",
				"#55d0e0",
				"#3871ba",
				"#ffa900",
				"#3e7cac",
				"#38659f",
				"#02aff0",
				"#00b4ac",
				"#d56c9a"
			];
			var colorIterator = 0;
			var color = '';

			for (var i = 0; i < items.length; i++)
			{
				if (items[i].classList.contains('intranet-contact-item-selected'))
				{
					var iconBlock = items[i].querySelector('.intranet-contact-logo');
					if (iconBlock && iconBlock.classList.contains('ui-icon-service-common'))
					{
						color = colorList[colorIterator];
						colorIterator = (colorIterator < colorList.length - 1) ? colorIterator + 1 : 0;
					}
					else
					{
						color = '';
					}

					this.setBlockColor(items[i], color);
				}
			}
		},
		setBlockColor: function (block, color)
		{
			block = BX(block);
			var iconBlock = block.querySelector('.intranet-contact-logo i');

			if (iconBlock)
			{
				if (color !== '')
				{
					block.style.backgroundColor = color;
					iconBlock.style.backgroundColor = color;
				}
				else
				{
					var style = getComputedStyle(iconBlock);
					block.style.backgroundColor = style.backgroundColor;
				}
			}
		},
		setBlockImage: function (block, image)
		{
			block = BX(block);
			var iconBlock = block.querySelector('.intranet-contact-logo i');

			if (iconBlock)
			{
				if (!!image)
				{
					block.style.backgroundImage = color;
					iconBlock.style.backgroundImage = color;
				}
			}
		}
	};

	BX.ContactCenter.Init = function(params)
	{
		var appearance = new BX.ContactCenter.Appearance(params);
		var ajax = new BX.ContactCenter.Ajax(params, appearance);

		var route = (new BX.Uri(window.location.href)).getQueryParam('route') || '';
		if (route && /^\/\w+/.test(route))
		{
			BX.SidePanel.Instance.emulateAnchorClick(route);
		}
	};

	BX.ContactCenter.MarketplaceApp = function(applicationId, appCode)
	{
		this.openRestAppLayout(applicationId, appCode);
	};

	BX.ContactCenter.MarketplaceApp.prototype =
	{
		openRestAppLayout: function(applicationId, appCode)
		{
			BX.ajax.runComponentAction(
				"bitrix:intranet.contact_center.list",
				"getRestApp",
				{
					mode: 'class',
					data: {
						code: appCode
					}
				}
			).then(function(response) {
				if(response.data.TYPE === "A")
				{
					this.showRestApplication(appCode);
				}
				else
				{
					BX.rest.AppLayout.openApplication(applicationId);
				}
			});
		},
		showRestApplication: function(appCode)
		{
			var applicationUrlTemplate = "/marketplace/detail/#app#/";
			var url = applicationUrlTemplate.replace("#app#", encodeURIComponent(appCode));
			BX.SidePanel.Instance.open(url, {allowChangeHistory: false});
		},
	};

})();
