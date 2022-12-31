/**
 * @module layout/ui/islands
 */
jn.define('layout/ui/islands', (require, exports, module) => {

	function Container(...children)
	{
		return View(
			{
				style: {
					backgroundColor: '#eef2f4',
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
					backgroundColor: '#ffffff',
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
				color: '#333333',
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
