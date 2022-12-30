<?php

namespace natof\vote\command;

use natof\vote\librairie\forms\CustomForm;
use natof\vote\librairie\forms\CustomFormResponse;
use natof\vote\librairie\forms\elements\Button;
use natof\vote\librairie\forms\elements\Image;
use natof\vote\librairie\forms\elements\Input;
use natof\vote\librairie\forms\MenuForm;
use natof\vote\librairie\gui\inventories\SimpleChestInventory;
use natof\vote\Vote;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

class VoteCommand extends Command
{
    public function file_get_contents_curl( $url ) {

    $ch = curl_init();

    curl_setopt( $ch, CURLOPT_AUTOREFERER, TRUE );
    curl_setopt( $ch, CURLOPT_HEADER, 0 );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, TRUE );

    $data = curl_exec( $ch );
    curl_close( $ch );

    return $data;

}
    
    
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) return;
        $data = new Config(Vote::getInstance()->getDataFolder() . "data/data.json", Config::JSON);
        $json = json_decode(file_get_contents_curl("https://vote.minelance.fr/api/v1/vote/check/" . $data->get("key") . "/" . strtolower($sender->getName())), true);
        if (isset($args[0])) {
            if ($args[0] == "setup") {
                if (Server::getInstance()->isOp($sender->getName()) || $sender->hasPermission("vote.use")) {
                    $sender->sendForm(new MenuForm("§9§l« §r§fVote config §9§l»", "", [
                        new Button("Reward", new Image("textures/items/emerald")),
                        new Button("Message", new Image("textures/blocks/command_block")),
                        new Button("Key", new Image("textures/items/nether_star"))
                    ], function (Player $player, Button $button) use ($data): void {
                        if ($button->getText() == "Reward") {
                            $inventory = new SimpleChestInventory();
                            $items = $data->get("inventory", null);
                            if ($items != null) {
                                $items = unserialize(base64_decode($items));
                                if (!empty($items)) foreach ($items as $item) {
                                    if (isset($item['id'])) {
                                        if ($inventory->canAddItem(Item::jsonDeserialize($item))) {
                                            $inventory->addItem(Item::jsonDeserialize($item));
                                        }
                                    }
                                }
                            }
                            $inventory->setName("§9§l« §r§fReward §9§l»");
                            $inventory->send($player);
                            $inventory->setCloseListener(function (Player $player, Inventory $inventory) use ($data): void {
                                $items = [];
                                foreach ($inventory->getContents() as $item) $items[] = $item->jsonSerialize();
                                $data->set("inventory", base64_encode(serialize($items)));
                                $data->save();
                            });
                        } else if ($button->getText() == "Message") {
                            $player->sendForm(new CustomForm("§9§l« §r§fVote config §9§l»", [
                                new Input("§9§l» §r§fNo vote message", $data->get("no_vote_message"), $data->get("no_vote_message")),
                                new Input("§9§l» §r§fVoted message", $data->get("vote_message"), $data->get("vote_message")),
                                new Input("§9§l» §r§fAlready voted message", $data->get("already_vote_message"), $data->get("already_vote_message")),
                                new Input("§9§l» §r§fCommand description (Need restart for change)", $data->get("command_description"), $data->get("command_description")),
                                new Input("§9§l» §r§fCommand (Need restart for change)", $data->get("command"), $data->get("command")),
                                new Input("§9§l» §r§fCommand Permission message", $data->get("command_permission_message", $data->get("command_permission_message")))
                            ], function (Player $player, CustomFormResponse $response) use ($data): void {
                                list($noVoteMessage, $voteMessage, $alreadyVote, $commandDescription, $command, $commandPermission) = $response->getValues();
                                $data->set("no_vote_message", $noVoteMessage);
                                $data->set("vote_message", $voteMessage);
                                $data->set("already_vote_message", $alreadyVote);
                                $data->set("command_description", $commandDescription);
                                $data->set("command", $command);
                                $data->set("command_permission_message", $commandPermission);
                                $data->save();
                            }));
                        } else if ($button->getText() == "Key") {
                            $player->sendForm(new CustomForm("§9§l« §r§fVote config §9§l»", [
                                new Input("§9§l» Key (The key must be secret)", $data->get("key"), $data->get("key"))
                            ], function (Player $player, CustomFormResponse $response) use ($data): void {
                                list($key) = $response->getValues();
                                $data->set("key", $key);
                                $data->save();
                            }));
                        }
                    }));
                    return;
                } else {
                    $sender->sendMessage($data->get("command_permission_message"));
                }
            }
        }
        if ($json == null) {
            $sender->sendMessage("§cThe key is not good contact a server admin!");
            return;
        }
        switch ($json["code"]) {
            case 0:
                $sender->sendMessage($data->get("no_vote_message"));
                break;
            case 1:
                $sender->sendMessage($data->get("vote_message"));
                $items = $data->get("inventory", null);
                if ($items != null) {
                    $items = unserialize(base64_decode($items));
                    if (!empty($items)) foreach ($items as $item) {
                        if (isset($item['id'])) {
                            if ($sender->getInventory()->canAddItem(Item::jsonDeserialize($item))) $sender->getInventory()->addItem(Item::jsonDeserialize($item));
                            else $sender->getWorld()->dropItem($sender->getPosition(), Item::jsonDeserialize($item));
                        }
                    }
                }
                break;
            case 2:
                $sender->sendMessage($data->get("already_vote_message"));
                break;
            case 3:
                $sender->sendMessage("§cThe key is not good contact a server admin!");
        }
    }
}
