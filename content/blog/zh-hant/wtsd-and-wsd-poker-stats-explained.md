---
title: "WTSD 與 W$SD 全解析：攤牌數據告訴你什麼"
slug: wtsd-and-wsd-poker-stats-explained
locale: zh-Hant
translation_of: wtsd-and-wsd-poker-stats-explained
excerpt: "WTSD 顯示你多常打到攤牌，W$SD 顯示你在攤牌時多常贏。兩者合起來，能看出你是棄牌太多、跟注太多，還是詐唬太少。"
meta_title: "WTSD 與 W$SD 全解析：撲克攤牌數據 — PokerHandsConverter"
meta_description: "WTSD（進入攤牌率）與 W$SD（攤牌贏錢率）衡量什麼、如何搭配解讀、粗略基準，以及它們能揭露的錯誤。"
published: true
published_at: "2026-10-21T09:00:00+00:00"
---

## 兩個數字，一個故事

**WTSD**（Went To ShowDown，進入攤牌率）與 **W$SD**（Won $ at ShowDown，攤牌贏錢率）是兩個告訴你一手牌打到最後會發生什麼事的數據。合起來看，它們是發現翻牌後打法漏洞最快的方式之一。

## 每個數據的意思

- **WTSD** 是你看到的翻牌中，有多少比例打到攤牌。PokerTracker 的 [統計參考資料](https://pt4.pokertracker.com/guides/PT3/general/statistical-reference-guide) 給出了公式：你打到攤牌的次數，除以你看到翻牌的次數。
- **W$SD** 是你的攤牌中有多少比例贏到錢。它包含平分底池，即使你分到的比投入的少也算。

舉例來說，如果你看了 1,000 次翻牌，其中 270 次打到攤牌，WTSD 就是 27%。如果這些攤牌中有 140 次贏到錢，W$SD 約為 52%。

## 如何搭配解讀

| WTSD | W$SD | 常見意義 |
|---|---|---|
| 高 | 低 | 用弱牌跟注太多 |
| 低 | 高 | 棄牌太頻繁，只有強牌才打到攤牌 |
| 高 | 高 | 比平均更鬆但有獲利，或抓詐唬很有效 |
| 低 | 低 | 很少攤牌而且贏不了：檢查你的詐唬與薄價值下注 |

WTSD 低並不一定是好事。從不打到攤牌的玩家可能放棄了太多勝率，而 WTSD 非常高的玩家則是為了看太多牌付出代價。

## 粗略基準

在 6-max 現金桌，常被引用的粗略參考是 WTSD 在 20% 後段、W$SD 在 50% 出頭。請把它們當成起點，而不是規則。它們會隨級別、桌型與你的打法風格而變，而且你的樣本大小比任何單一目標都重要。常詐唬的玩家，W$SD 會刻意偏低。

## 這些數據可能隱藏的錯誤

- **樣本太小。** 攤牌數據需要很多手牌才會穩定。請在樣本夠大之後才比較你的數字。[你需要多少手牌，撲克數據才可靠？](/zh-hant/blog/sample-size-reliable-poker-stats) 說明了原因。
- **混合不同型態。** 6-max 與滿桌的攤牌頻率不同，請分開看。[6-Max 與滿桌現金桌](/zh-hant/blog/6max-vs-full-ring-cash-games) 說明了差異。
- **只看單一數字。** 只有 WTSD 沒有 W$SD，或反過來，都很容易誤讀。

## 下一步

如果你剛接觸 HUD 數據，請從 [VPIP、PFR 與 3-Bet%](/zh-hant/blog/understanding-vpip-pfr-3bet-hud-stats) 開始，它們描述你的翻牌前風格。WTSD 與 W$SD 就是那種風格在翻牌後的結果。
