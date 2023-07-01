import {ajax as Ajax, Tag, Reflection, Loc} from "main.core";
import {EventEmitter} from "main.core.events";
import {MessageBox, MessageBoxButtons} from "ui.dialogs.messagebox";

export default class PromoPopup {
	userBoxNode: HTMLElement = null;

	static shouldBlockViewAndEdit(): boolean
	{
		if (!BX.Disk.isAvailableOnlyOffice())
		{
			return false;
		}
		if (BX.message['disk_onlyoffice_can_view'] === undefined)
		{
			return false;
		}

		return BX.message['disk_onlyoffice_can_view'] == false;
	}

	static shouldShowViewPromo(): boolean
	{
		if (!BX.Disk.isAvailableOnlyOffice())
		{
			return false;
		}
		if (BX.message['disk_onlyoffice_got_promo_about'] === undefined)
		{
			return false;
		}

		return BX.message['disk_onlyoffice_got_promo_about'] == false;
	}

	static shouldShowEndDemo(): boolean
	{
		if (!BX.Disk.isAvailableOnlyOffice())
		{
			return false;
		}
		if (BX.message['disk_onlyoffice_demo_ended'] == false)
		{
			return false;
		}
		if (BX.message['disk_onlyoffice_got_end_demo'] === undefined)
		{
			return false;
		}

		return BX.message['disk_onlyoffice_got_end_demo'] == false;
	}

	static shouldShowEditPromo(): boolean
	{
		if (!BX.Disk.isAvailableOnlyOffice())
		{
			return false;
		}
		if (BX.message['disk_onlyoffice_can_edit'] === undefined)
		{
			return false;
		}

		return BX.message['disk_onlyoffice_can_edit'] == false;
	}

	static canEdit(): boolean
	{
		if (!BX.Disk.isAvailableOnlyOffice())
		{
			return false;
		}
		if (BX.message['disk_onlyoffice_can_edit'] === undefined)
		{
			return false;
		}

		return BX.message['disk_onlyoffice_can_edit'] == true;
	}

	static registerView(optionName: string): void
	{
		BX.userOptions.save('disk', optionName, 'v', 1);
		BX.userOptions.send(null);

		if (optionName === 'got_promo_onlyoffice')
		{
			BX.message['disk_onlyoffice_got_promo_about'] = true;
		}
		else if (optionName === 'got_end_demo_onlyoffice')
		{
			BX.message['disk_onlyoffice_got_end_demo'] = true;
		}
	}

	static showCommonPromoForNonPaid(): void
	{
		if (this.shouldShowEndDemo())
		{
			this.showEndOfDemo();
		}
		else if (Reflection.getClass('BX.UI.InfoHelper'))
		{
			BX.UI.InfoHelper.show('limit_office_no_document', {featureId: 'disk_onlyoffice_edit'});
			EventEmitter.subscribeOnce('BX.UI.InfoHelper:onActivateTrialFeatureSuccess', () => {
				Ajax.runAction('disk.api.onlyoffice.handleTrialFeatureActivation', {});
			});
		}
	}

	static showEditPromo(): void
	{
		if (this.shouldShowEndDemo())
		{
			this.showEndOfDemo();
		}
		else if (Reflection.getClass('BX.UI.InfoHelper'))
		{
			BX.UI.InfoHelper.show('limit_office_small_documents', {featureId: 'disk_onlyoffice_edit'} );
		}
	}

	static showViewPromo(): void
	{
		const firstRow = this.canEdit() ? Loc.getMessage('JS_DISK_ONLYOFFICE_PROMO_EDIT_POPUP_1') : Loc.getMessage('JS_DISK_ONLYOFFICE_PROMO_VIEW_POPUP_1');
		const content = Tag.render`
			<div>
				<div>${firstRow}</div>
				<div style="padding-top: 15px;">${Loc.getMessage('JS_DISK_ONLYOFFICE_PROMO_VIEW_POPUP_2')}</div>
				<div style="padding-top: 15px;">${Loc.getMessage('JS_DISK_ONLYOFFICE_PROMO_VIEW_POPUP_3')}</div>
			</div>
		`;

		MessageBox.show({
			title: this.canEdit()? Loc.getMessage('JS_DISK_ONLYOFFICE_PROMO_EDIT_POPUP_TITLE') : Loc.getMessage('JS_DISK_ONLYOFFICE_PROMO_VIEW_POPUP_TITLE_MSGVER_1'),
			message: content,
			modal: true,
			buttons: MessageBoxButtons.OK,
			okCaption: Loc.getMessage('DISK_JS_BTN_CLOSE'),
			popupOptions: {
				events: {
					onPopupShow: () => {
						this.registerView('got_promo_onlyoffice');
					}
				}
			}
		});
	}

	static showEndOfDemo(): void
	{
		const content = Tag.render`
			<div>
				<div>${Loc.getMessage('JS_DISK_ONLYOFFICE_END_DEMO_POPUP_1')}</div>
				<ul>
					<li>${Loc.getMessage('JS_DISK_ONLYOFFICE_END_DEMO_POPUP_LIST_1')}</li>
					<li>${Loc.getMessage('JS_DISK_ONLYOFFICE_END_DEMO_POPUP_LIST_2')}</li>
					<li>${Loc.getMessage('JS_DISK_ONLYOFFICE_END_DEMO_POPUP_LIST_3')}</li>
				</ul>
				<div>${Loc.getMessage('JS_DISK_ONLYOFFICE_END_DEMO_POPUP_NOTICE')}</div>
			</div>
		`;

		MessageBox.show({
			title: Loc.getMessage('JS_DISK_ONLYOFFICE_END_DEMO_POPUP_TITLE'),
			message: content,
			modal: true,
			buttons: MessageBoxButtons.OK_CANCEL,
			okCaption: Loc.getMessage('JS_DISK_ONLYOFFICE_END_DEMO_POPUP_CONTINUE_WORK_WITH_DOCS'),
			onOk: () => {
				this.showEditPromo();

				return true;
			},
			cancelCaption: Loc.getMessage('JS_DISK_ONLYOFFICE_END_DEMO_POPUP_SETUP_WORK'),
			onCancel: () => {
				BX.Disk.InformationPopups.openWindowForSelectDocumentService({});

				return true;
			},
			popupOptions: {
				events: {
					onPopupShow: () => {
						this.registerView('got_end_demo_onlyoffice');

						BX.message.disk_document_service = null;
						Ajax.runAction('disk.api.onlyoffice.handleEndOfTrialFeature', {});
					}
				}
			}
		});
	}
};