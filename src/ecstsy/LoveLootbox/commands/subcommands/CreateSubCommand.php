<?php

namespace ecstsy\LoveLootbox\commands\subcommands;

use ecstsy\LoveLootbox\libs\CortexPE\Commando\args\RawStringArgument;
use ecstsy\LoveLootbox\libs\CortexPE\Commando\BaseSubCommand;
use ecstsy\LoveLootbox\utils\Screens;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

class CreateSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("name"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }
        
        $lootboxName = strtolower($args["name"]); 

        if (file_exists($this->getOwningPlugin()->getDataFolder() . "lootbox/" . $lootboxName . ".yml")) {
            $sender->sendMessage(C::RED . "A lootbox with the name '$lootboxName' already exists.");
            return;
        }

        Screens::createLootboxPanel($lootboxName)->send($sender);
    }

    public function getPermission(): string {
        return "lovelootbox.create";
    }
}