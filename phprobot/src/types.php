<?php
	if (!defined('GB_SYSTEM_LODADED')) die('Se requiere el sistema de GenericBot');

	require_once(dirname(__FILE__) . '/system.php');

	require_class('Timer');

///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////

	class IdList {
		public $o;
		public $id;
		public $register;
		public $visible;
		public $online = false;

		function trace() {
			$o = &$this->o;
			unset($this->o);
			print_r($this);
			$this->o = $o;
		}

		function __construct(GenericBot &$o, $id, $register = true) {
			$this->id = $id;
			if ($this->register = $register) $this->o = $o;

			$this->setOnList(true);
			$this->visible = false;
			$this->online  = true;
		}

		function appear() {
			$this->setOnList(true);
		}

		function disappear() {
			$this->setOnList(false);
		}

		function setOnList($flag) {
			$this->visible = $flag;

			if ($this->register) {
				if ($flag) {
					// A�ade a la lista de visibles y de memorizados
					if (($gpc = get_parent_class($this)) != __CLASS__) {
						$z = &$this->o->lists[$gpc]; if (!isset($z)) $z = array();
						$this->o->lists[$gpc][$this->id] = &$this;

						$z = &$this->o->lists[$gpc . '_memo']; if (!isset($z)) $z = array();
						$this->o->lists[$gpc . '_memo'][$this->id] = &$this;
					}

					$z = &$this->o->lists[$gpc]; if (!isset($z)) $z = array();
					$this->o->lists[get_class($this)][$this->id]      = &$this;

					$z = &$this->o->lists[$gpc . '_memo']; if (!isset($z)) $z = array();
					$this->o->lists[get_class($this) . '_memo'][$this->id] = &$this;
				} else {
					// Borra solo de la lista de visibles
					if (($gpc = get_parent_class($this)) != __CLASS__) {
						unset($this->o->lists[get_class($this)][$this->id]);
					}

					unset($this->o->lists[get_class($this)][$this->id]);
				}
			}
		}

		function __destruct() {
			if ($this->register) {
				if (isset($this->o)) {
					// Lo borra de la lista de visibles y de memorizados
					if (($gpc = get_parent_class($this)) != __CLASS__) {
						unset($this->o->lists[$gpc][$this->id]);
						unset($this->o->lists[$gpc . '_memo'][$this->id]);
					}

					unset($this->o->lists[get_class($this)][$this->id]);
					unset($this->o->lists[get_class($this) . '_memo'][$this->id]);
				}
			}
		}
	}

