<?php

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author iTX Technologies
 * @link https://itxtech.org
 *
 */
 
namespace synapsetransfer;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use synapse\Player;

class SynapseTransfer extends PluginBase{
	/** @var Config */
	private $config;
	private $conf;
	private $list;

	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
			"list" => [
				"1" => "1"
			]
		]);
		$this->conf = $this->config->getAll();
		$this->list = $this->conf["list"];
		if(!$this->getServer()->isSynapseEnabled()){
			$this->getLogger()->error("Synapse Client service has been disabled, this plugin won't work!");
			$this->setEnabled(false);
			return;
		}
		$this->getLogger()->info("Synapse Transfer has been enabled.");
	}

	public function onDisable(){
		$this->conf["list"] = $this->list;
		$this->config->setAll($this->conf);
		$this->config->save();
		$this->getLogger()->info("Synapse Transfer has been disabled.");
	}

	/**
	 * @param $des
	 * @return array|null
	 */
	public function getClientDataByDescription(string $des){
		foreach($this->getServer()->getSynapse()->getClientData() as $cdata){
			if($cdata["description"] == $des){
				return $cdata;
			}
		}
		return null;
	}

	/**
	 * @param string $des
	 * @return null|string
	 */
	public function getClientHashByDescription(string $des){
		foreach($this->getServer()->getSynapse()->getClientData() as $hash => $cdata){
			if($cdata["description"] == $des){
				return $hash;
			}
		}
		return null;
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		switch(strtolower($command->getName())){
			case "scid":
				if($command->testPermission($sender)){
					if(!isset($args[0]) or (strtolower($args[0]) != "add" and strtolower($args[0]) != "remove")){
						return false;
					}
					switch(strtolower($args[0])){
						case "add":
							if(isset($args[1]) and isset($args[2])){
								$this->list[$args[1]] = $args[2];
								$sender->sendMessage(TextFormat::GREEN . "$args[1] => $args[2]");
							}
							$sender->sendMessage(TextFormat::RED . "Missing arguments");
							break;
						case "remove":
							if(isset($this->list[$args[1]])){
								unset($this->list[$args[1]]);
								$sender->sendMessage(TextFormat::GREEN . "$args[1] has been removed successfully");
							}
							$sender->sendMessage(TextFormat::RED .  "$args[1] does not exist");
							break;
					}
				}else{
					$sender->sendMessage(TextFormat::RED . "You don't have permission to execute this command");
				}
				return true;
			case "listclients":
				if($command->testPermission($sender)){
					foreach($this->list as $c){
						if(($data = $this->getClientDataByDescription($c)) != null){
							$sender->sendMessage("ID: $c Status: " . TextFormat::GREEN .
								"Online" . TextFormat::WHITE . " Players: " . TextFormat::GREEN . "{$data["playerCount"]}" .
								TextFormat::WHITE . "/" . TextFormat::YELLOW . "{$data["maxPlayers"]}");
						}else{
							$sender->sendMessage("ID: $c Status: " . TextFormat::RED .
								"Offline");
						}
					}
				}else{
					$sender->sendMessage(TextFormat::RED . "You don't have permission to execute this command");
				}
				return true;
			case "transfer":
				if($command->testPermission($sender)){
					if(count($args) == 2){
						if(strtolower($args[0]) == strtolower($sender->getName())){
							$player = $this->getServer()->getPlayerExact($args[0]);
							if($player instanceof Player and ($hash = $this->getClientHashByDescription($args[0])) != null){
								$player->transfer($hash);
								$this->getServer()->broadcastMessage($player->getName() . "has been transferred to $hash");
							}
						}
					}elseif(count($args) == 1){
						if(($hash = $this->getClientHashByDescription($args[0])) != null){
							if($sender instanceof Player){
								$sender->transfer($hash);
								$this->getServer()->broadcastMessage($sender->getName() . "has been transferred to $hash");
							}else{
								$sender->sendMessage(TextFormat::RED . "You must be a SynapsePlayer to execute this command");
							}
						}
					}else{
						return false;
					}
				}else{
					$sender->sendMessage(TextFormat::RED . "You don't have permission to execute this command");
				}
				return true;
		}
		return false;
	}
}