<?php

namespace Bitrix\Call\Integration\AI;

enum SenseType: string
{
	case TRANSCRIBE = 'transcribe';
	case OVERVIEW = 'overview';
	case SUMMARY = 'summary';
	case INSIGHTS = 'insights';
}