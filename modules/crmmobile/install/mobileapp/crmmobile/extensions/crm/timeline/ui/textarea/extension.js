/**
 * @module crm/timeline/ui/textarea
 */
jn.define('crm/timeline/ui/textarea', (require, exports, module) => {
	const isAndroid = Application.getPlatform() === 'android';

	function Textarea({ ref, text, placeholder, onChange, style, testId })
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
				placeholderTextColor: '#bdc1c6',
				value: text,
				multiline: true,
				style: {
					paddingHorizontal: isAndroid ? 20 : 8,
					paddingVertical: 12,
					fontSize: 18,
					flexGrow: 1,
					height: '100%',
					color: '#333333',
					...style,
				},
				onChangeText: onChange,
			}),
		);
	}

	module.exports = {
		Textarea,
	};
});
