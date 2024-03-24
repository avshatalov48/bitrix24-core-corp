(() => {
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
			const widgetParams = {};
			widgetParams.name = 'list';
			const canOpenInDefault = options.canOpenInDefault || false;
			widgetParams.settings = options.widgetParams || {};
			widgetParams.settings.objectName = options.object;
			PageManager.openComponent(
				'JSStackComponent',
				{
					scriptPath: `/mobileapp/jn/${options.name}/?version=${options.version}`,
					componentCode: options.name,
					canOpenInDefault,
					params: options.componentParams,
					rootWidget: widgetParams,
				},
			);
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
			const widgetParams = {};
			widgetParams.name = 'form';
			widgetParams.settings = options.widgetParams || {};
			widgetParams.settings.objectName = options.object;
			PageManager.openComponent(
				'JSStackComponent',
				{
					scriptPath: `/mobileapp/jn/${options.name}/?version=${options.version}`,
					componentCode: options.name,
					params: options.componentParams,
					rootWidget: widgetParams,
				},
			);
		}

		/**
		 * @param options - parameters
		 * @param options.name - name of component (display in debugger)
		 * @param options.version - name of component (display in debugger)
		 * @param options.object - name of list object
		 * @param options.widgetParams - parameters of list widget
		 * @param options.componentParams - parameters of component which will be available thought BX.componentsParameters
		 * @param parentWidget
		 */
		static openLayout(options = {}, parentWidget = null)
		{
			if (!options.name)
			{
				throw new Error('Component name is empty.');
			}
			const canOpenInDefault = options.canOpenInDefault || false;

			let version = options.version;
			if (!version)
			{
				version = availableComponents[options.name] && availableComponents[options.name].version || '1.0';
			}

			const widgetParams = {};
			widgetParams.name = 'layout';
			widgetParams.settings = options.widgetParams || {};
			widgetParams.settings.objectName = 'layout';

			PageManager.openComponent(
				'JSStackComponent',
				{
					scriptPath: `/mobileapp/jn/${options.name}/?version=${version}`,
					componentCode: options.name,
					canOpenInDefault,
					params: options.componentParams,
					rootWidget: widgetParams,
				},
				parentWidget,
			);
		}
	}

	this.ComponentHelper = ComponentHelper;
})();
