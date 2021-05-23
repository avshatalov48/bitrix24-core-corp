(function()
{
	class ChatReadyCheck
	{
		constructor()
		{
			this.chatReady = false;
		}

		wait()
		{
			return new Promise((resolve) =>
			{
				if (this.chatReady === true)
				{
					return resolve();
				}

				let responseHandler = () => {
					this.chatReady = true;
					resolve();
					BX.removeCustomEvent("ImRecent::ready", responseHandler);
				};
				BX.addCustomEvent("ImRecent::ready", responseHandler);
				BX.postComponentEvent("ImRecent::checkReady", [], "im.recent");
			});
		}
	}

	window.ChatReadyCheck = new ChatReadyCheck();
})();