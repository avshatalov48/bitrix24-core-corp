/**
 * @module tasks/layout/checklist/preview/src/stub
 */
jn.define('tasks/layout/checklist/preview/src/stub', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PropTypes } = require('utils/validation');
	const AppTheme = require('apptheme');
	const { CheckBox } = require('layout/ui/checkbox');

	/**
	 * @function checklistPreviewStub
	 */
	const checklistPreviewStub = (props) => {
		const { content = null, margin = false, isLoading = false, onClick = null } = props;

		const defaultTemplate = View(
			{
				style: {
					flexDirection: 'row',
				},
			},
			new CheckBox({
				checked: true,
				isDisabled: true,
				style: {
					backgroundColor: AppTheme.colors.accentBrandBlue,
					opacity: 1,
				},
			}),
			Text({
				text: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_STUB_CREATE_CHECKLIST'),
				style: {
					marginLeft: 6,
					fontSize: 16,
					fontWeight: '400',
					color: AppTheme.colors.base4,
				},
			}),
		);

		const templates = Array.isArray(content) ? content : [content || defaultTemplate];

		return isLoading
			? Loader({
				style: {
					position: 'absolute',
					left: '50%',
					alignSelf: 'center',
					width: 30,
					height: 30,
				},
				tintColor: AppTheme.colors.base3,
				animating: true,
				size: 'small',
			})
			: View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						minHeight: 52,
						width: '100%',
						background: AppTheme.colors.bgContentPrimary,
						borderRadius: 6,
						borderWidth: 1,
						borderColor: AppTheme.colors.bgSeparatorSecondary,
						marginTop: margin ? 8 : 0,
						paddingHorizontal: 8,
					},
					onClick: () => {
						if (onClick)
						{
							onClick();
						}
					},
				},
				...templates,
			);
	};

	checklistPreviewStub.propTypes = {
		content: PropTypes.oneOfType([
			PropTypes.object,
			PropTypes.arrayOf(PropTypes.object),
		]),
		margin: PropTypes.bool,
		isLoading: PropTypes.bool,
		onClick: PropTypes.func,
	};

	module.exports = { checklistPreviewStub };
});
