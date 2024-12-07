/**
 * @module ui-system/blocks/avatar/src/elements/native
 */
jn.define('ui-system/blocks/avatar/src/elements/native', (require, exports, module) => {

	/**
	 * @class AvatarNative
	 */
	class AvatarNative extends LayoutComponent
	{
		return()
		{
			return Avatar(this.props);
		}
	}

	module.exports = {
		/**
		 * @param props
		 */
		AvatarNative: (props) => new AvatarNative(props),
	};
});