///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////

	class Entity extends IdList {
		public static $sensibleSimilar = 30;

		// ETC. (TODO)
		const player  = 0;
		const monster = 1;
		const npc     = 2;
		const pet     = 3;
		const flag    = 4;
		const warp    = 5;

		public $type;

		public $_name;
		//protected $_name;

		// Movimiento de la entidad
		public $path = array();
		public $moving = false;
		public $from_x = 0;
		public $from_y = 0;
		public $from_time;
		public $to_x = 0;
		public $to_y = 0;
		public $to_time_t;

		public $map_name;
		public $map_x = -1;
		public $map_y = -1;

		public $x;
		public $y;
		public $view_class;
		public $group;
		public $speed;

		public $hp     = -1;
		public $hp_max = -1;
		public $sp     = -1;
		public $sp_max = -1;

		public $guild  = NULL;
		public $emblem = NULL;
		public $party  = NULL;
		public $party_leader = false;

		function __construct(GenericBot &$o, $id, $register = true) {
			parent::__construct($o, $id, $register);

			if (isset($this->o) && $this->o instanceof GenericBot) {
				$this->map_name = $this->o->map->name;
			}
		}

		function same_map(Entity &$e) {
			return ($this->map_name == $e->map_name);
		}

		function current_map() {
			return ($this->map_name == $this->o->map->name);
		}

		// Check name from list
		function is($namel) {
			if (!is_array($namel)) $namel = array($namel);
			foreach ($namel as $name) if (strcasecmp($name, $this->name) == 0) return true;
			return false;
		}

		function trace() {
			$o1 = &$this->emblem; $this->emblem = NULL;
			$o2 = &$this->guild;  $this->guild  = NULL;
			$o3 = &$this->party;  $this->party  = NULL;

			parent::trace();

			$this->emblem = $o1;
			$this->guild  = $o2;
			$this->party  = $o3;
		}

		function setXY($x, $y) {
			$this->to_x = $this->from_x = $this->x = $x;
			$this->to_y = $this->from_y = $this->y = $y;
			$this->map_y = $this->map_x = -1;
		}

		function setXYMap($x, $y) {
			$this->map_x = $x;
			$this->map_y = $y;
		}

		function move($from_x = NULL, $from_y = NULL, $to_x, $to_y, $speed = NULL) {
			if (!isset($from_x)) $from_x = $this->x;
			if (!isset($from_y)) $from_y = $this->y;
			if (!isset($speed))  $speed  = $this->speed;

			$this->path = (isset($this->o) && isset($this->o->map)) ? $this->o->map->getPath($from_x, $from_y, $to_x, $to_y) : array();

			$this->from_time = new Timer();
			$this->to_time_t = ($speed * sizeof($this->path));
			$this->moving    = true;
			$this->from_x    = $this->x = $from_x;
			$this->from_y    = $this->y = $from_y;
			$this->to_x      = $to_x;
			$this->to_y      = $to_y;
			$this->visible   = true;

			$this->map_y     = $this->map_x = -1;

			$this->appear();

			if (isset($this->o)) $this->o->onMoveStart($this);

			//$this->trace(); exit; // Debug
		}

		function __destruct() {
			if ($this->register) {
				if (isset($this->view_class)) {
					unset($this->o->lists['Entity_view_class_name'][$this->view_class]);
				}

				if (isset($this->_name)) {
					unset($this->o->lists['Entity_name'][strtolower(trim($this->_name))]);
				}

				parent::__destruct();
			}
		}

		function __set($name, $val) {
			if ($this->register) {
				switch ($name) {
					case 'name':
						$entity_name_list = &$this->o->lists['Entity_name_list']; $entity_view_class_name_list = &$this->o->lists['Entity_view_class_name_list'];
						if ($this->view_class > 1000) {
							$entity_view_class_name_list[$this->view_class] = $val;
						} else {
							if (isset($this->_name) && isset($entity_name_list[$ns = strtolower(trim($this->_name))])) unset($entity_name_list[$ns]);

							$entity_name_list[strtolower(trim($this->_name = $val))] = $this->id;
						}
					break;
					default:
						$this->$name = $val;
					break;
				}
			} else {
				$this->$name = $val;
			}
		}

		function __get($name) {
			if ($this->register) {
				switch ($name) {
					case 'name':
						if (isset($this->_name)) return $this->_name;

						$entity_name_list = &$this->o->lists['entity_name_list']; $entity_view_class_name_list = &$this->o->lists['entity_view_class_name_list'];
						if (isset($entity_view_class_name_list[$this->view_class])) {
							$this->_name = $entity_view_class_name_list[$this->view_class];
						} else {
							// Se pide el nombre de la entidad
							sendGetEntityName($this->o, $this->id);
							$entity_name_list[$this->_name = 'unknown_' . $this->id] = $this->id;
						}
					break;
				}

				return isset($this->$name) ? $this->$name : false;
			}
		}

		static function getEntityById(GenericBot &$o, $id) {
			$z = &$o->lists['Entity_memo'][$id];
			return isset($z) ? $z : false;
		}

		static function getEntityByIdCreate(GenericBot &$o, $id) {
			$z = &$o->lists['Entity_memo'][$id];
			if (!isset($z)) $z = new Entity($o, $id);
			return $z;
		}

		static function getEntityBySimilarName(GenericBot &$o, $name) {
			foreach (Entity::entitySimilar($o, $name) as $v) {
				return ($v[1] > Entity::$sensibleSimilar) ? $v[0] : false;
			}

		}

		static function getEntityByName(GenericBot &$o, $name) {
			if (isset($o->lists['Entity_name_list'][strtolower(trim($name))])) {
				return entity::getEntityById(
					$o,
					$o->lists['Entity_name_list'][strtolower(trim($name))]
				);
			}

			return false;
		}

		static function getNameById(GenericBot &$o, $id) {
			return ($z = entity::getEntityById($o, $id)) ? $z->name : false;
		}

		static function deleteAll(GenericBot &$o) {
			foreach (get_declared_classes() as $class) {
				if ($class == __CLASS__ || get_parent_class($class) == __CLASS__) {
					if (isset($o->lists[$class . '_memo'])) {
						foreach ($o->lists[$class . '_memo'] as $k => $myo) {
							unset($o->lists[$class . '_memo'][$k]);
							unset($myo);
						}
					}

					if (isset($o->lists[$class])) {
						foreach ($o->lists[$class] as $k => $myo) {
							unset($o->lists[$class][$k]);
							unset($myo);
						}
					}
				}
			}
		}

		// EXTRA (FIX)
		// array(array(int, Entity), ...)
		static function entitySimilar(GenericBot &$o, $name) {
			//echo "Similar List:\n";
			$name = strtolower(trim($name));
			$z = &$o->lists['Entity_memo'];
			$return   = array();
			$percents = array();
			$entities = array();
			$fixn = 0;

			foreach ($z as $k => $e) {
				similar_text(strtolower(trim($e->name)), $name, $per);

				$per *= $e->current_map() ? $e->visible ? 1 : 0.75 : 0.50;

				//$per *= $e->visible ? 1 : 0.75;
				//$per *= $e->visible ? 1 : 0.5;
				/*
				if ($e->visible) {
					$dist = sqrt(pow($o->player->x - $e->x, 2) + pow($o->player->y - $e->y, 2));
					if ($dist > 5) $per /= ($dist / 5);
				}
				*/

				//echo "$per -> " . $e->name . "\n";

				$entities['f' . (string)$fixn] = $e;
				$percents['f' . (string)$fixn] = round($per, 3);
				$fixn++;
				//echo "Similar-Entity: " . $e->name . ' - ' . round($per, 2) . "\n";
			}
			//echo "END Similar List:\n";

			arsort($percents);
			$percents = array_slice($percents, 0, 5);
			foreach ($percents as $k => $percent) $return[] = array(&$entities[$k], $percent);

			return $return;
		}
	}

