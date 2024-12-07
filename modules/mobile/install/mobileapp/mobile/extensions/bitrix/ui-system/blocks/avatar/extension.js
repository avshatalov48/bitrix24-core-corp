/**
 * @module ui-system/blocks/avatar
 */
jn.define('ui-system/blocks/avatar', (require, exports, module) => {
	const { Feature } = require('feature');
	const { PureComponent } = require('layout/pure-component');
	const { makeLibraryImagePath } = require('asset-manager');
	const { AvatarNative } = require('ui-system/blocks/avatar/src/elements/native');
	const { AvatarEntityType } = require('ui-system/blocks/avatar/src/enums/entity-type-enum');
	const { AvatarView, AvatarViewClass, AvatarAccentGradient, AvatarShape } = require(
		'ui-system/blocks/avatar/src/elements/base');

	/**
	 * @param {AvatarProps} props
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

		getEmptyAvatar(entityEmptyAvatar)
		{
			const { emptyAvatar } = this.props;

			return makeLibraryImagePath(entityEmptyAvatar || emptyAvatar, 'empty-avatar');
		}

		getEntityParams()
		{
			const { type, entityType } = this.props;

			return AvatarEntityType.resolveType(type || entityType, AvatarEntityType.USER).getValue();
		}

		render()
		{
			const props = this.getAvatarProps();

			// if (Feature.isNativeAvatarSupported())
			// {
			// 	return AvatarNative(props);
			// }

			return AvatarView(props);
		}

		getAvatarProps()
		{
			const entityParams = this.getEntityParams();

			return {
				...entityParams,
				...this.props,
				emptyAvatar: this.getEmptyAvatar(entityParams?.emptyAvatar),
			};
		}
	}

	Avatar.defaultProps = AvatarViewClass.defaultProps;
	Avatar.propTypes = AvatarViewClass.propTypes;

	module.exports = {
		/**
		 * @param {AvatarViewProps} props
		 */
		Avatar: (props) => new Avatar(props),
		AvatarClass: Avatar,
		AvatarShape,
		AvatarEntityType,
		AvatarAccentGradient,
	};
});
