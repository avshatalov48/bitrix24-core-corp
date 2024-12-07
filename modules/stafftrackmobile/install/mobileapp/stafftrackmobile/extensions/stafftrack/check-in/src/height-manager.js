/**
 * @module stafftrack/check-in/height-manager
 */
jn.define('stafftrack/check-in/height-manager', (require, exports, module) => {
	const { StatusEnum } = require('stafftrack/model/shift');

	const HEADER_HEIGHT = 60;
	const MESSAGE_HEIGHT = 125;
	const MAP_HEIGHT = 210;
	const BUTTONS_HEIGHT = 100;
	const BOTTOM_PANEL = 30;
	const CARD_HEIGHT = 120;

	/**
	 * @class HeightManager
	 */
	class HeightManager
	{
		constructor(layoutWidget)
		{
			this.layoutWidget = layoutWidget;
			this.layoutHeight = HeightManager.getDefaultHeight();

			this.enabledBySettings = true;
			this.shiftStatus = null;
		}

		setStatus(shiftStatus)
		{
			this.shiftStatus = shiftStatus;
		}

		setEnabledBySettings(enabledBySettings)
		{
			this.enabledBySettings = enabledBySettings;
		}

		updateSheetHeight()
		{
			const layoutHeight = this.getLayoutHeight();

			if (layoutHeight !== this.layoutHeight)
			{
				this.changeSheetHeight(layoutHeight);
			}
		}

		changeSheetHeight(layoutHeight)
		{
			if (!this.layoutWidget)
			{
				return;
			}

			this.layoutHeight = layoutHeight;
			this.layoutWidget.setBottomSheetParams({ mediumPositionHeight: this.layoutHeight });
			this.layoutWidget.setBottomSheetHeight(this.layoutHeight);
		}

		getLayoutHeight()
		{
			return HeightManager.getDefaultHeight() + this.getAdditionalSheetHeight();
		}

		static getDefaultHeight()
		{
			return (
				HEADER_HEIGHT
				+ MESSAGE_HEIGHT
				+ MAP_HEIGHT
				+ BUTTONS_HEIGHT
				+ BOTTOM_PANEL
			);
		}

		getAdditionalSheetHeight()
		{
			if (!this.enabledBySettings)
			{
				return 0;
			}

			switch (this.shiftStatus)
			{
				case StatusEnum.WORKING.getValue():
					return 0;
				case StatusEnum.NOT_WORKING.getValue():
				case StatusEnum.CANCEL_WORKING.getValue():
					return -(MAP_HEIGHT + BUTTONS_HEIGHT) + CARD_HEIGHT;
				default:
					return 0;
			}
		}
	}

	module.exports = { HeightManager };
});
