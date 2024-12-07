/**
 * @module stafftrack/map/location-menu
 */
jn.define('stafftrack/map/location-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { Icon } = require('ui-system/blocks/icon');

	const { BaseMenu, baseSectionType, customSectionType } = require('stafftrack/base-menu');
	const { LocationEnum } = require('stafftrack/model/shift');

	class LocationMenu extends BaseMenu
	{
		getItems()
		{
			return [
				this.getItem(LocationEnum.REMOTELY.getValue()),
				this.getItem(LocationEnum.OFFICE.getValue()),
				this.getItem(LocationEnum.HOME.getValue()),
				this.getItem(LocationEnum.OUTSIDE.getValue()),
				this.getItem(LocationEnum.CUSTOM.getValue(), true),
			];
		}

		getItem(code, customSection = false)
		{
			return {
				id: code,
				testId: `stafftrack-location-menu-${code}`,
				sectionCode: customSection ? customSectionType : baseSectionType,
				title: this.getText(code),
				iconName: this.getIconName(code),
				styles: {
					icon: {
						color: Color.base3.toHex(),
					},
				},
			};
		}

		getText(value)
		{
			switch (value)
			{
				case LocationEnum.REMOTELY.getValue():
				case LocationEnum.OFFICE.getValue():
				case LocationEnum.HOME.getValue():
				case LocationEnum.OUTSIDE.getValue():
				case LocationEnum.CUSTOM.getValue():
					return this.props.locationList[value].name;
				default:
					return value;
			}
		}

		getIconName(value)
		{
			switch (value)
			{
				case LocationEnum.REMOTELY.getValue():
					return Icon.EARTH.getIconName();
				case LocationEnum.OFFICE.getValue():
					return Icon.COMPANY.getIconName();
				case LocationEnum.HOME.getValue():
					return Icon.HOME.getIconName();
				case LocationEnum.OUTSIDE.getValue():
					return Icon.MAP.getIconName();
				case LocationEnum.CUSTOM.getValue():
					return Icon.CHEVRON_TO_THE_RIGHT.getIconName();
				default:
					return null;
			}
		}

		getSections()
		{
			return [
				{
					id: baseSectionType,
					title: Loc.getMessage('M_STAFFTRACK_MAP_LOCATION_MENU_TITLE'),
					styles: {
						title: {
							font: {
								size: 16,
								color: Color.base1.toHex(),
								fontStyle: 'bold',
							},
						},
					},
				},
				{
					id: customSectionType,
					title: '',
				},
			];
		}
	}

	module.exports = { LocationMenu };
});
