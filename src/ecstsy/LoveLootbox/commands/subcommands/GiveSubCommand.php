<?php

namespace ecstsy\LoveLootbox\commands\subcommands;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\LoveLootbox\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

class GiveSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("name", true));
        $this->registerArgument(1, new RawStringArgument("lootbox", true));
        $this->registerArgument(2, new IntegerArgument("amount", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $lang = Utils::getConfiguration("lang.yml");    
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&l&cError: &r&cThis command must be used in-game."));
            return;
        }

        if (!isset($args["name"])) {
            $sender->sendMessage($this->getUsage());
            return;
        }
       
        if (!isset($args["lootbox"])) {
            $sender->sendMessage($this->getUsage());
            return;
        }

        $lootbox = isset($args["lootbox"]) ? $args["lootbox"] : null;
        $amount = isset($args["amount"]) ? $args["amount"] : 1;
        $player = Utils::getPlayerByPrefix($args["name"]);

        if ($player === null) {
            $sender->sendMessage(C::colorize("&r&cNo player by the name of &e" . $args["name"] . "&r&c is connected to this server."));
            return;
        }

        if ($lootbox === null) {
            $sender->sendMessage(C::colorize($lang->getNested("ERROR.INVALID-LOOTBOX")));
            return;
        }

        $item = Utils::createLootboxItem($lootbox, $amount);

        if ($item === null) {
            $sender->sendMessage(C::colorize($lang->getNested("ERROR.INVALID-LOOTBOX")));
            return;
        }

        if (!$player->getInventory()->canAddItem($item)) {
            $sender->sendMessage(C::colorize($lang->getNested("ERROR.INVENTORY-FULL")));
            return;
        }

        $player->getInventory()->addItem($item);
    }

    public function getUsage(): string
    {
        return C::colorize("&r&eUsage: &a/ll give &f<onlinePlayer> <lootbox> [amount]");
    }

    public function getPermission(): string {
        return "lovelootbox.give";
    }
}