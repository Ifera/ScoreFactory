<?php
declare(strict_types = 1);

namespace jackmd\scorefactory;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\Player;
use BadFunctionCallException;
use OutOfBoundsException;
use function array_map;
use function array_values;

class ScoreFactory{

	private const OBJECTIVE_NAME = "objective";
	private const CRITERIA_NAME = "dummy";

	private const MIN_LINES = 1;
	private const MAX_LINES = 15;

	public const SORT_ASCENDING = 0;
	public const SORT_DESCENDING = 1;

	public const SLOT_LIST = "list";
	public const SLOT_SIDEBAR = "sidebar";
	public const SLOT_BELOW_NAME = "belowname";

	/** @var ScoreCache[] */
	private static array $cache = [];

	/**
	 * Adds a Scoreboard to the player if he doesn't have one.
	 * Can also be used to update a scoreboard.
	 */
	public static function setScore(Player $player, string $displayName, int $slotOrder = self::SORT_ASCENDING, string $displaySlot = self::SLOT_SIDEBAR, string $objectiveName = self::OBJECTIVE_NAME, string $criteriaName = self::CRITERIA_NAME): void{
		if(isset(self::$cache[$player->getRawUniqueId()])){
			self::removeScore($player);
		}

		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = $displaySlot;
		$pk->objectiveName = $objectiveName;
		$pk->displayName = $displayName;
		$pk->criteriaName = $criteriaName;
		$pk->sortOrder = $slotOrder;

		self::$cache[$player->getRawUniqueId()] = ScoreCache::init($player, $objectiveName, $pk);
	}

	/**
	 * Removes a scoreboard from the player specified.
	 */
	public static function removeScore(Player $player): void{
		$objectiveName = isset(self::$cache[$player->getRawUniqueId()]) ? self::$cache[$player->getRawUniqueId()]->getObjective() : self::OBJECTIVE_NAME;

		$pk = new RemoveObjectivePacket();
		$pk->objectiveName = $objectiveName;
		$player->sendDataPacket($pk);

		unset(self::$cache[$player->getRawUniqueId()]);
	}

	/**
	 * @return Player[]
	 */
	public static function getActivePlayers(): array{
		return array_values(array_map(function(ScoreCache $cache){
			return $cache->getPlayer();
		}, self::$cache));
	}

	/**
	 * Returns true or false if a player has a scoreboard or not.
	 */
	public static function hasScore(Player $player): bool{
		return isset(self::$cache[$player->getRawUniqueId()]);
	}

	/**
	 * Set a message at the line specified to the players scoreboard.
	 */
	public static function setScoreLine(Player $player, int $line, string $message, int $type = ScorePacketEntry::TYPE_FAKE_PLAYER): void{
		if(!isset(self::$cache[$player->getRawUniqueId()])){
			throw new BadFunctionCallException("Cannot set a score to a player without a scoreboard. Please call ScoreFactory::setScore() beforehand.");
		}

		if($line < self::MIN_LINES || $line > self::MAX_LINES){
			throw new OutOfBoundsException("Line: $line is out of range, expected value between " . self::MIN_LINES . " and " . self::MAX_LINES);
		}

		$cache = self::$cache[$player->getRawUniqueId()];

		$entry = new ScorePacketEntry();
		$entry->objectiveName = $cache->getObjective();
		$entry->type = $type;
		$entry->customName = $message;
		$entry->score = $line;
		$entry->scoreboardId = $line;

		$cache->setEntry($line, $entry);
	}

	/**
	 * Send scoreboard to the player
	 */
	public static function send(Player $player, bool $sendLines = true){
		if(!isset(self::$cache[$player->getRawUniqueId()])){
			throw new BadFunctionCallException("Cannot send score to a player without a scoreboard. Please call ScoreFactory::setScore() beforehand.");
		}

		$cache = self::$cache[$player->getRawUniqueId()];
		$player->sendDataPacket($cache->getObjectivePacket());

		if($sendLines){
			$pk = new SetScorePacket();
			$pk->type = $pk::TYPE_CHANGE;
			$pk->entries = $cache->getEntries();
			$player->sendDataPacket($pk);
		}
	}
}