<?php
	class Socket {
		protected $Sock;
		protected $Connected;

		function Connect($Ip, $Port) {
			if (!extension_loaded('sockets') && !dl('php_sockets.dll')) return false;

			if ($this->Sock = @socket_create(AF_INET, SOCK_STREAM, 0)) {
				if (@socket_set_option($this->Sock, SOL_SOCKET, SO_REUSEADDR, 1)) {
					if (@socket_connect($this->Sock, @gethostbyname($Ip), $Port)) {
						$this->Connected = true;
						return true;
					}
				}
			}

			return false;
		}

		function Close() {
			if ($this->Connected) {
				socket_close($this->Sock);
				$this->Connected = false;
			}
		}

		function Extract($Length) {
			return $this->Connected ? @socket_read($this->Sock, $Length) : false;
		}

		function Send($Data) {
			if ($this->Connected) {
				@socket_write($this->Sock, $Data);
				return true;
			}

			return false;
		}

		function GetReadLength() {
			$ls = array($this->Sock);
			//return @socket_select($ls, $a = null, $a = null, 0);
			return @socket_select($ls, null, null, 0);
		}
	}
?>