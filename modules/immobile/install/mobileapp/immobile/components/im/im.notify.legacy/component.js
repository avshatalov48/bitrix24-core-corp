"use strict";
(()=>{


	class NotifyLegacy
	{
		constructor()
		{
			console.log('Notify legacy is loaded.');

			let configMessages = BX.componentParameters.get("MESSAGES", {});
			for (let messageId in configMessages)
			{
				if (configMessages.hasOwnProperty(messageId))
				{
					BX.message[messageId] = configMessages[messageId];
				}
			}

			BX.addCustomEvent("onChangeTitleProgress", (progress) => this.updateTitle(progress));

			this.updateTitle();
		}

		updateTitle(progress = false)
		{
			widget.setTitle({
				text: BX.message('COMPONENT_TITLE'),
				useProgress: progress,
				largeMode: true,
			});
		}
	}

	window.Notify = new NotifyLegacy();

})();