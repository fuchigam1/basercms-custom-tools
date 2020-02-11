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
		'Blog.BlogPosts.beforeRender',
	];

	/**
	 * blogBlogPostsbeforeRender
	 *
	 * @param CakeEvent $event
	 */
	public function blogBlogPostsbeforeRender(CakeEvent $event) {
		if (!BcUtil::isAdminSystem()) {
			return;
		}

		$Controller = $event->subject();
		$params = $Controller->request->params;

		if ($params['Content']['name'] === 'news') {
			if (in_array($Controller->request->params['action'], ['admin_add', 'admin_edit'])) {
				$Controller->Components->load('ExamplePlugin.SelectBlogPostForPetitCustomFieldComponent');
			}
		}

		return;
	}

}
