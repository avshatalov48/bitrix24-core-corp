;(function() {
	"use strict";


	BX.namespace("BX.Landing.UI.Panel");


	/**
	 * @extends {BX.Landing.UI.Panel.Content}
	 * @param id
	 * @param data
	 * @constructor
	 */
	BX.Landing.UI.Panel.Icon = function(id, data)
	{
		BX.Landing.UI.Panel.Content.apply(this, arguments);
		this.layout.classList.add("landing-ui-panel-icon");
		this.overlay.classList.add("landing-ui-panel-icon");
		this.overlay.hidden = true;
		this.resolver = (function() {});
		this.libraries = [
			BX.Landing.Icon.FontAwesome,
			BX.Landing.Icon.SimpleLine,
			BX.Landing.Icon.SimpleLineProOne,
			BX.Landing.Icon.SimpleLineProTwo,
			BX.Landing.Icon.EtLineIcons,
			BX.Landing.Icon.HSIcons
		];
		this.layout.hidden = true;
		document.body.appendChild(this.layout);
	};


	/**
	 * @type {BX.Landing.UI.Panel.Icon}
	 */
	BX.Landing.UI.Panel.Icon.instance = null;


	/**
	 * Gets instance of BX.Landing.UI.Panel.Icon
	 * @return {BX.Landing.UI.Panel.Icon}
	 */
	BX.Landing.UI.Panel.Icon.getInstance = function()
	{
		if (!BX.Landing.UI.Panel.Icon.instance)
		{
			BX.Landing.UI.Panel.Icon.instance = new BX.Landing.UI.Panel.Icon("icon_panel", {
				title: BX.message("LANDING_ICONS_SLIDER_TITLE")
			});
		}

		return BX.Landing.UI.Panel.Icon.instance;
	};


	BX.Landing.UI.Panel.Icon.prototype = {
		constructor: BX.Landing.UI.Panel.Icon,
		__proto__: BX.Landing.UI.Panel.Content.prototype,

		show: function()
		{
			return new Promise(function(resolve) {
				this.resolver = resolve;
				this.makeLayout();
				BX.Landing.UI.Panel.Content.prototype.show.call(this);
			}.bind(this));
		},


		onChange: function(icon)
		{
			this.resolver(icon);
			this.hide();
		},


		makeLayout: function()
		{
			if (!this.content.innerHTML)
			{
				this.libraries.forEach(function(library) {
					this.appendSidebarButton(
						new BX.Landing.UI.Button.SidebarButton(library.id, {
							text: library.name
						})
					);

					library.categories.forEach(function(category) {
						this.appendSidebarButton(
							new BX.Landing.UI.Button.SidebarButton(category.id, {
								text: category.name,
								onClick: this.onCategoryChange.bind(this, category.id),
								child: true
							})
						);
					}, this);
				}, this);

				this.onCategoryChange(this.libraries[0].categories[0].id);
			}
		},

		onCategoryChange: function(id)
		{
			this.content.innerHTML = "";

			this.libraries.forEach(function(library) {
				library.categories.forEach(function(category) {
					if (id === category.id)
					{
						var map = new Map();

						var categoryCard = new BX.Landing.UI.Card.BaseCard({
							title: category.name,
							className: "landing-ui-card-icons"
						});

						this.appendCard(categoryCard);

						category.items.forEach(function(item) {
							var iconCard = new BX.Landing.UI.Card.IconPreview({
								iconClassName: item,
								onClick: function() {
									this.onChange(item);
								}.bind(this)
							});
							categoryCard.body.appendChild(iconCard.layout);

							var styles = getComputedStyle(iconCard.body.firstChild, ":before");

							if (map.has(styles.content))
							{
								iconCard.layout.hidden = true;
							}
							else
							{
								map.set(styles.content, true);
							}
						}, this);
					}
				}, this);
			}, this);
		}
	}

})();