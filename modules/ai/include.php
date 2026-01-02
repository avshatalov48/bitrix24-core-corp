<?php

use Bitrix\AI\Engine;
use Bitrix\AI\Facade;
use Bitrix\Main\Loader;

if (Facade\Bitrix24::shouldUseB24() === false)
{
	Engine::addEngine(Engine\Enum\Category::TEXT, Engine\Cloud\Bitrix24::class);
	Engine::addEngine(Engine\Enum\Category::TEXT, Engine\Cloud\GigaChat::class);
	Engine::addEngine(Engine\Enum\Category::TEXT, Engine\Cloud\YandexGPT::class);
	Engine::addEngine(Engine\Enum\Category::TEXT, Engine\Cloud\ItSolution::class);

	Engine::addEngine(Engine\Enum\Category::TEXT, Engine\Cloud\ChatGPT::class);

	Engine::addEngine(Engine\Enum\Category::AUDIO, Engine\Cloud\BitrixAudio::class);
	Engine::addEngine(Engine\Enum\Category::AUDIO, Engine\Cloud\ItSolutionAudio::class);
	Engine::addEngine(Engine\Enum\Category::AUDIO, Engine\Cloud\Whisper::class);
	Engine::addEngine(Engine\Enum\Category::AUDIO, Engine\Cloud\SaluteSpeech::class);

	Engine::addEngine(Engine\Enum\Category::CALL, Engine\Cloud\AudioCall::class);
	Engine::addEngine(Engine\Enum\Category::CALL, Engine\Cloud\BitrixAudioCall::class);

	Engine::addEngine(Engine\Enum\Category::IMAGE, Engine\Cloud\YandexART::class);
	Engine::addEngine(Engine\Enum\Category::IMAGE, Engine\Cloud\Kandinsky::class);
	Engine::addEngine(Engine\Enum\Category::IMAGE, Engine\Cloud\Dalle::class);
}
Engine::triggerEngineAddedEvent();

include('prompt_updater.php');

//\Bitrix\Main\Config\Option::set('ai', 'MARTA_BOT_ENABLE', 'Y');

$documentRoot = Loader::getDocumentRoot();
if (is_dir($documentRoot . '/bitrix/modules/ai/dev/'))
{
	// developer mode
	Loader::registerNamespace('Bitrix\AI\Dev',  $documentRoot . '/bitrix/modules/ai/dev');
}