///////////////////////////////////////////////////////////////////////////////

	class Emblem extends IdList {
		public $data;

		static function getEmblemByIdCreate(GenericBot &$o, $id) {
			$z = &$o->lists['Emblem_memo'][$id];
			if (!isset($z)) $z = new Guild($o, $id);
			return $z;
		}
	}

///////////////////////////////////////////////////////////////////////////////

	class Guild extends IdList {
		public $name;
		public $emblem;
		public $member_list = array();

		function trace() {
			$o1 = &$this->emblem;      $this->emblem      = NULL;
			$o2 = &$this->member_list; $this->member_list = NULL;
			parent::trace();
			$this->emblem = $o1;
			$this->member_list = $o2;
		}

		static function getGuildByIdCreate(GenericBot &$o, $id) {
			$z = &$o->lists['Guild_memo'][$id];
			if (!isset($z)) $z = new Guild($o, $id);
			return $z;
		}
	}

	class Party extends IdList {
		public $name;
		public $share_exp;
		public $share_item;
		public $member_list = array();

		function getMemberNameList() {
			$list = array();
			foreach ($this->member_list as $member) $list[] = $member->name;
			return $list;
		}

		function trace() {
			$o2 = &$this->member_list; $this->member_list = NULL;
			parent::trace();
			$this->member_list = $o2;
		}

		static function getPartyByIdCreate(GenericBot &$o, $id) {
			$z = &$o->lists['Party_memo'][$id];
			if (!isset($z)) $z = new Party($o, $id);
			return $z;
		}
	}

///////////////////////////////////////////////////////////////////////////////

	class Player extends Entity {
		public $head;
		public $body;
	}

///////////////////////////////////////////////////////////////////////////////

	class MainPlayer extends Entity {
		public $hp;
		public $hp_max;
		public $sp;
		public $sp_max;
		public $flee;
		public $head;
		public $body;

		public $party;

		function trace() {
			$o1 = &$this->items; $this->items = NULL;
			$o2 = &$this->equip; $this->equip = NULL;
			parent::trace();
			$this->items = $o1;
			$this->equip = $o2;
		}
	}

