type BannerStyle = {
	backgroundColor: string,
}

type BannerProps = {
	iconUri: string,
	title: string,
	description: string,
	isComplete: boolean,
	style: BannerStyle,
};