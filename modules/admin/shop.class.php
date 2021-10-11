<?php

if (!defined("MCR")) {
    exit("Hacking Attempt!");
}

class submodule
{
    private $core, $db, $cfg, $user, $lng;

    public function __construct($core)
    {
        $this->core = $core;
        $this->db = $core->db;
        $this->cfg = $core->cfg;
        $this->user = $core->user;
        $this->lng = $core->lng_m;

        if (!$this->core->is_access('sys_adm_shop')) {
            $this->core->notify($this->core->lng['403'], $this->core->lng['e_403']);
        }

        $this->core->header .= $this->core->sp(MCR_THEME_MOD . "admin/shop/header.html");

        $bc = [
            'Панель управления' => ADMIN_URL,
            'Управление магазином' => ADMIN_URL . "&do=shop"
        ];

        $this->core->bc = $this->core->gen_bc($bc);
    }

    private function check_server()
    {
        if (!isset($_GET['id'])) {
            $this->core->notify($this->core->lng['e_msg'], 'Вы не выбрали сервер', 2 );
        }

        $id = intval($_GET['id']);
        $query = $this->db->query("SELECT COUNT(*) FROM mcr_shop_servers WHERE id = $id");

        if ($this->db->num_rows($query) <= 0) {
            $this->core->notify($this->core->lng['e_msg'], 'Такого сервера не существует', 2);
        }

        return $id;
    }

    private function get_product()
    {
        if (!isset($_GET['id'])) {
            $this->core->notify($this->core->lng['e_msg'], 'Вы не выбрали товар', 2, '?mode=admin&do=shop');
        }

        $id = intval($_GET['id']);
        $query = $this->db->query("SELECT * FROM mcr_shop_products WHERE id = $id");

        if ($this->db->num_rows($query) <= 0) {
            $this->core->notify($this->core->lng['e_msg'], 'Такого товара не существует', 2, '?mode=admin&do=shop');
        }

        return $this->db->fetch_assoc($query);
    }

    private function upload_image($type_img)
    {
        // type_img - это изображение товара или изображение сервера
        // 1 - блок, 2 - сервер, 3 - привелегия

        switch ($type_img) {
            case '1':
                $path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/shop/products/';
                break;
            case '2':
                $path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/shop/servers/';
                break;
            case '3':
                $path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/shop/donates/';
                break;
        }

        if (!is_uploaded_file($_FILES['image']['tmp_name'])) {
            $this->core->notify($this->core->lng['e_msg'], 'Произошла ошибка при загрузке фото', 2);
            return false;
        }

        $mw = 3840; // Макс ширина
        $mh = 2160; // Макс высота
        $mime = mime_content_type($_FILES["image"]["tmp_name"]); // Mime тип

        list($width, $height, $type, $attr) = getimagesize($_FILES['image']['tmp_name']); // Информация об изображении

        if ($width < 50 || $width > $mw) {
            $error = 1;
            $this->core->notify($this->core->lng['e_msg'], "По ширине картинка должна быть не меньше 50px и не больше $mw ", 2);
            return false;
        } else if ($height < 50 || $height > $mh) {
            $error = 1;
            $this->core->notify($this->core->lng['e_msg'], "По высоте картинка должна быть не меньше 50px и не больше $mh ", 2);
            return false;
        }

        // Если нету ошибок при начальных проверках загружаем картинку
        if (!isset($error)) {


            if ($mime === 'image/jpeg') {
				imagealphablending($_FILES['image']['tmp_name'], true);
                $resource = imageCreateFromJPEG($_FILES['image']['tmp_name']);
                

            } elseif ($mime === 'image/png') {
				imagealphablending($_FILES['image']['tmp_name'], true);
                $resource = imageCreateFromPNG($_FILES['image']['tmp_name']);
            }

            if (!$resource) {
                $this->core->notify($this->core->lng['e_msg'], 'Произошла ошибка при загрузке фото', 2);
                return false;
            }

            do {
                $file_name = uniqid() . '.png';
            } while (file_exists($path . $file_name));

            $destination_resource=imagecreatetruecolor($width, $height);
            imagealphablending($destination_resource, false);
            imagesavealpha($destination_resource, true);
            imagecopyresampled($destination_resource, $resource, 0, 0, 0, 0, $width, $height, $width, $height);
            imagepng($destination_resource, $path.$file_name);
            return $file_name;
        }
    }

