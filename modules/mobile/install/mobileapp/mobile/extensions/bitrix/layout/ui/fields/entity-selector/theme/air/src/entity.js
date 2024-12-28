/**
 * @module layout/ui/fields/entity-selector/theme/air/src/entity
 */
jn.define('layout/ui/fields/entity-selector/theme/air/src/entity', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
	const { PureComponent } = require('layout/pure-component');
	const { AvatarClass, Avatar } = require('ui-system/blocks/avatar');
	const IMAGE_SIZE = 32;

	/**
	 * @typedef {Object} AirThemeEntityProps
	 * @property {number} [id]
	 * @property {string} [name]
	 * @property {string} [imageUrl]
	 * @property {string} [avatar]
	 */
	class AirThemeEntity extends PureComponent
	{
		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
					testId: this.getTestId(),
				},
				this.renderAvatar(),
				this.renderText(),
			);
		}

		renderAvatar()
		{
			const { entityId, customData } = this.props;

			let avatarParams = this.getAvatarParams();

			if (customData || entityId)
			{
				avatarParams = {
					...avatarParams,
					...AvatarClass.resolveEntitySelectorParams(this.getSelectorEntityParams()),
				};
			}

			return Avatar(avatarParams);
		}

		renderText()
		{
			return View(
				{
					style: {
						flexShrink: 2,
					},
				},
				Text4({
					text: this.getTitle(),
					style: {
						color: Color.base2.toHex(),
						marginLeft: Indent.M.toNumber(),
						flexShrink: 2,
					},
					numberOfLines: 1,
					ellipsize: 'end',
					testId: `${this.getTestId()}_TITLE`,
				}),
			);
		}

		handleOnClick = () => {
			const field = this.getField();
			if (!field.isEmpty())
			{
				field.openEntity(this.getId(), this.props.isCollab, this.props.dialogId);
			}
		};

		getTitle()
		{
			const { title } = this.props;

			return title;
		}

		getId()
		{
			const { id } = this.props;

			return id;
		}

		getField()
		{
			const { field } = this.props;

			return field;
		}

		getTestId()
		{
			return `${this.getField()?.testId}_ENTITY_${this.getId()}`;
		}

		getAvatarSize()
		{
			return IMAGE_SIZE;
		}

		getImage()
		{
			const { imageUrl } = this.props;

			return imageUrl;
		}

		getSelectorEntityParams()
		{
			const {
				id,
				title,
				entityId,
				imageUrl,
				customData,
			} = this.props;

			return {
				id,
				title,
				entityId,
				imageUrl,
				customData,
			};
		}

		/**
		 * @returns AvatarBaseProps
		 */
		getAvatarParams()
		{
			return {
				testId: 'AVATAR_PROJECT',
				withRedux: true,
				name: this.getTitle(),
				id: this.getId(),
				size: this.getAvatarSize(),
				uri: this.getImage(),
				onClick: this.handleOnClick,
			};
		}
	}

	module.exports = {
		AirThemeEntity,
	};
});
