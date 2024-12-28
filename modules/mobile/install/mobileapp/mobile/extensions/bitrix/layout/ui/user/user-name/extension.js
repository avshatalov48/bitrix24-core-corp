/**
 * @module layout/ui/user/user-name
 */
jn.define('layout/ui/user/user-name', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
	const { PureComponent } = require('layout/pure-component');
	const { reduxConnect } = require('layout/ui/user/user-name/src/providers/redux');
	const { SelectorDataProvider } = require('layout/ui/user/user-name/src/providers/selector');
	const { UserType } = require('layout/ui/user/user-name/src/enums/type-enum');

	/**
	 * @typedef UserNameProps
	 * @property {string} testId
	 * @property {number} [id]
	 * @property {string} [name]
	 * @property {Color} [color]
	 * @property {Object} [textElement]
	 * @property {UserType} [entityType]
	 * @property {boolean} [withRedux]
	 *
	 * @class UserName
	 */
	class UserName extends PureComponent
	{
		withRedux()
		{
			const { withRedux } = this.props;

			return Boolean(withRedux);
		}

		render()
		{
			const Text = this.getTextElement();

			if (this.withRedux())
			{
				return this.renderStateConnector(Text);
			}

			return Text({
				...this.props,
				color: this.getColor(),
			});
		}

		renderStateConnector(element)
		{
			return reduxConnect(element)(this.props);
		}

		getTextElement = () => {
			const { textElement } = this.props;

			return textElement || Text4;
		};

		getColor()
		{
			const { color } = this.props;
			const entityType = this.getEntityType();

			return color || UserType.resolve(entityType, UserType.USER).getColor();
		}

		getEntityType()
		{
			const { entityType } = this.props;

			return entityType;
		}

		getId()
		{
			const { id } = this.props;

			return id;
		}
	}

	UserName.defaultProps = {
		withRedux: true,
	};

	UserName.propTypes = {
		textElement: PropTypes.func,
		testId: PropTypes.string.isRequired,
		id: PropTypes.number,
		name: PropTypes.string,
		color: PropTypes.instanceOf(Color),
		entityType: PropTypes.instanceOf(UserType),
		withRedux: PropTypes.bool,
	};

	module.exports = {
		/**
		 * @param {UserNameProps} props
		 */
		UserName: (props) => new UserName(props),
		UserNameClass: UserName,
		UserType,
		SelectorDataProvider,
	};
});
