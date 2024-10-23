<?php

namespace ecstsy\LoveLootbox\utils;

use ecstsy\LoveLootbox\libs\muqsit\invmenu\InvMenu;
use ecstsy\LoveLootbox\libs\muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use ecstsy\LoveLootbox\libs\muqsit\invmenu\type\InvMenuTypeIds;
use ecstsy\LoveLootbox\listeners\LootboxListener;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

class Screens {

    public static array $temporaryLootboxData = [];

    public static function getGrayGlassPane(): Item {
        return VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem()->setCustomName(" ");
    }

    private static function addMenuButton($inventory, int $slot, $item, string $name, array $lore): void {
        $item->setCustomName(C::colorize($name))->setLore(array_map(fn($line) => C::colorize($line), $lore));
        $inventory->setItem($slot, $item);
    }

    public static function createLootboxPanel(string $lootbox): InvMenu {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $inventory = $menu->getInventory();
        $menu->setName(C::colorize("&r&8Creating: " . $lootbox));

        if (!isset(self::$temporaryLootboxData[$lootbox])) {
            self::$temporaryLootboxData[$lootbox] = self::getDefaultLootboxData();
        }

        $lootboxData = self::$temporaryLootboxData[$lootbox];

        for ($slot = 0; $slot < 27; $slot++) {
            $inventory->setItem($slot, self::getGrayGlassPane());
        }

        self::addMenuButton($inventory, 0, VanillaBlocks::BARRIER()->asItem(), "&r&l&cCancel", [
            "&r&7Click to cancel creation of this",
            "&r&7lootbox.",
            "",
            "&r&c&oThis cannot be reversed"
        ]);

        self::addMenuButton($inventory, 8, VanillaItems::DYE()->setColor(DyeColor::LIME), "&r&a&lConfirm", [
            "&r&7Click to &aconfirm &7the creation of",
            "&r&7your new lootbox"
        ]);

        self::addMenuButton($inventory, 12, VanillaBlocks::OAK_SIGN()->asItem(), "&r&l&bEdit Displayname & Lore", [
            "&r&7Click to edit the displayname",
            "&r&7and the lore of the lootbox"
        ]);

        self::addMenuButton($inventory, 13, VanillaBlocks::CHEST()->asItem(), "&r&l&bChange Material", [
            "&r&7Click to change material of lootbox"
        ]);

        self::addMenuButton($inventory, 14, VanillaItems::NETHER_STAR(), "&r&l&bEdit Settings", [
            "&r&7Click to edit settings of this lootbox"
        ]);

        $menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction) use ($lootbox): void {
            $player = $transaction->getPlayer();
            $slot = $transaction->getAction()->getSlot();

            switch ($slot) {
                case 0:
                    unset(self::$temporaryLootboxData[$lootbox]);
                    $player->removeCurrentWindow();
                    break;
                case 12:
                    Screens::editLootboxCreation($lootbox)->send($player);
                    break;
                case 14:
                    break;
            }
        }));

        return $menu;
    }

    private static function getDefaultLootboxData(): array {
        return [
            'item' => [
                'material' => 'CHEST',
                'name' => '&r&cexample lootbox',
                'lore' => ['&r&7This is an example lore!']
            ],
            'animation' => [
                'type' => 1,
                'settings' => [
                    'title' => '&r&cexample lootbox',
                    'rewards-slots' => [2, 3, 4],
                    'bonus-rewards-slots' => [5, 6],
                    'timer-slots' => [0, 8],
                    'time' => 5,
                    'broadcast' => [
                        'enable' => true,
                        'header' => '&r&e&l(!) &e{PLAYER} has just opened the &c&lexample Lootbox &eand gotten',
                        'message' => '&r&6&l* &e&l{AMOUNT}x {ITEM}'
                    ],
                    'sound' => [
                        'start' => 'random.click',
                        'prize' => 'random.levelup'
                    ]
                ]
            ],
            'skippable' => true,
            'reward-preview' => [
                'enabled' => true,
                'show-percents' => true
            ],
            'rewards' => [],
            'bonus-rewards' => []
        ];
    }

    public static function editLootboxCreation(string $lootbox): InvMenu {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $inventory = $menu->getInventory();
        $data = self::$temporaryLootboxData[$lootbox];

        $menu->setName(C::colorize("&r&8Edit Display"));

        $excludedSlots = [13, 40, 45]; 
        $loreSlots = [19, 20, 21, 22, 23, 24, 25, 28, 29, 30, 31, 32, 33, 34]; 

        for ($slot = 0; $slot < $inventory->getSize(); $slot++) {
            if (!in_array($slot, array_merge($excludedSlots, $loreSlots), true)) {
                $inventory->setItem($slot, self::getGrayGlassPane());
            }
        }

        self::addMenuButton($inventory, 13, VanillaBlocks::OAK_SIGN()->asItem(), "&r&l&eSet Display Name", [
            "&r&7Click to change the display name",
            "",
            "&r&e&lCurrent: &r" . $data['item']['name']
        ]);

        foreach ($data['item']['lore'] as $index => $line) {
            if (isset($loreSlots[$index])) {
                $inventory->setItem($loreSlots[$index], VanillaBlocks::OAK_SIGN()->asItem()->setCustomName(C::colorize("&r&l&eLine&6 " . ($index + 1)))->setLore([C::colorize($line)]));
            }
        }
        
        $currentLoreCount = count($data['item']['lore']);
        if ($currentLoreCount < 14) {
            $addLoreSlot = self::getAddLoreSlot($currentLoreCount);
            if ($addLoreSlot !== -1) {
                self::addMenuButton($inventory, $addLoreSlot, VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GREEN())->asItem(), "&r&l&aAdd Lore Line", [
                    "&r&7Click to add another lore line"
                ]);
            }
        }
        
        if ($currentLoreCount === 7) {
        } else {
            
        }
        
        $material = StringToItemParser::getInstance()->parse($data['item']['material']);
        $previewItem = $material->setCustomName(C::colorize($data['item']['name']))->setLore(array_map(fn($lore) => C::colorize($lore), $data['item']['lore']));
        $inventory->setItem(40, $previewItem);

        self::addMenuButton($inventory, 45, VanillaBlocks::BARRIER()->asItem(), "&r&l&cGo Back", [
            "&r&7Click to return to the main menu"
        ]);

        $menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction) use ($lootbox, $data): void {
            $player = $transaction->getPlayer();
            $slot = $transaction->getAction()->getSlot();
            $messages = [
                "Please input the text you would like it to be &cRemember to add",
                "Write &ccancel &r&7to cancel"
            ];
        
            if ($slot === 13) {
                $player->sendMessage(C::colorize("&r&7Please input the text you would like it to be"));
                $player->sendMessage(C::colorize("&r&7Write &ccancel &r&7to cancel"));
                LootboxListener::$namingPlayers[$player->getName()] = $lootbox;
                $player->removeCurrentWindow();
            }
        
            if ($slot >= 19 && $slot <= 25 || $slot >= 28 && $slot <= 34) {
                $index = $slot - 19;
                if (isset($data['item']['lore'][$index])) {
                    foreach ($messages as $msg) {
                        $player->sendMessage(C::colorize("&r&7" . $msg));
                    }                   
                    
                    LootboxListener::$editingLore[$player->getName()] = ['lootbox' => $lootbox, 'lineIndex' => $index];
                    $player->removeCurrentWindow();
                }
            }
            
            if ($slot === self::getAddLoreSlot(count(self::$temporaryLootboxData[$lootbox]['item']['lore']))) {
                foreach ($messages as $msg) {
                    $player->sendMessage(C::colorize("&r&7" . $msg));
                }
                
                LootboxListener::$addingLore[$player->getName()] = $lootbox;
                $player->removeCurrentWindow();
                return;
            }

            if ($slot === 45) {
                Screens::createLootboxPanel($lootbox)->send($player);
            }
        }));

        return $menu;
    }

    /**
     * Get the appropriate slot for the "Add Lore Line" green glass pane
     */
    public static function getAddLoreSlot(int $loreCount): int {
        return match($loreCount) {
            1 => 20,
            2 => 21,
            3 => 22,
            4 => 23,
            5 => 24,
            6 => 25,
            7 => 28,
            8 => 29,
            9 => 30,
            10 => 31,
            11 => 32,
            12 => 33,
            13 => 34,
            default => -1  
        };
    }

}
