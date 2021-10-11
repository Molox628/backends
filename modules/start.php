<?php
if(!defined("MCR")){ exit("Hacking Attempt!"); }
class module
{
    // Определение видимости свойства core
    private $core, $db, $cfg, $user;


    public function __construct($core)
    {
        $this->core = $core;
        $this->db = $core->db;
        $this->cfg = $core->cfg;
        $this->user = $core->user;


        $core->title = "Старт";
        $bc = array(
            "Старт" => BASE_URL . "?mode=start"
        );


    }

    // Метод, возвращаемый результат
    public function content()
    {

        return $this->core->sp(MCR_THEME_MOD . "start/start.html");

    }
}