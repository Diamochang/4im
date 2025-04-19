# 4chan Improved (4im)

**English** | [简体中文](README_zh-CN.md)

This is a project derived from the currently publicly available [4chan source code](https://github.com/4chan-org/4chan) ([my fork](https://github.com/Diamochang/4chan-fixed)), and aims to improve the entire 4chan using advanced AIGC techniques and a little bit of my own PHP development experience, making it secure, customizable, internationalized, and not overloaded with shit code. This means that you can use 4im to build your own 4chan-style anonymous imageboard with no limitations whatsoever.

> [!IMPORTANT]
> This project is 100% independent of 4chan, and the creator has never worked for 4chan. Any site built using this project **is not equivalent to 4chan**, as their community characteristics are fundamentally different. **The creator and contributors of this project bear no responsibility for any non-technical issues that may arise from using websites built with this project.**

## Background
On April 14, 2025, 4chan got absolutely *wrecked* by a hacker group. Within less than a day, the site went down faster than a house of cards in a hurricane. The personal information of all 4chan administrators was leaked, and the leaked source code appeared on Kiwi Farms. In the aftermath, a number of YouTubers posted videos arguing that the attack was substantial proof that 4chan was dying.

But that's not all: on April 16th, a personal account on GitHub, which appears to be officially owned by 4chan, made the 4chan source code available to the world under the GNU General Public License version 3 via the repository mentioned above. However, I didn't check to see if the source code was the same as that leaked from Kiwi Farms. Late on April 19, I stumbled across some YouTube videos discussing the hack. Curiosity got the better of me, so I followed the breadcrumbs to the GitHub repo. After digging through the code and reading the issues, I was *floored*. A site with so much internet history under its belt didn’t even use Composer, and judging by the issues logged, the code quality could be summed up in five letters: CHAOS. Since the repo was licensed under a recognized free and open-source software license, I figured, why not take this golden opportunity to refactor the whole thing and make it usable for everyone? And here we are.

## Tech Stack
- PHP  
  - Composer  
- MySQL  

## Current Tasks
- [ ] Use tools to hunt down CVE vulnerabilities in the code  
- [ ] Improve code quality

## Help / Support Me
As I am a high school student, I am inevitably busy with school and have to find time to improve this hobby project. If you’re feeling generous, feel free to jump in and help via pull requests or suggest tools that could speed things up for me. I hope that with everyone working together, everyone can have a 4chan of their own.

You can hit me up via the email listed on my profile, Matrix, or whatever floats your boat. If you want to use PGP, my fingerprint is `618f8cbf95f6eaa3b7a9d6d610ecf5f8be40a8ce`.

## License
GNU General Public License version 3 or any later version. A copy of the license is [included](LICENSE) in the repository.

Depending on how things shake out, I might switch to another derivative license like [AGPL](https://www.gnu.org/licenses/agpl.html) or [LGPL](https://www.gnu.org/licenses/lgpl.html).