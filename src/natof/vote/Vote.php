<?php

namespace natof\vote;

use natof\vote\command\VoteCommand;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Vote extends PluginBase
{
    private static self $instance;

    protected function onLoad(): void{ self::$instance = $this;}
    public static function getInstance(): self {return self::$instance;}

    protected function onEnable(): void
    {
        @mkdir(self::getDataFolder() . "data");
        $this->saveResource("data/data.json");
        $data = new Config(Vote::getInstance()->getDataFolder() . "data/data.json", Config::JSON);
        self::getServer()->getCommandMap()->registerAll("vote", [
            new VoteCommand($data->get("command"), $data->get("command_description"))
        ]);
    }
}