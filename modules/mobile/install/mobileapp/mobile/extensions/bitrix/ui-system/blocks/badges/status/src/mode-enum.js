/**
 * @module ui-system/blocks/badges/status/src/mode-enum
 */
jn.define('ui-system/blocks/badges/status/src/mode-enum', (require, exports, module) => {
	const { Color } = require('tokens');
	const { BaseEnum } = require('utils/enums/base');

	const icons = {
		SUCCESS: (color) => `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_11272_358)"><path d="M0 8C0 3.58172 3.58172 0 8 0C12.4183 0 16 3.58172 16 8C16 12.4183 12.4183 16 8 16C3.58172 16 0 12.4183 0 8Z" fill="${color}"/><path d="M3.69238 8.00002L6.56418 11.0769L12.3078 4.9231" stroke="${Color.baseWhiteFixed}" stroke-width="1.4" stroke-linecap="round"/></g><defs><clipPath id="clip0_11272_358"><rect width="16" height="16" fill="${Color.baseWhiteFixed}"/></clipPath></defs></svg>`,
		WARNING: (color) => `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 8C0 3.58172 3.58172 0 8 0C12.4183 0 16 3.58172 16 8C16 12.4183 12.4183 16 8 16C3.58172 16 0 12.4183 0 8Z" fill="${color}"/><path d="M8.04395 9.64339L8.04413 3.69214M8.044 12.1049L8.04419 12.3075" stroke="${Color.baseWhiteFixed}" stroke-width="1.4" stroke-linecap="round"/></svg>`,
		DECLINE: (color) => `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 8C0 3.58172 3.58172 0 8 0C12.4183 0 16 3.58172 16 8C16 12.4183 12.4183 16 8 16C3.58172 16 0 12.4183 0 8Z" fill="${color}"/><path d="M4.92285 11.0769L11.0767 4.9231M11.0767 11.0769L4.92285 4.9231" stroke="${Color.baseWhiteFixed}" stroke-width="1.4" stroke-linecap="round"/></svg>`,
		OUTLINE_SUCCESS: (color) => `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M1 8C1 11.866 4.13401 15 8 15C11.866 15 15 11.866 15 8C15 4.13401 11.866 1 8 1C4.13401 1 1 4.13401 1 8ZM8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0Z" fill="${color}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M11.5983 5.40726C11.8371 5.6262 11.8533 5.99731 11.6343 6.23615L7.72987 10.4956C7.51607 10.7288 7.15584 10.7505 6.91561 10.5446L4.43096 8.41486C4.18495 8.204 4.15646 7.83364 4.36732 7.58763C4.57818 7.34163 4.94855 7.31314 5.19455 7.524L7.24838 9.28442L10.7694 5.4433C10.9883 5.20446 11.3594 5.18832 11.5983 5.40726Z" fill="${color}"/></svg>`,
		OUTLINE_DECLINE: (color) => `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M1 8C1 11.866 4.13401 15 8 15C11.866 15 15 11.866 15 8C15 4.13401 11.866 1 8 1C4.13401 1 1 4.13401 1 8ZM8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0Z" fill="${color}"/><path d="M11.234 4.94356C11.486 5.19558 11.486 5.60418 11.234 5.8562L9.00154 8.08866L11.2339 10.3211C11.486 10.5731 11.486 10.9817 11.2339 11.2337C10.9819 11.4857 10.5733 11.4857 10.3213 11.2337L8.0889 9.0013L5.85644 11.2338C5.60442 11.4858 5.19582 11.4858 4.9438 11.2338C4.69178 10.9817 4.69178 10.5731 4.9438 10.3211L7.17626 8.08866L4.9438 5.8562C4.69178 5.60418 4.69178 5.19558 4.9438 4.94356C5.19582 4.69154 5.60442 4.69154 5.85644 4.94356L8.0889 7.17602L10.3214 4.94356C10.5734 4.69154 10.982 4.69154 11.234 4.94356Z" fill="${color}"/></svg>`,
	};

	/**
	 * @class BadgeStatusModeType
	 * @template TBadgeModeType
	 * @extends {BaseEnum<BadgeStatusModeType>}
	 */
	class BadgeStatusMode extends BaseEnum
	{
		static SUCCESS = new BadgeStatusMode('SUCCESS', {
			color: Color.accentMainSuccess,
			icon: icons.SUCCESS,
		});

		static SUCCESS_PRIMARY = new BadgeStatusMode('SUCCESS_PRIMARY', {
			color: Color.accentMainPrimary,
			icon: icons.SUCCESS,
		});

		static WARNING = new BadgeStatusMode('WARNING', {
			color: Color.accentMainWarning,
			icon: icons.WARNING,
		});

		static WARNING_ALERT = new BadgeStatusMode('WARNING_ALERT', {
			color: Color.accentMainAlert,
			icon: icons.WARNING,
		});

		static WARNING_GREY = new BadgeStatusMode('WARNING_GREY', {
			color: Color.base3,
			icon: icons.WARNING,
		});

		static DECLINE = new BadgeStatusMode('DECLINE', {
			color: Color.accentMainAlert,
			icon: icons.DECLINE,
		});

		static OUTLINE_SUCCESS = new BadgeStatusMode('OUTLINE_SUCCESS', {
			color: Color.accentMainSuccess,
			icon: icons.OUTLINE_SUCCESS,
		});

		static OUTLINE_DECLINE = new BadgeStatusMode('OUTLINE_DECLINE', {
			color: Color.base3,
			icon: icons.OUTLINE_DECLINE,
		});

		getColor()
		{
			return this.getValue().color;
		}

		getIcon()
		{
			const iconColor = this.getColor();

			return this.getValue().icon(iconColor);
		}
	}

	module.exports = { BadgeStatusMode };
});
