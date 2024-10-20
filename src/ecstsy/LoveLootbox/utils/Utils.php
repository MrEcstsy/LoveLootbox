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
    
}
