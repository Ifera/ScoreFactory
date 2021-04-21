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

class ScoreFactory{

	/** @var string */
	private const OBJECTIVE_NAME = "objective";
	/** @var string */
	private const CRITERIA_NAME = "dummy";

	/** @var int */
	private const MIN_LINES = 1;
	/** @var int */
	private const MAX_LINES = 15;

	/** @var int */
	public const SORT_ASCENDING = 0;
	/** @var int */
	public const SORT_DESCENDING = 1;

	/** @var string */
	public const SLOT_LIST = "list";
	/** @var string */
	public const SLOT_SIDEBAR = "sidebar";
	/** @var string */
	public const SLOT_BELOW_NAME = "belowname";

	/** @var string[] */
	private static array $scoreboards = [];

	/**
	 * Adds a Scoreboard to the player if he doesn't have one.
	 * Can also be used to update a scoreboard.
	 *
	 * @param Player $player
	 * @param string $displayName
	 * @param int    $slotOrder
	 * @param string $displaySlot
	 * @param string $objectiveName
	 * @param string $criteriaName
	 */
	public static function setScore(Player $player, string $displayName, int $slotOrder = self::SORT_ASCENDING, string $displaySlot = self::SLOT_SIDEBAR, string $objectiveName = self::OBJECTIVE_NAME, string $criteriaName = self::CRITERIA_NAME): void{
		if(isset(self::$scoreboards[$player->getRawUniqueId()])){
			self::removeScore($player);
		}

		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = $displaySlot;
		$pk->objectiveName = $objectiveName;
		$pk->displayName = $displayName;
		$pk->criteriaName = $criteriaName;
		$pk->sortOrder = $slotOrder;
		$player->sendDataPacket($pk);

		self::$scoreboards[$player->getRawUniqueId()] = $objectiveName;
	}

	/**
	 * Removes a scoreboard from the player specified.
	 *
	 * @param Player $player
	 */
	public static function removeScore(Player $player): void{
		$objectiveName = self::$scoreboards[$player->getRawUniqueId()] ?? self::OBJECTIVE_NAME;

		$pk = new RemoveObjectivePacket();
		$pk->objectiveName = $objectiveName;
		$player->sendDataPacket($pk);

		unset(self::$scoreboards[$player->getRawUniqueId()]);
	}

	/**
	 * Returns an array consisting of a list of the players using scoreboard.
	 *
	 * @return string[]
	 */
	public static function getScoreboards(): array{
		return self::$scoreboards;
	}

	/**
	 * Returns true or false if a player has a scoreboard or not.
	 *
	 * @param Player $player
	 * @return bool
	 */
	public static function hasScore(Player $player): bool{
		return isset(self::$scoreboards[$player->getRawUniqueId()]);
	}

	/**
	 * Set a message at the line specified to the players scoreboard.
	 *
	 * @param Player $player
	 * @param int    $line
	 * @param string $message
	 * @param int    $type
	 */
	public static function setScoreLine(Player $player, int $line, string $message, int $type = ScorePacketEntry::TYPE_FAKE_PLAYER): void{
		if(!isset(self::$scoreboards[$player->getRawUniqueId()])){
			throw new BadFunctionCallException("Cannot set a score to a player without a scoreboard");
		}

		if($line < self::MIN_LINES || $line > self::MAX_LINES){
			throw new OutOfBoundsException("$line is out of range, expected value between " . self::MIN_LINES . " and " . self::MAX_LINES);
		}

		$entry = new ScorePacketEntry();
		$entry->objectiveName = self::$scoreboards[$player->getRawUniqueId()] ?? self::OBJECTIVE_NAME;
		$entry->type = $type;
		$entry->customName = $message;
		$entry->score = $line;
		$entry->scoreboardId = $line;

		$pk = new SetScorePacket();
		$pk->type = $pk::TYPE_CHANGE;
		$pk->entries[] = $entry;
		$player->sendDataPacket($pk);
	}
}