    private function blocks_list($id)
    {

        $query = $this->db->query("SELECT id, amount, price, title FROM mcr_shop_products WHERE server_id = $id AND type = 'item'");

        if ($this->db->num_rows($query) <= 0) {
            return $this->core->sp(MCR_THEME_MOD . 'admin/shop/blocks-none.html');
        }

        ob_start();
        while ($block = $this->db->fetch_assoc($query)) {
            $data = [
                'ID' => $block['id'],
                'TITLE' => $block['title'],
                'AMOUNT' => $block['amount'],
                'PRICE' => $block['price']
            ];

            echo $this->core->sp(MCR_THEME_MOD . 'admin/shop/block-id.html', $data);
        }

        return ob_get_clean();
    }

    private function edit_block()
    {
        $block = $this->get_product();

        $bc = [
            'Панель управления' => ADMIN_URL,
            'Управление магазином' => ADMIN_URL . "&do=shop",
            'Редактирование блока ' . $block['title'] => ''
        ];

        $this->core->bc = $this->core->gen_bc($bc);

        $data = [
            'ID' => $block['item_id'],
            'TITLE' => $block['title'],
            'AMOUNT' => $block['amount'],
            'PRICE' => $block['price'],
            'DESCRIPTION' => $block['description'],
            'IMG' => $block['img']
        ];

        if (isset($_POST['submit'])) {
            $amount = intval($_POST['amount']);
            $price = intval($_POST['price']);
            $title = $this->db->safesql($_POST['title']);
            $description = $this->db->safesql($_POST['description']);
            $item_id = $this->db->safesql($_POST['item-id']);
            $set = '';

            if ($amount < 0) {
                $this->core->notify($this->core->lng['e_msg'], 'Количество не может быть меньше ноля', 2, '?mode=admin&do=shop&op=edit_block&id=' . $block['id']);
            }

            if ($price < 0) {
                $this->core->notify($this->core->lng['e_msg'], 'Цена не может быть меньше ноля', 2, '?mode=admin&do=shop&op=edit_block&id=' . $block['id']);
            }

            if (is_uploaded_file($_FILES['image']['tmp_name'])) {
                $file_name = $this->upload_image(1);
            }

            if (isset($file_name)) {
                $set = ", img = '$file_name'";
            }

            $update = $this->db->query("UPDATE mcr_shop_products SET title = '$title', description = '$description', amount = '$amount', price = '$price', item_id = '$item_id' $set WHERE id = " . $block['id']);

            if ($update) {
                $this->core->notify($this->core->lng["e_success"], 'Информация обновлена', 3, '?mode=admin&do=shop&op=edit_block&id=' . $block['id']);
            }
        }

        return $this->core->sp(MCR_THEME_MOD . 'admin/shop/block-edit.html', $data);
    }

    private function delete_block()
    {
        $block = $this->get_product();

        $query = $this->db->query('DELETE FROM mcr_shop_products WHERE id = ' . $block['id']);
        $delete = unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/shop/products/' . $block['img']);

        if ($query && $delete) {
            $this->core->notify($this->core->lng["e_success"], 'Товар удален', 3, '?mode=admin&do=shop&op=server&id=' . $block['server_id']);
        }
    }

