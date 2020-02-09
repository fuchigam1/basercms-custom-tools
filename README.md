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
- /ExamplePlugin/Controller/Component/ParticularContentsApproverComponent.php
    - startup() でコンポーネント呼び出す


## Thanks

- [http://basercms.net/](http://basercms.net/)
- [http://wiki.basercms.net/](http://wiki.basercms.net/)
- [http://cakephp.jp](http://cakephp.jp)
