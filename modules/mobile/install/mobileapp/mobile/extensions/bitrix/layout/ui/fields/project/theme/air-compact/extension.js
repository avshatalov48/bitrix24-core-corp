/**
 * @module layout/ui/fields/project/theme/air-compact
 */
jn.define('layout/ui/fields/project/theme/air-compact', (require, exports, module) => {
	const { isEmpty } = require('utils/object');
	const { useCallback } = require('utils/function');
	const { ProjectAvatar, ProjectAvatarClass } = require('tasks/ui/avatars/project-avatar');
	const { ProjectFieldClass } = require('layout/ui/fields/project');
	const { AirCompactThemeView } = require('layout/ui/fields/base/theme/air-compact');

	const AVATAR_SIZE = 20;

	/** @type {function(object): object} */
	const ProjectField = (props) => {
		const AirCompactThemeWrapper = useCallback(({ field }) => {
			const value = field.getValue();
			const count = (field.isEmpty() || !Array.isArray(value)) ? 0 : value.length;
			const { textMultiple = '' } = field.getConfig();
			const onClick = field.getContentClickHandler();
			const entityList = field.getEntityList();
			let avatarParams = null;

			if (!isEmpty(entityList))
			{
				const entityParams = entityList[0];

				avatarParams = {
					testId: 'AVATAR_PROJECT',
					withRedux: true,
					name: entityParams.title,
					id: entityParams.id,
					size: AVATAR_SIZE,
					uri: entityParams.imageUrl,
					onClick,
				};

				if (entityParams.customData || entityParams.entityId)
				{
					avatarParams = {
						...avatarParams,
						...ProjectAvatarClass.resolveEntitySelectorParams({
							...entityParams,
							withRedux: true,
						}),
					};
				}
			}

			const avatar = avatarParams ? ProjectAvatar(avatarParams) : null;

			return AirCompactThemeView({
				count,
				avatar,
				onClick,
				textMultiple,
				testId: field.testId,
				empty: field.isEmpty(),
				readOnly: field.isReadOnly(),
				leftIcon: field.getLeftIcon(),
				hasError: field.hasErrorMessage(),
				multiple: field.isMultiple(),
				isRestricted: field.isRestricted(),
				defaultLeftIcon: field.getDefaultLeftIcon(),
				text: field.isMultiple() ? field.getTitleText() : field.getDisplayedValue(),
				wideMode: Boolean(field.props.wideMode),
				colorScheme: field.props.colorScheme,
				showLoader: field.props.showLoader,
				bindContainerRef: field.bindContainerRef,
			});
		});

		return new ProjectFieldClass({ ...props, ThemeComponent: AirCompactThemeWrapper });
	};

	module.exports = {
		ProjectField,
	};
});
