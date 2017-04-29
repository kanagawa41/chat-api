<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Feedback extends MY_Model {
    protected $_primary_key = 'feedback_id';

    public function __construct() {
        // CI_Model constructor の呼び出し
        parent :: __construct();
    }
}