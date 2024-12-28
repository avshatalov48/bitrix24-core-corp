/**
 * @module layout/ui/fields/project
 */
jn.define('layout/ui/fields/project', (require, exports, module) => {
	const { EntitySelectorFieldClass } = require('layout/ui/fields/entity-selector');
	const { checkDisabledToolById } = require('settings/disabled-tools');
	const { InfoHelper } = require('layout/ui/info-helper');
	const { Icon } = require('assets/icons');
	const AppTheme = require('apptheme');
	const { dispatch } = require('statemanager/redux/store');
	const { groupsUpsertedFromEntitySelector } = require('tasks/statemanager/redux/slices/groups');
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
					text: this.getEmptyText(),
				}),
			);
		}

		/**
		 * @private
		 * @return {string}
		 */
		getDefaultReadOnlyEmptyValue()
		{
			return BX.message('FIELDS_PROJECT_EMPTY');
		}

		renderEntity(project = {}, showPadding = false)
		{
			const { id, title, imageUrl, isCollab, dialogId } = project;
			const onClick = this.openEntity.bind(this, id, this.getParentWidget(), isCollab, dialogId);

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
					uri: this.getImageUrl(imageUrl || this.getDefaultAvatar()),
					onClick,
				}),
				View(
					{ onClick },
					Text({
						style: this.styles.projectText,
						numberOfLines: 1,
						ellipsize: 'end',
						text: title,
					}),
				),
			);
		}

		/**
		 * @public
		 * @return {string}
		 */
		getDefaultAvatar()
		{
			return DEFAULT_AVATAR;
		}

		getImageUrl(imageUrl)
		{
			if (imageUrl.indexOf(currentDomain) !== 0)
			{
				imageUrl = imageUrl.replace(String(currentDomain), '');
				imageUrl = (imageUrl.indexOf('http') === 0 ? imageUrl : `${currentDomain}${imageUrl}`);
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

		openEntity(projectId, isCollab = false, dialogId = '')
		{
			ProjectViewManager.open(env.userId, projectId, this.getParentWidget(), isCollab, dialogId);
		}

		async handleAdditionalFocusActions()
		{
			const projectsDisabled = await checkDisabledToolById('projects');
			const scrumDisabled = await checkDisabledToolById('scrum');

			if (projectsDisabled && scrumDisabled)
			{
				InfoHelper.openByCode('limit_projects_off');
				this.removeFocus();

				return;
			}

			this.openSelector();
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
					color: AppTheme.colors.accentMainLinks,
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
				defaultAvatar: (color = AppTheme.colors.base4) => {
					return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 23.9989C18.6275 23.9989 24 18.6266 24 11.9995C24 5.37234 18.6275 0 12 0C5.37258 0 0 5.37234 0 11.9995C0 18.6266 5.37258 23.9989 12 23.9989Z" fill="${color}"/><path d="M12.8127 6.23779H5.44922V7.87416H14.4492L12.8127 6.23779Z" fill="white"/><path d="M18.5401 8.69234H5.44922V17.6923H18.5401V8.69234Z" fill="white"/></svg>`;
				},
			};
		}

		getDefaultLeftIcon()
		{
			return Icon.FOLDER_WITH_CARD;
		}

		getAddButtonText()
		{
			return BX.message('FIELDS_PROJECT_ADD_BUTTON_TEXT');
		}

		setReduxState(projectsList)
		{
			dispatch(groupsUpsertedFromEntitySelector(projectsList));
		}
	}

	ProjectField.defaultProps = {
		...EntitySelectorFieldClass.defaultProps,
		showEditIcon: false,
	};

	module.exports = {
		ProjectType: 'project',
		ProjectFieldClass: ProjectField,
		ProjectField: (props) => new ProjectField(props),
	};
});
