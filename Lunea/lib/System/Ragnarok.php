<?php
	function GetXY($str) {
		$x = ((ord($str[0]) << 2) | ((ord($str[1]) & 0xC0) >> 6));
		$y = (((ord($str[1]) & 0x3f) << 4) | ((ord($str[2]) & 0xf0) >> 4));

		return array($x, $y);
	}

	function GetXYP($str) {
		list($x, $y) = GetXY($str);

		return new Position($x, $y);
	}

	function GetXY2($str) {
		$x1 = (((ord($str[0]) & 0xff) << 2) | ((ord($str[1]) & 0xC0) >> 6));
		$y1 = (((ord($str[1]) & 0x3f) << 4) | ((ord($str[2]) & 0xf0) >> 4));

		$x2 = (((ord($str[3]) & 0xfc) >> 2) | ((ord($str[2]) & 0x0f) << 6));
		$y2 = (((ord($str[3]) & 0x03) << 8) | ((ord($str[4]) & 0xff)));

		return array($x1, $y1, $x2, $y2);
	}

	function GetXYP2($str) {
		list($x1, $y1, $x2, $y2) = GetXY2($str);

		$Pos = new Position($x1, $y1);

		$Pos->X2 = $x2;
		$Pos->Y2 = $y2;

		return $Pos;
	}

	function MakeXY($x, $y) {
		$ret = '';
		AddR8($ret, (int)($x >> 2));
		AddR8($ret, (int)((($x % 4) << 6) | (int)($y >> 4)));
		AddR8($ret, (int)(($y % 16) << 4));

		return $ret;
	}

	function MakeXYP(Position $Position) {
		return MakeXY($Position->X, $Position->Y);
	}

	function GetAngle($x1, $y1, $x2, $y2) {
		list($dx, $dy) = array($x2 - $x1, $y2 - $y1);

		if ($dx == 0) return ($dy > 0) ? -90 : 90;

		$a = -((atan($dy / $dx) * 180) / M_PI);
		if ($dx < 0) $a += (($dy < 0) ? 180 : -180);
		return $a;
	}

	function MakeEnum() {
		$c = 0;
		foreach (func_get_args() as $v) if (!defined($v)) define($v, $c++);
	}

	function GetSimilarObjectValue(&$array, $param, $value) {
		if (!is_array($array)) $array = array($array);

		foreach (array_keys($array) as $k) {
			$va = &$array[$k];
			similar_text(strtolower(trim($va->$param)), $value, $per);

			if (!isset($prec) || $per > $prec) {
				$return = $k;
				$prec = $per;
			}
		}

		if (!isset($return)) return false;

		return $array[$return];
	}

	function GetSimilarValue($array, $value, $prec = 60) {
		if (!is_array($array)) $array = array($array);
		$return = $value = strtolower(trim($value));

		foreach ($array as $va) {
			similar_text(strtolower(trim($va)), $value, $per);
			if ($per > $prec) { $prec = $per; $return = $va; }
		}

		return $return;
	}

	function GetSimilarArray($array, $value) {
		$return = array();

		foreach ($array as $k => $v) {
			similar_text($v, $value, $per);
			$return[$k] = round($per, 2);
		}
		arsort($return);

		return $return;
	}

	function GetTickCount() {
		$time = (string)(time() * 1000);
		if (strlen($time) > 9) {
			return substr($time, -8, 8);
		} else {
			return $time;
		}
	}
?>