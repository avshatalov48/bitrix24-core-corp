/**
 * @module ui-system/blocks/avatar
 */
jn.define('ui-system/blocks/avatar', (require, exports, module) => {
	const { Color } = require('tokens');
	const { PropTypes } = require('utils/validation');
	const { PureComponent } = require('layout/pure-component');
	const { reduxConnect } = require('ui-system/blocks/avatar/src/providers/redux');
	const { AvatarView } = require('ui-system/blocks/avatar/src/elements/avatar-view');
	const {
		selectorDataProvider,
		SelectorDataProviderClass,
	} = require('ui-system/blocks/avatar/src/providers/selector');
	const { AvatarShape } = require('ui-system/blocks/avatar/src/enums/shape');
	const { AvatarEntityType } = require('ui-system/blocks/avatar/src/enums/entity-type');
	const { AvatarAccentGradient } = require('ui-system/blocks/avatar/src/enums/accent-gradient');

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
			return AvatarView.resolveBorderRadius(rounded, size);
		}

		/**
		 * @param {SelectorParams} params
		 *
		 * @return {Omit<*, 'onUriLoadFailure'|'onAvatarClick'>}
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

		render()
		{
			if (this.withRedux())
			{
				return reduxConnect(Avatar.getAvatar)(this.props);
			}

			return Avatar.getAvatar(this.props);
		}

		/**
		 * @param {Omit<*, 'onUriLoadFailure'|'onAvatarClick'>} props
		 * @returns {AvatarView|AvatarView}
		 */
		static getAvatar = (props) => {
			const {
				entityType,
				emptyAvatar: paramsEmptyAvatar,
				...restParams
			} = props;

			const {
				placeholder,
				...restEntityParams
			} = AvatarEntityType.resolveType(entityType).getValue();

			/**
			 * @type {AvatarView}
			 */
			return new AvatarView({
				...restEntityParams,
				...restParams,
				placeholder: {
					...placeholder,
					emptyAvatar: paramsEmptyAvatar || placeholder.emptyAvatar,
				},
			});
		};

		withRedux()
		{
			const { withRedux } = this.props;

			return Boolean(withRedux);
		}
	}

	AvatarView.defaultProps = {
		size: 32,
		icon: null,
		outline: null,
		withRedux: true,
		useLetterImage: true,
		backBorderWidth: null,
	};

	AvatarView.propTypes = {
		forwardRef: PropTypes.func,
		testId: PropTypes.string.isRequired,
		outline: PropTypes.number,
		id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		size: PropTypes.number,
		name: PropTypes.string,
		emptyAvatar: PropTypes.string,
		uri: PropTypes.string,
		shape: PropTypes.instanceOf(AvatarShape),
		entityType: PropTypes.instanceOf(AvatarEntityType),
		accent: PropTypes.bool,
		icon: PropTypes.object,
		accentGradient: PropTypes.instanceOf(AvatarAccentGradient),
		backgroundColor: PropTypes.instanceOf(Color),
		accentGradientColors: PropTypes.arrayOf(PropTypes.string),
		withRedux: PropTypes.bool,
		useLetterImage: PropTypes.bool,
		style: PropTypes.object,
		onClick: PropTypes.func,
	};

	module.exports = {
		/**
		 * @param {AvatarViewProps} props
		 */
		Avatar: (props) => new Avatar(props),
		AvatarClass: Avatar,
		AvatarShape,
		AvatarEntityType,
		AvatarAccentGradient,
		SelectorDataProviderClass,
	};
});
