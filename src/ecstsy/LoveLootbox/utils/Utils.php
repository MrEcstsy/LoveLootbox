<?php

namespace ecstsy\LoveLootbox\utils;

use ecstsy\LoveLootbox\Loader;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as C;

class Utils {

    private static array $configCache;

    public static function getConfiguration(string $fileName): ?Config {
        $pluginFolder = Loader::getInstance()->getDataFolder();
        $filePath = $pluginFolder . $fileName;

        if (isset(self::$configCache[$filePath])) {
            return self::$configCache[$filePath];
        }

        if (!file_exists($filePath)) {
            Loader::getInstance()->getLogger()->warning("Configuration file '$filePath' not found.");
            return null;
        }
        
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        switch ($extension) {
            case 'yml':
            case 'yaml':
                $config = new Config($filePath, Config::YAML);
                break;

            case 'json':
                $config = new Config($filePath, Config::JSON);
                break;

            default:
                Loader::getInstance()->getLogger()->warning("Unsupported configuration file format for '$filePath'.");
                return null;
        }

        self::$configCache[$filePath] = $config;
        return $config;
   }

    /**
     * Returns an online player whose name begins with or equals the given string (case insensitive).
     * The closest match will be returned, or null if there are no online matches.
     *
     * @param string $name The prefix or name to match.
     * @return Player|null The matched player or null if no match is found.
     */
    public static function getPlayerByPrefix(string $name): ?Player {
        $found = null;
        $name = strtolower($name);
        $delta = PHP_INT_MAX;

        /** @var Player[] $onlinePlayers */
        $onlinePlayers = Server::getInstance()->getOnlinePlayers();

        foreach ($onlinePlayers as $player) {
            if (stripos($player->getName(), $name) === 0) {
                $curDelta = strlen($player->getName()) - strlen($name);

                if ($curDelta < $delta) {
                    $found = $player;
                    $delta = $curDelta;
                }

                if ($curDelta === 0) {
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * Creates a lootbox item based on the identifier and the file data.
     *
     * @param string $identifier The identifier (filename) for the lootbox.
     * @param int $amount The number of lootbox items to create.
     * @return Item|null Returns the created item or null if not found.
     */
    public static function createLootboxItem(string $identifier, int $amount = 1): ?Item {
        $pluginDataPath = "lootbox/";
        $filePath = $pluginDataPath . $identifier . ".yml";

        
        $config = Utils::getConfiguration($filePath);

        if ($config === null) {
            return null;
        }
        
        $data = $config->getAll();
        $materialString = $data['item']['material'] ?? "CHEST"; 
        $name = $data['item']['name'] ?? "Unnamed Lootbox";
        $lore = $data['item']['lore'] ?? [];

        $material = StringToItemParser::getInstance()->parse($materialString);

        if ($material === null) {
            return null; 
        }

        $material->setCount($amount);

        $material->setCustomName(C::colorize($name));

        $coloredLore = array_map(static fn($line) => C::colorize($line), $lore);
        $material->setLore($coloredLore);

        $root = $material->getNamedTag();
        $lootboxTag = new CompoundTag();

        $lootboxTag->setString("lootbox", $identifier);

        $root->setTag("LoveLootbox", $lootboxTag);

        return $material;
    }
    
    public static function setupRewards(array $rewardData, ?Player $player = null): array
    {
        $rewards = [];
        $stringToItemParser = StringToItemParser::getInstance();
        
        foreach ($rewardData as $data) {
            if (!isset($data["item"])) {
                continue; 
            }

            $display = $data["display"] ?? false;
            if ($display === true && isset($data["command"]) && $player !== null) {
                $commandString = str_replace("{player}", $player->getName(), $data["command"]);
                Server::getInstance()->dispatchCommand(
                    new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()),
                    $commandString
                );
                continue;
            }

            $itemString = $data["item"];
            $item = $stringToItemParser->parse($itemString);
            if ($item === null) {
                continue;
            }

            $amount = $data["amount"] ?? 1;
            $item->setCount($amount);

            $name = $data["name"] ?? null;
            if ($name !== null) {
                $item->setCustomName(C::colorize($name));
            }

            $lore = $data["lore"] ?? null;
            if ($lore !== null) {
                $coloredLore = array_map(function ($line) {
                    return C::colorize($line);
                }, $lore);
                $item->setLore($coloredLore);
            }

            $enchantments = $data["enchantments"] ?? [];
            foreach ($enchantments as $enchantmentData) {
                $enchantmentString = $enchantmentData["enchant"] ?? null;
                $level = $enchantmentData["level"] ?? 1;

                if ($enchantmentString !== null) {
                    $enchantment = StringToEnchantmentParser::getInstance()->parse($enchantmentString);
                    if ($enchantment !== null) {
                        $item->addEnchantment(new EnchantmentInstance($enchantment, $level));
                    }
                }
            }

            $nbtData = $data["nbt"] ?? [];
            foreach ($nbtData as $tag => $value) {
                if (is_int($value)) {
                    $item->getNamedTag()->setInt($tag, $value);
                } else {
                    $item->getNamedTag()->setString($tag, $value);
                }
            }

            $rewards[] = $item;
        }

        return $rewards;
    }

    public static function startLootboxAnimation(Player $player, InvMenu $menu, array $settings, array $rewards, array $bonusRewards): void {
        $inventory = $menu->getInventory();
    
        $timerSlots = $settings['timer-slots'] ?? [];
        $rewardSlots = $settings['rewards-slots'] ?? [];
        $bonusRewardSlots = $settings['bonus-rewards-slots'] ?? [];
        $totalTime = $settings['time'] ?? 5; 
        $soundSettings = $settings['sound'] ?? [];
    
        Loader::getInstance()->getScheduler()->scheduleRepeatingTask(
            new LootboxTask($player, $inventory, $timerSlots, $rewardSlots, $bonusRewardSlots, $rewards, $bonusRewards, $totalTime, $soundSettings),
            1
        );
    
        $menu->setInventoryCloseListener(function(Player $player, Inventory $inventory) use ($rewardSlots, $bonusRewardSlots): void {
            $finalRewards = Utils::extractRewardsFromSlots($inventory, $rewardSlots);
            $finalBonusRewards = Utils::extractRewardsFromSlots($inventory, $bonusRewardSlots);
            $allRewards = array_merge($finalRewards, $finalBonusRewards);
    
            Utils::giveFinalRewards($player, $allRewards);
        });
    }
    
    /**
     * Helper method to extract items from specific inventory slots.
     */
    public static function extractRewardsFromSlots(Inventory $inventory, array $slots): array
    {
        $rewards = [];
        foreach ($slots as $slot) {
            $item = $inventory->getItem($slot);
            if (!$item->isNull()) {
                $rewards[] = $item;
            }
        }
        return $rewards;
    }

    /**
     * Gives the final rewards to the player by adding items to their inventory.
     */
    public static function giveFinalRewards(Player $player, array $rewards): void
    {
        foreach ($rewards as $reward) {
            if ($reward instanceof Item) {
                $player->getInventory()->addItem($reward);
            }
        }

        // TODO: Make into the broadcast message.
        $player->sendMessage("You have received your rewards!");
    }

     /**
     * @param Entity $player
     * @param string $sound
     * @param int $volume
     * @param int $pitch
     * @param int $radius
     */
    public static function playSound(Entity $player, string $sound, $volume = 1, $pitch = 1, int $radius = 5): void
    {
        foreach ($player->getWorld()->getNearbyEntities($player->getBoundingBox()->expandedCopy($radius, $radius, $radius)) as $p) {
            if ($p instanceof Player) {
                if ($p->isOnline()) {
                    $spk = new PlaySoundPacket();
                    $spk->soundName = $sound;
                    $spk->x = $p->getLocation()->getX();
                    $spk->y = $p->getLocation()->getY();
                    $spk->z = $p->getLocation()->getZ();
                    $spk->volume = $volume;
                    $spk->pitch = $pitch;
                    $p->getNetworkSession()->sendDataPacket($spk);
                }
            }
        }
    }
    
}