    private function server()
    {
        $id = $this->check_server();

        $bc = [
            'Панель управления' => ADMIN_URL,
            'Управление магазином' => ADMIN_URL . "&do=shop",
            'Сервер' => ''
        ];

        $this->core->bc = $this->core->gen_bc($bc);

        if (isset($_POST['add_block'])) {

            if (!@$_POST['title']) {
                $this->core->notify($this->core->lng['e_msg'], 'Вы не ввели название', 2);
            }

            if (!@$_POST['description']) {
                $this->core->notify($this->core->lng['e_msg'], 'Вы не ввели описание', 2);
            }

            if (!@$_POST['item-id']) {
                $this->core->notify($this->core->lng['e_msg'], 'Вы не ввели id', 2);
            }

            if (!@$_POST['amount']) {
                $this->core->notify($this->core->lng['e_msg'], 'Вы не ввели количество', 2);
            }

            if (@$_POST['amount'] < 0) {
                $this->core->notify($this->core->lng['e_msg'], 'Количество не может быть меньше ноля', 2);
            }

            if (@$_POST['price'] < 0) {
                $this->core->notify($this->core->lng['e_msg'], 'Цена не может быть меньше ноля', 2);
            }

            if (!@$_POST['price']) {
                $this->core->notify($this->core->lng['e_msg'], 'Вы не ввели цену', 2);
            }

            if (!@$_FILES['image']) {
                $this->core->notify($this->core->lng['e_msg'], 'Вы не добавили картинку', 2);
            }

            $amount = intval($_POST['amount']);
            $price = intval($_POST['price']);
            $item_id = $this->db->safesql($_POST['item-id']);
            $title = $this->db->safesql($_POST['title']);
            $description = $this->db->safesql($_POST['description']);
            $file_name = $this->upload_image(1);
            $tp = "item";

            $query = $this->db->query("INSERT INTO mcr_shop_products SET title = '$title', description = '$description', img = '$file_name', amount = '$amount', price = '$price', server_id = '$id', item_id = '$item_id', type = '$tp'");

            if ($query) {
                $this->core->notify($this->core->lng["e_success"], 'Блок добавлен', 3);
            } else {
                $this->core->notify($this->core->lng['e_msg'], 'Что-то пош1111ло не так', 2);
            }
        }

        $data = [
            'BLOCKS' => $this->blocks_list($id),
            'ID' => $id
        ];

        return $this->core->sp(MCR_THEME_MOD . 'admin/shop/server.html', $data);
    }

