/**
 * @module ui-system/blocks/avatar
 */
jn.define('ui-system/blocks/avatar', (require, exports, module) => {
	const { Feature } = require('feature');
	const { PureComponent } = require('layout/pure-component');
	const { makeLibraryImagePath } = require('asset-manager');
	const { AvatarNative } = require('ui-system/blocks/avatar/src/elements/native');
	const { reduxConnect } = require('ui-system/blocks/avatar/src/providers/redux');
	const {
		selectorDataProvider,
		SelectorDataProviderClass,
	} = require('ui-system/blocks/avatar/src/providers/selector');
	const { AvatarAccentGradient } = require('ui-system/blocks/avatar/src/enums/accent-gradient-enum');
	const { AvatarShape } = require('ui-system/blocks/avatar/src/enums/shape-enum');
	const { AvatarElementType } = require('ui-system/blocks/avatar/src/enums/element-type-enum');
	const { AvatarEntityType } = require('ui-system/blocks/avatar/src/enums/entity-type-enum');
	const { AvatarView, AvatarViewClass } = require('ui-system/blocks/avatar/src/elements/layout');

	/**
	 * @class Avatar
	 */
	class Avatar extends PureComponent
	{
		/**
		 * @param {boolean} rounded
		 * @param {number} size
		 * @returns number
		 */
		static resolveBorderRadius(rounded, size)
		{
			return AvatarViewClass.resolveBorderRadius(rounded, size);
		}

		/**
		 * @param {SelectorParams} params
		 */
		static resolveEntitySelectorParams(params)
		{
			const {
				onUriLoadFailure,
				onAvatarClick,
				...restParams
			} = selectorDataProvider(params);

			return restParams;
		}

		/**
		 * @returns {boolean}
		 */
		static isNativeSupported()
		{
			return Feature.isNativeAvatarSupported();
		}

		static getEmptyAvatar(emptyAvatar)
		{
			return makeLibraryImagePath(emptyAvatar, 'empty-avatar');
		}

		/**
		 * @param {AvatarBaseProps} params
		 */
		static getAvatarProps(params)
		{
			const {
				entityType,
				emptyAvatar: paramsEmptyAvatar,
				...restParams
			} = params;

			const {
				placeholder,
				...restEntityParams
			} = AvatarEntityType.resolveType(entityType).getValue();

			const emptyAvatar = Avatar.getEmptyAvatar(paramsEmptyAvatar || placeholder.emptyAvatar);

			return {
				...restEntityParams,
				...restParams,
				placeholder: {
					...placeholder,
					emptyAvatar,
				},
			};
		}

		/**
		 * @param {AvatarBaseProps} props
		 * @returns {AvatarNative|AvatarView}
		 */
		static getAvatar = (props) => {
			const { elementType, ...restProps } = props;
			/**
			 * @type {AvatarViewProps|AvatarBaseProps}
			 */
			const avatarProps = Avatar.getAvatarProps(restProps);
			const avatarType = elementType
				? AvatarElementType.resolve(elementType, AvatarElementType.NATIVE).getValue()
				: null;

			switch (avatarType)
			{
				case AvatarElementType.LAYOUT.getValue():
					return AvatarView(avatarProps);
				case AvatarElementType.NATIVE.getValue():
					return AvatarNative(avatarProps);
				default:
					return Avatar.isNativeSupported()
						? AvatarNative(avatarProps)
						: AvatarView(avatarProps);
			}
		};

		getStateConnector()
		{
			return reduxConnect;
		}

		render()
		{
			if (this.withRedux())
			{
				const stateConnector = this.getStateConnector();

				return stateConnector(Avatar.getAvatar)(this.props);
			}

			return Avatar.getAvatar(this.props);
		}

		withRedux()
		{
			const { withRedux } = this.props;

			return Boolean(withRedux);
		}
	}

	Avatar.defaultProps = AvatarViewClass.defaultProps;
	Avatar.propTypes = {
		elementType: PropTypes.instanceOf(AvatarElementType),
		...AvatarViewClass.propTypes,
	};

	module.exports = {
		/**
		 * @param {AvatarBaseProps} props
		 */
		Avatar: (props) => new Avatar(props),
		AvatarClass: Avatar,
		AvatarElementType,
		AvatarShape,
		AvatarEntityType,
		AvatarAccentGradient,
		SelectorDataProviderClass,
	};
});
