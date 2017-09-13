<?php
/**
 * Class for a Open Badge Factory -earnable badge.
 * @copyright  2017, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_earnable_badge {
    /**
     * @var string $description
    */
    private $description;
    /**
     * @var string $intent
    */
    private $intent;
    /**
     * @var integer $draft
    */
    private $draft;
    /**
     * @var integer $visible
    */
    private $visible;
    /**
     * @var integer $not_before
    */
    private $not_before;
    /**
     * @var string $id
    */
    private $id;
    /**
     * @var string $form
    */
    private $form;
    /**
     * @var string $client_id
    */
    private $client_id;
    /**
     * @var integer $ctime
    */
    private $ctime;
    /**
     * @var integer $mtime
    */
    private $mtime;
    /**
     * @var array $options
    */
    private $options;
    /**
     * @var string $redirect_url
    */
    private $redirect_url;
    /**
     * @var string $apply_url
    */
    private $apply_url;
    /**
     * @var integer $not_after
    */
    private $not_after;
    /**
     * @var string $name
    */
    private $name;
    /**
     * @var string $language
    */
    private $language;
    /**
     * @var string $badge_id
    */
    private $badge_id;
    /**
     * @var string $approval_method
    */
    private $approval_method;
    /**
     * @var string $form_html
     */
    private $form_html;
    /**
     * @var string $attach_evidence
    */
    private $attach_evidence;
    /**
     * @var string $client_alias
    */
    private $client_alias;
    /**
     * @var obf_earnable_badge[] $_cache
     */
    protected static $_cache = array();

    /**
     * @var obf_client $_client
     */
    protected $_client;

    public function get_description() {
      return $this->description;
    }

    public function get_intent() {
      return $this->intent;
    }

    public function get_draft() {
      return $this->draft;
    }

    public function get_visible() {
      return $this->visible;
    }

    public function get_not_before() {
      return $this->not_before;
    }

    public function get_id() {
      return $this->id;
    }

    public function get_form() {
      return $this->form;
    }

    public function get_client_id() {
      return $this->client_id;
    }

    public function get_ctime() {
      return $this->ctime;
    }

    public function get_mtime() {
      return $this->mtime;
    }

    public function get_options() {
      return $this->options;
    }

    public function get_redirect_url() {
      return $this->redirect_url;
    }

    public function get_apply_url() {
      return $this->apply_url;
    }

    public function get_not_after() {
      return $this->not_after;
    }

    public function get_name() {
      return $this->name;
    }

    public function get_language() {
      return $this->language;
    }

    public function get_badge_id() {
      return $this->badge_id;
    }

    public function get_approval_method() {
      return $this->approval_method;
    }

    public function get_form_html() {
      return $this->form_html;
    }

    public function get_attach_evidence() {
      return $this->attach_evidence;
    }

    public function get_client_alias() {
      return $this->client_alias;
    }

    public function set_description($description) {
      $this->description = $description;
      return $this;
    }

    public function set_intent($intent) {
      $this->intent = $intent;
      return $this;
    }

    public function set_draft($draft) {
      $this->draft = $draft;
      return $this;
    }

    public function set_visible($visible) {
      $this->visible = $visible;
      return $this;
    }

    public function set_not_before($not_before) {
      $this->not_before = $not_before;
      return $this;
    }

    public function set_id($id) {
      $this->id = $id;
      return $this;
    }

    public function set_form($form) {
      $this->form = $form;
      return $this;
    }

    public function set_client_id($client_id) {
      $this->client_id = $client_id;
      return $this;
    }

    public function set_ctime($ctime) {
      $this->ctime = $ctime;
      return $this;
    }

    public function set_mtime($mtime) {
      $this->mtime = $mtime;
      return $this;
    }

    public function set_options($options) {
      $this->options = $options;
      return $this;
    }

    public function set_redirect_url($redirect_url) {
      $this->redirect_url = $redirect_url;
      return $this;
    }

    public function set_apply_url($apply_url) {
      $this->apply_url = $apply_url;
      return $this;
    }

    public function set_not_after($not_after) {
      $this->not_after = $not_after;
      return $this;
    }

    public function set_name($name) {
      $this->name = $name;
      return $this;
    }

    public function set_language($language) {
      $this->language = $language;
      return $this;
    }

    public function set_badge_id($badge_id) {
      $this->badge_id = $badge_id;
      return $this;
    }

    public function set_approval_method($approval_method) {
      $this->approval_method = $approval_method;
      return $this;
    }

    public function set_form_html($form_html) {
      $this->form_html = $form_html;
      return $this;
    }

    public function set_attach_evidence($attach_evidence) {
      $this->attach_evidence = $attach_evidence;
      return $this;
    }

    public function set_client_alias($client_alias) {
      $this->client_alias = $client_alias;
      return $this;
    }

    /**
     * @return obf_client
     */
    protected function _get_client() {
      return $this->_client;
    }

    /**
     * @param obf_client $client
     * @return $this
     */
    protected function _set_client($client) {
      $this->_client = $client;
      return $this;
    }

    /**
     * Returns an instance of the class. If <code>$id</code> isn't set, this
     * will return a new instance.
     *
     * @param string $id The id of the earnablebadge.
     * @param obf_client $client
     * @return obf_earnable_badge
     */
    public static function get_instance($id = null, $client = null) {
        $obj = null;

        if (is_null($id)) {
          $obj = new self();
          if (!is_null($client)) {
            $obj->_set_client($client);
          }
        } else {
          $obj = new self();
          if (!is_null($client)) {
            $obj->_set_client($client);
          }
          $obj->set_id($id);
          $arr = $obj->_get_client()->get_earn($this->id);
          return $obj->populate_from_array($arr);
        }
        return $obj;
    }

    /**
     * Populates the object's properties from an array.
     *
     * @param array $arr The badge's data as an associative array
     * @see get_instance_from_array()
     * @return obf_badge
     */
    public function populate_from_array($arr) {
      foreach ($arr as $key => $value) {
        $setmethod = 'set_'.$key;
        if (method_exists($this, $setmethod)) {
          $this->{$setmethod}($value);
        }
      }
      return $this;
    }

    /**
     * Gets and returns the earnable badges from OBF.
     *
     * @param obf_client $client The client instance.
     * @param int $visible 1|0|null Get visible, non-visible or all earnables.
     * @return obf_earnable_badge[] The earnable badges.
     */
    public static function get_earnable_badges(obf_client $client = null, $visible = 1) {
        $client = is_null($client) ? obf_client::get_instance() : $client;
        $arr = $client->get_earnable_badges();

        foreach ($arr as $data) {
            $obj = self::get_instance_from_array($data);
            self::$_cache[$obj->get_id()] = $obj;
        }

        return self::$_cache;
    }
    /**
     * Creates a new instance of the class from an array.
     *
     * @param array $arr The badge data as an associative array
     * @return obf_earnable_badge The earnable badge.
     */
    public static function get_instance_from_array($arr) {
        return self::get_instance()->populate_from_array($arr);
    }
 }