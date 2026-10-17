---
title: "CoinPoker 的 ₮ 符號：USDT 在牌譜中如何呈現"
slug: coinpoker-usdt-currency-in-hand-histories
locale: zh-Hant
translation_of: coinpoker-usdt-currency-in-hand-histories
excerpt: "CoinPoker 以 USDT 計價，並印出不帶貨幣代碼的 ₮ 符號。以下說明這對盲注級別、資金追蹤與你的追蹤軟體意味著什麼。"
meta_title: "CoinPoker 牌譜中的 ₮ 貨幣符號與追蹤軟體 — PokerHandsConverter"
meta_description: "CoinPoker 牌譜中的 ₮（泰達幣）符號是什麼意思、追蹤軟體為何讀錯，以及它如何影響盲注級別與資金數字。"
published: true
published_at: "2026-10-25T09:00:00+00:00"
---

## 你在檔案裡看到什麼

CoinPoker 的現金桌以 USDT（一種穩定幣）計價，金額使用泰達幣符號 **₮**，而且沒有貨幣代碼：

```
CoinPoker Hand #129794100893: NLH (₮0.01/₮0.02) 2026/09/09 12:20:36 CEST
Seat 1: Hero (₮2 in chips)
2e9886c6: posts small blind ₮0.01
```

PokerStars 系列的牌譜使用一般貨幣符號（`$`、`€`），而且通常還有代碼，例如 `($0.01/$0.02 USD)`。追蹤軟體的匯入器是以這些格式為基礎設計的，所以 ₮ 才會造成麻煩。

## 哪裡會出錯

- **盲注級別。** 追蹤軟體從標頭判讀級別。如果它不認得 ₮，就無法判斷你打的是 1 分／2 分，於是依級別的報表可能出錯或是空的。
- **金額欄位。** 獲利、抽水與餘額數字，都取決於金額被讀成一種貨幣。
- **資金追蹤。** 貨幣不對的圖表與成績，很難和其他撲克室比較。

₮ 符號出現在現金桌。在錦標賽中，結構以籌碼表示，籌碼量與盲注行都沒有貨幣。

## USDT 與美元

USDT 的設計是追蹤美元，所以一個 ₮ 大約等於一美元。讓檔案能被讀取的常見做法，是把 ₮ 一對一當成 `$`，並在標頭加上貨幣代碼。這是接近但不精確的近似，不過對追蹤成績來說是合理的選擇。

[PokerHandsConverter](/zh-hant) 正是這樣做：它把整手牌中的 ₮ 轉成 `$`，在標頭加上貨幣代碼，並讓錦標賽的籌碼量保持沒有貨幣符號。轉換同時也會改寫追蹤軟體挑剔的其他欄位，時間戳記的部分請見 [時區問題](/zh-hant/blog/coinpoker-hand-history-timezone)。

## 抽水與費用也是 ₮

摘要行以 ₮ 回報總底池與抽水。某些牌桌上的 **Splash Fee** 行是追蹤軟體看不懂的，也是牌譜被拒絕的常見原因。[修正匯入 CoinPoker 手牌時的「Invalid Pot Size」錯誤](/zh-hant/blog/fix-invalid-pot-size-error-coinpoker-import) 有說明，而 [線上撲克的抽水（Rake）是什麼？它如何影響你的勝率](/zh-hant/blog/what-is-rake-in-online-poker) 介紹了基本概念。

## 下一步

當金額已是追蹤軟體讀得懂的格式，就把檔案匯入。[如何把 CoinPoker 牌譜匯入 PokerTracker 4](/zh-hant/blog/import-coinpoker-hands-pokertracker-4) 說明 PokerTracker 的做法，[Hold'em Manager 3](/zh-hant/blog/import-coinpoker-hands-holdem-manager-3) 也有獨立指南。
