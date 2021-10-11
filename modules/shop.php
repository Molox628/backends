<?php 
if(!defined("MCR")){ exit("Hacking Attempt!"); }
class module {
	// Определение видимости свойства core
	private $core, $db, $cfg, $user;


	public function __construct($core){
		$this->core		= $core;
		$this->db		= $core->db;
		$this->cfg		= $core->cfg;
		$this->user		= $core->user;


		$core->title = "Магазин";
		$bc = array(
			"Магазин" => BASE_URL."?mode=shop"
		);

		$this->core->bc = $this->core->gen_bc($bc);
		$this->core->header .= $this->core->sp(MCR_THEME_MOD."shop/header.html");
	}

	private function check_server() {
		if (isset($_GET['id'])) {
			$id = intval($_GET['id']);
			$query = $this->db->query('SELECT * FROM mcr_shop_servers WHERE id = '.$id);

			if ($query) {
				return $this->db->fetch_assoc($query);
			}
		}
		exit();
	}

	private function check_block($id = NULL) {
		$id = $id != null ? $id : $_GET['id'];
		$query = $this->db->query('SELECT * FROM mcr_shop_products WHERE id = '.$id);

		if ($query) {
			return $this->db->fetch_assoc($query);
		}
		exit();
	}

	private function blocks_list() {
		$server = $this->check_server();

		$query = $this->db->query("SELECT * FROM mcr_shop_products WHERE type = 'item' AND server_id = ".$server['id']);

		if ($this->db->num_rows($query) <= 0) {
			return;
		}

		ob_start();
		while ($block = $this->db->fetch_assoc($query)) {

			$data = [
				'ID' 		 => $block['id'],
				'TITLE' 	 => $block['title'],
				'IMG' 		 => $block['img'],
				'PRICE'		 => $block['price']
			];

			echo $this->core->sp(MCR_THEME_MOD."shop/block-id.html", $data);
		}
		return ob_get_clean();
	}

	private function server() {
		$server = $this->check_server();

		$data = [
			'BLOCKS' => $this->blocks_list(),
			'SERVER'	 => $server['title']
		];

		return $this->core->sp(MCR_THEME_MOD."shop/server.html", $data);;
	}

	private function servers_list() {
		$query = $this->db->query("SELECT * FROM mcr_shop_servers");

		if (!$query || $this->db->num_rows($query) == 0) {
			$data = 'Еще не добавлено ни одного сервера'; 
			json_encode($data);
			exit;
		}

		ob_start();
		while ($server = $this->db->fetch_assoc($query)) {

			$data = [
				'ID' 		 => $server['id'],
				'TITLE' 	 => $server['title'],
				'IMG' 		 => $server['img'],
				'DESCRIPTION'=> $server['description']
			];

			echo $this->core->sp(MCR_THEME_MOD."shop/server-id.html", $data);
		}
		return ob_get_clean();
	}

	private function block() {
		$query = $this->db->query('SELECT * FROM mcr_shop_servers');
		$server = $this->db->fetch_assoc($query);
		$block = $this->check_block();
		$data = [
			'ID' 		 => $block['id'],
			'TITLE' 	 => $block['title'],
			'AMOUNT' 	 => $block['amount'],
			'PRICE' 	 => $block['price'],
			'IMG' 		 => $block['img'],
			'SERVER'     => $server['title'],
			'DESCRIPTION'=> $block['description']
		];

		return $this->core->sp(MCR_THEME_MOD."shop/modal-id.html", $data);
	}

	private function buy() {
		if (!$this->user->is_auth) {
			$data = [
				'TEXT' => 'Для покупки нужно авторизоваться'
			];
			return $this->core->sp(MCR_THEME_MOD."shop/modal-error.html", $data);	
		}

		$product   = $this->check_block($_POST['id']);
		$price     = $product['price'];
		$money     = $this->user->realmoney;
		$login     = $this->user->login;
		$item_id   = $product['item_id'];
		$amount    = $product['amount'];
		$item_id   = $product['item_id'];
		$server_id = $product['server_id'];

		if ($money >= $price) {
				$update = $this->db->query("UPDATE mcr_iconomy SET realmoney = realmoney - $price WHERE login = '$login'");
				$insert = $this->db->query("INSERT INTO mcr_shopping_cart SET player = '$login', item = '$item_id', amount = $amount, server_id = $server_id");
				
				if ($update && $insert) {
					return $this->core->sp(MCR_THEME_MOD."shop/modal-success.html");
				} else {
					$data = [
						'TEXT' => 'SQL ERROR'
					];
					return $this->core->sp(MCR_THEME_MOD."shop/modal-error.html", $data);	
				}
		} else {
			$data = [
				'TEXT' => 'Не хватает средств'
			];
			return $this->core->sp(MCR_THEME_MOD."shop/modal-error.html", $data);	
		}
	}

	private function index() {	
		$data = [
			'SERVERS' => $this->servers_list()
		];

		if (isset($_GET['ajax'])) {
			echo "string";
		}

		return $this->core->sp(MCR_THEME_MOD."shop/main.html", $data);
	}

	public function content(){
		$op = isset($_GET['op']) ? $_GET['op'] : '';

		if ($op == '') {
			return $this->index();
		}
		
		switch ($op) {
			case 'server': echo $this->server(); break;
			case 'index':  echo $this->servers_list(); break;
			case 'block':  echo $this->block(); break;
			case 'buy':    echo $this->buy(); break;
		}

		exit();
	}
}

?>