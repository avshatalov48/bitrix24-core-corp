/**
 * @module layout/ui/fields/user/theme/air-compact
 */
jn.define('layout/ui/fields/user/theme/air-compact', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { AvatarStack } = require('ui-system/blocks/avatar-stack');
	const { withTheme } = require('layout/ui/fields/theme');
	const { UserName } = require('layout/ui/user/user-name');
	const { UserFieldClass } = require('layout/ui/fields/user');
	const { AirCompactThemeViewClass } = require('layout/ui/fields/base/theme/air-compact');

	const IMAGE_SIZE = 20;

	/**
	 * @class AirUserCompactThemeWrapper
	 */
	class AirUserCompactThemeWrapper extends AirCompactThemeViewClass
	{
		getContent()
		{
			const entityList = this.getEntityList();

			if (entityList.length > 1)
			{
				return this.getAvatarStack();
			}

			if (entityList.length === 1)
			{
				return this.getUserName();
			}

			return null;
		}

		getText()
		{
			const field = this.getField();

			return field.isEmpty() ? field.getTitleText() : null;
		}

		getAvatar()
		{
			const entityList = this.getEntityList();

			if (entityList.length !== 1)
			{
				return null;
			}

			return Avatar({
				testId: this.getTestId('compact-avatar'),
				size: IMAGE_SIZE,
				id: entityList?.[0].id,
				onClick: this.handleOnClick,
			});
		}

		getAvatarStack()
		{
			const entities = this.getEntityList().map(({ id }) => id);

			if (entities.length <= 1)
			{
				return null;
			}

			return AvatarStack({
				testId: this.getTestId('compact-avatar'),
				entities,
				size: IMAGE_SIZE,
				visibleEntityCount: 3,
				onClick: this.handleOnClick,
			});
		}

		getUserName()
		{
			const entityList = this.getEntityList();

			if (entityList.length !== 1)
			{
				return '';
			}

			const { title, id } = entityList[0];

			return UserName({
				id,
				testId: this.getTestId(`compact-user-${id}-title`),
				text: title,
				color: this.getDesignSchemeColor(),
				style: {
					marginLeft: Indent.XS.toNumber(),
					flexShrink: 2,
				},
				ellipsize: 'end',
				numberOfLines: 1,
			});
		}

		getLeftIcon()
		{
			const field = this.getField();
			const leftIcon = field.getLeftIcon();
			const defaultLeftIcon = field.getDefaultLeftIcon();

			if (leftIcon.icon)
			{
				return leftIcon.icon;
			}

			return defaultLeftIcon;
		}

		/**
		 * @return {UserField}
		 */
		getField()
		{
			const { field } = this.props;

			return field;
		}

		getEntityList()
		{
			return this.getField()?.getEntityList() || [];
		}

		getTestId(suffix)
		{
			return [this.getField()?.testId, suffix].filter(Boolean).join('-');
		}

		hasError()
		{
			return this.getField().hasErrorMessage();
		}

		isEmpty()
		{
			return this.getField().isEmpty();
		}

		isReadOnly()
		{
			return this.getField().isReadOnly();
		}

		handleOnClick()
		{
			const contentClick = this.getField()?.getContentClickHandler();

			contentClick?.();
		}

		getStyle()
		{
			return {
				maxWidth: 250,
			};
		}
	}

	module.exports = {
		UserField: withTheme(UserFieldClass, (props) => new AirUserCompactThemeWrapper(props)),
	};
});
