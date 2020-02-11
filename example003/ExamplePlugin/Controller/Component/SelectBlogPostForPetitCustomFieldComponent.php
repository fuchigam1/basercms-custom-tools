<?php
/**
 * [Component] SelectBlogPostForPetitCustomFieldComponent
 * - プチカスタムフィールドのselectタイプフィールドで、ブログ記事を選択できるようにする
 *
 * @link http://www.materializing.net/
 * @author arata
 * @license MIT
 */
class SelectBlogPostForPetitCustomFieldComponent extends Component {

	/**
	 * beforeRender
	 *
	 * @param Controller $Controller
	 */
	public function beforeRender(Controller $Controller) {
		parent::beforeRender($Controller);

		$fieldConfigField = array();

		foreach ($Controller->viewVars['fieldConfigField'] as $key => $fieldConfig) {
			if ($fieldConfig['PetitCustomFieldConfigField']['field_type'] === 'select') {
				if ($fieldConfig['PetitCustomFieldConfigField']['field_name'] === 'select_test') {
					$dataList = $this->getBlogPostList(1);
					$choiceText = $this->formatToTextType($dataList);
					if ($choiceText) {
						$fieldConfig['PetitCustomFieldConfigField']['choices'] = $choiceText;
					}
				}
			}
			$fieldConfigField[$key] = $fieldConfig;
		}

		$Controller->viewVars['fieldConfigField'] = $fieldConfigField;
	}

	/**
	 * 指定したブログコンテンツIDから、公開状態の記事リストを取得する
	 *
	 * @param int $blogContentId
	 * @return array
	 */
	private function getBlogPostList($blogContentId) {
		$BlogPostModel = ClassRegistry::init('Blog.BlogPost');

		$condition = $BlogPostModel->getConditionAllowPublish();
		$condition['BlogPost.blog_content_id'] = $blogContentId;

		$dataList = $BlogPostModel->find('list', [
			'conditions' => $condition,
			'order' => 'BlogPost.posts_date DESC, BlogPost.id DESC',
			'recursive' => -1,
			'callbacks' => false,
		]);

		return $dataList;
	}

	/**
	 * 配列をテキスト形式（プチカスタムフィールドのselectタイプ用の形式）に変換する
	 *
	 * @param array $dataList
	 * @return string
	 */
	private function formatToTextType($dataList) {
		if (!$dataList) {
			return '';
		}

		$textList = array();
		// 例: "ラベル名1:1\r\nラベル名2:2\r\nラベル名3:3";
		foreach ($dataList as $postId => $postName) {
			$textList[] = $postName . ':' . $postId;
		}

		return implode("\r\n", $textList);
	}

}
