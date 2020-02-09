<?php
/**
 * [ControllerEventListener]
 *
 * @link http://www.materializing.net/
 * @author arata
 * @license MIT
 */
class ExamplePluginControllerEventListener extends BcControllerEventListener {

	public $events = [
		'startup',
	];

	/**
	 * startup
	 *
	 * @param CakeEvent $event
	 */
	public function startup(CakeEvent $event) {
		if (!BcUtil::isAdminSystem()) {
			return;
		}

		$Controller = $event->subject();
		$Controller->Components->load('Example.ParticularContentsApprover');
	}

}
