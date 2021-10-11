<?php


require_once('Rcon.php');
require 'mojang-api.class.php';


use Thedudeguy\Rcon;


if (!defined("MCR")) {
 exit("Hacking Attempt!");
}

class module
{
    private $core, $db, $cfg, $user, $lng;

    public function __construct($core)
    {
        $this->core = $core;
        $this->db = $core->db;
        $this->cfg = $core->cfg;
        $this->user = $core->user;
        $this->lng = $core->lng_m;



    }

    private function delete_skin()
    {
        if (!$this->user->is_skin) {
            $this->core->notify("", $this->lng['skin_not_set'], 1, '?mode=profile');
        }

        if (file_exists(MCR_SKIN_PATH . $this->user->skin . '.png')) {
            unlink(MCR_SKIN_PATH . $this->user->skin . '.png');
        }

        if (file_exists(MCR_SKIN_PATH . 'interface/' . $this->user->skin . '.png')) {
            unlink(MCR_SKIN_PATH . 'interface/' . $this->user->skin . '.png');
        }

        if (file_exists(MCR_SKIN_PATH . 'interface/' . $this->user->skin . '_mini.png')) {
            unlink(MCR_SKIN_PATH . 'interface/' . $this->user->skin . '_mini.png');
        }

        if ($this->user->is_cloak) {
            $cloak = array(
                "tmp_name" => MCR_CLOAK_PATH . $this->user->cloak . '.png',
                "size" => filesize(MCR_CLOAK_PATH . $this->user->cloak . '.png'),
                "error" => 0,
                "name" => $this->user->cloak . '.png'
            );
            require_once(MCR_TOOL_PATH . 'cloak.class.php');
            $cloak = new cloak($this->core, $cloak);
        }

        $ctables = $this->cfg->db['tables'];
        $us_f = $ctables['users']['fields'];

        $update = $this->db->query("UPDATE `{$this->cfg->tabname('users')}` SET `{$us_f['is_skin']}`='0' WHERE `{$us_f['id']}`='{$this->user->id}'");
        if (!$update) {
            $this->core->notify($this->core->lng['e_attention'], $this->core->lng['e_sql_critical']);
        }

        // Лог действия
        $this->db->actlog($this->lng['log_delete'], $this->user->id);

        $this->core->notify($this->core->lng['e_success'], $this->lng['skin_success_del'], 3, '?mode=profile');

    }

    private function delete_cloak()
    {

        if (!$this->user->is_cloak) {
            $this->core->notify("", $this->lng['cloak_not_set'], 1, '?mode=profile');
        }

        if (file_exists(MCR_CLOAK_PATH . $this->user->login . '.png')) {
            unlink(MCR_CLOAK_PATH . $this->user->login . '.png');
        }

        if (!$this->user->is_skin) {
            unlink(MCR_SKIN_PATH . 'interface/' . $this->user->login . '.png');
            unlink(MCR_SKIN_PATH . 'interface/' . $this->user->login . '_mini.png');
        } else {
            require_once(MCR_TOOL_PATH . 'skin.class.php');

            $skin = array(
                "tmp_name" => MCR_SKIN_PATH . $this->user->login . '.png',
                "size" => filesize(MCR_SKIN_PATH . $this->user->login . '.png'),
                "error" => 0,
                "name" => $this->user->login . '.png'
            );

            $skin = new skin($this->core, $skin);
        }

        $ctables = $this->cfg->db['tables'];
        $us_f = $ctables['users']['fields'];

        $update = $this->db->query("UPDATE `{$this->cfg->tabname('users')}` SET `{$us_f['is_cloak']}`='0' WHERE `{$us_f['id']}`='{$this->user->id}'");
        if (!$update) {
            $this->core->notify($this->core->lng['e_attention'], $this->core->lng['e_sql_critical']);
        }

        // Лог действия
        $this->db->actlog($this->lng['log_delete_cl'], $this->user->id);

        $this->core->notify($this->core->lng['e_success'], $this->lng['cloak_success_del'], 3, '?mode=profile');

    }

