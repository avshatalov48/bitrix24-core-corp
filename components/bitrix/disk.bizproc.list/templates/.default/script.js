(function() {

	"use strict";

	/**
	 * @namespace BX.Disk.Component
	 */
	BX.namespace("BX.Disk.Component");

	/**
	 *
	 * @param {object} parameters
	 * @constructor
	 */
	BX.Disk.Component.BizprocList = function(parameters)
	{
		this.layout = {};
		this.layout.createButton = parameters.layout.createButton;
		this.createItems = parameters.createItems;
		this.linkPatterns = parameters.linkPatterns;

		this.bindEvents();
	};

	BX.Disk.Component.BizprocList.prototype =
		{
			constructor: BX.Disk.Component.BizprocList,

			bindEvents: function ()
			{
				BX.bind(this.layout.createButton, 'click', this.showCreateMenu.bind(this));

				BX.SidePanel.Instance.bindAnchors({
					rules: [
						{
							condition: [
								this.linkPatterns.createBlank,
								this.linkPatterns.editUrl
							],
							handler: function (event, link) {
								if(top)
								{
									top.document.location = link.url;
									event.preventDefault();
								}
							}
						}
					]
				});
			},

			showCreateMenu: function (event)
			{
				var menuItems = [];
				this.createItems.forEach(function (item) {
					item.target = '_top';
					menuItems.push(item);
				});

				BX.PopupMenu.show('BizprocList-create', BX.getEventTarget(event), menuItems,
					{
						angle: {
							position: 'top',
							offset: 45
						},
						autoHide: true,
						overlay: {
							opacity: 0.01
						}
					}
				);
			}
		};
})();
