<?php

namespace ecstsy\LoveLootbox\commands;

use ecstsy\LoveLootbox\commands\subcommands\CreateSubCommand;
use ecstsy\LoveLootbox\libs\CortexPE\Commando\args\IntegerArgument;
use ecstsy\LoveLootbox\libs\CortexPE\Commando\BaseCommand;
use ecstsy\LoveLootbox\commands\subcommands\GiveSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;
use ecstsy\LoveLootbox\Loader;

class LootboxCommand extends BaseCommand {

    private const PER_PAGE = 10;

    public function prepare(): void {
        $this->setPermission($this->getPermission());
        
        $this->registerArgument(0, new IntegerArgument("page", true));

        $this->registerSubCommand(new GiveSubCommand(Loader::getInstance(), "give", "Give lootbox to a player"));
        $this->registerSubCommand(new CreateSubCommand(Loader::getInstance(), "create", "Create a new lootbox"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&l&cError: &r&cThis command must be used in-game."));
            return;
        }

        $page = isset($args["page"]) ? $args["page"] : 1;

        $header = "&r&5Love&dLootbox &f- &8[&fPage &d" . $page . "&r&8]";

        $commands = [
            "&r&5/lovelootbox &dcreate &f<name>",
            "&r&5/lovelootbox &ddelete &f<name>",
            "&r&5/lovelootbox &dgive &f<player> <lootbox> <amount>",
            "&r&5/lovelootbox &dgiveall &f<lootbox> <amount>",
            "&r&5/lovelootbox &daddreward &f<lootbox> <chance> <give-item;true:false> <command;true:false>",
            "&r&5/lovelootbox &daddbonusreward &f<lootbox <chance> <give-item;true:false> <command;true:false>",
            "&r&5/lovelootbox &drewards &f<lootbox>",
            "&r&5/lovelootbox &dbonusrewards &f<lootbox>",
            "&r&5/lovelootbox &dadmin",
            "&r&5/lovelootbox &dreload",
        ];

        $totalCommands = count($commands);
        $totalPages = ceil($totalCommands / self::PER_PAGE);
        $page = max(1, min($page, $totalPages)); 

        $start = ($page - 1) * self::PER_PAGE;
        $commandsToShow = array_slice($commands, $start, self::PER_PAGE);

        $header = C::colorize("&r&5Love&dLootbox &f- &8[&fPage &d" . $page . "&r&8]");
        $sender->sendMessage($header);
        $sender->sendMessage(""); 

        foreach ($commandsToShow as $command) {
            $sender->sendMessage(C::colorize($command));
        }

    }

    public function getPermission(): string {
        return "lovelootbox.command";
    }
}
