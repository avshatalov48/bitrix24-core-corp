type Buttons = {
	text: string,
	type: 'default' | 'destructive' | 'cancel',
	onPress: void;
}

declare class Alert
{
	alert(title: string, description?: string, onPress?: void, buttonName?: string): void;

	confirm(title: string, description: string, buttons: Buttons): void;
}