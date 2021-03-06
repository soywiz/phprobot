<?php
	require_once(dirname(__FILE__) . '/src/class.GenericBot.php');

	class TestBot extends GenericBot {
		private $config;

//-----------------------------------------------------------------------------
// CONSTRUCTOR
//-----------------------------------------------------------------------------

		function __construct($file_ini = 'TestBot.ini') {
			if (!file_exists($file_ini = dirname(__FILE__) . '/' . $file_ini)) throw(new Exception("Cant't find {$file_ini} "));

			$ini = array_change_key_case(parse_ini_file($file_ini, true), CASE_LOWER);

			$this->config = array();
			$z = &$ini['simplebot']['host'];        $this->config['host']   = isset($z) ? $z : 'localhost';
			$z = &$ini['simplebot']['port'];        $this->config['port']   = isset($z) ? $z : 6900;
			$z = &$ini['simplebot']['user'];        $this->config['user']   = isset($z) ? $z : 'test2';
			$z = &$ini['simplebot']['password'];    $this->config['pass']   = isset($z) ? $z : 'test2';
			$z = &$ini['simplebot']['overwriteip']; $this->config['owip']   = isset($z) ? $z : false;
			$z = &$ini['simplebot']['server'];      $this->config['server'] = isset($z) ? $z : 'wiz_server';
			$z = &$ini['simplebot']['chara'];       $this->config['chara']  = isset($z) ? $z : 'nena';
			$z = &$ini['map']['master_user'];       $this->config['muser']  = isset($z) ? $z : 'thewiz';

			$m = strtolower(trim($this->config['owip']));
			$this->overwriteIp = $this->config['owip'] = ($m == 'false' || $m == '0' || $m == '') ? false : true;
			$this->config['muser'] = strtolower($this->config['muser']);

			echo "* __contruct();\n";
			parent::__construct();
		}

//-----------------------------------------------------------------------------
// CONNECTION
//-----------------------------------------------------------------------------

		function onDisconnect() {
			echo "* onDisconnect();\n";
			//echo "Ha sido desconectado del servidor...\nReconectando dentro de 10 segundos...\n"; sleep(10);
			$this->reconnect();
		}

//-----------------------------------------------------------------------------
// MASTER
//-----------------------------------------------------------------------------

		function onMasterLogin() {
			echo "* onMasterLogin();\n";
			$this->masterConnect($this->config['host'] . ':' . $this->config['port'], $this->config['user'], $this->config['pass']);
		}

		function onMasterLoginError() {
			echo "* onMasterLoginError();\n";
			$this->disconnect();
		}

//-----------------------------------------------------------------------------
// CHARA
//-----------------------------------------------------------------------------

		function onCharaLogin() {
			echo "* onCharaLogin();\n";
			// $this->serverList
			$this->serverCharaSelect($this->config['server']);
		}

		function onCharaSelectError() {
			echo "* onCharaSelectError();\n";
			$this->disconnect();
		}

		function onCharaSelect() {
			echo "* onCharaSelect();\n";
			// $this->characterList
			//$this->charaSelect('Nena');
			$this->charaSelect($this->config['chara']);
		}

//-----------------------------------------------------------------------------
// MAP
//-----------------------------------------------------------------------------

		function onMapStart() {
			echo "* onMapStart();\n";
			//echo "Iniciar Mapa\n";
			//sendSkillUse($this, SKILL_AL_HEAL, 10, Entity::getEntityById)
		}

		function onCharaInfoUpdate() {
			echo "* onCharaInfoUpdate();\n";
			// Show updated info
			//$this->player->trace();
			//print_r($this->characterSelected);
		}

		function onDisAppear(Entity &$e) {
			echo "* onDisAppear();\n";
		}

		function onAppear(Entity &$e) {
			echo "* onAppear();\n";
			$name = strtolower(trim($e->name));
			if ($name == $this->config['muser']) {
				/*
				//$this->useSkill(SKILL_AL_HEAL,     10, $e);
				$this->useSkill(SKILL_AL_INCAGI,   10, $e);
				$this->useSkill(SKILL_AL_INCAGI,   10, $e);
				$this->useSkill(SKILL_AL_INCAGI,   10, $e);
				//$this->useSkill(SKILL_AL_ANGELUS,  10, $e);
				$this->useSkill(SKILL_AL_BLESSING, 10, $e);
				$this->useSkill(SKILL_PR_IMPOSITIO, 5, $e);
				*/

				$this->lookAt($e);
			}
		}

		function onSay($type, $text, &$from = NULL, $from_name = NULL) {
			echo "* onSay();\n";
			if (isset($from) && !($from instanceof Entity)) return;
			if (!isset($from_name) && isset($from)) $from_name = $from->name;

			//echo "TYPE: $type, TEXT: $text"; if (isset($from)) echo ", FROM: (" . $from_name . ")"; echo "\n";

			if (strtolower(trim($from_name)) == $this->config['muser']) {
				$data = explode(' ', $text, 2);
				if (!isset($data[1])) $data[1] = ''; else $data[1] = ltrim($data[1]);

				$command = getSimilarValue(
					array(
						'exit', 'say', 'move', 'where', 'skill', 'similar', 'look'
					),
					$data[0],
					85
				);

				echo "COMMAND: {$command}\n";

				switch (strtolower(trim($command))) {
					case 'exit': exit; break;
					case 'say':  $this->say($data[1]); break;
					//case 'move': $this->moveAt($from->x, $from->y); break;
					//case 'move_near': $this->moveNear($from, 4); break;
					case 'move':
						$s = &$data[1];
						echo "MOVE: $s\n";
						if (trim($s)) {
							$this->moveNear($e = Entity::getEntityBySimilarName($this, $s), 4);
							echo "MOVE: $e";
							if ($e instanceof Entity) echo "-> " . $e->name;
							echo "\n";
						} else {
							$this->moveNear($from, 4);
						}
					break;
					case 'where':
						$to_say = $this->map->name . ' : ' . $this->player->x . ', ' . $this->player->y;
						echo $to_say . "\n";
						$this->say($to_say);
					break;
					case 'skill':
						Skill::$sensibleSimilar = 20;
						$s = &$data[1];
						try {
							$this->useSkill($sk = Skill::getSkillBySimilarName($this, $s), 10, $from);
							echo "Must use SKILL: " . $sk->name . "\n";
						} catch (Exception $e) {
							echo "Don't have skill\n";
						}
					break;
					case 'similar':
						foreach (Entity::entitySimilar($this, $data[1]) as $v) {
							list($e, $per) = $v;
							echo "$per. " . $e->name . "\n";
						}
					break;
					case 'look':
						foreach (Entity::entitySimilar($this, $data[1]) as $v) {
							if ($v[1] > 15) {
								$this->lookAt($v[0]);
								echo 'Looking...' . $v[0]->name . " - " . $v[1] . "\n";
							} else {
								echo "Can't Look ??\n";
							}
							break;
						}
					break;
					default:
						Skill::$sensibleSimilar = 44;
						try {
							$this->useSkill($sk = Skill::getSkillBySimilarName($this, $command), 10, $from);
							echo "Must use SKILL: " . $sk->name . "\n";
						} catch (Exception $e) {
							echo "I dont understand you\n";
						}
					break;
				}
			}
		}

//-----------------------------------------------------------------------------
// MAP
//-----------------------------------------------------------------------------

		function onMoving(Entity &$e) {
			if (!isset($this->myMoveTick)) $this->myMoveTick = 0;

			echo "* onMoving();\n";
			//echo $e->id . ': ' . $e->x . ', ' . $e->y . ' - (' . $e->name . ')' . "\n";
			//$this->lookAt($e);
			//$this->moveNear($e);
			if ($e->visible) {
				if (strtolower($e->name) == $this->config['muser']) {
					if ($this->myMoveTick++ >= 3) {
						$this->moveNear($e, 4);
						$this->myMoveTick = 0;
					}
				}
			} else {
				if (strtolower($e->name) == $this->config['muser']) {
					$this->moveNear($e, 4);
				}
				//$e->trace();
			}
		}

		function onUpdateHP(Entity &$e) {
			if (isset($e) && (($e->is($this->config['muser'])) || ($e === $this->player))) {
				if ((($e->hp * 100) / $e->hp_max) < 75) {
					$this->useSkill($sk = Skill::getSkillBySimilarName($this, 'heal'), 10, $e);
				}
			}
			echo $e->name . ' - ' . $e->hp . "/" . $e->hp_max . "\n";
		}

		function onMoveStart(Entity &$e) {
			echo "* onMoveStart();\n";
			//$this->moveNearTo($e->to_x, $e->to_y);
			//$this->moveNearTo($e->to_x, $e->to_y, 1);
			//echo $e->id . ': Moving Start (' . $e->x . ', ' . $e->y . ')'. "\n";
		}

		function onMoveEnd(Entity &$e) {
			// Si ya ha acabado su propio movimiento
			if ($e === $this->player) {
				if ($e = &Entity::getEntityByName($this, $this->config['muser'])) {
					if ($e->visible) $this->lookAt($e);
				}
			} else if (strtolower($e->name) == $this->config['muser']) {
				if ($e->visible) $this->lookAt($e);
			}

			echo "* onMoveEnd();\n";
			//echo $e->id . ': Moving Start End (' . $e->x . ', ' . $e->y . ')'. "\n";
			// Mira al personaje
			//$this->lookAt($e);
			//$this->moveNearTo($e->to_x, $e->to_y, 1);
		}

		function onTradeRequest(Entity &$e, $from_name) {
			echo "Request from: {$from_name}\n";
			if (isset($e) && $e->is($this->config['muser'])) {
				$this->tradeRequestAccept();
			} else {
				$this->tradeCancel();
			}
		}

		function onTradeStart(Entity &$e, $from_name) {
			echo "Trade Start: {$from_name}\n";
			$this->tradeZeny(0);
			$this->tradeOk();
		}

		function onTradeCancel(Entity &$e, $from_name, $error) {
			echo "Trade Cancel: {$from_name}, {$error}\n";
		}

		function onTradeSuccess(Entity &$e, $from_name) {
			echo "Trade Success: {$from_name}\n";
		}

		function onCrossPortal(Entity &$e, Portal &$p) {
			if ($e->is($this->config['muser'])) {
				$this->moveAt($p);
			}
		}

		function onSit(Entity &$e)   { echo "SIT: " . $e->name . "\n"; }
		function onStand(Entity &$e) { echo "STAND: " . $e->name . "\n"; }
	}

//-----------------------------------------------------------------------------
// MAIN
//-----------------------------------------------------------------------------

	$mybot = new TestBot();
	while (true) {
		try {
			$mybot->process();
		} catch (Exception $e) {
			echo $e;
		}
	}

	exit;
?>