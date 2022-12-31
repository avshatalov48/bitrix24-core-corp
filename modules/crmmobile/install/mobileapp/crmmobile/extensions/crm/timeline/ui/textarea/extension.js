/**
 * @module crm/timeline/ui/textarea
 */
jn.define('crm/timeline/ui/textarea', (require, exports, module) => {

	const isAndroid = Application.getPlatform() === 'android';

	function Textarea({ ref, text, placeholder, onChange })
	{
		return View(
			{
				style: {
					flexGrow: 1,
					flexDirection: 'column',
				}
			},
			TextInput({
				ref,
				placeholder,
				value: text,
				multiline: true,
				style: {
					paddingHorizontal: isAndroid ? 20 : 8,
					paddingVertical: 12,
					fontSize: 18,
					flexGrow: 1,
					maxHeight: 155,
				},
				onChangeText: onChange,
			}),
		);
	}

	module.exports = {
		Textarea,
	};

});