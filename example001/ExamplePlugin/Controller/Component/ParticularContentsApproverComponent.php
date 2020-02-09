<?php
/**
 * [Component]
 *
 * @link http://www.materializing.net/
 * @author arata
 * @license MIT
 */
class ParticularContentsApproverComponent extends Component {

	/**
	 * startup
	 *
	 * @param Controller $Controller
	 */
	public function startup(Controller $Controller) {
		// 特定のコンテンツのときのみ、公開承認機能を有効化する
		// isset 判定は、コンテンツ一覧画面には一意の判定コンテンツが存在しないため必要
		if (isset($Controller->request->params['Content'])) {
			$currentContent = $Controller->request->params['Content'];
			if ($currentContent['name'] !== 'news') {
				// newsコンテンツ以外のときは、公開承認として機能させる対象設定自体を空にすることで、他で動かないようにする
				Configure::write('CuApprover.targets', array());
			}
		}

		parent::startup($Controller);
	}

}
