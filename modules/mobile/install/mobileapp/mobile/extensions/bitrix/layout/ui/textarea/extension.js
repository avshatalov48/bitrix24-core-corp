/**
 * @module layout/ui/textarea
 */
jn.define('layout/ui/textarea', (require, exports, module) => {
	const { Color } = require('tokens');
	const isAndroid = Application.getPlatform() === 'android';

	function Textarea({ ref, text, placeholder, onChange, onLinkClick, style, testId })
	{
		return View(
			{
				style: {
					flexGrow: 1,
					flexDirection: 'column',
				},
			},
			TextInput({
				ref,
				testId,
				placeholder,
				placeholderTextColor: Color.base5,
				value: text,
				multiline: true,
				style: {
					paddingHorizontal: isAndroid ? 20 : 8,
					paddingVertical: 12,
					fontSize: 18,
					flexGrow: 1,
					height: '100%',
					color: Color.base1,
					...style,
				},
				onChangeText: onChange,
				onLinkClick,
			}),
		);
	}

	module.exports = { Textarea };
});