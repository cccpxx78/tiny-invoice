<?php
/**
 * Module Setting Configuration
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

use \Skeleton\Core\Web\Template;
use \Skeleton\Core\Web\Module;
use \Skeleton\Core\Web\Session;

class Web_Module_Setting_Configuration extends Module {
	/**
	 * Login required ?
	 * Default = yes
	 *
	 * @access public
	 * @var bool $login_required
	 */
	public $login_required = true;

	/**
	 * Template to use
	 *
	 * @access public
	 * @var string $template
	 */
	public $template = 'setting/configuration.twig';

	/**
	 * Display method
	 *
	 * @access public
	 */
	public function display() {
		$template = Template::Get();

		if (isset($_POST['setting'])) {
			if (!isset($_POST['setting']['enable_click_post'])) {
				$_POST['setting']['enable_click_post'] = false;
			}
			foreach ($_POST['setting'] as $key => $value) {
				try {
					$setting = Setting::get_by_name($key);
				} catch (Exception $e) {
					$setting = new Setting();
					$setting->name = $key;
				}
				$setting->value = $value;
				$setting->save();
			}

			Session::set_sticky('message', 'updated');
			Session::Redirect('/setting/configuration');
		}

		$template->assign('settings', Setting::get_as_array());

		Skin_Email::synchronize();
		$skin_emails = Skin_Email::get_all();
		$template->assign('skin_emails', $skin_emails);

		Skin_Pdf::synchronize();
		$skin_pdfs = Skin_Pdf::get_all();
		$template->assign('skin_pdfs', $skin_pdfs);
	}

	/**
	 * Secure
	 *
	 * @access public
	 */
	public function secure() {
		return 'admin.setting';
	}
}
