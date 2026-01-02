/**
 * @module background/ui-manager
 */
jn.define('background/ui-manager', (require, exports, module) => {
	const { debounce } = require('utils/function');
	const BackgroundUIManagerEvents = {
		tryToOpenComponentFromAnotherContext: 'BackgroundUIManagerEvents::tryToOpenComponentFromAnotherContext',
		openComponentInAnotherContext: 'BackgroundUIManager::openComponentInAnotherContext',
	};

	/**
	 * @class BackgroundUIManager
	 * Manager for background context that allows to open only
	 * one component at a time on application initialization.
	 */
	class BackgroundUIManager
	{
		constructor()
		{
			this.currentComponent = null;

			this.debounceedOpenComponentCallback = debounce(this.openComponentCallback, 3000, this);
			this.isComponentOpened = false;
			this.openComponentInAnotherContext = this.openComponentInAnotherContext.bind(this);
			this.bindEvents();
		}

		bindEvents()
		{
			BX.addCustomEvent(
				BackgroundUIManagerEvents.tryToOpenComponentFromAnotherContext,
				(data) => {
					const {
						componentName = null,
						priority = 0,
					} = data;

					if (componentName)
					{
						this.openComponent(
							componentName,
							this.openComponentInAnotherContext,
							priority,
						);
					}
				},
			);
		}

		openComponentInAnotherContext()
		{
			if (this.currentComponent)
			{
				BX.postComponentEvent(
					BackgroundUIManagerEvents.openComponentInAnotherContext,
					[
						this.currentComponent.componentName,
					],
				);
			}
		}

		openComponentCallback()
		{
			if (!this.currentComponent)
			{
				return;
			}

			const componentName = this.currentComponent.componentName;
			if (!componentName)
			{
				return;
			}

			const componentWasOpened = window?.backgroundUiManager?.[componentName];

			if (this.currentComponent.openCallback && componentWasOpened === undefined)
			{
				this.isComponentOpened = true;
				this.currentComponent.openCallback();
				window.backgroundUiManager = window.backgroundUiManager || {};
				window.backgroundUiManager[componentName] = true;
			}
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		canOpenComponentInBackground()
		{
			return this.currentComponent === null;
		}

		/**
		 * @public
		 */
		onCloseActiveComponent()
		{
			this.currentComponent = null;
			window.backgroundUiManager = window.backgroundUiManager || {};
			window.backgroundUiManager[this.currentComponent?.componentName] = null;
		}

		/**
		 * @public
		 * @param {string} componentName
		 * @param {function} openCallback
		 * @param {number} priority
		 */
		openComponent(componentName, openCallback, priority)
		{
			if (
				this.canOpenComponentInBackground()
				|| (
					this.currentComponent !== null
					&& this.isComponentOpened === false
					&& this.currentComponent.priority < priority
				)
			)
			{
				this.currentComponent = {
					componentName,
					openCallback,
					priority,
				};

				this.debounceedOpenComponentCallback();
			}
		}
	}

	module.exports = {
		BackgroundUIManager: new BackgroundUIManager(),
	};
});