    private function upload_skin()
    {
        require_once(MCR_TOOL_PATH . 'skin.class.php');
        $skin = new skin($this->core, $_FILES['skin']); // create new skin in folder

        if ($this->user->is_cloak) {
            $cloak = array(
                "tmp_name" => MCR_CLOAK_PATH . $this->user->login . '.png',
                "size" => (!file_exists(MCR_CLOAK_PATH . $this->user->login . '.png')) ? 0 : filesize(MCR_CLOAK_PATH . $this->user->login . '.png'),
                "error" => 0,
                "name" => $this->user->login . '.png'
            );
            require_once(MCR_TOOL_PATH . 'cloak.class.php');
            $cloak = new cloak($this->core, $cloak);
        }

        $ctables = $this->cfg->db['tables'];
        $us_f = $ctables['users']['fields'];

        $update = $this->db->query("UPDATE `{$this->cfg->tabname('users')}` SET `{$us_f['is_skin']}`='1' WHERE `{$us_f['id']}`='{$this->user->id}'");

        if (!$update) {
            $this->core->notify($this->core->lng['e_attention'], $this->core->lng['e_sql_critical']);
        }

        // Лог действия
        $this->db->actlog($this->lng['log_edit_sk'], $this->user->id);

        $this->core->notify($this->core->lng['e_success'], $this->lng['skin_success_edit'], 3, '?mode=profile');
    }

    private function upload_cloak()
    {
        require_once(MCR_TOOL_PATH . 'cloak.class.php');
        $cloak = new cloak($this->core, $_FILES['cloak']); // create new cloak in folder

        $ctables = $this->cfg->db['tables'];
        $us_f = $ctables['users']['fields'];

        $update = $this->db->query("UPDATE `{$this->cfg->tabname('users')}` SET `{$us_f['is_cloak']}`='1' WHERE `{$us_f['id']}`='{$this->user->id}'");

        if (!$update) {
            $this->core->notify($this->core->lng['e_attention'], $this->core->lng['e_sql_critical']);
        }

        // Лог действия
        $this->db->actlog($this->lng['log_edit_cl'], $this->user->id);

        $this->core->notify($this->core->lng['e_success'], $this->lng['cloak_success_edit'], 3, '?mode=profile');
    }

