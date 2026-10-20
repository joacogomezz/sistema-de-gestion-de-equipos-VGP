<?php 
	require_once dirname(__DIR__) . '/config.php';

	class conectar{
		private $servidor;
		private $usuario;
		private $password;
		private $bd;

		public function __construct() {
			$this->servidor = DB_HOST;
			$this->usuario  = DB_USER;
			$this->password = DB_PASS;
			$this->bd       = DB_NAME;
		}

		public function conexion(){
			$conexion = mysqli_connect($this->servidor, $this->usuario, $this->password, $this->bd);
			return $conexion;
		}
	}
?>
