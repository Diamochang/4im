<div align="center">
  <a href="https://github.com/Diamochang/4im">
    <img src="static/doc/4imlogo.svg" alt="4imロゴ" height="60">
  </a>
</div>

[English](README.md) | [简体中文](README_zh-CN.md) | **日本語**

**本翻译使用 DeepSeek-V3-0324 创建，几乎未经修改，有可能产生语法等方面的错误。我并没有系统的学习日语，所以欢迎学过日语的朋友提交 PR 来改进翻译。**

**この翻訳はDeepSeek-V3-0324を使って作成したもので、文法などに誤りがある可能性がありますが、ほぼ無修正です。 私は日本語を体系的に勉強したわけではないので、PRを投稿して翻訳を改善することに貢献したい日本の友人を歓迎します。 **

----

このプロジェクトは、公開されている[4chanソースコード](https://github.com/zearp/4chan)（[私のフォーク](https://github.com/Diamochang/4chan-fixed)）と[Vichan](https://github.com/vichan-devel/vichan)をベースにしています。既存のオープンソース匿名画像掲示板プロジェクトと最先端の生成AI技術、そして私のPHP開発スキルを活用し、アジアの文化や好みに特化した安全でカスタマイズ性の高い4chan風プラットフォームを目指しています。4imを使えば、制限なく自分専用の匿名画像掲示板を簡単に立ち上げられます。

> [!IMPORTANT]
> このプロジェクトは4chanとは100%無関係で、制作者は4chanで働いた経験もありません。このプロジェクトで構築されたサイトは**4chanと同等ではありません**。コミュニティの性質が根本的に異なります。**本プロジェクトの制作者及び貢献者は、このプロジェクトを使用して発生した非技術的問題について一切の責任を負いません。**

## 背景
2025年4月14日、4chanはハッカーグループによる深刻なサイバー攻撃を受け、1日でサイトがダウン。全管理者の個人情報が流出し、ソースコードはすぐにKiwi Farmsに掲載されました。その後、一部YouTuberが「これで4chanは終わり」と宣言しましたが、結局サイトは復旧しました。

しかし話はここで終わりません。4月16日、"4chan-org"というGitHubアカウントが現れ、4chanのソースコードをGNU General Public License version 3で公開（後にDMCA通知によりzearpのアカウントに移動、元アカウントは"yotsuba"リポジトリでコードを継続公開）。このコードがKiwi Farmsの流出版と一致するかは確認していません。4月19日、たまたま見たYouTubeの攻撃解説動画からGitHubリポジトリを発見。コードとissueをざっと見て衝撃を受けました：ネット文化の象徴的なサイトがComposerを使わず、他のユーザーの評価によればコード品質も散々だったのです。自由なオープンソースライセンスで公開されていたため、特にアジアのウェブマスター向けにリファクタリングしようと決意し、このプロジェクトが誕生しました。

## 使用している他のオープンソースプロジェクト
- Composer ([公式サイト](https://getcomposer.org/) | [ソースコード](https://github.com/composer/composer))
- Vichan ([公式サイト](https://vichan.info/) | [ソースコード](https://github.com/vichan-devel/vichan))
- PHP CS Fixer ([公式サイト](https://cs.symfony.com) | [ソースコード](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer))
- PHPStan ([公式サイト](https://phpstan.org/) | [ソースコード](https://github.com/phpstan/phpstan))  

## 今後の予定
- [ ] Vichan由来のコード改善
- [ ] UIを4chanにさらに近づける
- [ ] アジアン・テイストを追加
- [ ] 国際化の再構築

## 協力/支援について
高校生ということもあり、学校で忙しい中、趣味プロジェクトの改善時間を捻出しています。もし気が向いたら、プルリクエストで協力したり、作業効率化のためのツールを提案して頂けると嬉しいです。みんなで協力すれば、誰もがアジア風の4chanを持てるようになるはずです。

連絡はプロフィール記載のメール、Matrixなどお好きな方法でどうぞ。PGPを使いたい方のために、私のフィンガープリントは`618f8cbf95f6eaa3b7a9d6d610ecf5f8be40a8ce`です。

## ライセンス
GNU General Public License version 3以降。ライセンスのコピーはリポジトリに[同梱](LICENSE)されています。

今後の展開次第では、[AGPL](https://www.gnu.org/licenses/agpl.html)や[LGPL](https://www.gnu.org/licenses/lgpl.html)など別の派生ライセンスに変更する可能性があります。