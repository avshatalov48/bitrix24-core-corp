/**
 * @module tasks/layout/fields/flow
 */
jn.define('tasks/layout/fields/flow', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { EntitySelectorFieldClass } = require('layout/ui/fields/entity-selector');
	const { EntitySelectorFactoryType } = require('selector/widget/factory');
	const { withCurrentDomain } = require('utils/url');
	const { Icon } = require('assets/icons');

	const DEFAULT_ICON = `/bitrix/mobileapp/tasksmobile/extensions/tasks/layout/fields/flow/images/${AppTheme.id}/flow-icon.png`;

	/**
	 * @class TaskFlowField
	 */
	class TaskFlowField extends EntitySelectorFieldClass
	{
		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				selectorType: EntitySelectorFactoryType.TASK_FLOW,
			};
		}

		getEntityList()
		{
			return super.getEntityList().map((item) => ({
				...item,
				imageUrl: item.imageUrl || DEFAULT_ICON,
			}));
		}

		/**
		 * @public
		 * @return {string}
		 */
		getDefaultAvatar()
		{
			return DEFAULT_ICON;
		}

		getDefaultLeftIcon()
		{
			return Icon.BOTTLENECK;
		}

		getImageUrl(imageUrl)
		{
			return encodeURI(withCurrentDomain(imageUrl));
		}

		canOpenEntity()
		{
			return true;
		}

		openEntity(id)
		{
			void requireLazy('tasks:layout/flow/detail').then(({ FlowDetail }) => {
				FlowDetail.open({
					flowId: id,
					parentLayout: this.getParentWidget(),
				});
			});
		}

		async handleAdditionalFocusActions()
		{
			this.openSelector();
		}
	}

	TaskFlowField.propTypes = {
		...EntitySelectorFieldClass.propTypes,
	};

	TaskFlowField.defaultProps = {
		...EntitySelectorFieldClass.defaultProps,
		showEditIcon: false,
	};

	module.exports = {
		TaskFlowFieldClass: TaskFlowField,
		TaskFlowField: (props) => new TaskFlowField(props),
	};
});
