/**
 * @module layout/ui/fields/project
 */
jn.define('layout/ui/fields/project', (require, exports, module) => {

	const { EntitySelectorFieldClass } = require('layout/ui/fields/entity-selector');

	const DEFAULT_AVATAR = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/fields/project/images/default-avatar.png';
	const DEFAULT_SELECTOR_AVATAR = '/bitrix/mobileapp/mobile/extensions/bitrix/selector/providers/common/images/project.png';

	/**
	 * @class ProjectField
	 */
	class ProjectField extends EntitySelectorFieldClass
	{
		constructor(props)
		{
			super(props);
			this.state.showAll = false;
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				selectorType: (config.selectorType === '' ? 'project' : config.selectorType),
			};
		}

		renderEmptyContent()
		{
			return this.renderEmptyProject();
		}

		renderEmptyEntity()
		{
			return this.renderEmptyProject();
		}

		renderEmptyProject()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Image({
					style: this.styles.projectImage,
					uri: this.getImageUrl(DEFAULT_AVATAR),
				}),
				Text({
					style: this.styles.emptyEntity,
					numberOfLines: 1,
					ellipsize: 'end',
					text: BX.message('FIELDS_PROJECT_EMPTY'),
				}),
			);
		}

		renderEntity(project = {}, showPadding = false)
		{
			const onClick = this.openEntity.bind(this, project.id);

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingBottom: (showPadding ? 5 : undefined),
					},
				},
				Image({
					style: this.styles.projectImage,
					uri: this.getImageUrl(project.imageUrl || DEFAULT_AVATAR),
					onClick,
				}),
				View(
					{ onClick },
					Text({
						style: this.styles.projectText,
						numberOfLines: 1,
						ellipsize: 'end',
						text: project.title,
					}),
				),
			);
		}

		getImageUrl(imageUrl)
		{
			if (imageUrl.indexOf(currentDomain) !== 0)
			{
				imageUrl = imageUrl.replace(`${currentDomain}`, '');
				imageUrl = (imageUrl.indexOf('http') !== 0 ? `${currentDomain}${imageUrl}` : imageUrl);
			}

			if (imageUrl === (`${currentDomain}${DEFAULT_SELECTOR_AVATAR}`))
			{
				imageUrl = `${currentDomain}${DEFAULT_AVATAR}`;
			}

			return encodeURI(imageUrl);
		}

		canOpenEntity()
		{
			return true;
		}

		openEntity(projectId)
		{
			ProjectViewManager.open(env.userId, projectId, this.getParentWidget());
		}

		shouldShowEditIcon()
		{
			return BX.prop.getBoolean(this.props, 'showEditIcon', false);
		}

		renderLeftIcons()
		{
			if (this.isEmptyEditable())
			{
				return Image(
					{
						style: {
							width: 24,
							height: 24,
							marginRight: 8,
						},
						svg: {
							content: this.getSvgImages().defaultAvatar(this.getTitleColor()),
						},
					},
				);
			}

			return null;
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				entityContent: {
					...styles.entityContent,
					flexDirection: 'column',
					flexWrap: 'no-wrap',
				},
				projectImage: {
					width: 24,
					height: 24,
					borderRadius: 12,
				},
				projectText: {
					color: '#0b66c3',
					fontSize: 16,
					marginLeft: 5,
					flexShrink: 2,
				},
				emptyEntity: {
					...styles.emptyValue,
					marginLeft: 5,
				},
				wrapper: {
					paddingTop: (this.isLeftTitlePosition() ? 10 : 7),
					paddingBottom: (this.hasErrorMessage() ? 5 : 10),
				},
				readOnlyWrapper: {
					paddingTop: (this.isLeftTitlePosition() ? 10 : 7),
					paddingBottom: (this.hasErrorMessage() ? 5 : 10),
				},
			};
		}

		getSvgImages()
		{
			return {
				defaultAvatar: (color = '#a8adb4') => {
					return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 23.9989C18.6275 23.9989 24 18.6266 24 11.9995C24 5.37234 18.6275 0 12 0C5.37258 0 0 5.37234 0 11.9995C0 18.6266 5.37258 23.9989 12 23.9989Z" fill="${color}"/><path d="M12.8127 6.23779H5.44922V7.87416H14.4492L12.8127 6.23779Z" fill="white"/><path d="M18.5401 8.69234H5.44922V17.6923H18.5401V8.69234Z" fill="white"/></svg>`;
				},
			};
		}
	}

	module.exports = {
		ProjectType: 'project',
		ProjectField: (props) => new ProjectField(props),
	};

});