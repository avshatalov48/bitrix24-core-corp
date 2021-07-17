import {BaseEvent, EventEmitter} from 'main.core.events';
import Comment from './comment';
export default class Form extends EventEmitter
{
	constructor()
	{
		super();
		this.setEventNamespace('CRM:Calltracker:');
		this.params = {
			useAudioMessages: true,
			placeholder: '',
			//onEvent: this.onFormIsActive.bind(this),
			onSend: this.onSendButtonPressed.bind(this)
		};

		window.BX.MobileUI.TextField.show(this.params);

		this.showCommentStart = this.showCommentStart.bind(this);
		this.showCommentError = this.showCommentError.bind(this);
		this.showCommentSucceed = this.showCommentSucceed.bind(this);
	}

	show()
	{
		window.BXMobileApp.UI.Page.TextPanel.setText('');
		window.BXMobileApp.UI.Page.TextPanel.focus();
	}
	onFormIsActive(event)
	{
		console.log('event: ', event);
	}
	onSendButtonPressed({text, attachedFiles})
	{
		window.BXMPage.TextPanel.clear();

		const cleanText = String(text).trim();
		if (cleanText.length > 0 || (attachedFiles && attachedFiles.length > 0))
		{
			const entity = new Comment({
				text: cleanText,
				files: attachedFiles,
				events: {
					start: this.showCommentStart,
					error: this.showCommentError,
					success: this.showCommentSucceed
				}
			});
			this.emit('onNewComment', {comment: entity});
		}
	}

	showCommentStart({data:{entity}}: BaseEvent) {
	}
	showCommentError({data:{entity, error}}: BaseEvent) {
		this.emit('onFailedComment', {comment: entity, error});
	}
	showCommentSucceed({data:{entity, data: {item, items}}}: BaseEvent) {
		this.emit('onSucceedComment', {comment: entity, commentData: item, comments: items});
	}
	showWait()
	{
		window.BXMobileApp.UI.Page.TextPanel.showLoading(true);
	}
	closeWait()
	{
		window.BXMobileApp.UI.Page.TextPanel.showLoading(false);
	}
}