import {Reflection} from "main.core";
import {BaseButton, ButtonManager} from "ui.buttons";

const namespace = Reflection.namespace('BX.Crm.Component');
const Viewer = Reflection.namespace('BX.UI.Viewer');

declare type SignDocumentViewParameters = {
	pdfNode: ?Element,
	pdfSource: ?string,
	printButtonId: ?string,
	downloadButtonId: ?string,
};

let defaultComponent = null;

/**
 * @memberOf BX.Crm.Component
 */
class SignDocumentView
{
	pdfNode: ?Element;
	pdfSource: ?string;
	printButton: ?BaseButton;
	downloadButton: ?BaseButton;
	viewer: ?Viewer.SingleDocumentController;

	constructor(parameters: SignDocumentViewParameters)
	{
		this.pdfNode = parameters.pdfNode;
		this.pdfSource = parameters.pdfSource;
		if (parameters.printButtonId)
		{
			this.printButton = ButtonManager.createByUniqId(parameters.printButtonId);
		}
		if (parameters.downloadButtonId)
		{
			this.downloadButton = ButtonManager.createByUniqId(parameters.downloadButtonId);
		}

		this.#initViewer();
		this.#bindEvents();

		defaultComponent = this;
	}

	#initViewer(): void
	{
		const viewer = this.getViewer();
		if (!viewer)
		{
			return;
		}
		viewer.setItems([Viewer.buildItemByNode(this.pdfNode)]);
		viewer.setPdfSource(this.pdfSource);
		viewer.setScale(0.92);
		viewer.open();
	}

	getViewer(): ?Viewer.SingleDocumentController
	{
		if (!this.viewer && this.pdfNode)
		{
			this.viewer = new Viewer.SingleDocumentController({baseContainer: this.pdfNode});
		}

		return this.viewer ?? null;
	}

	#bindEvents(): void
	{
		if (this.printButton && this.getViewer())
		{
			this.printButton.bindEvent('click', () => {
				this.getViewer().print();
			})
		}
		if (this.downloadButton)
		{
			this.downloadButton.bindEvent('click', () => {
				window.open(this.pdfSource, '_blank');
			});
		}
	}

	static getDefaultComponent(): ?SignDocumentView
	{
		return defaultComponent;
	}
}

namespace.SignDocumentView = SignDocumentView;
