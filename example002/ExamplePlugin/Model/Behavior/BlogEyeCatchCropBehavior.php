<?php
/**
 * [Behavior] BlogEyeCatchCropBehavior
 * - ブログ記事にアイキャッチ画像をアップロードした際に、画像を加工して保存する
 * - 保存された画像の中心から指定サイズで切り抜く
 * - 利用方法: $Model->Behaviors->load('PLUGINNAME.BlogEyeCatchCrop', ['width' => 600, 'height' => 250]);
 *
 * @link http://www.materializing.net/
 * @author arata
 * @license MIT
 */
class BlogEyeCatchCropBehavior extends ModelBehavior {

	/**
	 * Defaults
	 *
	 * @var array
	 */
	private $_defaults = array(
		'field_name' => 'eye_catch',
		'width' => 0,
		'height' => 0,
		'imagecopy' => [
			// 'mobile_thumb' => [	// imagecopyに設定されているキー名
			// 	'width' => 296,		// imagecopyで生成されたサムネイルの横幅サイズを指定
			// 	'height' => 168,	// imagecopyで生成されたサムネイルの縦幅サイズを指定
			// 	'force' => true,	// imagecopyでのサムネイルが生成されていない場合、強制的にサムネイルを作成されるかどうかの指定
			// ],
		],
	);

	private $specifiedWidth = 0;	// 切り抜き時横幅サイズ
	private $specifiedHeight = 0;	// 切り抜き時縦幅サイズ
	private $targetFieldName = '';	// 切り抜く対象フィールド名
	private $targetImagecopy = [];		// サムネイル生成に対する設定

	/**
	 * setup
	 *
	 * @param Model $model Model using this behavior
	 * @param array $config Configuration settings for $model
	 */
	public function setup(Model $model, $config = array()) {
		parent::setup($model, $config);

		$this->_defaults = Hash::merge($this->_defaults, $config);
		$this->specifiedWidth = $this->_defaults['width'];
		$this->specifiedHeight = $this->_defaults['height'];
		$this->targetFieldName = $this->_defaults['field_name'];
		$this->targetImagecopy = $this->_defaults['imagecopy'];

		/**
		 * 専用ログ
		 */
		if (!defined('LOG_BEHAVIOR_BLOG_EYE_CATCH_CROP')) {
			define('LOG_BEHAVIOR_BLOG_EYE_CATCH_CROP', 'log_behavior_blog_eye_catch_crop');
			CakeLog::config('log_behavior_blog_eye_catch_crop', array(
				'engine' => 'FileLog',
				'types' => array('log_behavior_blog_eye_catch_crop'),
				'file' => 'log_behavior_blog_eye_catch_crop',
				'size' => '1MB',
				'rotate' => 5,
			));
		}
	}