///////////////////////////////////////////////////////////////////////////////

	class Npc     extends Entity { }
	class Monster extends Entity { }
	class Pet     extends Entity { }
	class Portal  extends Entity { }

///////////////////////////////////////////////////////////////////////////////

	class Skill extends IdList {
		public static $sensibleSimilar = 40;

		protected $_name = NULL;
		protected $_title;

		public $target;
		public $level_max;
		public $sp_max;
		public $range;
		public $canup;

		//function __construct() { }

		function __destruct() {
			if ($this->register) {
				unset($this->o->lists['Skill_name'][strtolower(trim($this->_title))]);
				unset($this->o->lists['Skill_title'][strtolower(trim($this->_name))]);

				parent::__destruct();
			}
		}

		function __toString() {
			return $this->title;
		}

		function __set($name, $val) {
			if ($this->register) {
				switch ($name) {
					case 'name':
						$vr = strtolower(trim($val));
						$skill_name_list = &$this->o->lists['Skill_name_list'];
						if (!isset($this->_name)) unset($skill_name_list[$vr]);
						$this->_name = $val;
						$skill_name_list[$vr] = &$this;
					break;
					case 'title':
						$vr = strtolower(trim($val));
						$skill_title_list = &$this->o->lists['Skill_title_list'];
						if (!isset($this->_name)) unset($skill_title_list[$vr]);
						$this->_title = $val;
						$skill_title_list[$vr] = &$this;
					break;
					default:
						$this->$name = $val;
					break;
				}
			}
		}

		function __get($name) {
			if ($this->register) {
				switch ($name) {
					case 'name':  return $this->_name; break;
					case 'title': return $this->_title; break;
				}

				return $this->$name;
			}
		}

		static function getSkillById(GenericBot &$o, $id)    { $z = &$o->lists['Skill_memo'][$id]; if (isset($z)) return $z; throw(new Exception()); }
		static function getSkillByTitle(GenericBot &$o, $id) { $z = &$o->lists['Skill_title'][strtolower(trim($id))]; if (isset($z)) return $z; throw(new Exception()); }
		static function getSkillByName(GenericBot &$o, $id)  { $z = &$o->lists['Skill_name'][strtolower(trim($id))]; if (isset($z)) return $z; throw(new Exception()); }
		static function getSkillBySimilarName(GenericBot &$o, $title) {
			//foreach ($o->lists as $k => $v) echo "-> $k\n";
			$z = &$o->lists['Skill'];
			//if (!isset($z)) $z = array();
			$pera = Skill::$sensibleSimilar; // Min %
			foreach ($z as $k => $s) {
				foreach (array($s->title, $s->name, $s->id) as $check) {
					similar_text(strtolower(trim($check)), $title, $per);
					if ($per > $pera) { $pera = $per; $return = $s; }
				}
			}

			if (!isset($return)) throw(new Exception());

			//echo 'SKILL: ' . $return->name . "\n";

			return $return;
		}

		static function load(GenericBot &$o, $file) {
			$z = &$o->lists['Skill_memo'];  $skill_memos  = &$z; if (!isset($z)) $z = array();
			$z = &$o->lists['Skill_name'];  $skill_names  = &$z; if (!isset($z)) $z = array();
			$z = &$o->lists['Skill_title']; $skill_titles = &$z; if (!isset($z)) $z = array();
			$z = &$o->lists['Skill_delay']; $skill_delays = &$z; if (!isset($z)) $z = array();

			if (file_exists($file) && is_readable($file)) {
				foreach (file($file) as $line) {
					$line = trim($line);
					if (strlen($line) == 0 || substr($line, 0, 1) == "'" || substr($line, 0, 1) == '#' || substr($line, 0, 2) == '//') continue;
					$opts = explode(' ', $line, 3); $id = $opts[0];

					$w = explode('#', $opts[1], 2);
					$opts[1] = $w[0];
					if (!isset($w[1])) $w[1] = 0;

					$skill_titles[$id] = $opts[2];
					$skill_names[$id]  = $opts[1];
					$skill_delays[$id] = $w[1];
					define('SKILL_' . $opts[1], $id);
				}
			}
		}
	}

///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////

?>