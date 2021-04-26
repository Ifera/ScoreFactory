<?php
declare(strict_types = 1);

namespace jackmd\scorefactory;

use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\Player;

/**
 * @internal
 */
class ScoreCache{

	private Player $player;
	private string $objective;
	private SetDisplayObjectivePacket $objectivePacket;

	/** @var ScorePacketEntry[][] */
	private array $entries = [];

	public static function init(Player $player, string $objective, SetDisplayObjectivePacket $objectivePacket): self{
		return new self($player, $objective, $objectivePacket);
	}

	private function __construct(Player $player, string $objective, SetDisplayObjectivePacket $objectivePacket){
		$this->player = $player;
		$this->objective = $objective;
		$this->objectivePacket = $objectivePacket;
	}

	public function getPlayer(): Player{
		return $this->player;
	}

	public function getObjective(): string{
		return $this->objective;
	}

	public function setObjective(string $objective): void{
		$this->objective = $objective;
	}

	public function getObjectivePacket(): SetDisplayObjectivePacket{
		return $this->objectivePacket;
	}

	public function setObjectivePacket(SetDisplayObjectivePacket $objectivePacket): void{
		$this->objectivePacket = $objectivePacket;
	}

	/**
	 * Indexed by (int) line -> ScorePacketEntry
	 *
	 * @return ScorePacketEntry[][]
	 */
	public function getEntries(): array{
		return $this->entries;
	}

	/**
	 * Should be indexed by (int) line -> ScorePacketEntry
	 * No more than 15 entries allowed. #blameMojang
	 *
	 * @param ScorePacketEntry[][] $entries
	 */
	public function setEntries(array $entries): void{
		$this->entries = $entries;
	}

	/**
	 * Index should be in between 1 and 15
	 */
	public function setEntry(int $index, ScorePacketEntry $entry){
		$this->entries[$index] = $entry;
	}

	public function __destruct(){
		unset($this->entries);
	}
}