---
title: "修正匯入 CoinPoker 手牌時的「Invalid Pot Size」錯誤"
slug: fix-invalid-pot-size-error-coinpoker-import
locale: zh-Hant
translation_of: fix-invalid-pot-size-error-coinpoker-import
excerpt: "幾乎所有 CoinPoker 匯入時的「Invalid Pot Size」錯誤，都源自同一種摘要行格式。以下是修正方法。"
meta_title: "修正 CoinPoker 匯入的「Invalid Pot Size」錯誤 — PokerHandsConverter"
meta_description: "為何 PokerTracker 4 會以「Invalid Pot Size」錯誤拒絕 CoinPoker 手牌，以及 Splash Fee 摘要行格式是如何造成的。"
published: true
---

## 最常見的 CoinPoker 匯入錯誤

如果 PokerTracker 4 以類似 *「Invalid pot size」* 的訊息拒絕 CoinPoker 的手牌——而且發生在你大部分手牌上，而不只是一兩手——那麼原因是明確且已知的：較新的 CoinPoker 摘要行格式，在抽水之外還多了一項 **Splash Fee**。

## 檔案裡實際是什麼

一般的 CoinPoker 摘要行看起來像這樣：

```
Total pot ₮3.67 | Rake ₮0.18
```

現在有些現金桌會在此之外再加上 Splash Fee：

```
Total pot ₮3.67 | Rake ₮0.18 | Splash Fee ₮0.02
```

PokerTracker 4 完全沒有「Splash Fee」這個概念——而且它出現在該行中，不只是被忽略，還會讓 PT4 根本讀不到 `Rake` 的數字。於是 PT4 把抽水算成 `0`，再拿它去對照自己根據下注行動獨立算出的底池，發現不吻合，就把該手牌判定為無效底池而拒絕匯入。

## 為什麼看起來與級別有關（其實無關）

由於 Splash Fee 在某些牌桌出現得比較頻繁，一開始可能會以為是特定級別的問題——「我的 NL10 手牌都正常，NL2 卻一直失敗」。其實它與級別無關，而是與哪些牌桌剛好收取這筆費用有關。修正必須一視同仁地套用，不論級別，否則同樣的失敗之後會在另一張牌桌上再度出現。

## 修正方法

[PokerHandsConverter](/zh-hant) 會偵測 Splash Fee 的格式，並把它併入 PokerTracker 4 本來就能理解的抽水數字，所以上面那一行會變成：

```
Total pot $3.67 | Rake $0.20
```

這正是 PT4 自己獨立計算所預期的抽水：底池等於各方所收金額加上抽水，帳目平衡、沒有不吻合，手牌也不會被拒絕。如果你今天在某個檔案上看到這個錯誤，把它重新過一遍轉換工具就能解決，完全不需要調整 PokerTracker 4 本身。

## 如果轉換後仍出現底池大小錯誤

還有幾種情況會產生外觀類似的錯誤，值得逐一排除：

- **Run-it-twice（發兩次牌）的牌面。** 這類手牌在轉換時會被標記為警告，而不是被默默猜測處理——請到轉換紀錄中查看該手牌是否有警告。
- **確實損毀的匯出檔。** 很罕見，但如果 CoinPoker 自己的匯出檔在中途被截斷，任何轉換工具都無法還原遺失的資料。