	/**
	 * afterSave
	 *
	 * @param Model $model
	 * @param bool $created
	 * @param array $options
	 */
	public function afterSave(Model $model, $created, $options = []) {
		parent::afterSave($model, $created, $options);

		if (!$this->isAble($model)) {
			return;
		}

		if ($model->name === 'BlogPost') {
			$BcUploadBehavior = $model->Behaviors->BcUpload;
			$savePath = $BcUploadBehavior->savePath[$model->name];
			$eyeCatchPath = $model->data[$model->name][$this->targetFieldName];
			$uploadedFile = $savePath . $eyeCatchPath;
		} else if ($model->name === 'CuApproverApplication') {
			$BcUploadBehavior = $model->Behaviors->BcUpload;
			$savePath = $BcUploadBehavior->savePath[$model->name];
			// アイキャッチ画像の正しいパスを取得するためには、逆変換掛けて画像パスを取る必要があるため
			$draftData = BcUtil::unserialize($model->data[$model->name]['draft']);
			$eyeCatchPath = $draftData['BlogPost'][$this->targetFieldName];
			$uploadedFile = $savePath . $eyeCatchPath;
		}

		$pathinfo = pathinfo($uploadedFile);
		// $pathinfo['dirname'] = "/var/www/html/files/blog/3/blog_posts/2020/01"
		// $pathinfo['basename'] = "00000018_eye_catch.jpg"
		// $pathinfo['extension'] = "jpg"
		// $pathinfo['filename'] = "00000018_eye_catch"
		// 加工元となる画像の情報を取るため
		$uploadedImage = getimagesize($uploadedFile);
		$uploadedImageSize = [
			'width' => $uploadedImage[0],
			'height' => $uploadedImage[1],
			'type' => $uploadedImage[2],
		];

		// 指定画像を切り抜き加工する: アップロードしたアイキャッチ画像そのものに対する加工
		if ( ($uploadedImageSize['width'] < $this->specifiedWidth) && ($uploadedImageSize['height'] < $this->specifiedHeight) ) {
			// 設定サイズより横幅、縦幅の両方が小さい画像の場合: 16:9に収まるようにトリミング
			$result = $this->calculateSixteenPairNine($uploadedImageSize['width'], $uploadedImageSize['height']);
			$this->makeCropImage($uploadedFile, $uploadedFile, $result['width'], $result['height']);
		} elseif ( ($uploadedImageSize['width'] > $this->specifiedWidth) && ($uploadedImageSize['height'] < $this->specifiedHeight) ) {
			//  横のみ大きい画像の場合: 縦を基準に16:9に収まるようにトリミング
			$result = $this->calculateSixteenPairNine($uploadedImageSize['width'], $uploadedImageSize['height']);
			$this->makeCropImage($uploadedFile, $uploadedFile, $result['width'], $result['height']);
		} elseif ( ($uploadedImageSize['width'] < $this->specifiedWidth) && ($uploadedImageSize['height'] > $this->specifiedHeight) ) {
			// 縦のみ大きい画像の場合: 横を基準に16:9に収まるようにトリミング
			$result = $this->calculateSixteenPairNine($uploadedImageSize['width'], $uploadedImageSize['height']);
			$this->makeCropImage($uploadedFile, $uploadedFile, $result['width'], $result['height']);
		} elseif ( ($uploadedImageSize['width'] == $this->specifiedWidth) && ($uploadedImageSize['height'] == $this->specifiedHeight) ) {
			// アップロードした画像が切り抜き指定サイズと同じ場合は画像に対して何もしない
		} else {
			// 設定サイズより横幅も縦幅も大きい画像
			$this->makeCropImage($uploadedFile, $uploadedFile, $this->specifiedWidth, $this->specifiedHeight);
		}

		/**
		 * サムネイル画像が生成されている場合、サムネイル画像も切り抜き指定サイズと同じ比率で切り抜く
		 */
		if (isset($BcUploadBehavior->settings[$model->name]['fields'][$this->targetFieldName]['imagecopy'])) {
			$settingImagecopy = $BcUploadBehavior->settings[$model->name]['fields'][$this->targetFieldName]['imagecopy'];
			foreach ($settingImagecopy as $imagecopyKeyName => $copySetting) {
				// imagecopyで生成されているサムネイルのパス
				$fullFileName = Hash::get($copySetting, 'prefix') . $pathinfo['filename'] . Hash::get($copySetting, 'suffix') . '.' . $pathinfo['extension'];
				$copyFile = $pathinfo['dirname'] . DS . $fullFileName;

				if (Hash::check($this->targetImagecopy, $imagecopyKeyName)) {
					// 生成されるサムネイルに対して、behavior側で設定指定を持つ場合、指定サイズでサムネイルを切り抜き直す
					if (file_exists($copyFile)) {
						$this->makeCropImage($copyFile, $copyFile, $this->targetImagecopy[$imagecopyKeyName]['width'], $this->targetImagecopy[$imagecopyKeyName]['height']);
						continue;
					} else {
						// サムネイルが生成されていない場合でも、強制サムネイル作成指定がある場合は、大元画像を元にサムネイル作る
						if ($this->targetImagecopy[$imagecopyKeyName]['force']) {
							$this->makeCropImage($uploadedFile, $copyFile, $this->targetImagecopy[$imagecopyKeyName]['width'], $this->targetImagecopy[$imagecopyKeyName]['height']);
						}
					}
				} else {
					// 生成されるサムネイルに対して、behavior側で設定指定を持たない場合、生成されているサムネイルを、基本設定の比率を保ったサイズで切り抜き直す
					if (file_exists($copyFile)) {
						// 加工元となる画像の情報を取るため
						$distinationImage = getimagesize($copyFile);
						$distinationImgSize = [
							'width' => $distinationImage[0],
							'height' => $distinationImage[1],
							'type' => $distinationImage[2],
						];
						// 616 x 289の画像を 横2000で縦も同じ比率を出す
						// 2000 / 616 = x
						// 289 * x =461
						// 2000 / 616 * 289
						$copySpecifiedHeight = floor($distinationImgSize['width'] / $this->specifiedWidth * $this->specifiedHeight);
						$this->makeCropImage($copyFile, $copyFile, $distinationImgSize['width'], $copySpecifiedHeight);
					}
				}

			}
		}
	}

