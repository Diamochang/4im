<div align="center">
  <a href="https://github.com/Diamochang/4im">
    <img src="static/doc/4imlogo.svg" alt="4im 标志" height="60">
  </a>
</div>

[English](README.md) | **简体中文** | [日本語](README_jp.md)

这是一个基于目前公开的 [4chan 源代码](https://github.com/zearp/4chan)（[我的 Fork](https://github.com/Diamochang/4chan-fixed)）和 [Vichan](https://github.com/vichan-devel/vichan) 衍生而来的项目，旨在利用 4chan 源代码被公开之前开源世界已经存在的匿名聊天板项目、先进的生成式人工智能技术和本人的一点 PHP 开发经验，针对亚洲的文化和习惯提供一个安全、可定制性强、国际化的类 4chan 方案。这就意味着，你可以使用 4im 低门槛搭建自己的 4chan 风格匿名聊天板，不受任何限制。

> [!IMPORTANT]
> 本项目完全独立于 4chan，项目的创建者也从未在 4chan 工作。使用本项目搭建的网站**不能等同于 4chan**，因为它们的社区特性与 4chan 不相同。**本项目创建者及全体贡献者对因使用由本项目搭建的网站产生的任何非技术性问题不负任何连带责任。**

## 背景
2025 年 4 月 14 日，4chan 遭遇某骇客组织的猛烈攻击，不到一天网站迅速崩溃。4chan 全体管理员的个人信息惨遭泄露，随后 Kiwi Farms 上出现了泄露的源代码。此日之后，有一些 YouTuber 发表视频，认为此次攻击事件实质性地证明 4chan 正在走向死亡。不过，4chan 后面还是恢复正常了。

可这事还没完。4 月 16 日，GitHub 上出现了名为“4chan-org”的个人账号，随后该账号用 GNU 通用公共许可证第三版向世人公开了 4chan 的源代码（后来由于收到 DMCA 通知，迁移到 zearp 名下，原账号新开“yotsuba”仓库继续存储源代码）。不过，我没有核对这里的源代码是否与 Kiwi Farms 上泄露的代码一致。4 月 19 日凌晨，我在 YouTube 上偶然划到有关此次攻击的评论视频，然后顺藤摸瓜找到了 GitHub 上的仓库。翻阅代码和 Issues 后，我感到无比震惊：这么一个承载了国际互联网诸多记忆的网站，居然不用 Composer，而且从其他用户的 Issues 来看它的代码质量可以用一个成语形容：混乱不堪。既然仓库使用一个公认的自由和开源软件许可证，我就想着利用这个机会自行重构 4chan 的源代码供大家（特别是亚洲的站长们）使用，于是就有了你现在所看到的这个项目。

## 使用的部分开源项目
- Composer（[官网](https://getcomposer.org/) | [源代码](https://github.com/composer/composer)）
- Vichan（[官网](https://vichan.info/) | [源代码](https://github.com/vichan-devel/vichan)）
- PHP CS Fixer（[官网](https://cs.symfony.com) | [源代码](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer)）
- PHPStan（[官网](https://phpstan.org/) | [源代码](https://github.com/phpstan/phpstan)）

## 待办事项
- [ ] 改进引自 Vichan 的代码
- [ ] 使界面更贴近 4chan
- [ ] 添加亚洲风味
- [ ] 再国际化

## 帮助 / 支持我
由于我是一位高中生，学业难免繁忙，只能抽时间来完善这个兴趣项目。你可以通过 Pull Request 来协助我完成一些任务，也可以向我提供一些工具使我能够高效开发。我希望在大家的共同努力下，人人都能拥有一个亚洲风味的 4chan。

你可以通过 GitHub 个人资料页的公开电子邮件、Matrix 等联系我了解更多。如果想要使用 PGP，我的指纹是`618f8cbf95f6eaa3b7a9d6d610ecf5f8be40a8ce`。

## 许可证
GNU Affero 通用公共许可证第三版或任何以后版本。许可证的副本已经[包含](LICENSE)在仓库中。