    private function settings()
    {

        if (!empty($_POST['firstname']) && !preg_match("/^[a-zа-яА-ЯёЁ]+$/iu", $_POST['firstname'])) {
            $this->core->notify($this->core->lng['e_msg'], $this->lng['e_valid_fname'], 2, '');
        }
        if (!empty($_POST['lastname']) && !preg_match("/^[a-zа-яА-ЯёЁ]+$/iu", $_POST['lastname'])) {
            $this->core->notify($this->core->lng['e_msg'], $this->lng['e_valid_lname'], 2, '');
        }
        if (!empty($_POST['birthday']) && !preg_match("/^(\d{2}-\d{2}-\d{4})?$/", $_POST['birthday'])) {
            $this->core->notify($this->core->lng['e_msg'], $this->lng['e_valid_bday'], 2, '');
        }

        $firstname = $this->db->safesql(@$_POST['firstname']);
        $lastname = $this->db->safesql(@$_POST['lastname']);
        $birthday = @$_POST['birthday'];

        $birthday = intval(strtotime($birthday));
        $newpass = $this->user->password;
        $newsalt = $this->user->salt;

        if (isset($_POST['newpass']) && !empty($_POST['newpass'])) {
            $old_pass = @$_POST['oldpass'];
            $old_pass = $this->core->gen_password($old_pass, $this->user->salt);

            if ($old_pass !== $this->user->password) {
                $this->core->notify($this->core->lng['e_msg'], $this->lng['e_valid_oldpass'], 2, '');
            }

            if ($_POST['newpass'] !== @$_POST['repass']) {
                $this->core->notify($this->core->lng['e_msg'], $this->lng['e_valid_repass'], 2, '');
            }

            $newsalt = $this->db->safesql($this->core->random());
            $newpass = $this->db->safesql($this->core->gen_password($_POST['newpass'], $newsalt));
        }

        $time = time();

        $ctables = $this->cfg->db['tables'];
        $us_f = $ctables['users']['fields'];

        $update = $this->db->query("UPDATE `{$this->cfg->tabname('users')}`
									SET `{$us_f['pass']}`='$newpass', `{$us_f['salt']}`='$newsalt', `{$us_f['ip_last']}`='{$this->user->ip}',
										`{$us_f['date_last']}`='$time', `{$us_f['fname']}`='$firstname', `{$us_f['lname']}`='$lastname',
										`{$us_f['bday']}`='$birthday'
									WHERE `{$us_f['id']}`='{$this->user->id}'");

        if (!$update) {
            $this->core->notify($this->core->lng['e_attention'], $this->core->lng['e_sql_critical'], 2, '');
        }

        // Лог действия
        $this->db->actlog($this->lng['log_settings'], $this->user->id);

        $this->core->notify($this->core->lng['e_success'], $this->lng['set_success_save'], 3, '');
    }

    public function get_server()
    {
        $query = $this->db->query("SELECT * FROM `mcr_shop_servers`");
        $rgf = "uploads/shop/servers/";
        ob_start();
        if ($this->db->num_rows($query) <= 0) {
            echo $this->core->sp(MCR_THEME_MOD . "profile/donate-none.html");
        } else {
            while ($server = $this->db->fetch_assoc($query)) {
                if ($server['enable_donate'] == 0) {
                    continue;
                } else {
                    echo $this->core->sp(MCR_THEME_MOD . "profile/servers.html", [
                        'ID' => $server['id'],
                        'TITLE' => $server['title'],
                        'DESCRIPTION' => $server['description'],
                        'IMAGES' => $rgf . $server['img'],
                    ]);
                }
            }
        }
        return ob_get_clean();

    }



    private function donate_list()
    {
        $id = $_POST['id'];
        $user = $this->user->login;

        //$query_get_isbuy = $this->db->query("SELECT * FROM `mcr_buy_groum_log` WHERE `user` = '{$user}' AND MIN(priority_group) AND MAX(id) ");
        $query_get_isbuy = $this->db->query("SELECT * FROM `mcr_buy_groum_log` WHERE `user` = '{$user}' AND priority_group = (SELECT MIN(priority_group) FROM mcr_buy_groum_log WHERE `user` = '{$user}') AND id = (SELECT MAX(id) FROM mcr_buy_groum_log WHERE `user` = '{$user}')");
        $query_get_isbuy_array = $this->db->fetch_assoc($query_get_isbuy);

        $query = $this->db->query("SELECT * FROM `mcr_shop_products` WHERE `server_id` = {$_POST['id']}");

        $rgf = "uploads/shop/donates/";

        //   echo $this->db->num_rows($this->db->query("SELECT * FROM `mcr_buy_groum_log` WHERE `user` = $id"));
        ob_start();
        if ($this->db->num_rows($query) <= 0) {
            echo $this->core->sp(MCR_THEME_MOD . "profile/donate-none.html");
        } else {
            $counter = 0;

            if ($this->db->num_rows($query_get_isbuy) < 1) {

                while ($donate = $this->db->fetch_assoc($query)) {
                    $data = array(
                        '_ID' => $donate['id'],
                        '_TITLE' => $donate['title'],
                        '_DESCRIPTION' => $donate['description'],
                        '_IMAGE' => $rgf . $donate['img'],
                        '_PRICE' => $donate['price'],
                        '_SERVER_ID' => $donate['server_id']
                    );

                    echo $this->core->sp(MCR_THEME_MOD . "profile/donate-id.html", $data, '?mode=profile');
                }
            }
            if ($this->db->num_rows($query_get_isbuy) >= 1) {
                $date = new DateTime();


                while ($donate = $this->db->fetch_assoc($query)) {
                    if ($query_get_isbuy_array['unix_time_after'] > $date->getTimestamp()) {
                        if ($query_get_isbuy_array['priority_group'] <= $donate['group_priority']) {
                            continue;
                        }
                    }

                    $data = array(
                        '_ID' => $donate['id'],
                        '_TITLE' => $donate['title'],
                        '_DESCRIPTION' => $donate['description'],
                        '_IMAGE' => $rgf . $donate['img'],
                        '_PRICE' => $donate['price'],
                        '_SERVER_ID' => $donate['server_id']
                    );
                    $counter++;



                    echo $this->core->sp(MCR_THEME_MOD . "profile/donate-id.html", $data, '?mode=profile');
                }
            }
            if($counter <= 0){
                echo $this->core->sp(MCR_THEME_MOD."profile/donate-null.html");
            }
        }


        $data['DONATES'] = ob_get_clean();

        echo $this->core->sp(MCR_THEME_MOD . "profile/donate-list.html", $data, '?mode=profile');
        exit();
    }


    private
    function exchanger()
    {

        if (isset($_POST['amount'])) {
            $amount = intval($_POST['amount']);
            $cource = 100;
            $new_mon = $amount * $cource;

            if ($amount <= 0) {
                $this->core->notify($this->core->lng['e_attention'], 'Invalid values for exchange!', 1, '?mode=profile');
            }

            if ($this->user->realmoney < $amount) {
                $this->core->notify($this->core->lng['e_attention'], 'Not enough money!', 1, '?mode=profile');
            }

            $query = $this->db->query("UPDATE mcr_iconomy SET realmoney = realmoney - {$amount} WHERE id = {$this->user->id}");
            $query2 = $this->db->query("UPDATE mcr_iconomy SET money = money + {$new_mon} WHERE id = {$this->user->id}");

            if ($query && $query2) {
                $this->core->notify($this->core->lng['e_success'], 'Successful exchange!', 3, '?mode=profile');

            }
        } else {
            $this->core->notify($this->core->lng['e_attention'], 'Invalid values for exchange!', 1, '?mode=profile');
        }

    }

    private
    function voucher()
    {
        $vvoucnher = $_POST['voucher'];
        $code_querytr = $this->db->query("SELECT * FROM mcr_code WHERE codes = '$vvoucnher'");
        $code_arrayt = $this->db->fetch_assoc($code_querytr);
        $code_log = $this->db->query("SELECT login FROM mcr_code_activation_log WHERE code = '$vvoucnher'");
        $code_log_arrayt = $this->db->fetch_assoc($code_log);

        $date = date("l dS of F Y h:i:s A");
        if (!$code_arrayt['id'] == null) {
            if ($code_arrayt['counts'] > 0) {

                if ($code_log_arrayt['login'] == $this->user->login) {

                    switch ($code_arrayt['type']) {

                        case "real":
                            $this->db->query("UPDATE mcr_iconomy SET realmoney = realmoney + {$code_arrayt['money']} WHERE id = {$this->user->id}");
                            $this->db->query("UPDATE mcr_code SET counts = counts - 1 WHERE id = {$code_arrayt['id']}");
                            $this->db->query("INSERT INTO mcr_code_activation_log (`login`, `code`, `dates`)  VALUES ('{$this->user->login}', '$vvoucnher', '$date')");
                            $this->core->notify($this->core->lng['e_success'], 'Successfully', 3, '?mode=profile');
                            break;

                        case "game":
                            $this->db->query("UPDATE mcr_iconomy SET money = money + {$code_arrayt['money']} WHERE id = {$this->user->id}");
                            $this->db->query("UPDATE mcr_code SET counts = counts - 1 WHERE id = {$code_arrayt['id']}");
                            $this->db->query("INSERT INTO mcr_code_activation_log (`login`, `code`, `dates`)  VALUES ('{$this->user->login}', '$vvoucnher', '$date')");
                            $this->core->notify($this->core->lng['e_success'], 'Successfully', 3, '?mode=profile');
                            break;

                        default:
                            $this->core->notify($this->core->lng['e_attention'], 'Promo code error', 1, '?mode=profile');
                            break;

                    }
                } else {
                    $this->core->notify($this->core->lng['e_attention'], 'This code has already been activated!', 1, '?mode=profile');
                }
            } else {
                $this->core->notify($this->core->lng['e_attention'], 'Vouchers run out', 1, '?mode=profile');
            }
        } else {
            $this->core->notify($this->core->lng['e_attention'], 'Voucher not found', 1, '?mode=profile');

        }


    }

    private
    function get_mojang_ver()
    {

        //  $query = $this->db->query("SELECT mojang_activate FROM mcr_users WHERE login = '{$this->user->login}'");
        //  $status = $this->db->fetch_assoc($query);
        //  if ($status['mojang_activate'] != 1) {
        //      return $this->core->sp(MCR_THEME_MOD . "profile/verification_null.html");
        //  } else {
        //     return $this->core->sp(MCR_THEME_MOD . "profile/verification_success.html");
        // }
        return $this->core->sp(MCR_THEME_MOD . "profile/verification_success.html", '?mode=profile');

    }

    private
    function mojang_ver()
    {
        $result = MojangAPI::authenticate($_POST['mojang_login'], $_POST['mojang_password']);
        if ($result) {
            $query = $this->db->query("SELECT * FROM mcr_uuid_mojang_history WHERE uuid = '{$result['id']}'");
            $status = $this->db->fetch_assoc($query);
            if ($status['uuid'] == null) {
                $this->db->query("INSERT INTO mcr_uuid_mojang_history (	uuid, count_max, count_current) VALUES ('{$result['id']}', 2, 1);");
                $this->db->query("UPDATE mcr_users SET mojang_uuid = '{$result['id']}', mojang_activate = 1 WHERE login = '{$this->user->login}'");
                $this->core->notify($this->core->lng['e_success'], 'Verification was successful! Have a nice game!', 3, '?mode=profile');
            } else if (($status['uuid'] == $result['id']) && ($status['count_max'] > $status['count_current'])) {
                $this->db->query("UPDATE mcr_users SET mojang_uuid = '{$result['id']}', mojang_activate = 1 WHERE login = '{$this->user->login}'");
                $this->db->query("UPDATE mcr_uuid_mojang_history SET count_current = 2 WHERE uuid = '{$result['id']}'");
                $this->core->notify($this->core->lng['e_success'], 'The second account was verified successfully! Please note that you will no longer be able to verify!', 5, '?mode=profile');
            } else {
                $this->core->notify($this->core->lng['e_attention'], 'You can no longer verify with this account! Verification count exceeded!', 1, '?mode=profile');
            }
        } else {
            $this->core->notify($this->core->lng['e_attention'], 'Invalid credentials or Minecraft not purchased. Verification failed!', 1, '?mode=profile');
        }
    }


    private
    function donate_modal()
    {
        $query = $this->db->query('SELECT * FROM mcr_monitoring');
        $server = $this->db->fetch_assoc($query);
        $id = intval($_POST['donate_id']);

        $query = $this->db->query("SELECT * FROM mcr_shop_products WHERE id = $id");

        if ($this->db->num_rows($query) == 0) {
            echo $this->core->sp(MCR_THEME_MOD . "profile/modal-error.html", '?mode=profile');
            exit();
        }

        $donate = $this->db->fetch_assoc($query);

        $data = array(
            '_ID' => $donate['id'],
            '_TITLE' => $donate['title'],
            'IMG' => $donate['img'],
            '_DESCRIPTION' => $donate['description'],
            '_SERVER' => $server['title'],
            '_PRICE' => $donate['price'],
            '_DAY' => $donate['time_day']
        );

        echo $this->core->sp(MCR_THEME_MOD . "profile/donate-modal.html", $data, '?mode=profile');
        exit();
    }

    private function donate_buy()
    {
        $date = new DateTime();

        if (!isset($_POST['donate_buy'])) {
            $this->core->notify($this->core->lng["e_msg"], 'Error', 2, '');
        }
        $id = intval($_POST['donate_buy']);
        $query = $this->db->query("SELECT * FROM mcr_shop_products WHERE id = $id");

        if (!$query) {
            $this->core->notify($this->core->lng["e_msg"], 'Donate not found!', 2, '?mode=profile');
        }

        $product = $this->db->fetch_assoc($query);
        $price = $product['price'];
        $money = $this->user->realmoney;
        $login = $this->user->login;
        $uuid = $this->user->uuid;
        $server_id = $product['server_id'];
        $permgroup = $product['permgroup'];
        $proirity = $product['group_priority'];
        $title = $product['title'];
        $count_time = 86400 * $product['time_day'];
        $unix_current = $date->getTimestamp();
        $unix_after = $unix_current + $count_time;

        $query_postfix = $this->db->query("SELECT cart_postfix FROM mcr_shop_servers WHERE id = '$server_id'");
        $cart_postfix = $this->db->fetch_assoc($query_postfix);

        if ($money >= $price) {
            $update = $this->db->query("UPDATE mcr_iconomy SET realmoney = realmoney - $price WHERE login = '$login'");
            $insert = $this->db->query("INSERT INTO mcr_ShoppingCart_" . $cart_postfix['cart_postfix'] . " SET player_name = '$login', player_uuid = '$uuid', purchase = '{
  \"type\": \"group\",
  \"purchaseData\": {
    \"group\": \"$permgroup\",
    \"time\": $count_time
  },
  \"displayData\": {
    \"name\": \"$title\",
    \"lore\": []
  }
} '");

            $insert_log = $this->db->query("INSERT INTO mcr_buy_groum_log SET user = '$login', unix_time_before = '$unix_current', priority_group = '$proirity', unix_time_after = '$unix_after'");

            if ($update && $insert && $insert_log) {
                $this->core->notify($this->core->lng["e_success"], "Purchase is complete!", 3, '?mode=profile');
            } else {
                $this->core->notify($this->core->lng["e_msg"], "SQL ERROR", 2, '?mode=profile');
            }
        } else {
            $this->core->notify($this->core->lng["e_msg"], 'Not enough funds!', 2, '?mode=profile');
        }
    }


    private
    function generate_sign($account, $CUR, $desc, $sum, $secret_key)
    {
        $hash_str = $account . '{up}' . $CUR . '{up}' . $desc . '{up}' . $sum . '{up}' . $secret_key;
        return hash('sha256', $hash_str);
    }

    private
    function get_sign($method, array $params, $secret_key)
    {
        ksort($params);
        unset($params['sign']);
        unset($params['signature']);
        array_push($params, $secret_key);
        array_unshift($params, $method);

        return hash('sha256', join('{up}', $params));
    }


    function query($array, $baseurl)
    {
        $cookie_request = "buycraft_basket={$array['buycraft_basket']};buycraft_basket_hash={$array['buycraft_basket_hash']};";


        $url_pay = "https://frsttest.tebex.io/checkout/pay";
        $ch1 = curl_init();

        curl_setopt($ch1, CURLOPT_URL, $baseurl);
        curl_setopt($ch1, CURLOPT_COOKIE, $cookie_request);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch1);
        curl_setopt($ch1, CURLOPT_URL, $baseurl);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch1);
        curl_setopt($ch1, CURLOPT_URL, $url_pay);
        curl_setopt($ch1, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch1);
        $info = curl_getinfo($ch1, CURLINFO_EFFECTIVE_URL);
        curl_close($ch1);
        return $info;
    }

    public
    function getGemoteCookies($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $result = curl_exec($ch);
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
        $cookies = array();
        foreach ($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        curl_close($ch);
        return $cookies;
    }

    private
    function payment($amount = 1)
    {
        $method = "tebex";
        if ($method == "up") {

            if (!$this->user->is_auth) {
                $this->core->notify($this->core->lng['e_403'], $this->lng['auth_required'], 1, "?mode=403");
            }
            if ($_REQUEST['money'] == null) {
                $this->core->notify($this->core->lng['warning'], 'Enter count', 1);
            }

            if ($_REQUEST['money'] < 1) {
                $this->core->notify($this->core->lng['e_403'], 'Cool your fucking ass', 2);
            }
            if (isset($_GET['code']) && !empty($_GET['code'])) {
                $code = $this->core->check_promocode($_GET['code']);
                $code_percent = is_bool($code) ? 0 : intval($code);
            }

            $time = time();
            $amount = intval($_REQUEST['money']);
            $payment_id = $this->user->id + time() + mt_rand(1, 9999);
            $key = $this->cfg->payment['UP_PUBLIC'];
            $CUR = $this->cfg->payment['CUR'];
            $sign = $this->generate_sign($payment_id, $CUR, 'FORESTMINE', $amount, $this->cfg->payment['UP_PRIVATE']);


            $this->db->query("INSERT INTO `mcr_payments_check` SET `sign` = '{$sign}', `order_id` = '{$payment_id}', `status` = 0, `time` = '{$time}', `amount` = '{$amount}', `user_id` = '{$this->user->id}', `percent_pay` = '$code_percent'");

            exit(header("Location: https://unitpay.money/pay/$key?sum=$amount&currency=$CUR&account=$payment_id&desc=FORESTMINE&locale=en&signature=$sign"));
        } else if ($method == "tebex") {
            if ($_POST['money'] > 1) {
                $baseurl = "https://frsttest.tebex.io/checkout/basket?ign={$this->user->login}";
                $array = $this->getGemoteCookies("https://frsttest.tebex.io/checkout/packages/add/4586682/single?ign={$this->user->login}&price={$_POST['money']}&submit=Continue");
                $suks = $this->query($array, $baseurl);
                header("Location: {$suks}");
            } else {
                $this->core->notify($this->core->lng['e_attention'], 'Invalid data', 1, '?mode=profile');
            }
        }
    }


    private
    function reception()
    {

        if ($_GET['params']['signature'] != $this->get_sign($_GET['method'], $_GET['params'], $this->cfg->payment['UP_PRIVATE'])) {
            exit(json_encode(['error' => 'Неверная подпись']));
        }

        $id = intval($_GET['params']['account']);
        $query = $this->db->query("SELECT * FROM `mcr_payments_check` WHERE `order_id` = '{$id}'");
        //  $donate = $this->db->fetch_assoc($query);

        if ($this->db->num_rows($query) > 0) {
            $info = $this->db->fetch_assoc($query);
        }

        switch ($_GET['method']) {
            case 'check':
                if (isset($info)) {
                    exit(json_encode(['result' => 'Ok']));
                } else {
                    exit(json_encode(['error' => ['message' => 'Ошибка']]));
                }
                break;
            case 'pay':
                if (isset($info) && $info['status'] != 1) {
                    $amount = intval($info['amount']);
                    $time = time();
                    $user_id = $info['user_id'];
                    $percent_pay = intval($info['percent_pay']);
                    $text = "Пополнение счета через UNITPAY на сумму " . $amount . " руб.";


                    if ($info['amount'] != $_GET['params']['orderSum']) {
                        exit(json_encode(['error' => ['message' => 'Сумма, отправленная с кассы, не совпадает с суммой в запросе']]));
                    }
                    $add_money_q = $this->db->query("UPDATE `mcr_iconomy` SET `realmoney` = `realmoney` + '{$amount}' WHERE `id` = '{$user_id}'");
                    $update2 = $this->db->query("UPDATE `mcr_payments_check` SET `status` = 1, `time` = '{$time}' WHERE `order_id` = '{$id}'");

                    if ($add_money_q && $update2) {
                        //  $log_query   = $this->db->log_user_action_add($user_id, $text, 3, "БОТ UNITPAY");
                        //    $log_admin   = $this->db->actlog($text, $user_id);
                        exit(json_encode(['result' => ['message' => 'Баланс пополнен']]));
                    }
                }
                exit(json_encode(['result' => ['message' => 'Error']]));
                break;
        }
        exit('Error');
    }


    private
    function get_votes_count()
    {
        $date = date('F Y');
        $query = $this->db->query("SELECT `count` FROM `mcr_votes` WHERE `user` = '{$this->user->login}' AND `date` = '{$date}'");
        $count = $this->db->fetch_assoc($query);
        if(!$count['count']){
            $count['count'] = 0;
        }
        return $count['count'];
    }

    public
    function content()
    {

        if ((isset($_GET['params']) && isset($_GET['method']))) {
            return $this->reception();
        }
        if (!$this->user->is_auth) {
            $this->core->notify($this->core->lng['e_403'], $this->lng['auth_required'], 1, "?mode=403");
        }

        if (!$this->core->is_access('sys_profile')) {
            $this->core->notify($this->core->lng['e_403'], $this->lng['access_by_admin'], 1, "?mode=403");
        }

        $this->core->header = $this->core->sp(MCR_THEME_MOD . "profile/header.html");

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {


            // Последнее обновление пользователя
            $this->db->update_user($this->user);

            if (isset($_POST['del-skin'])) {
                if (!$this->core->is_access('sys_profile_del_skin')) {
                    $this->core->notify($this->core->lng['e_403'], $this->lng['skin_access_by_admin'], 1, "?mode=403");
                }
                $this->delete_skin();
            } elseif (isset($_POST['del-cloak'])) {
                if (!$this->core->is_access('sys_profile_del_cloak')) {
                    $this->core->notify($this->core->lng['e_403'], $this->lng['cloak_access_by_admin'], 1, "?mode=403");
                }
                $this->delete_cloak();
            } elseif (isset($_FILES['skin'])) {
                if (!$this->core->is_access('sys_profile_skin')) {
                    $this->core->notify($this->core->lng['e_403'], $this->lng['skin_edit_by_admin'], 1, "?mode=403");
                }
                $this->upload_skin();
            } elseif (isset($_FILES['cloak'])) {
                if (!$this->core->is_access('sys_profile_cloak')) {
                    $this->core->notify($this->core->lng['e_403'], $this->lng['cloak_edit_by_admin'], 1, "?mode=403");
                }
                $this->upload_cloak();
            } elseif (isset($_POST['settings'])) {
                if (!$this->core->is_access('sys_profile_settings')) {
                    $this->core->notify($this->core->lng['e_403'], $this->lng['set_save_by_admin'], 1, "?mode=403");
                }
                $this->settings();
            } elseif (isset($_POST['amount'])) {
                if (!$this->core->is_access('sys_profile_settings')) {
                    $this->core->notify($this->core->lng['e_403'], $this->lng['set_save_by_admin'], 1, "?mode=403");
                }
                $this->exchanger();
            } elseif (isset($_POST['voucher'])) {
                if (!$this->core->is_access('sys_profile_settings')) {
                    $this->core->notify($this->core->lng['e_403'], $this->lng['set_save_by_admin'], 1, "?mode=403");
                }
                $this->voucher();
            } elseif (isset($_REQUEST['money'])) {
                if (!$this->core->is_access('sys_profile_settings')) {
                    $this->core->notify($this->core->lng['e_403'], $this->lng['set_save_by_admin'], 1, "?mode=403");
                }
                $this->payment();
            } elseif (isset($_POST['mojang_login'])) {
                if (!$this->core->is_access('sys_profile_settings')) {
                    $this->core->notify($this->core->lng['e_403'], $this->lng['set_save_by_admin'], 1, "?mode=403");
                }
                $this->mojang_ver();
            } elseif (isset($_POST['get_donate'])) {
                $this->donate_list();
            } elseif (isset($_POST['donate_buy'])) {
                $this->donate_buy();
            } elseif (isset($_POST['donate_id'])) {
                $this->donate_modal();
            } else {
                $this->core->notify('', '', 3, '?mode=profile');
            }
        }


        $do = !empty($_GET['do']) ? strtolower($this->db->HSC($_GET['do'])) : 'servers';

        $bc = [$this->lng['mod_name'] => BASE_URL . "?mode=profile"];
        switch ($do) {
            default:
                $cont = $this->core->sp(MCR_THEME_MOD . "profile/profile.html", ['SERVERS' => $this->get_server(), 'mojang_ver' => $this->get_mojang_ver(), 'votes_count' => $this->get_votes_count()]);
                break;


        }
        $this->core->bc = $this->core->gen_bc($bc);
        return $cont;
    }
}

?>