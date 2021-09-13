<?php
declare(strict_types = 1);

namespace jackmd\scorefactory;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use BadFunctionCallException;
use OutOfBoundsException;
use function mb_strtolower;

class ScoreFactory{

	/** @var string */
	private const OBJECTIVE_NAME = "objective";
	/** @var string */
	private const CRITERIA_NAME = "dummy";

	/** @var int */
	private const MIN_LINES = 1;
	/** @var int */
	private const MAX_LINES = 15;

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
	public static function setScore(
		Player $player,
		string $displayName,
		int $slotOrder = SetDisplayObjectivePacket::SORT_ORDER_ASCENDING,
		string $displaySlot = SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR,
		string $objectiveName = self::OBJECTIVE_NAME,
		string $criteriaName = self::CRITERIA_NAME
	): void{
        if (!$player->isConnected()){
            return;
        }
		if(isset(self::$scoreboards[mb_strtolower($player->getName())])){
			self::removeScore($player);
		}

		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = $displaySlot;
		$pk->objectiveName = $objectiveName;
		$pk->displayName = $displayName;
		$pk->criteriaName = $criteriaName;
		$pk->sortOrder = $slotOrder;
		$player->getNetworkSession()->sendDataPacket($pk);

		self::$scoreboards[mb_strtolower($player->getName())] = $objectiveName;
	}

	/**
	 * Removes a scoreboard from the player specified.
	 *
	 * @param Player $player
	 */
	public static function removeScore(Player $player): void{
        if (!$player->isConnected()){
            return;
        }
		$objectiveName = self::$scoreboards[mb_strtolower($player->getName())] ?? self::OBJECTIVE_NAME;

		$pk = new RemoveObjectivePacket();
		$pk->objectiveName = $objectiveName;
		$player->getNetworkSession()->sendDataPacket($pk);

		unset(self::$scoreboards[mb_strtolower($player->getName())]);
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
		return isset(self::$scoreboards[mb_strtolower($player->getName())]);
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
        if (!$player->isConnected()){
            return;
        }
		if(!isset(self::$scoreboards[mb_strtolower($player->getName())])){
			throw new BadFunctionCallException("Cannot set a score to a player without a scoreboard");
		}

		if($line < self::MIN_LINES || $line > self::MAX_LINES){
			throw new OutOfBoundsException("$line is out of range, expected value between " . self::MIN_LINES . " and " . self::MAX_LINES);
		}

		$entry = new ScorePacketEntry();
		$entry->objectiveName = self::$scoreboards[mb_strtolower($player->getName())] ?? self::OBJECTIVE_NAME;
		$entry->type = $type;
		$entry->customName = $message;
		$entry->score = $line;
		$entry->scoreboardId = $line;

		$pk = new SetScorePacket();
		$pk->type = $pk::TYPE_CHANGE;
		$pk->entries[] = $entry;
		$player->getNetworkSession()->sendDataPacket($pk);
	}
}