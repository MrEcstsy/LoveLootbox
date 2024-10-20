<?php

namespace ecstsy\LoveLootbox\listeners;

use ecstsy\LoveLootbox\utils\LootboxHandler;
use ecstsy\LoveLootbox\utils\Utils;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;

class LootboxListener implements Listener {

    public function onBlockPlace(BlockPlaceEvent $event): void {
        $item = $event->getItem();
        $tag = $item->getNamedTag();

        if ($tag->getTag("LoveLootbox") !== null) {
            $event->cancel();
        }
    }

    public function onPlayerItemUse(PlayerItemUseEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $tag = $item->getNamedTag();

        if (($lootbox = $tag->getTag("lootbox")) !== null) {
            $value = $lootbox->getValue();
            $config = Utils::getConfiguration("lootbox/" . $value . ".yml");

            $item->pop();
            $player->getInventory()->setItemInHand($item);

            $lootboxHandler = LootboxHandler::getInstance();

            $menu = $lootboxHandler->getLootboxMenu($player, $config->getNested("animation.type"), $config->get("rewards"), $config->get("bonus-rewards"), $config->getNested("animation.settings"));

            if ($menu === null) {
                return;
            }

            $menu->send($player);
        }
    }
}