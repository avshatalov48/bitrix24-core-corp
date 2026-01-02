import { LocalEmailPage } from './page/local-email-page';
import { Selector } from './selector';
import { EmailPage } from './page/email-page';
import { ExtranetPage } from './page/extranet-page';
import { GroupPage } from './page/group-page';
import { IntegratorPage } from './page/integrator-page';
import { LinkPage } from './page/link-page';
import { MassPage } from './page/mass-page';
import { RegisterPage } from './page/register-page';
import { SuccessPage } from './page/success-page';

export class PageFactory
{
	createLocalEmailPage(): LocalEmailPage
	{
		return new LocalEmailPage({
			departmentControl: this.createDepartmentControl(),
			transport: this.transport,
			linkRegisterEnabled: this.isSelfRegisterEnabled,
			analytics: this.analytics,
		});
	}

	createEmailPage(): EmailPage
	{
		return new EmailPage({
			departmentControl: this.createDepartmentControl(),
			inputsFactory: this.createInputRowFactory(this.useLocalEmailProgram),
			smsAvailable: this.isInvitationBySmsAvailable,
			useLocalEmailProgram: this.useLocalEmailProgram,
			onClickSwitchMode: () => {
				this.changeContent('mass-invite');
			},
			onClickAddInputRow: () => {
				this.getSetupArrow();
				this.updateArrow();
			},
		});
	}

	createGroupPage(): GroupPage
	{
		const projectId = this.userOptions?.groupId
			? parseInt(this.userOptions.groupId, 10)
			: 0;

		const intranetSelector = new Selector({
			options: {
				project: true,
				projectId,
				isAdmin: this.isAdmin,
				projectLimitExceeded: this.projectLimitExceeded,
				projectLimitFeatureId: this.projectLimitFeatureId,
			},
		});

		return new GroupPage({
			departmentControl: this.createDepartmentControl(),
			inputsFactory: this.createInputRowFactory(),
			tagSelectorGroup: intranetSelector.renderTagSelector(),
			onClickAddInputRow: () => {
				this.getSetupArrow();
				this.updateArrow();
			},
		});
	}

	createExtranetPage(): ExtranetPage
	{
		const projectId = this.userOptions?.groupId
			? parseInt(this.userOptions.groupId, 10)
			: 0;

		const selectorParams = {
			options: {
				project: 'extranet',
				projectId,
				isAdmin: this.isAdmin,
				projectLimitExceeded: this.projectLimitExceeded,
				projectLimitFeatureId: this.projectLimitFeatureId,
				showCreateButton: !this.isCollabEnabled,
			},
		};

		return new ExtranetPage({
			inputsFactory: this.createInputRowFactory(),
			tagSelectorGroup: (new Selector(selectorParams)).renderTagSelector(),
			onClickAddInputRow: () => {
				this.getSetupArrow();
				this.updateArrow();
			},
		});
	}

	createRegisterPage(): RegisterPage
	{
		const projectId = this.userOptions?.groupId
			? parseInt(this.userOptions.groupId, 10)
			: 0;

		const intranetSelector = new Selector({
			options: {
				project: true,
				projectId,
				isAdmin: this.isAdmin,
				projectLimitExceeded: this.projectLimitExceeded,
				projectLimitFeatureId: this.projectLimitFeatureId,
			},
		});

		return new RegisterPage({
			departmentControl: this.createDepartmentControl(),
			isCloud: this.isCloud,
			inputsFactory: this.createInputRowFactory(),
			withoutConfirm: !this.registerNeedeConfirm,
			tagSelectorGroup: intranetSelector.renderTagSelector(),
		});
	}

	createIntegratorPage(): IntegratorPage
	{
		return new IntegratorPage();
	}

	createLinkPage(): LinkPage
	{
		return new LinkPage({
			departmentControl: this.createDepartmentControl(),
			isAdmin: this.isAdmin,
			fastRegistrationAvailable: this.isSelfRegisterEnabled,
			needConfirmRegistration: this.registerNeedeConfirm,
			wishlist: this.wishlistValue,
			isCloud: this.isCloud,
			linkRegisterEnabled: this.isSelfRegisterEnabled,
			analytics: this.analytics,
			transport: this.transport,
		});
	}

	createMassPage(): MassPage
	{
		return new MassPage({
			departmentControl: this.createDepartmentControl(),
			smsAvailable: this.isInvitationBySmsAvailable,
			useOnlyPhone: this.useLocalEmailProgram,
			onClickSwitchMode: () => {
				this.changeContent('invite');
			},
		});
	}

	createSeccessPage(): SuccessPage
	{
		return new SuccessPage();
	}
}
