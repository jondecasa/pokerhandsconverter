---
title: "撲克中的 PFR 是什麼？翻牌前加注率與 VPIP–PFR 差距"
slug: what-is-pfr-in-poker-vpip-pfr-gap
locale: zh-Hant
translation_of: what-is-pfr-in-poker-vpip-pfr-gap
excerpt: "PFR 是玩家在翻牌前加注的頻率。以下說明公式、哪些算，以及 VPIP 與 PFR 的差距如何區分積極玩家與被動的跟注站。"
meta_title: "撲克的 PFR 是什麼？翻牌前加注率與差距 — PokerHandsConverter"
meta_description: "撲克 PFR 的意義、如何計算翻牌前加注百分比、6-max 的常見數字，以及 VPIP 與 PFR 的差距如何顯示誰積極、誰被動。"
published: true
published_at: "2026-12-20T09:00:00+00:00"
---

## PFR 的意義

**PFR** 是 *preflop raise*（翻牌前加注）的縮寫。它是玩家在翻牌前加注的手牌百分比，是 [VPIP](/zh-hant/blog/what-is-vpip-in-poker-meaning-formula) 的攻擊性搭檔：VPIP 告訴你某人玩多少手牌，PFR 告訴你其中有多少是靠加注來玩的。

```
PFR = 翻牌前加注的手牌數 / 你被發到的手牌數
```

不論是開局加注、3-bet、4-bet，還是對平跟者的隔離加注，都算加注。跟注永遠不算在 PFR 內。

## 一個實際例子

在 1,000 手牌中，你在翻牌前加注 180 次。你的 PFR 是 180 / 1,000 = 18%。

## PFR 與 RFI 不同

[Raise first in](/zh-hant/blog/raise-first-in-rfi-poker-stat-explained) 只計算在無人進池的底池中的第一次加注。PFR 計算每一次翻牌前加注，包括 3-bet 與 4-bet，所以它也反映你多常再加注。兩位玩家可以有相同的 PFR，卻有非常不同的開局範圍。

## VPIP–PFR 的差距

對 PFR 最有用的做法，是把它與 VPIP 相減。這個差距告訴你一位玩家進池的手牌中，有多少是*跟注*。

| 風格 | VPIP / PFR 範例 | 差距說明 |
|---|---|---|
| 緊凶（TAG） | 22 / 18 | 差距小：打的牌大多是加注 |
| 鬆凶（LAG） | 30 / 25 | 範圍寬，但大多是加注 |
| 被動的「跟注站」 | 35 / 9 | 差距巨大：常跟注、很少加注 |
| 非常緊 | 12 / 10 | 手牌少，大多是加注 |

作為粗略的參考，許多會贏錢的 6-max 老手差距只有幾個百分點。差距達到十個百分點以上，通常代表一位在翻牌前跟注太多的玩家。

## 用在對手身上

- **差距小、VPIP 中等。** 打加注風格的老手。他們很少只跟注，所以當他們只是跟注時，範圍往往比看起來更窄。
- **差距大。** 被動而鬆的玩家。他們的加注非常強，但跟注可以是任何牌。翻牌後可以對他們更薄地下注做價值，並避免詐唬他們。
- **PFR 接近 VPIP 且 VPIP 很低。** 極緊的玩家。他們的加注幾乎都是頂級強牌。

## 用在自己身上

如果你翻牌前常常跟注、很少加注，你的範圍有上限，也容易被對付。把一些跟注改成加注，尤其是在後位，以及在盲注位面對偷盲時，通常能改善你的結果。請看你的 [3-bet 與擠壓頻率](/zh-hant/blog/squeeze-percentage-poker-stat-explained)，了解你在別人開局後多常施加壓力。

想看完整的 HUD 基礎，請見[了解 VPIP、PFR 與 3-bet](/zh-hant/blog/understanding-vpip-pfr-3bet-hud-stats)。

## 一個提醒

和所有數據一樣，PFR 需要樣本。[需要多少手牌，你的數據才可靠](/zh-hant/blog/sample-size-reliable-poker-stats)說明了幾百手牌可能多麼容易誤導你。

## 確保追蹤軟體讀到的是正確的手牌

這裡的每一項數據，都取決於背後的手牌紀錄是否準確。如果你在 CoinPoker 打牌，請在匯入 PokerTracker 4 或 Hold'em Manager 3 之前，先[用 PokerHandsConverter 轉換手牌](/zh-hant)，讓追蹤軟體顯示的數字就是你實際打出來的結果。
