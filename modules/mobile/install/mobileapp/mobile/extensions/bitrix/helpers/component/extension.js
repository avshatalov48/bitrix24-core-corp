(() =>
{
	class ComponentHelper
	{
		/**
		 *
		 * @param options - parameters
		 * @param options.name - name of component (display in debugger)
		 * @param options.version - name of component (display in debugger)
		 * @param options.object - name of list object
		 * @param options.widgetParams - parameters of list widget
		 * @param options.componentParams - parameters of component which will be available thought BX.componentsParameters
		 *
		 */
		static openList(options = {})
		{
			let widgetParams = {};
			widgetParams.name = "list";
			widgetParams.settings =  options.widgetParams || {};
			widgetParams.settings.objectName = options.object;
			PageManager.openComponent("JSStackComponent",
				{
					scriptPath: "/mobile/mobile_component/" + options.name + "/?version=" + options.version,
					componentCode: options.name,
					params: options.componentParams,
					rootWidget: widgetParams
				});
		}
	}

	this.ComponentHelper = ComponentHelper;
})();