(function ()
{
	'use strict';

	BX.namespace('BX.Timeman.Component');

	BX.Timeman.Component.BaseComponent = function (options)
	{
		var scheduleCreateSliderWidth = options.scheduleCreateSliderWidth ? options.scheduleCreateSliderWidth : 1200;
		this.container = options.containerSelector ? document.querySelector(options.containerSelector) : document;

		this.exportManager = ((options.exportManager && options.exportManager instanceof BX.Timeman.Export) ?
			options.exportManager : null);

		if (window === window.top)
		{
			BX.SidePanel.Instance.bindAnchors({
				rules: [
					{
						condition: [
							new RegExp("/timeman/schedules/[0-9]+/shiftplan/($|\\?)", "i")
						],
						options: {
							cacheable: false,
							allowChangeHistory: false,
							width: 1400
						}
					},
					{
						condition: [
							new RegExp("/timeman/schedules/[0-9]+/update/($|\\?)", "i")
						],
						options: {
							cacheable: false,
							allowChangeHistory: false,
							width: 1200
						}
					},
					{
						condition: [
							new RegExp("/timeman/schedules/add/($|\\?)", "i")
						],
						options: {
							cacheable: false,
							allowChangeHistory: false,
							width: scheduleCreateSliderWidth
						}
					},
					{
						condition: [
							new RegExp("/timeman/schedules/[0-9]+/shifts/add/($|\\?)", "i")
						],
						options: {
							cacheable: false,
							allowChangeHistory: false,
							width: 800
						}
					}
				]
			});
		}
	};

	BX.Timeman.Component.BaseComponent.prototype = {
		selectOneByRole: function (role, container)
		{
			return container ?
				container.querySelector(this.buildSelectorByRole(role))
				: this.container.querySelector(this.buildSelectorByRole(role));
		},
		toggleSelectedItemInMenuPopup: function (selectedItem, popup)
		{
			if (!selectedItem || !popup)
			{
				return;
			}
			if (popup && popup.layout)
			{
				var selected = this.getMenuItemSelected(selectedItem.id, popup);
				if (selected)
				{
					selected.classList.toggle('menu-popup-item-take');
					selected.classList.toggle('menu-popup-no-icon');
				}
			}
		},
		getCookie: function (name)
		{
			var matches = document.cookie.match(new RegExp(
				"(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
			));

			return matches ? decodeURIComponent(matches[1]) : null;
		},
		setCookie: function (name, value, options)
		{
			var oldValue = this.getCookie(name);
			if (oldValue === value)
			{
				return;
			}
			options = options || {path: '/timeman/'};
			var expires = options.expires;
			if (typeof (expires) == "number" && expires)
			{
				var currentDate = new Date();
				currentDate.setTime(currentDate.getTime() + expires * 1000);
				expires = options.expires = currentDate;
			}
			if (expires && expires.toUTCString)
			{
				options.expires = expires.toUTCString();
			}
			value = encodeURIComponent(value);
			var updatedCookie = name + "=" + value;
			for (var propertyName in options)
			{
				if (!options.hasOwnProperty(propertyName))
				{
					continue;
				}
				updatedCookie += "; " + propertyName;
				var propertyValue = options[propertyName];
				if (propertyValue !== true)
				{
					updatedCookie += "=" + propertyValue;
				}
			}
			document.cookie = updatedCookie;
			return true;
		},
		getMenuItemSelected: function (selectedItemId, popup)
		{
			var item = popup.layout.menuContainer.querySelector('.js-id-' + selectedItemId);
			if (!item)
			{
				var menuItems = popup.getMenuItems();
				for (var i = 0; i < menuItems.length; i++)
				{
					if (popup.getMenuItem(menuItems[i].id)
						&& popup.getMenuItem(menuItems[i].id).getSubMenu()
						&& popup.getMenuItem(menuItems[i].id).getSubMenu().getMenuItem(selectedItemId)
						&& popup.getMenuItem(menuItems[i].id).getSubMenu().getMenuItem(selectedItemId).menuWindow)
					{
						return popup.getMenuItem(menuItems[i].id).getSubMenu().getMenuItem(selectedItemId).menuWindow.layout.menuContainer.querySelector('.js-id-' + selectedItemId);
					}
				}
			}
		},
		selectAllByRole: function (role, container)
		{
			return container ?
				container.querySelectorAll(this.buildSelectorByRole(role))
				: this.container.querySelectorAll(this.buildSelectorByRole(role));
		},
		buildSelectorByRole: function (role)
		{
			return '[data-role="' + role + '"]';
		},
		toggleElementVisibility: function (element)
		{
			if (!element)
			{
				return;
			}
			if (element.classList.contains('timeman-hide'))
			{
				this.showElement(element);
				return;
			}
			this.hideElement(element);
		},
		showElement: function (element)
		{
			if (element)
			{
				element.classList.remove('timeman-hide');
			}
		},
		hideElement: function (element)
		{
			if (element)
			{
				element.classList.add('timeman-hide');
			}
		}
	};
})();