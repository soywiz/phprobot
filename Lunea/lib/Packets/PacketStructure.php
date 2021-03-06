<?php
	Import('System.Buffer');

	class PacketStructure {
		public  $IdHex;
		public  $Id;
		public  $Length;
		public  $Description;
		private $Structure;

		// Crea una estructura de paquete a partir de un SimpleXMLElement
		function __construct(SimpleXMLElement $packet) {
			list($PacketId, $PacketLength, $PacketDescription) = array('', '', '');

			$this->Id          = &$PacketId;
			$this->Length      = &$PacketLength;
			$this->Description = &$PacketDescription;

			// Examina los atributos del paquete
			foreach ($packet->attributes() as $aname => $avalue) {
				switch (trim(strtolower($aname))) {
					case 'id':          $PacketId          = trim($avalue); break;
					case 'length':      $PacketLength      = trim($avalue); break;
					case 'description': $PacketDescription = trim($avalue); break;
					case 'structure':   $this->Structure   = trim($avalue); break;
				}
			}
			$PacketId = GetInteger($PacketId);

			$this->IdHex = '0x' . str_pad(dechex($PacketId), 4, '0', STR_PAD_LEFT);

			if ($PacketLength == '-') {
				$PacketLength = -1;
			} else {
				$PacketLength = (int)$PacketLength;
			}

			if (!isset($this->Structure)) $this->Structure = $this->ProcessEntryGroup($packet);

			//echo $this->Structure . "\n";
		}

		private function ProcessEntryGroup(SimpleXMLElement $group) {
			$Structure = '';
			$NameList  = array();
			$ReverseG  = false;

			foreach ($group as $k => $entry) {
				if (strcasecmp($k, 'entry') != 0) continue;

				list($name, $type, $length, $unused, $reverse, $param) = array(null, '', 0, false, false, '');

				foreach ($entry->attributes() as $aname => $avalue) {
					switch (strtolower(trim($aname))) {
						case 'name':    $name    = trim($avalue); break;
						case 'type':    $type    = strtolower(trim($avalue)); break;
						case 'length':  $length  = trim($avalue); break;
						case 'unused':  $unused  = GetBoolean(strtolower(trim($avalue))); break;
						case 'reverse': $reverse = GetBoolean(strtolower(trim($avalue))); break;
						case 'param':   $param   = strtolower(trim($avalue)); break;
					}
				}

				if (strtolower(trim($type)) == 'function') array_pop($NameList);

				if ($name != null && !$unused) $NameList[] = $name;

				if ($ReverseG != $reverse) {
					$Structure .= $reverse ? 'r' : 'n';
					$ReverseG = $reverse;
				}

				switch (strtolower(trim($type))) {
					case 'uint8':  case 'int8':  $Structure .= 'b'; break;
					case 'uint16': case 'int16': $Structure .= 'w'; break;
					case 'uint32': case 'int32': $Structure .= 'l'; break;
					case 'pos24':                $Structure .= 'p'; break;
					case 'pos40':                $Structure .= 'q'; break;
					case 'stringz':              $Structure .= 'z[' . $length . ']'; break;
					case 'string':               $Structure .= 's[' . $length . ']'; break;
					case 'function':
						$Structure .= 'f[' . $param  . ']';
					break;
					case 'group':
						$Structure .= 'x[' . $length . '][' . $this->ProcessEntryGroup($entry) . ']';
					break;
					default:
						throw(new Exception('Unknown Type (' . $type . ')'));
					break;
				}

				if ($unused) $Structure .= '-';
			}

			$Structure = 'a[' . implode(';', $NameList) . ']' . $Structure;

			return $Structure;
		}

		public function Extract($String) {
			return ParseStrPacket($String, $this->Structure);
		}
	}
?>