	/**
	 * 指定した画像を、中心から指定サイズで切り抜く
	 *
	 * @param string $uploadedFile アップロードした画像ファイルのパス。切り抜き元画像
	 * @param string $newDistination 切り抜いた画像の作成先
	 * @param int $specifiedWidth 中心からの切り抜き時指定横幅
	 * @param int $specifiedHeight 中心からの切り抜き時指定高さ
	 */
	private function makeCropImage($uploadedFile, $newDistination, $specifiedWidth, $specifiedHeight) {
		// 加工元となるアイキャッチ画像の情報を取るため
		$distinationImage = getimagesize($uploadedFile);
		$distinationImgSize = [
			'width' => $distinationImage[0],
			'height' => $distinationImage[1],
			'type' => $distinationImage[2],
		];

		// 元となる画像のオブジェクトを生成
		switch($distinationImgSize['type']) {
			case IMAGETYPE_GIF:
				$srcImage = imagecreatefromgif($uploadedFile);
				break;
			case IMAGETYPE_JPEG:
				$srcImage = imagecreatefromjpeg($uploadedFile);
				break;
			case IMAGETYPE_PNG:
				$srcImage = imagecreatefrompng($uploadedFile);
				break;
			default:
				return;
		}

		// 土台となる画像を作成
		$newImage = $this->createBaseImage($specifiedWidth, $specifiedHeight);

		// 求める画像サイズとの比を求める
		$width = $distinationImgSize['width'];
		$height = $distinationImgSize['height'];
		$width_gap = $width / $specifiedWidth;
		$height_gap = $height / $specifiedHeight;

		if ($width_gap < $height_gap) {
			// 横より縦の比率が大きい場合は、求める画像サイズより縦長
			// → 縦の上下をカット
			$cut = ceil((($height_gap - $width_gap) * $specifiedHeight) / 2);
			imagecopyresampled($newImage, $srcImage, 0, 0, 0, $cut, $specifiedWidth, $specifiedHeight, $width, $height - ($cut * 2));
		} else if ($height_gap < $width_gap) {
			// 縦より横の比率が大きい場合は、求める画像サイズより横長
			// → 横の左右をカット
			$cut = ceil((($width_gap - $height_gap) * $specifiedWidth) / 2);
			imagecopyresampled($newImage, $srcImage, 0, 0, $cut, 0, $specifiedWidth, $specifiedHeight, $width - ($cut * 2), $height);
		} else {
			// 縦横比が同じなら、そのまま縮小
			imagecopyresampled($newImage, $srcImage, 0, 0, 0, 0, $specifiedWidth, $specifiedHeight, $width, $height);
		}
		imagedestroy($srcImage);

		switch($distinationImgSize['type']) {
			case IMAGETYPE_GIF:
				imagegif($newImage, $newDistination);
				break;
			case IMAGETYPE_JPEG:
				imagejpeg($newImage, $newDistination, 100);
				break;
			case IMAGETYPE_PNG:
				imagepng($newImage, $newDistination);
				break;
			default:
				return false;
		}
		imagedestroy($newImage);
	}

