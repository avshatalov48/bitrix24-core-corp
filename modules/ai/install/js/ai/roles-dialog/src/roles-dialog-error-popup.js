import { Tag, Loc } from 'main.core';
import { Popup, CloseIconSize } from 'main.popup';
import { Icon, Main } from 'ui.icon-set.api.core';

import './css/roles-dialog-error-popup.css';

export function showRolesDialogErrorPopup()
{
	const popup = new Popup({
		content: renderPopupContent(),
		resizable: false,
		width: 881,
		height: 621,
		padding: 0,
		contentPadding: 0,
		borderRadius: '10px 10px 4px 4px',
		className: 'ai_roles-dialog_popup',
		animation: true,
		cacheable: false,
		autoHide: true,
		closeByEsc: true,
		closeIcon: true,
		closeIconSize: CloseIconSize.LARGE,
	});

	popup.show();
}

function renderPopupContent(): HTMLElement
{
	return Tag.render`
		<div class="ai__roles-dialog_error-popup-inner">
			<div class="ai__roles-dialog_error-popup-content">
				<div class="ai__roles-dialog_error-popup-content-warning-icon">
					${renderWarningIcon()}
				</div>
				<p class="ai__roles-dialog_error-popup-content-error-text">
					${Loc.getMessage('AI_COPILOT_ROLES_ERROR_TEXT')}
				</p>
			</div>
		</div>
	`;
}

function renderWarningIcon(): HTMLElement
{
	const warningIconColor = 'rgba(176, 149, 220, 0.4)';

	const warningIcon = new Icon({
		icon: Main.WARNING,
		size: 56,
		color: warningIconColor,
	});

	return warningIcon.render();
}
