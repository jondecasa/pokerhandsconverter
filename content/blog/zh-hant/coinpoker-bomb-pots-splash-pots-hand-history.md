---
title: "牌譜中的 CoinPoker 炸彈底池（Bomb Pot）與 Splash 底池"
slug: coinpoker-bomb-pots-splash-pots-hand-history
locale: zh-Hant
translation_of: coinpoker-bomb-pots-splash-pots-hand-history
excerpt: "炸彈底池會跳過翻牌前，Splash 底池會憑空加錢。以下說明兩者在 CoinPoker 牌譜中的樣子，以及為什麼它們會扭曲你的數據。"
meta_title: "CoinPoker 炸彈底池與 Splash 底池在牌譜中的樣子 — PokerHandsConverter"
meta_description: "CoinPoker 的炸彈底池與 Splash 底池在牌譜中如何呈現、兩者有何不同，以及如何避免它們扭曲追蹤軟體的數據。"
published: true
published_at: "2026-10-31T09:00:00+00:00"
---

## 兩種特殊底池，兩種不同效果

CoinPoker 的現金桌有兩個會改變牌局、並在牌譜中留下痕跡的功能。它們很容易搞混。

| | 炸彈底池（Bomb pot） | Splash 底池 |
|---|---|---|
| 錢從哪裡來 | 玩家，以自動底注的方式 | 由 CoinPoker 提供的現金掉落 |
| 翻牌前下注 | 跳過：牌局直接從翻牌開始 | 正常 |
| 公共牌 | 雙牌面 | 單牌面 |

CoinPoker 在自己的說明頁面解釋了這兩者：[什麼是炸彈底池](https://coinpoker.com/help/bomb-pot/) 與 [什麼是 Splash 底池](https://coinpoker.com/help/splash/)。確切金額會依遊戲而不同，請查看這些頁面了解目前的細節。

## 炸彈底池在檔案中的樣子

標頭會標明遊戲類型，而且每位玩家都下底注而不是盲注：

```
CoinPoker Hand #129794200777: NLH BombPot (₮0.01/₮0.02) 2026/09/09 12:45:02 CEST
Hero: posts the ante ₮0.10
070fbf52: posts the ante ₮0.10
...
*** HOLE CARDS ***
*** FLOP *** [Kh 7c 2s]
```

**完全沒有翻牌前行動**：發完牌之後直接進入翻牌。當炸彈底池發兩個牌面時，摘要會寫 `Hand was run with two boards`。

## Splash 底池在檔案中的樣子

Splash 底池像一般牌局一樣進行，只是在盲注之前多一行，顯示被投入的金額：

```
SPLASH dropped ₮0.04
2e9886c6: posts small blind ₮0.01
4f1fcfb2: posts big blind ₮0.02
```

在某些牌桌上，摘要裡的抽水旁邊還會出現 **Splash Fee**，這是匯入被拒絕的常見原因。[修正匯入 CoinPoker 手牌時的「Invalid Pot Size」錯誤](/zh-hant/blog/fix-invalid-pot-size-error-coinpoker-import) 有說明。

## 為什麼它們會扭曲你的數據

- **炸彈底池沒有翻牌前行動。** VPIP 與 PFR 這類數據描述的是翻牌前的選擇，而炸彈底池裡沒有任何選擇。值得檢查你的追蹤軟體如何計算這些手牌，免得你的翻牌前數字被稀釋。
- **底池大小暴增。** 每位玩家的底注或被投入的現金，讓底池比實際的行動看起來更大。這可能讓少數幾手牌的攤牌與勝率數字被拉高。
- **抽水與費用。** Splash 的錢有自己的費用，所以每手牌的抽水並不一致。[線上撲克的抽水（Rake）是什麼？它如何影響你的勝率](/zh-hant/blog/what-is-rake-in-online-poker) 介紹了基本概念。

## 數得出來，才不會漏掉

[PokerHandsConverter](/zh-hant) 會統計它轉換的每個檔案中的 Splash 底池與炸彈底池，讓你知道一段牌局裡有多少手是特殊牌。結果頁面會把這兩個數字與總手牌數並列顯示。

等你的牌譜準備好之後，就匯入它們。[如何把 CoinPoker 牌譜匯入 PokerTracker 4](/zh-hant/blog/import-coinpoker-hands-pokertracker-4) 說明了做法。
