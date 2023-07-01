/**
 * @module layout/ui/fields/client/elements/title
 */
jn.define('layout/ui/fields/client/elements/title', (require, exports, module) => {

	const { Loc } = require('loc');

	/**
	 * @function ClientItemTitle
	 */
	function ClientItemTitle(props)
	{
		const { id, hidden, title, type, onOpenBackdrop, testId } = props;

		const onClick = () => {
			if (id && !hidden && onOpenBackdrop)
			{
				onOpenBackdrop();
			}
		};

		return View({
				style: {
					flexDirection: 'row',
					alignItems: 'flex-start',
					justifyContent: 'flex-start',
					flexShrink: 2,
				},
			},
			View(
				{
					style: {
						flexShrink: 2,
						marginRight: 8,
					},
					onClick,
				},
				Text({
						testId: `${testId}-name`,
						style: {
							color: !id || hidden ? '#333333' : '#0b66c3',
							fontSize: 18,
						},
						numberOfLines: 1,
						ellipsize: 'end',
						text: title,
					},
				),
			),
			type && View(
				{
					style: {
						flexShrink: 0,
						height: 18,
						borderColor: '#2fc6f6',
						borderRadius: 12,
						borderWidth: 1,
						paddingHorizontal: 9,
						marginTop: 3,
						justifyContent: 'center',
					},
					onClick,
				},
				Text({
					testId: `${testId}-type`,
					style: {
						fontSize: 10,
						marginBottom: Application.getPlatform() === 'android' ? 3 : 2,
					},
					text: Loc.getMessage(`FIELDS_CLIENT_TITLE_${type.toUpperCase()}_MSGVER_1`),
				}),
			),
		);
	}

	module.exports = { ClientItemTitle };
});