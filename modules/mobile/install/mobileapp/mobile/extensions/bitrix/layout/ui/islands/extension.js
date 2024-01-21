/**
 * @module layout/ui/islands
 */
jn.define('layout/ui/islands', (require, exports, module) => {
	const AppTheme = require('apptheme');

	function Container(...children)
	{
		return View(
			{
				style: {
					backgroundColor: AppTheme.colors.bgPrimary,
				},
				resizableByKeyboard: true,
			},
			ScrollView(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
					},
				},
				View(
					{},
					...children,
				),
			),
		);
	}

	function Island(...children)
	{
		return View(
			{
				style: {
					padding: 16,
					paddingTop: 0,
					backgroundColor: AppTheme.colors.bgContentPrimary,
					borderRadius: 12,
					marginBottom: 12,
				},
			},
			...children,
		);
	}

	function Title(text)
	{
		return Text({
			style: {
				color: AppTheme.colors.base1,
				fontWeight: 'bold',
				fontSize: 16,
				width: '100%',
				textAlign: 'left',
				paddingTop: 0,
				paddingBottom: 0,
				marginTop: 16,
				marginBottom: 8,
			},
			text: String(text),
		});
	}

	function FormGroup(...fields)
	{
		return FieldsWrapper({ fields });
	}

	module.exports = { Container, Island, Title, FormGroup };
});
