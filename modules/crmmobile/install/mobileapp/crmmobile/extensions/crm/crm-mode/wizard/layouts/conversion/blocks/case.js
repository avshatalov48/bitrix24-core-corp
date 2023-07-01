/**
 * @module crm/crm-mode/wizard/layouts/conversion/blocks/case
 */
jn.define('crm/crm-mode/wizard/layouts/conversion/blocks/case', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BooleanField } = require('layout/ui/fields/boolean');

	/**
	 * @class CaseBlock
	 */
	class CaseBlock extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const { moveCase } = props;
			this.state = {
				enable: moveCase,
			};
		}

		render()
		{
			const { onChange } = this.props;
			const { enable } = this.state;

			return BooleanField({
				id: 'casesToDeals',
				value: enable,
				config: {
					description: Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_CONVERSION_CASE_BOOLEAN_DESCRIPTION'),
				},
				showTitle: false,
				readOnly: false,
				onChange: () => {
					this.setState(
						{ enable: !enable },
						() => {
							onChange({ name: 'moveCase', value: !enable });
						},
					);
				},
			});
		}
	}

	module.exports = {
		caseBlock: (props) => new CaseBlock(props),
	};
});
