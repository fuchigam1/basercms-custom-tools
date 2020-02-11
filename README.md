# baserCMS カスタマイズ例

baserCMSのカスタマイズ例を載せてます。  
コンポーネント単位、ビヘイビア単位で利用できる内容にまとめ、複製利用できるようにしてあります。  


## 構成

exampleNO 毎に、カスタマイズ事例を載せてます。

### 留意点

- 各例のプラグイン名（ExamplePlugin）はサンプルです。自身で利用するプラグイン名に置き換えてください。


## 事例一覧

### example001: Component
公開承認機能を特定のコンテンツのみで有効化するコンポーネント。

- /ExamplePlugin/Event/ExampleControllerEventListener.php
    - startup() でコンポーネント呼び出す
- /ExamplePlugin/Controller/Component/ParticularContentsApproverComponent.php
    - コンテンツ名で有効化するコンテンツを指定している例


### example002: Behavior
ブログ記事のアイキャッチ画像を、指定サイズで切り抜きするビヘイビア。

- /ExamplePlugin/Event/ExamplePluginModelEventListener.php
    - afterSave() でビヘイビア呼び出す
    - 公開承認機能がなければ cuApproverCuApproverApplicationAfterSave() は不要
- /ExamplePlugin/Model/Behavior/BlogEyeCatchCropBehavior.php
    - 設定サイズより横幅も縦幅も大きい画像は切り抜き加工する
    - 設定サイズより横幅、縦幅の両方が小さい画像の場合: 16:9にトリミング
    - 横のみ大きい画像の場合: 縦を基準に16:9に収まるようにトリミング
    - 縦のみ大きい画像の場合: 横を基準に16:9に収まるようにトリミング
    - アップロードした画像が切り抜き指定サイズと同じ場合は画像に対して何もしない
    - imagicopyに対してサイズ指定を持たない場合、生成されているサムネイルを、基本設定の比率を保ったサイズで切り抜き直す

```
$Model->Behaviors->load('ExamplePlugin.BlogEyeCatchCrop', [
	// アイキャッチ画像の切り抜きサイズを指定
	'width' => {{int_number}}, 'height' => {{int_number}},
	'imagecopy' => [
		// アイキャッチ画像のPCサイズの切り抜きサイズを指定。force: true で画像生成がなされないサイズの場合でも強制作成できる
		'thumb' => ['width' => {{int_number}}, 'height' => {{int_number}}],
		// アイキャッチ画像の携帯サイズの切り抜きサイズを指定。force: true で画像生成がなされないサイズの場合でも強制作成できる
		'mobile_thumb' => ['width' => {{int_number}}, 'height' => {{int_number}}, 'force' => true],
	],
]);
```


### example003: Component
プチカスタムフィールドのselectタイプフィールドで、ブログ記事を選択できるようにするコンポーネント。

- /ExamplePlugin/Event/ExamplePluginControllerEventListener.php
    - blogBlogPostsbeforeRender() でコンポーネント呼び出す
- /ExamplePlugin/Controller/Component/SelectBlogPostForPetitCustomFieldComponent.php
    - selectタイプフィールドの選択内容にブログ記事を設定している例


## Thanks

- [http://basercms.net/](http://basercms.net/)
- [http://wiki.basercms.net/](http://wiki.basercms.net/)
- [http://cakephp.jp](http://cakephp.jp)
