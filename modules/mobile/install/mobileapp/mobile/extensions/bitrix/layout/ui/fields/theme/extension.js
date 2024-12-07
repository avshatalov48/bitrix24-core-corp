/**
 * @module layout/ui/fields/theme
 */
jn.define('layout/ui/fields/theme', (require, exports, module) => {
	/**
	 * @type {function(Class, function): function(props: object): object}
	 */
	const withTheme = (FieldComponentClass, ThemeComponent) => {
		return (props) => FieldComponentClass && new FieldComponentClass({ ...props, ThemeComponent });
	};

	module.exports = {
		withTheme,
	};
});
