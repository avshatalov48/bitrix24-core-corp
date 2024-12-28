<?php

use Bitrix\AI\Engine;
use Bitrix\AI\Facade;

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

	Engine::addEngine(Engine\Enum\Category::CALL, Engine\Cloud\AudioCall::class);

	Engine::addEngine(Engine\Enum\Category::IMAGE, Engine\Cloud\YandexART::class);
	Engine::addEngine(Engine\Enum\Category::IMAGE, Engine\Cloud\Kandinsky::class);
}
Engine::triggerEngineAddedEvent();

include('prompt_updater.php');