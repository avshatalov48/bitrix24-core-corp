/**
 * @module user-profile/common-tab/src/block/about-me/block
 */
jn.define('user-profile/common-tab/src/block/about-me/block', (require, exports, module) => {
	const { BaseBlock } = require('user-profile/common-tab/src/block/base-block');
	const { AboutMe } = require('user-profile/common-tab/src/block/about-me/view');
	const { Loc } = require('loc');

	class AboutMeBlock extends BaseBlock
	{
		prepareProps(commonTabData)
		{
			const {
				aboutMe = {
					text: 'some temporary text about me',
					files: [],
				},
				isEditMode,
				onChange,
				parentWidget,
			} = commonTabData ?? {};

			return {
				aboutMe,
				isEditMode,
				onChange,
				parentWidget,
				testId: 'about-me-card',
			};
		}

		isAvailable()
		{
			// todo: uncomment after intranet api will be finished
			/* const { aboutMe, isEditMode } = this.props;

			return isEditMode || aboutMe.text?.trim() !== ''; */

			return false;
		}

		getTitle()
		{
			if (this.props.isEditMode)
			{
				return Loc.getMessage('M_PROFILE_ABOUT_ME_EDIT_MODE_TITLE');
			}

			return Loc.getMessage('M_PROFILE_ABOUT_ME_TITLE');
		}

		getContentClass()
		{
			return AboutMe;
		}
	}

	module.exports = { AboutMeBlock };
});
