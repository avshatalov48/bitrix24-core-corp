(function() {

	"use strict";

	/**
	 * @namespace BX.Disk.Model.ExternalLink
	 */
	BX.namespace("BX.Disk.Model.ExternalLink");

	/**
	 *
	 * @param {object} parameters
	 * @extends {BX.Disk.Model.Item}
	 * @constructor
	 */
	BX.Disk.Model.ExternalLink.Description = function(parameters)
	{
		BX.Disk.Model.Item.apply(this, arguments);

		this.templateId = this.templateId || 'external-link-setting-info';
	};

	BX.Disk.Model.ExternalLink.Description.prototype =
	{
		__proto__: BX.Disk.Model.Item.prototype,
		constructor: BX.Disk.Model.ExternalLink.Description,

		getDefaultStateValues: function ()
		{
			return {
				buildText: this.buildText.bind(this)
			};
		},

		bindEvents: function ()
		{
			BX.addCustomEvent("Disk.ExternalLink.Settings:onSave", this.handleSavingExternalLinkSettings.bind(this));
		},

		handleSavingExternalLinkSettings: function (settingsModel)
		{
			this.setState(settingsModel.state);
			this.render();
		},

		buildText: function ()
		{
			var externalLinkDescription = this.state;
			var text = [];
			var calcRelativePeriod = false;
			if(externalLinkDescription.hasPassword)
			{
				text.push(BX.message('DISK_JS_EL_DESCRIPTION_EXTERNAL_LINK_WITH_PASSWORD'));
			}

			if(externalLinkDescription.hasDeathTime)
			{
				text.push(BX.message('DISK_JS_EL_DESCRIPTION_EXTERNAL_LINK_WITH_DEATH_TIME'));
			}

			if(calcRelativePeriod && externalLinkDescription.hasDeathTime)
			{
				var secondsToDeath = externalLinkDescription.deathTimeTimestamp - (Date.now() / 1000);
				var daysToDeath = secondsToDeath / (24*3600);
				daysToDeath = parseInt(daysToDeath, 10);
				var textToDeath = [];

				if(daysToDeath >= 1)
				{
					textToDeath.push(BX.Disk.getNumericCase(
						daysToDeath,
						BX.message('DISK_JS_EL_DESCRIPTION_DAY_NUMERAL_1'),
						BX.message('DISK_JS_EL_DESCRIPTION_DAY_NUMERAL_21'),
						BX.message('DISK_JS_EL_DESCRIPTION_DAY_NUMERAL_2_4'),
						BX.message('DISK_JS_EL_DESCRIPTION_DAY_NUMERAL_5_20')
					).replace('#COUNT#', daysToDeath));
				}
				else
				{
					var hourToDeath = parseInt(secondsToDeath / 3600, 10);
					if(hourToDeath > 0)
					{
						textToDeath.push(BX.Disk.getNumericCase(
							hourToDeath,
							BX.message('DISK_JS_EL_DESCRIPTION_HOUR_NUMERAL_1'),
							BX.message('DISK_JS_EL_DESCRIPTION_HOUR_NUMERAL_21'),
							BX.message('DISK_JS_EL_DESCRIPTION_HOUR_NUMERAL_2_4'),
							BX.message('DISK_JS_EL_DESCRIPTION_HOUR_NUMERAL_5_20')
						).replace('#COUNT#', hourToDeath));
					}

					var minutesToDeath = parseInt((secondsToDeath - hourToDeath * 3600) / 60, 10);
					if(minutesToDeath)
					{
						textToDeath.push(BX.Disk.getNumericCase(
							daysToDeath,
							BX.message('DISK_JS_EL_DESCRIPTION_MINUTE_NUMERAL_1'),
							BX.message('DISK_JS_EL_DESCRIPTION_MINUTE_NUMERAL_21'),
							BX.message('DISK_JS_EL_DESCRIPTION_MINUTE_NUMERAL_2_4'),
							BX.message('DISK_JS_EL_DESCRIPTION_MINUTE_NUMERAL_5_20')
						).replace('#COUNT#', minutesToDeath));
					}
				}

				text.push(textToDeath.join(' '));
			}

			return text.join(', ');
		}
	};
})();