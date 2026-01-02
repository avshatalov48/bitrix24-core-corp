/**
 * @module intranet/create-department/src/permissions-box
 */

jn.define('intranet/create-department/src/permissions-box', (require, exports, module) => {
	const { Loc } = require('loc');
	const { StatusBox } = require('layout/ui/status-box');
	const { makeLibraryImagePath } = require('asset-manager');
	const { ButtonDesign } = require('ui-system/form/buttons/button');

	/**
	 * @param {Object} props
	 * @param {Object} [props.parentWidget]
	 * @param {boolean} [props.subdepartmentCreationCase=false]
	 * @param {function} [props.onClose]
	 */
	const showAccessRestrictionBox = (props) => {
		const {
			parentWidget,
			onClose,
			subdepartmentCreationCase = false,
		} = props;
		const imageUri = makeLibraryImagePath('no-create-permissions.svg', 'create-department', 'intranet');

		StatusBox.open({
			backdropTitle: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_NO_PERMISSIONS_BOX_TITLE'),
			testId: 'create-department-no-permissions-box',
			imageUri,
			parentWidget,
			title: subdepartmentCreationCase
				? Loc.getMessage('M_INTRANET_CREATE_SUBDEPARTMENT_NO_PERMISSIONS_TITLE')
				: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_NO_PERMISSIONS_TITLE'),
			description: subdepartmentCreationCase
				? Loc.getMessage('M_INTRANET_CREATE_SUBDEPARTMENT_NO_PERMISSIONS_DESCRIPTION')
				: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_NO_PERMISSIONS_DESCRIPTION'),
			buttonText: Loc.getMessage('M_INTRANET_CREATE_DEPARTMENT_NO_PERMISSIONS_BUTTON_TEXT'),
			buttonDesign: ButtonDesign.OUTLINE,
			onClose,
		});
	};

	module.exports = { showAccessRestrictionBox };
});
