(function ()
{
	'use strict';

	BX.namespace('BX.Timeman.Component');

	BX.Timeman.Component.BaseComponent = function (options)
	{
		this.container = options.containerSelector ? document.querySelector(options.containerSelector) : document;
	};

	BX.Timeman.Component.BaseComponent.prototype = {
		selectOneByRole: function (role, container)
		{
			return container ?
				container.querySelector(this.buildSelectorByRole(role))
				: this.container.querySelector(this.buildSelectorByRole(role));
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