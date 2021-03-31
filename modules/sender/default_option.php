<?php

$sender_default_option = [
	'interval'=>'20',
	'auto_method'=>'agent',
	'max_emails_per_hit'=>'500',
	'max_emails_per_cron'=>'500',
	'reiterate_method'=>'agent',
	'reiterate_interval'=>'60',
	'address_from'=>'',
	'address_send_to_me'=>'',
	'unsub_link'=>'',
	'sub_link'=>'',
	'auto_agent_interval'=>'0',
	'track_mails'=>  \Bitrix\Sender\Integration\Bitrix24\Service::isCloudRegionMayTrackMails() ? 'N' : 'Y',
];