    private function add_server()
    {
        if (isset($_POST['add_server'])) {
            if (!@$_POST['title']) {
                $this->core->notify($this->core->lng['e_msg'], 'Вы не ввели название', 2, '?mode=admin&do=shop');
            }

            if (!@$_POST['description']) {
                $this->core->notify($this->core->lng['e_msg'], 'Вы не ввели описание', 2, '?mode=admin&do=shop');
            }

            if (!@$_FILES['image']) {
                $this->core->notify($this->core->lng['e_msg'], 'Вы не добавили картинку', 2, '?mode=admin&do=shop');
            }

            $file_name = $this->upload_image(2);
            $title = $this->db->safesql($_POST['title']);
            $description = $this->db->safesql($_POST['description']);
            $cart_postfix = $this->db->safesql($_POST['cart_postfix']);
            $cart_postfix_table = 'mcr_ShoppingCart_' . $cart_postfix;

            $query = $this->db->query("INSERT INTO mcr_shop_servers SET title = '$title', description = '$description', img = '$file_name', cart_postfix = '$cart_postfix'");
            $query1 = $this->db->query("CREATE TABLE " . $cart_postfix_table . " (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`player_name` VARCHAR(16) NOT NULL COLLATE 'utf8_general_ci',
	`player_uuid` CHAR(36) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
	`purchase` VARCHAR(1024) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
	`created_at` TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=6
;");

            if ($query && $query1 == true) {
                $this->core->notify($this->core->lng["e_success"], 'Сервер добавлен', 3);
            } else {
                $this->core->notify($this->core->lng['e_msg'], 'Что-то пошло не так', 2);
            }
        }
        $this->core->notify($this->core->lng['e_msg'], 'Что-то пошло не так', 2, '?mode=admin&do=shop');
    }

    private function add_donate()
    {
        $server_id = $this->check_server();

        if (isset($_POST['add-donate'])) {
            if (!@$_POST['title']) {
                $this->core->notify($this->core->lng['e_msg'], 'Вы не ввели название', 2, '?mode=admin&do=shop&op=donates&id=' . $server_id);
            }

            if (!@$_POST['description']) {
                $this->core->notify($this->core->lng['e_msg'], 'Вы не ввели описание', 2, '?mode=admin&do=shop&op=donates&id=' . $server_id);
            }

            if (!@$_POST['permgroup']) {
                $this->core->notify($this->core->lng['e_msg'], 'Вы не название группы на сервере', 2, '?mode=admin&do=shop&op=donates&id=' . $server_id);
            }

            if (!@$_POST['time']) {
                $this->core->notify($this->core->lng['e_msg'], 'Вы не ввели количество дней', 2, '?mode=admin&do=shop&op=donates&id=' . $server_id);
            }

            if (!@$_POST['price']) {
                $this->core->notify($this->core->lng['e_msg'], 'Вы не ввели цену', 2, '?mode=admin&do=shop&op=donates&id=' . $server_id);
            }

            if (!@$_POST['priority']) {
                $this->core->notify($this->core->lng['e_msg'], 'Вы не указали приоритет', 2, '?mode=admin&do=shop&op=donates&id=' . $server_id);
            }

            if (!@$_FILES['image']) {
                $this->core->notify($this->core->lng['e_msg'], 'Вы не добавили картинку', 2, '?mode=admin&do=shop&op=donates&id=' . $server_id);
            }

            $file_name = $this->upload_image(3);
            $title = $this->db->safesql($_POST['title']);
            $description = $this->db->safesql($_POST['description']);
            $perm = $this->db->safesql($_POST['permgroup']);
            $price = intval($_POST['price']);
            $time_day = intval($_POST['time']);
            $extra = $perm;
            $priority = intval($_POST['priority']);

            $query = $this->db->query("INSERT INTO mcr_shop_products SET title = '$title', description = '$description', img = '$file_name', price = $price, type = 'permgroup', time_day = $time_day, extra = '$extra', amount = 1, server_id = $server_id, permgroup = '$perm', group_priority = '$priority'");

            if ($query) {
                $this->core->notify($this->core->lng["e_success"], 'Привелегия добавлена', 3, '?mode=admin&do=shop&op=donates&id=' . $server_id);
            } else {
                $this->core->notify($this->core->lng['e_msg'], 'Что-то пошло не так', 2, '?mode=admin&do=shop&op=donates&id=' . $server_id);
            }
        }
        $this->core->notify($this->core->lng['e_msg'], 'Что-то пошло не так', 2, '?mode=admin&do=shop&op=donates&id=' . $server_id);
    }

    private function delete_server()
    {
        $id = $this->check_server();

        $query = $this->db->query("DELETE FROM mcr_shop_servers WHERE id = $id");
        $query2 = $this->db->query("SELECT * FROM mcr_shop_products WHERE server_id = $id");

        $query3 = $this->db->query("DELETE FROM mcr_shop_products WHERE server_id = $id");


        ob_start();
        if ($this->db->num_rows($query2) <= 0) {
            echo $this->core->sp(MCR_THEME_MOD . "profile/server-none.html");
        } else {
            while ($block = $this->db->fetch_assoc($query2)) {
                unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/shop/donates/' . $block['img']);
            }
        }
        ob_get_clean();


        if ($query && $query3) {
            $this->core->notify($this->core->lng["e_success"], 'Сервер удален', 3, '?mode=admin&do=shop');
        } else {
            $this->core->notify($this->core->lng['e_msg'], 'Что-то пошло не так', 2, '?mode=admin&do=shop');
        }
    }

    private function server_list()
    {
        $query = $this->db->query("SELECT id, title, description, cart_postfix FROM mcr_shop_servers");

        if ($this->db->num_rows($query) < 1) {
            return $this->core->sp(MCR_THEME_MOD . 'admin/shop/server-none.html');
        }

        ob_start();
        while ($server = $this->db->fetch_assoc($query)) {
            $data = [
                'ID' => $server['id'],
                'TITLE' => $server['title'],
                'DESCRIPTION' => $server['description'],
                'TABLE' => "mcr_ShoppingCart_" . $server['cart_postfix']

            ];

            echo $this->core->sp(MCR_THEME_MOD . 'admin/shop/server-id.html', $data);
        }

        return ob_get_clean();
    }

    private function edit_server()
    {
        $id = $this->check_server();

        $bc = array(
            'Панель управления' => ADMIN_URL,
            'Управление магазином' => ADMIN_URL . "&do=shop",
            'Редактирование сервера' => ''
        );

        $this->core->bc = $this->core->gen_bc($bc);

        $query = $this->db->query("SELECT * FROM mcr_shop_servers WHERE id = $id");
        $server = $this->db->fetch_assoc($query);

        $data = [
            'TITLE' => $server['title'],
            'DESCRIPTION' => $server['description'],
            'IMG' => $server['img']
        ];

        if (isset($_POST['submit'])) {
            $title = $this->db->safesql($_POST['title']);
            $description = $this->db->safesql($_POST['description']);
            $set = '';

            if (is_uploaded_file($_FILES['image']['tmp_name'])) {
                $file_name = $this->upload_image(2);
            }

            if (isset($file_name)) {
                $set = ", img = '$file_name'";
            }

            $update = $this->db->query("UPDATE mcr_shop_servers SET title = '$title', description = '$description' $set WHERE id = $id");

            if ($update) {
                $this->core->notify($this->core->lng["e_success"], 'Информация обнолена', 3, '?mode=admin&do=shop&op=edit_server&id=' . $id);
            }
        }

        return $this->core->sp(MCR_THEME_MOD . 'admin/shop/server-edit.html', $data);
    }

    private function donates_list($id)
    {
        $query = $this->db->query("SELECT id, title, price, time_day FROM mcr_shop_products WHERE server_id = $id AND type = 'permgroup'");

        if ($this->db->num_rows($query) < 1) {
            return $this->core->sp(MCR_THEME_MOD . 'admin/shop/donates-none.html');
        }

        ob_start();
        while ($donate = $this->db->fetch_assoc($query)) {
            $data = [
                'ID' => $donate['id'],
                'TITLE' => $donate['title'],
                'PRICE' => $donate['price'],
                'TIME' => $donate['time_day']
            ];

            echo $this->core->sp(MCR_THEME_MOD . 'admin/shop/donate-id.html', $data);
        }

        return ob_get_clean();
    }

    private function server_donates()
    {
        $id = $this->check_server();

        $bc = array(
            'Панель управления' => ADMIN_URL,
            'Управление магазином' => ADMIN_URL . "&do=shop",
            'Донаты' => ''
        );

        $this->core->bc = $this->core->gen_bc($bc);
        $data = [
            'ID' => $id,
            'DONATES' => $this->donates_list($id)
        ];

        return $this->core->sp(MCR_THEME_MOD . 'admin/shop/server-donates.html', $data);
    }

    /** private function add_block() {
     * $this->core->notify($this->core->lng['e_msg'], 'Вы не ввели название', 2);
     *
     * $server_id = $this->check_server();
     *
     * if (isset($_POST['add-block'])) {
     * if (!@$_POST['title']) {
     * $this->core->notify($this->core->lng['e_msg'], 'Вы не ввели название', 2);
     * }
     *
     * if (!@$_POST['description']) {
     * $this->core->notify($this->core->lng['e_msg'], 'Вы не ввели описание', 2, '?mode=admin&do=shop&op=server&id='.$server_id);
     * }
     *
     * if (!@$_POST['item-id']) {
     * $this->core->notify($this->core->lng['e_msg'], 'Вы не название группы на сервере', 2, '?mode=admin&do=shop&op=server&id='.$server_id);
     * }
     *
     * if (!@$_POST['amount']) {
     * $this->core->notify($this->core->lng['e_msg'], 'Вы не ввели количество ', 2, '?mode=admin&do=shop&op=server&id='.$server_id);
     * }
     *
     * if (!@$_POST['price']) {
     * $this->core->notify($this->core->lng['e_msg'], 'Вы не ввели цену', 2, '?mode=admin&do=shop&op=server&id='.$server_id);
     * }
     *
     * if (!@$_FILES['image']) {
     * $this->core->notify($this->core->lng['e_msg'], 'Вы не добавили картинку', 2, '?mode=admin&do=shop&op=server&id='.$server_id);
     * }
     *
     * $file_name   = $this->upload_image(1);
     * $title       = $this->db->safesql($_POST['title']);
     * $description = $this->db->safesql($_POST['description']);
     * $amount = $this->db->safesql($_POST['amount']);
     * $item_id         = $this->db->safesql($_POST['item-id']);
     * $price         = intval($_POST['price']);
     * $extra         = $item_id;
     *
     * $query = $this->db->query("INSERT INTO mcr_shop_products SET title = '$title', description = '$description', img = '$file_name', price = $price, type = 'item', extra = '$extra', amount = '$amount', server_id = '$server_id'");
     *
     * if ($query) {
     * $this->core->notify($this->core->lng["e_success"], 'Привелегия добавлена', 3, '?mode=admin&do=shop&op=donates&id='.$server_id);
     * } else {
     * return $this->core->notify($this->core->lng['e_msg'], 'Что-то пошло не так', 2, '?mode=admin&do=shop&op=donates&id='.$server_id);
     * }
     * }
     * return  $this->core->notify($this->core->lng['e_msg'], 'Что-то пошло не так', 2, '?mode=admin&do=shop&op=donates&id='.$server_id);
     * }
     **/
    private function edit_donate()
    {
        $donate = $this->get_product();

        $data = [
            'ID' => $donate['id'],
            'TITLE' => $donate['title'],
            'PRICE' => $donate['price'],
            'TIME' => $donate['time_day'],
            'PERM' => $donate['permgroup'],
            'DESCRIPTION' => $donate['description']
        ];

        $bc = [
            'Панель управления' => ADMIN_URL,
            'Управление магазином' => ADMIN_URL . "&do=shop",
            'Редактирование доната ' . $donate['title'] => ''
        ];

        $this->core->bc = $this->core->gen_bc($bc);

        if (isset($_POST['submit'])) {
            $price = intval($_POST['price']);
            $title = $this->db->safesql($_POST['title']);
            $description = $this->db->safesql($_POST['description']);
            $perm = $this->db->safesql($_POST['permgroup']);
            $time_day = intval($_POST['time']);
            $time_sec = $time_day * 86400;
            $extra = "$perm?lifetime=$time_sec";
            $set = '';

            if ($price < 0) {
                $this->core->notify($this->core->lng['e_msg'], 'Цена не может быть меньше ноля', 2, '?mode=admin&do=shop&op=edit_donate&id=' . $block['id']);
            }

            if (is_uploaded_file($_FILES['image']['tmp_name'])) {
                $file_name = $this->upload_image(3);
            }

            if (isset($file_name)) {
                $set = ", img = '$file_name'";
            }

            $update = $this->db->query("UPDATE mcr_shop_products SET title = '$title', description = '$description', time_day = '$time_day', price = '$price', permgroup = '$perm', type = 'permgroup', extra = '$extra' $set WHERE id = " . $donate['id']);

            if ($update) {
                $this->core->notify($this->core->lng["e_success"], 'Информация обновлена', 3, '?mode=admin&do=shop&op=edit_donate&id=' . $donate['id']);
            }
        }

        return $this->core->sp(MCR_THEME_MOD . 'admin/shop/donate-edit.html', $data);
    }

    private function delete_donate()
    {
        $donate = $this->get_product();

        $query = $this->db->query('DELETE FROM mcr_shop_products WHERE id = ' . $donate['id']);
        $delete = unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/shop/donates/' . $donate['img']);

        if ($query && $delete) {
            $this->core->notify($this->core->lng["e_success"], 'Донат удален', 3, '?mode=admin&do=shop&op=donates&id=' . $donate['server_id']);
        }
    }

    private function index()
    {
        $data = [
            'SERVERS' => $this->server_list()
        ];

        return $this->core->sp(MCR_THEME_MOD . 'admin/shop/main.html', $data);
    }

    public function content()
    {

        $op = (isset($_GET['op'])) ? $_GET['op'] : 'index';

        switch ($op) {
            case 'add_block':
                $content = $this->add_block();
                break;
            case 'edit_block':
                $content = $this->edit_block();
                break;
            case 'delete_block':
                $content = $this->delete_block();
                break;
            case 'server':
                $content = $this->server();
                break;
            case 'add_server':
                $content = $this->add_server();
                break;
            case 'edit_server':
                $content = $this->edit_server();
                break;
            case 'delete_server':
                $content = $this->delete_server();
                break;
            case 'donates':
                $content = $this->server_donates();
                break;
            case 'add_donate':
                $content = $this->add_donate();
                break;
            case 'edit_donate':
                $content = $this->edit_donate();
                break;
            case 'delete_donate':
                $content = $this->delete_donate();
                break;

            default:
                $content = $this->index();
                break;
        }

        return $content;
    }
}