	/**
	 * ベース画像を作成する
	 *
	 * @param int 幅
	 * @param int 高さ
	 * @return Image イメージオブジェクト
	 */
	private function createBaseImage($width, $height) {
		$image = imagecreatetruecolor($width, $height);
		$color = imagecolorallocatealpha($image, 255, 255, 255, 0);
		imagealphablending($image, true);
		imagesavealpha($image, true);
		imagefill($image, 0, 0, $color);
		return $image;
	}

	/**
	 * 切り抜き加工を実行できる状態か判定する
	 *
	 * @param Object $model
	 * @return boolean
	 */
	private function isAble($model) {
		if (!$this->specifiedWidth || !$this->specifiedHeight) {
			CakeLog::write(LOG_BEHAVIOR_BLOG_EYE_CATCH_CROP, '[BlogEyeCatchCropBehavior] 幅指定、高さ指定がないため切り抜きできません。');
			CakeLog::write(LOG_BEHAVIOR_BLOG_EYE_CATCH_CROP, '[BlogEyeCatchCropBehavior]$this->specifiedWidth ' . $this->specifiedWidth);
			CakeLog::write(LOG_BEHAVIOR_BLOG_EYE_CATCH_CROP, '[BlogEyeCatchCropBehavior]$this->specifiedHeight ' . $this->specifiedHeight);
			return false;
		}
		if (!$this->targetFieldName) {
			CakeLog::write(LOG_BEHAVIOR_BLOG_EYE_CATCH_CROP, '[BlogEyeCatchCropBehavior] 対象フィールドの指定がないため切り抜きできません。');
			CakeLog::write(LOG_BEHAVIOR_BLOG_EYE_CATCH_CROP, '[BlogEyeCatchCropBehavior]$this->targetFieldName ' . $this->targetFieldName);
			return false;
		}

		$uploaded = false; // アイキャッチ画像ファイルがアップロードされたかどうかの判定
		$uploadedFile = ''; // アップロードされた画像の実パス

		if ($model->name === 'BlogPost') {
			if (isset($model->data[$model->name][$this->targetFieldName . '_'])) {
				$uploaded = true;
			}
			if (!$uploaded) {
				return false;
			}

			$BcUploadBehavior = $model->Behaviors->BcUpload;
			$savePath = $BcUploadBehavior->savePath[$model->name];
			$eyeCatchPath = $model->data[$model->name][$this->targetFieldName];
			$uploadedFile = $savePath . $eyeCatchPath;

			// アイキャッチ画像が存在するなら処理継続する
			if (file_exists($uploadedFile)) {
				return true;
			}

		} elseif ($model->name === 'CuApproverApplication') {
			// 公開承認機能の下書きにアイキャッチ画像を上げた場合
			if ( isset($_FILES['data']['tmp_name']['BlogPost'][$this->targetFieldName]) ) {
				if ( is_uploaded_file($_FILES['data']['tmp_name']['BlogPost'][$this->targetFieldName]) ) {
					$uploaded = true;
				}
			}
			if (!$uploaded) {
				return false;
			}

			$BcUploadBehavior = $model->Behaviors->BcUpload;
			if ($BcUploadBehavior) {
				$savePath = $BcUploadBehavior->savePath[$model->name];
				// アイキャッチ画像の正しいパスを取得するためには、逆変換掛けて画像パスを取る必要があるため
				$draftData = BcUtil::unserialize($model->data[$model->name]['draft']);
				$eyeCatchPath = $draftData['BlogPost'][$this->targetFieldName];
				$uploadedFile = $savePath . $eyeCatchPath;
			}

			// アイキャッチ画像が存在するなら処理継続する
			if (file_exists($uploadedFile)) {
				return true;
			}

		} else {
			return false;
		}

		return false;
	}

	/**
	 * 指定した横幅と高さを元に、19:6 で計算した横幅と高さを計算して返す
	 *
	 * @param int $width
	 * @param int $height
	 * @return array
	 */
	private function calculateSixteenPairNine($width, $height) {
		$result = [
			'width' => $width,
			'height' => $height,
		];

		if ($width > $height) {
			// 縦幅を求める: this.num * 9 / 16
			$result['height'] = floor($width * 9 / 16);
		} else {
			// 横幅を求める: this.num * 16 / 9
			$result['width'] = floor($height * 16 / 9);
		}

		return $result;
	}

}
