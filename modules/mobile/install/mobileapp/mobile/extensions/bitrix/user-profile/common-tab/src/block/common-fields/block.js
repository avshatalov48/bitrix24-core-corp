/**
 * @module user-profile/common-tab/src/block/common-fields/block
 */
jn.define('user-profile/common-tab/src/block/common-fields/block', (require, exports, module) => {
	const { BaseBlock } = require('user-profile/common-tab/src/block/base-block');
	const { CommonFields } = require('user-profile/common-tab/src/block/common-fields/view');
	const { Type } = require('type');

	class CommonFieldsBlock extends BaseBlock
	{
		prepareProps(commonTabData)
		{
			const {
				isEditMode,
				onChange,
				onFocus,
				commonFields,
				parentWidget,
			} = commonTabData ?? {};

			return {
				testId: 'common-fields-card',
				isEditMode,
				onChange,
				onFocus,
				sections: commonFields,
				parentWidget,
			};
		}

		isAvailable()
		{
			const { sections } = this.props;

			return Type.isArrayFilled(sections);
		}

		getContentClass()
		{
			return CommonFields;
		}

		shouldUseBaseEditWrapper()
		{
			return false;
		}
	}

	module.exports = { CommonFieldsBlock };
});
