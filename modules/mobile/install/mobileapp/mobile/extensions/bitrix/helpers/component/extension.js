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
		 * @param options.canOpenInDefault - if true component is can be opened in default navigation stack
		 * @param options.widgetParams - parameters of list widget
		 * @param options.componentParams - parameters of component which will be available thought BX.componentsParameters
		 *
		 */
		static openList(options = {})
		{
			let widgetParams = {};
			widgetParams.name = "list";
			let canOpenInDefault = options.canOpenInDefault || false;
			widgetParams.settings =  options.widgetParams || {};
			widgetParams.settings.objectName = options.object;
			PageManager.openComponent("JSStackComponent",
				{
					scriptPath: "/mobileapp/jn/" + options.name + "/?version=" + options.version,
					componentCode: options.name,
					canOpenInDefault: canOpenInDefault,
					params: options.componentParams,
					rootWidget: widgetParams
				});
		}

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
		static openForm(options = {})
		{
			let widgetParams = {};
			widgetParams.name = "form";
			widgetParams.settings =  options.widgetParams || {};
			widgetParams.settings.objectName = options.object;
			PageManager.openComponent("JSStackComponent",
				{
					scriptPath: "/mobileapp/jn/" + options.name + "/?version=" + options.version,
					componentCode: options.name,
					params: options.componentParams,
					rootWidget: widgetParams
				});
		}
	}

	this.ComponentHelper = ComponentHelper;
})();