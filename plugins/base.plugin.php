<?php
class Base_Plugin {
	public $sock;
	public $bot;
	public function __construct($bot, $irc){
		$this->bot = $bot;
		$this->irc = $irc;
		$this->setup();
	}
	public function setup(){
		if(!$this->bot->isCommand("help")){
			$this->bot->addCommand("help", "Shows commands and how to use them", "[<command>]", USER_LEVEL_GLOBAL);
		}
		$this->bot->addCommand("userlevel", "Shows a users bot control level", "[<user>]", USER_LEVEL_GLOBAL);
		$this->bot->addCommand("set", "Set a property", "<property> <value>", USER_LEVEL_OWNER);
		$this->bot->addCommand("owners", "List owners", "", USER_LEVEL_GLOBAL);
		$this->bot->addCommand("admins", "List admins", "", USER_LEVEL_GLOBAL);
		$this->bot->addCommand("moderators", "List moderators", "", USER_LEVEL_GLOBAL);
		$this->bot->addCommand("owners", "List or change owners", "[add/remove] [<user>]", USER_LEVEL_OWNER);
		$this->bot->addCommand("admins", "List or change admins", "[add/remove] [<user>]", USER_LEVEL_OWNER);
		$this->bot->addCommand("moderators", "List or change moderators", "[add/remove] [<user>]", USER_LEVEL_ADMIN);
	}
	public function onLoop(){}
	public function onNick($user, $new, $hostmask){}
	public function onMode($message, $command, $user, $channel, $hostmask){}
	public function onJoin($message, $command, $user, $channel, $hostmask){}
	public function onPart($message, $command, $user, $channel, $hostmask){}
	public function onKick($message, $command, $user, $channel, $hostmask){}
	public function onCommand($message, $command, $user, $channel, $hostmask){
		$count = 1;
		$argument = explode(" ", trim(substr($message, strlen($this->bot->prefix.$command))));
		$userLevel = $this->bot->getUserLevel($user, $hostmask);

		if(!$this->bot->isCommand($command, USER_LEVEL_OWNER)){
			$this->irc->sendMessage($channel, "$user: Command '$command' does not exist");
			return;
		}

		if($this->bot->getCommandMinimumLevel($command) > $userLevel){
			$this->irc->sendMessage($channel, $user.": You are not authorized to perform '$command'");
			return;
		}

		switch($command){
			case "userlevel":
				if(is_array($argument) && !empty($argument[0])){
					// Check for target user in our channels
					foreach($this->irc->users as $userChannel => $users){
						if(!array_key_exists($argument[0], $this->irc->users[$userChannel])){
							continue;
						}
						$userLevel = $this->bot->getUserLevel($argument[0], $this->irc->users[$userChannel][$argument[0]]);
						$this->irc->sendMessage($channel, $user.": $argument[0]'s bot control level is: $userLevel");
						return;
					}
					$this->irc->sendMessage($channel, "$user: Unknown user $argument[0]");
				}else{
					$this->irc->sendMessage($channel, $user.": Your bot control level is: $userLevel");
				}
			break;
			case "set":
				switch($argument[0]){
					case "autoOP":
						if(isset($argument[1])){
							$arg1 = trim($argument[1]);
							if($arg1 == "true" || $arg1 == "1"){
								$this->bot->setPluginProperty("OP_Plugin", "autoOP", true);
							}else{
								$this->bot->setPluginProperty("OP_Plugin", "autoOP", false);			}
						}
					break;
				}
				$passedVars = array(
					"message" => $message,
					"command" => $command,
					"user" => $user,
					"channel" => $channel,
					"hostmask" => $hostmask
				);
				$this->bot->triggerEvent("onSet", $passedVars);
			break;
			case "owners":
				if(is_array($argument) && !empty($argument[0])){
					if($userLevel < USER_LEVEL_OWNER){
						$this->irc->sendMessage($channel, $user.": You are not authorized to add/remove owners");
						return;
					}
					if(empty($argument[1])){
						$this->irc->sendMessage($channel, "$user: Please specify a user");
						return;
					}
					$owners = explode(",", substr($message, strlen($this->irc->prefix.$command." ".$argument[0]." ")));
					switch($argument[0]){
						case "add":
							foreach($owners as $owner){
								$owner = trim($owner);
								foreach($this->irc->users as $userChannel => $users){
									if(array_key_exists($owner, $this->irc->users[$userChannel])){
										$this->bot->owners[$owner] = $this->irc->users[$userChannel][$owner];
										$this->irc->sendMessage($channel, "$user: $owner!".$this->irc->users[$userChannel][$owner]." added to owners list");
										return;
									}
								}
								$this->irc->sendMessage($channel, "$user: Unknown user '$owner'");
							}
							break;
						case "remove":
							foreach($owners as $owner){
								$owner = trim($owner);
								$this->irc->sendMessage($channel, "$user: $owner!".$this->bot->owners[$owner]." removed from owners list");
								unset($this->bot->owners[$owner]);
							}
							break;
						default:
								$this->irc->sendMessage($channel, "$user: Unknown argument '$argument[1]'");
							break;
					}
				}else{
					$msg = "Owners: ";
					foreach($this->bot->owners as $owner => $hostmask){
						$msg .= $owner;
						$msg .= " ";
					}
					$this->irc->sendMessage($channel, "$user: $msg");
				}
			break;
			case "admins":
				if(is_array($argument) && !empty($argument[0])){
					if($userLevel < USER_LEVEL_OWNER){
						$this->irc->sendMessage($channel, $user.": You are not authorized to add/remove admins");
						return;
					}
					if(empty($argument[1])){
						$this->irc->sendMessage($channel, "$user: Please specify a user");
						return;
					}
					$admins = explode(",", substr($message, strlen($this->irc->prefix.$command." ".$argument[0]." ")));
					switch($argument[0]){
						case "add":
							foreach($admins as $admin){
								$admin = trim($admin);
								foreach($this->irc->users as $userChannel => $users){
									if(array_key_exists($admin, $this->irc->users[$userChannel])){
										$this->bot->owners[$admin] = $this->irc->users[$userChannel][$owner];
										$this->irc->sendMessage($channel, "$user: $admin!".$this->irc->users[$userChannel][$admin]." added to admin list");
										return;
									}
								}
								$this->irc->sendMessage($channel, "$user: Unknown user '$admin'");
							}
							break;
						case "remove":
							foreach($admins as $admin){
								$admin = trim($admin);
								$this->irc->sendMessage($channel, "$user: $admin!".$this->bot->admin[$admin]." removed from admin list");
								unset($this->bot->admins[$admin]);
							}
							break;
						default:
								$this->irc->sendMessage($channel, "$user: Unknown argument '$argument[1]'");
							break;
					}
				}else{
					$msg = "Admins: ";
					foreach($this->bot->admins as $admin => $hostmask){
						$msg .= $admin;
						$msg .= " ";
					}
					$this->irc->sendMessage($channel, "$user: $msg");
				}
			break;
			case "moderators":
				if(is_array($argument) && !empty($argument[0])){
					if($userLevel < USER_LEVEL_OWNER){
						$this->irc->sendMessage($channel, $user.": You are not authorized to add/remove moderators");
						return;
					}
					if(empty($argument[1])){
						$this->irc->sendMessage($channel, "$user: Please specify a user");
						return;
					}
					$mods = explode(",", substr($message, strlen($this->irc->prefix.$command." ".$argument[0]." ")));
					switch($argument[0]){
						case "add":
							foreach($mods as $mod){
								$mod = trim($mod);
								foreach($this->irc->users as $userChannel => $users){
									if(array_key_exists($mod, $this->irc->users[$userChannel])){
										$this->bot->moderators[$mod] = $this->irc->users[$userChannel][$mod];
										$this->irc->sendMessage($channel, "$user: $mod!".$this->irc->users[$userChannel][$mod]." added to moderators list");
										return;
									}
								}
								$this->irc->sendMessage($channel, "$user: Unknown user '$mod'");
							}
							break;
						case "remove":
							foreach($mods as $mod){
								$mod = trim($mod);
								$this->irc->sendMessage($channel, "$user: $mod!".$this->bot->moderators[$mod]." removed from moderators list");
								unset($this->irc->moderators[$mod]);
							}
							break;
						default:
							$this->irc->sendMessage($channel, "$user: Unknown argument '$argument[1]'");
							break;
					}
				}else{
					$msg = "Moderators: ";
					foreach($this->bot->moderators as $moderator => $hostmask){
						$msg .= $moderator;
						$msg .= " ";
					}
					$this->irc->sendMessage($channel, "$user: $msg");
				}
			break;
			case "ping":
				$running = round(microtime(true) - $this->bot->start_time);
				$commit = @exec("git log -n 1 --pretty=format:'%h'");
				$this->irc->sendMessage($channel, "$user: ".BOT." version ".VERSION."; commit $commit; uptime ".$running."s.");
			break;
			case "help":
				if(is_array($argument) && !empty($argument[0])){
					if(!$this->bot->isCommand($argument[0], USER_LEVEL_OWNER)){
						$this->irc->sendMessage($channel, "$user: Command '$argument[0]' does not exist");
						return;
					}
					if($this->bot->getCommandMinimumLevel($argument[0]) > $userLevel){
						$this->irc->sendMessage($channel, $user.": You are not authorized to perform '$argument[0]'");
						return;
					}
					$usage = $this->bot->getCommandUsage($argument[0], $userLevel);
					$description = $this->bot->getCommandDescription($argument[0], $userLevel);
					$this->irc->sendMessage($channel, "$user: ".$this->bot->prefix."$argument[0] $usage");
					$this->irc->sendMessage($channel, "$user: $description");
				}else{
					$msg = "Available commands: ";

					$userLevel = $this->bot->getUserLevel($user, $hostmask);

					foreach($this->bot->commands as $command => $levels){
						if($this->bot->isCommand($command, $userLevel)){
							$msg .= $this->bot->prefix.$command;
							$msg .= " ";
						}
					}
					$this->irc->sendMessage($channel, "$user: ".$msg);
				}
			break;
		}
	}
	public function onMessage($message, $command, $user, $channel, $hostmask){}
	public function onTopic($message, $command, $user, $channel, $hostmask){}
}
