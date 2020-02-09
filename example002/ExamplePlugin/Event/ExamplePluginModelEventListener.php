<?php
/**
 * [ModelEventListener]
 *
 * @link http://www.materializing.net/
 * @author arata
 * @license MIT
 */
class ExamplePluginModelEventListener extends BcModelEventListener {

	public $events = array(
		'Blog.BlogPost.afterSave',
		'CuApprover.CuApproverApplication.afterSave',
	);

	/**
	 * blogBlogPostAfterSave
	 *
	 * @param CakeEvent $event
	 */
	public function blogBlogPostAfterSave(CakeEvent $event) {
		$Model = $event->subject();

		if ($Model->data['BlogPost']['blog_content_id'] == 3) {
			$Model->Behaviors->load('ExamplePlugin.BlogEyeCatchCrop', [
				'width' => 616, 'height' => 289,
				'imagecopy' => [
					'mobile_thumb' => ['width' => 296, 'height' => 168, 'force' => true],
				],
			]);
		}

		return true;
	}

	/**
	 * cuApproverCuApproverApplicationAfterSave
	 * - 公開承認機能の下書き保存時、下書きに保存したアイキャッチ画像を切り抜くため
	 *
	 * @param CakeEvent $event
	 */
	public function cuApproverCuApproverApplicationAfterSave(CakeEvent $event) {
		$Model = $event->subject();

		if (Hash::check($Model->data, 'BlogPost')) {
			if (empty($Model->data['BlogPost']['blog_content_id'])) {
				return true;
			}

			if ($Model->data['BlogPost']['blog_content_id'] == 3) {
				$Model->Behaviors->load('ExamplePlugin.BlogEyeCatchCrop', [
					'width' => 616, 'height' => 289,
					'imagecopy' => [
						'mobile_thumb' => ['width' => 296, 'height' => 168, 'force' => true],
					],
				]);
			}
		}

		return true;
	}

}
