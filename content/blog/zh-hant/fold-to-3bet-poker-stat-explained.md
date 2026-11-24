---
title: "面對 3-Bet 的棄牌率（Fold to 3-Bet）：意義與剝削方法"
slug: fold-to-3bet-poker-stat-explained
locale: zh-Hant
translation_of: fold-to-3bet-poker-stat-explained
excerpt: "Fold to 3-bet 顯示一位玩家被再加注時多常放棄自己的開池。以下說明它怎麼計算、數字代表什麼，以及如何調整你的 3-bet 與自己的防守。"
meta_title: "面對 3-Bet 的棄牌率：意義、公式與範圍 — PokerHandsConverter"
meta_description: "Fold to 3-bet 衡量什麼、如何計算、它與 4-bet% 和跟注的關係、6-max 的常見數字，以及如何剝削棄牌太多或太少的玩家。"
published: true
published_at: "2026-11-26T09:00:00+00:00"
---

## Fold to 3-bet 衡量什麼

當你開池加注，而有人再加注時，這次再加注就是 **3-bet**。**Fold to 3-bet** 是一位玩家在開池後面對 3-bet 的情況中，選擇棄牌的比例。

```
fold to 3-bet = 面對 3-bet 時棄牌的次數 / 加注後面對 3-bet 的次數
```

它是 [3-bet%](/zh-hant/blog/understanding-vpip-pfr-3bet-hud-stats) 的防守面，也是 [4-bet%](/zh-hant/blog/4-bet-percentage-poker-stat-explained) 的對應數據。

## 三種可能的回應加起來是 100%

面對 3-bet 時，玩家可以棄牌、跟注或 4-bet。在同一批機會中，fold to 3-bet、跟注 3-bet 與 4-bet% 加起來是 100%。

一個實際例子：一位對手開池後面對 3-bet 共 25 次。他們棄牌 16 次、跟注 6 次、4-bet 3 次。他們的 fold to 3-bet 是 16 / 25 = 64%，跟注佔 24%，4-bet 佔 12%。

## 這個數字告訴你什麼

在 6-max 現金桌，老手的 fold to 3-bet 落在 55% 到 65% 左右很常見。一如既往，這只是參考，它取決於位置以及是誰在 3-bet。

- **fold to 3-bet 偏高**（例如 75% 以上）。這位玩家開池後被施壓就放棄。你可以用更寬的範圍 3-bet 他們，包括帶有阻擋牌或一些勝率、你不會拿來對付會反擊的人的手牌。
- **fold to 3-bet 偏低**（例如低於 45%）。這位玩家大量防守，不是跟注就是 4-bet。從你的 3-bet 範圍中拿掉詐唬，並用強牌做價值 3-bet，因為他們會付錢給你。
- **數據中 4-bet 的比例很大。** 如果非棄牌的大多是 4-bet，代表對手很積極，好的回應是多用你的強牌跟注來誘捕他們。

## 位置與情境會改變它

- **有位置與沒位置。** 玩家在有位置時比沒位置時更願意跟注 3-bet，所以同一位玩家可能顯示出兩個很不同的棄牌率。
- **是誰在 3-bet。** 面對緊的對手，玩家棄牌更多；面對他們知道經常 3-bet 的人，則會更多防守。
- **籌碼深度。** 淺籌碼讓面對 3-bet 時棄牌變得更少見，因為 4-bet 幾乎就是全下。

## 用在自己身上

你自己的 fold to 3-bet 是一個漏洞偵測器。如果它非常高，好的對手可以每次都用任意兩張牌 3-bet 你並獲利。如果非常低，你可能在用不該用的手牌跟注或 4-bet。請拿它與你的 [4-bet%](/zh-hant/blog/4-bet-percentage-poker-stat-explained) 比較，看看你如何運用那些非棄牌的情況。

## 一個提醒

這是一個很少見的情境。你每一百手牌大概開池十五次，而被 3-bet 只發生在其中一小部分，所以建立在 20 或 30 次機會上的數字可能大幅波動。在你認定某位對手棄牌太多之前，請先看[需要多少手牌，你的數據才可靠](/zh-hant/blog/sample-size-reliable-poker-stats)。

## 確保追蹤軟體讀到的是正確的手牌

這裡的每一項數據，都取決於背後的手牌紀錄是否準確。如果你在 CoinPoker 打牌，請在匯入 PokerTracker 4 或 Hold'em Manager 3 之前，先[用 PokerHandsConverter 轉換手牌](/zh-hant)，讓追蹤軟體顯示的數字就是你實際打出來的結果。
